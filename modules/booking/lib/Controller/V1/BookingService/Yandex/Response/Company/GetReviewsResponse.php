<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ReviewCollection;

class GetReviewsResponse implements \JsonSerializable
{
	public function __construct(
		public readonly ReviewCollection $reviewCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'reviews' => $this->reviewCollection->toArray(),
		];
	}
}
