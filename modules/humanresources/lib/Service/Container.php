<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter;
use Bitrix\HumanResources\Compatibility\Converter\UserBackwardConverter;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Repository\NodeRelationRepository;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Contract\Repository\StructureRepository;
use Bitrix\Main\DI\ServiceLocator;
use \Bitrix\HumanResources\Contract;
use \Bitrix\HumanResources\Util;

class Container
{
	public static function instance(): Container
	{
		return self::getService('humanresources.container');
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

	public static function getNodeRepository(): NodeRepository
	{
		return self::getService('humanresources.repository.node');
	}

	public static function getNodeAccessCodeRepository(): Contract\Repository\NodeAccessCodeRepository
	{
		return self::getService('humanresources.repository.node.access.code');
	}

	public static function getNodeRelationRepository(): NodeRelationRepository
	{
		return self::getService('humanresources.repository.node.relation');
	}

	public static function getNodeMemberRepository(): NodeMemberRepository
	{
		return self::getService('humanresources.repository.node.member');
	}

	public static function getStructureRepository(): StructureRepository
	{
		return self::getService('humanresources.repository.structure');
	}

	public static function getNodeService(): Contract\Service\NodeService
	{
		return self::getService('humanresources.service.node');
	}

	public static function getRoleRepository(): Contract\Repository\RoleRepository
	{
		return self::getService('humanresources.repository.role');
	}

	public static function getRoleHelperService(): Contract\Service\RoleHelperService
	{
		return self::getService('humanresources.service.role.helper');
	}

	public static function getEventSenderService(): Contract\Service\EventSenderService
	{
		return self::getService('humanresources.service.event.sender');
	}

	public static function getNodeMemberService(): Contract\Service\NodeMemberService
	{
		return self::getService('humanresources.service.node.member');
	}

	public static function getStructureWalkerService(): Contract\Service\StructureWalkerService
	{
		return self::getService('humanresources.service.structure.walker');
	}

	public static function getCacheManager(): Contract\Util\CacheManager
	{
		return self::getService('humanresources.util.cache');
	}

	public static function getAccessRolePermissionService(): Contract\Service\Access\RolePermissionService
	{
		return self::getService('humanresources.service.access.rolePermission');
	}

	public static function getAccessRoleRelationService(): Contract\Service\Access\RoleRelationService
	{
		return self::getService('humanresources.service.access.roleRelation');
	}

	public static function getAccessPermissionRepository(): Contract\Repository\Access\PermissionRepository
	{
		return self::getService('humanresources.repository.access.permission');
	}

	public static function getAccessRoleRepository(): Contract\Repository\Access\RoleRepository
	{
		return self::getService('humanresources.repository.access.role');
	}

	public static function getAccessRoleRelationRepository(): Contract\Repository\Access\RoleRelationRepository
	{
		return self::getService('humanresources.repository.access.roleRelation');
	}

	public static function getStructureBackwardConverter(): StructureBackwardConverter
	{
		return self::getService('humanresources.compatibility.converter');
	}

	public static function getStructureUserBackwardConverter(): UserBackwardConverter
	{
		return self::getService('humanresources.compatibility.converter.user');
	}

	public static function getNodeRelationService(): Contract\Service\NodeRelationService
	{
		return self::getService('humanresources.service.node.relation');
	}

	public static function getStructureLogger(): Contract\Util\Logger
	{
		return self::getService('humanresources.util.database.logger');
	}

	public static function getSemaphoreService(): Contract\Service\SemaphoreService
	{
		return self::getService('humanresources.service.semaphore');
	}

	public static function getUserService(): Contract\Service\UserService
	{
		return self::getService('humanresources.service.user');
	}

	public static function getUserRepository(): Contract\Repository\UserRepository
	{
		return self::getService('humanresources.repository.user');
	}

	public static function getNodeMemberCounterHelper(): Util\NodeMemberCounterHelper
	{
		return self::getService('humanresources.helper.node.member.counter');
	}

	public static function getAccessNodeRepository(): Contract\Repository\Access\AccessNodeRepository
	{
		return self::getService('humanresources.repository.access.accessNodeRepository');
	}
}
