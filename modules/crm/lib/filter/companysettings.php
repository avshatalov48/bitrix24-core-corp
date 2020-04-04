<?php
namespace Bitrix\Crm\Filter;
class CompanySettings extends EntitySettings
{
	const FLAG_ENABLE_ADDRESS = 1;

	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmCompany::GetUserFieldEntityID();
	}
}