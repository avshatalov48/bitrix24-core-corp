<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body;

use Bitrix\Main\Type\DateTime;

class CalendarLogo extends Logo
{
	protected DateTime $date;

	public function __construct(DateTime $dateTime)
	{
		$this->iconCode = 'calendar';
		$this->date = clone $dateTime;
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

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'timestamp' => $this->getDate()->getTimestamp(),
			]
		);
	}
}
