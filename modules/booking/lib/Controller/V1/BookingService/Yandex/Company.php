<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex;

use Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company\GetAvailableDatesResponse;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company\GetAvailableTimeSlotsResponse;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company\GetResourcesResponse;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company\GetReviewsResponse;
use Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company\GetServicesResponse;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Yandex\AvailableDatesProvider;
use Bitrix\Booking\Internals\Service\Yandex\AvailableTimeSlotsProvider;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ReviewCollection;
use Bitrix\Booking\Internals\Service\Yandex\ResourceProvider;
use Bitrix\Booking\Internals\Service\Yandex\ServiceProvider;
use Bitrix\Main\Request;

class Company extends BaseController
{
	private ServiceProvider $serviceProvider;
	private ResourceProvider $resourceProvider;
	private AvailableTimeSlotsProvider $availableTimeSlotsProvider;
	private AvailableDatesProvider $availableDatesProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->resourceProvider = Container::getYandexResourceProvider();
		$this->serviceProvider = Container::getYandexServiceProvider();
		$this->availableTimeSlotsProvider = Container::getYandexAvailableTimeSlotsProvider();
		$this->availableDatesProvider = Container::getYandexAvailableDatesProvider();
	}

	public function getServicesAction(
		string $companyId,
		string $resourceId = null,
	): GetServicesResponse|null
	{
		return $this->handle(
			fn() => new GetServicesResponse(
				serviceCollection: $this->serviceProvider->getServices($companyId, $resourceId)
			)
		);
	}

	public function getResourcesAction(
		string $companyId,
		array $serviceIds = [],
	): GetResourcesResponse|null
	{
		return $this->handle(
			fn() => new GetResourcesResponse(
				resourceCollection: $this->resourceProvider->getResources($companyId, $serviceIds)
			)
		);
	}

	public function getReviewsAction(
		string $companyId,
		int $resourceId,
	): GetReviewsResponse|null
	{
		return $this->handle(
			fn() => new GetReviewsResponse(
				reviewCollection: new ReviewCollection()
			)
		);
	}

	public function getAvailableDatesAction(
		string $companyId,
		array $serviceIds,
		string $from,
		string $to,
		string|null $resourceId = null,
	): GetAvailableDatesResponse|null
	{
		return $this->handle(
			fn() => new GetAvailableDatesResponse(
				availableDateCollection: $this->availableDatesProvider->getAvailableDates(
					$companyId,
					$serviceIds,
					$from,
					$to,
					$resourceId,
				)
			)
		);
	}

	public function getAvailableTimeSlotsAction(
		string $companyId,
		array $serviceIds,
		string $date,
		string|null $resourceId = null,
	): GetAvailableTimeSlotsResponse|null
	{
		return $this->handle(
			fn() => new GetAvailableTimeSlotsResponse(
				availableTimeSlotCollection: $this->availableTimeSlotsProvider->getAvailableTimeSlots(
					$companyId,
					$serviceIds,
					$date,
					$resourceId,
				)
			)
		);
	}
}
