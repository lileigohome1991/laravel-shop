<?php
namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;

use App\Models\InstallmentItem;
use App\Models\Order;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);

        return view('installments.index', ['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);
        // 取出当前分期付款的所有的还款计划，并按还款顺序排序
        $items = $installment->items()->orderBy('sequence')->get();
        return view('installments.show', [
            'installment' => $installment,
            'items'       => $items,
            // 下一个未完成还款的还款计划
            'nextItem'    => $items->where('paid_at', null)->first(),
        ]);
    }

    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        // 校验微信回调参数
        $data = app('wechat_pay')->verify(null, true);
        // 根据单号拆解出对应的商品退款单号及对应的还款计划序号
        list($no, $sequence) = explode('_', $data['out_refund_no']);

        $item = InstallmentItem::query()
            ->whereHas('installment', function ($query) use ($no) {
                $query->whereHas('order', function ($query) use ($no) {
                    $query->where('refund_no', $no); // 根据订单表的退款流水号找到对应还款计划
                });
            })
            ->where('sequence', $sequence)
            ->first();

        // 没有找到对应的订单，原则上不可能发生，保证代码健壮性
        if (!$item) {
            return $failXml;
        }

        // 如果退款成功
        if ($data['refund_status'] === 'SUCCESS') {
            // 将还款计划退款状态改成退款成功
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
            ]);
            // // 设定一个标志位
            // $allSuccess = true;
            // foreach ($item->installment->items as $item) {
            //     if ($item->paid_at && $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS) {
            //         $allSuccess = false;
            //         break;
            //     }
            // }
            // // 如果所有退款都成功，则将对应商品订单的退款状态修改为退款成功
            // if ($allSuccess) {
            //     $item->installment->order->update([
            //         'refund_status' => Order::REFUND_STATUS_SUCCESS,
            //     ]);
            // }
            $item->installment->refreshRefundStatus();
        } else {
            // 否则将对应还款计划的退款状态改为退款失败
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
            ]);
        }

        return app('wechat_pay')->success();
    }
}