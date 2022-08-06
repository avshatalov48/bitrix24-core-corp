<?php

namespace Bitrix\Crm\Requisite\Conversion;

class ContactAddressConverter extends EntityAddressConverter
{
	const LOGGER_TAG = 'CRM_CONTACT_ADDRESS_CONVERTER';

	public function __construct(int $presetId = 0, bool $enablePermissionCheck = true)
	{
		parent::__construct(\CCrmOwnerType::Contact, $presetId, $enablePermissionCheck);
	}
}