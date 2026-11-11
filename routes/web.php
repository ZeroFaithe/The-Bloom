<?php
use App\Controllers\AdminController;
use App\Controllers\AdminMethodController;
use App\Controllers\AdminVideoController;
use App\Controllers\AuthController;
use App\Controllers\GuideController;
use Core\Router;

/** @var Core\Application $app */
$router = $app->router;

// Runs on every request (names come from config.php)
$router->use('logger');
$router->use('csrf'); // only checks POST/PUT/DELETE

// Public
$router->get('/', [GuideController::class, 'index']);
$router->get('/guides', [GuideController::class, 'index']);
$router->get('/guides/{slug}', [GuideController::class, 'show']);

// Login / logout
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Staff area (admins and editors)
$router->group(['prefix' => '/admin', 'middleware' => ['auth']], function (Router $r) {
    $r->get('/', [AdminController::class, 'dashboard']);

    // Guides
    $r->get('/guides', [AdminController::class, 'guideIndex']);
    $r->get('/guides/create', [AdminController::class, 'guideCreate']);
    $r->post('/guides', [AdminController::class, 'guideStore']);
    $r->get('/guides/{id}/edit', [AdminController::class, 'guideEdit']);
    $r->post('/guides/{id}/update', [AdminController::class, 'guideUpdate']);
    $r->post('/guides/{id}/delete', [AdminController::class, 'guideDelete']);
    $r->post('/guides/{id}/status', [AdminController::class, 'guideToggle']);

    // YouTube video search for the guide form (uses API quota, so staff only)
    $r->get('/videos/search', [AdminVideoController::class, 'search']);

    // Brewing methods (admins only)
    $r->group(['prefix' => '/methods', 'middleware' => ['role:admin']], function (Router $r) {
        $r->get('/', [AdminMethodController::class, 'index']);
        $r->get('/create', [AdminMethodController::class, 'create']);
        $r->post('/', [AdminMethodController::class, 'store']);
        $r->get('/{id}/edit', [AdminMethodController::class, 'edit']);
        $r->post('/{id}/update', [AdminMethodController::class, 'update']);
        $r->post('/{id}/delete', [AdminMethodController::class, 'delete']);
    });
});
