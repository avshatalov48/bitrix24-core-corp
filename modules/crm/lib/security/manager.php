<?php
namespace Bitrix\Crm\Security;

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
	public static function resolveController(string $permissionEntityType)
	{
		$controllers = self::getControllers();
		foreach($controllers as $controller)
		{
			if($controller->isEnabled() && $controller->isPermissionEntityTypeSupported($permissionEntityType))
			{
				return $controller;
			}
		}

		return null;
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
