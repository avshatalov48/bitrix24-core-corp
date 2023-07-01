<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\UncompletedActivity;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;

/**
 * This agent finds entries in the EntityUncompletedActivityTable that refer to completed tasks
 * and recalculates them based on the entity and owner.
 */
class FixCompletedUncompletedActivityAgent extends AgentBase
{
	private const BATCH_SIZE = 10;

	private const LAST_ID_OPTION_NAME = 'uncompleted_activity_fix_completed_last_id';

	private const AGENT_DONE = false;

	private const AGENT_CONTINUE = true;


	public static function doRun(): bool
	{
		$uncompletedActivities = self::queryUncompletedActivitiesToFix(self::getLastId());

		if (empty($uncompletedActivities))
		{
			self::cleanOptions();
			return self::AGENT_DONE;
		}

		foreach ($uncompletedActivities as $activity)
		{
			$itemIdentifier = new ItemIdentifier($activity['ENTITY_TYPE_ID'], $activity['ENTITY_ID']);
			$uncompletedActivity = new UncompletedActivity($itemIdentifier, $activity['RESPONSIBLE_ID']);
			$uncompletedActivity->synchronize();
		}

		return self::AGENT_CONTINUE;
	}

	/**
	 * @param int $lastId
	 * @return array
	 */
	private static function queryUncompletedActivitiesToFix(int $lastId): array
	{
		$query = EntityUncompletedActivityTable::query()
			->setSelect(['ID', 'ENTITY_TYPE_ID', 'ENTITY_ID', 'RESPONSIBLE_ID'])
			->registerRuntimeField(
				'',
				new ReferenceField('A',
					ActivityTable::getEntity(),
					['=ref.ID' => 'this.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				),
			)
			->where('ID', '>', $lastId)
			->where('A.COMPLETED', true)
			->setOrder(['ID' => 'ASC'])
			->setLimit(self::BATCH_SIZE);

		return $query->fetchAll();
	}

	private static function getLastId(): int
	{
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, -1);
	}

	private static function cleanOptions()
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
	}

}