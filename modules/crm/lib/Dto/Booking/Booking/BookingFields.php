<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking\Booking;

use Bitrix\Crm\Dto\Booking\Client;
use Bitrix\Crm\Dto\Booking\DatePeriod;
use Bitrix\Crm\Dto\Booking\EntityFieldsInterface;
use Bitrix\Crm\Dto\Booking\ExternalData;
use Bitrix\Crm\Dto\Booking\Resource;

class BookingFields implements EntityFieldsInterface
{
	/**
	 * @param Resource[] $resources
	 * @param Client[] $clients
	 * @param ExternalData[] $externalData
	 */
	public function __construct(
		public readonly int $id,
		public readonly DatePeriod $datePeriod,
		public readonly bool $isOverbooking,
		public readonly bool $isConfirmed,
		public readonly array $resources,
		public readonly array $clients,
		public readonly array $externalData,
		public readonly string|null $name,
		public readonly int|null $createdBy,
		public readonly string|null $note,
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			id: $params['id'],
			datePeriod: DatePeriod::mapFromArray($params['datePeriod']),
			isOverbooking: $params['isOverbooking'] ?? false,
			isConfirmed: $params['isConfirmed'] ?? false,
			resources: array_map(static fn ($resource) => Resource::mapFromArray($resource), $params['resources']),
			clients: array_map(static fn ($client) => Client::mapFromArray($client), $params['clients']),
			externalData: array_map(
				static fn ($externalData) => ExternalData::mapFromArray($externalData),
				$params['externalData'],
			),
			name: $params['name'] ?? null,
			createdBy: $params['createdBy'] ?? null,
			note: $params['note'] ?? null,
		);
	}

	public static function tryFrom(array|null $params): self|null
	{
		if (!$params || !is_array($params))
		{
			return null;
		}

		try
		{
			return self::mapFromArray($params);
		}
		catch (\Throwable)
		{
			return null;
		}
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'datePeriod' => $this->datePeriod->toArray(),
			'isOverbooking' => $this->isOverbooking,
			'isConfirmed' => $this->isConfirmed,
			'resources' => array_map(static fn (Resource $resource) => $resource->toArray(), $this->resources),
			'clients' => array_map(static fn (Client $client) => $client->toArray(), $this->clients),
			'externalData' => array_map(
				static fn (ExternalData $externalData) => $externalData->toArray(),
				$this->externalData,
			),
			'name' => $this->name,
			'createdBy' => $this->createdBy,
			'note' => $this->note,
		];
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	/**
	 * @return Client[]
	 */
	public function getClients(): array
	{
		return $this->clients;
	}

	/**
	 * @return ExternalData[]
	 */
	public function getExternalData(): array
	{
		return $this->externalData;
	}
}
