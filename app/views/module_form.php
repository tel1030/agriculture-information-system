<?php $pageTitle =
    ($action === 'create' ? 'Add ' : ($action === 'view' ? 'View ' : 'Edit ')) .
    rtrim($d['title'], 's');
$route = $name . '.php';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow"><a href="<?= e(
    $route,
) ?>" class="back-link">← <?= $d['title'] ?></a> · RECORD</div><h1><?= e(
    $pageTitle,
) ?></h1><p><?= in_array($action, ['create', 'edit'], true)
    ? 'Enter the details below. Required fields are marked.'
    : 'Review the saved information for this record.' ?></p></div></section>
<?php if ($action === 'view'):
    $lookupNames = [
        'farms' => [
            'farmer_id' => 'owner_name',
            'soil_type_id' => 'soil_type_name',
            'irrigation_type_id' => 'irrigation_type_name',
        ],
        'crops' => ['category_option_id' => 'category_name', 'season_option_id' => 'season_name'],
        'plantings' => [
            'farm_id' => 'farm_name',
            'crop_id' => 'crop_display',
            'status_id' => 'status_name',
        ],
        'harvests' => ['quality_option_id' => 'quality_name', 'remark_option_id' => 'remark_name'],
    ]; ?><section class="panel detail-panel"><div class="detail-grid"><?php foreach (
    $d['fields']
    as $field => $spec
):

    [$label, $type] = $spec;
    $value = $record[$field] ?? null;
    if (isset($lookupNames[$name][$field])) {
        $value = $record[$lookupNames[$name][$field]] ?? $value;
    }
    if ($name === 'harvests' && $field === 'planting_id' && isset($record['farm_name'])) {
        $value =
            '#' .
            $record['planting_id'] .
            ' · ' .
            $record['farm_name'] .
            ' · ' .
            $record['crop_display'];
    }
    if ($type === 'lookup' && $value !== null) {
        foreach ($fieldOptions[$field] ?? [] as $choice) {
            if ((string) $choice['id'] === (string) $value) {
                $value = $choice['name'];
            }
        }
    }
    ?><div class="detail-item"><span><?= e($label) ?></span><strong><?= e(
    $value === null || $value === '' ? '—' : $value,
) ?></strong></div><?php
endforeach; ?></div><div class="form-actions"><a class="button button-primary action-edit" href="<?= e(
    $route,
) ?>?action=edit&amp;id=<?= e(
    $id,
) ?>">Edit record</a><a class="button button-secondary" href="<?= e(
    $route,
) ?>">Back to list</a></div></section>
<?php
else:
     ?><section class="panel form-panel"><form method="post" action="<?= e(
    $route,
) ?>" class="record-form"><input type="hidden" name="csrf_token" value="<?= e(
    csrf_token(),
) ?>"><input type="hidden" name="form_action" value="<?= $action === 'create'
    ? 'create'
    : 'update' ?>"><?php if ($action === 'edit'): ?><input type="hidden" name="id" value="<?= e(
    $id,
) ?>"><?php endif; ?><div class="form-grid"><?php foreach ($d['fields'] as $field => $spec):

    [$label, $type, $required, $limit] = $spec;
    $value = $record[$field] ?? '';
    ?><label class="field-label"><?= e($label) ?> <?=
 $required ? '<span class="required">*</span>' : ''
 ?><?php if ($type === 'lookup'): ?><select name="<?= e($field) ?>" <?= $required
    ? 'required'
    : '' ?>><option value="">Choose an option</option><?php foreach (
    $fieldOptions[$field]
    as $option
): ?><option value="<?= e($option['id']) ?>" <?= (string) $value === (string) $option['id']
    ? 'selected'
    : '' ?>><?= e(
    $option['name'],
) ?></option><?php endforeach; ?></select><?php else: ?><input name="<?= e(
    $field,
) ?>" type="<?= $type === 'decimal' || $type === 'decimal_optional'
    ? 'number'
    : e($type) ?>" <?php if (
    $type === 'decimal' ||
    $type === 'decimal_optional'
): ?>step="0.01" min="<?= $field === 'seed_quantity' ? '0' : '0.01' ?>"<?php endif; ?> <?php if (
     $type === 'integer'
 ): ?>min="1" step="1"<?php endif; ?> value="<?= e($value) ?>" maxlength="<?= $limit ?:
    null ?>" <?= $required ? 'required' : '' ?>><?php endif;
 ?></label><?php
endforeach; ?></div><div class="form-actions"><button class="button button-primary" type="submit">Save record <span>→</span></button><a class="button button-secondary" href="<?= e(
    $route,
) ?>">Cancel</a><span class="form-required-note"><span class="required">*</span> Required</span></div></form></section><?php
endif; ?>
<?php require __DIR__ . '/layout_bottom.php'; ?>
