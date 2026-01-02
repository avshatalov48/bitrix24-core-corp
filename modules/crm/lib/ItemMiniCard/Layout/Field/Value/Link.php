<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value;

use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Link implements Arrayable, JsonSerializable
{
	public function __construct(
		public string $href,
		public string $title,
		public string $target,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'href' => $this->href,
			'title' => $this->title,
			'target' => $this->target,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
