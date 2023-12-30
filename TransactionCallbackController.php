<?php

namespace App\Http\Controllers\Payments;

use Paymob;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Subscriptions\PaymobTransactionFailed;
use App\Jobs\Subscriptions\PaymobTransactionSuccess;

class TransactionCallbackController extends Controller
{

    public function processed(Request $request)
    {
        $data = $request->only([
            'obj.amount_cents',
            'obj.created_at',
            'obj.currency',
            'obj.error_occured',
            'obj.has_parent_transaction',
            'obj.id',
            'obj.integration_id',
            'obj.is_3d_secure',
            'obj.is_auth',
            'obj.is_capture',
            'obj.is_refunded',
            'obj.is_standalone_payment',
            'obj.is_voided',
            'obj.order.id',
            'obj.owner',
            'obj.pending',
            'obj.source_data.pan',
            'obj.source_data.sub_type',
            'obj.source_data.type',
            'obj.success'
        ]);
        $values = array_values($data['obj']);
        foreach ($values as &$val) {
            if (is_array($val)) {
                $val = array_values($val);
                $val = implode($val);
            }
            if ($val === true) $val = "true";
            if ($val === false) $val = "false";
        }
        $string = implode($values);
        $calculated_hmac = hash_hmac('sha512', $string, config('paymob.hmac_key'));
        $received_hmac =  $request->query('hmac');
        $is_valid_hmac =  hash_equals($receivedHMAC, $calculatedHMAC);
        if ($is_valid_hmac) {
            try {
                $order = Payment::where('merchant_order_id', $request->obj['order']['merchant_order_id'])->firstOrFail();
                $order->update([
                    'status' => $request->obj['success'] == "true" ? 'SUCCESS' : 'FAILED',
                    'source_data' => $request->obj['source_data']['pan'],
                    'gateway_response' => $request->obj['data']['message'],
                ]);
                if($order->status == 'SUCCESS') {
                    if($order->subscription->plan_id == $order->plan_id){
                        $order->subscription->renew();
                    }else{
                        $order->subscription->swap($order->plan_id);
                    }
                }
            } catch (\Throwable $th) {
                throw $th;
            }
        }else{
            return abort(403,'HMAC NOT VALIED');
        }
    }

    public function response(Request $request)
    {
        $status = filter_var($request['success'], FILTER_VALIDATE_BOOLEAN);
        return redirect()->route('manager.subscription.payments',[
            'status'    => $status,
            'message'   => $this->getErrorMessage($request['txn_response_code']),
        ]);
    }

    public function getErrorMessage($code){
        $errors=[
            'APPROVED'  => 'تم الدفع بنجاح',
            '200'       => 'تم الدفع بنجاح',
            'BLOCKED'   => 'تم حظر عملية الدفع من بوابة الدفع',
            'B'         => 'Process_Has_Been_Blocked_From_System',
            '5'         => 'ليس لديك رصيد كافي',
            'F'         => 'Your_card_is_not_authorized_with_3D_secure',
            '7'         => 'Incorrect_card_expiration_date',
            '2'         => 'Declined',
            '6051'      => 'Balance_is_not_enough',
            '637'       => 'خطأ في الرقم السري المتغير',
            '11'        => 'Security_checks_are_not_passed_by_the_system',
        ];
        if(isset($errors[$code])) return $errors[$code];
        else return 'An_error_occurred_while_executing_the_operation';
    }
}
