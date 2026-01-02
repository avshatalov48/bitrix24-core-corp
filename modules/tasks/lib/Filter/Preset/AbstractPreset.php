<?php

namespace Bitrix\Tasks\Filter\Preset;

use Bitrix\Main\Type\Contract\Arrayable;

abstract class AbstractPreset implements Arrayable
{
	abstract public function getCode(): string;
	abstract protected function getName(): ?string;
	abstract protected function getFields(): array;

	protected bool $isDefault = false;
	protected int $sort = 0;

	public function setIsDefault(bool $isDefault): static
	{
		$this->isDefault = $isDefault;

		return $this;
	}

	public function setSort(int $sort): static
	{
		$this->sort = $sort;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'default' => $this->isDefault,
			'fields' => $this->getFields(),
			'sort' => $this->sort,
		];
	}
}
