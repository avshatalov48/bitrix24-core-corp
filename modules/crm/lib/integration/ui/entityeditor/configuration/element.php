<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\Configuration;

class Element
{
	public function __construct(
		private string $name,
		private ?bool $isShowAlways = null,
		private ?array $options = null,
	)
	{
	}

	public static function fromArray(array $element): ?self
	{
		$name = $element['name'] ?? null;
		if (empty($name))
		{
			return null;
		}

		$isShowAlways = (bool)($element['optionFlags'] ?? $element['isShowAlways'] ?? null);
		$options = is_array($element['options'] ?? null) ? $element['options'] : null;

		return new self($name, $isShowAlways, $options);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		if (empty($name))
		{
			return $this;
		}

		$this->name = $name;

		return $this;
	}

	public function isShowAlways(): bool
	{
		return $this->isShowAlways ?? false;
	}

	public function setShowAlways(?bool $isShowAlways): self
	{
		$this->isShowAlways = $isShowAlways;

		return $this;
	}

	public function getOptions(): array
	{
		return $this->options ?? [];
	}

	public function setOption(string $name, mixed $value): self
	{
		$this->options[$name] = $value;

		return $this;
	}

	public function clearOptions(): self
	{
		$this->options = [];

		return $this;
	}

	public function toArray(): array
	{
		$result = [
			'name' => $this->name,
		];

		if ($this->isShowAlways !== null)
		{
			$result['optionFlags'] = $this->isShowAlways ? "1" : "0";
		}

		if ($this->options !== null)
		{
			$result['options'] = $this->options;
		}

		return $result;
	}
}
