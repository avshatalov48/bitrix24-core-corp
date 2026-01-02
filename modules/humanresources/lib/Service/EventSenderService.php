<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract\Enum\EventName;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class EventSenderService implements \Bitrix\HumanResources\Contract\Service\EventSenderService
{
	public const MODULE_NAME = 'humanresources';
	private EventManager $eventManager;

	/**
	 * @var Event[] $eventQueue
	 */
	private array $eventQueue = [];

	public function __construct(?EventManager $eventManager = null)
	{
		$this->eventManager = $eventManager ?? EventManager::getInstance();
	}

	public function send(EventName $event, array $eventData): Event
	{
		$event = new Event(
			self::MODULE_NAME,
			$event->name,
			$eventData,
		);

		try
		{
			$event->send();

			return $event;
		}
		catch (\Throwable $t)
		{
			AddMessage2Log(
				'EventSenderService::send: '
				. $t->getMessage()
				. '. Trace as string: ' . $t->getTraceAsString(),
				'humanresources',
			);

			return $event;
		}
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
			if (isset($handler['TO_MODULE_ID']) && $handler['TO_MODULE_ID'] === self::MODULE_NAME)
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

	/**
	 * @inheritdoc
	 */
	public function queueEvent(EventName $event, array $eventData): Event
	{
		$event = new Event(
			self::MODULE_NAME,
			$event->name,
			$eventData,
		);
		$this->eventQueue[] = $event;

		return $event;
	}

	/**
	 * @inheritdoc
	 */
	public function sendQueued(): void
	{
		foreach ($this->eventQueue as $event)
		{
			$event->send();
		}
		$this->clearQueue();
	}

	/**
	 * @inheritdoc
	 */
	public function clearQueue(): void
	{
		$this->eventQueue = [];
	}
}