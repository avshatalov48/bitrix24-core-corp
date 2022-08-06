<?php

namespace Bitrix\Crm\Requisite\Conversion;

class CompanyUfAddressConverter extends EntityUfAddressConverter
{
	const LOGGER_TAG = 'CRM_COMPANY_UF_ADDRESS_CONVERTER';

	function __construct(
		int $sourceEntityTypeId, string $sourceUserFieldName,
		int $presetId = 0, bool $enablePermissionCheck = true
	)
	{
		parent::__construct(
			\CCrmOwnerType::Company,
			$sourceEntityTypeId, $sourceUserFieldName,
			$presetId, $enablePermissionCheck
		);
	}
}