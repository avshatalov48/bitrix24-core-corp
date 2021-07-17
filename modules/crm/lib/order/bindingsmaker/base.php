<?php

namespace Bitrix\Crm\Order\BindingsMaker;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Order\Shipment;

/**
 * Class Base
 * @package Bitrix\Crm\Order\BindingsMaker
 */
abstract class Base
{
	/**
	 * @param Order $order
	 * @param array $options
	 * @return array[]
	 */
	public static function makeByOrder(Order $order, array $options = []): array
	{
		$extraBindings = static::getExtraBindings($options);
		$withDeal = static::getWithDeal($options);

		$result = [
			static::makeBinding(\CCrmOwnerType::Order, $order->getId())
		];

		if ($withDeal === true && $order->getDealBinding())
		{
			$result[] = static::makeBinding(\CCrmOwnerType::Deal, $order->getDealBinding()->getDealId());
		}

		static::addExtraBindings($result, $extraBindings);

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param array $options
	 * @return array[]
	 */
	public static function makeByPayment(Payment $payment, array $options = [])
	{
		/** @var Order $order */
		$order = $payment->getOrder();

		$result = static::makeByOrder($order, $options);

		$result[] = static::makeBinding(\CCrmOwnerType::OrderPayment, $payment->getId());

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @param array $options
	 * @return array[]
	 */
	public static function makeByShipment(Shipment $shipment, array $options = [])
	{
		/** @var Order $order */
		$order = $shipment->getOrder();

		$result = static::makeByOrder($order, $options);

		$result[] = static::makeBinding(\CCrmOwnerType::OrderShipment, $shipment->getId());

		return $result;
	}

	/**
	 * @return string
	 */
	abstract protected static function getPrefix(): string;

	/**
	 * @param array $options
	 * @return array
	 */
	private static function getExtraBindings(array $options): array
	{
		return isset($options['extraBindings']) ? $options['extraBindings'] : [];
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	private static function getWithDeal(array $options): bool
	{
		return isset($options['withDeal']) ? (bool)$options['withDeal'] : true;
	}

	/**
	 * @param array $result
	 * @param array $extraBindings
	 */
	private static function addExtraBindings(array &$result, array $extraBindings): void
	{
		foreach ($extraBindings as $extraBinding)
		{
			$result[] = static::makeBinding($extraBinding['TYPE_ID'], $extraBinding['ID']);
		}
	}

	/**
	 * @param int $typeId
	 * @param int $id
	 * @return array|int[]
	 */
	private static function makeBinding(int $typeId, int $id): array
	{
		return [
			static::getPrefix() . '_TYPE_ID' => $typeId,
			static::getPrefix() . '_ID' => $id
		];
	}
}
