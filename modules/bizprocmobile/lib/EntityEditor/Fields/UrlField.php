<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class UrlField extends BaseField
{
	public function getType(): string
	{
		return 'url';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): string
	{
		return is_string($value) ? $value : '';
	}

	protected function convertToWebType($value): string
	{
		return $this->convertToMobileType($value);
	}
}
