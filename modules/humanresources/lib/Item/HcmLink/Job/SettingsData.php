<?php

namespace Bitrix\HumanResources\Item\HcmLink\Job;

class SettingsData implements \JsonSerializable
{
	public function __construct(
		public ?array $documentIdByEmployeeId = null,
	)
	{
	}

	public static function fromArray(array $data): static
	{
		return new self(
			documentIdByEmployeeId: $data['documentIdByEmployeeId'] ?? null,
		);
	}

	public function toArray(): array
	{
		$data = [];

		if (!empty($this->documentIdByEmployeeId))
		{
			$data['documentIdByEmployeeId'] = $this->documentIdByEmployeeId;
		}

		return $data;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}