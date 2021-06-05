<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class ResourceBookingTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		return $entityTypeId !== \CCrmOwnerType::Quote ? parent::isSupported($entityTypeId) : false;
	}

	protected static function areDynamicTypesSupported(): bool
	{
		return false;
	}

	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('calendar');
	}

	public static function getCode()
	{
		return 'RESOURCE_BOOKING';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_RESOURCE_BOOKING_NAME');
	}
}