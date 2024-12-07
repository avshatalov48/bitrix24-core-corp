<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Enum\EventName;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class EventSenderService implements \Bitrix\HumanResources\Contract\Service\EventSenderService
{
	private EventManager $eventManager;

	public function __construct(?EventManager $eventManager = null)
	{
		$this->eventManager = $eventManager ?? EventManager::getInstance();
	}
	public function send(EventName $event, array $eventData): Event
	{
		$event = new Event(
			'humanresources',
			$event->name,
			$eventData,
		);
		$event->send();

		return $event;
	}


	/**
	 * @param string $fromModuleId
	 * @param string $event
	 *
	 * @return void
	 */
	public function removeEventHandlers(string $fromModuleId, string $event): void
	{
		$handlers = $this->eventManager->findEventHandlers(
			$fromModuleId,
			$event,
		);

		foreach ($handlers as $key => $handler)
		{
			if (isset($handler['TO_MODULE_ID']) && $handler['TO_MODULE_ID'] === 'humanresources')
			{
				$this->eventManager->removeEventHandler(
					$fromModuleId,
					$event,
					$key
				);
			}
		}

		Container::getSemaphoreService()->lock($fromModuleId. '-' .$event);
	}
}