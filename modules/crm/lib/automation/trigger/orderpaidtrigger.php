<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderPaidTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return
			$entityTypeId === \CCrmOwnerType::Deal
			|| \CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId)
		;
	}

	public static function getCode()
	{
		return 'ORDER_PAID';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_ORDER_PAID_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_ORDER_PAID_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['payment'];
	}
}