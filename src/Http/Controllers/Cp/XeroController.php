<?php

namespace Phpsa\StatamicXero\Http\Controllers\Cp;

use Illuminate\Http\Request;
use Statamic\Facades\Blueprint;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Phpsa\StatamicXero\XeroHelper;
use Phpsa\StatamicXero\ConfigStore;
use XeroAPI\XeroPHP\Api\AccountingApi;
use Webfox\Xero\OauthCredentialManager;
use Statamic\Http\Controllers\CP\CpController;

class XeroController extends CpController
{

    public function index(Request $request, OauthCredentialManager $xeroCredentials)
    {
        try {
            // Check if we've got any stored credentials
            if ($xeroCredentials->exists()) {
                /*
                 * We have stored credentials so we can resolve the AccountingApi,
                 * If we were sure we already had some stored credentials then we could just resolve
                 * this through the controller
                 * But since we use this route for the initial authentication we cannot be sure!
                 */

                $xero             = resolve(AccountingApi::class);
                $organisationName = $xero->getOrganisations($xeroCredentials->getTenantId())
                    ->getOrganisations()[0]
                    ->getName();
                $user             = $xeroCredentials->getUser();
                $username         = "{$user['given_name']} {$user['family_name']} ({$user['username']})";
            }
        } catch (\throwable $e) {
            // Can happen if credentials have been revoked / there's an error with the organisation (e.g. it's expired)
            app(XeroHelper::class)->flushAllCaches();
            $error = $e->getMessage();
        }

        $blueprint = $this->getBlueprint($xeroCredentials);
        $fields = $blueprint->fields();
        $values = app(ConfigStore::class)->getData();

        $fields = $fields->addValues($values);

        return view('statamic-xero::xero-authentication', [
            'connected'        => $xeroCredentials->exists(),
            'error'            => $error ?? null,
            'organisationName' => $organisationName ?? null,
            'username'         => $username ?? null,
            'blueprint'        => $blueprint->toPublishArray(),
            'values'           => $fields->values(),
            'meta'             => $fields->meta(),
        ]);
    }

    public function authorise(Redirector $redirect, OauthCredentialManager $oauth)
    {
        $scopes = Config::get('xero.oauth.scopes');
        if (! in_array('accounting.contacts', $scopes)) {
            $scopes[] = 'accounting.contacts';
        }
        if (! in_array('accounting.transactions', $scopes)) {
            $scopes[] = 'accounting.transactions';
        }
        Config::set('xero.oauth.scopes', $scopes);

        return $redirect->to($oauth->getAuthorizationUrl());
    }

    public function update(Request $request)
    {
        $blueprint = $this->getBlueprint();

    // Get a Fields object, and populate it with the submitted values.
        $fields = $blueprint->fields()->addValues($request->all());

    // Perform validation. Like Laravel's standard validation, if it fails,
    // a 422 response will be sent back with all the validation errors.
        $fields->validate();

    // Perform post-processing. This will convert values the Vue components
    // were using into values suitable for putting into storage.
        $values = $fields->process()->values();

        // Do something with the values. Here we'll update the product model.
        app(ConfigStore::class)->store($values->toArray());
    }

    protected function getBlueprint(): \Statamic\Fields\Blueprint
    {

        $accounts = app(XeroHelper::class)->getAccounts()->toArray();
        $taxCodes = app(XeroHelper::class)->getTaxCodes()->toArray();

        return Blueprint::makeFromFields([
            'sales_revenue'       => [
                'type'         => 'select',
                'instructions' => 'Map sales revenue to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'accounts_receivable' => [
                'type'         => 'select',
                'instructions' => 'Map successfully paid orders to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'shipping_delivery'   => [
                'display'      => 'Shipping & Delivery',
                'type'         => 'select',
                'instructions' => 'Map shipping costs to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'rounding'            => [
                'type'         => 'select',
                'instructions' => 'Rounding adjustments to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'discounts'           => [
                'type'         => 'select',
                'instructions' => 'Map discounts to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'additional_fees'     => [
                'type'         => 'select',
                'instructions' => 'Map additional fees /adjustments to this account',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an account',
                'options'      => $accounts
            ],
            'tax_code'            => [
                'type'         => 'select',
                'instructions' => 'Map taxable items to this tax code / leave empty for default',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  => 'Please select an tax rate',
                'options'      => $taxCodes
            ],
            'tax_free_code'       => [
                'type'         => 'select',
                'instructions' => 'Map non-taxable items to this tax code / leave empty for default',
                'validate'     => 'nullable',
                'clearable'    => true,
                'width'        => 50,
                'placeholder'  =>  'Please select an tax rate',
                'options'      => $taxCodes
            ],
        ]);
    }
}
