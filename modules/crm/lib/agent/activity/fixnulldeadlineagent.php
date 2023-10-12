<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

class FixNullDeadlineAgent extends Stepper
{
	protected static $moduleId = 'crm';

	public function execute(array &$result)
	{
		$result['steps'] = (int)($result['steps'] ?? 0);

		$processedCount = $this->processActivities($result);

		return ($processedCount < $this->getLimit())
			? self::FINISH_EXECUTION
			: self::CONTINUE_EXECUTION
		;
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'FixNullDeadlineAgent', 100);
	}

	private function processActivities(array &$result): int
	{
		$processedCount = 0;

		$connection = Application::getConnection();
		$iterator = ActivityTable::query()
			->setSelect(['ID'])
			->whereNull('DEADLINE')
			->setLimit($this->getLimit())
			->setOrder(['ID' => 'asc'])
			->exec()
		;

		$maxDate = \CCrmDateTimeHelper::GetMaxDatabaseDate();

		while ($item = $iterator->fetch())
		{
			$activityId = (int)$item['ID'];
			ActivityTable::update($activityId, ['DEADLINE' => \CCrmDateTimeHelper::getMaxDatabaseDateObject()]);
			$connection->query("UPDATE b_crm_act_counter_light SET LIGHT_COUNTER_AT=$maxDate WHERE ACTIVITY_ID=$activityId");
			\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForActivity($activityId);

			$result['steps']++;
			$processedCount++;
		}

		return $processedCount;
	}
}
