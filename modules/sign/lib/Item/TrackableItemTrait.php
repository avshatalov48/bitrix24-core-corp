<?php

namespace Bitrix\Sign\Item;

use UnitEnum;

trait TrackableItemTrait
{
	private array $original = [];

	public function initOriginal(): void
	{
		$reflection = new \ReflectionClass($this);
		$props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		$excludedPropertiesMap = array_flip($this->getExcludedFromCopyProperties());
		foreach ($props as $prop)
		{
			$name = $prop->getName();
			if (!$prop->isStatic() && !array_key_exists($name, $excludedPropertiesMap))
			{
				$value = $prop->getValue($this);
				$this->original[$name] = is_object($value) && !$value instanceof UnitEnum ? clone $value : $value;
			}
		}
	}

	public function getOriginal(): array
	{
		return $this->original;
	}

	protected function getExcludedFromCopyProperties(): array
	{
		return [
			'id',
		];
	}
}