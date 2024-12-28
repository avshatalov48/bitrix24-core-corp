<?php

namespace Bitrix\Crm\Service\Integration\Sign\Kanban;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use CCrmOwnerType;

final class PullService
{
	private const UPDATE_EVENT = 'UPDATE';
	private const DELETE_EVENT = 'DELETE';

	private readonly ?Factory $b2eEntityFactory;

	public function __construct()
	{
		$this->b2eEntityFactory = Container::getInstance()->getFactory(CCrmOwnerType::SmartB2eDocument);
	}

	public function sendB2ePullUpdateEventByEntityId(int $entityId): bool
	{
		return $this->sendB2ePullEvent($entityId, self::UPDATE_EVENT);
	}

	public function sendB2ePullDeleteEventByEntityId(int $entityId): bool
	{
		return $this->sendB2ePullEvent($entityId, self::DELETE_EVENT);
	}

	private function sendB2ePullEvent(int $entityId, string $event): bool
	{
		$item = $this->getItem($entityId);

		if ($item === null)
		{
			return false;
		}

		$data = $item->getCompatibleData();
		$entityName = CCrmOwnerType::ResolveName($item->getEntityTypeId());
		$pullItem = Entity::getInstance($entityName)?->createPullItem($data);

		if ($pullItem === null)
		{
			return false;
		}

		$params = $this->getParams($entityName, $item->getCategoryId());

		return match ($event)
		{
			self::DELETE_EVENT => PullManager::getInstance()->sendItemDeletedEvent($pullItem, $params),
			default => PullManager::getInstance()->sendItemUpdatedEvent($pullItem, $params),
		};
	}

	private function getItem(int $entityId): ?\Bitrix\Crm\Item
	{
		if ($entityId < 1)
		{
			return null;
		}

		return $this->b2eEntityFactory?->getItem($entityId);
	}

	private function getParams(string $entityName, ?int $categoryId = null): array
	{
		$result = [
			'TYPE' => $entityName,
			'SKIP_CURRENT_USER' => false,
			'EVENT_ID' => Container::getInstance()->getContext()->getEventId(),
		];
		if ($categoryId !== null)
		{
			$result['CATEGORY_ID'] = $categoryId;
		}

		return $result;
	}
}
