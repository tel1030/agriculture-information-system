<?php $pageTitle = 'Welcome back'; ?>
<!doctype html><html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#164b35"><title>Sign in · AIS</title><link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__ . '/../../public/assets/css/app.css') ?>"></head><body class="auth-body">
<main class="auth-wrap"><section class="auth-card"><a class="brand auth-brand" href="login.php"><span class="brand-mark">A</span><span class="brand-copy"><strong>Agree<span>Culture</span></strong><small>Information System</small></span></a><div class="auth-intro"><h1>Welcome back</h1><p>Sign in to manage farms, crops, planting activity, and harvest records.</p></div>
<?php if (!empty($error)): ?><div class="alert alert-error" role="alert"><?= e(
    $error,
) ?></div><?php endif; ?>
<form method="post" class="form-stack" autocomplete="on"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><label>Email address<input type="email" name="email" maxlength="254" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button button-primary button-full" type="submit">Sign in <span aria-hidden="true">→</span></button></form><div class="auth-foot"><span class="secure-dot"></span>Protected staff access</div><div class="auth-message"><p>Growing better decisions,<br><strong>one season at a time.</strong></p></div></section></main></body></html>
