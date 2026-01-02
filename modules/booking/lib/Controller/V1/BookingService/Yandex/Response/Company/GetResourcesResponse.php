<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ResourceCollection;

class GetResourcesResponse implements \JsonSerializable
{
	public function __construct(
		public readonly ResourceCollection $resourceCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'resources' => $this->resourceCollection->toArray(),
		];
	}
}
