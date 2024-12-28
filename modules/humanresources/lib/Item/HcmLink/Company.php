<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class Company implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public string $code,
		public int $myCompanyId,
		public string $title,
		public array $data = [],
		public ?DateTime $createdAt = null,
		public ?int $id = null,
	)
	{
	}

	public function toArray():array
	{
		return [
			'id' => $this->id,
			'code' => $this->code,
			'myCompanyId' => $this->myCompanyId,
			'title' => $this->title,
			'data' => $this->data,
			'createdAt' => ($this->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
		];
	}

	public function jsonSerialize():array
	{
		return $this->toArray();
	}
}