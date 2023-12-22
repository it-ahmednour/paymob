<?php

namespace App\Classes\Payments;
use Illuminate\Support\Facades\Http;

class PaymobMobileWallet extends Paymob
{

    ### Step-1 Create Order In Paymob ###
    public function makePaymentOrder($amount, $merchentOrderId){
        $this->amountCents = ($amount * 100);
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymobsolutions.com/api/ecommerce/orders', [
            'auth_token'        => $this->authToken,
            'delivery_needed'   => false,
            'amount_cents'      => $this->amountCents,
            'currency'          => config('paymob.currency'),
            'merchant_order_id' => $merchentOrderId,
            'api_source'        => 'INVOICE',
            'items'             => [],
        ])->throw();
        $this->paymobOrderId = $response['id'];
        return $this;
    }

    ## Step-2 Get Payment url From Paymob ###
    public function checkOut($clientInfo, $mobileWallet){
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymobsolutions.com/api/acceptance/payment_keys', [
            'auth_token'            => $this->authToken,
            'amount_cents'          => $this->amountCents,
            'currency'              => config('paymob.currency'),
            'order_id'              => $this->paymobOrderId,
            'integration_id'        => config('paymob.wallet_integration_id'),
            'expiration'            => config('paymob.expiration'),
            'billing_data'          => $clientInfo,
            'lock_order_when_paid'  => true,
        ])->throw();

        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymobsolutions.com/api/acceptance/payments/pay', [
            'source'        => [
                'identifier'    => $mobileWallet,
                'subtype'       => 'WALLET',
            ],
            'payment_token' => $response['token'],
        ])->throw();
        return response()->json([
            'url' => $response['redirect_url'] ? $response['redirect_url'] : $response['iframe_redirection_url'],
        ],200);
    }
}
