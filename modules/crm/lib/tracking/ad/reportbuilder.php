<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Ad;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

use Bitrix\Crm;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class ReportBuilder.
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
class ReportBuilder extends Builder
{
	/** @var Builder[] $builders  */
	protected $builders = null;

	/**
	 * Get complete label.
	 *
	 * @return string|null
	 */
	public function getCompleteLabel()
	{
		foreach ($this->getInternalBuilders() as $builder)
		{
			if (!$builder->isComplete())
			{
				return $builder->getCompleteLabel();
			}
		}

		return null;
	}

	protected function isBuilt()
	{
		foreach ($this->getInternalBuilders() as $builder)
		{
			if (!$builder->isBuilt())
			{
				return false;
			}
		}

		return true;
	}

	protected function build()
	{
		foreach ($this->getInternalBuilders() as $builder)
		{
			if ($builder->isComplete())
			{
				continue;
			}

			if (!$builder->run()->isComplete())
			{
				$this->errorCollection->add($builder->getErrorCollection()->toArray());
			}

			return;
		}
	}

	private function getInternalBuilders()
	{
		if (!$this->builders)
		{
			$this->builders = [
				new Index\IndexBuilder(),
				new Index\TraceLinker(),
			];
		}

		foreach ($this->builders as $builder)
		{
			$builder->setSourceId($this->sourceId)->setPeriod($this->dateFrom, $this->dateTo);
		}

		return $this->builders;
	}

	public function getRows($level, $parentId = null)
	{
		$result = [];
		$items = $this->getExpenses($level, $parentId);
		while($item = $items->fetch())
		{
			$childId = $item['CHILD_ID'];
			$row = [
				'id' => (int) $item['CHILD_ID'],
				'code' => $item['CODE'],
				'enabled' => (bool) $item['IS_ENABLED'],
				'title' => $item['TITLE'],
				'level' => (int) $item['LEVEL'],
				'nextLevel' => Tracking\Source\Level\Type::getNextId((int) $item['LEVEL']),
				'prevLevel' => Tracking\Source\Level\Type::getPrevId((int) $item['LEVEL']),
				'impressions' => (int) $item['IMPRESSIONS_SUM'],
				'actions' => (int) $item['ACTIONS_SUM'],
				'outcome' => (int) $item['EXPENSES_SUM'],
				'currencyId' => $item['CURRENCY_ID'],
				'leads' => 0,
				'deals' => 0,
				'successDeals' => 0,
				'cost' => 0,
				'income' => 0,
				'roi' => 0,
				'roiScale' => 0,
			];

			$row['ctr'] = $row['impressions'] ? round($row['actions'] * 100 / $row['impressions'], 2) : 0;
			$row['cpc'] = $row['actions'] ? round($row['outcome'] / $row['actions'], 2) : 0;

			$result[$childId] = $row;
		}
		if (!$result)
		{
			return [];
		}

		$dbgList = [];
		if (Crm\Settings\LeadSettings::isEnabled())
		{
			$items = $this->getTraces(
				$query = Crm\LeadTable::query(),
				\CCrmOwnerType::Lead,
				$level,
				$parentId
			);
			while ($item = $items->fetch())
			{
				$childId = $item['CHILD_ID'];
				if ($result[$childId])
				{
					$result[$childId]['leads'] = $item['CNT'];
				}
				$dbgList[] = $item;
			}
		}

		$dbgList = [];
		$items = $this->getTraces(
			$query = Crm\DealTable::query()->addSelect('IS_WON'),
			\CCrmOwnerType::Deal,
			$level,
			$parentId
		);

		while ($item = $items->fetch())
		{
			$childId = $item['CHILD_ID'];
			if ($result[$childId])
			{
				$row = $result[$childId];

				$row['deals'] += $item['CNT'];
				if ($item['IS_WON'])
				{
					$row['successDeals'] = $item['CNT'];
					$row['income'] = $item['INCOME'];
					$row['cost'] = $row['successDeals']
						? round($row['outcome'] / $row['successDeals'], 2)
						: 0;

					$row['roi'] = $row['outcome']
						? round(($row['income'] - $row['outcome']) * 100 / $row['outcome'], 0)
						: 0;

					if ($row['roi'] < 0)
					{
						$row['roiScale'] = -1;
					}
					if ($row['roi'] > 0)
					{
						$row['roiScale'] = 1;
					}
					if ($row['roi'] >= 100)
					{
						$row['roiScale'] = 2;
					}
					if ($row['roi'] == 0)
					{
						$row['roiScale'] = 0;
					}
				}

				$result[$childId] = $row;
			}
			$dbgList[] = $item;
		}

		return array_values($result);
	}

	private function getTraces(ORM\Query\Query $query, $entityTypeId, $level, $parentId = null)
	{
		$query
			->addSelect('CNT')
			->addSelect('INCOME')
			->registerRuntimeField(new Orm\Fields\Relations\Reference(
				'TRACE_ENTITY',
				Tracking\Internals\TraceEntityTable::class,
				[
					'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?', $entityTypeId),
					'=this.ID' => 'ref.ENTITY_ID'
				]
			))
			->registerRuntimeField(new Orm\Fields\ExpressionField(
				'CNT', 'COUNT(*)'
			))
			->registerRuntimeField(new Orm\Fields\ExpressionField(
				'INCOME', 'SUM(%s)', 'OPPORTUNITY_ACCOUNT'
			))
			->where('DATE_CREATE', '>=', $this->dateFrom)
			->where('DATE_CREATE', '<', (clone $this->dateTo)->add('1 day'))
			->where('TRACE_ENTITY.TRACE.SOURCE_ID', $this->sourceId)
			->where('TRACE_ENTITY.TRACE.TRACE_SOURCE.LEVEL', $level)
		;

		$query->addSelect('TRACE_ENTITY.TRACE.TRACE_SOURCE.SOURCE_CHILD_ID','CHILD_ID');
		$query->addSelect('TRACE_ENTITY.TRACE.TRACE_SOURCE.LEVEL','LEVEL');
		$query->addSelect('TRACE_ENTITY.TRACE.TRACE_SOURCE.SOURCE_CHILD.CODE','CHILD_CODE');
		$query->addSelect('TRACE_ENTITY.TRACE.TRACE_SOURCE.SOURCE_CHILD.TITLE','CHILD_TITLE');

		if ($parentId)
		{
			$query->where('TRACE_ENTITY.TRACE.TRACE_SOURCE.SOURCE_CHILD.PARENT_ID', $parentId);
		}

		return $query->exec();
	}

	private function getExpenses($level, $parentId = null)
	{
		$query = Tracking\Internals\SourceExpensesTable::query()
			->registerRuntimeField(new ORM\Fields\ExpressionField(
				'IMPRESSIONS_SUM',
				'SUM(%s)',
				'IMPRESSIONS'
			))
			->registerRuntimeField(new ORM\Fields\ExpressionField(
				'ACTIONS_SUM',
				'SUM(%s)',
				'ACTIONS'
			))
			->registerRuntimeField(new ORM\Fields\ExpressionField(
				'EXPENSES_SUM',
				'SUM(%s)',
				'EXPENSES'
			))
			->setSelect([
				'IMPRESSIONS_SUM',
				'ACTIONS_SUM',
				'EXPENSES_SUM',
				'CURRENCY_ID',
			])
			->where('SOURCE_ID', $this->sourceId)
			->where('DATE_STAT', '>=', $this->dateFrom)
			->where('DATE_STAT', '<=', $this->dateTo)
			->where('TYPE_ID', Tracking\Internals\SourceExpensesTable::TYPE_AD)
			->where('SOURCE_CHILD.LEVEL', Tracking\Source\Level\Type::Keyword)
			->where('LEVEL', $level)
		;

		switch ($level)
		{
			case Tracking\Source\Level\Type::Keyword:
				$prefix = 'SOURCE_CHILD';
				break;
			case Tracking\Source\Level\Type::Adgroup:
				$prefix = 'SOURCE_CHILD.PARENT';
				break;
			case Tracking\Source\Level\Type::Campaign:
				$prefix = 'SOURCE_CHILD.PARENT.PARENT';
				$parentId = null;
				break;
			default:
				throw new SystemException("Wrong level `{$level}`.");
		}

		if ($parentId)
		{
			$query->where("{$prefix}.PARENT_ID", $parentId);
		}

		$query->addSelect("{$prefix}.TITLE", 'TITLE');
		$query->addSelect("{$prefix}.CODE", 'CODE');
		$query->addSelect("{$prefix}.LEVEL", 'LEVEL');
		$query->addSelect("{$prefix}.ID", 'CHILD_ID');
		$query->addSelect("{$prefix}.PARENT_ID", 'PARENT_ID');
		$query->addSelect("{$prefix}.IS_ENABLED", 'IS_ENABLED');

		return $query->exec();
	}
}