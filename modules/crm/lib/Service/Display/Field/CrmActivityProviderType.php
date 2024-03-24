<?php

namespace Bitrix\Crm\Service\Display\Field;

use Bitrix\Crm\Service\Display\Options;

class CrmActivityProviderType extends BaseSimpleField
{
	public const TYPE = 'crm_activity_provider_type';

	protected function render(Options $displayOptions, $entityId, $value): string
	{
		throw new Exception('Multiple values are not supported');
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		[$providerId, $providerTypeId] = explode('-', $fieldValue);

		return \CAllCrmActivity::GetProviderById($providerId)::getTypeName($providerTypeId);
	}
}
