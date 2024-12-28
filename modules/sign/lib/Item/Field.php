<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\FieldType;

class Field implements Contract\Item
{
	public function __construct(
		public int $blankId,
		public int $party,
		public string $type,
		public string $name,
		public ?string $label,
		public ?string $connectorType = null,
		public ?string $entityType = null,
		public ?string $entityCode = null,
		public ?int $id = null,
		public ?string $hint = null,
		public ?string $placeHolder = null,
		public ?Item\Field\ValueCollection $values = null,
		public ?Item\Field\ItemCollection $items = null,
		public ?FieldCollection $subfields = null,
		public ?bool $required = null,
	) {}

	public function isTypeFile(): bool
	{
		return in_array($this->type, [FieldType::FILE, FieldType::SIGNATURE, FieldType::STAMP], true);
	}

	public function isFilled(): bool
	{
		$value = $this->values->toArray()[0] ?? null;
		if (!$value)
		{
			return false;
		}

		if ($this->isTypeFile())
		{
			return $value->fileId > 0;
		}

		return $value->text !== '' && $value->text !== null;
	}

	public function replaceValueIfPresent(?Item\Field\Value $fieldValue): static
	{
		if ($fieldValue)
		{
			$this->values = new Item\Field\ValueCollection($fieldValue);
		}

		return $this;
	}
}