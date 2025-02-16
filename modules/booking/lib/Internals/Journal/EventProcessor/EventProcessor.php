<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal\EventProcessor;

use Bitrix\Booking\Internals\Journal\JournalEventCollection;

interface EventProcessor
{
	public function process(JournalEventCollection $eventCollection): void;
}
