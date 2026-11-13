
<?php
$pageTitle = 'Activity log';
require __DIR__ . '/layout_top.php';
?>

<section class="page-heading">
    <div>
        <div class="eyebrow">ADMINISTRATION · AUDIT</div>
        <h1>Activity log</h1>
        <p>Recent account and record changes, with sensitive fields excluded.</p>
    </div>
</section>

<section class="panel table-panel">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID ↑</th>
                    <th>Date and time</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Area</th>
                    <th>Record</th>
                    <th>Change summary</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['log_id']) ?></td>

                        <td>
                            <?= e(date('M j, Y H:i', strtotime($row['created_at']))) ?>
                        </td>

                        <td><?= e($row['full_name'] ?? 'System') ?></td>

                        <td>
                            <?= e(str_replace('_', ' ', strtolower($row['action']))) ?>
                        </td>

                        <td><?= e($row['entity_table']) ?></td>

                        <td><?= e($row['record_pk'] ?? '—') ?></td>

                        <td class="audit-data">
                            <?php
                            $data = $row['new_values'] ?: $row['old_values'];

                            if (is_string($data)) {
                                $data = json_decode($data, true);
                            }

                            // Audit values should be objects, but older or malformed rows
                            // may contain a scalar. Keep those rows renderable as well.
                            if (is_object($data)) {
                                $data = (array) $data;
                            }

                            if (is_array($data)) {
                                $fields = $data['fields'] ?? array_keys($data);
                                if (!is_array($fields)) {
                                    $fields = [$fields];
                                }
                                echo e(implode(', ', array_map('strval', $fields)));
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/layout_bottom.php';
?>
