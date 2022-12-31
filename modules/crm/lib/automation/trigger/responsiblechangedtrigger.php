<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class ResponsibleChangedTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return in_array($entityTypeId, [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead, \CCrmOwnerType::Order], true);
	}

	public static function getCode()
	{
		return 'RESP_CHANGED';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_RESPONSIBLE_CHANGED_NAME_1');
	}

	public static function getGroup(): array
	{
		return ['elementControl'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_RESPONSIBLE_CHANGED_DESCRIPTION') ?? '';
	}
}