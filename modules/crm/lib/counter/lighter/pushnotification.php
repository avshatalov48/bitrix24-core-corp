<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Kanban;
use Bitrix\Main\DI\ServiceLocator;
use CCrmOwnerType;

final class PushNotification
{
	private PullManager $pullManager;

	public function __construct()
	{
		$this->pullManager =  ServiceLocator::getInstance()->get('crm.integration.pullmanager');
	}

	public function notifyTimeline(array $activities): void
	{
		foreach ($activities as $activity)
		{
			ActivityController::getInstance()->notifyTimelinesAboutActivityUpdate($activity, \Bitrix\Crm\Timeline\ActivityController::resolveAuthorID($activity));
		}
	}

	public function notifyKanban(EntitiesInfo $entitiesInfo)
	{
		foreach ($entitiesInfo as $item)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']);
			$kanbanEntity = Kanban\Entity::getInstance($entityTypeName);

			if (!$kanbanEntity)
			{
				continue;
			}

			$ownerId = $item['OWNER_ID'];
			$categoryId = $item['CATEGORY_ID'];

			$this->pullManager->sendItemUpdatedEvent(
				[
					'id'=> $ownerId,
					'data' => []
				],
				[
					'TYPE' => $entityTypeName,
					'SKIP_CURRENT_USER' => false,
					'CATEGORY_ID' => $categoryId,
					'IGNORE_DELAY' => true,
				],
			);

		}
	}

}