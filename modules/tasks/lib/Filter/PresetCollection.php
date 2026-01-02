<?php

namespace Bitrix\Tasks\Filter;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Filter\Preset\AbstractPreset;

class PresetCollection implements Arrayable
{
	private array $items = [];

	public function add(AbstractPreset $preset): self
	{
		$preset->setSort(count($this->items) + 1);
		$this->items[$preset->getCode()] = $preset->toArray();

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}
