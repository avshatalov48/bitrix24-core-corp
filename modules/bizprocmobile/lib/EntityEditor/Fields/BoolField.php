<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class BoolField extends BaseField
{
	public function getType(): string
	{
		return 'boolean';
	}

	public function getConfig(): array
	{
		return [];
	}

	protected function convertToMobileType($value): bool
	{
		return $value === 'Y';
	}

	protected function convertToWebType($value): string
	{
		return $value === 'true' ? 'Y' : 'N';
	}
}
