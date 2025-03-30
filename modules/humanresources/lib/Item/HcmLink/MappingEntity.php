<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use ReturnTypeWillChange;

class MappingEntity implements Item, Arrayable, \JsonSerializable
{
	/**
	 * @param int $id
	 * @param string $name
	 * @param string $avatarLink
	 * @param string $position
	 * @param ?int $suggestId
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $avatarLink,
		public string $position,
		public ?int $suggestId = null,
	)
	{}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'avatarLink' => $this->avatarLink,
			'position' => $this->position,
			'suggestId' => $this->suggestId,
		];
	}


	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}