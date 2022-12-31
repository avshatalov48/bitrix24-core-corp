<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\UncompletedActivity;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

class ProcessEntityUncompletedActivitiesAgent extends Stepper
{
	protected static $moduleId = 'crm';

	public function execute(array &$result)
	{
		$result['steps'] = (int)($result['steps'] ?? 0);

		$limit = $this->getLimit();
		$lastId = ($result['lastId'] ?? 0);
		$processedCount = 0;

		$items = $this->getList($lastId, $limit);

		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$result['steps']++;
			$processedCount++;

			$bindings = $this->getUnprocessedBindings($lastId);
			UncompletedActivity::synchronizeForBindingsAndResponsibles($bindings, [
				$item['RESPONSIBLE_ID'],
				0
			]);
		}

		$result['lastId'] = $lastId;

		if ($processedCount < $limit)
		{
			$this->onStepperComplete();

			return self::FINISH_EXECUTION;
		}
		else
		{
			return self::CONTINUE_EXECUTION;
		}
	}

	protected function getLimit(): int
	{
		return (int)Option::get('crm', 'EntityUncompletedActAgentLimit', 50);
	}

	protected function onStepperComplete(): void
	{
		\COption::RemoveOption('crm', 'enable_entity_uncompleted_act');
	}

	protected function getList(int $lastId, int $limit): array
	{
		$ids = array_column(ActivityTable::query()
			->setSelect([
				'ID',
			])
			->where('ID', '>', $lastId)
			->where('COMPLETED', false)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchAll(), 'ID')
		;
		if (empty($ids))
		{
			return [];
		}

		return ActivityTable::query()
			->setSelect([
				'ID',
				'RESPONSIBLE_ID',
			])
			->whereIn('ID', $ids)
			->fetchAll()
		;
	}

	protected function getUnprocessedBindings(int $lastId): array
	{
		$bindings = \CCrmActivity::GetBindings($lastId);
		foreach ($bindings as $id => $binding)
		{
			$bindingExists = !!\Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable::query()
				->where('ENTITY_TYPE_ID', $binding['OWNER_TYPE_ID'])
				->where('ENTITY_ID', $binding['OWNER_ID'])
				->setSelect(['ID'])
				->setLimit(1)
				->fetch()
			;

			if ($bindingExists)
			{
				unset($bindings[$id]);
			}
		}

		return $bindings;
	}
}
