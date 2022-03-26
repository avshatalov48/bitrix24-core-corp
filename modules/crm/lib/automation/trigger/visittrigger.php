<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class VisitTrigger extends BaseTrigger
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
		return \Bitrix\Crm\Activity\Provider\Visit::isAvailable();
	}

	public static function getCode()
	{
		return 'VISIT';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_VISIT_NAME');
	}
}