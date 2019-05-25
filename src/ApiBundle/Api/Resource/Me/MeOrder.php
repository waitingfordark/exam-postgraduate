<?php

namespace ApiBundle\Api\Resource\Me;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use ApiBundle\Api\Annotation\ResponseFilter;

class MeOrder extends AbstractResource
{
    /**
     * @ResponseFilter(class="ApiBundle\Api\Resource\Me\MeOrderFilter", mode="simple")
     */
    public function search(ApiRequest $request)
    {
        $conditions = array(
            'user_id' => $this->getCurrentUser()->getId(),
        );
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $orders = $this->getOrderService()->searchOrders(
            $conditions,
            array('created_time' => 'DESC'),
            $offset,
            $limit
        );

        foreach ($orders as &$order) {
            $product = $this->getProduct($order['id']);
            $covers = $product->cover;
            $order['cover'] = count($covers) ? $covers : array('middle' => '');
            $order['targetType'] = $product->targetType;
            $order['targetId'] = $product->targetId;
            $order['targetUrl'] = $this->generateUrl($product->successUrl[0], $product->successUrl[1], true);
        }
        $total = $this->getOrderService()->countOrders($conditions);

        return $this->makePagingObject($orders, $total, $offset, $limit);
    }

    private function getProduct($orderId)
    {
        $orderItems = $this->getOrderService()->findOrderItemsByOrderId($orderId);
        $orderItem = reset($orderItems);

        return $this->getOrderFacadeService()->getOrderProductByOrderItem($orderItem);
    }

    protected function getOrderService()
    {
        return $this->getBiz()->service('Order:OrderService');
    }

    private function getOrderFacadeService()
    {
        return $this->getBiz()->service('OrderFacade:OrderFacadeService');
    }
}
