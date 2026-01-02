<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response\CalendarData;

use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Main\Type\Contract\Arrayable;

class ServiceDto implements Arrayable
{
	public function __construct(
		public readonly int $id,
		public readonly string|null $name,
		public readonly array $permissions,
	)
	{
	}

	public static function fromEntity(ExternalDataItem $externalDataItem): self
	{
		$serviceData = $externalDataItem->getData();

		return new self(
			id: (int)$externalDataItem->getValue(),
			name: $serviceData['data']['name'],
			permissions: $serviceData['permissions'],
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'permissions' => $this->permissions,
		];
	}
}
