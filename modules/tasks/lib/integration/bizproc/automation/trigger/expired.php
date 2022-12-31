<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Expired extends Base
{
	public static function getCode()
	{
		return 'EXPIRED';
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_EXPIRED_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_EXPIRED_DESCRIPTION') ?? '';
	}
}