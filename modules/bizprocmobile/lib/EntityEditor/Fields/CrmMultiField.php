<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Loader;

class CrmMultiField extends BaseField
{
	protected string $propertyType;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->propertyType = $property['Type'] ?? '';
	}

	public function isMultiple(): bool
	{
		return true;
	}

	public function getType(): string
	{
		return 'combined';
	}

	public function getConfig(): array
	{
		$items = [];
		$menuItems = [];
		if (!empty($this->propertyType) && Loader::includeModule('crm'))
		{
			$multiFieldItems = \CCrmFieldMulti::GetEntityTypeList(mb_strtoupper($this->propertyType), false);
			foreach ($multiFieldItems as $key => $value)
			{
				$items[] = ['NAME' => $value, 'VALUE' => $key];
				$menuItems[] = ['title' => $value, 'id' => $key];
			}
		}

		return [
			'type' => $this->propertyType,
			'items' => $items,
			'primaryField' => [
				'id' => 'VALUE',
				'title' => $this->getTitle(),
				'type' => $this->propertyType,
			],
			'secondaryField' => [
				'id' => 'VALUE_TYPE',
				'type' => 'menu-select',
				'required' => true,
				'showRequired' => false,
				'config' => [
					'menuTitle' => $this->getTitle(),
					'items' => $menuItems,
				],
			],
		];
	}

	protected function convertToMobileType($value): ?array
	{
		if (is_array($value))
		{
			$key = array_key_first($value);

			return ['value' => array_merge($value[$key], ['id' => $key])];
		}

		return null;
	}

	public function convertValueToMobile(): ?array
	{
		$value = $this->value;
		if (is_array($value) && isset($value[mb_strtoupper($this->propertyType)]))
		{
			$value = $value[mb_strtoupper($this->propertyType)];
			$multiValue = [];
			if (is_array($value))
			{
				foreach ($value as $key => $singleValue)
				{
					$multiValue[] = $this->convertToMobileType([$key => $singleValue]);
				}
			}

			return $multiValue;
		}

		return null;
	}

	protected function convertToWebType($value): ?array
	{
		if (
			is_array($value)
			&& isset($value['VALUE'], $value['VALUE_TYPE'])
			&& $value['VALUE'] !== ''
			&& $value['VALUE'] !== 'undefined'
		)
		{
			return ['VALUE' => $value['VALUE'], 'VALUE_TYPE' => $value['VALUE_TYPE']];
		}

		return null;
	}

	public function convertValueToWeb(): array
	{
		$value = $this->value;

		$multiValues = [];
		if (is_array($value))
		{
			foreach ($value as $singleValue)
			{
				if (isset($singleValue['id'], $singleValue['value']))
				{
					$id = $singleValue['value']['id'] ?? $singleValue['id'];
					$realValue = $this->convertToWebType($singleValue['value']);
					if ($realValue)
					{
						$multiValues[$id] = $realValue;
					}
				}
			}
		}

		return [mb_strtoupper($this->propertyType) => $multiValues];
	}
}
