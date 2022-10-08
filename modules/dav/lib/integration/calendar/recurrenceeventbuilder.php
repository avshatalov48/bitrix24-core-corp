<?php

namespace Bitrix\Dav\Integration\Calendar;

class RecurrenceEventBuilder
{
	/** @var \CDavICalendarComponent  */
	private $component;

	public function __construct(array $events)
	{
		if (!$events)
		{
			return null;
		}
		$this->component = new \CDavICalendarComponent();

		$this->component->SetType('VCALENDAR');

		$this->component->SetProperties([
            new \CDavICalendarProperty('VERSION:2.0'),
            new \CDavICalendarProperty('PRODID:-//davical.org//NONSGML AWL Calendar//EN'),
            new \CDavICalendarProperty('METHOD:PUBLISH')
        ]);
		$result = [];
		$timezone = \CDavICalendarTimeZone::GetTimezone(\CDavICalendarTimeZone::getTimeZoneId());
		if ($timezone)
		{
			$timezoneComponent = new \CDavICalendarComponent();
			$timezoneComponent->InitializeFromString($timezone);
			$result[] = $timezoneComponent;
		}

		foreach ($events as $event)
		{
			$eventComponent = new \CDavICalendarComponent();
			$eventComponent->InitializeFromArray($event);
			$result[] = $eventComponent;
		}

		$this->component->SetComponents($result);
	}

	public function Render(): string
	{
		return trim($this->component->Render());
	}
}