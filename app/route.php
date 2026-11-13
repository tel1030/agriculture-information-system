<?php

declare(strict_types=1);

// Beginner Note: Each small public page points to one section while the shared router keeps its security checks in one place.
function route_to(string $page, ?string $module = null): never
{
    $_GET['page'] = $page;
    if ($module !== null) {
        $_GET['module'] = $module;
    }
    require dirname(__DIR__) . '/public/index.php';
    exit();
}
