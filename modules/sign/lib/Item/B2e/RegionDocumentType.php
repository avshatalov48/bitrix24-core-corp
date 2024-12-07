<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Sign\Contract\Item;

class RegionDocumentType implements Item, \JsonSerializable, Arrayable
{
	public function __construct(
		public string $code,
		public string $description,
	) {}

	public function toArray(): array
	{
		return [
			'code' => $this->code,
			'description' => $this->description,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
