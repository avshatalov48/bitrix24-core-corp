<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Product implements Arrayable, JsonSerializable
{
	public function __construct(
		public string $title,
		public string $url,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'url' => $this->url,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
