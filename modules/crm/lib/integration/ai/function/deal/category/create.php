<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Category;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category\CreateParameters;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use CCrmOwnerType;

final class Create implements AIFunction
{
	private readonly Factory $factory;
	private readonly UserPermissions\EntityPermissions\Category $categoryPermissions;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->factory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		$this->categoryPermissions = Container::getInstance()->getUserPermissions($this->currentUserId)->category();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): \Bitrix\Main\Result
	{
		$parameters = new CreateParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		$category = $this->factory->createCategory();

		if (!$this->categoryPermissions->canAdd($category))
		{
			return Result::failAccessDenied();
		}

		$result = $category
			->setName($parameters->name)
			->setSortAfterMaxCategory()
			->save();

		if (!$result->isSuccess())
		{
			return $result;
		}

		$this->factory->clearCategoriesCache();

		return Result::success(id: $category->getId());
	}
}
