<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class DeliveryStatus
 * @package Bitrix\Crm\Invoice
 */
class DeliveryStatus extends Sale\DeliveryStatus
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
		return array();
	}

	/**
	 * Get all statuses names for current class type.
	 *
	 * @param null $lang
	 * @return array|mixed
	 */
	public static function getAllStatusesNames($lang = null)
	{
		return array();
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 */
	public static function getList(array $parameters = array())
	{
		return new Main\DB\ArrayResult(array());
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
		return array();
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
		return array();
	}

	/**
	 * @param $groupId
	 * @param array $operations
	 * @return array|mixed
	 */
	public static function getStatusesGroupCanDoOperations($groupId, array $operations)
	{
		return array();
	}
}