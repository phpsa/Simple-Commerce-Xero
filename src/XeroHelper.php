<?php

namespace Phpsa\StatamicXero;

use Exception;
use Illuminate\Support\Facades\Cache;
use XeroAPI\XeroPHP\Api\AccountingApi;
use Webfox\Xero\OauthCredentialManager;
use Illuminate\Support\Traits\ForwardsCalls;
use Throwable;

class XeroHelper
{
    use ForwardsCalls;

    protected $xeroCredentials = null;

    public function __construct(OauthCredentialManager $xeroCredentials)
    {
        $this->xeroCredentials = $xeroCredentials;
    }

    public function getTenantId()
    {
        return $this->xeroCredentials->getTenantId();
    }

    public function getAccounts()
    {

        $accounts = Cache::get('statamic_xero_api_accounts');

        if (! $accounts) {
            //$apiInstance->getAccounts($xeroTenantId, null, $where);
            $xero             = resolve(AccountingApi::class);
            try {
                $accounts = collect($xero->getAccounts($this->getTenantId(), null, 'Status=="ACTIVE"')->getAccounts())->mapWithKeys(function ($account) {
                    return [
                        $account['code'] => "{$account['name']} - {$account['code']} - {$account['type']}"
                    ];
                });
                if ($accounts->count()) {
                    Cache::add('statamic_xero_api_accounts', $accounts);
                }
            } catch (Throwable $e) {
               // throw new Exception($e->getMessage(), $e->getCode(), $e);
                return collect([]);
            }
        }

        return $accounts;
    }

    public function getContacts($where = '')
    {
        try {
            return collect(
                $this->getXeroApi()->getContacts(
                    $this->getTenantId(),
                    null,
                    $where
                )->getContacts()
            );
        } catch (Throwable $e) {
            return collect([]);
        }
    }

    public function getTaxCodes()
    {
        $taxes =   Cache::get('statamic_xero_api_taxrates');
        try {
            $taxes = collect($this->getXeroApi()->getTaxRates(
                $this->getTenantId(),
                'Status=="ACTIVE"'
            )->getTaxRates())->mapWithKeys(function ($tax) {

                        return [
                            $tax['tax_type'] => "{$tax['tax_type']} - {$tax['name']} - {$tax['display_tax_rate']}"
                        ];
            });
            if ($taxes->count()) {
                Cache::add('statamic_xero_api_taxrates', $taxes);
            }
        } catch (Throwable $e) {
            return collect([]);
        }

        return $taxes;
    }

    public function createInvoices($invoice)
    {
        return $this->getXeroApi()->createInvoices($this->getTenantId(), $invoice);
    }

    public function getXeroApi(): AccountingApi
    {
        return resolve(AccountingApi::class);
    }

    public function flushAllCaches(): void
    {
        collect([
            'statamic_xero_api_accounts',
            'statamic_xero_api_taxrates'
        ])->each(function ($item) {
            Cache::forget($item);
        });
    }


    public function __call($method, $parameters)
    {
        $xero             = resolve(AccountingApi::class);
        return $this->forwardCallTo(
            $xero,
            $method,
            $parameters
        );
    }


    public function splitNames($name): array
    {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . preg_quote($last_name, '#') . '#', '', $name));
        return [$first_name, $last_name];
    }


    public function withDecimals($number, $places = 2)
    {
        return number_format((float)$number, $places, '.', '');
    }
}

// $where = 'Status=="ACTIVE"';
// $accounts = $apiInstance->getAccounts($xeroTenantId, null, $where);
