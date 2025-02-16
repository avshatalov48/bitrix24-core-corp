<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Container;
use Bitrix\Main;
use Bitrix\Booking\Entity;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Booking;

abstract class BaseController extends Main\Engine\JsonController
{
	public function getDefaultPreFilters()
	{
		$prefilters = parent::getDefaultPreFilters();

		if (Loader::includeModule('intranet'))
		{
			$prefilters[] = new \Bitrix\Intranet\ActionFilter\IntranetUser();
		}

		return $prefilters;
	}

	public function handleRequest(callable $fn): mixed
	{
		try
		{
			return $fn();
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return null;
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new Main\Engine\AutoWire\ExactParameter(
				className: Entity\Booking\Booking::class,
				parameterName: 'booking',
				constructor: function ($className, array $booking = []): ?Entity\Booking\Booking
				{
					try
					{
						$fields = $this->getEntityFields($booking, Container::getBookingRepository());

						return $fields === null
							? null
							: Entity\Booking\Booking::mapFromArray([...$fields, ...$booking])
							;
					}
					catch (Booking\Exception\Exception $e)
					{
						$this->addError(new Error($e->getMessage(), $e->getCode()));
					}

					return null;
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				className: Entity\Booking\BookingCollection::class,
				parameterName: 'bookingList',
				constructor: function ($className, array $bookingList = []): ?Entity\Booking\BookingCollection
				{
					try
					{
						$bookingCollection = new Entity\Booking\BookingCollection();
						foreach ($bookingList as $booking)
						{
							$fields = $this->getEntityFields($booking, Container::getBookingRepository());
							$booking = $fields === null
								? null
								: Entity\Booking\Booking::mapFromArray([...$fields, ...$booking])
							;

							$bookingCollection->add($booking);
						}

						return $bookingCollection;
					}
					catch (Booking\Exception\Exception $e)
					{
						$this->addError(new Error($e->getMessage(), $e->getCode()));
					}

					return null;
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				className: Entity\Resource\Resource::class,
				parameterName: 'resource',
				constructor: function ($className, array $resource = []): ?Entity\Resource\Resource
				{
					try
					{
						$fields = $this->getEntityFields($resource, Container::getResourceRepository());

						return $fields === null
							? null
							: Entity\Resource\Resource::mapFromArray([...$fields, ...$resource])
							;
					}
					catch (Booking\Exception\Exception $e)
					{
						$this->addError(new Error($e->getMessage(), $e->getCode()));
					}

					return null;
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				className: Entity\ResourceType\ResourceType::class,
				parameterName: 'resourceType',
				constructor: function ($className, array $resourceType = []): ?Entity\ResourceType\ResourceType
				{
					try
					{
						$fields = $this->getEntityFields($resourceType, Container::getResourceTypeRepository());

						return $fields === null
							? null
							: Entity\ResourceType\ResourceType::mapFromArray([...$fields, ...$resourceType])
							;
					}
					catch (Booking\Exception\Exception $e)
					{
						$this->addError(new Error($e->getMessage(), $e->getCode()));
					}

					return null;
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				className: \DateTimeImmutable::class,
				parameterName: 'selectedDate',
				constructor: function ($className, string $selectedDate = null): \DateTimeImmutable
				{
					return ($selectedDate === null)
						? new \DateTimeImmutable("today 00:00")
						: new \DateTimeImmutable($selectedDate)
						;
				}
			),
		];
	}

	private function getEntityFields(array $providedFields, $repository): array|null
	{
		if (empty($providedFields))
		{
			return null;
		}

		$fields = [];

		if (!empty($providedFields['id']))
		{
			$entity = $repository->getById((int)$providedFields['id']);
			if (!$entity)
			{
				return null;
			}

			$fields = $entity->toArray();
		}

		return $fields;
	}
}
