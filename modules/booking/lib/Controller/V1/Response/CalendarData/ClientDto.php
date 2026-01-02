<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response\CalendarData;

use Bitrix\Booking\Entity\Client\Client;
use Bitrix\Main\Type\Contract\Arrayable;

class ClientDto implements Arrayable
{
	public function __construct(
		public readonly int $id,
		public readonly string|null $name,
		public readonly array|null $phones,
		public readonly string|null $image,
		public readonly string $type,
		public readonly array $permissions,
	)
	{
	}

	public static function fromEntity(Client $client): self
	{
		return new self(
			id: $client->getId(),
			name: $client->getData()['data']['name'] ?? null,
			phones: $client->getData()['data']['phones'] ?? null,
			image: $client->getData()['data']['image'] ?? null,
			type: $client->getType()?->getCode(),
			permissions: $client->getData()['permissions'],
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'phones' => $this->phones,
			'image' => $this->image,
			'type' => $this->type,
			'permissions' => $this->permissions,
		];
	}
}
