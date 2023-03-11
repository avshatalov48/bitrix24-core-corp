<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class WebHook extends Base
{
	public static function isEnabled()
	{
		return (Main\Loader::includeModule('rest'));
	}

	public static function getCode()
	{
		return 'WEBHOOK';
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_WEBHOOK_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['code'])
		)
		{
			return (string)$trigger['APPLY_RULES']['code'] === (string)$this->getInputData('code');
		}
		return true;
	}
}
