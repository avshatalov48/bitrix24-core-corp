<?php
namespace Bitrix\Crm\Filter;
class OrderSettings extends EntitySettings
{
	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Order;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \Bitrix\Crm\Order\Manager::getUfId();
	}
}