<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\InvoiceRepositoryInterface;
use App\Repositories\Eloquent\EloquentInvoiceRepository;

use App\Payment\PaymentMethodInterface;
use App\Payment\CreditCardPayment;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {



    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
