<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DeductedTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Order);
	}

	public static function getCode()
	{
		return 'DEDUCTED';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DEDUCTED_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DEDUCTED_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['delivery'];
	}
}