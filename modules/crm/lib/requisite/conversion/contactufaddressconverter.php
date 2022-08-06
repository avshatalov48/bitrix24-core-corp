<?php

namespace Bitrix\Crm\Requisite\Conversion;

class ContactUfAddressConverter extends EntityUfAddressConverter
{
	const LOGGER_TAG = 'CRM_CONTACT_UF_ADDRESS_CONVERTER';

	function __construct(
		int $sourceEntityTypeId, string $sourceUserFieldName,
		int $presetId = 0, bool $enablePermissionCheck = true
	)
	{
		parent::__construct(
			\CCrmOwnerType::Contact,
			$sourceEntityTypeId, $sourceUserFieldName,
			$presetId, $enablePermissionCheck
		);
	}
}