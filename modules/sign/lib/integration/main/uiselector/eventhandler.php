<?php

namespace Bitrix\Sign\Integration\Main\UiSelector;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class EventHandler
{
	public static function OnUISelectorGetProviderByEntityType(Event $event): EventResult
	{
		$entityType = $event->getParameter('entityType');

		return match($entityType)
		{
			SignGroupEntityProvider::ENTITY_TYPE => new EventResult(
				EventResult::SUCCESS,
				[
					'result' => new \Bitrix\Sign\Integration\Main\UISelector\SignGroupEntityProvider(),
				],
				'sign',
			),
			default => new EventResult(EventResult::UNDEFINED, null, 'sign')
		};
	}
}