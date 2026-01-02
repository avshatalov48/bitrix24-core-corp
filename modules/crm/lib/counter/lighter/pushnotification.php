<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\DI\ServiceLocator;

class PushNotification
{
	use Singleton;

	private PullManager $pullManager;

	public function __construct()
	{
		$this->pullManager = ServiceLocator::getInstance()->get('crm.integration.pullmanager');
	}

	public function notifyTimeline(array $activities): void
	{
		$activityController = ActivityController::getInstance();

		foreach ($activities as $activity)
		{
			$activityController->notifyTimelinesAboutActivityUpdate(
				$activity,
				ActivityController::resolveAuthorID($activity),
			);
		}
	}

	public function notifyKanban(array $entitiesInfo): void
	{
		$queue = Container::getInstance()->getPullEventsQueue();

		foreach ($entitiesInfo as $singleEntity)
		{
			$item = ItemIdentifier::createFromArray($singleEntity);
			if ($item)
			{
				$queue->onLightCounter($item);
			}
		}
	}
}
