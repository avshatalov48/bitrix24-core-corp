<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;

interface JournalRepositoryInterface
{
	public function append(JournalEvent $event): void;
	public function getPending(int $limit = 50): JournalEventCollection;
	public function getById(int $id): JournalEvent|null;
	public function markProcessed(JournalEventCollection $collection): void;
}
