<?php

namespace Bitrix\Crm\Order\Permissions;
//@codingStandardsIgnoreFile
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

/**
 * Class Order
 * @package Bitrix\Crm\Order\Permissions
 */
class Order
{
	protected static $TYPE_NAME = 'ORDER';

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canAddItems(\CCrmOwnerType::Order)
	 * @return bool
	 */
	public static function checkCreatePermission()
	{
		return Container::getInstance()
			->getUserPermissions()
			->entityType()
			->canAddItems(\CCrmOwnerType::Order)
		;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canUpdate
	 * @param $id
	 *
	 * @return bool
	 */
	public static function checkUpdatePermission($id): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		return $userPermissions->item()->canUpdate(\CCrmOwnerType::Order, (int)$id);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canRead
	 * @param int $id
	 */
	public static function checkReadPermission($id = 0): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		return ($id > 0)
			? $userPermissions->item()->canRead(\CCrmOwnerType::Order, (int)$id)
			: $userPermissions->entityType()->canReadItems(\CCrmOwnerType::Order)
		;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canDelete
	 * @param $id
	 *
	 * @return bool
	 */
	public static function checkDeletePermission($id)
	{
		return $id && Container::getInstance()
				->getUserPermissions()
				->item()
				->canDelete(\CCrmOwnerType::Order, (int)$id)
			;
	}

	/**
	 * @param $id
	 * @param array $params
	 */
	public static function prepareConversionPermissionFlags($id, array &$params)
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		$canCreateDeal = $userPermissions->entityType()->canAddItems(\CCrmOwnerType::Deal);
		$canCreateInvoice = IsModuleInstalled('sale') && $userPermissions->entityType()->canAddItems(\CCrmOwnerType::Invoice);

		$params['CAN_CONVERT_TO_DEAL'] = $canCreateDeal;
		$params['CAN_CONVERT_TO_INVOICE'] = $canCreateInvoice;
		$params['CAN_CONVERT'] = $params['CONVERT'] = ($canCreateInvoice || $canCreateDeal);

		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getConversionRestriction();
		if($restriction->hasPermission())
		{
			$params['CONVERSION_PERMITTED'] = true;
		}
		else
		{
			$params['CONVERSION_PERMITTED'] = false;
			$params['CONVERSION_LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
		}
	}
}
