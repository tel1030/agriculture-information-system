<?php $pageTitle = 'Accounts';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow">ADMINISTRATION · ACCESS CONTROL</div><h1>Staff accounts</h1><p>Create and manage Administrator and Staff access.</p></div></section>
<div class="users-layout"><section class="panel table-panel"><div class="table-toolbar"><div><h2>System users</h2><p><?= count(
    $rows,
) ?> account<?= count($rows) === 1
     ? ''
     : 's' ?></p></div></div><div class="table-scroll"><table class="data-table"><thead><tr><th>ID ↑</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign in</th><th></th></tr></thead><tbody><?php foreach (
    $rows
    as $row
): ?><tr><td><?= e($row['user_id']) ?></td><td><?= e($row['full_name']) ?></td><td><?= e(
    $row['email'],
) ?></td><td><?= e($row['role']) ?></td><td class="<?= $row['is_active']
    ? 'account-active'
    : 'account-inactive' ?>"><?= $row['is_active'] ? 'Active' : 'Inactive' ?></td><td><?= $row[
    'last_login_at'
]
    ? e(date('M j, Y', strtotime($row['last_login_at'])))
    : 'Never' ?></td><td><?php if (
    (int) $row['user_id'] !== $user['user_id']
): ?><form method="post" class="inline-form" data-confirm="<?= $row['is_active']
    ? 'Disable'
    : 'Enable' ?> this account?"><input type="hidden" name="csrf_token" value="<?= e(
     csrf_token(),
 ) ?>"><input type="hidden" name="form_action" value="toggle"><input type="hidden" name="id" value="<?= e(
    $row['user_id'],
) ?>"><button class="action-link action-toggle" type="submit"><?= $row['is_active']
    ? 'Disable'
    : 'Enable' ?></button></form><form method="post" class="inline-form" data-confirm="Permanently delete this account? This cannot be undone."><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= e(
    $row['user_id'],
) ?>"><button class="action-link action-delete action-danger" type="submit">Delete</button></form><?php else: ?><span class="muted-copy">You</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel"><div class="eyebrow">NEW ACCOUNT</div><h2>Create a user</h2><p class="muted-copy">New users must change the temporary password at their first sign in.</p><?php if (
    !empty($error)
): ?><div class="alert alert-error"><?= e(
    $error,
) ?></div><?php endif; ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="form_action" value="create"><label>Full name<input name="full_name" maxlength="100" required></label><label>Email<input type="email" name="email" maxlength="254" required></label><label>Role<select name="role" required><option value="">Choose role</option><option>Administrator</option><option>Staff</option></select></label><label>Temporary password<input type="password" name="password" minlength="12" required><small>At least 12 characters.</small></label><button class="button button-primary" type="submit">Create account</button></form></section></div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
