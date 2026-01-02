<?php

namespace Bitrix\Crm\RepeatSale\DataCollector;

use Bitrix\Crm\Activity\LastCommunication\LastCommunicationAvailabilityChecker;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\LastCommunicationTable;
use CCrmOwnerType;

final class ClientDataCollector extends BaseDataCollector
{
	protected const SUPPORTED_ENTITY_TYPES = [
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company,
	];

	public function getMarkers(array $parameters = []): array
	{
		$entityId = (int)($parameters['entityId'] ?? 0);
		if ($entityId <= 0)
		{
			return [];
		}

		$select = $this->entityTypeId === CCrmOwnerType::Contact
			? [
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_NAME,
				Item::FIELD_NAME_CREATED_TIME,
				Item::FIELD_NAME_LAST_ACTIVITY_TIME,
			]
			: [
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_TITLE,
				Item::FIELD_NAME_CREATED_TIME,
				Item::FIELD_NAME_LAST_ACTIVITY_TIME,
			]
		;

		if (LastCommunicationAvailabilityChecker::getInstance()->isEnabled())
		{
			$select = array_merge($select, [
				LastCommunicationTable::getLastStateFieldName()
			]);
		}

		/** @var Item|null $item */
		$item = $this
			->getData([
				'select' => $select,
				'filter' => [
					Item::FIELD_NAME_ID => $entityId,
				],
				'limit' => 1,
			])[0] ?? null
		;

		if (!$item)
		{
			return [];
		}

		$clientName = $this->entityTypeId === CCrmOwnerType::Contact
			? $item->getName()
			: $item->getTitle()
		;
		$createdDate = $item->getCreatedTime()
			? $item->getCreatedTime()->toString()
			: null
		;

		$lastInteractionDate = null;
		if ($item->hasField(LastCommunicationTable::getLastStateFieldName()))
		{
			$lastInteractionDate = $item->get(LastCommunicationTable::getLastStateFieldName())?->toString();
		}

		if (empty($lastInteractionDate))
		{
			$lastInteractionDate = $item->hasField(Item::FIELD_NAME_LAST_ACTIVITY_TIME)
				? $item->getLastActivityTime()->toString()
				: null
			;
		}

		return [
			'name' => $clientName,
			'created_date' => $createdDate,
			'last_interaction_date' => $lastInteractionDate,
		];
	}
}
