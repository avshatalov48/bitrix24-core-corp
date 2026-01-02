<?php

namespace Bitrix\Crm\Activity\LastCommunication;

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Activity\Provider\Email;
use Bitrix\Crm\Activity\Provider\OpenLine;
use Bitrix\Crm\Activity\Provider\WebForm;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\EO_LastCommunication_Collection;
use Bitrix\Crm\Model\LastCommunicationTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

class SyncLastCommunication
{
	private array $acceptedProviders;

	private static array $activitiesById = [];

	public function __construct()
	{
		$this->acceptedProviders = [
			Call::getId() => LastCommunicationTable::ENUM_CALL_TIME,
			OpenLine::getId() => LastCommunicationTable::ENUM_IM_OPEN_LINES_TIME,
			WebForm::getId() => LastCommunicationTable::ENUM_WEB_FORM_TIME,
			Email::getId() => LastCommunicationTable::ENUM_EMAIL_TIME,
		];
	}

	public function getAllowedProviders(): array
	{
		return array_keys($this->acceptedProviders);
	}

	private function isAllowedProviders(string $providerId): bool
	{
		return array_key_exists($providerId, $this->acceptedProviders);
	}

	public function upsertLastCommunication(
		int $activityId,
		string $providerId,
		DateTime $lastUpdated = new DateTime(),
		bool $forceUpdate = false,
	): void
	{
		if (!$this->isAllowedProviders($providerId))
		{
			return;
		}

		$activityBindings = ActivityBindingTable::getList([
			'filter' => [
				'ACTIVITY_ID' => $activityId,
			],
		]);

		while ($binding = $activityBindings->fetch())
		{
			$itemIdentifier = ItemIdentifier::createFromArray($binding);
			if (!$itemIdentifier)
			{
				continue;
			}

			$type = $this->getTypeByProvider($providerId);

			//update by some type
			LastCommunicationTable::upsertLastCommunicationData($itemIdentifier, $lastUpdated, $type, $activityId, $forceUpdate);

			if ($type === LastCommunicationTable::ENUM_LAST_TIME)
			{
				continue;
			}

			//update for last communication
			LastCommunicationTable::upsertLastCommunicationData(
				$itemIdentifier,
				$lastUpdated,
				LastCommunicationTable::ENUM_LAST_TIME,
				$activityId,
				$forceUpdate,
			);
		}
	}

	private function getTypeByProvider(string $providerId): string
	{
		return $this->acceptedProviders[$providerId] ?? LastCommunicationTable::ENUM_LAST_TIME;
	}

	public function onActivityAddIfSuitable(int $activityId): void
	{
		$activity = $this->getActivity($activityId);

		if (!$activity)
		{
			return;
		}

		$this->upsertLastCommunication((int)$activity['ID'], $activity['PROVIDER_ID'], $activity['CREATED']);
	}

	public function onActivityRemoveIfSuitable(ItemIdentifier $itemIdentifier, int $activityId): void
	{
		$communications = LastCommunicationTable::query()
			->setSelect(['ID', 'TYPE'])
			->where('ENTITY_TYPE_ID', $itemIdentifier->getEntityTypeId())
			->where('ENTITY_ID', $itemIdentifier->getEntityId())
			->where('ACTIVITY_ID', $activityId)
			->fetchCollection()
		;

		if ($communications->count() < 1 || $communications->count() > 2)
		{
			return;
		}

		LastCommunicationTable::deleteByItemIdentifierAndActivityId($itemIdentifier, $activityId);

		$activityIds = array_column(ActivityBindingTable::query()
			->setSelect(['ACTIVITY_ID'])
			->where('OWNER_TYPE_ID', $itemIdentifier->getEntityTypeId())
			->where('OWNER_ID', $itemIdentifier->getEntityId())
			->fetchAll(), 'ACTIVITY_ID');

		if (empty($activityIds))
		{
			return;
		}

		$providerId = $this->getProviderIdFromTypes($communications);

		if (!$providerId)
		{
			return;
		}

		$this->updateLastTimeForProvider($itemIdentifier, $providerId, $activityIds);

		$this->updateLastTimeByStoredData($itemIdentifier);
	}

	public function onEntityDelete(ItemIdentifier $itemIdentifier): void
	{
		LastCommunicationTable::deleteByEntity($itemIdentifier);
	}

	private function getActivity(int $activityId): ?array
	{
		if (isset(self::$activitiesById[$activityId]))
		{
			return self::$activitiesById[$activityId];
		}

		$activity = ActivityTable::query()
			->setSelect(['ID', 'PROVIDER_ID', 'CREATED'])
			->where('ID', '=', $activityId)
			->fetch()
		;

		if (!$activity || !$activity['PROVIDER_ID'] || !$this->isAllowedProviders($activity['PROVIDER_ID']))
		{
			return null;
		}

		self::$activitiesById[$activityId] = $activity;

		return $activity;
	}

	private function getProviderIdFromTypes(EO_LastCommunication_Collection $communications): ?string
	{
		$type = null;
		foreach ($communications as $item)
		{
			if ($item->getType() !== LastCommunicationTable::ENUM_LAST_TIME)
			{
				$type = $item->getType();

				break;
			}
		}

		return $type ? array_search($type, $this->acceptedProviders, true) : null;
	}

	public function updateLastTimeForProvider(ItemIdentifier $itemIdentifier, string $providerId, array $activityIds): void
	{
		$lastActivity = ActivityTable::query()
			->setSelect(['ID', 'CREATED'])
			->where('PROVIDER_ID', $providerId)
			->whereIn('ID', $activityIds)
			->setLimit(1)
			->setOrder(['ID' => 'desc'])
			->fetch()
		;

		if (!$lastActivity)
		{
			return;
		}

		LastCommunicationTable::upsertLastCommunicationData(
			$itemIdentifier,
			$lastActivity['CREATED'],
			$this->acceptedProviders[$providerId],
			$lastActivity['ID'],
			true,
		);
	}

	public function updateLastTimeByStoredData(ItemIdentifier $itemIdentifier): void
	{
		$lastCommunicationByTime = LastCommunicationTable::query()
			->setSelect(['LAST_COMMUNICATION_TIME', 'ACTIVITY_ID'])
			->addSelect(Query::expr()->max('LAST_COMMUNICATION_TIME'), 'MAX_LAST_COMMUNICATION_TIME')
			->where('ENTITY_TYPE_ID', $itemIdentifier->getEntityTypeId())
			->where('ENTITY_ID', $itemIdentifier->getEntityId())
			->whereIN('TYPE', [
				LastCommunicationTable::ENUM_CALL_TIME,
				LastCommunicationTable::ENUM_EMAIL_TIME,
				LastCommunicationTable::ENUM_IM_OPEN_LINES_TIME,
				LastCommunicationTable::ENUM_WEB_FORM_TIME,
			])
			->setLimit(1)
			->setGroup('ACTIVITY_ID') //postgresql aggregation needs
			->fetch()
		;

		if (!$lastCommunicationByTime)
		{
			return;
		}

		LastCommunicationTable::upsertLastCommunicationData(
			$itemIdentifier,
			$lastCommunicationByTime['LAST_COMMUNICATION_TIME'],
			LastCommunicationTable::ENUM_LAST_TIME,
			$lastCommunicationByTime['ACTIVITY_ID'],
			true,
		);
	}
}
