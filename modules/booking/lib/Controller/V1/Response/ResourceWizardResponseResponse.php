<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

class ResourceWizardResponseResponse implements \JsonSerializable
{
	public function __construct(
		public readonly array $advertisingResourceTypes,
		public readonly array $notificationsSettings,
		public readonly array $companyScheduleSlots,
		public readonly bool $isCompanyScheduleAccess,
		public readonly string $weekStart,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'advertisingResourceTypes' => $this->advertisingResourceTypes,
			'notificationsSettings' => $this->notificationsSettings,
			'companyScheduleSlots' => $this->companyScheduleSlots,
			'isCompanyScheduleAccess' => $this->isCompanyScheduleAccess,
			'weekStart' => $this->weekStart,
		];
	}
}
