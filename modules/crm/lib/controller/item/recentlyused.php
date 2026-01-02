<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Controller\Action\Entity\SearchAction;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use CCrmComponentHelper;
use CCrmOwnerType;

final class RecentlyUsed extends Base
{
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				CategoryIdentifier::class,
				'categoryIdentifier',
				static function($className, $entityTypeId, $categoryId) {
					return new CategoryIdentifier($entityTypeId, $categoryId);
				}
			),
		];
	}

	public function getListAction(CategoryIdentifier $categoryIdentifier, int $expandEntityTypeId, array $extraCategoryIds = [], bool $isMyCompany = false): array
	{
		if (
			!in_array($expandEntityTypeId, [CCrmOwnerType::Company, CCrmOwnerType::Contact], true)
			|| !CCrmOwnerType::ResolveName($categoryIdentifier->getEntityTypeId())
		)
		{
			return [];
		}

		if (
			$isMyCompany
			&& $expandEntityTypeId === CCrmOwnerType::Company
			&& !Container::getInstance()->getUserPermissions()->myCompany()->canSearch()
		)
		{
			return [];
		}

		if (
			!$isMyCompany
			&& !Container::getInstance()->getUserPermissions()->entityType()->canReadItemsInCategory(
				$expandEntityTypeId, $categoryIdentifier->getCategoryId()
			)
		)
		{
			return [];
		}

		$searchOptions['categoryId'] = $categoryIdentifier->getCategoryId();
		foreach ($extraCategoryIds as $categoryId)
		{
			$categoryId = (int)$categoryId;
			$searchOptions['extraCategoryIds'][$categoryId] = $categoryId;
		}

		$expandEntityName = strtolower(CCrmOwnerType::ResolveName($expandEntityTypeId));

		return SearchAction::prepareSearchResultsJson(
			Entity::getRecentlyUsedItems(
				$this->getEntityCategoryCode($categoryIdentifier),
				$expandEntityName,
				[
					'EXPAND_ENTITY_TYPE_ID' => $expandEntityTypeId,
					'EXPAND_CATEGORY_ID' => $this->getExpandCategoryId($categoryIdentifier, $expandEntityTypeId),
					'CHECK_IS_MY_COMPANY' => $isMyCompany,
				]
			),
			$searchOptions,
		);
	}

	private function getEntityCategoryCode(CategoryIdentifier $categoryIdentifier): string
	{
		return sprintf(
			'crm.%s.details',
			strtolower(CCrmOwnerType::ResolveName($categoryIdentifier->getEntityTypeId()))
		);
	}

	private function getExpandCategoryId(CategoryIdentifier $categoryIdentifier, int $expandEntityTypeId): string
	{
		$categoryParams = CCrmComponentHelper::getEntityClientFieldCategoryParams(
			$expandEntityTypeId,
			$categoryIdentifier->getCategoryId(),
			$categoryIdentifier->getEntityTypeId()
		);

		return $categoryParams[$expandEntityTypeId]['categoryId'];
	}
}