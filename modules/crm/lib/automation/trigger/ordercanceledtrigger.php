<?php

namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderCanceledTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return ($entityTypeId === \CCrmOwnerType::Order);
	}

	public static function getCode()
	{
		return 'ORDER_CANCELED';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_ORDER_CANCELED_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_ORDER_CANCELED_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['delivery'];
	}
}