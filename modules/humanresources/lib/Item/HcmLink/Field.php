<?php

namespace Bitrix\HumanResources\Item\HcmLink;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\HcmLink\FieldEntityType;
use Bitrix\HumanResources\Type\HcmLink\FieldType;
use Bitrix\Main\Type\Contract\Arrayable;

class Field implements Item, Arrayable, \JsonSerializable
{
	public function __construct(
		public int $companyId,
		public string $field,
		public string $title,
		public FieldType $type,
		public FieldEntityType $entityType,
		public int $ttl,
		public ?int $id = null,
	)
	{
	}
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'companyId' => $this->companyId,
			'field' => $this->field,
			'title' => $this->title,
			'type' => $this->type->name,
			'entityType' => $this->entityType->name,
			'ttl' => $this->ttl,
		];
	}
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}