<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Category\Entity;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Category extends Base
{
	/** @var UserPermissions */
	protected $userPermissions;

	protected function init(): void
	{
		parent::init();

		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	protected function getFactory(int $entityTypeId): ?Service\Factory
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'),
					ErrorCode::NOT_FOUND)
			);

			return null;
		}
		if (!$factory->isCategoriesSupported())
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return null;
		}

		return $factory;
	}

	public function fieldsAction(int $entityTypeId): ?array
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		$fieldsInfo = $factory->getCategoryFieldsInfo();

		return [
			'fields' => $this->prepareFieldsInfo($fieldsInfo),
		];
	}

	public function getAction(int $entityTypeId, int $id): ?array
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		$category = $factory->getCategory($id);
		if (!$category)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return null;
		}

		if (!$this->userPermissions->canViewItemsInCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		return [
			'category' => $category,
		];
	}

	public function listAction(int $entityTypeId, array $filter = []): ?Page
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		$categories = $this->userPermissions->filterAvailableForReadingCategories(
			$factory->getCategories()
		);

		$filteredCategories = [];
		if (isset($filter['code']))
		{
			foreach ($categories as $category)
			{
				if ($category->getCode() === $filter['code'])
				{
					$filteredCategories[] = $category;
				}
			}
		}
		else
		{
			$filteredCategories = $categories;
		}

		return new Page(
			'categories',
			$filteredCategories,
			static function () use ($filteredCategories): int {
				return count($filteredCategories);
			}
		);
	}

	public function deleteAction(int $entityTypeId, int $id): void
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return;
		}
		$category = $factory->getCategory($id);
		if (!$category)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return;
		}

		if ($category->getIsSystem())
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_CATEGORY_DELETE_ERROR_SYSTEM'),
					ErrorCode::REMOVING_DISABLED
				)
			);

			return;
		}

		if (!$this->userPermissions->canDeleteCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return;
		}

		if (!$this->userPermissions->canDeleteCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return;
		}

		$result = $category->delete();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function addAction(int $entityTypeId, array $fields): ?array
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		if ($fields['isSystem'] === 'Y')
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_CATEGORY_ADD_ERROR_SYSTEM'),
					ErrorCode::ADDING_DISABLED
				)
			);

			return null;
		}

		$category = $factory->createCategory();
		if (!$this->userPermissions->canAddCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		return $this->updateCategory($factory, $category, $fields);
	}

	public function updateAction(int $entityTypeId, int $id, array $fields): ?array
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		$category = $factory->getCategory($id);
		if (!$category)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return null;
		}

		if (!$this->userPermissions->canUpdateCategory($category))
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		return $this->updateCategory($factory, $category, $fields);
	}

	protected function updateCategory(Service\Factory $factory, Entity\Category $category, array $fields): ?array
	{
		$processResult = $this->processFields($factory, $category, $fields);
		if (!$processResult->isSuccess())
		{
			$this->addErrors($processResult->getErrors());
			return null;
		}

		$saveResult = $category->save();
		if (!$saveResult->isSuccess())
		{
			$this->addErrors($saveResult->getErrors());
			return null;
		}

		return [
			'category' => $category,
		];
	}

	protected function processFields(Service\Factory $factory, Entity\Category $category, array $fields): Result
	{
		$name = isset($fields['name']) ? (string)$fields['name'] : null;
		if (!is_null($name))
		{
			$category->setName($name);
		}

		$sort = isset($fields['sort']) ? (int)$fields['sort'] : null;
		if (!is_null($sort) && $sort >= 0)
		{
			$category->setSort($sort);
		}

		if ($this->canChangeWhichCategoryIsDefault($factory))
		{
			$isDefault = $fields['isDefault'] ?? null;

			if (($isDefault === 'N') || ($isDefault === false))
			{
				$category->setIsDefault(false);
			}
			elseif (($isDefault === 'Y') || ($isDefault === true))
			{
				$result = $this->makeCurrentDefaultCategoryNotDefault($factory);

				if ($result->isSuccess())
				{
					$category->setIsDefault(true);
				}
			}
		}

		return $result ?? new Result();
	}

	protected function canChangeWhichCategoryIsDefault(Service\Factory $factory): bool
	{
		$isDefaultFieldInfo = $factory->getCategoryFieldsInfo()['IS_DEFAULT'] ?? null;
		if (is_null($isDefaultFieldInfo))
		{
			return false;
		}

		return !\CCrmFieldInfoAttr::isFieldReadOnly($isDefaultFieldInfo);
	}

	protected function makeCurrentDefaultCategoryNotDefault(Service\Factory $factory): Result
	{
		$defaultCategory = $factory->getDefaultCategory();
		if (!$defaultCategory)
		{
			return new Result();
		}

		$defaultCategory->setIsDefault(false);

		return $defaultCategory->save();
	}
}
