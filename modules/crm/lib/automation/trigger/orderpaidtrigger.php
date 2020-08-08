<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderPaidTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return $entityTypeId === \CCrmOwnerType::Deal;
	}

	public static function getCode()
	{
		return 'ORDER_PAID';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_ORDER_PAID_NAME');
	}
}