<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Update\Stepper;

class DeleteUnactualUncompletedActivitiesAgent extends Stepper
{
	protected static $moduleId = 'crm';

	public function execute(array &$result)
	{
		if (Option::get('crm', 'enable_entity_countable_act', 'Y') !== 'Y')
		{
			return self::CONTINUE_EXECUTION; // wait ProcessEntityCountableActivitiesAgent to finish
		}
		if (Option::get('crm', 'enable_any_incoming_act', 'Y') !== 'Y')
		{
			return self::CONTINUE_EXECUTION; // wait SynchronizeUncompletedActivityDataAgent to finish
		}

		$result['steps'] = (int)($result['steps'] ?? 0);

		$limit = $this->getLimit();
		$processedCount = 0;

		$ids = array_column(
			EntityUncompletedActivityTable::query()
				->setSelect([
					'ID'
				])
				->registerRuntimeField(
					'',
					new ReferenceField('A',
						ActivityTable::getEntity(),
						(new ConditionTree())
							->whereColumn('ref.ID', 'this.ACTIVITY_ID')
						,
						['join_type' => Join::TYPE_LEFT]
					)
				)
				->setLimit($limit)
				->whereNull('A.ID')
				->setOrder(['ID' => 'ASC'])
				->fetchAll(),
			'ID'
		);
		if (empty($ids))
		{
			return self::FINISH_EXECUTION;
		}
		foreach ($ids as $id)
		{
			EntityUncompletedActivityTable::delete($id);
			$result['steps']++;
			$processedCount++;
		}

		if ($processedCount < $limit)
		{
			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'DeleteUnactualUncompletedActivitiesAgent', 10);
	}
}
