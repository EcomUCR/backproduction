<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use App\Mail\Transport\BrevoTransport;
use App\Services\Contracts\VisaClientContract;
use App\Services\VisaClient;
use App\Services\MockVisaClient;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VisaClientContract::class, function () {
            return config('services.visa.mock')
                ? new MockVisaClient()
                : new VisaClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aquí registramos el nuevo driver "brevo"
        Mail::extend('brevo', function (array $config = []) {
            return new BrevoTransport();
        });
    }
}
