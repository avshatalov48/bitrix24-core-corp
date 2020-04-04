<?php
namespace Bitrix\Crm\Merger;

use Bitrix\Main;
Main\Localization\Loc::loadMessages(__FILE__);

class LeadMergerException extends EntityMergerException
{
	const CONFLICT_OCCURRED_RETURN_CUSTOMER = 700;

	protected function getMessageByCode($code)
	{
		if($code === self::CONFLICT_OCCURRED_RETURN_CUSTOMER)
		{
			return 'Leads must have same return customer statuses.';
		}
		return parent::getMessageByCode($code);
	}

	public function getLocalizedMessage()
	{
		switch($this->getCode())
		{
			case self::CONFLICT_OCCURRED_RETURN_CUSTOMER:
				return Main\Localization\Loc::getMessage(
					'CRM_LEAD_MERGER_EXCEPTION_CONFLICT_RETURN_CUSTOMER'
				);
		}
		return parent::getLocalizedMessage();
	}
}
