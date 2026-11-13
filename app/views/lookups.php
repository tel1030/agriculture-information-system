<?php $pageTitle = 'Lookup values';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow">ADMINISTRATION · REFERENCE DATA</div><h1>Lookup values</h1><p>Maintain the controlled dropdown choices used by forms.</p></div></section>
<div class="lookup-grid"><?php foreach ($sets as $key => $set):
    [
        $table,
        $idCol,
        $labelCol,
        $title,
        $kind,
    ] = $set; ?><section class="panel lookup-card"><div class="eyebrow">CONTROLLED OPTIONS</div><h2><?= e(
    $title,
) ?></h2><form method="post" class="lookup-form"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="set" value="<?= e(
    $key,
) ?>"><input type="hidden" name="form_action" value="save"><input name="label" aria-label="New <?= e(
    strtolower(rtrim($title, 's')),
) ?>" maxlength="100" placeholder="Add an option" required><button class="button button-primary button-small" type="submit">Add</button></form><div class="lookup-list"><?php foreach (
    $data[$key]
    as $row
): ?><div class="lookup-item"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="set" value="<?= e(
    $key,
) ?>"><input type="hidden" name="form_action" value="save"><input type="hidden" name="id" value="<?= e(
    $row['id'],
) ?>"><input name="label" value="<?= e($row['label']) ?>" aria-label="Edit <?= e(
    $row['label'],
) ?>"><button class="action-edit" type="submit">Save</button></form><?php if (
    $row['is_active']
): ?><form method="post" data-confirm="Deactivate this option? Existing records will keep their saved value."><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="set" value="<?= e(
    $key,
) ?>"><input type="hidden" name="form_action" value="deactivate"><input type="hidden" name="id" value="<?= e(
    $row['id'],
) ?>"><button class="action-danger action-toggle" type="submit">Disable</button></form><?php else: ?><span class="muted-copy">Disabled</span><?php endif; ?></div><?php endforeach; ?></div></section><?php
endforeach; ?></div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
