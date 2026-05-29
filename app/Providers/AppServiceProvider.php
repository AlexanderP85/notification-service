<?php

namespace App\Providers;

use App\Services\Providers\EmailProviderInterface;
use App\Services\Providers\EmailProviderMock;
use App\Services\Providers\SmsProviderInterface;
use App\Services\Providers\SmsProviderMock;
use Illuminate\Support\ServiceProvider;

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
