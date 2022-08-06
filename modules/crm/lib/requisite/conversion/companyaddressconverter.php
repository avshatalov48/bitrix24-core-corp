<?php

namespace Bitrix\Crm\Requisite\Conversion;

class CompanyAddressConverter extends EntityAddressConverter
{
	const LOGGER_TAG = 'CRM_COMPANY_ADDRESS_CONVERTER';

	public function __construct(int $presetId = 0, bool $enablePermissionCheck = true)
	{
		parent::__construct(\CCrmOwnerType::Company, $presetId, $enablePermissionCheck);
	}
}