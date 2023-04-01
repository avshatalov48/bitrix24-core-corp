<?php

namespace Bitrix\Crm\Service\Timeline\Item;

class Payload implements \JsonSerializable
{
	private array $data = [];

	public function jsonSerialize(): array
	{
		return $this->data;
	}

	public function addValueInt(string $name, int $value): self
	{
		$this->addValue($name, $value);

		return $this;
	}

	public function addValueString(string $name, string $value): self
	{
		$this->addValue($name, $value);

		return $this;
	}

	public function addValueBoolean(string $name, bool $value): self
	{
		$this->addValue($name, $value);

		return $this;
	}

	public function addValueArrayOfInt(string $name, array $value): self
	{
		$this->addValue($name, array_map('intval', $value));

		return $this;
	}

	protected function addValue(string $name, $value): self
	{
		$this->data[$name] = $value;

		return $this;
	}
}
