<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class StringField extends BaseField
{
	public function getType(): string
	{
		return 'string';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): string
	{
		return \CBPHelper::hasStringRepresentation($value) ? (string)$value : '';
	}

	protected function convertToWebType($value): string
	{
		return $this->convertToMobileType($value);
	}
}
