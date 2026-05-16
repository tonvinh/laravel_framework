<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Max 5 login attempts per minute per IP and per email
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower((string) $request->input('email', ''))),
            ];
        });

        // Max 3 registration attempts per minute per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
