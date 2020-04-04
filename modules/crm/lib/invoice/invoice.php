<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Crm\Search;
use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Invoice
 * @package Bitrix\Crm\Invoice
 */
class Invoice extends Sale\Order
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\InvoiceTable::getMap();
	}

	/**
	 * @param array $data
	 * @return mixed
	 */
	protected function addInternal(array $data)
	{
		return Internals\InvoiceTable::add($data);
	}

	/**
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	protected function add()
	{
		$result = parent::add();

		//region Search content index
		if ($result->isSuccess())
		{
			$id = $result->getId();
			if ($id > 0)
			{
				Search\SearchContentBuilderFactory::create(\CCrmOwnerType::Invoice)->build($id);
			}
		}
		//endregion Search content index

		return $result;
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return mixed
	 */
	protected static function updateInternal($primary, array $data)
	{
		return Internals\InvoiceTable::update($primary, $data);
	}

	/**
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	protected function update()
	{
		$result = parent::update();

		//region Search content index
		if ($result->isSuccess())
		{
			$id = $result->getId();
			if ($id > 0)
			{
				Search\SearchContentBuilderFactory::create(\CCrmOwnerType::Invoice)->build($id);
			}
		}
		//endregion Search content index

		return $result;
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 */
	protected static function deleteInternal($primary)
	{
		return Internals\InvoiceTable::delete($primary);
	}

	/**
	 * @param array $parameters
	 *
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList(array $parameters = array())
	{
		return Internals\InvoiceTable::getList($parameters);
	}

	/**
	 * @param $orderId
	 */
	protected static function deleteExternalEntities($orderId)
	{
		return;
	}

	/**
	 * @return mixed
	 * @throws Main\ArgumentException
	 */
	protected function getStatusOnPaid()
	{
		$semantic = \CCrmStatus::GetInvoiceStatusSemanticInfo();

		return $semantic['FINAL_SUCCESS_FIELD'];
	}

	/**
	 * @return Main\EventResult
	 */
	public static function OnInitRegistryList()
	{
		$registry = array(
			REGISTRY_TYPE_CRM_INVOICE => array(
				Sale\Registry::ENTITY_ORDER => '\Bitrix\Crm\Invoice\Invoice',
				Sale\Registry::ENTITY_TAX => '\Bitrix\Crm\Invoice\Tax',
				Sale\Registry::ENTITY_DISCOUNT => '\Bitrix\Crm\Invoice\Discount',
				Sale\Registry::ENTITY_PROPERTY => 'Bitrix\Crm\Invoice\Property',
				Sale\Registry::ENTITY_PROPERTY_VALUE => '\Bitrix\Crm\Invoice\PropertyValue',
				Sale\Registry::ENTITY_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Invoice\PropertyValueCollection',
				Sale\Registry::ENTITY_BASKET => '\Bitrix\Crm\Invoice\Basket',
				Sale\Registry::ENTITY_BASKET_ITEM => '\Bitrix\Crm\Invoice\BasketItem',
				Sale\Registry::ENTITY_BASKET_PROPERTIES_COLLECTION => '\Bitrix\Crm\Invoice\BasketPropertiesCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTY_ITEM => '\Bitrix\Crm\Invoice\BasketPropertyItem',
				Sale\Registry::ENTITY_PAYMENT => '\Bitrix\Crm\Invoice\Payment',
				Sale\Registry::ENTITY_PAYMENT_COLLECTION => '\Bitrix\Crm\Invoice\PaymentCollection',
				Sale\Registry::ENTITY_SHIPMENT => '\Bitrix\Crm\Invoice\Shipment',
				Sale\Registry::ENTITY_SHIPMENT_COLLECTION => '\Bitrix\Crm\Invoice\ShipmentCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM => '\Bitrix\Crm\Invoice\ShipmentItem',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_COLLECTION => '\Bitrix\Crm\Invoice\ShipmentItemCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE => '\Bitrix\Crm\Invoice\ShipmentItemStore',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE_COLLECTION => '\Bitrix\Crm\Invoice\ShipmentItemStoreCollection',
				Sale\Registry::ENTITY_OPTIONS => 'Bitrix\Main\Config\Option',
				Sale\Registry::ENTITY_ORDER_STATUS => 'Bitrix\Crm\Invoice\InvoiceStatus',
				Sale\Registry::ENTITY_DELIVERY_STATUS => 'Bitrix\Crm\Invoice\DeliveryStatus',
				Sale\Registry::ENTITY_ENTITY_MARKER => 'Bitrix\Crm\Invoice\EntityMarker',
				Sale\Registry::ENTITY_PERSON_TYPE => 'Bitrix\Crm\Invoice\PersonType',
				Sale\Registry::ENTITY_ORDER_HISTORY => 'Bitrix\Crm\Invoice\InvoiceHistory',
				Sale\Registry::ENTITY_ORDER_DISCOUNT => '\Bitrix\Crm\Invoice\InvoiceDiscount',
				Sale\Registry::ENTITY_DISCOUNT_COUPON => '\Bitrix\Crm\Invoice\DiscountCouponsManager',
				Sale\Registry::ENTITY_NOTIFY => 'Bitrix\Crm\Invoice\Notify',
				Sale\Registry::ENTITY_TRADE_BINDING_COLLECTION => 'Bitrix\Crm\Invoice\TradeBindingCollection',
				Sale\Registry::ENTITY_TRADE_BINDING_ENTITY => 'Bitrix\Crm\Invoice\TradeBindingEntity',
			)
		);

		return new Main\EventResult(Main\EventResult::SUCCESS, $registry);
	}

	/**
	 * @param $mapping
	 * @return Sale\Order|null|string
	 */
	public function getBusinessValueProviderInstance($mapping)
	{
		$providerInstance = parent::getBusinessValueProviderInstance($mapping);

		if ($providerInstance === null)
		{
			if (is_array($mapping))
			{
				switch ($mapping['PROVIDER_KEY'])
				{
					case 'REQUISITE':
					case 'MC_REQUISITE':
					case 'CRM_COMPANY':
					case 'CRM_MYCOMPANY':
					case 'CRM_CONTACT':
					case 'MC_BANK_DETAIL':
					case 'BANK_DETAIL':
						$providerInstance = $this;
						break;
				}
			}
		}

		return $providerInstance;
	}

}