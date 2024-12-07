<?php

namespace Bitrix\Crm\Service\Integration\Sign\Kanban;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use CCrmOwnerType;

final class PullService
{
	private readonly ?Factory $b2eEntityFactory;

	public function __construct()
	{
		$this->b2eEntityFactory = Container::getInstance()->getFactory(CCrmOwnerType::SmartB2eDocument);
	}

	public function sendB2ePullUpdateEventByEntityId(int $entityId): bool
	{
		if ($entityId < 1)
		{
			return false;
		}

		$item = $this->b2eEntityFactory?->getItem($entityId);

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

		return PullManager::getInstance()->sendItemUpdatedEvent($pullItem, $params);
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
