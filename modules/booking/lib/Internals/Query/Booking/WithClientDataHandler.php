<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Internals\Container;

class WithClientDataHandler
{
	public function __invoke(WithClientDataRequest $request): void
	{
		$bookingCollection = $request->bookingCollection;

		$clientCollections = [];
		foreach ($bookingCollection as $booking)
		{
			$clientCollections[] = $booking->getClientCollection();
		}

		Container::getProviderManager()::getCurrentProvider()
			?->getClientProvider()
			?->loadClientDataForCollection(...$clientCollections);
	}
}
