<?php

declare(strict_types=1);

namespace Bitrix\Booking\Interfaces;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\ClientCollection;

interface DataProviderInterface
{
	public function getMoneyStatistics(...$externalDataCollections): array;

	public function getBaseCurrencyId(): string|null;

	public function loadDataForCollection(...$externalDataCollections): void;

	public function setClientsData(ClientCollection $clientCollection, ...$externalDataCollections): void;

	public function updateBindings(Booking $updatedBooking, Booking $prevBooking): void;
}
