<?php

namespace Bitrix\AI;

class Result
{
	public function __construct(
		private mixed $rawData,
		private ?string $prettifyData,
		private bool $cached = false,
		private ?array $jsonData = [],
	) {}

	public function getRawData(): mixed
	{
		return $this->rawData;
	}

	public function getPrettifiedData(): ?string
	{
		return $this->prettifyData;
	}

	public function getJsonData(): ?array
	{
		return $this->jsonData;
	}

	public function isCached(): bool
	{
		return $this->cached;
	}
}