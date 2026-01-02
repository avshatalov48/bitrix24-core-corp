<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\WaitListItem;

use Bitrix\Crm\Dto\Booking\Client;
use Bitrix\Crm\Dto\Booking\EntityFieldsInterface;
use Bitrix\Crm\Dto\Booking\ExternalData;

class WaitListItemFields implements EntityFieldsInterface
{
	public function __construct(
		public readonly int $id,
		public readonly array $clients,
		public readonly array $externalData,
		public readonly int|null $createdBy,
		public readonly string|null $note,
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			id: $params['id'],
			clients: array_map(static fn ($client) => Client::mapFromArray($client), $params['clients']),
			externalData: array_map(
				static fn ($externalData) => ExternalData::mapFromArray($externalData),
				$params['externalData'],
			),
			createdBy: $params['createdBy'] ?? null,
			note: $params['note'] ?? null,
		);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function getClients(): array
	{
		return $this->clients;
	}

	public function getExternalData(): array
	{
		return $this->externalData;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'clients' => array_map(static fn (Client $client) => $client->toArray(), $this->clients),
			'externalData' => array_map(
				static fn (ExternalData $externalData) => $externalData->toArray(),
				$this->externalData,
			),
			'createdBy' => $this->createdBy,
			'note' => $this->note,
		];
	}
}
