<?php

declare(strict_types=1);

// Beginner Note: This file starts the secure session and shares small helpers with every page.
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');

function load_local_env(): void
{
    $file = dirname(__DIR__) . '/.env';
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}
load_local_env();

$privateLogDirectory = dirname(__DIR__) . '/storage/logs';
if (getenv('APP_ENV') === 'production') {
    ini_set('error_log', 'php://stderr');
} elseif (is_dir($privateLogDirectory) && is_writable($privateLogDirectory)) {
    ini_set('error_log', $privateLogDirectory . '/application.log');
}

class AccessDeniedException extends DomainException {}
class NotFoundException extends RuntimeException {}

$trustedProxy = getenv('APP_TRUSTED_PROXY') === 'true';
$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    ($trustedProxy && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (PHP_SAPI !== 'cli' && getenv('APP_ENV') === 'production' && !$isHttps) {
    $appUrl = getenv('APP_URL') ?: '';
    if (str_starts_with($appUrl, 'https://')) {
        header('Location: ' . rtrim($appUrl, '/') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 308);
        exit();
    }
    http_response_code(400);
    exit('HTTPS is required for this application.');
}
$secureCookie = getenv('APP_SESSION_SECURE') === 'true' || getenv('APP_ENV') === 'production';
session_name('ais_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie && $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// [LEARNING EXPLANATION] The server stores the session ID in a secure cookie, while the role and user ID stay on the server. Idle expiry limits how long an unattended sign-in remains active.
$now = time();
if (
    !empty($_SESSION['user_id']) &&
    isset($_SESSION['last_activity']) &&
    $now - (int) $_SESSION['last_activity'] > 1800
) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['flash'] = [
        'kind' => 'warning',
        'message' => 'Your session expired. Sign in again to continue.',
    ];
} elseif (!empty($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = $now;
}
if ($isHttps && getenv('APP_ENV') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header(
    "Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
);
if (!empty($_SESSION['user_id'])) {
    header('Cache-Control: private, no-store, max-age=0');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    // [LEARNING EXPLANATION] PDO is PHP's database connection layer. Environment variables keep the host and password out of public files; TLS protects the connection while data travels to PostgreSQL.
    $host = getenv('SUPABASE_DB_HOST');
    $user = getenv('SUPABASE_DB_USER');
    $password = getenv('SUPABASE_DB_PASSWORD');
    if (!$host || !$user || !$password) {
        throw new RuntimeException('Database connection is not configured.');
    }
    $port = getenv('SUPABASE_DB_PORT') ?: '5432';
    $name = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $mode = getenv('SUPABASE_DB_SSLMODE') ?: 'verify-full';
    $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$mode}";
    $cert = getenv('SUPABASE_DB_SSLROOTCERT');
    if ($cert) {
        $dsn .= ';sslrootcert=' . $cert;
    }
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function h(string $text): string
{
    return e($text);
}
function query_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) || is_numeric($value) ? (string) $value : $default;
}
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Please submit a valid form value.');
    }
    return $value;
}
function go(string $page, array $params = []): never
{
    $routeMap = [
        'login' => 'login.php',
        'dashboard' => 'index.php',
        'profile' => 'profile.php',
        'reports' => 'reports.php',
        'users' => 'users.php',
        'lookups' => 'lookups.php',
        'audit' => 'activity.php',
    ];
    if ($page === 'module') {
        $module = (string) ($params['module'] ?? '');
        $route = in_array($module, ['farmers', 'farms', 'crops', 'plantings', 'harvests'], true)
            ? $module . '.php'
            : 'index.php';
        unset($params['module']);
    } else {
        $route = $routeMap[$page] ?? 'index.php';
    }
    $query = $params ? '?' . http_build_query($params) : '';
    header('Location: ' . $route . $query);
    exit();
}
function flash(string $kind, string $message): void
{
    $_SESSION['flash'] = ['kind' => $kind, 'message' => $message];
}
function pop_flash(): ?array
{
    $value = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $value;
}
function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}
function verify_csrf(): void
{
    // [LEARNING EXPLANATION] A session CSRF token proves that a submitted change form came from this signed-in browser session.
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        throw new RuntimeException('This form expired. Refresh the page and try again.');
    }
}
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        go('login');
    }
}
// [LEARNING EXPLANATION] Authorization checks what a signed-in person may do. This server check protects direct URLs and crafted requests even when a link is hidden.
function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'Administrator') {
        http_response_code(403);
        throw new AccessDeniedException('You do not have permission to open this page.');
    }
}
function current_user(): array
{
    return [
        'user_id' => (int) ($_SESSION['user_id'] ?? 0),
        'name' => (string) ($_SESSION['full_name'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
    ];
}
function audit(
    string $action,
    string $entity,
    ?string $key = null,
    ?array $old = null,
    ?array $new = null,
): void {
    // [LEARNING EXPLANATION] Prepared statements send values separately from SQL text. This keeps typed-in text from becoming part of a command.
    $stmt = db()->prepare(
        'INSERT INTO public.audit_logs(actor_user_id, action, entity_table, record_pk, old_values, new_values) VALUES (:actor,:action,:entity,:key,CAST(:old AS jsonb),CAST(:new AS jsonb))',
    );
    $stmt->execute([
        'actor' => $_SESSION['user_id'] ?? null,
        'action' => $action,
        'entity' => $entity,
        'key' => $key,
        'old' => $old ? json_encode($old, JSON_THROW_ON_ERROR) : null,
        'new' => $new ? json_encode($new, JSON_THROW_ON_ERROR) : null,
    ]);
}
function db_transaction(callable $operation): mixed
{
    // [LEARNING EXPLANATION] A transaction groups the record change and its audit entry. Commit saves both; rollback undoes both if either step fails.
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $operation($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
function valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}
function clean_text(string $name, int $max, bool $required = true): string
{
    $value = trim(post_string($name));
    if (
        ($required && $value === '') ||
        mb_strlen($value) > $max ||
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)
    ) {
        throw new InvalidArgumentException("Please enter a valid {$name}.");
    }
    return $value;
}
function clean_number(
    string $name,
    bool $required = true,
    bool $positive = false,
    bool $integer = false,
): int|float|null {
    $raw = trim(post_string($name));
    if ($raw === '' && !$required) {
        return null;
    }
    if (!preg_match($integer ? '/^\d+$/' : '/^\d+(?:\.\d{1,2})?$/', $raw)) {
        throw new InvalidArgumentException("Please enter a valid number for {$name}.");
    }
    $number = $integer ? (int) $raw : (float) $raw;
    if ($positive ? $number <= 0 : $number < 0) {
        throw new InvalidArgumentException("The value for {$name} is outside the allowed range.");
    }
    return $number;
}
function date_value(string $name): string
{
    $value = trim(post_string($name));
    if (!valid_date($value)) {
        throw new InvalidArgumentException("Please enter a valid {$name}.");
    }
    return $value;
}
function safe_error(Throwable $error): string
{
    if ($error instanceof PDOException) {
        error_log($error->getMessage());
        if ((string) $error->getCode() === '23503') {
            return 'A related record is no longer available. Refresh the page and choose an available option.';
        }
        if ((string) $error->getCode() === '23505') {
            return 'A record with those details already exists.';
        }
        if ((string) $error->getCode() === '23514') {
            return 'One or more values do not meet the database rules.';
        }
        return 'The database could not complete this request. Check the connection and try again.';
    }
    if (
        $error instanceof InvalidArgumentException ||
        $error instanceof DomainException ||
        $error instanceof RuntimeException
    ) {
        return $error->getMessage();
    }
    error_log($error->getMessage());
    return 'The request could not be completed. Check the connection and try again.';
}
