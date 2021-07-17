<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc;

class LeadStatus extends Bizproc\BaseType\Select
{
	public static function getName(): string
	{
		return Loc::getMessage('CRM_BP_FIELDTYPE_LEAD_STATUS') ?: parent::getName();
	}

	protected static function getFieldOptions(Bizproc\FieldType $fieldType)
	{
		$options = \CCrmStatus::GetStatusList('STATUS');

		return static::normalizeOptions($options);
	}

	public static function renderControlSingle(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	public static function renderControlMultiple(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}
}
