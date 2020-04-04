<?php
namespace Bitrix\Crm\Filter;
class LeadSettings extends EntitySettings
{
	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmLead::GetUserFieldEntityID();
	}
}