<?php

namespace Bitrix\Crm\Service\Sale\EntityLinkBuilder;

use Bitrix\Crm\Order\EntityBinding;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;

/**
 * Class EntityLinkBuilder
 *
 * @package Bitrix\Crm\Service\Sale\EntityLinkBuilder
 * @internal
 */
final class EntityLinkBuilder
{
	/**
	 * Service instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return ServiceLocator::getInstance()->get('crm.sale.entity.linkBuilder');
	}

	/**
	 * @param int $orderId
	 * @return string|null
	 */
	public function getEntityDetailUrlByOrderId(int $orderId): ?string
	{
		$entity = $this->getEntityByOrderId($orderId);
		if (!$entity)
		{
			return null;
		}

		return Container::getInstance()->getRouter()->getItemDetailUrl(
			$entity->getOwnerTypeId(),
			$entity->getOwnerId()
		);
	}

	/**
	 * @param int $orderId
	 * @return int|null
	 */
	public function getEntityOwnerIdByOrderId(int $orderId): ?int
	{
		$entity = $this->getEntityByOrderId($orderId);
		if (!$entity)
		{
			return null;
		}

		return $entity->getOwnerId();
	}

	/**
	 * @param int $orderId
	 * @param Context|null $context
	 * @return string
	 */
	public function getOrderDetailsLink(int $orderId, ?Context $context = null): string
	{
		return $this->isShopLink($context)
			? \CComponentEngine::MakePathFromTemplate(
				Option::get('crm', 'path_to_order_details'),
				[
					'order_id' => $orderId
				]
			)
			: '';
	}

	/**
	 * @param int $paymentId
	 * @param Context|null $context
	 * @return string
	 */
	public function getPaymentDetailsLink(int $paymentId, ?Context $context = null): string
	{
		return $this->isShopLink($context)
			? \CComponentEngine::MakePathFromTemplate(
				Option::get('crm', 'path_to_order_payment_details'),
				[
					'payment_id' => $paymentId
				]
			)
			: '';
	}

	/**
	 * @param int $shipmentId
	 * @param Context|null $context
	 * @return string
	 */
	public function getShipmentDetailsLink(int $shipmentId, ?Context $context = null): string
	{
		return $this->isShopLink($context)
			? \CComponentEngine::MakePathFromTemplate(
				Option::get('crm', 'path_to_order_shipment_details'),
				[
					'shipment_id' => $shipmentId
				]
			)
			: '';
	}

	/**
	 * @param Context $context
	 * @return EntityLinkBuilder
	 */
	public function setContext(Context $context): EntityLinkBuilder
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @param Context|null $context
	 * @return bool
	 */
	private function isShopLink(?Context $context = null): bool
	{
		return (
			$this->isWithOrdersMode()
			|| (
				$context
				&& (
					$context->isShopLinkForced()
					|| $context->isShopArea()
				)
			)
		);
	}

	/**
	 * @return bool
	 */
	private function isWithOrdersMode(): bool
	{
		return \CCrmSaleHelper::isWithOrdersMode();
	}

	/**
	 * @param int $orderId
	 * @return EntityBinding|null
	 */
	private function getEntityByOrderId(int $orderId): ?EntityBinding
	{
		$order = Order::load($orderId);
		if (!$order)
		{
			return null;
		}

		$binding = $order ? $order->getEntityBinding() : null;
		if (!$binding)
		{
			return null;
		}

		return $binding;
	}
}
