<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Journal;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Internals\Service\Journal\JournalEvent|null getFirstCollectionItem()
 * @method JournalEvent[] getIterator()
 */
class JournalEventCollection extends BaseEntityCollection
{
	public function __construct(JournalEvent ...$events)
	{
		foreach ($events as $event)
		{
			$this->collectionItems[] = $event;
		}
	}
}
