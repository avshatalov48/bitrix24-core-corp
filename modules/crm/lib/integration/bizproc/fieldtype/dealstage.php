<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc;
use Bitrix\Crm\Category\DealCategory;

class DealStage extends Bizproc\BaseType\Select
{
	public static function getName(): string
	{
		return Loc::getMessage('CRM_BP_FIELDTYPE_DEAL_STAGE') ?: parent::getName();
	}

	protected static function getFieldOptions(Bizproc\FieldType $fieldType)
	{
		$categoryId = $fieldType->getSettings()['categoryId'] ?? null;

		$options = $categoryId !== null
			? DealCategory::getStageList($categoryId)
			: DealCategory::getFullStageList()
		;

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
