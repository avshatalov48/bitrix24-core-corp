<?php

namespace Bitrix\Tasks\Internals\Counter\Event;

use Bitrix\Tasks\Integration\Socialnetwork\SpaceService;

class EgressEventProcessor
{
	public function process(): void
	{
		$sonetSpaceService = new SpaceService();

		foreach (EventCollection::getInstance()->list() as $event)
		{
			/* @var Event $event */
			// send events to the external services, modules
			$sonetSpaceService->addEvent($event->getType(), $event->getData());
		}
	}
}