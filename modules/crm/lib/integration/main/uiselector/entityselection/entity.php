<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection;

class Entity
{
	protected string $type;
	protected string $prefix;
	protected string $fullPrefix;
	protected array $options = [];

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setPrefix(string $prefix): self
	{
		$this->prefix = $prefix;

		return $this;
	}

	public function getPrefix(): string
	{
		return $this->prefix;
	}

	public function setFullPrefix(string $fullPrefix): self
	{
		$this->fullPrefix = $fullPrefix;

		return $this;
	}

	public function getFullPrefix(): string
	{
		return $this->fullPrefix;
	}

	public function setOption(array $options): self
	{
		$this->options = $options;

		return $this;
	}

	public function getOptions(): array
	{
		return $this->options;
	}
}
