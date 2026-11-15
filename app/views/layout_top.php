<?php
$pageTitle = $pageTitle ?? 'Agriculture Information System';
$navPage = query_string('page', 'dashboard');
$navModule = query_string('module');
$dataVersion = 0;
try {
    $dataVersion = (int) db()
        ->query(
            "SELECT COALESCE(MAX(log_id),0) FROM public.audit_logs WHERE action IN ('INSERT','UPDATE','DELETE','ACCOUNT_CHANGE','PASSWORD_CHANGE')",
        )
        ->fetchColumn();
} catch (Throwable) {
    /* Keep the friendly error page renderable when the database is down. */
}
?>
<!doctype html>
<html lang="en" data-theme="<?= e($theme ?? 'light') ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#164b35"><link rel="manifest" href="manifest.webmanifest">
    <title><?= e($pageTitle) ?> · AIS</title><link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-version="<?= $dataVersion ?>">
<div class="app-shell" id="app-shell">
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">A</span><span class="brand-copy"><strong>Agri<span>Culture</span></strong><small>Information System</small></span></a>
    <button class="sidebar-collapse" type="button" id="sidebar-toggle" aria-label="Collapse navigation" aria-expanded="true"><span class="sidebar-toggle-arrow" aria-hidden="true">‹</span><span class="sidebar-toggle-leaf" aria-hidden="true">❧</span></button>
    <nav class="nav-list">
        <a class="nav-link <?= $navPage === 'dashboard'
            ? 'active'
            : '' ?>" href="index.php"><span class="nav-icon">◫</span><span>Overview</span></a>
        <p class="nav-caption">OPERATIONS</p>
        <?php foreach (
            [
                'farmers' => 'Farmers',
                'farms' => 'Farms',
                'crops' => 'Crops',
                'plantings' => 'Plantings',
                'harvests' => 'Harvests',
            ]
            as $key => $label
        ): ?>
            <a class="nav-link <?= $navPage === 'module' && $navModule === $key
                ? 'active'
                : '' ?>" href="<?= e($key) ?>.php"><span class="nav-icon"><?= [
    'farmers' => '♧',
    'farms' => '▧',
    'crops' => '❋',
    'plantings' => '⌁',
    'harvests' => '◉',
][$key] ?></span><span><?= $label ?></span></a>
        <?php endforeach; ?>
        <p class="nav-caption">INSIGHTS</p>
        <a class="nav-link <?= $navPage === 'reports'
            ? 'active'
            : '' ?>" href="reports.php"><span class="nav-icon">▤</span><span>Reports</span></a>
        <?php if (
            ($user['role'] ?? '') ===
            'Administrator'
        ): ?><p class="nav-caption">ADMINISTRATION</p>
            <a class="nav-link <?= $navPage === 'users'
                ? 'active'
                : '' ?>" href="users.php"><span class="nav-icon">♙</span><span>Accounts</span></a>
            <a class="nav-link <?= $navPage === 'lookups'
                ? 'active'
                : '' ?>" href="lookups.php"><span class="nav-icon">☷</span><span>Lookup values</span></a>
            <a class="nav-link <?= $navPage === 'audit'
                ? 'active'
                : '' ?>" href="activity.php"><span class="nav-icon">◷</span><span>Activity log</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
        <a class="nav-link <?= $navPage === 'profile'
            ? 'active'
            : '' ?>" href="profile.php"><span class="nav-icon">⚙</span><span>My profile</span></a>
        <div class="account-mini"><span class="avatar"><?= e(
            mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1)),
        ) ?></span><span class="account-name"><strong><?= e(
    $user['name'] ?? '',
) ?></strong><small><?= e($user['role'] ?? '') ?></small></span>
            <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(
                csrf_token(),
            ) ?>"><button class="icon-button logout-button" type="submit" aria-label="Sign out" title="Sign out">↗</button></form>
        </div>
    </div>
</aside>
<button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Close navigation"></button>
<main class="main-area">
    <header class="topbar"><button class="mobile-menu" type="button" id="mobile-menu" aria-label="Open navigation" aria-expanded="false">☰</button><div class="breadcrumbs"><strong><?= e(
        $pageTitle,
    ) ?></strong></div><div class="topbar-right"><span class="live-pill"><i></i> Secure workspace</span><span class="top-avatar"><?= e(
    mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1)),
) ?></span></div></header>
    <section class="page-content">
        <?php if (!empty($flash)): ?><div class="alert alert-<?= e(
    $flash['kind'],
) ?>" role="status"><span><?= e(
    $flash['message'],
) ?></span><button type="button" class="alert-close" aria-label="Dismiss message">×</button></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-error" role="alert"><span><?= e(
    $error,
) ?></span><button type="button" class="alert-close" aria-label="Dismiss message">×</button></div><?php endif; ?>
        <div class="alert alert-warning refresh-notice" id="refresh-notice" role="status" hidden>New data is available. <button class="refresh-button" type="button" data-refresh>Refresh page</button><button type="button" class="alert-close" aria-label="Dismiss message">×</button></div>
