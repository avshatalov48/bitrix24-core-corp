<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Type\Field\PortableFieldType;

class PortableUserField extends PortableField
{
	public function __construct(
		public readonly string $entityId,
		public readonly string $id,
		public readonly array $structure,
		public readonly array $items = [],
	) {}

	public function getType(): PortableFieldType
	{
		return PortableFieldType::USER_FIELD;
	}

	public function getId(): string
	{
		return "USER_FIELD_$this->id";
	}

	public function jsonSerialize(): array
	{
		return parent::jsonSerialize()
			+ [
				'entityId' => $this->entityId,
				'structure' => $this->structure,
				'items' => $this->items,
			]
		;
	}

	public function getFieldName(): string
	{
		return (string)($this->structure['FIELD_NAME'] ?? '');
	}
}