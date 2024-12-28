<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type\Field\PortableFieldType;

abstract class PortableField implements Item, \JsonSerializable
{
	abstract public function getType(): PortableFieldType;

	abstract public function getId(): string;

	public function jsonSerialize(): array
	{
		return [
			'type' => $this->getType()->value,
			'id' => $this->getId(),
		];
	}
}