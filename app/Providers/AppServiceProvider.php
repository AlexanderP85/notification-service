<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Providers\SmsProviderInterface;
use App\Services\Providers\SmsProviderMock;
use App\Services\Providers\EmailProviderInterface;
use App\Services\Providers\EmailProviderMock;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, SmsProviderMock::class);
        $this->app->bind(EmailProviderInterface::class, EmailProviderMock::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
