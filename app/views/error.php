<?php $pageTitle = 'Something needs attention';
require __DIR__ . '/layout_top.php';
?>
<section class="page-heading"><div><div class="eyebrow">REQUEST STATUS</div><h1><?= e(
    $pageTitle,
) ?></h1><p><?= e(
    $message,
) ?></p></div><a class="button button-secondary" href="index.php">Back to overview</a></section>
<?php require __DIR__ . '/layout_bottom.php'; ?>
