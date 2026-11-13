<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/domain.php';

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/app/views/' . $view . '.php';
}
function get_pref_theme(): string
{
    if (empty($_SESSION['user_id'])) {
        return 'light';
    }
    $stmt = db()->prepare('SELECT theme FROM public.user_preferences WHERE user_id=:id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return (string) ($stmt->fetchColumn() ?: 'light');
}
function common_data(): array
{
    try {
        $theme = get_pref_theme();
    } catch (Throwable) {
        $theme = 'light';
    }
    return ['theme' => $theme, 'user' => current_user(), 'flash' => pop_flash()];
}
function audit_meta(array $values): array
{
    return ['fields' => array_keys($values)];
}

function delete_linked_records(PDO $pdo, string $module, int $id): void
{
    $rules = [
        'farmers' => [
            [
                'harvests',
                'harvest_id',
                'harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id',
                'planting_id IN (SELECT planting_id FROM public.plantings WHERE farm_id IN (SELECT farm_id FROM public.farms WHERE farmer_id=:id))',
            ],
            [
                'plantings',
                'planting_id',
                'planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id',
                'farm_id IN (SELECT farm_id FROM public.farms WHERE farmer_id=:id)',
            ],
            [
                'farms',
                'farm_id',
                'farm_id,farmer_id,farm_name,location,area_hectares,soil_type_id,irrigation_type_id',
                'farmer_id=:id',
            ],
        ],
        'farms' => [
            [
                'harvests',
                'harvest_id',
                'harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id',
                'planting_id IN (SELECT planting_id FROM public.plantings WHERE farm_id=:id)',
            ],
            [
                'plantings',
                'planting_id',
                'planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id',
                'farm_id=:id',
            ],
        ],
        'crops' => [
            [
                'harvests',
                'harvest_id',
                'harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id',
                'planting_id IN (SELECT planting_id FROM public.plantings WHERE crop_id=:id)',
            ],
            [
                'plantings',
                'planting_id',
                'planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id',
                'crop_id=:id',
            ],
        ],
        'plantings' => [
            [
                'harvests',
                'harvest_id',
                'harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id',
                'planting_id=:id',
            ],
        ],
    ];

    foreach ($rules[$module] ?? [] as [$table, $key, $columns, $condition]) {
        $select = $pdo->prepare(
            "SELECT {$columns} FROM public.{$table} WHERE {$condition} FOR UPDATE",
        );
        $select->execute(['id' => $id]);
        $rows = $select->fetchAll();
        foreach ($rows as $row) {
            audit('DELETE', $table, (string) $row[$key], audit_meta($row));
        }
        if ($rows) {
            $delete = $pdo->prepare("DELETE FROM public.{$table} WHERE {$condition}");
            $delete->execute(['id' => $id]);
        }
    }
}

$page = query_string('page', empty($_SESSION['user_id']) ? 'login' : 'dashboard');
try {
    if ($page === 'login') {
        if (!empty($_SESSION['user_id'])) {
            go('dashboard');
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                verify_csrf();
                $email = strtolower(trim(post_string('email')));
                $password = post_string('password');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
                    throw new InvalidArgumentException('Enter a valid email and password.');
                }
                // [LEARNING EXPLANATION] The password is checked against its one-way hash. The role comes from the database record, never from a form field.
                $stmt = db()->prepare(
                    'SELECT user_id,full_name,email,password_hash,role,is_active::text AS is_active,must_change_password,failed_login_count,locked_until FROM public.app_users WHERE lower(email)=:email',
                );
                $stmt->execute(['email' => $email]);
                $account = $stmt->fetch();
                $locked =
                    $account &&
                    $account['locked_until'] &&
                    strtotime((string) $account['locked_until']) > time();
                if (
                    !$account ||
                    $account['is_active'] !== 'true' ||
                    $locked ||
                    !password_verify($password, $account['password_hash'])
                ) {
                    if ($account && $account['is_active'] === 'true' && !$locked) {
                        $fail = (int) $account['failed_login_count'] + 1;
                        $until = $fail >= 5 ? date('c', time() + 900) : null;
                        $up = db()->prepare(
                            'UPDATE public.app_users SET failed_login_count=:n, locked_until=:until WHERE user_id=:id',
                        );
                        $up->execute([
                            'n' => $fail,
                            'until' => $until,
                            'id' => $account['user_id'],
                        ]);
                    }
                    audit('LOGIN_FAILED', 'app_users', null);
                    throw new RuntimeException('Invalid email or password.');
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $account['user_id'];
                $_SESSION['full_name'] = $account['full_name'];
                $_SESSION['role'] = $account['role'];
                $_SESSION['last_activity'] = time();
                csrf_token();
                db_transaction(function (PDO $pdo) use ($account): void {
                    $up = $pdo->prepare(
                        'UPDATE public.app_users SET failed_login_count=0,locked_until=NULL,last_login_at=now() WHERE user_id=:id',
                    );
                    $up->execute(['id' => $account['user_id']]);
                    $pref = $pdo->prepare(
                        'INSERT INTO public.user_preferences(user_id) VALUES (:id) ON CONFLICT (user_id) DO NOTHING',
                    );
                    $pref->execute(['id' => $account['user_id']]);
                    audit('LOGIN', 'app_users', (string) $account['user_id']);
                });
                go($account['must_change_password'] ? 'profile' : 'dashboard');
            } catch (Throwable $exception) {
                $error = safe_error($exception);
            }
        }
        render('login', common_data() + ['error' => $error]);
        exit();
    }

    require_login();
    $who = current_user();
    if ($page === 'freshness') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        echo json_encode(
            [
                'version' => (int) db()
                    ->query(
                        "SELECT COALESCE(MAX(log_id),0) FROM public.audit_logs WHERE action IN ('INSERT','UPDATE','DELETE','ACCOUNT_CHANGE','PASSWORD_CHANGE')",
                    )
                    ->fetchColumn(),
            ],
            JSON_THROW_ON_ERROR,
        );
        exit();
    }
    $stmt = db()->prepare(
        'SELECT must_change_password FROM public.app_users WHERE user_id=:id AND is_active',
    );
    $stmt->execute(['id' => $who['user_id']]);
    $accountState = $stmt->fetch();
    if (!$accountState) {
        $_SESSION = [];
        session_destroy();
        go('login');
    }
    $mustChange = (bool) $accountState['must_change_password'];
    if ($mustChange && !in_array($page, ['profile', 'logout'], true)) {
        flash('warning', 'Change the initial password before using the system.');
        go('profile');
    }

    if ($page === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        audit('LOGOUT', 'app_users', (string) $who['user_id']);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'secure' => $cookie['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        go('login');
    }

    if ($page === 'profile') {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                verify_csrf();
                $theme = post_string('theme');
                $fullName = clean_text('full_name', 100);
                if (!in_array($theme, ['light', 'dark'], true)) {
                    throw new InvalidArgumentException('Choose light or dark mode.');
                }
                $oldPass = post_string('current_password');
                $newPass = post_string('new_password');
                $passwordChanged = false;
                $pdo = db();
                $pdo->beginTransaction();
                $userStmt = $pdo->prepare(
                    'SELECT password_hash,must_change_password FROM public.app_users WHERE user_id=:id FOR UPDATE',
                );
                $userStmt->execute(['id' => $who['user_id']]);
                $account = $userStmt->fetch();
                $nameChange = $pdo->prepare(
                    'UPDATE public.app_users SET full_name=:name WHERE user_id=:id',
                );
                $nameChange->execute(['name' => $fullName, 'id' => $who['user_id']]);
                if ($newPass !== '' || $account['must_change_password']) {
                    if (
                        !$account['must_change_password'] &&
                        !password_verify($oldPass, $account['password_hash'])
                    ) {
                        throw new InvalidArgumentException('Current password is not correct.');
                    }
                    if (strlen($newPass) < 12) {
                        throw new InvalidArgumentException(
                            'Use a new password with at least 12 characters.',
                        );
                    }
                    if (hash_equals($oldPass, $newPass)) {
                        throw new InvalidArgumentException(
                            'Choose a password different from the current password.',
                        );
                    }
                    $change = $pdo->prepare(
                        'UPDATE public.app_users SET password_hash=:hash,must_change_password=FALSE WHERE user_id=:id',
                    );
                    $change->execute([
                        'hash' => password_hash($newPass, PASSWORD_DEFAULT),
                        'id' => $who['user_id'],
                    ]);
                    $passwordChanged = true;
                    audit('PASSWORD_CHANGE', 'app_users', (string) $who['user_id']);
                }
                $pref = $pdo->prepare(
                    'INSERT INTO public.user_preferences(user_id,theme) VALUES (:id,:theme) ON CONFLICT(user_id) DO UPDATE SET theme=EXCLUDED.theme,updated_at=now()',
                );
                $pref->execute(['id' => $who['user_id'], 'theme' => $theme]);
                $pdo->commit();
                $_SESSION['full_name'] = $fullName;
                if ($passwordChanged) {
                    session_regenerate_id(true);
                }
                flash('success', 'Profile settings saved.');
                go('profile');
            } catch (Throwable $exception) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = safe_error($exception);
            }
        }
        render('profile', common_data() + ['error' => $error, 'mustChange' => $mustChange]);
        exit();
    }

    if ($page === 'dashboard') {
        $counts = [];
        foreach (
            [
                'farmers' => 'Farmers',
                'farms' => 'Farms',
                'crops' => 'Crops',
                'plantings' => 'Planting records',
                'harvests' => 'Harvest records',
            ]
            as $table => $label
        ) {
            $counts[] = [
                'label' => $label,
                'value' => (int) db()
                    ->query("SELECT COUNT(*) FROM public.{$table}")
                    ->fetchColumn(),
            ];
        }
        $statuses = db()
            ->query(
                'SELECT ps.status_name,COUNT(p.planting_id)::int AS total FROM public.planting_statuses ps LEFT JOIN public.plantings p USING(status_id) GROUP BY ps.status_id,ps.status_name ORDER BY ps.status_id',
            )
            ->fetchAll();
        $maxStatus = max(1, ...array_map(fn($row) => (int) $row['total'], $statuses));
        $totals = db()
            ->query(
                'SELECT COALESCE(SUM(quantity_kg),0) total_kg,SUM(quantity_kg*selling_price_per_kg) FILTER(WHERE selling_price_per_kg IS NOT NULL) value FROM public.harvests',
            )
            ->fetch();
        $recentPlantings = db()
            ->query(
                'SELECT * FROM (SELECT p.planting_id,fm.farm_name,c.crop_name,ps.status_name,p.planting_date FROM public.plantings p JOIN public.farms fm USING(farm_id) JOIN public.crops c USING(crop_id) JOIN public.planting_statuses ps USING(status_id) ORDER BY p.planting_id DESC LIMIT 6) recent ORDER BY planting_id ASC',
            )
            ->fetchAll();
        $recentHarvests = db()
            ->query(
                'SELECT * FROM (SELECT h.harvest_id,fm.farm_name,c.crop_name,h.harvest_date,h.quantity_kg FROM public.harvests h JOIN public.plantings p USING(planting_id) JOIN public.farms fm USING(farm_id) JOIN public.crops c USING(crop_id) ORDER BY h.harvest_id DESC LIMIT 6) recent ORDER BY harvest_id ASC',
            )
            ->fetchAll();
        render(
            'dashboard',
            common_data() +
                compact(
                    'counts',
                    'statuses',
                    'maxStatus',
                    'totals',
                    'recentPlantings',
                    'recentHarvests',
                ),
        );
        exit();
    }

    if ($page === 'module') {
        $modules = module_definitions();
        $name = query_string('module');
        if (!isset($modules[$name])) {
            throw new NotFoundException('That page was not found.');
        }
        $d = $modules[$name];
        $action = query_string('action', 'list');
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $postAction = post_string('form_action');
            $postId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            if ($postAction === 'delete') {
                if ($who['role'] !== 'Administrator') {
                    throw new DomainException('Only an Administrator may delete records.');
                }
                if (!$postId) {
                    throw new InvalidArgumentException('Choose a record to delete.');
                }
                try {
                    db_transaction(function (PDO $pdo) use ($d, $name, $postId): void {
                        $q = $pdo->prepare(
                            "SELECT {$d['recordSelect']} FROM public.{$d['table']} WHERE {$d['key']}=:id FOR UPDATE",
                        );
                        $q->execute(['id' => $postId]);
                        $old = $q->fetch();
                        if (!$old) {
                            throw new DomainException('That record no longer exists.');
                        }
                        delete_linked_records($pdo, $name, $postId);
                        $del = $pdo->prepare(
                            "DELETE FROM public.{$d['table']} WHERE {$d['key']}=:id",
                        );
                        $del->execute(['id' => $postId]);
                        audit('DELETE', $d['table'], (string) $postId, audit_meta($old));
                    });
                    flash('success', $d['title'] . ' record deleted.');
                    go('module', ['module' => $name]);
                } catch (Throwable $exception) {
                    $error = safe_error($exception);
                    $action = 'list';
                }
            } elseif (in_array($postAction, ['create', 'update'], true)) {
                $values = build_module_values($d);
                $pdo = db();
                if ($postAction === 'update' && $name === 'plantings' && $postId) {
                    $harvestCheck = $pdo->prepare(
                        'SELECT MIN(harvest_date) FROM public.harvests WHERE planting_id=:id',
                    );
                    $harvestCheck->execute(['id' => $postId]);
                    $firstHarvest = $harvestCheck->fetchColumn();
                    if ($firstHarvest && $values['planting_date'] > $firstHarvest) {
                        throw new InvalidArgumentException(
                            'Planting date cannot be after an already recorded harvest.',
                        );
                    }
                }
                db_transaction(function (PDO $pdo) use ($d, $values, $postAction, $postId): void {
                    if ($postAction === 'create') {
                        $cols = array_keys($values);
                        $sql =
                            'INSERT INTO public.' .
                            $d['table'] .
                            ' (' .
                            implode(',', $cols) .
                            ') VALUES (:' .
                            implode(',:', $cols) .
                            ') RETURNING ' .
                            $d['key'];
                        $st = $pdo->prepare($sql);
                        $st->execute($values);
                        $newId = (string) $st->fetchColumn();
                        audit('INSERT', $d['table'], $newId, null, audit_meta($values));
                    } else {
                        if (!$postId) {
                            throw new InvalidArgumentException('Choose a record to update.');
                        }
                        $st = $pdo->prepare(
                            "SELECT {$d['recordSelect']} FROM public.{$d['table']} WHERE {$d['key']}=:id FOR UPDATE",
                        );
                        $st->execute(['id' => $postId]);
                        $old = $st->fetch();
                        if (!$old) {
                            throw new DomainException('That record no longer exists.');
                        }
                        $sets = array_map(fn($col) => $col . '=:' . $col, array_keys($values));
                        $values['id'] = $postId;
                        $up = $pdo->prepare(
                            'UPDATE public.' .
                                $d['table'] .
                                ' SET ' .
                                implode(',', $sets) .
                                ' WHERE ' .
                                $d['key'] .
                                '=:id',
                        );
                        $up->execute($values);
                        audit('UPDATE', $d['table'], (string) $postId, [
                            'changed_fields' => array_keys($values),
                        ]);
                    }
                });
                flash('success', $d['title'] . ' record saved.');
                go('module', ['module' => $name]);
            } else {
                throw new DomainException('This action is not available.');
            }
        }
        if ($action === 'create' || $action === 'edit' || $action === 'view') {
            $record = null;
            if ($action !== 'create') {
                if (!$id) {
                    throw new InvalidArgumentException('Choose a valid record.');
                }
                $q = db()->prepare(
                    "SELECT {$d['recordSelect']} FROM public.{$d['table']} WHERE {$d['key']}=:id",
                );
                $q->execute(['id' => $id]);
                $record = $q->fetch();
                if (!$record) {
                    throw new NotFoundException('That record was not found.');
                }
            }
            if ($action === 'view') {
                $q = db()->prepare(
                    'SELECT ' .
                        $d['select'] .
                        ' FROM ' .
                        $d['from'] .
                        ' WHERE ' .
                        $d['keyExpr'] .
                        '=:id',
                );
                $q->execute(['id' => $id]);
                $record = $q->fetch();
            }
            $fieldOptions = [];
            foreach ($d['fields'] as $field => $definition) {
                if ($definition[1] === 'lookup') {
                    $fieldOptions[$field] = lookup_options($definition[4]);
                }
            }
            render(
                'module_form',
                common_data() +
                    compact('d', 'name', 'action', 'id', 'record', 'fieldOptions', 'error'),
            );
            exit();
        }
        $where = [];
        $params = [];
        $search = trim(query_string('q'));
        if ($search !== '') {
            $parts = [];
            foreach ($d['search'] as $i => $column) {
                $key = 'q' . $i;
                $parts[] = $column . ' ILIKE :' . $key;
                $params[$key] = '%' . $search . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        $activeFilters = [];
        foreach ($d['filters'] as $filter => $config) {
            $value = filter_var($_GET[$filter] ?? null, FILTER_VALIDATE_INT);
            if ($value) {
                $where[] = ($config['column'] ?? $filter) . '=:' . $filter;
                $params[$filter] = $value;
                $activeFilters[$filter] = $value;
            }
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = db()->prepare('SELECT COUNT(*) FROM ' . $d['from'] . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $requestedSize = filter_var($_GET['per_page'] ?? 20, FILTER_VALIDATE_INT);
        $perPage = in_array($requestedSize, [10, 20, 50], true) ? $requestedSize : 20;
        $pages = max(1, (int) ceil($total / $perPage));
        $requestedPage = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT);
        $currentPage = min($pages, max(1, $requestedPage ?: 1));
        $offset = ($currentPage - 1) * $perPage;
        $stmt = db()->prepare(
            'SELECT ' .
                $d['select'] .
                ' FROM ' .
                $d['from'] .
                $whereSql .
                ' ORDER BY ' .
                $d['keyExpr'] .
                ' ASC LIMIT :lim OFFSET :off',
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $filterOptions = [];
        foreach ($d['filters'] as $key => $config) {
            $filterOptions[$key] = lookup_options($config['query']);
        }
        render(
            'module_list',
            common_data() +
                compact(
                    'd',
                    'name',
                    'rows',
                    'search',
                    'total',
                    'perPage',
                    'pages',
                    'currentPage',
                    'offset',
                    'filterOptions',
                    'activeFilters',
                    'error',
                ),
        );
        exit();
    }

    if ($page === 'reports') {
        $start = trim(query_string('start'));
        $end = trim(query_string('end'));
        if (
            ($start !== '' && !valid_date($start)) ||
            ($end !== '' && !valid_date($end)) ||
            ($start !== '' && $end !== '' && $start > $end)
        ) {
            throw new InvalidArgumentException('Choose a valid date range.');
        }
        $plantingSql =
            'SELECT p.planting_id,fm.farm_name,fr.first_name||\' \'||fr.last_name owner_name,c.crop_name,c.variety,p.planting_date,p.area_planted,ps.status_name FROM public.plantings p JOIN public.farms fm USING(farm_id) JOIN public.farmers fr USING(farmer_id) JOIN public.crops c USING(crop_id) JOIN public.planting_statuses ps USING(status_id)';
        $plantingWhere = [];
        $plantingParams = [];
        if ($start !== '') {
            $plantingWhere[] = 'p.planting_date>=CAST(:ps_start AS date)';
            $plantingParams['ps_start'] = $start;
        }
        if ($end !== '') {
            $plantingWhere[] = 'p.planting_date<=CAST(:ps_end AS date)';
            $plantingParams['ps_end'] = $end;
        }
        if ($plantingWhere) {
            $plantingSql .= ' WHERE ' . implode(' AND ', $plantingWhere);
        }
        $plantingSql .= ' ORDER BY p.planting_id ASC';
        $plantings = db()->prepare($plantingSql);
        $plantings->execute($plantingParams);
        $plantingRows = $plantings->fetchAll();
        $harvestSql =
            'SELECT h.harvest_id,h.harvest_date,fm.farm_name,c.crop_name,c.variety,h.quantity_kg,h.selling_price_per_kg,(h.quantity_kg*h.selling_price_per_kg) estimated_value,q.option_label quality_name,r.option_label remark_name FROM public.harvests h JOIN public.plantings p USING(planting_id) JOIN public.farms fm USING(farm_id) JOIN public.crops c USING(crop_id) JOIN public.harvest_options q ON q.option_id=h.quality_option_id AND q.option_type=\'grade\' JOIN public.harvest_options r ON r.option_id=h.remark_option_id AND r.option_type=\'remark\'';
        $harvestWhere = [];
        $harvestParams = [];
        if ($start !== '') {
            $harvestWhere[] = 'h.harvest_date>=CAST(:hs_start AS date)';
            $harvestParams['hs_start'] = $start;
        }
        if ($end !== '') {
            $harvestWhere[] = 'h.harvest_date<=CAST(:hs_end AS date)';
            $harvestParams['hs_end'] = $end;
        }
        if ($harvestWhere) {
            $harvestSql .= ' WHERE ' . implode(' AND ', $harvestWhere);
        }
        $harvestSql .= ' ORDER BY h.harvest_id ASC';
        $harvestStmt = db()->prepare($harvestSql);
        $harvestStmt->execute($harvestParams);
        $harvestRows = $harvestStmt->fetchAll();
        $farmers = db()
            ->query(
                'SELECT fr.farmer_id,fr.first_name,fr.last_name,fr.address,COUNT(f.farm_id)::int farm_count,STRING_AGG(f.farm_name,\', \' ORDER BY f.farm_name) farm_names FROM public.farmers fr LEFT JOIN public.farms f USING(farmer_id) GROUP BY fr.farmer_id ORDER BY fr.farmer_id ASC',
            )
            ->fetchAll();
        $byStatus = db()
            ->query(
                'SELECT ps.status_id,ps.status_name,COUNT(p.planting_id)::int total FROM public.planting_statuses ps LEFT JOIN public.plantings p USING(status_id) GROUP BY ps.status_id,ps.status_name ORDER BY ps.status_id ASC',
            )
            ->fetchAll();
        $harvestTotal = array_sum(array_map(fn($r) => (float) $r['quantity_kg'], $harvestRows));
        $hasPricedHarvest = (bool) array_filter(
            $harvestRows,
            fn($r) => $r['selling_price_per_kg'] !== null,
        );
        $harvestValue = $hasPricedHarvest
            ? array_sum(
                array_map(
                    fn($r) => $r['estimated_value'] === null ? 0 : (float) $r['estimated_value'],
                    $harvestRows,
                ),
            )
            : null;
        if (query_string('export') === 'harvests') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="ais-harvest-report.csv"');
            $out = fopen('php://output', 'w');
            $headers = array_keys(
                $harvestRows[0] ?? [
                    'harvest_id' => '',
                    'harvest_date' => '',
                    'farm_name' => '',
                    'crop_name' => '',
                    'variety' => '',
                    'quantity_kg' => '',
                    'selling_price_per_kg' => '',
                    'estimated_value' => '',
                    'quality_name' => '',
                    'remark_name' => '',
                ],
            );
            fputcsv($out, $headers);
            foreach ($harvestRows as $row) {
                fputcsv(
                    $out,
                    array_map(
                        fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "\t" . $v : $v,
                        $row,
                    ),
                );
            }
            fclose($out);
            exit();
        }
        render(
            'reports',
            common_data() +
                compact(
                    'start',
                    'end',
                    'plantingRows',
                    'harvestRows',
                    'farmers',
                    'byStatus',
                    'harvestTotal',
                    'harvestValue',
                ),
        );
        exit();
    }

    if ($page === 'users') {
        require_admin();
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $form = post_string('form_action');
            if ($form === 'create') {
                $full = clean_text('full_name', 100);
                $email = strtolower(trim(post_string('email')));
                $password = post_string('password');
                $role = post_string('role');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                    throw new InvalidArgumentException('Enter a valid email.');
                }
                if (strlen($password) < 12) {
                    throw new InvalidArgumentException(
                        'Temporary password must be at least 12 characters.',
                    );
                }
                if (!in_array($role, ['Administrator', 'Staff'], true)) {
                    throw new InvalidArgumentException('Choose a valid role.');
                }
                db_transaction(function (PDO $pdo) use ($full, $email, $password, $role): void {
                    $st = $pdo->prepare(
                        'INSERT INTO public.app_users(full_name,email,password_hash,role,must_change_password) VALUES (:name,:email,:hash,:role,TRUE) RETURNING user_id',
                    );
                    $st->execute([
                        'name' => $full,
                        'email' => $email,
                        'hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                    ]);
                    $id = (string) $st->fetchColumn();
                    $pref = $pdo->prepare(
                        'INSERT INTO public.user_preferences(user_id) VALUES (:id)',
                    );
                    $pref->execute(['id' => $id]);
                    audit('ACCOUNT_CHANGE', 'app_users', $id, null, [
                        'role' => $role,
                        'active' => true,
                    ]);
                });
                flash(
                    'success',
                    'Account created. The user must change the temporary password at first login.',
                );
                go('users');
            }
            if ($form === 'toggle') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id === $who['user_id']) {
                    throw new InvalidArgumentException('You cannot disable your own account.');
                }
                db_transaction(function (PDO $pdo) use ($id): void {
                    $st = $pdo->prepare(
                        'SELECT role,is_active::text AS is_active FROM public.app_users WHERE user_id=:id FOR UPDATE',
                    );
                    $st->execute(['id' => $id]);
                    $old = $st->fetch();
                    if (!$old) {
                        throw new NotFoundException('Account not found.');
                    }
                    $active = $old['is_active'] !== 'true';
                    if (!$active && $old['role'] === 'Administrator') {
                        $admins = (int) $pdo
                            ->query(
                                "SELECT COUNT(*) FROM public.app_users WHERE role='Administrator' AND is_active",
                            )
                            ->fetchColumn();
                        if ($admins <= 1) {
                            throw new DomainException(
                                'At least one active Administrator account is required.',
                            );
                        }
                    }
                    $up = $pdo->prepare(
                        'UPDATE public.app_users SET is_active = NOT is_active WHERE user_id=:id',
                    );
                    $up->execute(['id' => $id]);
                    audit('ACCOUNT_CHANGE', 'app_users', (string) $id, null, ['active' => $active]);
                });
                flash('success', 'Account status updated.');
                go('users');
            }
            if ($form === 'delete') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id === $who['user_id']) {
                    throw new InvalidArgumentException('You cannot delete your own account.');
                }
                db_transaction(function (PDO $pdo) use ($id): void {
                    $st = $pdo->prepare(
                        'SELECT role,is_active::text AS is_active FROM public.app_users WHERE user_id=:id FOR UPDATE',
                    );
                    $st->execute(['id' => $id]);
                    $old = $st->fetch();
                    if (!$old) {
                        throw new NotFoundException('Account not found.');
                    }
                    if ($old['role'] === 'Administrator' && $old['is_active'] === 'true') {
                        $admins = (int) $pdo
                            ->query(
                                "SELECT COUNT(*) FROM public.app_users WHERE role='Administrator' AND is_active",
                            )
                            ->fetchColumn();
                        if ($admins <= 1) {
                            throw new DomainException(
                                'At least one active Administrator account is required.',
                            );
                        }
                    }
                    $up = $pdo->prepare('DELETE FROM public.app_users WHERE user_id=:id');
                    $up->execute(['id' => $id]);
                    audit('ACCOUNT_CHANGE', 'app_users', (string) $id, ['deleted' => true], null);
                });
                flash('success', 'Account deleted.');
                go('users');
            }
        }
        $rows = db()
            ->query(
                'SELECT user_id,full_name,email,role,is_active::text AS is_active,last_login_at,created_at FROM public.app_users ORDER BY user_id ASC',
            )
            ->fetchAll();
        render('users', common_data() + compact('rows', 'error'));
        exit();
    }

    if ($page === 'lookups') {
        require_admin();
        $sets = [
            'crop_category' => [
                'crop_options',
                'option_id',
                'option_label',
                'Crop categories',
                'category',
            ],
            'season' => ['crop_options', 'option_id', 'option_label', 'Growing seasons', 'season'],
            'soil' => ['soil_types', 'soil_type_id', 'soil_type_name', 'Soil types', null],
            'irrigation' => [
                'irrigation_types',
                'irrigation_type_id',
                'irrigation_type_name',
                'Irrigation types',
                null,
            ],
            'status' => [
                'planting_statuses',
                'status_id',
                'status_name',
                'Planting statuses',
                null,
            ],
            'grade' => ['harvest_options', 'option_id', 'option_label', 'Harvest grades', 'grade'],
            'remark' => [
                'harvest_options',
                'option_id',
                'option_label',
                'Harvest remarks',
                'remark',
            ],
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $key = post_string('set');
            if (!isset($sets[$key])) {
                throw new InvalidArgumentException('Choose a valid lookup group.');
            }
            [$table, $idCol, $labelCol, $title, $kind] = $sets[$key];
            $label = clean_text('label', 100);
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $field = $kind === null ? $labelCol : 'option_label';
            $whereKind = $kind === null ? '' : ' AND option_type=:kind';
            if (post_string('form_action') === 'deactivate') {
                if (!$id) {
                    throw new InvalidArgumentException('Choose a lookup value.');
                }
                db_transaction(function (PDO $pdo) use (
                    $table,
                    $idCol,
                    $whereKind,
                    $kind,
                    $id,
                ): void {
                    $up = $pdo->prepare(
                        "UPDATE public.{$table} SET is_active=FALSE WHERE {$idCol}=:id" .
                            $whereKind,
                    );
                    $p = ['id' => $id];
                    if ($kind !== null) {
                        $p['kind'] = $kind;
                    }
                    $up->execute($p);
                    audit('UPDATE', $table, (string) $id, null, ['fields' => ['is_active']]);
                });
                flash('success', 'Lookup value deactivated.');
                go('lookups');
            }
            if ($id) {
                $sql = "UPDATE public.{$table} SET {$field}=:label WHERE {$idCol}=:id" . $whereKind;
                $p = ['label' => $label, 'id' => $id];
                if ($kind !== null) {
                    $p['kind'] = $kind;
                }
                db_transaction(function (PDO $pdo) use ($sql, $p, $table, $id): void {
                    $pdo->prepare($sql)->execute($p);
                    audit('UPDATE', $table, (string) $id, null, ['fields' => ['label']]);
                });
            } else {
                $cols =
                    $kind === null ? "{$labelCol},is_active" : "option_type,{$labelCol},is_active";
                $sql =
                    "INSERT INTO public.{$table}({$cols}) VALUES (" .
                    ($kind === null ? ':label,TRUE' : ':kind,:label,TRUE') .
                    ") RETURNING {$idCol}";
                $p = ['label' => $label];
                if ($kind !== null) {
                    $p['kind'] = $kind;
                }
                db_transaction(function (PDO $pdo) use ($sql, $p, $table): void {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($p);
                    audit('INSERT', $table, (string) $stmt->fetchColumn(), null, [
                        'fields' => ['label'],
                    ]);
                });
            }
            flash('success', 'Lookup value saved.');
            go('lookups');
        }
        $data = [];
        foreach ($sets as $key => [$table, $idCol, $labelCol, $title, $kind]) {
            $sql =
                "SELECT {$idCol} id,{$labelCol} label,is_active FROM public.{$table}" .
                ($kind === null ? '' : " WHERE option_type='" . $kind . "'") .
                ' ORDER BY ' .
                $idCol;
            $data[$key] = db()->query($sql)->fetchAll();
        }
        render('lookups', common_data() + compact('data', 'sets'));
        exit();
    }

    if ($page === 'audit') {
        require_admin();
        $rows = db()
            ->query(
                'SELECT l.log_id,l.created_at,u.full_name,l.action,l.entity_table,l.record_pk,l.old_values,l.new_values FROM public.audit_logs l LEFT JOIN public.app_users u ON u.user_id=l.actor_user_id ORDER BY l.log_id ASC LIMIT 200',
            )
            ->fetchAll();
        render('audit', common_data() + compact('rows'));
        exit();
    }

    throw new NotFoundException('That page was not found.');
} catch (Throwable $exception) {
    $message = safe_error($exception);
    $code = http_response_code();
    if ($code < 400) {
        http_response_code(
            $exception instanceof AccessDeniedException
                ? 403
                : ($exception instanceof NotFoundException
                    ? 404
                    : ($exception instanceof InvalidArgumentException ||
                    $exception instanceof DomainException
                        ? 422
                        : 500)),
        );
    }
    render('error', common_data() + ['message' => $message]);
}
