<?
namespace Bitrix\Crm\Order\Permissions;

/**
 * Class Order
 * @package Bitrix\Crm\Order\Permissions
 */
class Order
{
	protected static $TYPE_NAME = 'ORDER';

	/**
	 * @param int $id
	 * @param int $entityTypeId
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkConvertPermission($id = 0, $entityTypeId = 0, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		if($entityTypeId === \CCrmOwnerType::Deal)
		{
			return \CCrmDeal::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeId === \CCrmOwnerType::Invoice)
		{
			return \CCrmInvoice::CheckCreatePermission($userPermissions);
		}

		return (\CCrmDeal::CheckCreatePermission($userPermissions)
			|| \CCrmInvoice::CheckCreatePermission($userPermissions));
	}

	/**
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkImportPermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckImportPermission(self::$TYPE_NAME, $userPermissions);
	}

	/**
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkCreatePermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	/**
	 * @param $id
	 * @param null $userPermissions
	 * @param array|null $options
	 *
	 * @return bool
	 */
	public static function checkUpdatePermission($id, $userPermissions = null, array $options = null)
	{
		$entityAttrs = $id > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		return \CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $id, $userPermissions, $entityAttrs);
	}

	/**
	 * @param $statusID
	 * @param $permissionTypeID
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkStatusPermission($statusID, $permissionTypeID, \CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		$permissionName = \Bitrix\Crm\Security\EntityPermissionType::resolveName($permissionTypeID);
		$entityAttrs = array("STATUS_ID{$statusID}");

		return $permissionName !== '' &&
			($userPermissions->GetPermType(self::$TYPE_NAME, $permissionName, $entityAttrs) > BX_CRM_PERM_NONE);
	}

	/**
	 * @param int $id
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkReadPermission($id = 0, $userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $id, $userPermissions);
	}

	/**
	 * @param $id
	 * @param null $userPermissions
	 * @param array|null $options
	 *
	 * @return bool
	 */
	public static function checkDeletePermission($id, $userPermissions = null, array $options = null)
	{
		$entityAttrs = $id > 0 && is_array($options) && isset($options['ENTITY_ATTRS']) ? $options['ENTITY_ATTRS'] : null;
		return \CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $id, $userPermissions, $entityAttrs);
	}

	/**
	 * @param $id
	 * @param array $params
	 * @param null $userPermissions
	 */
	public static function prepareConversionPermissionFlags($id, array &$params, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		$canCreateDeal = \CCrmDeal::CheckCreatePermission($userPermissions);
		$canCreateInvoice = IsModuleInstalled('sale') && \CCrmInvoice::CheckCreatePermission($userPermissions);

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

	/**
	 * @param null $userPermissions
	 * @return bool
	 */
	public static function checkExportPermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckExportPermission(self::$TYPE_NAME, $userPermissions);
	}

	/**
	 * @param $id
	 * @param $responsibleId
	 */
	public static function updatePermission($id, $responsibleId)
	{
		$responsibleId = (int)$responsibleId;
		if($responsibleId <= 0)
		{
			return;
		}

		$entityAttrs = self::buildEntityAttr($responsibleId);
		\CCrmPerms::UpdateEntityAttr(\CCrmOwnerType::OrderName, $id, $entityAttrs);
	}

	/**
	 * @param $userId
	 * @param array $attributes
	 *
	 * @return array
	 */
	public static function buildEntityAttr($userId, $attributes = array())
	{
		$userId = (int)$userId;
		$result = array("U{$userId}");
		if(isset($attributes['OPENED']) && $attributes['OPENED'] == 'Y')
		{
			$result[] = 'O';
		}

		$userAttributes = \CCrmPerms::BuildUserEntityAttr($userId);
		return array_merge($result, $userAttributes['INTRANET']);
	}

	/**
	 * @param array $ids
	 *
	 * @return array
	 */
	public static function getPermissionAttributes(array $ids)
	{
		return \CCrmPerms::GetEntityAttr(self::$TYPE_NAME, $ids);
	}

	public static function copyPermsFromInvoices()
	{
		//Copy perms from invoices to orders
		$CCrmRole = new \CCrmRole();
		$dbRoles = $CCrmRole->GetList();

		while($arRole = $dbRoles->Fetch())
		{
			$arPerms = $CCrmRole->GetRolePerms($arRole['ID']);

			if(!isset($arPerms['ORDER']) && is_array($arPerms['INVOICE']))
			{
				foreach ($arPerms['INVOICE'] as $key => $value)
				{
					if(isset($value['-']) && $value['-'] != 'O')
						$arPerms['ORDER'][$key]['-'] = $value['-'];
					else
						$arPerms['ORDER'][$key]['-'] = 'X';
				}
			}

			$arFields = array('RELATION' => $arPerms);
			$CCrmRole->Update($arRole['ID'], $arFields);
		}

		return '';
	}
}
