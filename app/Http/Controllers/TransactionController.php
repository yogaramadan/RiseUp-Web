<?php

namespace App\Http\Controllers;

use App\Models\Funding;
use App\Models\Payment;
use App\Models\transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function Fund(Request $request)
    {

        \Midtrans\Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_ISPRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('IS3DS');


        $auth = auth()->user();

        $user = User::find($auth->id);

        $funding =  Funding::find($request->funding_id);




        $transaction_details = array(
            'order_id'    => time(),
            'gross_amount'  => $request->amount
        );; // Simpan perubahan

        $items = array(
            array(
                'id'       => 'fund-' . time(),
                'price'    => $request->amount,
                'quantity' => 1,
                'name'     => 'Funding '
            ),

        );


        $name = explode(' ', $user['name']);
        if (count($name) > 1) {
            $customer_details = array(
                'first_name'       => $name[0],
                'last_name'        => $name[1],
                'email'            => $user['email'],
                'phone'            => "08214124",

            );
        } else {
            $customer_details = array(
                'first_name'       => $name[0],
                'email'            => $user['email'],
                'phone'            => "08124124",

            );
        }

        $transaction = array(
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
            'item_details' => $items,

        );



        $paymentUrl = \Midtrans\Snap::createTransaction($transaction);
        $payment = Payment::create([
            'service_name' => 'Snap Midtrans',
            'payment_code' =>  $items[0]['id'],
            'payment_url' =>   $paymentUrl->redirect_url,
            'service_id' => 'midtrans',


        ]);

        transaction::create([

            'user_id' => $auth->id,
            'payment_id' => $payment->id ,
            'amount' => $request->amount,
            'status' => false,
            'funding_id' =>  $funding->id,

        ]);




        return response()->json([
            'message' => 'Success',
            'data' => $paymentUrl], 200);
    }
}
