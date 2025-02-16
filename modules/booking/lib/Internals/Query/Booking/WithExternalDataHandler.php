<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Internals\Container;

class WithExternalDataHandler
{
	public function __invoke(WithExternalDataRequest $request): void
	{
		$bookingCollection = $request->bookingCollection;

		$externalDataCollections = [];
		foreach ($bookingCollection as $booking)
		{
			$externalDataCollections[] = $booking->getExternalDataCollection();
		}

		Container::getProviderManager()::getCurrentProvider()
			?->getDataProvider()
			?->loadDataForCollection(...$externalDataCollections);
	}
}

