<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Integration\HumanResources;

use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use CIntranetAuthProvider;

class EventHandler
{
	public static function onNodeUpdated(\Bitrix\Main\Event $event): void
	{
		if (!\Bitrix\Main\Loader::includeModule('humanresources'))
		{
			return;
		}

		/**
		 * @var \Bitrix\HumanResources\Item\Node $node
		 */
		$node = $event->getParameter('node');
		$changedFields = $event->getParameter('fields');
		if (!$node || !in_array('active', $changedFields))
		{
			return;
		}

		$members = Container::getNodeMemberService()->getAllEmployees($node->id, true);

		foreach ($members as $member)
		{
			(new CIntranetAuthProvider())->DeleteByUser($member->entityId);
		}
	}

	public static function onNodeDeleted(\Bitrix\Main\Event $event): void
	{
		(new CIntranetAuthProvider())->DeleteAll();
	}

	public static function onMemberChanges(\Bitrix\Main\Event $event): void
	{
		if (!\Bitrix\Main\Loader::includeModule('humanresources'))
		{
			return;
		}

		/**
		 * @var \Bitrix\HumanResources\Item\NodeMember $member
		 */
		$member = $event->getParameter('member');
		if (!$member || $member->entityType !== MemberEntityType::USER)
		{
			return;
		}

		(new CIntranetAuthProvider())->DeleteByUser($member->entityId);

		$managers = self::getMemberManagerIds($member);

		foreach ($managers as $manager)
		{
			(new CIntranetAuthProvider())->DeleteByUser($manager);
		}
	}

	private static function getMemberManagerIds(\Bitrix\HumanResources\Item\NodeMember $member): ?array
	{
		$nodeRepository = Container::getNodeRepository();
		$nodeMemberRepository = Container::getNodeMemberRepository();
		$node = $nodeRepository->getById($member->nodeId);

		if (!$node)
		{
			return [];
		}

		$allParentsChain = $nodeRepository->getParentOf(
			$node,
			\Bitrix\HumanResources\Enum\DepthLevel::FULL,
		);

		$headRoleId = Container::getRoleHelperService()->getHeadRoleId();
		$managers = $nodeMemberRepository->findAllByRoleIdAndNodeCollection($headRoleId, $allParentsChain);

		return array_map(
			fn($manager) => $manager->entityId,
			iterator_to_array($managers),
		);
	}
}