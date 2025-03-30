<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Main\Type\DateTime;
use DateTimeImmutable;

class ClientStatisticsProvider
{
	private BookingProvider $bookingProvider;
	private BookingClientRepositoryInterface $clientRepository;

	public function __construct()
	{
		$this->bookingProvider = new BookingProvider();
		$this->clientRepository = Container::getBookingClientRepository();
	}

	public function getTotalClients(): int
	{
		return $this->clientRepository->getTotalClients();
	}

	public function getTotalClientsToday(int $userId): int
	{
		return $this->clientRepository->getTotalNewClientsToday(
			$this->getTodayBookingIds($userId),
		);
	}

	private function getTodayBookingIds(int $userId): array
	{
		$todayStart = (new DateTimeImmutable('today'))->setTime(0, 0);
		$todayEnd = (new DateTimeImmutable('tomorrow'))->setTime(0, 0);

		return $this->bookingProvider->getList(
			new GridParams(
				filter: new BookingFilter([
					'INCLUDE_DELETED' => true,
					'CREATED_WITHIN' => [
						'FROM' => DateTime::createFromTimestamp(
							$todayStart->getTimestamp() - \CTimeZone::GetOffset()
						),
						'TO' => DateTime::createFromTimestamp(
							$todayEnd->getTimestamp() - \CTimeZone::GetOffset()
						),
					],
				]),
			),
			userId: $userId,
		)->getEntityIds();
	}
}
