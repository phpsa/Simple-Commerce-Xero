<?php

namespace Phpsa\StatamicXero;

use Statamic\Facades\Utility;
use Illuminate\Routing\Router;
use Statamic\Providers\AddonServiceProvider;
use Phpsa\StatamicXero\Http\Controllers\Cp\XeroController;
use DoubleThreeDigital\SimpleCommerce\Events\CartCompleted;
use Phpsa\StatamicXero\Listeners\CartCompleteListener;

class ServiceProvider extends AddonServiceProvider
{


    protected $listen = [
  /*
      'Acme\Example\Events\OrderShipped' => [
        'Acme\Example\Listeners\SendShipmentNotification',
    ], */

        CartCompleted::class => [
            CartCompleteListener::class
        ],
       // CartSaved::class           => [],
       // CartUpdated::class         => [],
       // CouponRedeemed::class      => [],
       // CustomerAddedToCart::class => [],
        //PostCheckout::class => [],
        //PreCheckout::class         => [],
        //StockRunningLow::class     => [],
        //StockRunningLow::class     => [],
    ];


    protected $publishables = [
        __DIR__ . '/../resources/svg'   => 'svg',
    ];

    protected $routes = [
        'web' => __DIR__ . '/../routes/web.php',
      //  'cp' => __DIR__ . '/../routes/web.php',
    ];

    public function boot()
    {
        parent::boot();

        Utility::make('xero-authentication')
              ->view('statamic-xero::xero-authentication')
            ->title(__('Xero Authentication'))
            ->icon('shield-key')
            ->description(__('Checks your connection to the Xero OAuth 2 system or allows you to reset it!'))
            ->docsUrl('https://statamic-addons.cgs4k.nz')
            ->routes(function (Router $router) {
                // $router->get('/manage/xero', [ XeroController::class, 'manage'])->prefix('statatata')->name('xero.auth.success');
                $router->post('/', [ XeroController::class, 'update'])->name('update');
            })
             ->action([XeroController::class, 'index'])
            ->register();

        $this->publishes([
            __DIR__ . '/../resources/views/checkout/gateways/' => resource_path('views/checkout/gateways/'),
        ], 'statamic-xero-gateway');
    }

    protected function schedule($schedule)
    {
 //   $schedule->command('something')->daily();
    }
}
