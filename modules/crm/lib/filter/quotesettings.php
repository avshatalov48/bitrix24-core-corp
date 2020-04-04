<?php
namespace Bitrix\Crm\Filter;
class QuoteSettings extends EntitySettings
{
	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Quote;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmQuote::GetUserFieldEntityID();
	}
}