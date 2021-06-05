<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Category extends Base
{
	/** @var Factory */
	protected $factory;
	/** @var UserPermissions */
	protected $userPermissions;

	protected function init(): void
	{
		parent::init();

		$entityTypeId = $this->getRequest()->get('entityTypeId');
		if (empty($entityTypeId))
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));
			return;
		}

		$this->factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$this->factory)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));
			return;
		}
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	protected function processBeforeAction(Action $action): bool
	{
		return (parent::processBeforeAction($action) && (count($this->getErrors()) <= 0));
	}

	public function getAction(int $id): ?array
	{
		$category = $this->factory->getCategory($id);
		if ($category)
		{
			if (!$this->userPermissions->canViewItemsInCategory($category))
			{
				$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_ACCESS_DENIED')));
				return null;
			}

			return $category->jsonSerialize();
		}
		$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR')));

		return null;
	}

	public function addAction(array $fields): ?array
	{
		$category = $this->factory->createCategory();
		if (!$this->userPermissions->canAddCategory($category))
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_ACCESS_DENIED')));
			return null;
		}

		$category = $this->updateCategory($category, $fields);
		if (!$category)
		{
			return null;
		}

		$result = $category->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return $category->jsonSerialize();
	}

	public function updateAction(int $id, array $fields): ?array
	{
		$category = $this->factory->getCategory($id);
		if (!$category)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR')));
			return null;
		}
		if (!$this->userPermissions->canUpdateCategory($category))
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_ACCESS_DENIED')));
			return null;
		}
		$category = $this->updateCategory($category, $fields);
		if (!$category)
		{
			return null;
		}

		$result = $category->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return $category->jsonSerialize();
	}

	protected function updateCategory(
		\Bitrix\Crm\Category\Entity\Category $category,
		array $fields
	): ?\Bitrix\Crm\Category\Entity\Category
	{
		$name = $fields['name'] ?? '';
		$sort = $fields['sort'] ? (int) $fields['sort'] : null;
		$isDefault = is_bool($fields['isDefault']) ? $fields['isDefault'] : null;
		$category->setName($name);
		if ($sort > 0)
		{
			$category->setSort($sort);
		}
		if ($isDefault === true)
		{
			$defaultCategory = $this->factory->getDefaultCategory();
			if ($defaultCategory)
			{
				$defaultCategory->setIsDefault(false);
				$saveDefaultCategoryResult = $defaultCategory->save();
				if (!$saveDefaultCategoryResult->isSuccess())
				{
					$this->addErrors($saveDefaultCategoryResult->getErrors());
					return null;
				}

				$category->setIsDefault(true);
			}
		}
		else
		{
			$category->setIsDefault(false);
		}

		return $category;
	}

	public function listAction(): Page
	{
		$result = [];

		$categories = $this->userPermissions->filterAvailableForReadingCategories($this->factory->getCategories());
		foreach ($categories as $category)
		{
			$result[] = $category->jsonSerialize();
		}

		return new Page('categories', $result, count($result));
	}

	public function deleteAction($id): void
	{
		$category = $this->factory->getCategory($id);
		if ($category)
		{
			if (!$this->userPermissions->canDeleteCategory($category))
			{
				$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_ACCESS_DENIED')));
				return;
			}
			$result = $category->delete();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}
		else
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR')));
		}
	}
}
