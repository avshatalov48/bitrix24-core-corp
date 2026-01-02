<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Provider\Params\WaitListItem\WaitListItemFilter;
use Bitrix\Main\Type\DateTime;
use CTimeZone;
use DateTimeImmutable;

class ClientStatisticsProvider
{
	private BookingProvider $bookingProvider;
	private BookingClientRepositoryInterface $clientRepository;
	private WaitListItemProvider $waitListItemProvider;

	public function __construct()
	{
		$this->bookingProvider = new BookingProvider();
		$this->clientRepository = Container::getBookingClientRepository();
		$this->waitListItemProvider = new WaitListItemProvider();
	}

	public function getTotalClients(): int
	{
		return $this->clientRepository->getTotalClients();
	}

	public function getTotalClientsToday(int $userId): int
	{
		$filter = [];

		$bookingIds = $this->getTodayBookingIds($userId);
		if ($bookingIds)
		{
			$filter[EntityType::Booking->value] = $bookingIds;
		}

		$waitListItemIds = $this->getTodayWaitListItemIds($userId);
		if ($waitListItemIds)
		{
			$filter[EntityType::WaitList->value] = $waitListItemIds;
		}

		return $this->clientRepository->getTotalNewClientsToday(
			(new Params\Client\ClientFilter([
				'ENTITY_TYPE_IDS' => $filter,
			]))
		);
	}

	private function getTodayBookingIds(int $userId): array
	{
		return $this->bookingProvider->getList(
			new GridParams(
				filter: new BookingFilter([
					'INCLUDE_DELETED' => true,
					'CREATED_WITHIN' => [
						'FROM' => DateTime::createFromTimestamp(
							$this->getTodayStartDate()->getTimestamp() - CTimeZone::GetOffset()
						),
						'TO' => DateTime::createFromTimestamp(
							$this->getTodayEndDate()->getTimestamp() - CTimeZone::GetOffset()
						),
					],
				]),
			),
			userId: $userId,
		)->getEntityIds();
	}

	private function getTodayWaitListItemIds(int $userId): array
	{
		return $this->waitListItemProvider->getList(
			(
				new GridParams(
					filter: new WaitListItemFilter([
						'INCLUDE_DELETED' => true,
						'CREATED_WITHIN' => [
							'FROM' => DateTime::createFromTimestamp(
								$this->getTodayStartDate()->getTimestamp() - CTimeZone::GetOffset()
							),
							'TO' => DateTime::createFromTimestamp(
								$this->getTodayEndDate()->getTimestamp() - CTimeZone::GetOffset()
							),
						],
					]),
				)
			),
			userId: $userId,
		)->getEntityIds();
	}

	private function getTodayStartDate(): DateTimeImmutable
	{
		return (new DateTimeImmutable('today'))->setTime(0, 0);
	}

	private function getTodayEndDate(): DateTimeImmutable
	{
		return (new DateTimeImmutable('tomorrow'))->setTime(0, 0);
	}
}
