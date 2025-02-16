<?php

namespace Bitrix\Booking\Entity;

use Bitrix\Booking\Internals\EventIntersection\DatePeriodWithDatePeriod;
use Bitrix\Booking\Internals\EventIntersection\RruleWithDatePeriod;
use Bitrix\Booking\Internals\EventIntersection\RruleWithRrule;
use Bitrix\Booking\Internals\Rrule;

trait EventTrait
{
	abstract public function isEventRecurring(): bool;
	abstract public function getEventDatePeriod(): DatePeriod;
	abstract public function getEventRrule(): ?Rrule;

	public function doEventsIntersect(EventInterface $event): bool
	{
		if (!$this->isEventRecurring() && !$event->isEventRecurring())
		{
			return (new DatePeriodWithDatePeriod())->doIntersect(
				$this->getEventDatePeriod(),
				$event->getEventDatePeriod()
			);
		}

		if ($this->isEventRecurring() && $event->isEventRecurring())
		{
			return (new RruleWithRrule())->doIntersect(
				$this->getEventRrule(),
				$event->getEventRrule()
			);
		}

		if ($this->isEventRecurring())
		{
			$rrule = $this->getEventRrule();
			$datePeriod = $event->getEventDatePeriod();
		}
		else
		{
			$rrule = $event->getEventRrule();
			$datePeriod = $this->getEventDatePeriod();
		}

		return (new RruleWithDatePeriod())->doIntersect($rrule, $datePeriod);
	}
}
