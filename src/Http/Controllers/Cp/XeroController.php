<?php

namespace Phpsa\StatamicXero\Http\Controllers\Cp;

use Illuminate\Http\Request;
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
            $error = $e->getMessage();
        }

        return view('statamic-xero::xero-authentication', [
            'connected'        => $xeroCredentials->exists(),
            'error'            => $error ?? null,
            'organisationName' => $organisationName ?? null,
            'username'         => $username ?? null
        ]);
    }


    public function manage(Request $request)
    {
    }
}
