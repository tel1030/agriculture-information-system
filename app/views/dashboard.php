<?php $pageTitle = 'Overview';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow"><?= strtoupper(
    date('l · j F Y'),
) ?></div><h1>Good day, <?= e(
    strtok($user['name'], ' '),
) ?> <span class="heading-spark">✳</span></h1><p>Here’s a quick look at what’s happening across your agricultural records.</p></div></section>
<div class="metric-grid"><?php foreach (
    $counts as $card
): ?><article class="metric-card"><div class="metric-top"><span><?= $card[
    'label'
] ?></span></div><strong class="metric-value"><?= number_format(
    $card['value'],
) ?></strong><div class="metric-foot"><span>Current records</span><span class="metric-arrow">↗</span></div></article><?php endforeach; ?></div>
<div class="dashboard-grid"><section class="panel status-panel"><div class="panel-heading"><div><div class="eyebrow">FIELD ACTIVITY</div><h2>Planting status</h2></div><a class="text-link" href="plantings.php">View records <span>→</span></a></div><div class="status-list"><?php foreach (
    $statuses
    as $status
): ?><div class="status-row"><div class="status-label"><span><?= e(
    $status['status_name'],
) ?></span><strong><?= number_format(
    (int) $status['total'],
) ?></strong></div><progress class="bar-progress" value="<?= e(
    $status['total'],
) ?>" max="<?= $maxStatus ?>" aria-label="<?= e(
    $status['status_name'],
) ?>"></progress></div><?php endforeach; ?></div><div class="harvest-summary"><div><span class="summary-icon">◉</span><span><small>Total recorded harvest</small><strong><?= number_format(
    (float) $totals['total_kg'],
    2,
) ?> <em>kg</em></strong></span></div><span class="summary-note">Recorded quantity</span></div></section>
<section class="panel value-panel"><div class="eyebrow">HARVEST VALUE</div><h2>Estimated value</h2><p>Based on harvest records with a selling price entered.</p><div class="value-number"><?php if (
    $totals['value'] === null
): ?>Not recorded<?php else: ?><span>₱</span><?= number_format(
    (float) $totals['value'],
    2,
); ?><?php endif; ?></div><div class="value-note"><i>i</i> Estimate only · excludes harvests without a price</div><a class="button button-secondary button-small" href="reports.php">Explore reports <span>→</span></a></section></div>
<div class="dashboard-grid lower-grid"><section class="panel"><div class="panel-heading"><div><div class="eyebrow">LATEST ACTIVITY</div><h2>Recent plantings</h2></div><a class="text-link" href="plantings.php">See all <span>→</span></a></div><?php if (
    !$recentPlantings
): ?><div class="empty-state"><span>⌁</span><strong>No plantings yet</strong><p>Planting records will appear here after they’re added.</p></div><?php else: ?><div class="compact-list"><?php foreach (
    $recentPlantings
    as $row
): ?><div class="compact-item"><span class="compact-icon">⌁</span><span class="compact-main"><strong><?= e(
    $row['crop_name'],
) ?></strong><small><?= e($row['farm_name']) ?> · <?= date(
     'M j, Y',
     strtotime($row['planting_date']),
 ) ?></small></span><span class="row-id">#<?= e(
    $row['planting_id'],
) ?></span><span class="status-chip status-<?= e(
    strtolower(str_replace(' ', '-', (string) $row['status_name'])),
) ?>"><?= e(
    $row['status_name'],
) ?></span></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="panel"><div class="panel-heading"><div><div class="eyebrow">LATEST ACTIVITY</div><h2>Recent harvests</h2></div><a class="text-link" href="harvests.php">See all <span>→</span></a></div><?php if (
    !$recentHarvests
): ?><div class="empty-state"><span>◉</span><strong>No harvests yet</strong><p>Harvest records will appear here after they’re added.</p></div><?php else: ?><div class="compact-list"><?php foreach (
    $recentHarvests
    as $row
): ?><div class="compact-item"><span class="compact-icon harvest-icon">◉</span><span class="compact-main"><strong><?= e(
    $row['crop_name'],
) ?> · <?= number_format((float) $row['quantity_kg'], 2) ?> kg</strong><small><?= e(
     $row['farm_name'],
 ) ?> · <?= date(
     'M j, Y',
     strtotime($row['harvest_date']),
 ) ?></small></span><span class="row-id">#<?= e(
    $row['harvest_id'],
) ?></span></div><?php endforeach; ?></div><?php endif; ?></section></div>
<?php require __DIR__ . '/layout_bottom.php'; ?>
