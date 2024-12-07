<?php

namespace Bitrix\Stafftrack\Integration\HumanResources;

use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\StaffTrack\Trait\Singleton;
use Bitrix\StaffTrack\Item;

class Structure
{
	use Singleton;

	public function getDepartmentsByUserId(int $userId): Item\Collection\DepartmentCollection
	{
		if (!Main\Loader::includeModule('humanresources'))
		{
			return new Item\Collection\DepartmentCollection([]);
		}

		$departmentNodes = Container::getNodeService()->getNodesByUserId($userId);

		$departments = [];
		foreach ($departmentNodes->getIterator() as $departmentNode)
		{
			$departments[] = new Item\Department(
				id: $departmentNode->id,
				name: $departmentNode->name,
			);
		}

		return new Item\Collection\DepartmentCollection($departments);
	}

	public function getDepartmentUserIds(int $departmentId): array
	{
		if (!Main\Loader::includeModule('humanresources'))
		{
			return [];
		}

		$userNodes = Container::getNodeMemberService()->getAllEmployees($departmentId);

		return array_map(static fn ($entity) => $entity->entityId, [...$userNodes->getIterator()]);
	}

	public function getDepartmentHeadId(int $departmentId): int
	{
		if (!Main\Loader::includeModule('humanresources'))
		{
			return 0;
		}

		$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;

		if ($headRole === null)
		{
			return 0;
		}

		$headMembers = Container::getNodeMemberRepository()->findAllByRoleIdAndNodeId($headRole, $departmentId);

		return $headMembers->getIterator()->current()?->entityId ?? 0;
	}
}
