<?php
namespace App\Services\Payments;

use Throwable;
use App\Models\Plan;
use App\Models\Payment;
use App\Classes\Payments\PaymobCreditCard;
use App\Classes\Payments\PaymobMobileWallet;
use App\Http\Requests\Manager\CheckoutRequest;

class PaymentService
{
    protected $request;
    protected $manager;
    protected $amount;

    public function __construct(CheckoutRequest $request)
    {
        $this->request = $request->validated();
        $this->manager = auth()->user();
        $this->amount = $this->getOrderAmount();
    }

    public function makePayment(){
        try {
            ### Create Local Payment Order ###
            $payment = Payment::create([
                'action'            => $this->getOrderAction(),
                'payment_method'    => $this->request['payment_type'],
                'plan_id'           => $this->request['plan_id'] ?? NULL,
                'manager_id'        => $this->manager->id,
                'subscription_id'   => $this->manager->subscription->id,
                'amount'            => $this->amount,
            ]);

            $clientInfo = [
                'first_name'    => $payment->manager->first_name,
                'last_name'     => $payment->manager->last_name,
                'email'         => $payment->manager->email,
                'phone_number'  => $payment->manager->mobile,
                'country'       => $payment->manager->country->code,
                'state'         => 'NA',
                'city'          => 'NA',
                'street'        => 'NA',
                'building'      => 'NA',
                'floor'         => 'NA',
                'apartment'     => 'NA',
            ];

            $mobileWallet = $this->request['wallet_number'] ?? null;
            return app(
                [
                    'CREDIT' => PaymobCreditCard::class,
                    'WALLET' => PaymobMobileWallet::class,
                ][$this->request['payment_type']]
            )->makePaymentOrder($payment->amount, $payment->merchant_order_id)->checkOut($clientInfo, $mobileWallet);

        } catch (Throwable $th) {
            throw $th;
        }
    }

    private function getOrderAction(){
        if($this->request['plan_id']){
            if($this->request['plan_id'] == subscription()->plan_id) return 'RENEW';
            return 'UPGRADE';
        }
        return 'DEPOSIT';
    }

    private function getOrderAmount(){
        if($this->request['plan_id']){
            return Plan::findOrFail($this->request['plan_id'])->price;
        }
        return subscription()->plan->price;
    }
}
