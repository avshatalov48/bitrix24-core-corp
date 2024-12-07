<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body;

use Bitrix\Main\Type\DateTime;

class CalendarLogo extends Logo
{
	protected DateTime $date;
	protected ?int $calendarEventId;
	protected ?string $backgroundColor = null;

	public function __construct(DateTime $dateTime, ?int $calendarEventId = null)
	{
		$this->iconCode = 'calendar';
		$this->date = clone $dateTime;
		$this->calendarEventId = $calendarEventId;
	}

	public function getDate(): DateTime
	{
		return $this->date;
	}

	public function setDate($date): self
	{
		$this->date = $date;

		return $this;
	}

	public function getCalendarEventId(): ?int
	{
		return $this->calendarEventId;
	}

	public function setCalendarEventId(int $calendarEventId): self
	{
		$this->calendarEventId = $calendarEventId;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'timestamp' => $this->getDate()->getTimestamp(),
				'calendarEventId' => $this->getCalendarEventId(),
				'backgroundColor' => $this->getBackgroundColor(),
			]
		);
	}

	public function getBackgroundColor(): ?string
	{
		return $this->backgroundColor;
	}

	public function setBackgroundColor(?string $backgroundColor): CalendarLogo
	{
		$this->backgroundColor = $backgroundColor;

		return $this;
	}
}
