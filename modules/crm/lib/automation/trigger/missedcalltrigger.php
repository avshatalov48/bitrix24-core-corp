<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class MissedCallTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return (Loader::includeModule('voximplant') && \CVoxImplantHttp::VERSION >= 19);
	}

	public static function getCode()
	{
		return 'MISSED_CALL';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_MISSED_CALL_NAME_1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (empty($trigger['APPLY_RULES']['LINE_NUMBER']))
		{
			return true;
		}

		$lineA = (string) $trigger['APPLY_RULES']['LINE_NUMBER'];
		$lineB = (string) $this->getInputData('LINE_NUMBER');

		return ($lineA === $lineB);
	}

	public static function toArray()
	{
		$result = parent::toArray();
		$result['LINES'] = array_values(\CVoxImplantConfig::GetLines(false, true));
		return $result;
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_MISSED_CALL_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}
}