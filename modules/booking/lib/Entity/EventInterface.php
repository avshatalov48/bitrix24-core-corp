<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity;

use Bitrix\Booking\Internals\Service\Rrule;

interface EventInterface
{
	public function isEventRecurring(): bool;
	public function getEventDatePeriod(): DatePeriod;
	public function getEventRrule(): ?Rrule;
	public function doEventsIntersect(EventInterface $event): bool;
}
