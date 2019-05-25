<?php

namespace ApiBundle\Api\Resource\Trade\Factory;

class AlipayLegacyH5Trade extends BaseTrade
{
    protected $payment = 'alipay';

    protected $platformType = 'Wap';

    public function getCustomFields($params)
    {
        return array(
            'return_url' => $this->generateUrl('cashier_pay_return_for_h5', array('payment' => 'alipay'), true),
        );
    }

    public function getCustomResponse($trade)
    {
        return array(
            'payUrl' => $this->generateUrl('cashier_redirect_for_h5', array('tradeSn' => $trade['trade_sn'])),
        );
    }
}
