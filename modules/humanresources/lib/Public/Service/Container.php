<?php

namespace Bitrix\HumanResources\Public\Service;

use Bitrix\HumanResources\Public\Service\Node\UserService;
use Bitrix\HumanResources\Public\Service\Team\UserService as TeamUserService;
use Bitrix\HumanResources\Public\Service\Department\UserService as DepartmentUserService;
use Bitrix\Main\DI\ServiceLocator;

/**
 * Container with services for usage in external modules
 */
class Container
{
	public static function instance(): Container
	{
		return self::getService('humanresources.public.container');
	}

	private static function getService(string $name): mixed
	{
		$prefix = 'humanresources.';
		if (mb_strpos($name, $prefix) !== 0)
		{
			$name = $prefix . $name;
		}
		$locator = ServiceLocator::getInstance();

		return $locator->has($name)
			? $locator->get($name)
			: null
		;
	}

	public static function getNodeSettingsService(): NodeSettingsService
	{
		return self::getService('humanresources.service.public.nodeSettings');
	}

	public static function getUserService(): UserService
	{
		return self::getService('humanresources.public.service.node.userService');
	}

	public static function getUserTeamService(): TeamUserService
	{
		return self::getService('humanresources.service.public.team.userService');
	}

	public static function getUserDepartmentService(): DepartmentUserService
	{
		return self::getService('humanresources.service.public.department.userService');
	}
}
