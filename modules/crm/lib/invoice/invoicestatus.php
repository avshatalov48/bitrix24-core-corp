<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Crm\StatusTable;
use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class InvoiceStatus
 * @package Bitrix\Crm\Invoice
 */
class InvoiceStatus extends Sale\OrderStatus
{
	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * Get all statuses for current class type.
	 *
	 * @return array|mixed
	 */
	public static function getAllStatuses()
	{
		$statusList = \CCrmStatus::GetStatusList('INVOICE_STATUS');
		return array_keys($statusList);
	}

	/**
	 * Get all statuses names for current class type.
	 *
	 * @param null $lang
	 * @return array|mixed
	 */
	public static function getAllStatusesNames($lang = null)
	{
		return \CCrmStatus::GetStatusList('INVOICE_STATUS');
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		$parameters['filter']['=ENTITY_ID'] = 'INVOICE_STATUS';

		return StatusTable::getList($parameters);
	}

	/**
	 * @param $statusId
	 * @return bool
	 */
	public static function isAllowPay($statusId)
	{
		return true;
	}

	/**
	 * @param $userId
	 * @return array
	 */
	protected static function getUserGroups($userId)
	{
		return array();
	}

	/**
	 * @param $groupId
	 * @param $fromStatus
	 * @param array $operations
	 * @return bool
	 */
	public static function canGroupDoOperations($groupId, $fromStatus, array $operations)
	{
		return true;
	}

	/**
	 * @param $groupId
	 * @param $fromStatus
	 * @return array|mixed
	 */
	protected static function getAllowedGroupStatuses($groupId, $fromStatus)
	{
		return static::getAllStatusesNames();
	}

	/**
	 * Get statuses user can do operations within
	 *
	 * @param $userId
	 * @param array $operations
	 * @return array|mixed
	 */
	public static function getStatusesUserCanDoOperations($userId, array $operations)
	{
		return static::getAllStatusesNames();
	}

	/**
	 * @param $groupId
	 * @param array $operations
	 * @return array|mixed
	 */
	public static function getStatusesGroupCanDoOperations($groupId, array $operations)
	{
		return static::getAllStatuses();
	}

	/**
	 * @return mixed
	 */
	public static function getInitialStatus()
	{
		return \CCrmStatus::GetFirstStatusID('INVOICE_STATUS');
	}

	/**
	 * @return string
	 */
	public static function getFinalStatus()
	{
		return 'D';
	}

}