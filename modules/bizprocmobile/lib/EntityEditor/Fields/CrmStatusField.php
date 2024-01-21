<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Loader;

class CrmStatusField extends BaseField
{
	public function getType(): string
	{
		return 'crm_status';
	}

	public function getConfig(): array
	{
		$items = [];

		if (
			isset($this->property['Options'])
			&& is_string($this->property['Options'])
			&& Loader::includeModule('crm')
		)
		{
			$statuses = \CCrmStatus::GetStatusList($this->property['Options']);
			if ($statuses)
			{
				$items = array_map(
					static fn($key, $value) => ['value' => $key, 'name' => $value],
					array_keys($statuses),
					$statuses,
				);
			}
		}

		return ['items' => $items];
	}

	protected function convertToMobileType($value): mixed
	{
		return $value;
	}

	protected function convertToWebType($value): mixed
	{
		return $this->convertToMobileType($value);
	}
}
