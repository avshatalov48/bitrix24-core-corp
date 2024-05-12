<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

final class Conversion extends Base
{
	private UserPermissions $userPermissions;

	protected function getDefaultPreFilters(): array
	{
		$prefilters = parent::getDefaultPreFilters();

		$prefilters[] = new Scope(Scope::AJAX);

		return $prefilters;
	}

	protected function init(): void
	{
		parent::init();

		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	/**
	 * Returns a list of categories that can be a destinations for conversion - user can add items to them.
	 * Note that a user might not have read access to them - it's irrelevant in this case, only adding matters.
	 *
	 * @noinspection PhpUnused
	 *
	 * @param int $entityTypeId
	 *
	 * @return Page|null
	 */
	public function getDstCategoryListAction(int $entityTypeId): ?Page
	{
		$factory = $this->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		$categories = $this->filterAvailableForAddingCategories(
			$factory->getCategories()
		);

		return new Page(
			'categories',
			$categories,
			static function () use ($categories): int {
				return count($categories);
			}
		);
	}

	/**
	 * @todo move to user permissions. its here only for hotfix simplicity
	 *
	 * @param Category[] $categories
	 *
	 * @return Category[]
	 */
	private function filterAvailableForAddingCategories(array $categories): array
	{
		return array_values(array_filter($categories, $this->userPermissions->canAddItemsInCategory(...)));
	}

	private function getFactory(int $entityTypeId): ?Factory
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
}
