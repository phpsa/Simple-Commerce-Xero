<?php

namespace Phpsa\StatamicXero\Listeners;

use Statamic\Facades\Site;
use Statamic\Facades\Entry;
use Illuminate\Support\Collection;
use Phpsa\StatamicXero\XeroHelper;
use Phpsa\StatamicXero\ConfigStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Coupons\Coupon;
use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\Facades\Currency;
use DoubleThreeDigital\SimpleCommerce\Facades\Shipping;
use DoubleThreeDigital\SimpleCommerce\Orders\Calculator;
use DoubleThreeDigital\SimpleCommerce\Events\CartCompleted;
use Illuminate\Support\Carbon;

class CartCompleteListener implements ShouldQueue
{

    public function handle(CartCompleted $event)
    {
        //
        $contact = $this->findOrCreateContact($event->order);
        if ($contact) {
                // create the Invoice

            try {
                    // save the invoice
                $invoice = $this->createInvoice($contact, $event->order);
                $result = app(XeroHelper::class)->createInvoices($invoice);
                dd($result);

            // Would $orderTotal ever be more than $invoice->Total?
            // If so, what should happen with rounding?
                $orderTotal = Plugin::getInstance()->withDecimals($this->decimals, $order->getTotalPrice());
                if ($invoice->Total > $orderTotal) {
                // caclulate how much rounding to adjust
                    $roundingAdjustment = $orderTotal - $invoice->Total;
                    $roundingAdjustment = Plugin::getInstance()->withDecimals($this->decimals, $roundingAdjustment);

                // add rounding to invoice
                    $lineItem = new LineItem($this->getApplication());
                    $lineItem->setAccountCode($this->_client->getOrgSettings()->accountRounding);
                    $lineItem->setDescription("Rounding adjustment: Order Total: $" . $orderTotal);
                    $lineItem->setQuantity(1);
                    $lineItem->setUnitAmount($roundingAdjustment);
                    $invoice->addLineItem($lineItem);

                // update the invoice with new rounding adjustment
                    $invoice->save();
                }

                $invoiceRecord = new InvoiceRecord();
                $invoiceRecord->orderId = $order->id;
                $invoiceRecord->invoiceId = $invoice->InvoiceID;
                $invoiceRecord->save();

            // TODO: add hook (after_invoice_save)

                return $invoice;
            } catch (\Throwable $e) {
                dd($e);
                throw new \Exception($e);
            }

                // only continue to payment if a payment has been made and payments are enabled
            if ($invoice && $order->isPaid && $this->_client->getOrgSettings()->createPayments) {
                    // before we can make the payment we need to get the Account
                $account = $this->getAccountByCode($this->_client->getOrgSettings()->accountReceivable);
                if ($account) {
                    $payment = $this->createPayment($invoice, $account, $order);
                }
                return true;
            }
        }

        dd($event);
    }

    protected function findOrCreateContact(Order $order)
    {
        $customer = $order->customer();

        $contactEmail = $customer->get('email') ;
        $contactName = $customer->get('name');
        $id = $customer->id();

        $where = 'EmailAddress=="2' . $contactEmail . '" OR ContactNumber=="' . $id . '"';

        $xeroApi = app(XeroHelper::class);

        $contact = $xeroApi->getContacts($where)->first();

        if (! $contact) {
            $names = $xeroApi->splitNames($contactName);
            $contact = new Contact();
            $contact->setName($contactName)
                    ->setContactNumber($id)
                    ->setFirstName($names[0])
                    ->setLastName($names[1])
                    ->setEmailAddress($contactEmail);

            $contacts = new Contacts();
            $contacts->setContacts([$contact]);

            $result = $xeroApi->updateOrCreateContacts($xeroApi->getTenantId(), $contacts);

            $contact = $result->getContacts()[0];

                // TODO: add hook (before_save_contact)
        }

        return $contact;
    }

    protected function createInvoice(Contact $contact, Order $order)
    {
        $siteTax = collect(Config::get('simple-commerce.sites'))
                    ->get(Site::current()->handle())['tax'];
        $calculatedOrder = resolve(Calculator::class)->calculate($order);

        $invoice = new Invoice();
        $invoice->setStatus('AUTHORISED')
            ->setType('ACCREC')
            ->setContact($contact)
            ->setCurrencyCode(Currency::get(Site::current())['code'])
            ->setLineAmountTypes($siteTax['included_in_prices'] ? "Inclusive" : "Exclusive")
            ->setInvoiceNumber($order->get('title'))
            ->setSentToContact(true)
            ->setDueDate(Carbon::now()->toDateTime());

        $lineItems = $this->parseLineItems($calculatedOrder);
        $lineItems->push($this->parseShipping($order, $calculatedOrder['shipping_total']));
        $lineItems->push($this->parseCoupon($order, $calculatedOrder['coupon_total']));

        $invoice->setLineItems($lineItems->filter()->toArray());

        return $invoice;
    }

    protected function parseLineItems(array $order): Collection
    {
        $items = [];
        $salesRevenueCode =  app(ConfigStore::class)->getSalesRevenue();
        $salesTaxCode = app(ConfigStore::class)->getSalesTaxCode();
        $noTaxCode = app(ConfigStore::class)->getTaxFreeCode();
        foreach ($order['items'] as $item) {
            $product = Product::find($item['product']);
            $lineItem = new LineItem();
            $lineItem->setAccountCode($salesRevenueCode);
            $lineItem->setDescription($product->get('title'));
            $lineItem->setQuantity($item['quantity']);
            $lineItem->setUnitAmount(app(XeroHelper::class)->withDecimals($item['total'] / 100));

            if ($product->isExemptFromTax() && $noTaxCode) {
                $lineItem->setTaxType($noTaxCode);
            }
            if (! $product->isExemptFromTax() && $salesTaxCode) {
                $lineItem->setTaxType($salesTaxCode);
            }

            $items[] = $lineItem;
        }
        return collect($items);
    }

    protected function parseCoupon(Order $order, $total): ?LineItem
    {
        if (! $order->get('coupon') || empty($total)) {
            return null;
        }

        $lineItem = new LineItem();
        $lineItem->setAccountCode(app(ConfigStore::class)->getDiscounts());
        $lineItem->setDescription($order->coupon()->get('title'));
        $lineItem->setQuantity(1);
        $lineItem->setUnitAmount(app(XeroHelper::class)->withDecimals($total / 100));

        return $lineItem;
    }

    protected function parseShipping(Order $order, $total): ?LineItem
    {
        if (! isset($order->data['shipping_method'])) {
            return null;
        }
        $shipping = Shipping::use($order->data['shipping_method']); //->calculateCost($order->entry());

        $lineItem = new LineItem();
        $lineItem->setAccountCode(app(ConfigStore::class)->getShippingDelivery());
        $lineItem->setDescription($shipping->name());
        $lineItem->setQuantity(1);
        $lineItem->setUnitAmount(app(XeroHelper::class)->withDecimals($total / 100));

        return $lineItem;
    }
}
