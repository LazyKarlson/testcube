<?php

namespace App\Providers;

use App\Contracts\CacheServiceInterface;
use App\Contracts\CommentRepositoryInterface;
use App\Contracts\PostRepositoryInterface;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Observers\CommentObserver;
use App\Observers\PostObserver;
use App\Observers\RoleObserver;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Services\CacheService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repositories to interfaces
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);

        // Bind services to interfaces
        $this->app->bind(CacheServiceInterface::class, CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers for automatic cache invalidation
        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);
        Role::observe(RoleObserver::class);

        // Register policies
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }
}
