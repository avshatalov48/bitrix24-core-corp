<?php
namespace Bitrix\Crm\Filter;
class ContactSettings extends EntitySettings
{
	const FLAG_ENABLE_ADDRESS = 1;

	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Contact;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmContact::GetUserFieldEntityID();
	}
}