<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal;

interface JournalServiceInterface
{
	public function append(JournalEvent $event): void;
}
