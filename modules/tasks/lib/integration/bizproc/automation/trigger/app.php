<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class App extends Base
{
	public static function isEnabled()
	{
		return (Main\Loader::includeModule('rest'));
	}

	public static function getCode()
	{
		return 'APP';
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_APP_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		$appA = (int)$trigger['APPLY_RULES']['APP_ID'];
		$codeA = (string)$trigger['APPLY_RULES']['CODE'];

		$appB = (int)$this->getInputData('APP_ID');
		$codeB = (string)$this->getInputData('CODE');

		return ($appA === $appB && $codeA === $codeB);
	}
}
