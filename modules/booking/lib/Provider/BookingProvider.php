<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Provider\Params\GridParams;

class BookingProvider
{
	private BookingRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getBookingRepository();
	}

	public function getList(GridParams $gridParams, int $userId): BookingCollection
	{
		return $this->repository->getList(
			limit: $gridParams->limit,
			offset: $gridParams->offset,
			filter: $gridParams->filter,
			sort: $gridParams->getSort(),
			select: $gridParams->getSelect(),
		);
	}

	public function withCounters(BookingCollection $bookingCollection, int $userId): self
	{
		$counterRepository = Container::getCounterRepository();

		/** @var Booking $booking */
		foreach ($bookingCollection as $booking)
		{
			$counters = [];

			$value = $counterRepository->get(
				userId: $userId,
				type: CounterDictionary::BookingUnConfirmed,
				entityId: $booking->getId(),
			);
			$counters[] = [
				'type' => CounterDictionary::BookingUnConfirmed->value,
				'value' => $value,
			];

			$value += $counterRepository->get(
				userId: $userId,
				type: CounterDictionary::BookingDelayed,
				entityId: $booking->getId(),
			);
			$counters[] = [
				'type' => CounterDictionary::BookingDelayed->value,
				'value' => $value,
			];

			$booking->setCounter($value);
			$booking->setCounters($counters);
		}

		return $this;
	}

	public function withClientsData(BookingCollection $bookingCollection): self
	{
		$clientCollections = [];

		foreach ($bookingCollection as $booking)
		{
			$clientCollections[] = $booking->getClientCollection();
		}

		Container::getProviderManager()::getCurrentProvider()
			?->getClientProvider()
			?->loadClientDataForCollection(...$clientCollections);

		return $this;
	}

	public function withExternalData(BookingCollection $bookingCollection): self
	{
		$externalDataCollections = [];

		foreach ($bookingCollection as $booking)
		{
			$externalDataCollections[] = $booking->getExternalDataCollection();
		}

		Container::getProviderManager()::getCurrentProvider()
			?->getDataProvider()
			?->loadDataForCollection(...$externalDataCollections);

		return $this;
	}

	public function getIntersectionsList(int $userId, Booking $booking): BookingCollection
	{
		return $this->repository->getIntersectionsList($booking, $userId);
	}

	public function getById(int $userId, int $id): Booking|null
	{
		return $this->repository->getById($id, $userId);
	}

	public function getBookingForManager(int $id): Booking|null
	{
		return $this->repository->getByIdForManager($id);
	}

	public function getByHash(string $hash): Booking
	{
		return (new BookingConfirmLink())->getBookingByHash($hash);
	}
}
