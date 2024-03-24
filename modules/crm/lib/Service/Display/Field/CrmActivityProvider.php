<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class CrmActivityProvider extends BaseSimpleField
{
	public const TYPE = 'crm_activity_provider';

	protected function render(Options $displayOptions, $entityId, $value): string
	{
		throw new Exception('Multiple values are not supported');
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		$provider = \CAllCrmActivity::GetProviderById($fieldValue);
		if (!$provider)
		{
			return '';
		}

		return htmlspecialcharsbx($provider::getName());
	}
}
