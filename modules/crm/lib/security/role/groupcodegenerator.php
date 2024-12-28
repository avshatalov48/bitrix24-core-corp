<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Service\Container;

class GroupCodeGenerator
{
	private const AUTOMATED_SOLUTION_GROUP_CODE_PREFIX = 'AUTOMATED_SOLUTION_';
	private const AUTOMATED_SOLUTION_LIST_GROUP_CODE = 'AUTOMATED_SOLUTION_LIST';

	public static function getGroupCodeByEntityTypeId(int $entityTypeId): ?string
	{
		if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return null;
		}
		$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		if ($type?->getCustomSectionId())
		{
			return self::getGroupCodeByAutomatedSolutionId($type->getCustomSectionId());
		}

		return null;
	}

	public static function getGroupCodeByAutomatedSolutionId(string $automatedSolutionId): ?string
	{
		return $automatedSolutionId > 0 ? self::AUTOMATED_SOLUTION_GROUP_CODE_PREFIX . $automatedSolutionId : null;
	}

	public static function isAutomatedSolutionGroupCode(string $groupCode): bool
	{
		return str_starts_with($groupCode, self::AUTOMATED_SOLUTION_GROUP_CODE_PREFIX);
	}

	public static function getCrmFormGroupCode(): string
	{
		return 'CRM_WEBFORM';
	}

	public static function getWidgetGroupCode(): string
	{
		return 'CRM_BUTTON';
	}

	public static function getAutomatedSolutionListCode(): string
	{
		return self::AUTOMATED_SOLUTION_LIST_GROUP_CODE;
	}
}
