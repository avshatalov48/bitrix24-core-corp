<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaymentTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Order);
	}

	public static function getCode()
	{
		return 'PAYMENT';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_PAYMENT_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_PAYMENT_DESCRIPTION') ?? '';
	}
}