<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Crm\Search;
use Bitrix\Crm\Automation;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

Loc::loadMessages(__FILE__);

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
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function save()
	{
		$result = $this->checkAccountNumber($this->getField('ACCOUNT_NUMBER'));
		if (!$result->isSuccess())
		{
			return $result;
		}

		return parent::save();
	}

	private function checkAccountNumber($accountNumber) : Sale\Result
	{
		$result = new Sale\Result();

		if (
			(
				is_string($accountNumber)
				&& $accountNumber !== ''
			)
			|| is_numeric($accountNumber))
		{
			$res = static::getList(
				[
					'order' => ['ID' => 'ASC'],
					'filter' => [
						'=ACCOUNT_NUMBER' => $accountNumber,
						'!ID' => $this->getField('ID')
					],
					'select' => ['ID'],
					'limit' => 1
				]
			);

			if ($res->fetch())
			{
				$result->addError(new Main\Error(
					Loc::getMessage('CRM_INVOICE_ERR_EXISTING_ACCOUNT_NUMBER'),
					'CRM_INVOICE_ERR_EXISTING_ACCOUNT_NUMBER'
				));
			}
		}

		return $result;
	}

	protected function onAfterSave()
	{
		$result = parent::onAfterSave();

		if (
			$this->fields->isChanged('STATUS_ID')
			&& Automation\Factory::canUseAutomation()
		)
		{
			Automation\Trigger\InvoiceTrigger::onInvoiceStatusChanged(
				$this->getId(),
				$this->getField('STATUS_ID')
			);
		}

		return $result;
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
	 * Set account number.
	 *
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 */
	protected function setAccountNumber()
	{
		$accountNumber = $this->getField('ACCOUNT_NUMBER');
		if (!((is_string($accountNumber) && $accountNumber <> '') || is_numeric($accountNumber)))
		{
			parent::setAccountNumber();
		}
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
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY => '\Bitrix\Crm\Invoice\ShipmentProperty',
				Sale\Registry::ENTITY_PROPERTY_VALUE => '\Bitrix\Crm\Invoice\PropertyValue',
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY_VALUE => '\Bitrix\Crm\Invoice\ShipmentPropertyValue',
				Sale\Registry::ENTITY_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Invoice\PropertyValueCollection',
				Sale\Registry::ENTITY_SHIPMENT_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Invoice\ShipmentPropertyValueCollection',
				Sale\Registry::ENTITY_BASKET => '\Bitrix\Crm\Invoice\Basket',
				Sale\Registry::ENTITY_BASKET_ITEM => '\Bitrix\Crm\Invoice\BasketItem',
				Sale\Registry::ENTITY_BASKET_PROPERTIES_COLLECTION => '\Bitrix\Crm\Invoice\BasketPropertiesCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTY_ITEM => '\Bitrix\Crm\Invoice\BasketPropertyItem',
				Sale\Registry::ENTITY_BASKET_RESERVE_COLLECTION => '\Bitrix\Crm\Invoice\ReserveQuantityCollection',
				Sale\Registry::ENTITY_BASKET_RESERVE_COLLECTION_ITEM => '\Bitrix\Crm\Invoice\ReserveQuantity',
				Sale\Registry::ENTITY_PAYMENT => '\Bitrix\Crm\Invoice\Payment',
				Sale\Registry::ENTITY_PAYMENT_COLLECTION => '\Bitrix\Crm\Invoice\PaymentCollection',
				Sale\Registry::ENTITY_PAYABLE_ITEM_COLLECTION => '\Bitrix\Crm\Invoice\PayableItemCollection',
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
			if (is_array($mapping) && isset($mapping['PROVIDER_KEY']))
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
