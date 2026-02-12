<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Room;
use App\Models\Room_manage;
use App\Models\Booking_manage;
use App\Models\Country;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Services\PayPalService;

use Razorpay\Api\Api;

use Mollie\Laravel\Facades\Mollie;

class CheckoutFrontController extends Controller
{
    protected $PayPalClient;

    public function __construct(PayPalService $PayPalClient)
    {
        $this->PayPalClient = $PayPalClient;
    }
	
    public function LoadCheckout($id, $title)
    {
		$country_list = Country::where('is_publish', '=', 1)->orderBy('country_name', 'ASC')->get();
		$rtdata = Room::where('id', $id)->where('is_publish', '=', 1)->first();
		$total_room = Room_manage::where('roomtype_id', '=', $id)->where('book_status', '=', 2)->where('is_publish', '=', 1)->count();
		
        return view('frontend.checkout', compact('country_list', 'rtdata', 'total_room'));
    }
	
    public function LoadThank()
    {	
		return view('frontend.thank');
    }

    public function SendBookingRequest(Request $request)
    {
		$res = array();
		$gtext = gtext();
		$gtax = getTax();
		
		Session::forget('pt_payment_error');
		
		$roomtype_id = $request->input('roomtype_id');
		$total_room = $request->input('room');
		
		if($total_room == 0){
			$res['msgType'] = 'error';
			$res['msg'] = array('oneError' => array(__('Oops! Your booking request is failed. Please enter room number.')));
			return response()->json($res);
		}
		
		$customer_id = '';
		
		$newaccount = $request->input('new_account');
		
		if ($newaccount == 'true' || $newaccount == 'on') {
			$new_account = 1;
		}else {
			$new_account = 0;
		}

		$payment_method_id = $request->input('payment_method');

		if($new_account == 1){
			
			$validator = Validator::make($request->all(),[
				'name' => 'required',
				'phone' => 'required',
				'country' => 'required',
				'state' => 'required',
				'zip_code' => 'required',
				'city' => 'required',
				'address' => 'required',
				'payment_method' => 'required',
				'checkin_date' => 'required',
				'checkout_date' => 'required',
				'room' => 'required',
				'email' => 'required|email|unique:users',
				'password' => 'required|confirmed',
			]);

			if(!$validator->passes()){
				$res['msgType'] = 'error';
				$res['msg'] = $validator->errors()->toArray();
				return response()->json($res);
			}

			$userData = array(
				'name' => $request->input('name'),
				'email' => $request->input('email'),
				'phone' => $request->input('phone'),
				'address' => $request->input('address'),
				'state' => $request->input('state'),
				'zip_code' => $request->input('zip_code'),
				'city' => $request->input('city'),
				'password' => Hash::make($request->input('password')),
				'bactive' => base64_encode($request->input('password')),
				'status_id' => 1,
				'role_id' => 2
			);
			
			$customer_id = User::create($userData)->id;
			
		}else{
			
			$validator = Validator::make($request->all(),[
				'name' => 'required',
				'email' => 'required',
				'phone' => 'required',
				'country' => 'required',
				'state' => 'required',
				'zip_code' => 'required',
				'city' => 'required',
				'address' => 'required',
				'payment_method' => 'required',
				'checkin_date' => 'required',
				'checkout_date' => 'required',
				'room' => 'required'
			]);
			
			if(!$validator->passes()){
				$res['msgType'] = 'error';
				$res['msg'] = $validator->errors()->toArray();
				return response()->json($res);
			}

			$customer_id = $request->input('customer_id');
		}
		
		$rtdata = Room::where('id', $roomtype_id)->where('is_publish', '=', 1)->first();
		
		$start_random = RandomString(3);
		$end_random = RandomString(3);
		
		$booking_no = $start_random.date("his").$end_random;

		$room_price = comma_remove($rtdata->price);
		$in_date = $request->input('checkin_date');
		$out_date = $request->input('checkout_date');
		
		$is_discount = $rtdata->is_discount;
		
		$total_days = DateDiffInDays($in_date, $out_date);
		
		$subtotal = $room_price*$total_room*$total_days;

		$total_discount = 0;
		if($is_discount == 1){
			if($rtdata->old_price !=''){
				$old_price = $rtdata->old_price;
				$discount = $old_price*$total_room*$total_days;
				$total_discount = $discount - $subtotal;
			}
		}		
		
		$tax_rate = $gtax['percentage'];

		$total_tax = (($subtotal*$tax_rate)/100);
		
		$total_amount = $subtotal+$total_tax;
		$paid_amount = 0;
		$due_amount = $total_amount;

		$data = array(
			'booking_no' => $booking_no,
			'roomtype_id' => $roomtype_id,
			'customer_id' => $customer_id,
			'payment_method_id' => $payment_method_id,
			'payment_status_id' => 2,
			'booking_status_id' => 1,
			'total_room' => $total_room,
			'total_price' => $room_price,
			'discount' => $total_discount,
			'tax' => $total_tax,
			'subtotal' => $subtotal,
			'total_amount' => $total_amount,
			'paid_amount' => $paid_amount,
			'due_amount' => $due_amount,
			'in_date' => $in_date,
			'out_date' => $out_date,
			'name' => $request->input('name'),
			'email' => $request->input('email'),
			'phone' => $request->input('phone'),
			'country' => $request->input('country'),
			'state' => $request->input('state'),
			'zip_code' => $request->input('zip_code'),
			'city' => $request->input('city'),
			'address' => $request->input('address'),
			'comments' => $request->input('comments')
		);

		$order_master_id = Booking_manage::create($data)->id;
		
		//set order master ids into session
		Session::put('order_master_ids', $order_master_id);
		
		if($order_master_id>0){
			$intent = '';

			$description = 'Total Room: '.$total_room.', Booking No: '. $booking_no;

			$totalAmount = $total_amount;

			//Stripe
			if($payment_method_id == 3){
				if($gtext['stripe_isenable'] == 1){
					$stripe_secret = $gtext['stripe_secret'];
					
					// Enter Your Stripe Secret
					\Stripe\Stripe::setApiKey($stripe_secret);
							
					$amount = $totalAmount;
					$amount *= 100;
					$amount = (int) $amount;
					if($gtext['stripe_currency'] !=''){
						$currency = $gtext['stripe_currency'];
					}else{
						$currency = 'usd';
					}
					
					$payment_intent = \Stripe\PaymentIntent::create([
						'amount' => $amount,
						'currency' => $currency,
						'description' => $description,
						'payment_method_types' => ['card']
					]);
					$intent = $payment_intent->client_secret;
				}
				
			//Paypal
			}elseif($payment_method_id == 4){
				
				if($gtext['isenable_paypal'] == 1){
				
					$PayPalData = [
						'intent' => 'CAPTURE',
						"application_context" => [
							"return_url" => route('success.PayPalPayment'),
							"cancel_url" => route('cancel.PayPalPayment'),
						],
					   "purchase_units" => [
							0 => [
								"amount" => [
									"currency_code" => $gtext['paypal_currency'],
									"value" => "{$totalAmount}",
								],
								"description" => $description
							]
						]
					];
					
					$accessToken = $this->PayPalClient->generateAccessToken();
					$PayPalResponse = $this->PayPalClient->createOrder($accessToken, $PayPalData);
					
					if (isset($PayPalResponse['id']) && $PayPalResponse['id'] != null){
						foreach ($PayPalResponse['links'] as $links) {
							if ($links['rel'] == 'approve') {
								$redirect_url = $links['href'];
								break;
							}
						}
						
						if(isset($redirect_url)) {
							$intent = $redirect_url;
						}
					}else{
						
						Booking_manage::where('id', $order_master_id)->delete();
						
						$res['msgType'] = 'error';
						$res['msg'] = array('oneError' => array(__('Unknown error occurred')));
						return response()->json($res);
					}
				}
			
			//Razorpay
			}elseif($payment_method_id == 5){
				$intent = '';
				
				if($gtext['isenable_razorpay'] == 1){
					
					$razorpay_payment_id = $request->input('razorpay_payment_id');
					
					if($razorpay_payment_id == ''){
						$res['msgType'] = 'error';
						$res['msg'] = array('oneError' => array(__('Payment failed')));
						return response()->json($res);
					}
			
					$razorpay_key_id = $gtext['razorpay_key_id'];
					$razorpay_key_secret = $gtext['razorpay_key_secret'];
					
					$api = new Api($razorpay_key_id, $razorpay_key_secret);
					
					$payment = $api->payment->fetch($razorpay_payment_id);

					if(!empty($razorpay_payment_id)){
						
						try {
							$response = $api->payment->fetch($razorpay_payment_id)->capture(array('amount'=>$payment['amount'])); 
							
							$api->payment->fetch($razorpay_payment_id)->edit(array('notes'=> array('description'=> $description)));
							
						}catch (\Exception $e){
							
							Booking_manage::where('id', $order_master_id)->delete();
							
							$res['msgType'] = 'error';
							$res['msg'] = array('oneError' => array(__('Payment failed')));
							return response()->json($res);
						}            
					}
				}
			
			//Mollie
			}elseif($payment_method_id == 6){
	
				if($gtext['isenable_mollie'] == 1){

					$priceString = number_format($totalAmount, 2);
					$price = str_replace(",","", $priceString);
					$amount = (string) $price;
					// $amount = strval($price);

					$mollie_currency = $gtext['mollie_currency'];
						
					$mollie_api_key = $gtext['mollie_api_key'];
					Mollie::api()->setApiKey($mollie_api_key); // your mollie test api key

					$makePayment = [
						"amount" => [
							"currency" => $mollie_currency, //'EUR', // Type of currency you want to send
							"value" => $amount, //'30.00' You must send the correct number of decimals, thus we enforce the use of strings
						],
						"description" => $description, 
						"redirectUrl" => route('frontend.thank') // after the payment completion where you to redirect
					];
				
					$payment = Mollie::api()->payments->create($makePayment);
				
					$payment = Mollie::api()->payments->get($payment->id);
					
					$intent = $payment->getCheckoutUrl();
				}
				
			// Paystack
			}elseif($payment_method_id == 7){

				if($gtext['isenable_paystack'] == 1){

					$paystack_secret = $gtext['paystack_secret_key'];
					$paystack_currency = $gtext['paystack_currency'] ?? 'NGN';

					$reference = 'BK_'.$booking_no.'_'.uniqid();

					// save reference for later verification
					Booking_manage::where('id', $order_master_id)->update([
						'payment_reference' => $reference
					]);

					$amount = (int) ($totalAmount * 100); // Paystack uses kobo

					$fields = [
						'email' => $request->input('email'),
						'amount' => $amount,
						'currency' => $paystack_currency,
						'reference' => $reference,
						'callback_url' => route('paystack.callback'),
						'metadata' => [
							'booking_id' => $order_master_id,
							'booking_no' => $booking_no,
							'customer_id' => $customer_id
						]
					];

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/initialize");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
					curl_setopt($ch, CURLOPT_HTTPHEADER, [
						"Authorization: Bearer $paystack_secret",
						"Content-Type: application/json"
					]);

					$response = curl_exec($ch);
					curl_close($ch);

					$paystack = json_decode($response, true);

					if (!$paystack || $paystack['status'] !== true) {

						Booking_manage::where('id', $order_master_id)->delete();

						$res['msgType'] = 'error';
						$res['msg'] = array('oneError' => array(__('Unable to initialize Paystack payment')));
						return response()->json($res);
					}

					$intent = $paystack['data']['authorization_url'];
				}
			}else{
				$intent = '';
			}
			
			if($payment_method_id != 4){

				if($gtext['ismail'] == 1){
					BookingNotify($order_master_id, 'booking_request');
				}
			}
			
			$res['msgType'] = 'success';
			$res['msg'] = __('Your booking request is successfully.');
			$res['intent'] = $intent;
			return response()->json($res);
		}else{
			$res['msgType'] = 'error';
			$res['msg'] = __('Oops! Your booking request is failed. Please try again.');
			return response()->json($res);
		}
    }

	public function PaystackCallback(Request $request)
{
    $reference = $request->query('reference');

    if (!$reference) {
        return Redirect::route('frontend.checkout');
    }

    $gtext = gtext();
    $secret = $gtext['paystack_secret_key'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/".$reference);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secret"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $paystack = json_decode($response, true);

    if ($paystack && $paystack['status'] === true && $paystack['data']['status'] === 'success') {

        $booking_id = $paystack['data']['metadata']['booking_id'];

        Booking_manage::where('id', $booking_id)->update([
            'payment_status_id' => 1,
            'paid_amount' => $paystack['data']['amount'] / 100,
            'due_amount' => 0
        ]);

        if (gtext()['ismail'] == 1) {
            BookingNotify($booking_id, 'booking_request');
        }

        return Redirect::route('frontend.thank');
    }

    return Redirect::route('frontend.checkout');
}


public function PaystackWebhook(Request $request)
{
    $secret = gtext()['paystack_secret_key'];

    $signature = $request->header('x-paystack-signature');
    $payload = $request->getContent();

    if ($signature !== hash_hmac('sha512', $payload, $secret)) {
        abort(401);
    }

    $event = json_decode($payload, true);

    if ($event['event'] === 'charge.success') {

        $data = $event['data'];
        $reference = $data['reference'];
        $booking_id = $data['metadata']['booking_id'];

        Booking_manage::where('id', $booking_id)->update([
            'payment_status_id' => 1,
            'paid_amount' => $data['amount'] / 100,
            'due_amount' => 0
        ]);
    }

    return response()->json(['status' => 'ok']);
}

	
    public function PayPalPaymentSuccess(Request $request){
		$gtext = gtext();
		
		$order_master_ids = Session::get('order_master_ids');
        Session::forget('order_master_ids');
		
		$accessToken = $this->PayPalClient->generateAccessToken();
		$OrderId = $request['token'];

        if (empty($request['PayerID']) || empty($request['token'])) {
			
			Booking_manage::where('id', $order_master_ids)->delete();
			
            \Session::put('pt_payment_error', __('Payment failed'));
            return Redirect::route('frontend.checkout');
        }
		
		$response = $this->PayPalClient->capturePaymentOrder($accessToken, $OrderId);
		$resArr = json_decode($response->getBody(), true); 

        // Handle the response as needed
        if ($response->getStatusCode() === 201) {

			if (isset($resArr['status']) && $resArr['status'] == 'COMPLETED') {
				
				// $TransactionID = $resArr['purchase_units'][0]['payments']['captures'][0]['id'];
				
				if($gtext['ismail'] == 1){
					BookingNotify($order_master_ids, 'booking_request');
				}
				
				return Redirect::route('frontend.thank');
			}
        } else {
			Booking_manage::where('id', $order_master_ids)->delete();
			
			\Session::put('pt_payment_error', __('Payment failed'));
			return Redirect::route('frontend.checkout');
        }
    }
	
    public function PayPalPaymentCancel(){
		
		$order_master_ids = Session::get('order_master_ids');
        Session::forget('order_master_ids');
		
		Booking_manage::where('id', $order_master_ids)->delete();

		\Session::put('pt_payment_error', __('Payment failed'));
		return Redirect::route('frontend.checkout');
    }
	
    public function getCheckOutTotalPrice(Request $request)
    {
		$res = array();
		$gtext = gtext();
		$gtax = getTax();

		$roomtype_id = $request->input('roomtype_id');
		$in_date = $request->input('checkin_date');
		$out_date = $request->input('checkout_date');
		$total_room = $request->input('total_room');
		
		$rtdata = Room::where('id', $roomtype_id)->where('is_publish', '=', 1)->first();

		$room_price = comma_remove($rtdata->price);
		
		$is_discount = $rtdata->is_discount;

		$total_days = DateDiffInDays($in_date, $out_date);
		
		$subtotal = $room_price*$total_room*$total_days;
		
		$total_discount = 0;
		if($is_discount == 1){
			if($rtdata->old_price !=''){
				$old_price = $rtdata->old_price;
				$discount = $old_price*$total_room*$total_days;
				$total_discount = $discount - $subtotal;
			}
		}

		$tax_rate = $gtax['percentage'];

		$total_tax = (($subtotal*$tax_rate)/100);
		
		$total_amount = $subtotal+$total_tax;
		
		$res['subtotal'] = $subtotal;
		$res['total_tax'] = $total_tax;
		$res['total_amount'] = $total_amount;
		
		if($gtext['currency_position'] == 'left'){
			$res['total_table'] = '<table class="table total-price-card">
					<tbody>
						<tr><td><span class="title">'.__('Subtotal').'</span><span class="price">'.$gtext['currency_icon'].NumberFormat($subtotal).'</span></td></tr>
						<tr><td><span class="title">'.__('Tax').'</span><span class="price">'.$gtext['currency_icon'].NumberFormat($total_tax).'</span></td></tr>
						<tr><td><span class="title">'.__('Discount').'</span><span class="price">'.$gtext['currency_icon'].NumberFormat($total_discount).'</span></td></tr>
						<tr><td><span class="title">'.__('Total').'</span><span class="price">'.$gtext['currency_icon'].NumberFormat($total_amount).'</span></td></tr>
					</tbody>
				</table>';
		}else{
			$res['total_table'] = '<table class="table total-price-card">
					<tbody>
						<tr><td><span class="title">'.__('Subtotal').'</span><span class="price">'.NumberFormat($subtotal).$gtext['currency_icon'].'</span></td></tr>
						<tr><td><span class="title">'.__('Tax').'</span><span class="price">'.NumberFormat($total_tax).$gtext['currency_icon'].'</span></td></tr>
						<tr><td><span class="title">'.__('Discount').'</span><span class="price">'.NumberFormat($total_discount).$gtext['currency_icon'].'</span></td></tr>
						<tr><td><span class="title">'.__('Total').'</span><span class="price">'.NumberFormat($total_amount).$gtext['currency_icon'].'</span></td></tr>
					</tbody>
				</table>';
		}

		return response()->json($res);
    }	
}
