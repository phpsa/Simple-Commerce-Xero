<?php

namespace Phpsa\StatamicXero\Listeners;

use Phpsa\StatamicXero\XeroHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Events\CartCompleted;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;

class CartCompleteListener implements ShouldQueue
{

    public function handle(CartCompleted $event)
    {
        //
        $contact = $this->findOrCreateContact($event->order);

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
}
