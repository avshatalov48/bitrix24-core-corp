<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Efficiency;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Util\Type\DateTime;

class ResponsibleEfficiency extends Effective
{
	use EfficiencyTrait;

	protected int $flowId;
	protected array $responsibleIds;
	protected Range $range;

	public function __construct(Range $range, int $flowId)
	{
		$this->range = $range;
		$this->flowId = $flowId;

		$this->init();
	}

	public static function isEnabled(): bool
	{
		return Option::get('tasks', 'tasks_flow_responsible_efficiency_enabled', 'Y') === 'Y';
	}

	public function get(int $userId): int
	{
		if (!static::isEnabled())
		{
			return $this->getDefaultEfficiency();
		}

		if ($userId <= 0)
		{
			return $this->getDefaultEfficiency();
		}

		try
		{
			$this->load($userId);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);

			return $this->getDefaultEfficiency();
		}

		return $this->efficiencies[$userId] ?? $this->getDefaultEfficiency();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function load(int ...$responsibleIds): void
	{
		if (empty($responsibleIds))
		{
			return;
		}

		$this->responsibleIds = $responsibleIds;

		$this->countEfficiencies();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function countTotals(): void
	{
		$query = self::getInProgressQuery(
			DateTime::createFromInstance($this->range->from()),
			DateTime::createFromInstance($this->range->to()),
		);

		$flowReference = (
			new ReferenceField(
				'TMP_FLOW',
				FlowTaskTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			)
		)->configureJoinType(Join::TYPE_LEFT);

		$totals =
			$query
				->setSelect(
					[
						'CNT' => Query::expr()->countDistinct('ID'),
						'RESPONSIBLE_ID',
					],
				)
				->where('TMP_FLOW.FLOW_ID', $this->flowId)
				->whereIn('RESPONSIBLE_ID', $this->responsibleIds)
				->registerRuntimeField($flowReference)
				->addGroup('RESPONSIBLE_ID')
				->exec()
				->fetchAll()
		;

		$totalsByUserId = array_combine(
			array_column($totals, 'RESPONSIBLE_ID'),
			array_map('intval', array_column($totals, 'CNT')),
		);

		foreach ($this->responsibleIds as $userId)
		{
			$this->totals[$userId] = $totalsByUserId[$userId] ?? 0;
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function countViolations(): void
	{
		$query = self::getViolationsQuery(
			DateTime::createFromInstance($this->range->from()),
			DateTime::createFromInstance($this->range->to()),
		);

		$flowReference = (
		new ReferenceField(
			'TMP_FLOW',
			FlowTaskTable::getEntity(),
			Join::on('this.TASK_ID', 'ref.TASK_ID')
		)
		)->configureJoinType(Join::TYPE_LEFT);

		$violations =
			$query
				->setSelect(
					[
						'CNT' => Query::expr()->countDistinct('TASK_ID'),
						'RESPONSIBLE_ID' => 'T.RESPONSIBLE_ID',
					],
				)
				->where('TMP_FLOW.FLOW_ID', $this->flowId)
				->whereIn('T.RESPONSIBLE_ID', $this->responsibleIds)
				->whereColumn('T.CREATED_BY', '<>', 'T.RESPONSIBLE_ID')
				->registerRuntimeField($flowReference)
				->addGroup('T.RESPONSIBLE_ID')
				->exec()
				->fetchAll()
		;

		$violationsByUserId = array_combine(
			array_column($violations, 'RESPONSIBLE_ID'),
			array_map('intval', array_column($violations, 'CNT')),
		);

		foreach ($this->responsibleIds as $userId)
		{
			$this->violations[$userId] = $violationsByUserId[$userId] ?? 0;
		}
	}

	protected function init(): void
	{
		$this->totals = [];
		$this->violations = [];
		$this->efficiencies = [];
	}
}
