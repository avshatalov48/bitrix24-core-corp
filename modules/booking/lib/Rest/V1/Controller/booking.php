<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller;

use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Booking\Command\Booking\BookingResult;
use Bitrix\Booking\Command\Booking\CreateBookingFromWaitListItemCommand;
use Bitrix\Booking\Command\Booking\RemoveBookingCommand;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Booking\BookingSort;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Booking\Rest\V1\Controller;
use Bitrix\Booking\Rest\V1\Factory\Filter\CreatedWithin;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class Booking extends Controller
{
	private const ENTITY_ID = 'BOOKING';
	private \Bitrix\Booking\Rest\V1\Factory\Entity\Booking $bookingFactory;
	private BookingProvider $bookingProvider;
	private \Bitrix\Booking\Rest\V1\Factory\Entity\Resource $resourceFactory;

	public function init(): void
	{
		$this->bookingFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\Booking();
		$this->bookingProvider = new BookingProvider();

		$this->resourceFactory = new \Bitrix\Booking\Rest\V1\Factory\Entity\Resource();

		parent::init();
	}

	/**
	 * @restMethod booking.v1.Booking.get
	 */
	public function getAction(int $id): ?array
	{
		$booking =
			$this
				->bookingProvider
				->getById(
					userId: $this->getUserId(),
					id: $id,
				)
		;
		if (!$booking)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Booking not found',
					Exception::CODE_BOOKING_NOT_FOUND,
				)
			);
		}

		return [
			self::ENTITY_ID => $this->prepareBookingToReturn($booking),
		];
	}

	/**
	 * @restMethod booking.v1.Booking.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $filter = [],
		array $order = [],
	): ?Page
	{
		if (isset($filter['CREATED_WITHIN']))
		{
			$createdWithinFactory = new CreatedWithin();
			$validationResult = $createdWithinFactory->validateRestFields($filter['CREATED_WITHIN']);
			if (!$validationResult->isSuccess())
			{
				return $this->responseWithErrors($validationResult->getErrors());
			}

			$filter['CREATED_WITHIN'] = $createdWithinFactory->createFromRestFields($filter['CREATED_WITHIN']);
		}

		$bookingCollection =
			$this
				->bookingProvider
				->getList(
					new GridParams(
						limit: $pageNavigation->getLimit(),
						offset: $pageNavigation->getOffset(),
						filter: new BookingFilter($filter),
						sort: new BookingSort($order),
						select: new BookingSelect(['RESOURCES']),
					),
					userId: $this->getUserId(),
				)
		;

		$bookings = [];
		foreach ($bookingCollection as $booking)
		{
			$bookings[] = $this->prepareBookingToReturn($booking);
		}

		return new Page(
			id: self::ENTITY_ID,
			items: $bookings,
			totalCount: 0,
		);
	}

	private function prepareBookingToReturn(\Bitrix\Booking\Entity\Booking\Booking $booking): array
	{
		$bookingRestFields = $this->convertToRestFields($booking);

		$resourceIds = [];
		foreach ($bookingRestFields['RESOURCES'] as $resource)
		{
			$resourceIds[] = $resource['ID'];
		}

		$bookingRestFields['RESOURCE_IDS'] = $resourceIds;

		return $bookingRestFields;
	}

	/**
	 * @restMethod booking.v1.Booking.add
	 */
	public function addAction(array $fields): ?int
	{
		$validationResult = $this->bookingFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$booking =
			$this
				->bookingFactory
				->createFromRestFields(
					fields: $fields,
					userId: $this->getUserId(),
				)
		;
		$command = new AddBookingCommand(
			createdBy: $this->getUserId(),
			booking: $booking,
			allowOverbooking: true,
		);

		/** @var BookingResult $result */
		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return
			$result
				->getBooking()
				->getId()
			;
	}

	/**
	 * @restMethod booking.v1.Booking.update
	 */
	public function updateAction(int $id, array $fields): ?bool
	{
		$validationResult = $this->bookingFactory->validateRestFields(
			fields: $fields,
		);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$booking =
			$this
				->bookingProvider
				->getById(
					userId: $this->getUserId(),
					id: $id,
				)
		;
		if (!$booking)
		{
			return $this->responseWithError(
				ErrorBuilder::build(
					'Booking not found',
					Exception::CODE_BOOKING_UPDATE,
				)
			);
		}

		$booking =
			$this
				->bookingFactory
				->createFromRestFields(
					fields: [
						...$this->convertToRestFields($booking),
						...$fields,
					],
					booking: $booking
				)
		;

		$booking->setId($id);

		$command = new UpdateBookingCommand(
			updatedBy: $this->getUserId(),
			booking: $booking,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.Booking.delete
	 */
	public function deleteAction(int $id): ?bool
	{
		$command = new RemoveBookingCommand(
			id: $id,
			removedBy: $this->getUserId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			return $this->responseWithErrors($result->getErrors());
		}

		return true;
	}

	/**
	 * @restMethod booking.v1.Booking.createFromWaitList
	 */
	public function createFromWaitListAction(
		int $waitListId,
		array $fields,
	): ?int
	{
		$validationResult = $this->bookingFactory->validateRestFields($fields);
		if (!$validationResult->isSuccess())
		{
			return $this->responseWithErrors($validationResult->getErrors());
		}

		$resources = $this->resourceFactory->createCollectionFromResourceIds($fields['RESOURCE_IDS'])->toArray();

		$converter = new Converter(
			Converter::KEYS
			| Converter::LC_FIRST
			| Converter::TO_CAMEL
			| Converter::RECURSIVE
		);
		$datePeriod = $converter->process($fields['DATE_PERIOD']);

		$command = new CreateBookingFromWaitListItemCommand(
			waitListItemId: $waitListId,
			resources: $resources,
			datePeriod: $datePeriod,
			createdBy: $this->getUserId(),
			allowOverbooking: true,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return
			$result
				->getBooking()
				->getId()
			;
	}
}
