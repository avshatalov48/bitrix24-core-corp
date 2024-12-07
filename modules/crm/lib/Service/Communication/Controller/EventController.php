<?php

namespace Bitrix\Crm\Service\Communication\Controller;

use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEvent;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelEventTable;
use Bitrix\Crm\Service\Communication\Result\TouchedItemIdentifier;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\EntityObject;

final class EventController
{
	use Singleton;

	/**
	 * @param ChannelEvent $channelEvent
	 * @param TouchedItemIdentifier[] $touchedItemIdentifiers
	 * @param int|null $userId
	 * @return Result
	 */
	public function register(
		ChannelEvent $channelEvent,
		array $touchedItemIdentifiers,
		?int $userId = null
	): Result
	{
		$resultItems = [];
		foreach ($touchedItemIdentifiers as $touchedItemIdentifier)
		{
			$resultItems[] = $touchedItemIdentifier->toArray();
		}


		$data = [
			'channelEvent' => $channelEvent->toArray(),
			'resultItems' => $resultItems,
		];

		return $this->save(
			$channelEvent->getChannel()->getModuleId(),
			$channelEvent->getEventId(),
			$userId,
			$data
		);
	}

	private function save(string $moduleId, string $eventId, ?int $userId, array $data): Result
	{
		$entity = $this->getEventEntityByEventId($moduleId, $eventId);
		if ($entity)
		{
			return CommunicationChannelEventTable::update(
				$entity->getId(),
				[
					'USER_ID' => $userId ?? $entity->getUserId(),
					'DATA' => $data,
				]
			);
		}

		return CommunicationChannelEventTable::add([
			'MODULE_ID' => $moduleId,
			'EVENT_ID' => $eventId,
			'USER_ID' => $userId,
			'DATA' => $data,
		]);
	}

	public function getEventEntityByEventId(string $moduleId, string $eventId): ?EntityObject
	{
		return (CommunicationChannelEventTable::getList([
			'filter' => [
				'MODULE_ID' => $moduleId,
				'EVENT_ID' => $eventId,
			],
			'limit' => 1,
		]))->fetchObject();
	}
}
