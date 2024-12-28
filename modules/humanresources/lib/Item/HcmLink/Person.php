<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class Person implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int $companyId,
		public string $code,
		public string $title,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
		public ?int $userId = null,
		public ?int $id = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'companyId' => $this->companyId,
			'userId' => $this->userId,
			'person' => $this->code,
			'title' => $this->title,
			'createdAt' => ($this->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
			'updatedAt' => ($this->updatedAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}