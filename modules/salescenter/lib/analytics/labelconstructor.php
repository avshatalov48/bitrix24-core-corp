<?php

namespace Bitrix\Salescenter\Analytics;

use Bitrix\Crm\Order;
use Bitrix\Main;
use Bitrix\Sale;

/**
 * Class LabelConstructor
 * @package Bitrix\Salescenter\Analytics
 */
class LabelConstructor
{
	/**
	 * @param Sale\Order $order
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getContextLabel(Sale\Order $order) : string
	{
		/** @var Sale\TradeBindingEntity $item */
		foreach ($order->getTradeBindingCollection() as $item)
		{
			$platform = $item->getTradePlatform();
			if ($platform)
			{
				return $platform->getAnalyticCode();
			}
		}

		if (
			$order->getField('ID_1C')
			&& $order->getField('EXTERNAL_ORDER') === 'Y'
		)
		{
			return '1c';
		}

		return '';
	}

	/**
	 * @param Sale\Payment $payment
	 * @return string
	 */
	public function getPaySystemTag(Sale\Payment $payment) : string
	{
		$service = $payment->getPaySystem();

		if ($service === null)
		{
			return '';
		}

		$tag = $service->getField('ACTION_FILE');
		if ($service->getField('PS_MODE'))
		{
			$tag .= ':'.$service->getField('PS_MODE');
		}

		return $tag;
	}

	/**
	 * @param Order\Order $order
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getChannelLabel(Order\Order $order) : string
	{
		$manager = new Order\SendingChannels\Manager();
		$data = $manager->getChannelList($order);

		return $data['CHANNEL_TYPE'] ?? '';
	}

	/**
	 * @param Order\Order $order
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getChannelNameLabel(Order\Order $order) : string
	{
		$manager = new Order\SendingChannels\Manager();
		$data = $manager->getChannelList($order);

		return $data['CHANNEL_NAME'] ?? '';
	}
}