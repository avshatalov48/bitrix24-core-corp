<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\Date;

class DateField extends SingleField
{
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly bool $isEditable,
		public readonly bool $isShowAlways,
		public readonly mixed $value = null,
		public readonly string $format = '',
	)
	{
	}

	public static function createByData(array $fieldData, mixed $value) : static
	{
		if (is_string($value) && !empty($value))
		{
			try
			{
				$value = new Date($value);
			}
			catch (ObjectException)
			{
			}
		}

		$format = isset($fieldData['data']['dateViewFormat']) && is_string($fieldData['data']['dateViewFormat'])
			? $fieldData['data']['dateViewFormat']
			: '';

		return new static(
			id: $fieldData['name'],
			title: $fieldData['title'],
			isEditable: $fieldData['editable'] ?? false,
			isShowAlways: $fieldData['showAlways'] ?? false,
			value: $value,
			format: $format,
		);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getValue(): ?Date
	{
		return $this->isValid() ? $this->value : null;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function isEditable(): bool
	{
		return $this->isEditable;
	}

	public function isShowAlways(): bool
	{
		return $this->isShowAlways;
	}

	public function isValid(mixed $value = null): bool
	{
		$value ??= $this->value;

		return $value instanceof Date;
	}
}
