<?php

namespace App\Classes\Payments;
use Illuminate\Support\Facades\Http;

class Paymob
{
    protected $authToken;
    protected $amountCents;
    protected $paymobOrderId;

    public function __construct()
    {
        $this->authToken = $this->getAuthToken();
    }

    ### Refund Tranasaction To Client ####
    public function refund($trans_id, $amount)
    {
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymob.com/api/acceptance/void_refund/refund', [
            'auth_token'        => $this->authToken,
            'transaction_id'    => $trans_id,
            'amount_cents'      => ($amount * 100),
        ])->throw();
        return $response;
    }

    ### Void Tranasaction To Client ####
    public function void($trans_id)
    {
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post("https://accept.paymob.com/api/acceptance/void_refund/void?token={$this->authToken}", [
            'transaction_id'    => $trans_id,
        ])->throw();
        return $response;
    }

    ############# Private Funtcions ################
    protected function getAuthToken()
    {
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymobsolutions.com/api/auth/tokens', [
            'api_key' => config('paymob.api_key'),
        ])->throw();
        return $response['token'];
    }

    ### Validate Tranasaction HMAC Form Paymob ####
    public function validateHmac($data)
    {

        $response = Http::withToken($this->authToken)->withHeaders(['content-type' => 'application/json'])
        ->get("https://accept.paymobsolutions.com/api/acceptance/transactions/{$trans_id}/hmac_calc")->throw();
        return hash_hmac('sha512', $response['hmac_string'], config('paymob.hmac_key')) ? true : false;
    }

}
