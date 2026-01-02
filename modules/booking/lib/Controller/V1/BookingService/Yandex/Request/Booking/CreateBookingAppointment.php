<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking;

class CreateBookingAppointment
{
	private array $serviceIds;
	private string $datetime;
	private string|null $resourceId;

	public function __construct(array $serviceIds, string $datetime, string|null $resourceId)
	{
		$this->serviceIds = $serviceIds;
		$this->datetime = $datetime;
		$this->resourceId = $resourceId;
	}

	public static function mapFromArray(array $request): self
	{
		return new self(
			$request['serviceIds'],
			$request['datetime'],
			$request['resourceId'] ?? null,
		);
	}

	public function getServiceIds(): array
	{
		return $this->serviceIds;
	}

	public function getDatetime(): string
	{
		return $this->datetime;
	}

	public function getResourceId(): string|null
	{
		return $this->resourceId;
	}
}
