<?php

namespace Bitrix\Crm\Counter\QueryBuilder;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Counter\QueryBuilder;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * Group of counters depend on activities deadlines
 */
class DeadlineBased extends QueryBuilder
{
	protected ?Date $periodFrom = null;
	protected ?Date $periodTo = null;
	protected $useOnlyMinDeadline = false;

	public function getPeriodFrom(): ?Date
	{
		return $this->periodFrom;
	}

	public function setPeriodFrom(?Date $periodFrom): self
	{
		$this->periodFrom = $periodFrom;

		return $this;
	}

	private function getLowBound(): ?DateTime
	{
		if (!$this->getPeriodFrom())
		{
			return null;
		}
		$lowBound = DateTime::createFromTimestamp($this->getPeriodFrom()->getTimestamp());
		$lowBound->setTime(0, 0, 0);

		return \CCrmDateTimeHelper::getServerTime($lowBound,$this->userIds[0] ?? null)->disableUserTime();
	}

	public function getPeriodTo(): ?Date
	{
		return $this->periodTo;
	}

	public function setPeriodTo(?Date $periodTo): self
	{
		$this->periodTo = $periodTo;

		return $this;
	}

	private function getHighBound(): ?DateTime
	{
		if (!$this->getPeriodTo())
		{
			return null;
		}
		$highBound = DateTime::createFromTimestamp($this->getPeriodTo()->getTimestamp());
		$highBound->setTime(23, 59, 59);

		return \CCrmDateTimeHelper::getServerTime($highBound,$this->userIds[0] ?? null)->disableUserTime();
	}

	public function useOnlyMinDeadline(): bool
	{
		return $this->useOnlyMinDeadline;
	}

	public function setUseOnlyMinDeadline(bool $useOnlyMinDeadline): self
	{
		$this->useOnlyMinDeadline = $useOnlyMinDeadline;

		return $this;
	}

	public function build(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		if (!$this->useOnlyMinDeadline)
		{
			return $this->buildCompatible($query);
		}

		return parent::build($query);
	}

	protected function buildCompatible(\Bitrix\Main\ORM\Query\Query $query): \Bitrix\Main\ORM\Query\Query
	{
		$query->registerRuntimeField(
			'',
			new ReferenceField('B',
				ActivityBindingTable::getEntity(),
				array(
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new SqlExpression($this->entityTypeId)
				),
				array('join_type' => 'INNER')
			)
		);

		//region Activity (inner join with correlated query for fix issue #109347)
		$activityQuery = ActivityTable::query();

		$this->applyResponsibleFilter($activityQuery, 'RESPONSIBLE_ID');
		$this->applyDeadlineFilter($activityQuery);

		$activityQuery->addFilter('=COMPLETED', 'N');
		$activityQuery->addSelect('ID');

		$query->registerRuntimeField(
			'',
			new ReferenceField('A',
				\Bitrix\Main\Entity\Base::getInstanceByQuery($activityQuery),
				array('=ref.ID' => 'this.B.ACTIVITY_ID'),
				array('join_type' => 'INNER')
			)
		);
		//endregion

		if($this->getSelectType() === self::SELECT_TYPE_ENTITIES)
		{
			$query->addSelect('B.OWNER_ID', 'ENTY');
			if($this->isUseDistinct())
			{
				$query->addGroup('B.OWNER_ID');
			}
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID'));
			$query->addSelect('QTY');
		}

		return $query;
	}

	protected function applyReferenceFilter(array &$referenceFilter): void
	{
		if (count($this->userIds) <= 1)
		{
			$referenceFilter['=ref.RESPONSIBLE_ID'] =new SqlExpression('?i', count($this->userIds) ? $this->userIds[0] : 0); // 0 means "All users"
		}
		else
		{
			$referenceFilter['@ref.RESPONSIBLE_ID'] =new SqlExpression(implode(',', $this->userIds));
		}

		$referenceFilter['=ref.IS_INCOMING_CHANNEL'] = new SqlExpression('?', 'N');
		if (count($this->userIds) <= 1)
		{
			$this->applyDeadlineReferenceFilter($referenceFilter);
		}
	}

	protected function applyCounterTypeFilter(\Bitrix\Main\ORM\Query\Query $query): void
	{
		if($this->getSelectType() === self::SELECT_TYPE_ENTITIES && count($this->userIds) > 1)
		{
			$this->applyDeadlineHavingFilter($query);
		}
	}

	private function applyDeadlineReferenceFilter(array &$referenceFilter): void
	{
		$lowBound = $this->getLowBound();
		$highBound = $this->getHighBound();

		$sqlHelper = Application::getConnection()->getSqlHelper();

		if ($lowBound)
		{
			$referenceFilter['>=ref.MIN_DEADLINE'] = new SqlExpression($sqlHelper->convertToDbDateTime($lowBound));
		}
		if ($highBound)
		{
			$referenceFilter['<=ref.MIN_DEADLINE'] = new SqlExpression($sqlHelper->convertToDbDateTime($highBound));
		}
	}

	private function applyDeadlineHavingFilter(\Bitrix\Main\ORM\Query\Query $query): void
	{
		$lowBound = $this->getLowBound();
		$highBound = $this->getHighBound();

		$sqlHelper = Application::getConnection()->getSqlHelper();

		if ($lowBound && $highBound)
		{
			$query->whereExpr(
				'MIN(MIN_DEADLINE) BETWEEN ' .
				$sqlHelper->convertToDbDateTime($lowBound) .
				' AND ' . $sqlHelper->convertToDbDateTime($highBound),
				[]
			);
		}
		elseif ($lowBound)
		{
			$query->whereExpr('MIN(MIN_DEADLINE) >= '. $sqlHelper->convertToDbDateTime($lowBound), []);
		}
		elseif ($highBound)
		{
			$query->whereExpr('MIN(MIN_DEADLINE) <= '. $sqlHelper->convertToDbDateTime($highBound), []);
		}
	}

	private function applyDeadlineFilter(\Bitrix\Main\ORM\Query\Query $query): void
	{
		$lowBound = $this->getLowBound();
		$highBound = $this->getHighBound();

		if ($lowBound)
		{
			$query->addFilter('>=DEADLINE', $lowBound->toUserTime());
		}

		if ($highBound)
		{
			$query->addFilter('<=DEADLINE', $highBound->toUserTime());
		}
	}
}
