<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CallTrigger extends BaseTrigger
{
	protected static function hasLines()
	{
		return (Loader::includeModule('voximplant') && \CVoxImplantHttp::VERSION >= 19);
	}

	public static function getCode()
	{
		return 'CALL';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_CALL_NAME_1');
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

	protected static function getPropertiesMap(): array
	{
		if (!static::hasLines())
		{
			return [];
		}

		return [
			[
				'Id' => 'LINE_NUMBER',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_CALL_PROPERTY_LINE'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_CALL_DEFAULT_LINE'),
				'Settings' => [
					'OptionsLoader' => [
						'type' => 'component',
						'component' => 'bitrix:crm.automation',
						'action' => 'getCallLines',
						'mode' => 'class',
					],
				],
			]
		];
	}

	public static function getGroup(): array
	{
		return ['clientCommunication'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_CALL_DESCRIPTION') ?? '';
	}
}