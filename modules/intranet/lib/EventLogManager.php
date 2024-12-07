<?php

namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Bitrix24\Component\EventList;

class EventLogManager
{
	private static EventLogManager $instance;
	private ?array $eventLogGetAuditHandlers = null;
	private ?array $availableEventTypes = null;


	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function getEventLogGetAuditHandlers(): array
	{
		if ($this->eventLogGetAuditHandlers === null)
		{
			foreach(GetModuleEvents('main', 'OnEventLogGetAuditHandlers', true) as $event)
			{
				$this->eventLogGetAuditHandlers[] = ExecuteModuleEventEx($event);
			}
		}

		return $this->eventLogGetAuditHandlers;
	}

	public function getEventTypes(): array
	{
		if ($this->availableEventTypes === null)
		{
			$eventList = [];

			if (Loader::includeModule('bitrix24'))
			{
				$eventList = EventList::prepareEventTypes();
			}
			else
			{
				foreach ($this->getEventLogGetAuditHandlers() as $eventLogGetAuditHandler)
				{
					$eventList = array_merge($eventList, $eventLogGetAuditHandler->GetAuditTypes());
				}
			}

			foreach ($eventList as $event => $name)
			{
				$eventList[$event] = preg_replace("/^\\[.*?]\\s+/", "", $name);
			}

			$this->availableEventTypes = $eventList;
		}

		return $this->availableEventTypes;
	}
}