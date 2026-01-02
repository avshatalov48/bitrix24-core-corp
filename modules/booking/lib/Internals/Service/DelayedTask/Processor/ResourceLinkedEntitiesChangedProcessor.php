<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask\Processor;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Booking\Internals\Service\ClientService;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\DataInterface;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\ResourceLinkedEntitiesChangedData;
use Bitrix\Booking\Internals\Service\EventForBookingService;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;

class ResourceLinkedEntitiesChangedProcessor implements ProcessorInterface
{
	private ResourceRepositoryInterface $resourceRepository;
	private BookingRepositoryInterface $bookingRepository;
	private ClientService $clientService;
	private EventForBookingService $eventForBookingService;

	public function __construct(
		private readonly ResourceLinkedEntitiesChangedData $resourceLinkedEntitiesChangedData
	)
	{
		$this->resourceRepository = Container::getResourceRepository();
		$this->bookingRepository = Container::getBookingRepository();
		$this->clientService = Container::getClientService();
		$this->eventForBookingService = Container::getEventForBookingService();
	}

	public function __invoke(): void
	{
		if (self::checkData($this->resourceLinkedEntitiesChangedData))
		{
			$this->processLinkedUserEntitiesChange();
		}
	}

	/**
	 * @param ResourceLinkedEntitiesChangedData $data
	 */
	public static function checkData(DataInterface $data): bool
	{
		$isUsersAdded = $data->added
			&& !$data->added
				->getByTypeAndId(ResourceLinkedEntityType::User)
				->isEmpty()
		;
		$isUsersDeleted = $data->deleted
			&& !$data->deleted
				->getByTypeAndId(ResourceLinkedEntityType::User)
				->isEmpty()
		;

		return $isUsersAdded || $isUsersDeleted;
	}

	private function processLinkedUserEntitiesChange(): void
	{
		/** @var Resource $resource */
		$resource = $this->resourceRepository->getById($this->resourceLinkedEntitiesChangedData->resourceId);

		// get all future (not started yet) bookings
		$bookings = $this->bookingRepository->getList(
			filter: (new BookingFilter([
				'RESOURCE_ID' => [$resource->getId()],
				'WITHIN' => [
					'DATE_FROM' => $resource->getUpdatedAt(),
				],
			])),
			select: (new BookingSelect(['RESOURCES', 'CLIENTS', 'EXTERNAL_DATA']))->prepareSelect(),
		);

		foreach ($bookings as $booking)
		{
			$this->clientService->loadClientData($booking->getClientCollection());
			$this->eventForBookingService->onBookingResourceUpdated($booking);
		}
	}
}
