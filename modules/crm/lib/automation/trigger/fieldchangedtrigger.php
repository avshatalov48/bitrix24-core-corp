<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Bizproc;
use Bitrix\Main\Localization\Loc;

class FieldChangedTrigger extends BaseTrigger
{
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
		$changedFields = (array)$this->getInputData('CHANGED_FIELDS');

		if (empty($followFields) || empty($changedFields))
		{
			return false;
		}

		$intersect = array_intersect($followFields, $changedFields);

		return !empty($intersect);
	}

	public static function toArray()
	{
		$result = parent::toArray();

		//get entity type id
		$entityTypeId = func_get_arg(0);
		$result['SETTINGS']['Properties'] = [
			[
				'Id' => 'fields',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_FIELD_CHANGED_PROPERTY_FIELDS'),
				'Type' => '@field-selector',
				'Settings' => [
					'Fields' => static::getFields($entityTypeId),
					'ChooseFieldLabel' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_FIELD_CHANGED_PROPERTY_FIELDS_CHOOSE'),
				],
			],
		];

		return $result;
	}

	public static function getGroup(): array
	{
		return ['elementControl'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_FIELD_CHANGED_DESCRIPTION') ?? '';
	}

	protected static function getFields($entityTypeId): array
	{
		$fields = array_values(Bizproc\Automation\Helper::getDocumentFields(
			\CCrmBizProcHelper::ResolveDocumentType($entityTypeId))
		);

		$filter = function ($field) use ($entityTypeId)
		{
			$id = $field['Id'];

			if (
				$id === 'ID'
				|| $id === 'LEAD_ID'
				|| $id === 'DEAL_ID'
				|| $id === 'CONTACT_ID'
				|| $id === 'CONTACT_IDS'
				|| $id === 'COMPANY_ID'
				|| $id === 'CREATED_BY_ID'
				|| $id === 'MODIFY_BY_ID'
				|| $id === 'DATE_CREATE'
				|| $id === 'DATE_MODIFY'
				|| $id === 'WEBFORM_ID'
				|| $id === 'STATUS_ID'
				|| $id === 'STAGE_ID'
				|| $id === 'CATEGORY_ID'
				|| $id === 'ORIGINATOR_ID'
				|| $id === 'ORIGIN_ID'
				|| $field['Type'] === 'phone'
				|| $field['Type'] === 'web'
				|| $field['Type'] === 'email'
				|| $field['Type'] === 'im'
				|| strpos($id, 'EVENT_') === 0
				|| strpos($id, 'PHONE_') === 0
				|| strpos($id, 'WEB_') === 0
				|| strpos($id, 'EMAIL_') === 0
				|| strpos($id, 'IM_') === 0
				|| strpos($id, 'OPPORTUNITY') !== false
				|| strpos($id, 'CURRENCY_ID') !== false
				|| strpos($id, 'ASSIGNED_BY') !== false
				|| strpos($id, 'RESPONSIBLE') !== false
				|| strpos($id, '.') !== false
				|| strpos($id, '_PRINTABLE') !== false
				|| ($entityTypeId === \CCrmOwnerType::Order && strpos($id, 'UF_') === 0)
			)
			{
				return false;
			}

			return true;
		};

		return array_values(array_filter($fields, $filter));
	}
}
