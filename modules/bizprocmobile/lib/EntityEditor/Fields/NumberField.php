<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class NumberField extends BaseField
{
	protected bool $isInteger;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isInteger = $property['Type'] === 'int';
	}

	public function getType(): string
	{
		return 'number';
	}

	public function getConfig(): array
	{
		return [
			'type' => $this->isInteger ? 'int' : 'double',
			'precision' => $this->isInteger ? 0 : 15,
		];
	}

	protected function convertToMobileType($value): ?string
	{
		if (is_numeric($value))
		{
			return (string)($this->isInteger ? (int)$value : (double)$value);
		}

		return null;
	}

	protected function convertToWebType($value): ?string
	{
		return $this->convertToMobileType($value);
	}
}
