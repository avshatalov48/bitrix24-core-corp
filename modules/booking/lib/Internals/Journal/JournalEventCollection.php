<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Journal;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Internals\Journal\JournalEvent|null getFirstCollectionItem()
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
