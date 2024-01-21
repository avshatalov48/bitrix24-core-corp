<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class TextField extends BaseField
{
	protected bool $isHtmlField;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isHtmlField = $property['Type'] === 'S:HTML';
	}

	public function getType(): string
	{
		return 'text';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): string
	{
		if ($this->isHtmlField && is_array($value) && isset($value['TEXT']))
		{
			$value = $value['TEXT'];
		}

		return $this->convertToString($value);
	}

	protected function convertToWebType($value): string|array
	{
		$value = $this->convertToString($value);

		return $this->isHtmlField ? ['TYPE' => 'text', 'TEXT' => $value] : $value;
	}

	protected function convertToString($value): string
	{
		return \CBPHelper::hasStringRepresentation($value) ? (string)$value : '';
	}
}
