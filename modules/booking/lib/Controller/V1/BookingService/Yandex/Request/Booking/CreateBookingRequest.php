<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Request\Booking;

class CreateBookingRequest
{
	private string $companyId;
	private CreateBookingUser $user;
	private CreateBookingAppointment $appointment;
	private string|null $comment = null;
	private array|null $additionalFields = null;

	public function __construct(
		string $companyId,
		CreateBookingUser $user,
		CreateBookingAppointment $appointment,
		string|null $comment = null,
		array|null $additionalFields = null
	)
	{
		$this->companyId = $companyId;
		$this->user = $user;
		$this->appointment = $appointment;
		$this->comment = $comment;
		$this->additionalFields = $additionalFields;
	}

	public static function mapFromArray(array $request): self
	{
		return new self(
			companyId: $request['companyId'],
			user: CreateBookingUser::mapFromArray($request['user']),
			appointment: CreateBookingAppointment::mapFromArray($request['appointment']),
			comment: $request['comment'] ?? null,
			additionalFields: $request['additionalFields'] ?? null,
		);
	}

	public function getCompanyId(): string
	{
		return $this->companyId;
	}

	public function getUser(): CreateBookingUser
	{
		return $this->user;
	}

	public function getAppointment(): CreateBookingAppointment
	{
		return $this->appointment;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function getAdditionalFields(): array|null
	{
		return $this->additionalFields;
	}
}
