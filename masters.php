<?php
require_once __DIR__ . '/inc/header.php';
$artisans = fetch_artisans();
?>
<h1 class="h4 mb-3">Наши мастера</h1>
<div class="row g-3">
    <?php foreach ($artisans as $artisan): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?=$artisan['studio_name'];?></h5>
                    <p class="text-muted mb-1"><?=htmlspecialchars($artisan['full_name'] ?? ''); ?></p>
                    <p><?=htmlspecialchars($artisan['bio']);?></p>
                    <a class="btn btn-outline-primary" href="/profile.php?id=<?=$artisan['id'];?>">Профиль</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

