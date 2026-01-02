<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Public\Service\Node;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type\StructureRole;

class UserService
{
	/**
	 * Returns first NodeMember by user ID and structure roles
	 *
	 * @param int $userId
	 * @param array<StructureRole> $structureRoles
	 *
	 * @return NodeMember|null Found node member or null if not found
	 */
	public function findByUserIdAndStructureRoles(int $userId, array $structureRoles): ?NodeMember
	{
		if (empty($structureRoles))
		{
			return null;
		}

		return
			(new NodeMemberDataBuilder)
				->addFilter(
					new NodeMemberFilter(
						entityIdFilter: EntityIdFilter::fromEntityId($userId),
					),
				)
				->setStructureRoles($structureRoles)
				->get()
		;
	}
}
