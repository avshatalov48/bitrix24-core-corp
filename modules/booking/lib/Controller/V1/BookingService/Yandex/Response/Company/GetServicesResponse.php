<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ServiceCollection;

class GetServicesResponse implements \JsonSerializable
{
	public function __construct(
		public readonly ServiceCollection $serviceCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'services' => $this->serviceCollection->toArray(),
		];
	}
}
