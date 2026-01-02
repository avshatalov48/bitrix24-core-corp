<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Booking\Command\Booking\BookingResult;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking\CreateBookingAppointment;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking\CreateBookingRequest;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking\CreateBookingUser;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingSource;
use Bitrix\Booking\Entity\Client\Client;
use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\Client\ClientType;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Exception\Yandex\BookingCreateForbiddenException;
use Bitrix\Booking\Internals\Exception\Yandex\ResourceNotFoundException;
use Bitrix\Booking\Internals\Exception\Yandex\ServiceNotFoundException;
use Bitrix\Booking\Internals\Exception\Yandex\SlotUnavailableException;
use Bitrix\Booking\Internals\Integration\Catalog\ServiceSkuProvider;
use Bitrix\Booking\Internals\Integration\Crm\Contact\ContactDto;
use Bitrix\Booking\Internals\Integration\Crm\Contact\ContactService;
use Bitrix\Booking\Internals\Integration\Crm\DealService;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Resource\ResourceFilter;
use Bitrix\Booking\Provider\Params\Resource\ResourceSelect;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Result;
use DateTimeImmutable;
use DateInterval;
use DateTimeInterface;
use Bitrix\Booking\Internals\Service\Yandex;

class CreateBookingService
{
	private DatePeriod|null $appointmentDatePeriod = null;

	public function __construct(
		private readonly BookingRepositoryInterface $bookingRepository,
		private readonly ResourceRepositoryInterface $resourceRepository,
		private readonly ServiceSkuProvider $serviceSkuProvider,
		private readonly ContactService $contactService,
		private readonly DealService $dealService,
	)
	{
	}

	public function create(CreateBookingRequest $createBookingRequest): Yandex\Dto\Item\Booking
	{
		$appointment = $createBookingRequest->getAppointment();

		$resourceId = $appointment->getResourceId() !== null ? (int)$appointment->getResourceId() : null;
		if ($resourceId !== null)
		{
			$resource = $this->resourceRepository->getById($resourceId);
			if ($resource === null)
			{
				throw new ResourceNotFoundException();
			}
		}

		$externalDataCollection = $this->createExternalDataCollection($appointment);

		$appointmentDate = DateTimeImmutable::createFromFormat(
			DateTimeInterface::ATOM,
			$appointment->getDatetime(),
		);

		$resource = $this->findResource(
			$externalDataCollection->filterByType((new CatalogSkuItemType())->buildFilter())->getValues(),
			$appointmentDate,
			$resourceId
		);

		$contactId = $this->findOrCreateContact($createBookingRequest->getUser());
		if (!$contactId)
		{
			throw new BookingCreateForbiddenException();
		}

		/** @var Result|BookingResult $addResult */
		$addResult = (new AddBookingCommand(
			createdBy: (int)CurrentUser::get()->getId(),
			booking: (new Booking())
				->setSource(BookingSource::Yandex)
				->setNote($createBookingRequest->getComment())
				->setDatePeriod($this->appointmentDatePeriod)
				->setResourceCollection(new ResourceCollection($resource))
				->setExternalDataCollection($externalDataCollection)
				->setClientCollection(
					new ClientCollection(
						(new Client())
							->setId($contactId)
							->setType(
								(new ClientType())
									->setModuleId('crm')
									->setCode(\CCrmOwnerType::ContactName)
							)
					)
				)
			,
		))->run();

		if (!$addResult->isSuccess())
		{
			throw new BookingCreateForbiddenException();
		}

		$booking = $addResult->getBooking();

		Application::getInstance()->addBackgroundJob(
			function () use ($booking) {
				$this->dealService->createDealForBooking($booking);
			}
		);

		return Yandex\Dto\Item\Booking::createFromBooking($booking);
	}

	private function createExternalDataCollection(CreateBookingAppointment $appointment): ExternalDataCollection
	{
		$result = new ExternalDataCollection();

		$serviceIds = array_unique(array_map('intval', $appointment->getServiceIds()));
		if (empty($serviceIds))
		{
			throw new ServiceNotFoundException();
		}

		$skus = $this->serviceSkuProvider->get($serviceIds);
		if (count($skus) !== count($serviceIds))
		{
			throw new ServiceNotFoundException();
		}

		foreach ($skus as $sku)
		{
			$result->add((new CatalogSkuItemType())->createItem()->setValue((string)$sku->getId()));
		}

		return $result;
	}

	private function findResource(
		array $skuIds,
		DateTimeImmutable $appointmentDate,
		int|null $resourceId
	): Resource
	{
		$resourceFilter = [
			'IS_MAIN' => true,
			'LINKED_ENTITY' => [
				'TYPE' => ResourceLinkedEntityType::Sku,
				'ID' => $skuIds,
			],
		];
		if ($resourceId !== null)
		{
			$resourceFilter['ID'] = $resourceId;
		}

		$resourceCollection = $this->resourceRepository->getList(
			filter: new ResourceFilter($resourceFilter),
			select: new ResourceSelect(['SETTINGS']),
		);

		if ($resourceCollection->isEmpty())
		{
			throw new ResourceNotFoundException();
		}

		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			foreach ($resource->getSlotRanges() as $slotRange)
			{
				if (!in_array($appointmentDate->format('D'), $slotRange->getWeekDays(), true))
				{
					continue;
				}

				$appointmentDatePeriod = new DatePeriod(
					dateFrom: $appointmentDate,
					dateTo: $appointmentDate->add(new DateInterval('PT' . $slotRange->getSlotSize() . 'M')),
				);

				if (!$slotRange->makeDatePeriod($appointmentDate)->contains($appointmentDatePeriod))
				{
					continue;
				}

				$bookingCollection = $this->bookingRepository->getList(
					filter: new BookingFilter([
						'RESOURCE_ID' => [
							$resource->getId(),
						],
						'WITHIN' => [
							'DATE_FROM' => $appointmentDatePeriod->getDateFrom()->getTimestamp(),
							'DATE_TO' => $appointmentDatePeriod->getDateTo()->getTimestamp(),
						],
					]),
					select: (new BookingSelect(['RESOURCES']))->prepareSelect(),
				);

				$isAnyBookingOverlapsWithAppointment = false;
				foreach ($bookingCollection as $booking)
				{
					if ($booking->doEventsIntersect($appointmentDatePeriod))
					{
						$isAnyBookingOverlapsWithAppointment = true;

						break;
					}
				}
				if ($isAnyBookingOverlapsWithAppointment)
				{
					continue;
				}

				$this->appointmentDatePeriod = $appointmentDatePeriod;

				return $resource;
			}
		}

		throw new SlotUnavailableException();
	}

	private function findOrCreateContact(CreateBookingUser $user): int|null
	{
		return $this->contactService->findOrCreate(
			(new ContactDto($user->getName()))
				->setEmail($user->getEmail())
				->setPhone($user->getPhone())
		);
	}
}
