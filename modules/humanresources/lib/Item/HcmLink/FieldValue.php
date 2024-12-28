<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class FieldValue implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int $employeeId,
		public int $fieldId,
		public string $value,
		public ?DateTime $createdAt = null,
		public ?DateTime $expiredAt = null,
		public ?int $id = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'employeeId' => $this->employeeId,
			'fieldId' => $this->fieldId,
			'value' => $this->value,
			'createdAt' => ($this->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
			'expiredAt' => ($this->expiredAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}