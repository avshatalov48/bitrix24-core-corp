<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Message;

use Bitrix\Booking\Entity\BaseEntityCollection;

/**
 * @method \Bitrix\Booking\Entity\Message\BookingMessage|null getFirstCollectionItem()
 * @method BookingMessage[] getIterator()
 */
class BookingMessageCollection extends BaseEntityCollection
{
	public function __construct(BookingMessage ...$items)
	{
		foreach ($items as $item)
		{
			$this->collectionItems[] = $item;
		}
	}

	public static function mapFromArray(array $props): self
	{
		$result = [];

		foreach ($props as $item)
		{
			$result[] = BookingMessage::mapFromArray($item);
		}

		return new self(...$result);
	}

	public function filterByBookingId(int $bookingId): self
	{
		$result = new self();
		
		foreach ($this as $item)
		{
			if ($item->getBookingId() !== $bookingId)
			{
				continue;
			}

			$result->add($item);
		}

		return $result;
	}
}
