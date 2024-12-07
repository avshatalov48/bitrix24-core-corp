<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Security\QueryBuilder\OptionsBuilder;
use Bitrix\Crm\Service\Container;

class CCrmPerms
{
	const PERM_NONE = BX_CRM_PERM_NONE;
	const PERM_SELF = BX_CRM_PERM_SELF;
	const PERM_DEPARTMENT = BX_CRM_PERM_DEPARTMENT;
	const PERM_SUBDEPARTMENT = BX_CRM_PERM_SUBDEPARTMENT;
	const PERM_OPEN = BX_CRM_PERM_OPEN;
	const PERM_ALL = BX_CRM_PERM_ALL;
	const PERM_CONFIG = BX_CRM_PERM_CONFIG;

	const ATTR_READ_ALL = 'RA';

	private static $INSTANCES = array();
	protected $userId = 0;
	protected $arUserPerms = array();

	function __construct($userId)
	{
		$this->userId = intval($userId);
		$this->arUserPerms = CCrmRole::GetUserPerms($this->userId);
	}

	/**
	 * Get current user permissions
	 * @return \CCrmPerms
	 */
	public static function GetCurrentUserPermissions()
	{
		$userID = CCrmSecurityHelper::GetCurrentUserID();
		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new CCrmPerms($userID);
		}
		return self::$INSTANCES[$userID];
	}

	/**
	 * Get specified user permissions
	 * @param int $userID User ID.
	 * @return \CCrmPerms
	 */
	public static function GetUserPermissions($userID)
	{
		if(!is_int($userID))
		{
			$userID = intval($userID);
		}

		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new CCrmPerms($userID);
		}
		return self::$INSTANCES[$userID];
	}

	public static function GetCurrentUserID()
	{
		return CCrmSecurityHelper::GetCurrentUserID();
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isAdmin() instead
	 * @see \Bitrix\Crm\Service\UserPermissions::isAdmin
	 */
	public static function IsAdmin($userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = is_numeric($userID) ? (int)$userID : 0;
		}
		if($userID <= 0)
		{
			$userID = null;
		}

		return \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions($userID)->isAdmin();
	}

	public static function IsAuthorized()
	{
		return CCrmSecurityHelper::GetCurrentUser()->IsAuthorized();
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\UserPermissions::getAttributesProvider()->getUserAttributes() instead
	 * @see \Bitrix\Crm\Security\AttributesProvider::getUserAttributes
	 */
	static public function GetUserAttr($iUserID)
	{
		return Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions((int)$iUserID)
			->getAttributesProvider()
			->getUserAttributes()
		;
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\UserPermissions::getAttributesProvider()->getEntityAttributes() instead
	 * @see \Bitrix\Crm\Security\AttributesProvider::getEntityAttributes
	 */
	static public function BuildUserEntityAttr($userID)
	{
		return Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions((int)$userID)
			->getAttributesProvider()
			->getEntityAttributes()
		;
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\UserPermissions::getAttributesProvider()->getUserAttributes() instead
	 * @see \Bitrix\Crm\Security\AttributesProvider::getUserAttributes
	 */
	static public function GetCurrentUserAttr()
	{
		return Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions(CCrmSecurityHelper::GetCurrentUserID())
			->getAttributesProvider()
			->getUserAttributes()
		;
	}

	public function GetUserID()
	{
		return $this->userId;
	}

	public function GetUserPerms()
	{
		return $this->arUserPerms;
	}

	public function HavePerm($permEntity, $permAttr, $permType = 'READ'): bool
	{
		// HACK: only for product and currency support
		$permType = mb_strtoupper($permType);
		if ($permEntity == 'CONFIG' && $permAttr == self::PERM_CONFIG && $permType == 'READ')
		{
			return true;
		}

		// HACK: Compatibility with CONFIG rights
		if ($permEntity == 'CONFIG')
			$permType = 'WRITE';

		if(self::IsAdmin($this->userId))
		{
			return $permAttr != self::PERM_NONE;
		}

		// Change config right also grant right to change robots.
		if (
			$permType === 'AUTOMATION'
			&& $permAttr == BX_CRM_PERM_ALL
			&& self::HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
		)
		{
			return true;
		}

		if (!isset($this->arUserPerms[$permEntity][$permType]))
		{
			return $permAttr == self::PERM_NONE;
		}
		$entityTypePerm = $this->arUserPerms[$permEntity][$permType]['-'] ?? self::PERM_NONE;

		if ($entityTypePerm == self::PERM_NONE)
		{
			foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
			{
				if ($sField == '-')
				{
					continue ;
				}

				foreach ($arFieldValue as $sAttr)
				{
					if ($sAttr > $permAttr)
					{
						return $sAttr == self::PERM_NONE;
					}
				}

				return $permAttr == self::PERM_NONE;
			}
		}

		if ($permAttr == self::PERM_NONE)
		{
			return $entityTypePerm == self::PERM_NONE;
		}

		if ($entityTypePerm >= $permAttr)
		{
			return true;
		}

		return false;
	}

	public function GetPermType($permEntity, $permType = 'READ', $arEntityAttr = array())
	{
		if (self::IsAdmin($this->userId))
			return self::PERM_ALL;

		// Change config right also grant right to change robots.
		if (
			$permType === 'AUTOMATION'
			&& self::HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
		)
		{
			return BX_CRM_PERM_ALL;
		}

		if (!isset($this->arUserPerms[$permEntity][$permType]))
		{
			return self::PERM_NONE;
		}

		if($permType === 'READ'
			&& (in_array(self::ATTR_READ_ALL, $arEntityAttr, true)
				|| in_array('CU'.$this->userId, $arEntityAttr, true)
			)
		)
		{
			return self::PERM_ALL;
		}

		if (empty($arEntityAttr))
		{
			return $this->arUserPerms[$permEntity][$permType]['-'] ?? self::PERM_NONE;
		}

		foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
		{
			if ($sField == '-')
			{
				continue ;
			}

			foreach ($arFieldValue as $fieldValue => $sAttr)
			{
				if (in_array($sField.$fieldValue, $arEntityAttr))
				{
					return $sAttr;
				}
			}
		}

		return $this->arUserPerms[$permEntity][$permType]['-'] ?? self::PERM_NONE;
	}

	public static function GetEntityGroup($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = CCrmRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_crm_role_relation WHERE RELATION LIKE \'G%\' AND ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql);
			while($row = $res->Fetch())
				$arResult[] = mb_substr($row['RELATION'], 1);
		}
		return $arResult;
	}

	public static function GetEntityRelations($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = CCrmRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_crm_role_relation WHERE ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql);
			while($row = $res->Fetch())
				$arResult[] = $row['RELATION'];
		}
		return $arResult;
	}

	static public function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = self::GetCurrentUserPermissions();
		}

		$result = (
			CCrmLead::IsAccessEnabled($userPermissions)
			|| CCrmContact::IsAccessEnabled($userPermissions)
			|| CCrmCompany::IsAccessEnabled($userPermissions)
			|| CCrmDeal::IsAccessEnabled($userPermissions)
			|| CCrmQuote::IsAccessEnabled($userPermissions)
			|| CCrmInvoice::IsAccessEnabled($userPermissions)
		);

		if (!$result)
		{
			$permissions = Container::getInstance()->getUserPermissions($userPermissions->GetUserID());

			if ($permissions->canReadType(\CCrmOwnerType::SmartInvoice))
			{
				return true;
			}

			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
			// avoiding exceptions as this method has usages across the product.
			try
			{
				$dynamicTypesMap->load([
					'isLoadStages' => false,
					'isLoadCategories' => false,
				]);
			}
			catch (Exception $exception)
			{
			}
			catch (Error $error)
			{
			}
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				if ($permissions->canReadType($type->getEntityTypeId()))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	public function CheckEnityAccess($permEntity, $permType, $arEntityAttr)
	{
		if (!is_array($arEntityAttr))
			$arEntityAttr = array();

		$enableCummulativeMode = COption::GetOptionString('crm', 'enable_permission_cumulative_mode', 'Y') === 'Y';

		$permAttr = $this->GetPermType($permEntity, $permType, $arEntityAttr);
		if ($permAttr == self::PERM_NONE)
		{
			return false;
		}
		if ($permAttr == self::PERM_ALL)
		{
			return true;
		}
		if ($permAttr == self::PERM_OPEN)
		{
			if((in_array('O', $arEntityAttr) || in_array('U'.$this->userId, $arEntityAttr)))
			{
				return true;
			}

			//For backward compatibility (is not comulative mode)
			if(!$enableCummulativeMode)
			{
				return false;
			}
		}
		if ($permAttr >= self::PERM_SELF && in_array('U'.$this->userId, $arEntityAttr))
		{
			return true;
		}

		$arAttr = self::GetUserAttr($this->userId);

		if ($permAttr >= self::PERM_DEPARTMENT && is_array($arAttr['INTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his department
			foreach ($arAttr['INTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		if ($permAttr >= self::PERM_SUBDEPARTMENT && is_array($arAttr['SUBINTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his intranet
			foreach ($arAttr['SUBINTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\UserPermissions::getAttributesProvider()->getEntityListAttributes($permEntity, $permType) instead
	 * @see \Bitrix\Crm\Security\AttributesProvider::getEntityListAttributes
	 */
	public function GetUserAttrForSelectEntity($permEntity, $permType, $bForcePermAll = false)
	{
		return Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($this->userId)
			->getAttributesProvider()
			->getEntityListAttributes((string)$permEntity, (string)$permType)
		;
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->createListQueryBuilder()->build() instead
	 * @see \Bitrix\Crm\Security\QueryBuilder::build
	 */
	static public function BuildSqlForEntitySet(array $entityTypes, $aliasPrefix, $permType, $options = [])
	{
		$userId = null;
		if (isset($options['PERMS']) && is_object($options['PERMS']))
		{
			/** @var \CCrmPerms $options ['PERMS'] */
			$userId = $options['PERMS']->GetUserID();
		}
		$builderOptions = OptionsBuilder::makeFromArray((array)$options)
			->setOperations((array)$permType)
			->setAliasPrefix((string)$aliasPrefix)
			->build()
		;

		$queryBuilder = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder($entityTypes, $builderOptions)
		;

		return $queryBuilder->buildCompatible();
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->createListQueryBuilder()->build()
	 * @see \Bitrix\Crm\Security\QueryBuilder::build
	 */
	static public function BuildSql($permEntity, $sAliasPrefix, $mPermType, $arOptions = array())
	{
		$userId = null;
		if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
		{
			/** @var \CCrmPerms $arOptions ['PERMS'] */
			$userId = $arOptions['PERMS']->GetUserID();
		}
		$builderOptions = OptionsBuilder::makeFromArray((array)$arOptions)
			->setOperations((array)$mPermType)
			->setAliasPrefix((string)$sAliasPrefix)
			->build()
		;

		$queryBuilder = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder($permEntity, $builderOptions)
		;

		return $queryBuilder->buildCompatible();
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Security\Manager::resolveController($entityType)->unregister($entityType, $entityId) instead
	 * @see \Bitrix\Crm\Security\Controller::unregister
	 */
	static public function DeleteEntityAttr($entityType, $entityId)
	{
		\Bitrix\Crm\Security\Manager::resolveController($entityType)->unregister($entityType, $entityId);
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Security\Manager::resolveController($permEntity)->getPermissionAttributes($arIDs) instead
	 * @see \Bitrix\Crm\Security\Controller::getPermissionAttributes
	 */
	static public function GetEntityAttr($permEntity, $arIDs)
	{
		return
			\Bitrix\Crm\Security\Manager::resolveController($permEntity)
				->getPermissionAttributes((string)$permEntity, (array)$arIDs)
		;
	}

	/**
	 * @deprecated Use \Bitrix\Crm\Security\Manager::resolveController($permissionEntityType)->register($permissionEntityType, $entityId, $options) instead
	 * @see \Bitrix\Crm\Security\Controller::register
	 */
	static public function UpdateEntityAttr($permissionEntityType, $entityId, $entityAttributes = [])
	{
		$registerOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
			->setEntityAttributes($entityAttributes)
		;

		\Bitrix\Crm\Security\Manager::resolveController($permissionEntityType)
			->register($permissionEntityType, $entityId, $registerOptions)
		;
	}

	public static function ResolvePermissionEntityType($entityType, $entityID, array $parameters = null)
	{
		if(!is_integer($entityID))
		{
			$entityID = (int)$entityID;
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return $entityType;
		}

		$categoryID = is_array($parameters) && isset($parameters['CATEGORY_ID'])
			? (int)$parameters['CATEGORY_ID'] : -1;

		if($categoryID < 0 && $entityID > 0)
		{
			if ($factory instanceof \Bitrix\Crm\Service\Factory\Deal)
			{
				//todo temporary decision while Deal Factory does not support work with items.
				$categoryID = CCrmDeal::GetCategoryID($entityID);
			}
			else
			{
				$items = $factory->getItems([
					'select' => [\Bitrix\Crm\Item::FIELD_NAME_CATEGORY_ID],
					'filter' => [
						'=ID' => $entityID
					],
					'limit' => 1,
				]);
				if (isset($items[0]))
				{
					$categoryID = $items[0]->getCategoryId();
				}
			}
		}

		return \Bitrix\Crm\Service\UserPermissions::getPermissionEntityType($entityTypeId, $categoryID);
	}

	public static function HasPermissionEntityType($permissionEntityType)
	{
		if(DealCategory::hasPermissionEntity($permissionEntityType))
		{
			return true;
		}

		$entityTypeID = CCrmOwnerType::ResolveID($permissionEntityType);
		return ($entityTypeID !== CCrmOwnerType::Undefined && $entityTypeID !== CCrmOwnerType::System);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\UserPermissions::getEntityNameByPermissionEntityType()
	 */
	public static function ResolveEntityTypeName($permissionEntityType)
	{
		return \Bitrix\Crm\Service\UserPermissions::getEntityNameByPermissionEntityType($permissionEntityType)
			?? $permissionEntityType
		;
	}
}
