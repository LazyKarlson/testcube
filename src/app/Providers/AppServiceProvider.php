<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Role;
use App\Observers\CommentObserver;
use App\Observers\PostObserver;
use App\Observers\RoleObserver;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
