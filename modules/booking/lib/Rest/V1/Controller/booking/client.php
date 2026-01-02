<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\Booking;

use Bitrix\Booking\Command\Booking\BookingResult;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;

class Client extends Controller
{
	private const ENTITY_ID = 'BOOKING_CLIENT';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\Client $clientFactory;
	private BookingProvider $bookingProvider;

	public function init(): void
	{
		$this->clientFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\Client();
		$this->bookingProvider = new BookingProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.booking.Client.list
	 */
	public function listAction(
		int $bookingId,
	): ?Page
	{
		$booking =
			$this
				->bookingProvider
				->getById(
					userId: $this->getUserId(),
					id: $bookingId,
				)
		;
		if (!$booking)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Booking not found',
					code: Exception::CODE_BOOKING_NOT_FOUND,
				)
			);
		}

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($booking->getClientCollection()),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.booking.Client.unset
	 */
	public function unsetAction(int $bookingId): ?bool
	{
		$booking =
			$this
				->bookingProvider
				->getById(
					userId: $this->getUserId(),
					id: $bookingId,
				)
		;
		if (!$booking)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Booking not found',
					code: Exception::CODE_BOOKING_NOT_FOUND,
				)
			);
		}

		$booking->setClientCollection(new ClientCollection());

		$command = new UpdateBookingCommand(
			updatedBy: $this->getUserId(),
			booking: $booking,
		);

		/** @var BookingResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.booking.Client.set
	 */
	public function setAction(
		int $bookingId,
		array $clients,
	): ?bool
	{
		$booking =
			$this
				->bookingProvider
				->getById(
					userId: $this->getUserId(),
					id: $bookingId,
				)
		;
		if (!$booking)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					message: 'Booking not found',
					code: Exception::CODE_BOOKING_NOT_FOUND,
				)
			);
		}

		$validationResult = $this->clientFactory->validateRestFieldsList($clients);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		/** @var ClientCollection $clientCollection */
		$clientCollection = $this->clientFactory->createCollectionFromRestFields($clients);
		$booking->setClientCollection($clientCollection);

		$command = new UpdateBookingCommand(
			updatedBy: $this->getUserId(),
			booking: $booking,
		);

		/** @var BookingResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}
}
