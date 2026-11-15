<?php
$pageTitle = 'Activity log';
require __DIR__ . '/layout_top.php';

$entityLabels = [
    'app_users' => 'User account',
    'farmers' => 'Farmer record',
    'farms' => 'Farm record',
    'crops' => 'Crop record',
    'plantings' => 'Planting record',
    'harvests' => 'Harvest record',
];
$fieldLabels = [
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'full_name' => 'Name',
    'contact_number' => 'Contact number',
    'date_registered' => 'Registration date',
    'farmer_id' => 'Farmer',
    'farm_id' => 'Farm',
    'farm_name' => 'Farm name',
    'area_hectares' => 'Farm size',
    'crop_id' => 'Crop',
    'crop_name' => 'Crop name',
    'variety' => 'Variety',
    'category_option_id' => 'Category',
    'growing_period_days' => 'Growing days',
    'season_option_id' => 'Season',
    'planting_date' => 'Planting date',
    'area_planted' => 'Planted area',
    'seed_quantity' => 'Seed quantity',
    'expected_harvest_date' => 'Expected harvest date',
    'status_id' => 'Status',
    'harvest_date' => 'Harvest date',
    'quantity_kg' => 'Harvest quantity',
    'selling_price_per_kg' => 'Selling price',
    'quality_option_id' => 'Quality',
    'remark_option_id' => 'Remarks',
    'role' => 'Role',
    'is_active' => 'Access status',
    'active' => 'Access status',
    'label' => 'Lookup label',
];
$actionLabels = [
    'INSERT' => 'Added',
    'UPDATE' => 'Updated',
    'DELETE' => 'Deleted',
    'ACCOUNT_CHANGE' => 'Account updated',
    'LOGIN' => 'Signed in',
    'LOGIN_FAILED' => 'Sign-in failed',
    'LOGOUT' => 'Signed out',
    'PASSWORD_CHANGE' => 'Password changed',
];
$sensitiveFields = ['password', 'password_hash', 'token', 'csrf_token', 'secret'];
$entityLabel = static function (string $table) use ($entityLabels): string {
    if (isset($entityLabels[$table])) {
        return $entityLabels[$table];
    }
    if (str_ends_with($table, '_options') || str_ends_with($table, '_types')) {
        return 'Lookup value';
    }
    return ucwords(str_replace('_', ' ', $table)) . ' record';
};
$fieldLabel = static function (string $field) use ($fieldLabels): string {
    return $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
};
$summaryFor = static function (array $row) use (
    $actionLabels,
    $entityLabel,
    $fieldLabel,
    $sensitiveFields,
): string {
    $action = (string) $row['action'];
    $entity = $entityLabel((string) $row['entity_table']);
    if (in_array($action, ['LOGIN', 'LOGIN_FAILED', 'LOGOUT', 'PASSWORD_CHANGE'], true)) {
        return match ($action) {
            'LOGIN' => 'Successful sign-in.',
            'LOGIN_FAILED' => 'Unsuccessful sign-in attempt.',
            'LOGOUT' => 'Signed out.',
            'PASSWORD_CHANGE' => 'Password was changed.',
        };
    }

    $data = $row['new_values'] ?: $row['old_values'];
    if (is_string($data)) {
        $data = json_decode($data, true);
    }
    if (is_object($data)) {
        $data = (array) $data;
    }
    $fields = [];
    if (is_array($data)) {
        $fields = $data['changed_fields'] ?? $data['fields'] ?? array_keys($data);
        if (!is_array($fields)) {
            $fields = [$fields];
        }
    }
    $fields = array_values(
        array_unique(
            array_filter(
                array_map('strval', $fields),
                static fn(string $field): bool => !in_array(strtolower($field), $sensitiveFields, true),
            ),
        ),
    );

    if ($action === 'ACCOUNT_CHANGE') {
        $label = 'Updated account';
    } else {
        $label = ($actionLabels[$action] ?? 'Changed') . ' ' . strtolower($entity);
    }
    if (!$fields) {
        return $label . '.';
    }
    $fieldNames = array_map($fieldLabel, $fields);
    return $label . ': ' . implode(', ', $fieldNames) . '.';
};
?>

<section class="page-heading">
    <div>
        <div class="eyebrow">ADMINISTRATION · AUDIT</div>
        <h1>Activity log</h1>
        <p>Recent account and record changes, with sensitive values excluded.</p>
    </div>
</section>

<section class="panel table-panel">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date and time</th>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Affected item</th>
                    <th>Change summary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(date('M j, Y g:i A', strtotime($row['created_at']))) ?></td>
                        <td><?= e($row['full_name'] ?? 'System') ?></td>
                        <td><?= e($actionLabels[$row['action']] ?? ucwords(str_replace('_', ' ', strtolower($row['action'])))) ?></td>
                        <td><?= e($entityLabel((string) $row['entity_table'])) ?><?= $row['record_pk'] !== null ? ' #' . e($row['record_pk']) : '' ?></td>
                        <td class="audit-data"><?= e($summaryFor($row)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$rows): ?>
            <div class="empty-state table-empty"><strong>No activity recorded yet</strong></div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php'; ?>
