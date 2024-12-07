<?php

namespace Bitrix\Crm\AutomatedSolution\Support;

use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

/**
 * Helpers with predefined filters for b_crm_dynamic_type table
 */
final class TypeFilter
{
	public static function isTypeBoundToAnyAutomatedSolution(Type $type): bool
	{
		return $type->fill('CUSTOM_SECTION_ID') !== null;
	}

	public static function getOnlyCrmTypesFilter(): ConditionTree
	{
		return (new ConditionTree())->whereNull('CUSTOM_SECTION_ID');
	}

	public static function getOnlyExternalTypesFilter(): ConditionTree
	{
		return (new ConditionTree())->whereNotNull('CUSTOM_SECTION_ID');
	}
}
