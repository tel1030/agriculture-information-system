<?php
$pageTitle = 'Activity log';
require __DIR__ . '/layout_top.php';

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
        <table class="data-table audit-table">
            <thead>
                <tr>
                    <th>Date and time</th>
                    <th>User</th>
                    <th>Activity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(date('M j, Y g:i A', strtotime($row['created_at']))) ?></td>
                        <td><?= e($row['full_name'] ?? 'System') ?></td>
                        <td><?= e($actionLabels[$row['action']] ?? ucwords(str_replace('_', ' ', strtolower($row['action'])))) ?></td>
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
