<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Message\BookingMessage;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingMessageRepositoryInterface;

class BookingMessageProvider
{
	private BookingMessageRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = Container::getBookingMessageRepository();
	}

	public function getById(string $senderModule, string $senderCode, int $externalId): BookingMessage|null
	{
		return $this->repository->getByExternalId(
			senderModule: $senderModule,
			senderCode: $senderCode,
			externalId: $externalId
		);
	}
}
