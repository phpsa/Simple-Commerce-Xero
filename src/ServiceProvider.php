<?php

namespace Phpsa\StatamicXero;

use Statamic\Statamic;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;
use Statamic\CP\Navigation\Nav as NavigationNav;
use DoubleThreeDigital\SimpleCommerce\Events\CartSaved;
use DoubleThreeDigital\SimpleCommerce\Events\CartUpdated;
use DoubleThreeDigital\SimpleCommerce\Events\PreCheckout;
use DoubleThreeDigital\SimpleCommerce\Events\PostCheckout;
use DoubleThreeDigital\SimpleCommerce\Events\CartCompleted;
use DoubleThreeDigital\SimpleCommerce\Events\CouponRedeemed;
use DoubleThreeDigital\SimpleCommerce\Events\StockRunningLow;
use DoubleThreeDigital\SimpleCommerce\Events\CustomerAddedToCart;
use Illuminate\Routing\Router;
use Phpsa\StatamicXero\Http\Controllers\Cp\XeroController;

class ServiceProvider extends AddonServiceProvider
{


    protected $listen = [
  /*
      'Acme\Example\Events\OrderShipped' => [
        'Acme\Example\Listeners\SendShipmentNotification',
    ], */

        CartCompleted::class       => [],
        CartSaved::class           => [],
        CartUpdated::class         => [],
        CouponRedeemed::class      => [],
        CustomerAddedToCart::class => [],
        PostCheckout::class        => [],
        PreCheckout::class         => [],
        StockRunningLow::class     => [],
        StockRunningLow::class     => [],
    ];

    protected $subscribe = [
 //   'Acme\Example\Listeners\UserEventSubscriber',
    ];

    protected $publishables = [
        __DIR__ . '/../resources/svg'   => 'svg',
    ];

    protected $routes = [
        'web' => __DIR__ . '/../routes/web.php',
    ];

    public function boot()
    {
        parent::boot();

        Statamic::afterInstalled(function ($command) {
            //do i do anythink here?
        });

        Utility::make('xero-authentication')
              ->view('statamic-xero::xero-authentication')
            ->title(__('Xero Authentication'))
            ->icon('shield-key')
            ->description(__('Checks your connection to the Xero OAuth 2 system or allows you to reset it!'))
            ->docsUrl('https://statamic-addons.cgs4k.nz')
            ->routes(function (Router $router) {
                // $router->get('/manage/xero', [ XeroController::class, 'manage'])->prefix('statatata')->name('xero.auth.success');
            //    $router->post('/', [ XeroController::class, 'update'])->name('update');
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
