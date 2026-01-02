<?php

namespace Bitrix\Crm\ItemMiniCard\Layout;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

abstract class AbstractComponent implements Arrayable, JsonSerializable
{
	abstract public function getName(): string;

	abstract public function getProps(): array;

	public function toArray(): array
	{
		return [
			'componentName' => $this->getName(),
			'componentProps' => $this->getProps(),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
