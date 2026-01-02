<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

abstract class SingleField implements Field
{
	public static function createByData(array $fieldData, mixed $value): static
	{
		return new static(
			id: $fieldData['name'],
			title: $fieldData['title'],
			isEditable: $fieldData['editable'] ?? false,
			isShowAlways: $fieldData['showAlways'] ?? false,
			value: $value,
		);
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getType(),
			'isMultiple' => $this->isMultiple(),
			...get_object_vars($this),
		];
	}

	public function isMultiple(): bool
	{
		return false;
	}

	public function getType(): string
	{
		$class = static::class;
		$parts = explode('\\', $class);
		$className = end($parts);
		$className = str_replace('Field', '', $className);

		return strtolower($className);
	}
}
