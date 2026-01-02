<?php declare(strict_types=1);

namespace Bitrix\AI\Payload\JsonSchema;


class Constraints
{
	private array $constraints = [];

	public function setEnum(array $values): self
	{
		$this->constraints['enum'] = $values;

		return $this;
	}

	public function setDescription(string $description): self
	{
		$this->constraints['description'] = $description;

		return $this;
	}

	public function setItems(array $values): self
	{
		$this->constraints['items'] = $values;

		return $this;
	}

	protected function set(string $key, $value): self
	{
		$this->constraints[$key] = $value;

		return $this;
	}

	public function toArray(): array
	{
		return $this->constraints;
	}
}