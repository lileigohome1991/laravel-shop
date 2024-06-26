<?php

use Illuminate\Support\Facades\Route;

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


// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Route::get('/', 'PagesController@root')->name('root')->middleware('verified');  邮箱验证
// Route::get('/', 'PagesController@root')->name('root');
Route::redirect('/', '/products')->name('root');

// Auth::routes();
Auth::routes(['verify' => true]);


//auth 中间件代表需要登录，verified中间件代表需要经过邮箱验证
Route::group(['middleware'=>['auth','verified']],function(){  
    Route::get('user_addresses','UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create','UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses', 'UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{user_address}', 'UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{user_address}', 'UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{user_address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');

    Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
    Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');
    Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');

    Route::post('cart', 'CartController@add')->name('cart.add');
    Route::get('cart', 'CartController@index')->name('cart.index');
    Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove');

    Route::post('orders', 'OrdersController@store')->name('orders.store');

    Route::get('orders','OrdersController@index')->name('orders.index');

    Route::get('orders/{order}', 'OrdersController@show')->name('orders.show');

    Route::get('payment/{order}/alipay','PaymentController@payByAlipay')->name('payment.alipay');

    
    Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');

    Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat');

    Route::post('orders/{order}/received', 'OrdersController@received')->name('orders.received');

    Route::get('orders/{order}/review', 'OrdersController@review')->name('orders.review.show');
   Route::post('orders/{order}/review', 'OrdersController@sendReview')->name('orders.review.store');

   Route::post('orders/{order}/apply_refund', 'OrdersController@applyRefund')->name('orders.apply_refund');

   Route::get('coupon_codes/{code}', 'CouponCodesController@show')->name('coupon_codes.show');

   Route::post('crowdfunding_orders', 'OrdersController@crowdfunding')->name('crowdfunding_orders.store');


   Route::post('payment/{order}/installment', 'PaymentController@payByInstallment')->name('payment.installment');
   Route::get('installments', 'InstallmentsController@index')->name('installments.index');

   Route::get('installments/{installment}', 'InstallmentsController@show')->name('installments.show');

   Route::get('payment/{installment}/installmentsAlipay', 'PaymentController@payInstallmentsByAlipay')->name('payment.installments.alipay');
    Route::get('payment/alipay/installmentsReturn', 'PaymentController@alipayInstallmentsReturn')->name('payment.installments.alipay.return');

    Route::get('payment/{installment}/installmentsWechat', 'PaymentController@payInstallmentsByWechat')->name('payment.installments.wechat');

    Route::post('seckill_orders', 'OrdersController@seckill')->name('seckill_orders.store')->middleware('random_drop:50');
});

Route::get('products', 'ProductsController@index')->name('products.index');
Route::get('products/{product}', 'ProductsController@show')->name('products.show');


Route::post('payment/wechat/refund_notify', 'PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');
Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify');
Route::post('payment/wechat/notify', 'PaymentController@wechatNotify')->name('payment.wechat.notify');

// 后端回调不能放在 auth 中间件中
Route::post('payment/alipay/installmentsNotify', 'PaymentController@alipayInstallmentsNotify')->name('payment.installments.alipay.notify');
Route::post('payment/wechat/installmentsNotify', 'PaymentController@wechatInstallmentsNotify')->name('payment.installments.wechat.notify');

Route::post('installments/wechat/refund_notify', 'InstallmentsController@wechatRefundNotify')->name('installments.wechat.refund_notify');

//支付宝 沙箱测试
Route::get('alipay', function() {
    return app('alipay')->web([
        'out_trade_no' => ''.time(),
        'total_amount' => '0.01',
        'subject' => 'yansongda 测试 - 1',
    ]);
});



