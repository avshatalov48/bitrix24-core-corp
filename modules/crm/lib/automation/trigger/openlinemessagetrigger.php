<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineMessageTrigger extends OpenLineTrigger
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
		return (Integration\OpenLineManager::isEnabled()
			&& class_exists('\Bitrix\ImOpenLines\Crm')
			&& method_exists('\Bitrix\ImOpenLines\Crm', 'executeAutomationMessageTrigger')
		);
	}

	public static function getCode()
	{
		return 'OPENLINE_MSG';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_MESSAGE_NAME');
	}
}