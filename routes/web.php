<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\User;
use Illuminate\Http\Request;
use App\Mail\EventRegistered;
use Illuminate\Support\Facades\Input;

Route::get('/', function () {
    return view('index');
});

Route::post('/register', function (Request $request) {
    //  If we have the email provided
    if (!empty($request->input('email'))) {
        // Get the user from the database
        $userExists = User::where('email', $request->input('email'))->count();

        //  If the user exists
        if ($userExists) {
            $user = User::where('email', $request->input('email'))->first();
            //  Find out if they have payed successfully before
            $hasTransactedBefore = $user->transactions->where('success_state', 1)->count();

            //  If they have paid
            if ($hasTransactedBefore != 0) {
                //  Notify the user
                $request->session()->flash('alert', array('You have already registered and paid for this event! Visit your email to verify. Thank you', 'success'));

                return back();
            }

            //  Go back and let the user know they have paid before
        } else {
            if ($request->input('abortRegistration') == '1') {
                //  Notify the user
                $request->session()->flash('alert', array('Registration with "'.$request->input('email').'" does not exist. Please re-enter the email you used when registering previously otherwise return to <a href="/">Registration</a>', 'success'));

                return back();
            } else {
                //  Create a new user
                $user = User::create($request->all());

                Mail::to($request->input('email'))->send(new EventRegistered($user));

                //Alert update success
                $request->session()->flash('alert', array('You have been registered successfully! Complete your application by paying for your seat', 'success'));
            }
        }

        if ($userExists) {
            $transaction = $user->transactions()->create([
                'user_id' => $user->id,
            ]);
            session(['user' => $user, 'transaction' => $transaction]);
        }

        return redirect('/payment-options');
    } else {
        $request->session()->flash('alert', array('Please register!', 'danger'));

        return redirect('/');
    }

    //Mail::to( $request->input('email_address'))->send(new EventRegistered($user));
    //} else {
        //$request->session()->flash('status','You have already Registered For this Event');
        // return view('welcome');
    //}
});

Route::get('/payment-options', function () {
    return view('payment');
});

Route::get('/paymentSuccessful', function () {
    $transaction_ID = Input::get('p2', false);    //  Transaction ID
    $amount = Input::get('p6', false);            //  Amount
    $payment_type = Input::get('p7', false);      //  Payment Type
    $package_type = Input::get('p8', false);      //  Package Type

    $transaction = Transaction::find($transaction_ID)->update([
        'payment_type' => $payment_type,
        'package_type' => $package_type,
        'amount' => $user->id,
        'success_state' => 1,
    ]);

    return view('paymentSuccessful');
});

Route::get('/paymentUnSuccessful', function () {
    return view('paymentUnSuccessful');
});

Route::get('/emailtemplate', function () {
    return view('sendEmailTemplate');
});

Route::get('/faq', function () {
    return view('faq');
});
