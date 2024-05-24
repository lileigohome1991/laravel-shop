<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use App\Exceptions\InvalidRequestException;

use Endroid\QrCode\QrCode;
use App\Events\OrderPaid;

use Illuminate\Validation\Rule;
use App\Models\Installment;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            'out_trade_no' => $order->no, // 订单编号，需保证在商户端不重复
            'total_amount' => $order->total_amount, // 订单金额，单位元，支持小数点后两位
            'subject'      => '支付 Laravel Shop 的订单：'.$order->no, // 订单标题
        ]);
    }

    // 前端回调页面
    public function alipayReturn()
    {
        // 校验提交的参数是否合法
        // $data = app('alipay')->callback();
        // dd($data);

        try {
            app('alipay')->callback();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    // 服务器端回调
    public function alipayNotify()
    {
        // $data = app('alipay')->callback();
        // \Log::debug('Alipay notify', $data->all());

        // 校验输入参数
        $data  = app('alipay')->callback();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if(!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }
        // $data->out_trade_no 拿到订单流水号，并在数据库中查询
        $order = Order::where('no', $data->out_trade_no)->first();
        // 正常来说不太可能出现支付了一笔不存在的订单，这个判断只是加强系统健壮性。
        if (!$order) {
            return 'fail';
        }
        // 如果这笔订单的状态已经是已支付
        if ($order->paid_at) {
            // 返回数据给支付宝
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'        => Carbon::now(), // 支付时间
            'payment_method' => 'alipay', // 支付方式
            'payment_no'     => $data->trade_no, // 支付宝订单号
        ]);

        $this->afterPaid($order);
        return app('alipay')->success();
    }

/*******************************************************************************************************/ 
    public function payByWechat(Order $order, Request $request) {
        // 校验权限
        $this->authorize('own', $order);
        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // scan 方法为拉起微信扫码支付  之前是直接返回，现在把返回值放到一个变量里
        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $order->no,  // 商户订单流水号，与支付宝 out_trade_no 一样
            'total_fee' => $order->total_amount * 100, // 与支付宝不同，微信支付的金额单位是分。
            'body'      => '支付 Laravel Shop 的订单：'.$order->no, // 订单描述
        ]);
        
        // 把要转换的字符串作为 QrCode 的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data  = app('wechat_pay')->verify();
        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)->first();
        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }
        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        $this->afterPaid($order);
        return app('wechat_pay')->success();
    }
    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->callback(null, true);

        // 没有找到对应的订单，原则上不可能发生，保证代码健壮性
        if(!$order = Order::where('no', $data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            // 退款成功，将订单退款状态改成退款成功
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            // 退款失败，将具体状态存入 extra 字段，并表退款状态改成失败
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra
            ]);
        }

        return app('wechat_pay')->success();
    }



    //********************************分期付款*************************************************** */
    public function payByInstallment(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // 订单不满足最低分期要求
        if ($order->total_amount < config('app.min_installment_amount')) {
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }
        // 校验用户提交的还款月数，数值必须是我们配置好费率的期数
       
        $this->validate($request, [
            'count' => ['required', Rule::in(array_keys(config('app.installment_fee_rate')))],
        ]);

        // $this->validate($request, [
        //     'count' => ['required', function ($attribute, $value, $fail) {
        //                                 // return Rule::in(array_keys(config('app.installment_fee_rate')))
        //                                 if(!in_array($value,array_keys(config('app.installment_fee_rate')))){
        //                                     return $fail("期数不对");
        //                                 }
        //                             }
        //     ],
        // ]);

        // 删除同一笔商品订单发起过其他的状态是未支付的分期付款，避免同一笔商品订单有多个分期付款
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();
        $count = $request->input('count');
        // 创建一个新的分期付款对象
        $installment = new Installment([
            // 总本金即为商品订单总金额
            'total_amount' => $order->total_amount,
            // 分期期数
            'count'        => $count,
            // 从配置文件中读取相应期数的费率
            'fee_rate'     => config('app.installment_fee_rate')[$count],
            // 从配置文件中读取当期逾期费率
            'fine_rate'    => config('app.installment_fine_rate'),
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();
        // 第一期的还款截止日期为明天凌晨 0 点
        $dueDate = Carbon::tomorrow();
        // 计算每一期的本金
        $base = big_number($order->total_amount)->divide($count)->getValue();
        // 计算每一期的手续费
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
        // 根据用户选择的还款期数，创建对应数量的还款计划
        for ($i = 0; $i < $count; $i++) {
            // 最后一期的本金需要用总本金减去前面几期的本金
            if ($i === $count - 1) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }
            $installment->items()->create([
                'sequence' => $i,
                'base'     => $base,
                'fee'      => $fee,
                'due_date' => $dueDate,
            ]);
            // 还款截止日期加 30 天
            $dueDate = $dueDate->copy()->addDays(30);
        }

        return $installment;
    }



    public function payInstallmentsByAlipay(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }
        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('该分期订单已结清');
        }
        // 获取当前分期付款最近的一个未支付的还款计划
        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()) {
            // 如果没有未支付的还款，原则上不可能，因为如果分期已结清则在上一个判断就退出了
            throw new InvalidRequestException('该分期订单已结清');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            // 支付订单号使用分期流水号+还款计划编号
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject'      => '支付 Laravel Shop 的分期订单：'.$installment->no,
            // 这里的 notify_url 和 return_url 可以覆盖掉在 AppServiceProvider 设置的回调地址
            // 'notify_url'   => '', // todo
            // 'return_url'   => '' // todo,

            // 'notify_url'   => ngrok_url('installments.alipay.notify'),
            'return_url'   => route('payment.installments.alipay.return'),
        ]);
    }

    // 支付宝前端回调
    public function alipayInstallmentsReturn()
    {
        try {
            app('alipay')->callback();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    // 支付宝后端回调
    public function alipayInstallmentsNotify()
    {
        // 校验支付宝回调参数是否正确
        $data = app('alipay')->callback();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }


        if ($this->paid($data->out_trade_no, 'alipay', $data->trade_no)) {
            return app('alipay')->success();
        }

        return 'fail';

        // // 拉起支付时使用的支付订单号是由分期流水号 + 还款计划编号组成的
        // // 因此可以通过支付订单号来还原出这笔还款是哪个分期付款的哪个还款计划
        // list($no, $sequence) = explode('_', $data->out_trade_no);
        // // 根据分期流水号查询对应的分期记录，原则上不会找不到，这里的判断只是增强代码健壮性
        // if (!$installment = Installment::where('no', $no)->first()) {
        //     return 'fail';
        // }
        // // 根据还款计划编号查询对应的还款计划，原则上不会找不到，这里的判断只是增强代码健壮性
        // if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
        //     return 'fail';
        // }
        // // 如果这个还款计划的支付状态是已支付，则告知支付宝此订单已完成，并不再执行后续逻辑
        // if ($item->paid_at) {
        //     return app('alipay')->success();
        // }

        // // 使用事务，保证数据一致性
        // \DB::transaction(function () use ($data, $no, $installment, $item) {
        //     // 更新对应的还款计划
        //     $item->update([
        //         'paid_at'        => Carbon::now(), // 支付时间
        //         'payment_method' => 'alipay', // 支付方式
        //         'payment_no'     => $data->trade_no, // 支付宝订单号
        //     ]);

        //     // 如果这是第一笔还款
        //     if ($item->sequence === 0) {
        //         // 将分期付款的状态改为还款中
        //         $installment->update(['status' => Installment::STATUS_REPAYING]);
        //         // 将分期付款对应的商品订单状态改为已支付
        //         $installment->order->update([
        //             'paid_at'        => Carbon::now(),
        //             'payment_method' => 'installment', // 支付方式为分期付款
        //             'payment_no'     => $no, // 支付订单号为分期付款的流水号
        //         ]);
        //         // 触发商品订单已支付的事件
        //         event(new OrderPaid($installment->order));
        //     }

        //     // 如果这是最后一笔还款
        //     if ($item->sequence === $installment->count - 1) {
        //         // 将分期付款状态改为已结清
        //         $installment->update(['status' => Installment::STATUS_FINISHED]);
        //     }
        // });

        // return app('alipay')->success();
    }


    public function payInstallmentsByWechat(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }
        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('该分期订单已结清');
        }
        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()) {
            throw new InvalidRequestException('该分期订单已结清');
        }

        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_fee'    => $nextItem->total * 100,
            'body'         => '支付 Laravel Shop 的分期订单：'.$installment->no,
            // 'notify_url'   => '', // todo
            'notify_url'   => ngrok_url('payment.installments.wechat.notify'),
        ]);
        // 把要转换的字符串作为 QrCode 的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }


    public function wechatInstallmentsNotify()
    {
        $data = app('wechat_pay')->verify();
        if ($this->paid($data->out_trade_no, 'wechat', $data->transaction_id)) {
            return app('wechat_pay')->success();
        }

        return 'fail';
    }

    protected function paid($outTradeNo, $paymentMethod, $paymentNo)
    {
        list($no, $sequence) = explode('_', $outTradeNo);
        if (!$installment = Installment::where('no', $no)->first()) {
            return false;
        }
        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return false;
        }
        if ($item->paid_at) {
            return true;
        }

        \DB::transaction(function () use ($paymentNo, $paymentMethod, $no, $installment, $item) {
            $item->update([
                'paid_at'        => Carbon::now(),
                'payment_method' => $paymentMethod,
                'payment_no'     => $paymentNo,
            ]);

            if ($item->sequence === 0) {
                $installment->update(['status' => Installment::STATUS_REPAYING]);
                $installment->order->update([
                    'paid_at'        => Carbon::now(),
                    'payment_method' => 'installment',
                    'payment_no'     => $no,
                ]);
                event(new OrderPaid($installment->order));
            }
            if ($item->sequence === $installment->count - 1) {
                $installment->update(['status' => Installment::STATUS_FINISHED]);
            }
        });

        return true;
    }

}
