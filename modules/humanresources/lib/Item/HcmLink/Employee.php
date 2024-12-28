<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class Employee implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int $personId,
		public string $code,
		public array $data,
		public ?DateTime $createdAt = null,
		public ?int $id = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'personId' => $this->personId,
			'code' => $this->code,
			'data' => $this->data,
			'createdAt' => ($this->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}