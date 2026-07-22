<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\StudentMiddleware;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('admin', AdminMiddleware::class);
        $this->app['router']->aliasMiddleware('student', StudentMiddleware::class);
    }
}
