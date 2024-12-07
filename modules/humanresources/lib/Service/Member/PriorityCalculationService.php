<?php

namespace Bitrix\HumanResources\Service\Member;

use Bitrix\HumanResources\Type;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection;

class PriorityCalculationService
{
	public function getMemberPriorityDifference(
		Item\NodeMember $member,
		Item\NodeMember $targetMember,
		Collection\RoleCollection $roleCollection,
	): ?int
	{
		$memberPriority = $this->getMemberPriority($member, $roleCollection);
		$targetMemberPriority = $this->getMemberPriority($targetMember, $roleCollection);

		if ($memberPriority === null || $targetMemberPriority === null)
		{
			return null;
		}

		return $memberPriority - $targetMemberPriority;
	}

	public function getMemberPriority(
		Item\NodeMember $member,
		Collection\RoleCollection $roleCollection,
	): ?int
	{
		$priority = PHP_INT_MIN;
		foreach ($member->roles as $roleId)
		{
			$priority = max($roleCollection->getItemById($roleId)?->priority ?? PHP_INT_MIN, $priority);
		}

		return $priority === PHP_INT_MIN ? null : $priority;
	}

	public function getMemberAffectingChildPriority(
		Item\NodeMember $member,
		Item\Collection\RoleCollection $roleCollection,
	): ?int
	{
		$roleCollection = $roleCollection->filter(
			static fn(Item\Role $role) => $role->childAffectionType === Type\RoleChildAffectionType::AFFECTING
		);

		return $this->getMemberPriority($member, $roleCollection);
	}
}