<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Phone implements Arrayable, JsonSerializable
{
	public function __construct(
		public string $value,
		public string $href,
		public string $onclick,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'value' => $this->value,
			'href' => $this->href,
			'onclick' => $this->onclick,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
