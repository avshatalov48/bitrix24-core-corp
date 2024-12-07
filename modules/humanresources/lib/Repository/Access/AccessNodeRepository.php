<?php

namespace Bitrix\HumanResources\Repository\Access;

use Bitrix\HumanResources;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Model\NodePathTable;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

final class AccessNodeRepository implements HumanResources\Contract\Repository\Access\AccessNodeRepository
{
	public function isDepartmentUser(
		int $nodeId,
		int $userId,
		bool $checkSubdepartments = false
	): bool
	{
		$department =
			NodeTable::query()
				->setSelect(['ID'])
				->where('TYPE', NodeEntityType::DEPARTMENT->name)
				->where('ID', $nodeId)
				->registerRuntimeField(
					'nm',
					new Reference(
						'nm',
						Model\NodeMemberTable::class,
						Join::on('this.ID', 'ref.NODE_ID'),
					),
				)
				->where('nm.ENTITY_ID', $userId)
				->where('nm.ENTITY_TYPE', MemberEntityType::USER->name)
				->setLimit(1)
				->fetch()
		;

		if ($department)
		{
			return true;
		}

		if (!$checkSubdepartments)
		{
			return false;
		}

		return $this->isSubdepartmentUser($nodeId, $userId);
	}

	private function isSubdepartmentUser(
		int $nodeId,
		int $userId,
	): bool
	{
		$departmentList =
			NodeTable::query()
				->setSelect(['ID'])
				->where('TYPE', NodeEntityType::DEPARTMENT->name)
				->registerRuntimeField(
					'nm',
					new Reference(
						'nm',
						Model\NodeMemberTable::class,
						Join::on('this.ID', 'ref.NODE_ID'),
					),
				)
				->where('nm.ENTITY_ID', $userId)
				->where('nm.ENTITY_TYPE', MemberEntityType::USER->name)
				->fetchAll()
		;

		$departmentIds = [];
		foreach ($departmentList as $node)
		{
			$departmentIds[] = $node['ID'];
		}

		if (empty($departmentIds))
		{
			return false;
		}

		$subNode = NodePathTable::query()
			->setSelect(['CHILD_ID'])
			->whereIn('PARENT_ID', $departmentIds)
			->where('CHILD_ID', $nodeId)
			->setLimit(1)
			->fetch()
		;

		if ($subNode)
		{
			return true;
		}

		return false;
	}
}