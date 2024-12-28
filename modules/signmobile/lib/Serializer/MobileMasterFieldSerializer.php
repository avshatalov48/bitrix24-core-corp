<?php

namespace Bitrix\SignMobile\Serializer;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Item\Field;
use Bitrix\Sign\Item\FieldCollection;
use Bitrix\Sign\Type\FieldType;

class MobileMasterFieldSerializer
{
	public function serialize(FieldCollection $collection): array
	{
		return array_map(fn(Field $field): array => $this->serializeField($field), $collection->toArray());
	}

	protected function serializeField(Field $field): array
	{
		$array = [
			'uid' => $field->name,
			'name' => $field->label,
			'type' => $this->getType($field->type),
			'value' => $this->getValue($field->type, $field->values?->getFirst()?->text),
			'required' => $field->required ?? true,
		];

		if ($field->items)
		{
			$array['items'] = $this->serializeItems($field->items);
		}

		if ($field->subfields)
		{
			$array['subfields'] = $this->serialize($field->subfields);
		}

		return $array;
	}

	private function getType(string $type): string
	{
		return match ($type)
		{
			FieldType::DATE => 'date',
			FieldType::LIST => 'list',
			FieldType::ADDRESS => 'address',
			default => 'string',
		};
	}

	private function serializeItems(Field\ItemCollection $collection): array
	{
		return array_map(
			fn(Field\Item $item): array => ['label' => $item->value, 'code' => $item->id],
			$collection->toArray(),
		);
	}

	private function getValue(string $type, ?string $value): int|string
	{
		return match ($type)
		{
			FieldType::DATE => $this->formatDate($value),
			default => $value ?? '',
		};
	}

	private function formatDate(?string $value): int|string
	{
		return $value !== null
			? (new DateTime($value))->getTimestamp()
			: ''
			;
	}
}