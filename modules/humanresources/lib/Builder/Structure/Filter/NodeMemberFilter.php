<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Filter;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Model\NodePathTable;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\SystemException;

final class NodeMemberFilter extends BaseFilter
{
	public function __construct(
		public ?EntityIdFilter $entityIdFilter = null,
		public ?MemberEntityType $entityType = MemberEntityType::USER,
		public ?NodeFilter $nodeFilter = null,
		public bool $findRelatedMembers = false,
		public ?bool $active = true,
	)
	{}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function prepareFilter(): ConditionTree
	{
		$conditionTree = new ConditionTree();

		$this->nodeFilter?->setCurrentAlias('NODE');

		if (
			$this->entityIdFilter?->entityIds !== null
			&& $this->findRelatedMembers
			&& $this->nodeFilter
		)
		{
			$this->applyRelatedCondition($conditionTree);
		}
		else if($this->entityIdFilter)
		{
			$conditionTree->where($this->entityIdFilter->prepareFilter());
		}

		if ($this->entityType !== null)
		{
			$conditionTree->where(
				$this->getFieldByQueryContext('ENTITY_TYPE'),
				$this->entityType->value,
			);
		}

		if ($this->active !== null)
		{
			$conditionTree->where($this->getFieldByQueryContext('ACTIVE'), $this->active);
		}

		if ($this->nodeFilter !== null)
		{
			$conditionTree->addCondition($this->nodeFilter->prepareFilter());
		}

		return $conditionTree;
	}

	/**
	 * @param ConditionTree $conditionTree
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function applyRelatedCondition(ConditionTree $conditionTree): void
	{
		$query = NodeMemberTable::query();

		if ($this->nodeFilter->depthLevel === DepthLevel::NONE)
		{
			$conditionTree->whereExists(
				new SqlExpression(
					"SELECT 1 FROM ?#"
					. " sub_hrm WHERE sub_hrm.NODE_ID = ?#.NODE_ID AND sub_hrm.ENTITY_ID = ?",
					NodeMemberTable::getTableName(),
					$query->getInitAlias(),
					$this->entityIdFilter->entityIds->first(),
				),
			);
		}

		if ($this->nodeFilter->direction === Direction::ROOT)
		{
			$expression =
				"SELECT np.PARENT_ID FROM"
				. " ?# sub_hrm "
				. "LEFT JOIN ?# np "
				. "ON np.CHILD_ID = sub_hrm.NODE_ID "
				. "WHERE sub_hrm.ENTITY_ID = ?";

			$expression = match ($this->nodeFilter->depthLevel)
			{
				DepthLevel::FIRST => $expression . " AND np.DEPTH = 1",
				DepthLevel::NONE => $expression . " AND np.DEPTH = 0",
				default => $expression,
			};

			$conditionTree->whereIn(
				'NODE_ID',
				new SqlExpression(
					$expression,
					NodeMemberTable::getTableName(),
					NodePathTable::getTableName(),
					$this->entityIdFilter->entityIds->first(),
					$this->nodeFilter->depthLevel,
				),
			);
		}
	}
}