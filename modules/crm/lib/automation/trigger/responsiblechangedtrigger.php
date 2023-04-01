<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

class ResponsibleChangedTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		$supported = [
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Order,
			\CCrmOwnerType::SmartDocument,
		];
		if (in_array($entityTypeId, $supported, true))
		{
			return true;
		}

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			return (
				static::areDynamicTypesSupported()
				&& !is_null($factory)
				&& $factory->isAutomationEnabled()
				&& $factory->isStagesEnabled()
			);
		}

		return false;
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