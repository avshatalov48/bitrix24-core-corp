<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal;

interface JournalServiceInterface
{
	public function append(JournalEvent $event): void;
}
