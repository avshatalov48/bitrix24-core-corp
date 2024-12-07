<?php
namespace Bitrix\Crm\Security;

use Bitrix\Crm\Agent\Security\DynamicTypes\AttrConvertOptions;
use Bitrix\Crm\Security\Controller\DynamicItem;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main;
use Bitrix\Crm\Security\Controller\Contact;
use Bitrix\Crm\Security\Controller\Company;
use Bitrix\Crm\Security\Controller\Lead;
use Bitrix\Crm\Security\Controller\Deal;
use Bitrix\Crm\Security\Controller\Compatible;

class Manager
{
	protected static $controllers = null;

	/**
	 * @return Controller[]
	 */
	protected static function getControllers()
	{
		if(self::$controllers === null)
		{
			self::$controllers = [
				new Contact(),
				new Company(),
				new Lead(),
				new Deal(),
				new Compatible(), // should be last
			];
		}
		return self::$controllers;
	}

	/**
	 * @param string $permissionEntityType Permission Entity Type.
	 * @return Controller|null
	 */
	public static function resolveController(string $permissionEntityType): ?Controller
	{
		$controllers = self::getControllers();

		$controller = self::tryResolveControllerForDynamicType($permissionEntityType);
		if ($controller !== null)
		{
			return $controller;
		}

		foreach($controllers as $controller)
		{
			if($controller->isPermissionEntityTypeSupported($permissionEntityType))
			{
				return $controller;
			}
		}

		return null;
	}

	private static function tryResolveControllerForDynamicType(string $permissionEntityType): ?Controller
	{
		$entityTypeName = UserPermissions::getEntityNameByPermissionEntityType($permissionEntityType);
		if (isset(self::$controllers[$entityTypeName]))
		{
			return self::$controllers[$entityTypeName];
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		if (!DynamicItem::isSupportedType($entityTypeId))
		{
			return null;
		}

		// To use the Dynamic Type controller, you need to make sure that the conference is completely completed.
		if (AttrConvertOptions::isEntityTypeNotConvertedYet($entityTypeId))
		{
			return null;
		}

		self::$controllers[$entityTypeName] = new DynamicItem($entityTypeId);

		return self::$controllers[$entityTypeName];
	}

	/**
	 * @param int $entityTypeID Entity Type ID (see \CCrmOwnerType)
	 * @return Controller
	 * @throws Main\NotSupportedException
	 */
	public static function getEntityController($entityTypeID)
	{
		$controllers = self::getControllers();
		foreach($controllers as $controller)
		{
			if($controller->isEntityTypeSupported($entityTypeID))
			{
				return $controller;
			}
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
	}

	public static function getCompatibleController(): Controller
	{
		$controllers = self::getControllers();
		foreach($controllers as $controller)
		{
			if($controller instanceof Compatible)
			{
				return $controller;
			}
		}

		throw new Main\NotSupportedException("Compatible controller not found");
	}
}
