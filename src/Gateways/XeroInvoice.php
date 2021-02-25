<?php

namespace Phpsa\StatamicXero\Gateways;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;
use DoubleThreeDigital\SimpleCommerce\Data\Gateways\BaseGateway;
use DoubleThreeDigital\SimpleCommerce\Data\Gateways\GatewayPrep;
use DoubleThreeDigital\SimpleCommerce\Data\Gateways\GatewayPurchase;
use DoubleThreeDigital\SimpleCommerce\Data\Gateways\GatewayResponse;
use Statamic\Entries\Entry;
use Illuminate\Http\Request;

class XeroInvoice extends BaseGateway implements Gateway
{
    public function name(): string
    {
        return 'Invoice Via Xero';
    }

    public function prepare(GatewayPrep $data): GatewayResponse
    {
        return new GatewayResponse(true, []);
    }

    public function purchase(GatewayPurchase $data): GatewayResponse
    {
        //api post to XERO

       // $paymentMethod = PaymentMethod::retrieve($data->request()->payment_method);
       //should return an invoice id and maybe a url
        return new GatewayResponse(true, [
            'id'     => 'xero_id',
            'object' => 'invoice_url'
        ]);
    }

    public function purchaseRules(): array
    {
        return [];
    }

    public function getCharge(Entry $order): GatewayResponse
    {
        return new GatewayResponse(true, [
            'id'        => '123456789abcdefg',
            'last_four' => '4242',
            'date'      => (string) now()->subDays(14),
            'refunded'  => false,
        ]);
    }

    public function refundCharge(Entry $order): GatewayResponse
    {
        return new GatewayResponse(true, []);
    }

    public function webhook(Request $request)
    {
        return null;
    }
}
