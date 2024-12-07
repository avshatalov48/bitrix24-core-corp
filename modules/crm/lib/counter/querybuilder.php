<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;

abstract class QueryBuilder
{
	public const SELECT_TYPE_QUANTITY = 'QTY';
	public const SELECT_TYPE_ENTITIES = 'ENTY';

	protected int $entityTypeId;
	/**
	 * @var int[]
	 */
	protected array $userIds;
	protected string $selectType = self::SELECT_TYPE_QUANTITY;
	protected bool $useDistinct = true;
	protected bool $needExcludeUsers = false;
	protected bool $useUncompletedActivityTable = false;
	protected ?int $counterLimit = null;

	public function __construct(int $entityTypeId, array $userIds = [])
	{
		$this->entityTypeId = $entityTypeId;
		$this->userIds = array_values(array_unique(array_map('intval', $userIds)));
	}

	public function getSelectType(): string
	{
		return $this->selectType;
	}

	public function setSelectType(string $selectType): self
	{
		$this->selectType = $selectType;

		return $this;
	}

	public function isUseDistinct(): bool
	{
		return $this->useDistinct;
	}

	public function setUseDistinct(bool $useDistinct): self
	{
		$this->useDistinct = $useDistinct;

		return $this;
	}

	public function useUncompletedActivityTable(): bool
	{
		return $this->useUncompletedActivityTable;
	}

	public function setUseUncompletedActivityTable(bool $useUncompletedActivityTable): self
	{
		$this->useUncompletedActivityTable = $useUncompletedActivityTable;

		return $this;
	}

	public function needExcludeUsers(): bool
	{
		return $this->needExcludeUsers;
	}

	public function setExcludeUsers(bool $needExcludeUsers): self
	{
		$this->needExcludeUsers = $needExcludeUsers;

		return $this;
	}

	public function getCounterLimit(): ?int
	{
		return $this->counterLimit;
	}

	public function setCounterLimit(?int $counterLimit): self
	{
		$this->counterLimit = $counterLimit;

		return $this;
	}

	protected function applyResponsibleFilter(\Bitrix\Main\ORM\Query\Query $query, string $responsibleFieldName)
	{
		if (!empty($this->userIds))
		{
			if ($this->needExcludeUsers())
			{
				if (count($this->userIds) > 1)
				{
					$query->whereNotIn($responsibleFieldName, array_merge($this->userIds, [0]));
				}
				else
				{
					$query->whereNot($responsibleFieldName, $this->userIds[0]);
					$query->whereNot($responsibleFieldName, 0);
				}
			}
			else
			{
				if (count($this->userIds) > 1)
				{
					$query->whereIn($responsibleFieldName, $this->userIds);
				}
				else
				{
					$query->where($responsibleFieldName, $this->userIds[0]);
				}
			}
		}
	}

	/**
	 * Compatibility mode used while \Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable is not completely filled with data
	 * @return bool
	 */
	protected function canUseUncompletedActivityTable(): bool
	{
		return Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y';
	}

	/**
	 * Compatibility mode used while \Bitrix\Crm\Counter\EntityCountableActivityTable is not completely filled with data
	 * @return bool
	 */
	protected function canUseEntityCountableActivityTable(): bool
	{
		return Option::get('crm', 'enable_entity_countable_act', 'Y') === 'Y';
	}

	public function build(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		if (
			!$this->canUseUncompletedActivityTable()
			|| (
				!$this->useUncompletedActivityTable()
				&& !$this->canUseEntityCountableActivityTable()
			)
		)
		{
			return $this->buildCompatible($query);
		}

		if (!$this->canUseEntityCountableActivityTable() || $this->useUncompletedActivityTable())
		{
			return $this->buildForUncompletedActivityTable($query);
		}

		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($this->entityTypeId))
		;

		$this->applyEntityCountableActivityTableReferenceFilter($referenceFilter);

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				EntityCountableActivityTable::getEntity(),
				$referenceFilter,
				['join_type' => Join::TYPE_INNER]
			)
		);
		$this->applyResponsibleFilter($query, 'A.ENTITY_ASSIGNED_BY_ID');

		if($this->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
			if($this->isUseDistinct())
			{
				$query->addGroup('ID');
			}
		}
		else
		{
			if ($this->getCounterLimit())
			{
				$query->setLimit($this->getCounterLimit());
				$query->addSelect('ID');

				$entity = Entity::getInstanceByQuery($query);

				$newQuery = (new \Bitrix\Main\ORM\Query\Query($entity));
				$newQuery->registerRuntimeField('', $this->getQuantityExpression());
				$newQuery->addSelect('QTY');

				return $newQuery;
			}
			else
			{
				$query->registerRuntimeField('', $this->getQuantityExpression());
				$query->addSelect('QTY');
			}
		}

		return $query;
	}

	protected function getJoinType(): string
	{
		return Join::TYPE_INNER;
	}

	protected function applyUncompletedActivityTableReferenceFilter(\Bitrix\Main\ORM\Query\Filter\ConditionTree $referenceFilter): void
	{
	}

	protected function applyEntityCountableActivityTableReferenceFilter(\Bitrix\Main\ORM\Query\Filter\ConditionTree $referenceFilter): void
	{
	}

	protected function applyCounterTypeFilter(\Bitrix\Main\ORM\Query\Query $query): void
	{
	}

	abstract protected function buildCompatible(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query;

	protected function buildForUncompletedActivityTable(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		$referenceFilter = (new ConditionTree())
			->whereColumn('ref.ENTITY_ID', 'this.ID')
			->where('ref.ENTITY_TYPE_ID', new SqlExpression($this->entityTypeId))
		;

		$this->applyUncompletedActivityTableReferenceFilter($referenceFilter);

		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				EntityUncompletedActivityTable::getEntity(),
				$referenceFilter,
				['join_type' => $this->getJoinType()]
			)
		);

		$this->applyResponsibleFilter($query, $this->getEntityAssignedColumnName());
		$this->applyCounterTypeFilter($query);

		if ($this->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('ID', 'ENTY');
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
			$query->addSelect('QTY');
		}

		return $query;
	}

	protected function getEntityAssignedColumnName(): string
	{
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if (!$factory)
		{
			return Item::FIELD_NAME_ASSIGNED;
		}

		return $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
	}

	protected function getQuantityExpression(): ExpressionField
	{
		if ($this->isUseDistinct())
		{
			return new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID');
		}

		return new ExpressionField('QTY', 'COUNT(%s)', 'ID');
	}
}
