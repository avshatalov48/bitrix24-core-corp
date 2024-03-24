<?php

namespace Bitrix\Crm\Kanban\Queries;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Pseudoactivity\WaitEntry;
use Bitrix\Crm\Traits;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;

class QueryEntityActivityCounter
{
	use Traits\Singleton;

	private ActCounterLightTimeRepo $lightCounterRepo;

	public function __construct()
	{
		$this->lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
	}

	/**
	 * @param int $entityTypeId
	 * @param array $entityIds
	 * @return array{
	 *     ID: int,
	 *     COMPLETED: string,
	 *     BIND_OWNER_ID: int,
	 *     BIND_OWNER_TYPE_ID: int,
	 *     RESPONSIBLE_ID: int,
	 *     DEADLINE: string,
	 *     LIGHT_COUNTER_AT: string
	 * }
	 */
	public function queryActivities(int $entityTypeId, array $entityIds): array
	{
		return $this->lightCounterRepo
			->activitiesWithLightTimeByEntityIds($entityTypeId, $entityIds);
	}

	/**
	 * @param int $entityTypeId
	 * @param array $entityIds
	 * @param array $activitiesIds
	 * @return array{
	 *     ACTIVITY_ID: int,
	 *     OWNER_ID: int
	 * }
	 */
	public function queryIncomingActivities(int $entityTypeId, array $entityIds, array $activitiesIds): array
	{
		return IncomingChannelTable::getList([
			'select' => [
				'ACTIVITY_ID',
				'OWNER_ID' => 'BINDING.OWNER_ID',
			],
			'filter' => [
				'BINDING.OWNER_TYPE_ID' => $entityTypeId,
				'@BINDING.OWNER_ID' => $entityIds,
				'@ACTIVITY_ID' => $activitiesIds,
				'=COMPLETED' => 'N',
			],
			'runtime' => [
				new ReferenceField(
					'BINDING',
					ActivityBindingTable::class,
					Join::on('this.ACTIVITY_ID', 'ref.ACTIVITY_ID'),
					['join_type' => 'INNER']
				)
			]
		])->fetchAll();
	}

	/**
	 * @param int $entityTypeId
	 * @param array $entityIds
	 * @param int|null $limit
	 * @return array{
	 *     ACTIVITY_ID: int,
	 *     OWNER_ID: int
	 * }
	 */
	public function queryBindings(int $entityTypeId, array $entityIds, ?int $limit = null): array
	{
		return ActivityBindingTable::getList([
			'select' => [
				'ACTIVITY_ID',
				'OWNER_ID',
			],
			'filter' => [
				'OWNER_ID' => $entityIds,
				'OWNER_TYPE_ID' => $entityTypeId,
			],
			'limit' => $limit,
		])->fetchAll();
	}

	/**
	 * @param int $entityTypeId
	 * @param array $entityIds
	 * @return array{
	 *     OWNER_ID: int,
	 *     ID: int max ID from b_crm_wait related with OWNER_ID
	 * }
	 */
	public function queryWaits(int $entityTypeId, array $entityIds): array
	{
		return WaitEntry::getRecentIDsByOwner($entityTypeId, $entityIds);
	}

}