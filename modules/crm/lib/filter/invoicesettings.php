<?php
namespace Bitrix\Crm\Filter;
class InvoiceSettings extends EntitySettings
{
	const FLAG_RECURRING = 1;

	/**
	 * Get Entity Type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Invoice;
	}

	/**
	 * Get User Field Entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return \CCrmInvoice::GetUserFieldEntityID();
	}
}