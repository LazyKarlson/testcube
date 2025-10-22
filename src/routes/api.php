<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email verification (public route with signed URL)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

// Public read-only routes with rate limiting (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    // Meta - public metadata
    Route::get('/meta/roles', [RoleController::class, 'metaRoles']);

    // Posts - public read access
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/search', [PostController::class, 'search']);
    Route::get('/posts/{post}', [PostController::class, 'show']);

    // Comments - public read access
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::get('/comments/{comment}', [CommentController::class, 'show']);

    // Statistics - public read access
    Route::get('/stats/posts', [StatsController::class, 'posts']);
    Route::get('/stats/comments', [StatsController::class, 'comments']);
    Route::get('/stats/users', [StatsController::class, 'users']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Email verification routes
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->name('verification.send');
    Route::get('/email/verify/check', [AuthController::class, 'checkEmailVerification'])
        ->name('verification.check');

    // Roles - available to all authenticated users
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/users/{user}/roles', [RoleController::class, 'getUserRoles']);

    // Posts - create/update/delete require permissions
    Route::middleware('permission:create_posts')->group(function () {
        Route::post('/posts', [PostController::class, 'store']);
    });

    Route::middleware('permission:update_posts')->group(function () {
        Route::put('/posts/{post}', [PostController::class, 'update']);
        Route::patch('/posts/{post}', [PostController::class, 'update']);
    });

    Route::middleware('permission:delete_posts')->group(function () {
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    });

    Route::get('/my-posts', [PostController::class, 'myPosts']);

    // Comments - create/update/delete require permissions
    Route::middleware('permission:create_comments')->group(function () {
        Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    });

    Route::middleware('permission:update_comments')->group(function () {
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    });

    Route::middleware('permission:delete_comments')->group(function () {
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    });

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // User management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Role management
        Route::post('/users/{user}/roles', [RoleController::class, 'assignRole']);
        Route::delete('/users/{user}/roles', [RoleController::class, 'removeRole']);
    });
});
