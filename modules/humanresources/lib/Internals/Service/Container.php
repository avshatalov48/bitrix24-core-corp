<?php

namespace Bitrix\HumanResources\Internals\Service;

use Bitrix\HumanResources\Internals\Repository\Structure\Node\NodeMemberRepository;
use Bitrix\HumanResources\Internals\Repository\Structure\Node\NodeRepository;
use Bitrix\HumanResources\Internals\Service\Structure\AccessService;
use Bitrix\HumanResources\Internals\Service\Structure\NodeAccessCodeService;
use Bitrix\HumanResources\Internals\Repository\Structure\NodeAccessCodeRepository;
use Bitrix\HumanResources\Internals\Service\Structure\NodeChatService;
use Bitrix\HumanResources\Internals\Service\Structure\NodeCollabService;
use Bitrix\HumanResources\Internals\Service\Structure\NodeMemberService;
use Bitrix\HumanResources\Internals\Service\Structure\NodeSettingsService;
use Bitrix\Main\DI\ServiceLocator;

/**
 * Container with services for usage in internal modules
 */
class Container
{
	public static function instance(): Container
	{
		return self::getService('humanresources.internal.container');
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

	public static function getNodeAccessCodeService(): NodeAccessCodeService
	{
		return self::getService('humanresources.internal.service.structure.nodeAccessService');
	}

	public static function getNodeChatService(): NodeChatService
	{
		return self::getService('humanresources.service.internal.nodeChatService');
	}

	public static function getNodeCollabService(): NodeCollabService
	{
		return self::getService('humanresources.service.internal.nodeCollabService');
	}

	public static function getNodeMemberService(): NodeMemberService
	{
		return self::getService('humanresources.internal.service.nodeMemberService');
	}

	public static function getNodeSettingsService(): NodeSettingsService
	{
		return self::getService('humanresources.service.internal.nodeSettingsService');
	}

	public static function getNodeRepository(): NodeRepository
	{
		return self::getService('humanresources.repository.internal.nodeRepository');
	}

	public static function getNodeAccessCodeRepository(): NodeAccessCodeRepository
	{
		return self::getService('humanresources.internal.repository.structure.nodeAccessCode');
	}

	public static function getAccessService(): AccessService
	{
		return self::getService('humanresources.service.internal.accessService');
	}

	public static function getNodeMemberRepository(): NodeMemberRepository
	{
		return self::getService('humanresources.internal.repository.structure.node.nodeMemberRepository');
	}
}
