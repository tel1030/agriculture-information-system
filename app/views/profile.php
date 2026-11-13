<?php $pageTitle = 'My profile';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow">PERSONAL SETTINGS</div><h1>My profile</h1><p>Manage your account security and display preference.</p></div></section>
<div class="profile-layout"><section class="panel form-panel"><form method="post" class="record-form"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><div><div class="eyebrow">ACCOUNT DETAILS</div><h2>Your name</h2><label class="field-label">Full name<input name="full_name" maxlength="100" value="<?= e(
    $user['name'],
) ?>" required></label></div><div><div class="eyebrow">APPEARANCE</div><h2>Theme preference</h2><p class="muted-copy">Choose a comfortable look for your workspace.</p><div class="theme-choice"><label><input type="radio" name="theme" value="light" <?= $theme ===
'light'
    ? 'checked'
    : '' ?>> ☀ Light mode</label><label><input type="radio" name="theme" value="dark" <?= $theme ===
'dark'
    ? 'checked'
    : '' ?>> ☾ Dark mode</label></div></div><div class="password-section"><div class="eyebrow">ACCOUNT SECURITY</div><h2><?= $mustChange
    ? 'Set a new password'
    : 'Change password' ?></h2><p class="muted-copy">Use at least 12 characters. Passwords are stored as a secure one-way hash.</p><?php if (
    !$mustChange
): ?><label class="field-label">Current password<input type="password" name="current_password" autocomplete="current-password"></label><?php endif; ?><label class="field-label">New password<input type="password" name="new_password" minlength="12" autocomplete="new-password" <?= $mustChange
    ? 'required'
    : '' ?>></label></div><div class="form-actions"><button class="button button-primary" type="submit">Save profile</button></div></form></section><aside class="profile-help"><strong>Keep your account safe</strong><p>Your account can access farmer and agricultural records. Keep your password private and sign out on shared devices.</p><ul><li>Use 12 or more characters</li><li>Do not reuse an old password</li><li>Change a temporary password right away</li></ul></aside></div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
