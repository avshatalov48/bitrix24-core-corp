<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sign\B2e\TypeService;
use Bitrix\Main\Localization\Loc;

final class AddKanbanCategories extends AgentBase
{
	public static function doRun(): bool
	{
		$typeService = Container::getInstance()->getSignB2eTypeService();

		if (!$typeService->isCreated())
		{
			return false;
		}

		$defaultCategoryId = $typeService->getDefaultCategoryId();
		if (!$defaultCategoryId)
		{
			return false;
		}

		if ($typeService->isCategoriesEnabled())
		{
			return false;
		}

		if ($typeService->getCategoryByCode(TypeService::SIGN_B2E_ITEM_CATEGORY_CODE) === null)
		{
			$updateDefaultCategoryResult = $typeService->updateDefaultCategory(
				Loc::getMessage('CRM_SMART_B2E_TO_EMPLOYEE_CATEGORY_NAME') ?? '',
				TypeService::SIGN_B2E_ITEM_CATEGORY_CODE,
			);
			if (!$updateDefaultCategoryResult->isSuccess())
			{
				return false;
			}
		}

		$fromEmployeeCategory = $typeService->getCategoryByCode(TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE);
		$categoryId = $fromEmployeeCategory['ID'] ?? null;
		if ($fromEmployeeCategory === null)
		{
			$addCategoryResult = $typeService->addCategory(
				Loc::getMessage('CRM_SMART_B2E_FROM_EMPLOYEE_CATEGORY_NAME') ?? '',
				TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE,
			);
			if (!$addCategoryResult->isSuccess())
			{
				return false;
			}

			$categoryId = $addCategoryResult->getId();
		}

		if (!is_int($categoryId))
		{
			return false;
		}

		$addCategoryDefaultPermissionsResult = $typeService->addCategoryDefaultPermissions($categoryId);
		if (!$addCategoryDefaultPermissionsResult->isSuccess())
		{
			return false;
		}

		$typeService->enableCategories();

		return false;
	}

}
