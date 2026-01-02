<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingMessageRepositoryInterface;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\ExternalDataService;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Trait\ExternalDataTrait;

class BookingProvider
{
	use ExternalDataTrait;

	public function __construct(
		private BookingRepositoryInterface|null $repository = null,
		private BookingMessageRepositoryInterface|null $messageRepository = null,
		private ExternalDataService|null $externalDataService = null,
	)
	{
		$this->repository = $repository ?? Container::getBookingRepository();
		$this->messageRepository = $messageRepository ?? Container::getBookingMessageRepository();
		$this->externalDataService = $this->externalDataService ?? Container::getExternalDataService();
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

	public function withMessages(BookingCollection $bookingCollection): self
	{
		$messageCollection = $this->messageRepository->getByBookingIds($bookingCollection->getEntityIds());

		foreach ($bookingCollection as $booking)
		{
			$booking->setMessageCollection(
				$messageCollection->filterByBookingId($booking->getId())
			);
		}

		return $this;
	}

	public function getIntersectionsList(int $userId, Booking $booking): BookingCollection
	{
		return $this->repository->getIntersectionsList($booking, $userId);
	}

	public function getById(
		int $userId,
		int $id,
		bool $withCounters = true,
		bool $withClientsData = true,
		bool $withExternalData = true,
	): Booking|null
	{
		return $this->repository->getById(
			id: $id,
			userId: $userId,
			withCounters: $withCounters,
			withClientsData: $withClientsData,
			withExternalData: $withExternalData,
		);
	}

	public function getBookingForManager(int $id): Booking|null
	{
		return $this->repository->getByIdForManager($id);
	}

	public function getByHash(string $hash): Booking
	{
		return (new BookingConfirmLink())->getBookingByHash($hash);
	}

	protected function getExternalDataService(): ExternalDataService
	{
		return $this->externalDataService;
	}
}
