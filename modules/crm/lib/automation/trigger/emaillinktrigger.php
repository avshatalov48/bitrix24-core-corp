<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EmailLinkTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'EMAIL_LINK';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_LINKHOOK_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['url'])
		)
		{
			return (string)$trigger['APPLY_RULES']['url'] === (string)$this->getInputData('URL');
		}
		return true;
	}


}