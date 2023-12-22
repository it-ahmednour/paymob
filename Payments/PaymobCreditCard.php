<?php

namespace App\Classes\Payments;
use Illuminate\Support\Facades\Http;

class PaymobCreditCard extends Paymob
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
    public function checkOut($clientInfo , $mobileWallet){
        $response = Http::withHeaders(['content-type' => 'application/json'])
        ->post('https://accept.paymobsolutions.com/api/acceptance/payment_keys', [
            'auth_token'            => $this->authToken,
            'amount_cents'          => $this->amountCents,
            'currency'              => config('paymob.currency'),
            'order_id'              => $this->paymobOrderId,
            'integration_id'        => config('paymob.credit_integration_id'),
            'expiration'            => config('paymob.expiration'),
            'billing_data'          => $clientInfo,
            'lock_order_when_paid'  => true,
        ])->throw();

        return response()->json([
            'url' => 'https://accept.paymobsolutions.com/api/acceptance/iframes/'.config('paymob.credit_iframe_id').'?payment_token='.$response['token'],
        ],200);
    }
}
