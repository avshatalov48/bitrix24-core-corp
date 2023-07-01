<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Service\Container;
use Bitrix\SalesCenter\Controller\Order;

class Payment extends Base
{
	use SalescenterControllerWrapper;

	public function createPaymentAction(array $options): bool
	{
		$products = $this->prepareBasketItems($options['products']);
		unset($options['products']);
		$entity = Container::getInstance()->getFactory((int)$options['ownerTypeId'])->getItem((int)$options['ownerId']);
		$options['orderId'] = $this->getOrderId($entity);

		$this->forward(
			Order::class,
			'createPayment',
			[
				'basketItems' => $products,
				'options' => $options,
			]
		);

		$errors = $this->getErrors();
		if (!empty($errors))
		{
			$this->errorCollection->clear();
			$errors = $this->markErrorsAsPublic($errors);
			$this->addErrors($errors);
		}

		return true;
	}

	public function resendMessageAction(array $options): bool
	{
		$factory = Container::getInstance()->getFactory((int)$options['ownerTypeId']);
		if (!$factory)
		{
			return false;
		}

		$entity = $factory->getItem((int)$options['ownerId']);
		if (!$entity)
		{
			return false;
		}
		$orderId = $this->getOrderId($entity);

		$this->forward(
			Order::class,
			'resendPayment',
			[
				'orderId' => $orderId,
				'paymentId' => $options['paymentId'],
				'shipmentId' => $options['shipmentId'],
				'options' => $options,
			]
		);

		$errors = $this->getErrors();
		if (!empty($errors))
		{
			$this->errorCollection->clear();
			$errors = $this->markErrorsAsPublic($errors);
			$this->addErrors($errors);
		}

		return true;
	}
}