<?php

namespace Bitrix\Crm\Integration\AI\Function\Category;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Category\Dto\DeleteParameters;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Localization\Loc;

final class Delete implements AIFunction
{
	private readonly UserPermissions\EntityPermissions\Category $permissions;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->permissions = Container::getInstance()->getUserPermissions($this->currentUserId)->category();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): \Bitrix\Main\Result
	{
		$parameters = new DeleteParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		/** @var Factory $factory */
		$factory = Container::getInstance()->getFactory($parameters->entityTypeId);

		/** @var Category $category */
		$category = $factory->getCategory($parameters->categoryId);

		if (!$this->permissions->canDelete($category))
		{
			return Result::failAccessDenied();
		}

		if ($category->getIsDefault())
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_CATEGORY_DELETE_DEFAULT_CATEGORY_ERROR'), 'DELETE_DEFAULT_CATEGORY');
		}

		return $category->delete();
	}
}
