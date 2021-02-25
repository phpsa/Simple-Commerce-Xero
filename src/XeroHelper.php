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

    public function getXeroApi()
    {
        return resolve(AccountingApi::class);
    }

    public function flushAllCaches()
    {
        collect([
            'statamic_xero_api_accounts'
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


    public function splitNames($name)
    {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . preg_quote($last_name, '#') . '#', '', $name));
        return [$first_name, $last_name];
    }
}

// $where = 'Status=="ACTIVE"';
// $accounts = $apiInstance->getAccounts($xeroTenantId, null, $where);
