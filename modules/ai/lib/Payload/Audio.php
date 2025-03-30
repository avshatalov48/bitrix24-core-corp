<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Prompt\Role;

class Audio extends Payload implements IPayload
{
	private const DEFAULT_USAGE_COST_DEMO_PLAN = 1;

	public function __construct(
		protected string $payload
	) {}

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		return [
			'file' => $this->payload,
			'fields' => $this->markers,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getCost(): int
	{
		if (!is_null($this->customCost))
		{
			return $this->customCost;
		}

		if (Bitrix24::isDemoLicense())
		{
			return self::DEFAULT_USAGE_COST_DEMO_PLAN;
		}

		return self::DEFAULT_USAGE_COST;
	}

	/**
	 * @inheritDoc
	 */
	public function pack(): string
	{
		return json_encode([
			'data' => $this->payload,
			'markers' => $this->markers,
			'role' => $this->role?->getCode(),
			static::PROPERTY_CUSTOM_COST => $this->customCost
		]);
	}

	/**
	 * @inheritDoc
	 */
	public static function unpack(string $packedData): ?static
	{
		$unpackedData = json_decode($packedData, true);

		$data = $unpackedData['data'] ?? '';
		$markers = $unpackedData['markers'] ?? [];
		$role = $unpackedData['role'] ?? null;

		$payload = (new self($data))->setMarkers($markers)->setRole(Role::get($role));
		static::setCustomCost($payload, $unpackedData);

		return $payload;
	}
}
