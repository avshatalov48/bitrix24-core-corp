<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class ResourceBookingTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		if ($entityTypeId === \CCrmOwnerType::Quote || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return false;
		}

		return parent::isSupported($entityTypeId);
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
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_RESOURCE_BOOKING_NAME_1');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_RESOURCE_BOOKING_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['other'];
	}
}