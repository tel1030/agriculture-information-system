<?php
$pageTitle = $pageTitle ?? 'Agriculture Information System';
$navPage = query_string('page', 'dashboard');
$navModule = query_string('module');
$navIcons = [
    'overview' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    'farmers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1.5a7 7 0 0 1 14 0V20"/></svg>',
    'farms' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10 12 4l9 6v10H3z"/><path d="M9 20v-6h6v6M3 10h18"/></svg>',
    'crops' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21V9"/><path d="M12 14c-5 0-8-3-8-8 5 0 8 3 8 8ZM12 11c0-5 3-8 8-8 0 5-3 8-8 8Z"/></svg>',
    'plantings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21V11"/><path d="M12 15c-4 0-7-2.5-7-6 4 0 7 2 7 6ZM12 11c0-4 3-7 7-7 0 4-3 7-7 7Z"/><path d="M8 21h8"/></svg>',
    'harvests' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16l-2 10H6L4 10Z"/><path d="M3 10c1-3 3-4 5-4 1-3 3-4 5-4s4 1 5 4c2 0 3 1 3 4M8 13v4m4-4v4m4-4v4"/></svg>',
    'reports' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V12m5 8V5m5 15v-9m5 9V3"/></svg>',
    'accounts' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1m1-14a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v1"/></svg>',
    'audit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.2.9-1.2 2.1-1.4-.6a8 8 0 0 1-1.6.9l-.2 1.5h-2.4l-.2-1.5a8 8 0 0 1-1.6-.9l-1.4.6-1.2-2.1 1.2-.9a7 7 0 0 1 0-1.8l-1.2-.9 1.2-2.1 1.4.6a8 8 0 0 1 1.6-.9l.2-1.5h2.4l.2 1.5a8 8 0 0 1 1.6.9l1.4-.6 1.2 2.1-1.2.9a7 7 0 0 1 0 1.8Z"/></svg>',
];
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
    <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"><img src="assets/images/tree-ring-logo.png" alt=""></span><span class="brand-copy"><strong>Agree<span>Culture</span></strong><small>Information System</small></span></a>
    <button class="sidebar-collapse" type="button" id="sidebar-toggle" aria-label="Collapse navigation" aria-expanded="true"><svg class="sidebar-toggle-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5-7 7 7 7"/></svg><svg class="sidebar-toggle-leaf" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4C11 4 5 7 5 14a6 6 0 0 0 6 6c7 0 9-8 9-16Z"/><path d="M4 21c3-5 7-8 12-11"/></svg></button>
    <nav class="nav-list" tabindex="0" aria-label="Main menu items">
        <a class="nav-link <?= $navPage === 'dashboard'
            ? 'active'
            : '' ?>" href="index.php"><span class="nav-icon"><?= $navIcons['overview'] ?></span><span>Overview</span></a>
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
: '' ?>" href="<?= e($key) ?>.php"><span class="nav-icon"><?= $navIcons[$key] ?></span><span><?= $label ?></span></a>
        <?php endforeach; ?>
        <p class="nav-caption">INSIGHTS</p>
        <a class="nav-link <?= $navPage === 'reports'
            ? 'active'
            : '' ?>" href="reports.php"><span class="nav-icon"><?= $navIcons['reports'] ?></span><span>Reports</span></a>
        <?php if (
            ($user['role'] ?? '') ===
            'Administrator'
        ): ?><p class="nav-caption">ADMINISTRATION</p>
            <a class="nav-link <?= $navPage === 'users'
                ? 'active'
                : '' ?>" href="users.php"><span class="nav-icon"><?= $navIcons['accounts'] ?></span><span>Accounts</span></a>
            <a class="nav-link <?= $navPage === 'audit'
                ? 'active'
                : '' ?>" href="activity.php"><span class="nav-icon"><?= $navIcons['audit'] ?></span><span>Activity log</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
        <a class="nav-link <?= $navPage === 'profile'
            ? 'active'
            : '' ?>" href="profile.php"><span class="nav-icon"><?= $navIcons['profile'] ?></span><span>My profile</span></a>
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
