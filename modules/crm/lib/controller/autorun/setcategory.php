<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\SetCategoryPreparedData;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SetCategory extends Base
{
	protected function isEntityTypeSupported(Factory $factory): bool
	{
		return $factory->isCategoriesEnabled();
	}

	protected function getPreparedDataDtoClass(): string
	{
		return SetCategoryPreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\SetCategoryPreparedData
	{
		$categoryId = (int)($params['categoryId'] ?? null);
		if (!$factory->isCategoryExists($categoryId))
		{
			// we want DTO validation to fail if a category with this id doesn't exist
			$categoryId = null;
		}

		return new SetCategoryPreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'categoryId' => $categoryId,
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof SetCategoryPreparedData))
		{
			throw new ArgumentTypeException('data', SetCategoryPreparedData::class);
		}

		return $item->getCategoryId() === $data->categoryId;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		return false;
	}

	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!($data instanceof SetCategoryPreparedData))
		{
			throw new ArgumentTypeException('data', SetCategoryPreparedData::class);
		}

		$permissions = Container::getInstance()->getUserPermissions();

		if (!(
			!$item->isNew() && $permissions->checkAddPermissions($item->getEntityTypeId(), $data->categoryId)
			)
		)
		{
			return (new Result())->addError(new Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED')));
		}

		$item->setCategoryId($data->categoryId);

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
	}
}
