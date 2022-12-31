<?php

namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DeliveryFinishedTrigger
 * @package Bitrix\Crm\Automation\Trigger
 */
class DeliveryFinishedTrigger extends BaseTrigger
{
	/**
	 * @inheritDoc
	 */
	public static function isSupported($entityTypeId)
	{
		return $entityTypeId === \CCrmOwnerType::Deal;
	}

	/**
	 * @inheritDoc
	 */
	public static function getCode()
	{
		return 'DELIVERY_FINISHED';
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DELIVERY_FINISHED_NAME_1');
	}

	public static function getGroup(): array
	{
		return ['delivery'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DELIVERY_FINISHED_DESCRIPTION') ?? '';
	}
}
