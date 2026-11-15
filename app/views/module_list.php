<?php
$pageTitle = $d['title'];
$route = $name . '.php';
$deleteWarning = match ($name) {
    'farmers'
        => 'Deleting this farmer also deletes their farms, planting records, and harvest records.',
    'farms' => 'Deleting this farm also deletes its planting and harvest records.',
    'crops' => 'Deleting this crop also deletes its planting and harvest records.',
    'plantings' => 'Deleting this planting also deletes its harvest records.',
    default => 'This cannot be undone.',
};
$listParams = ['q' => $search, 'per_page' => $perPage] + $activeFilters;
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading">
    <div><div class="eyebrow">OPERATIONS · <?= $d['title'] ?></div><h1><?= $d[
    'title'
] ?></h1><p>Manage <?= strtolower($d['title']) ?> and keep your records up to date.</p></div>
    <a class="button button-primary" href="<?= e(
        $route,
    ) ?>?action=create"><span>＋</span> Add <?= rtrim(strtolower($d['title']), 's') ?></a>
</section>
<section class="panel table-panel">
    <div class="table-toolbar<?= $d['title'] === 'Plantings'
        ? ' plantings-toolbar'
        : '' ?>">
        <div><h2>All <?= strtolower($d['title']) ?></h2><p><?= number_format(
    $total,
) ?> record<?= $total === 1 ? '' : 's' ?> found</p></div>
        <form class="filter-bar" method="get" action="<?= e($route) ?>">
            <label class="search-box"><span>⌕</span><input type="search" name="q" value="<?= e(
                $search,
            ) ?>" placeholder="Search <?= strtolower(
    $d['title'],
) ?>" aria-label="Search <?= strtolower($d['title']) ?>"></label>
            <?php foreach ($d['filters'] as $filter => $config): ?>
                <select name="<?= e($filter) ?>" aria-label="Filter by <?= e(
    $config['label'],
) ?>"><option value="">All <?= e($config['label']) ?></option>
                    <?php foreach ($filterOptions[$filter] as $option): ?><option value="<?= e(
    $option['id'],
) ?>" <?= ($activeFilters[$filter] ?? null) == $option['id'] ? 'selected' : '' ?>><?= e(
    $option['name'],
) ?></option><?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            <button class="button button-secondary button-small" type="submit">Apply</button>
        </form>
    </div>
    <div class="table-scroll"><table class="data-table responsive-list-table">
        <thead><tr><?php foreach ($d['columns'] as $column => $label): ?><th><?=
e($label) ?><?= $column === $d['key'] ? ' ↑' : ''
?></th><?php endforeach; ?><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="<?= count($d['columns']) +
                1 ?>"><div class="empty-state table-empty"><span>⌕</span><strong>No matching records</strong><p>Try a different search or add a new record.</p></div></td></tr>
        <?php else:foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($d['columns'] as $column => $label): ?><td data-label="<?= e(
    $label,
) ?>">
                    <?php if ($column === 'status_name'): ?><span class="status-chip"><?= e(
    $row[$column],
) ?></span>
                    <?php elseif ($column === 'area_hectares' || $column === 'area_planted'): ?><?= number_format(
    (float) $row[$column],
    2,
) ?> ha
                    <?php
                    elseif ($column === 'seed_quantity'): ?><?= number_format(
    (float) $row[$column],
    2,
) ?> kg*
                    <?php
                    elseif ($column === 'quantity_kg'): ?><?= number_format(
    (float) $row[$column],
    2,
) ?> kg
                    <?php
                    elseif ($column === 'estimated_value'): ?><?= $row[$column] === null
                            ? '—'
                            : '₱ ' . number_format((float) $row[$column], 2) ?>
                    <?php
                    elseif ($column === 'selling_price_per_kg'): ?><?= $row[$column] === null
                            ? '—'
                            : '₱ ' . number_format((float) $row[$column], 2) ?>
                    <?php
                    else: ?><?= e($row[$column] ?? '—') ?><?php endif; ?>
                </td><?php endforeach; ?>
                <td class="row-actions" data-label="Actions">
                    <a class="action-link" href="<?= e($route) ?>?action=view&amp;id=<?= e(
    $row[$d['key']],
) ?>">View</a>
                    <a class="action-link action-edit" href="<?= e(
                        $route,
                    ) ?>?action=edit&amp;id=<?= e($row[$d['key']]) ?>">Edit</a>
                    <?php if ($user['role'] === 'Administrator'): ?>
                    <form method="post" action="<?= e(
                        $route,
                    ) ?>" class="inline-form" data-confirm="Delete this <?= e(
    strtolower(rtrim($d['title'], 's')) . ' record',
) ?>? <?= e($deleteWarning) ?> This cannot be undone.">
                        <input type="hidden" name="csrf_token" value="<?= e(
                            csrf_token(),
                        ) ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= e(
    $row[$d['key']],
) ?>"><button class="action-link action-danger action-delete" type="submit">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach;endif; ?>
        </tbody>
    </table></div>
    <div class="table-footer">
        <span>Showing <?= number_format($total ? $offset + 1 : 0) ?>–<?= number_format(
    min($offset + $perPage, $total),
) ?> of <?= number_format($total) ?></span>
        <div class="pagination"><label>Rows <select data-page-size><?php foreach (
            [10, 20, 50]
            as $size
        ): ?><option value="<?= $size ?>" <?= $size === $perPage
    ? 'selected'
    : '' ?>><?= $size ?></option><?php endforeach; ?></select></label>
            <?php
            $previous = $listParams + ['p' => max(1, $currentPage - 1)];
            $next = $listParams + ['p' => min($pages, $currentPage + 1)];
            ?>
            <a class="page-button" aria-label="Previous page" href="<?= e($route) ?>?<?= e(
    http_build_query($previous),
) ?>" <?= $currentPage <= 1 ? 'aria-disabled="true"' : '' ?>>‹</a>
            <span>Page <?= $currentPage ?> of <?= $pages ?></span>
            <a class="page-button" aria-label="Next page" href="<?= e($route) ?>?<?= e(
    http_build_query($next),
) ?>" <?= $currentPage >= $pages ? 'aria-disabled="true"' : '' ?>>›</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout_bottom.php'; ?>
