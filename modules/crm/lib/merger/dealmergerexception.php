<?php
namespace Bitrix\Crm\Merger;

use Bitrix\Main;
Main\Localization\Loc::loadMessages(__FILE__);

class DealMergerException extends EntityMergerException
{
	const CONFLICT_OCCURRED_CATEGORY    = 600;
	const CONFLICT_OCCURRED_RECURRENCE  = 610;

	protected function getMessageByCode($code)
	{
		if($code === self::CONFLICT_OCCURRED_CATEGORY)
		{
			return 'Deals must belong to same category.';
		}
		elseif($code === self::CONFLICT_OCCURRED_RECURRENCE)
		{
			return 'Deals must have same recurrence statuses.';
		}
		return parent::getMessageByCode($code);
	}

	public function getLocalizedMessage()
	{
		switch($this->getCode())
		{
			case self::CONFLICT_OCCURRED_CATEGORY:
				return Main\Localization\Loc::getMessage(
					'CRM_DEAL_MERGER_EXCEPTION_CONFLICT_OCCURRED_CATEGORY'
				);
			case self::CONFLICT_OCCURRED_RECURRENCE:
				return Main\Localization\Loc::getMessage(
					'CRM_DEAL_MERGER_EXCEPTION_CONFLICT_OCCURRED_RECURRENCE'
				);
		}
		return parent::getLocalizedMessage();
	}
}
