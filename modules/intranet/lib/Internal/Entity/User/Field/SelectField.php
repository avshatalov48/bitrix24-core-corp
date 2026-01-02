<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

use Bitrix\Main\ArgumentException;

class SelectField extends SingleField
{
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly bool $isEditable,
		public readonly bool $isShowAlways,
		/** @var $items array<string, string> */
		public readonly array $items,
		public readonly mixed $value = null,
	)
	{
	}

	/**
	 * @throws ArgumentException
	 */
	public static function createByData(array $fieldData, mixed $value): static
	{
		$items = [];

		if (
			$fieldData['type'] === 'enumeration'
			&& isset($fieldData['data']['fieldInfo']['ENUM'])
			&& is_array($fieldData['data']['fieldInfo']['ENUM'])
		)
		{
			foreach ($fieldData['data']['fieldInfo']['ENUM'] as $enum)
			{
				if (isset($enum['ID'], $enum['VALUE']))
				{
					$items[(int)$enum['ID']] = $enum['VALUE'];
				}
			}
		}
		elseif (!empty($fieldData['data']['items']))
		{
			foreach ($fieldData['data']['items'] as $item)
			{
				$items[$item['VALUE']] = $item['NAME'];
			}
		}
		else
		{
			throw new ArgumentException('Selectable user field required items');
		}

		return new SelectField(
			id: $fieldData['name'],
			title: $fieldData['title'],
			isEditable: $fieldData['editable'] ?? false,
			isShowAlways: $fieldData['showAlways'] ?? false,
			items: $items,
			value: $value,
		);
	}

	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return string|int|null - selected item id or null if value is not valid
	 */
	public function getValue(): int|string|null
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

		return (is_int($value) || is_string($value))
			&& array_key_exists($value, $this->items);
	}
}
