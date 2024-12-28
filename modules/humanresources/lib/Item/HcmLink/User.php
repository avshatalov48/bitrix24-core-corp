<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use ReturnTypeWillChange;

class User implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $name,
		public string $avatarLink,
		public string $position,
	)
	{}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'avatarLink' => $this->avatarLink,
			'position' => $this->position,
		];
	}


	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}