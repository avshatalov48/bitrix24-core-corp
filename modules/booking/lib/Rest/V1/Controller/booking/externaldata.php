<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\Booking;

use Bitrix\Booking\Command\Booking\BookingResult;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;

class ExternalData extends Controller
{
	private const ENTITY_ID = 'EXTERNAL_DATA';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\ExternalData $externalDataFactory;
	private BookingProvider $bookingProvider;

	public function init(): void
	{
		$this->externalDataFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\ExternalData();
		$this->bookingProvider = new BookingProvider();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.booking.ExternalData.list
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

		$externalDataCollection = $booking->getExternalDataCollection();

		return new Page(
			id: self::ENTITY_ID,
			items: $this->convertToRestFields($externalDataCollection),
			totalCount: 0,
		);
	}

	/**
	 * @restMethod booking.v1.booking.ExternalData.unset
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

		$booking->setExternalDataCollection(new ExternalDataCollection());

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
	 * @restMethod booking.v1.booking.ExternalData.set
	 */
	public function setAction(
		int $bookingId,
		array $externalData,
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

		$externalDataCollection = new ExternalDataCollection();
		foreach ($externalData as $externalDataItem)
		{
			$externalDataCollection->add($this->externalDataFactory->createFromRestFields($externalDataItem));
		}

		$booking->setExternalDataCollection($externalDataCollection);

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
