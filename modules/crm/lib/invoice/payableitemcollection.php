<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\PayableBasketItem;
use Bitrix\Sale\PayableShipmentItem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Shipment;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class PaymentCollection
 * @package Bitrix\Crm\Invoice
 */
class PayableItemCollection extends Sale\PayableItemCollection
{
	/**
	 * @param Payment $payment
	 * @return Sale\PayableItemCollection
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function load(Payment $payment)
	{
		$collection = static::createCollectionObject();
		$collection->setPayment($payment);

		return $collection;
	}

	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected static function deleteInternal($primary)
	{
		return new Main\Entity\DeleteResult();
	}

	/**
	 * @param BasketItem $basketItem
	 * @return PayableBasketItem
	 * @throws Main\SystemException
	 */
	public function createItemByBasketItem(BasketItem $basketItem = null): PayableBasketItem
	{
		throw new Main\SystemException(
			Main\Localization\Loc::getMessage(
				'CRM_INVOICE_PAYABLE_ITEM_NOT_SUPPORTED',
				[
					'#METHOD_NAME#' => __METHOD__
				]
			)
		);
	}

	/**
	 * @param Shipment $shipment
	 * @return PayableShipmentItem
	 * @throws Main\SystemException
	 */
	public function createItemByShipment(Shipment $shipment): PayableShipmentItem
	{
		throw new Main\SystemException(
			Main\Localization\Loc::getMessage(
				'CRM_INVOICE_PAYABLE_ITEM_NOT_SUPPORTED',
				[
					'#METHOD_NAME#' => __METHOD__
				]
			)
		);
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return new Main\DB\ArrayResult([]);
	}
}