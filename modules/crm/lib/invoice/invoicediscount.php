<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class OrderDiscount
 * @package Bitrix\Crm\Invoice
 */
class InvoiceDiscount extends Sale\OrderDiscount
{
	/**
	 * Delete all data by order.
	 *
	 * @param int $order			Order id.
	 * @return void
	 */
	public static function deleteByOrder($order)
	{
		$order = (int)$order;
		if ($order <= 0)
			return;
		Internals\InvoiceRulesTable::clearByOrder($order);
		Internals\InvoiceDiscountDataTable::clearByOrder($order);
		Internals\InvoiceRoundTable::clearByOrder($order);
	}

	/**
	 * Validate coupon.
	 *
	 * @param array $fields		Coupon data.
	 * @return Sale\Result
	 */
	protected static function validateCoupon(array $fields)
	{
		if ($fields['TYPE'] == Sale\Internals\DiscountCouponTable::TYPE_ARCHIVED)
			return new Sale\Result();

		return parent::validateCoupon($fields);
	}

	/* order discounts */

	/**
	 * Order discount getList.
	 *
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getOrderDiscountIterator(array $parameters)
	{
		return Internals\InvoiceDiscountTable::getList($parameters);
	}

	/**
	 * Low-level method add new discount for order.
	 *
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\AddResult|null
	 */
	protected static function addOrderDiscountInternal(array $fields)
	{
		return Internals\InvoiceDiscountTable::add($fields);
	}

	/**
	 * Returns the list of missing discount fields.
	 *
	 * @param array $fields		Discount fields.
	 * @return array
	 */
	protected static function checkRequiredOrderDiscountFields(array $fields)
	{
		return Internals\InvoiceDiscountTable::getEmptyFields($fields);
	}

	/**
	 * Clear raw order discount data.
	 *
	 * @param array $rawFields	Discount information.
	 * @return array|null
	 */
	protected static function normalizeOrderDiscountFieldsInternal(array $rawFields)
	{
		$result = Internals\InvoiceDiscountTable::prepareDiscountData($rawFields);
		return (is_array($result) ? $result : null);
	}

	/**
	 * Calculate order discount hash (prototype).
	 *
	 * @param array $fields		Discount information.
	 * @return string|null
	 */
	protected static function calculateOrderDiscountHashInternal(array $fields)
	{
		$hash = Internals\InvoiceDiscountTable::calculateHash($fields);
		return ($hash === false ? null : $hash);
	}

	/* order discounts end */

	/* order coupons */
	/**
	 * Order coupons getList.
	 *
	 * @param array $parameters \Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOrderCouponIterator(array $parameters)
	{
		return Internals\InvoiceCouponsTable::getList($parameters);
	}

	/**
	 * Low-level method add new coupon for order.
	 *
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\AddResult|null
	 */
	protected static function addOrderCouponInternal(array $fields)
	{
		return Internals\InvoiceCouponsTable::add($fields);
	}

	/* order coupons end */

	/* order discount modules */

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getOrderDiscountModuleIterator(array $parameters)
	{
		return Internals\InvoiceModulesTable::getList($parameters);
	}

	/**
	 * Low-level method save order discount modules.
	 *
	 * @param int $orderDiscountId
	 * @param array $modules
	 * @return bool
	 */
	protected static function saveOrderDiscountModulesInternal($orderDiscountId, array $modules)
	{
		$result = true;

		$resultModule = Internals\InvoiceModulesTable::saveOrderDiscountModules(
			$orderDiscountId,
			$modules
		);
		if (!$resultModule)
		{
			Internals\InvoiceDiscountTable::clearList($orderDiscountId);
			$result = false;
		}
		unset($resultModule);

		return $result;
	}

	/* discount results */

	protected static function getResultEntityInternal($entity)
	{
		$result = null;

		/** @var Discount $discountClassName */
		$discountClassName = static::getDiscountClassName();
		switch ($entity)
		{
			case $discountClassName::ENTITY_BASKET_ITEM:
				$result = Internals\InvoiceRulesTable::ENTITY_TYPE_BASKET_ITEM;
				break;
			case $discountClassName::ENTITY_DELIVERY:
				$result = Internals\InvoiceRulesTable::ENTITY_TYPE_DELIVERY;
				break;
		}

		return $result;
	}

	protected static function getResultEntityFromInternal($entityType)
	{
		$result = null;

		/** @var Discount $discountClassName */
		$discountClassName = static::getDiscountClassName();
		switch ($entityType)
		{
			case Internals\InvoiceRulesTable::ENTITY_TYPE_BASKET_ITEM:
				$result = $discountClassName::ENTITY_BASKET_ITEM;
				break;
			case Internals\InvoiceRulesTable::ENTITY_TYPE_DELIVERY:
				$result = $discountClassName::ENTITY_DELIVERY;
				break;
		}

		return $result;
	}

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getResultIterator(array $parameters)
	{
		if (!isset($parameters['select']))
		{
			$parameters['select'] = ['*', 'RULE_DESCR' => 'DESCR.DESCR', 'RULE_DESCR_ID' => 'DESCR.ID'];
		}

		if (!isset($parameters['order']))
		{
			$parameters['order'] = ['ID' => 'ASC'];
		}

		return Internals\InvoiceRulesTable::getList($parameters);
	}

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getResultDescriptionIterator(array $parameters)
	{
		return Internals\InvoiceRulesDescrTable::getList($parameters);
	}

	/**
	 * Low-level method add new result discount for order.
	 *
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\AddResult|null
	 */
	protected static function addResultInternal(array $fields)
	{
		return Internals\InvoiceRulesTable::add($fields);
	}

	/**
	 * Low-level method add new result description for order.
	 *
	 * @param array $fields
	 * @return Main\Entity\AddResult|null
	 * @throws \Exception
	 */
	protected static function addResultDescriptionInternal(array $fields)
	{
		return Internals\InvoiceRulesDescrTable::add($fields);
	}

	/**
	 * Low-level method update result discount for order.
	 *
	 * @param int $id
	 * @param array $fields
	 * @return Main\Entity\UpdateResult|null
	 * @throws \Exception
	 */
	protected static function updateResultInternal($id, array $fields)
	{
		return Internals\InvoiceRulesTable::update($id, $fields);
	}

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getStoredDataIterator(array $parameters)
	{
		return Internals\InvoiceDiscountDataTable::getList($parameters);
	}

	/**
	 * Low-level method update stored data for order (prototype).
	 *
	 * @param int $id			Tablet row id.
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\UpdateResult|null
	 */
	protected static function updateStoredDataInternal($id, array $fields)
	{
		return Internals\InvoiceDiscountDataTable::update($id, $fields);
	}

	/**
	 * low-level method returns the order stored data table name (prototype).
	 *
	 * @return string|null
	 */
	protected static function getStoredDataTableInternal()
	{
		return Internals\InvoiceDiscountDataTable::getTableName();
	}

	/**
	 * Low-level method update result description for order.
	 *
	 * @param int $id
	 * @param array $fields
	 * @return Main\Entity\UpdateResult|null
	 * @throws \Exception
	 */
	protected static function updateResultDescriptionInternal($id, array $fields)
	{
		return Internals\InvoiceRulesDescrTable::update($id, $fields);
	}

	/**
	 * Low-level method returns result table name (prototype).
	 *
	 * @return string|null
	 */
	protected static function getResultTableNameInternal()
	{
		return Internals\InvoiceRulesTable::getTableName();
	}

	/**
	 * Low-level method returns result description table name (prototype).
	 *
	 * @return string|null
	 */
	protected static function getResultDescriptionTableNameInternal()
	{
		return Internals\InvoiceRulesDescrTable::getTableName();
	}

	/**
	 * Low-level method returns only those fields that are in the result table.
	 *
	 * @param array $fields
	 * @return array|null
	 */
	protected static function checkResultTableWhiteList(array $fields)
	{
		$fields = array_intersect_key($fields, Internals\InvoiceRulesTable::getEntity()->getScalarFields());
		return (!empty($fields) ? $fields : null);
	}

	/**
	 * Low-level method returns only those fields that are in the result description table.
	 *
	 * @param array $fields
	 * @return array|null
	 */
	protected static function checkResultDescriptionTableWhiteList(array $fields)
	{
		$fields = array_intersect_key($fields, Internals\InvoiceRulesDescrTable::getEntity()->getScalarFields());
		return (!empty($fields) ? $fields : null);
	}

	/* round result */

	protected static function getRoundEntityInternal($entity)
	{
		$result = null;

		/** @var Discount $discountClassName */
		$discountClassName = static::getDiscountClassName();
		switch ($entity)
		{
			case $discountClassName::ENTITY_BASKET_ITEM:
				$result = Internals\InvoiceRoundTable::ENTITY_TYPE_BASKET_ITEM;
				break;
		}

		return $result;
	}

	protected static function getRoundEntityFromInternal($entity)
	{
		$result = null;

		/** @var Discount $discountClassName */
		$discountClassName = static::getDiscountClassName();
		switch ($entity)
		{
			case Internals\InvoiceRoundTable::ENTITY_TYPE_BASKET_ITEM:
				$result = $discountClassName::ENTITY_BASKET_ITEM;
				break;
		}

		return $result;
	}

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getRoundResultIterator(array $parameters)
	{
		if (empty($parameters['select']))
		{
			$parameters['select'] = ['*'];
		}

		return Internals\InvoiceRoundTable::getList($parameters);
	}

	/**
	 * Low-level method add new round result for order.
	 *
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\AddResult|null
	 */
	protected static function addRoundResultInternal(array $fields)
	{
		return Internals\InvoiceRoundTable::add($fields);
	}

	/**
	 * Low-level method update round result for order (prototype).
	 *
	 * @param int $id			Tablet row id.
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\UpdateResult|null
	 */
	protected static function updateRoundResultInternal($id, array $fields)
	{
		return Internals\InvoiceRoundTable::update($id, $fields);
	}

	/**
	 * Low-level method returns round result table name.
	 *
	 * @return string|null
	 */
	protected static function getRoundTableNameInternal()
	{
		return Internals\InvoiceRoundTable::getTableName();
	}

	/* data storage */

	/**
	 * Low-level method for convert storage types to internal format.
	 *
	 * @param string $storageType	Abstract storage type.
	 * @return int|null
	 */
	protected static function getStorageTypeInternal($storageType)
	{
		$result = null;

		switch ($storageType)
		{
			case static::STORAGE_TYPE_DISCOUNT_ACTION_DATA:
				$result = Internals\InvoiceDiscountDataTable::ENTITY_TYPE_DISCOUNT_STORED_DATA;
				break;
			case static::STORAGE_TYPE_ORDER_CONFIG:
				$result = Internals\InvoiceDiscountDataTable::ENTITY_TYPE_ORDER;
				break;
			case static::STORAGE_TYPE_ROUND_CONFIG:
				$result = Internals\InvoiceDiscountDataTable::ENTITY_TYPE_ROUND;
				break;
		}

		return $result;
	}

	/**
	 * @param array $parameters		\Bitrix\Main\Entity\DataManager::getList parameters.
	 * @return Main\DB\Result|null
	 */
	protected static function getStorageIterator(array $parameters)
	{
		return Internals\InvoiceDiscountDataTable::getList($parameters);
	}

	/**
	 * Low-level method add stored data for order.
	 *
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\AddResult|null
	 */
	protected static function addStoredDataInternal(array $fields)
	{
		return Internals\InvoiceDiscountDataTable::add($fields);
	}

	/**
	 * Low-level method update stored data for order (prototype).
	 *
	 * @param int $id			Tablet row id.
	 * @param array $fields		Tablet fields.
	 * @return Main\Entity\UpdateResult|null
	 */
	protected static function updateOrderStoredDataInternal($id, array $fields)
	{
		return Internals\InvoiceDiscountDataTable::update($id, $fields);
	}

	/* data storage end */
}