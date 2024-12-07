<?php

namespace Bitrix\Crm\Activity\UncompletedActivity;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\DB\SqlQueryException;

class UncompletedActivityRepo
{
	private bool $existedRecordLoaded = false;
	private ?array $existedRecord = null;

	public function __construct(
		private ItemIdentifier $itemIdentifier,
		private int $responsibleId
	)
	{
	}

	private function loadExistedRecord(): ?array
	{
		$existedRecord = EntityUncompletedActivityTable::query()
			->where('ENTITY_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
			->where('ENTITY_ID', $this->itemIdentifier->getEntityId())
			->where('RESPONSIBLE_ID', $this->responsibleId)
			->setSelect(['ID', 'MIN_DEADLINE', 'IS_INCOMING_CHANNEL', 'HAS_ANY_INCOMING_CHANEL', 'MIN_LIGHT_COUNTER_AT'])
			->setLimit(1)
			->fetch()
		;

		return is_array($existedRecord) ? $existedRecord : null;
	}

	public function getExistedRecordId(): ?int
	{
		$existedRecord = $this->getExistedRecord();

		return (isset($existedRecord['ID']) && $existedRecord['ID']) ? (int)$existedRecord['ID'] : null;
	}

	public function getExistedRecord(): ?array
	{
		if ($this->existedRecordLoaded)
		{
			return $this->existedRecord;
		}
		$this->existedRecordLoaded = true;
		$existedRecord = $this->loadExistedRecord();
		$this->existedRecord = is_array($existedRecord) ? $existedRecord : null;

		return $this->existedRecord;
	}

	public function upsert(UpsertDto $dto): void
	{
		$fields = [
			'ACTIVITY_ID' => $dto->activityId(),
			'MIN_DEADLINE' => $dto->minDeadline(),
			'IS_INCOMING_CHANNEL' => $dto->isIncomingChannel() ? 'Y' : 'N',
			'HAS_ANY_INCOMING_CHANEL' => $dto->hasAnyIncomingChannel() ? 'Y' : 'N',
			'MIN_LIGHT_COUNTER_AT' => $dto->minLightTime()
		];
		$existedRecord = $this->loadExistedRecord();

		if ($existedRecord)
		{
			try
			{
				EntityUncompletedActivityTable::update((int)$existedRecord['ID'], $fields);
			}
			catch (SqlQueryException $e)
			{
				if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
				{
					$existedRecords = EntityUncompletedActivityTable::query()
						->where('ENTITY_TYPE_ID', $dto->itemIdentifier()->getEntityTypeId())
						->where('ENTITY_ID', $dto->itemIdentifier()->getEntityId())
						->where('RESPONSIBLE_ID', $dto->responsibleId())
						->setLimit(10)
						->setSelect(['ID'])
						->fetchAll()
					;

					$existedRecordsIds = array_column($existedRecords, 'ID');

					if (empty($existedRecordsIds))
					{
						throw $e;
					}

					$lastId = array_pop($existedRecordsIds);

					if (count($existedRecordsIds) > 0)
					{
						EntityUncompletedActivityTable::deleteByIds($existedRecordsIds);
					}

					EntityUncompletedActivityTable::update((int)$lastId, $fields);
				}
			}
		}
		else
		{
			$addFields = $fields;
			$addFields['ENTITY_TYPE_ID'] = $dto->itemIdentifier()->getEntityTypeId();
			$addFields['ENTITY_ID'] = $dto->itemIdentifier()->getEntityId();
			$addFields['RESPONSIBLE_ID'] = $dto->responsibleId();
			$addFields['MIN_LIGHT_COUNTER_AT'] = $dto->minLightTime();

			try
			{
				EntityUncompletedActivityTable::add($addFields);
			}
			catch (SqlQueryException $e)
			{
				if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
				{
					$existedRecord = $this->loadExistedRecord();
					if (!$existedRecord)
					{
						throw $e;
					}
					EntityUncompletedActivityTable::update($existedRecord['ID'], $fields);
				}
			}
		}
	}

	public function getUncompletedIncomingActivityId(): ?int
	{
		$query = IncomingChannelTable::query()
			->setSelect(['ACTIVITY_ID'])
			->where('BINDINGS.OWNER_TYPE_ID', $this->itemIdentifier->getEntityTypeId())
			->where('BINDINGS.OWNER_ID', $this->itemIdentifier->getEntityId())
			->where('COMPLETED', false)
			->setLimit(1)
		;
		if ($this->responsibleId > 0)
		{
			$query->where('RESPONSIBLE_ID', $this->responsibleId);
		}
		$activity = $query->fetch();

		return $activity ? (int)$activity['ACTIVITY_ID'] : null;
	}


}