<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class FieldChangedTrigger extends BaseTrigger
{
	public static function isSupported($entityTypeId)
	{
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return parent::isSupported($entityTypeId);
		}

		$supported = [\CCrmOwnerType::Deal, \CCrmOwnerType::Lead, \CCrmOwnerType::Quote, \CCrmOwnerType::SmartInvoice];
		return in_array($entityTypeId, $supported, true);
	}

	public static function getCode()
	{
		return 'FIELD_CHANGED';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FIELD_CHANGED_NAME_1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		$followFields = is_array($trigger['APPLY_RULES']['fields']) ? $trigger['APPLY_RULES']['fields'] : [];
		$changedFields = (array) $this->getInputData('CHANGED_FIELDS');

		if (empty($followFields) || empty($changedFields))
		{
			return false;
		}

		$intersect = array_intersect($followFields, $changedFields);

		return !empty($intersect);
	}

	public static function getGroup(): array
	{
		return ['elementControl'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FIELD_CHANGED_DESCRIPTION') ?? '';
	}
}