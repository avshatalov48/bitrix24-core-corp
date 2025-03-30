<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Prompt\Role;

class Text extends Payload implements IPayload
{
	public function __construct(
		protected string $payload
	) {}

	/**
	 * @inheritDoc
	 */
	public function getData(): string
    {
		return (new Formatter($this->payload, $this->engine))->format($this->markers);
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

		$payload = (new static($data))->setMarkers($markers)->setRole(Role::get($role));

		static::setCustomCost($payload, $unpackedData);

		return $payload;
	}
}
