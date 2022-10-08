<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\ItemIdentifier;

class UncompletedActivity
{
	private ItemIdentifier $itemIdentifier;
	private int $responsibleId;

	public static function synchronizeForBindingsAndResponsibles(array $bindings, array $responsibleIds): void
	{
		$responsibleIds = array_unique($responsibleIds);

		$processedBindingsMap = [];
		foreach ($bindings as $binding)
		{
			if (!\CCrmOwnerType::isCorrectEntityTypeId($binding['OWNER_TYPE_ID']))
			{
				continue;
			}
			if ($binding['OWNER_ID'] <= 0)
			{
				continue;
			}
			if (isset($processedBindingsMap[$binding['OWNER_TYPE_ID']][$binding['OWNER_ID']]))
			{
				continue;
			}
			$processedBindingsMap[$binding['OWNER_TYPE_ID']][$binding['OWNER_ID']] = true;

			foreach ($responsibleIds as $responsibleId)
			{
				$instance = new self(new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']), (int)$responsibleId);
				$instance->synchronize();
			}
		}
	}

	public static function synchronizeForActivity(int $activityId, ?array $bindings = null): void
	{
		$activity = \CCrmActivity::GetList(
			[],
			['=ID' => $activityId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			['nTopCount' => 1],
			['RESPONSIBLE_ID', 'COMPLETED']
		)->Fetch();

		if ($activity && $activity['COMPLETED'] === 'N')
		{
			if (is_null($bindings))
			{
				$bindings = \CCrmActivity::GetBindings($activityId);
			}
			self::synchronizeForBindingsAndResponsibles(
				$bindings,
				[
					0,
					(int)$activity['RESPONSIBLE_ID']
				]
			);
		}
	}

	public function __construct(ItemIdentifier $itemIdentifier, int $responsibleId)
	{
		$this->itemIdentifier = $itemIdentifier;
		$this->responsibleId = $responsibleId;
	}

	public function synchronize(): void
	{
		$uncompletedActivity = $this->getUncompletedActivity();

		if ($uncompletedActivity)
		{
			$activityId = (int)$uncompletedActivity['ID'];
			$deadline = $uncompletedActivity['DEADLINE'] ?? \CCrmDateTimeHelper::GetMaxDatabaseDate(false);
			$isIncomingChannel = false;
			if (\CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
			{
				// if minimal uncompleted activity has \CCrmDateTimeHelper::GetMaxDatabaseDate() deadline,
				// try to find IS_INCOMING_CHANNEL:
				$incomingChannelFilter = $this->prepareUncompletedActivityFilter();
				$incomingChannelFilter['__JOINS'] = [
					[
						'SQL' => 'INNER JOIN b_crm_act_incoming_channel ACTINC ON ACTINC.ACTIVITY_ID = A.ID',
						'TYPE' => 'INNER',
					]
				];
				$incomingChannelActivity = \CCrmActivity::GetList(
					[
						'ID' => 'ASC',
					],
					$incomingChannelFilter,
					false,
					['nTopCount' => 1],
					[
						'ID',
					]
				)->Fetch();

				if ($incomingChannelActivity)
				{
					$activityId = (int)$incomingChannelActivity['ID'];
					$isIncomingChannel = true;
				}
			}

			$this->update(
				$activityId,
				$deadline,
				$isIncomingChannel
			);
		}
		else
		{
			// there are no uncompleted activities
			$existedRecordId = $this->getExistedRecordId();
			if ($existedRecordId)
			{
				EntityUncompletedActivityTable::delete($existedRecordId);
			}
		}
	}

	private function getUncompletedActivity(): ?array
	{
		$filter = $this->prepareUncompletedActivityFilter();

		$explicitDeadlineFilter = $filter;
		$explicitDeadlineFilter['!=DEADLINE'] = null;

		$firstUncompletedActivityWithExplicitDeadline = \CCrmActivity::GetList(
			[
				'DEADLINE' => 'ASC',
			],
			$explicitDeadlineFilter,
			false,
			['nTopCount' => 1],
			[
				'ID',
				'DEADLINE'
			]
		)->Fetch();

		if ($firstUncompletedActivityWithExplicitDeadline)
		{
			return $firstUncompletedActivityWithExplicitDeadline;
		}

		/*
		 * at this point there are either activities with DEADLINE === null or no activities at all.
		 * if an activity has DEADLINE === null, it's essentially means that its deadline is \CCrmDateTimeHelper::GetMaxDatabaseDate().
		 * so simply fetch the most recent activity, if it exists. DEADLINE for all of them is going be the same.
		 */
		$lastUncompletedActivity = \CCrmActivity::GetList(
			[
				'CREATED' => 'DESC',
			],
			$filter,
			false,
			['nTopCount' => 1],
			[
				'ID',
				'DEADLINE'
			]
		)->Fetch();

		return $lastUncompletedActivity ? $lastUncompletedActivity : null;
	}

	private function prepareUncompletedActivityFilter(): array
	{
		$filter = [
			'BINDINGS' => [
				[
					'OWNER_TYPE_ID' => $this->itemIdentifier->getEntityTypeId(),
					'OWNER_ID' => $this->itemIdentifier->getEntityId(),
				],
			],
			'CHECK_PERMISSIONS' => 'N',
			'COMPLETED' => 'N',
		];
		if ($this->responsibleId > 0)
		{
			$filter['RESPONSIBLE_ID'] = $this->responsibleId;
		}

		return $filter;
	}

	public function update(int $activityId, string $minDeadline, bool $isIncomingChannel)
	{
		global $DB;

		$fields = [
			'ACTIVITY_ID' => $activityId,
			'MIN_DEADLINE' => $minDeadline,
			'IS_INCOMING_CHANNEL' => $isIncomingChannel ? 'Y' : 'N',
		];
		$existedRecordId = $this->getExistedRecordId();
		if ($existedRecordId)
		{
			\CTimeZone::Disable();
			// ORM cannot be used because it does not support \CCrmDateTimeHelper::GetMaxDatabaseDate() datetime value hack
			$DB->Query(sprintf(
				"UPDATE %s SET %s WHERE ID = %d",
				EntityUncompletedActivityTable::getTableName(),
				$DB->PrepareUpdate(EntityUncompletedActivityTable::getTableName(), $fields),
				$existedRecordId
			));
			\CTimeZone::Enable();
		}
		else
		{
			$fields['ENTITY_TYPE_ID'] = $this->itemIdentifier->getEntityTypeId();
			$fields['ENTITY_ID'] = $this->itemIdentifier->getEntityId();
			$fields['RESPONSIBLE_ID'] =$this->responsibleId;
			$DB->Add(EntityUncompletedActivityTable::getTableName(), $fields);
		}
	}

	public static function unregister(ItemIdentifier $itemIdentifier): void
	{
		EntityUncompletedActivityTable::deleteByItemIdentifier($itemIdentifier);
	}

	private function getExistedRecordId(): ?int
	{
		$existedRecord = EntityUncompletedActivityTable::query()
			->where('ENTITY_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
			->where('ENTITY_ID', $this->itemIdentifier->getEntityId())
			->where('RESPONSIBLE_ID', $this->responsibleId)
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()
		;

		return $existedRecord['ID'] ? (int)$existedRecord['ID'] : null;
	}
}
