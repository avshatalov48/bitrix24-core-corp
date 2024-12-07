<?php

namespace Bitrix\Sign\Callback;

use Bitrix\Main;

class Message
{
	public const Type = '';

	protected array $data = [];
	protected string $debugTraceId = '';

	public function validate(): Main\Result
	{
		return new Main\Result;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getDebugTraceId(),
			'type' => static::Type,
			'data' => $this->getData(),
		];
	}

	public function setData(array $data): self
	{
		$this->data = $data;
		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function getDebugTraceId(): string
	{
		return $this->debugTraceId;
	}

	public function setDebugTraceId(string $debugTraceId): self
	{
		$this->debugTraceId = $debugTraceId;
		return $this;
	}

	public function getVersion(): ?int
	{
		return (int) $this->data['version'] ?? null;
	}

	public function setVersion(int $version): self
	{
		$this->data['version'] = $version;
		return $this;
	}
}