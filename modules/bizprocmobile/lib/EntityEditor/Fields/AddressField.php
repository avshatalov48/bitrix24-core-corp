<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Loader;

class AddressField extends BaseField
{
	protected bool $isFileManModuleIncluded;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isFileManModuleIncluded = Loader::includeModule('fileman');
	}

	public function getType(): string
	{
		return 'address';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): array
	{
		if (is_string($value) && $this->isFileManModuleIncluded)
		{
			return \Bitrix\Fileman\UserField\Types\AddressType::parseValue($value);
		}

		return ['', '', null, null];
	}

	protected function convertToWebType($value): mixed
	{
		if ($this->fieldTypeObject)
		{
			$errors = [];
			$value = $this->fieldTypeObject->extractValue(
				['Field' => 'field'],
				['field' => $value],
				$errors
			);

			if ($errors)
			{
				return null;
			}

			if (is_array($value) && $this->isMultiple())
			{
				return $value[0] ?? null;
			}
		}

		return is_string($value) ? $value : null;
	}
}
