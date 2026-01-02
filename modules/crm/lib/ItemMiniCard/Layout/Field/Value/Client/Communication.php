<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Communication implements JsonSerializable, Arrayable
{
	public function __construct(
		public string $typeId,
		public string $valueTypeCaption,
		public string $valueType,
		public string $value,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'typeId' => $this->typeId,
			'valueTypeCaption' => $this->valueTypeCaption,
			'valueType' => $this->valueType,
			'value' => $this->value,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
