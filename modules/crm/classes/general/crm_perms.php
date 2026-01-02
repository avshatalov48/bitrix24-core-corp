<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Security\QueryBuilder\OptionsBuilder;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;

class CCrmPerms
{
	const PERM_NONE = UserPermissions::PERMISSION_NONE;
	const PERM_SELF = UserPermissions::PERMISSION_SELF;
	const PERM_DEPARTMENT = UserPermissions::PERMISSION_DEPARTMENT;
	const PERM_SUBDEPARTMENT = UserPermissions::PERMISSION_SUBDEPARTMENT;
	const PERM_OPEN = UserPermissions::PERMISSION_OPENED;
	const PERM_ALL = UserPermissions::PERMISSION_ALL;
	const PERM_CONFIG = UserPermissions::PERMISSION_CONFIG;

	const ATTR_READ_ALL = UserPermissions::ATTRIBUTES_READ_ALL;

	private static $INSTANCES = array();
	protected $userId = 0;
	protected ?array $arUserPerms = null;

	function __construct($userId)
	{
		$this->userId = intval($userId);
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
	 * @deprecated Do not use permission attributes directly!
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

	/**
	 * @deprecated Do not use permission attributes directly!
	 */
	public function GetUserPerms()
	{
		if (is_null($this->arUserPerms))
		{
			$this->arUserPerms = CCrmRole::GetUserPerms($this->userId);
		}

		return $this->arUserPerms;
	}

	/**
	 * @deprecated Do not use permission attributes directly!
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()
	 */
	public function HavePerm($permEntity, $permAttr, $permType = 'READ'): bool
	{
		$permEntity = (string)$permEntity;
		$permAttr = (string)$permAttr;
		$permType = (string)$permType;

		// HACK: only for product and currency support
		$permType = mb_strtoupper($permType);

		// CRM admins can always edit robots.
		if (
			$permType === 'AUTOMATION'
			&& $permAttr == BX_CRM_PERM_ALL
			&& self::HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
		)
		{
			return true;
		}
		// Config READ is always true
		if ($permEntity == 'CONFIG' && $permAttr == self::PERM_CONFIG && $permType == 'READ')
		{
			return true;
		}
		// HACK: Compatibility with CONFIG rights
		if ($permEntity == 'CONFIG')
		{
			$permType = 'WRITE';
		}

		$permissionsManager = PermissionsManager::getInstance($this->userId);
		if ($permAttr === self::PERM_NONE)
		{
			return !$permissionsManager->hasPermission($permEntity, $permType);
		}

		return $permissionsManager->hasPermissionLevel($permEntity, $permType, $permAttr);
	}

	/**
	 * @deprecated Do not use permission attributes directly!
	 */
	public function GetPermType($permEntity, $permType = 'READ', $arEntityAttr = array())
	{
		// Change config right also grant right to change robots.
		if (
			$permType === 'AUTOMATION'
			&& self::HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')
		)
		{
			return BX_CRM_PERM_ALL;
		}

		return PermissionsManager::getInstance($this->userId)
			->getPermissionAttributeByEntityAttributes((string)$permEntity, (string)$permType, (array)$arEntityAttr)
		;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadAnyItems();
	 */
	static public function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = self::GetCurrentUserPermissions();
		}

		return Container::getInstance()->getUserPermissions($userPermissions->GetUserID())->entityType()->canReadSomeItemsInCrm();
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canRead(); etc
	 */
	public function CheckEnityAccess($permEntity, $permType, $arEntityAttr)
	{
		return PermissionsManager::getInstance($this->userId)
			->doUserAttributesMatchesToEntityAttributes($permEntity, $permType, $arEntityAttr)
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
			->itemsList()
			->createQueryBuilder($entityTypes, $builderOptions)
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
			->itemsList()
			->createQueryBuilder($permEntity, $builderOptions)
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

		return (new \Bitrix\Crm\Category\PermissionEntityTypeHelper($entityTypeId))
			->getPermissionEntityTypeForCategory($categoryID)
		;
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
}
