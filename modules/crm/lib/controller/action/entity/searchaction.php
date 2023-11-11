<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\Category\NamingHelper;
use Bitrix\Crm\Controller\EntitySearchScope;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Search\Result\Factory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Search;
use Bitrix\Main\UI\PageNavigation;

Loc::loadMessages(__FILE__);

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action
 * @example BX.ajax.runAction("crm.api.entity.search", { data: { searchQuery: "John Smith", options: { scope: "denomination", types: [ BX.CrmEntityType.names.contact ] } } });
 */
class SearchAction extends Search\SearchAction
{
	protected const LIMIT = \Bitrix\Crm\Search\Result\Provider::DEFAULT_LIMIT;

	protected $limit;
	protected $userId;

	public function __construct($name, Controller $controller, $config = [])
	{
		$this->userId = \CCrmSecurityHelper::GetCurrentUserID();
		parent::__construct($name, $controller, $config);
	}

	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null)
	{
		if (!is_array($options))
		{
			$options = [];
		}

		$entityTypeIds = $this->prepareSearchEntityTypeIds($options);

		$scope = isset($options['scope'])
			? EntitySearchScope::resolveID($options['scope'])
			: EntitySearchScope::UNDEFINED;
		if ($scope === EntitySearchScope::UNDEFINED)
		{
			$scope = EntitySearchScope::DENOMINATION;
		}

		$searchRestriction = RestrictionManager::getSearchLimitRestriction();

		$this->limit = static::LIMIT;
		$results = [];

		foreach ($entityTypeIds as $entityTypeId)
		{
			if (
				!$searchRestriction->isExceeded($entityTypeId)
				&& $this->limit > 0
			)
			{
				if (
					\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId)
					&& !Container::getInstance()->getTypeByEntityTypeId($entityTypeId)
				)
				{
					continue;
				}
				$searchResultProvider = Factory::createProvider($entityTypeId);
				$searchResultProvider->setUserId($this->userId);
				$searchResultProvider->setLimit($this->limit);
				$searchResultProvider->setUseDenominationSearch($scope !== EntitySearchScope::INDEX);
				$searchResultProvider->setAdditionalFilter($this->getAdditionalFilter($entityTypeId, $options));
				$searchResultProvider->setAffectedCategories($this->getAffectedCategoriesFromOptions($options));

				if ($this->isMyCompanyFromOptions($options) && Container::getInstance()->getUserPermissions()->getMyCompanyPermissions()->canSearch())
				{
					$searchResultProvider->setCheckPermissions(false);
				}

				$searchResult = $searchResultProvider->getSearchResult($searchQuery);
				$categoryId = $options['categoryId'] ?? 0;

				$adapter = Factory::createResultAdapter($entityTypeId, $categoryId);
				self::applyCategoryLabelsToAdapter($adapter, (int)$entityTypeId, $options);

				$results = array_merge(
					$results,
					$adapter->adapt($searchResult)
				);

				$this->limit = static::LIMIT - count($results);
			}
		}

		return $results;
	}

	protected function prepareSearchEntityTypeIds(array $options): array
	{
		$supportedEntityTypeIds = Factory::getSupportedEntityTypeIds();
		$types = (isset($options['types']) && is_array($options['types']))
			? $options['types']
			: [];
		if (empty($types)) // use all types
		{
			return $supportedEntityTypeIds;
		}

		$result = [];
		foreach ($types as $i => $type)
		{
			if (!is_numeric($type))
			{
				$type = \CCrmOwnerType::ResolveID($type);
			}
			$type = (int)$type;

			if (in_array($type, $supportedEntityTypeIds, true))
			{
				$result[] = $type;
			}
		}

		return $result;
	}

	protected function getAdditionalFilter(int $entityTypeId, array $options): array
	{
		$categoryFilter = [];
		$categoriesFromOptions = $this->getAffectedCategoriesFromOptions($options);
		if (!empty($categoriesFromOptions) && is_array($categoriesFromOptions) && in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
		{
			if (count($categoriesFromOptions) === 1)
			{
				$categoryFilter['=CATEGORY_ID'] = reset($categoriesFromOptions);
			}
			else
			{
				$categoryFilter['@CATEGORY_ID'] = $categoriesFromOptions;
			}
		}

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return array_merge($categoryFilter, [
				'=IS_MY_COMPANY' => $this->isMyCompanyFromOptions($options) ? 'Y' : 'N',
			]);
		}
		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return $categoryFilter;
		}

		return [];
	}

	protected function getAffectedCategoriesFromOptions(array $options): ?array
	{
		if (isset($options['categoryId']))
		{
			$categoryId = (int)$options['categoryId'];

			$result = [
				$categoryId
			];
			if (isset($options['extraCategoryIds']) && is_array($options['extraCategoryIds']))
			{
				foreach($options['extraCategoryIds'] as $extraCategoryId)
				{
					$extraCategoryId = (int)$extraCategoryId;
					if ($extraCategoryId >= 0)
					{
						$result[] = $extraCategoryId;
					}
				}
				$result = array_unique($result);
			}

			return $result;
		}

		return null;
	}

	protected function provideLimits($searchQuery, array $options = null)
	{
		if ($options === null)
		{
			$options = [];
		}

		$entityTypeIds = $this->prepareSearchEntityTypeIds($options);

		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		$limits = [];

		foreach ($entityTypeIds as $entityTypeId)
		{
			if ($searchRestriction->isExceeded($entityTypeId))
			{
				$limits[] = self::prepareLimitExceededError($entityTypeId);
			}
		}

		return $limits;
	}

	public static function prepareLimitExceededError($entityTypeID)
	{
		$restriction = RestrictionManager::getSearchLimitRestriction();
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		/**
		 * CRM_CONTROLLER_SEARCH_ACTION_LEAD_LIMIT_EXCEEDED
		 * CRM_CONTROLLER_SEARCH_ACTION_DEAL_LIMIT_EXCEEDED
		 * CRM_CONTROLLER_SEARCH_ACTION_CONTACT_LIMIT_EXCEEDED
		 * CRM_CONTROLLER_SEARCH_ACTION_COMPANY_LIMIT_EXCEEDED
		 * CRM_CONTROLLER_SEARCH_ACTION_QUOTE_LIMIT_EXCEEDED
		 * CRM_CONTROLLER_SEARCH_ACTION_INVOICE_LIMIT_EXCEEDED
		 */
		$content = Loc::getMessage("CRM_CONTROLLER_SEARCH_ACTION_{$entityTypeName}_LIMIT_EXCEEDED");
		if (!$content)
		{
			$content = Loc::getMessage("CRM_CONTROLLER_SEARCH_ACTION_{$entityTypeName}_LIMIT_EXCEEDED_MSGVER_1");
		}

		$info = $restriction->prepareStubInfo(
			[
				'ENTITY_TYPE_ID' => $entityTypeID,
				'CONTENT' => $content,
				'GLOBAL_SEARCH' => true,
			]
		);

		$resultLimit = new Search\ResultLimit(
			\CCrmOwnerType::ResolveName($entityTypeID),
			$info['TITLE'],
			$info['DESCRIPTION']
		);
		$resultLimit->setButtons($info['BUTTONS']);

		return $resultLimit;
	}

	/**
	 * Create JSON for recently used search results
	 *
	 * @param array $items
	 * @return array|array[]
	 * @throws Main\NotImplementedException
	 */
	public static function prepareSearchResultsJson(array $items, array $searchOptions = [])
	{
		$result = [];
		$supportedEntityTypeIds = Factory::getSupportedEntityTypeIds();
		$itemsByEntityType = [];
		$entityTypeToCategoryMap = [];

		foreach ($items as $item)
		{
			$entityTypeId = (int)$item['ENTITY_TYPE_ID'];
			if (!isset($itemsByEntityType[$entityTypeId]))
			{
				$itemsByEntityType[$entityTypeId] = [];
			}
			/**
			 * Assuming that all entity type's items have the same category
			 */
			$entityTypeToCategoryMap[$entityTypeId] = $item['CATEGORY_ID'] ?? null;
			$itemsByEntityType[$entityTypeId][] = (int)$item['ENTITY_ID'];
		}

		foreach ($itemsByEntityType as $entityTypeId => $entityIds)
		{
			if (in_array($entityTypeId, $supportedEntityTypeIds, true))
			{
				$searchResult = new Result();
				$searchResult->addIds($entityIds);

				$adapter = Factory::createResultAdapter(
					$entityTypeId,
					$entityTypeToCategoryMap[$entityTypeId] ?? null
				);
				self::applyCategoryLabelsToAdapter($adapter, (int)$entityTypeId, $searchOptions);

				$result = array_merge(
					$result,
					$adapter->adapt($searchResult)
				);
			}
		}

		return array_map(
			function($item) {
				/** @var Search\ResultItem $item */
				return $item->jsonSerialize();
			},
			$result
		);
	}

	private static function applyCategoryLabelsToAdapter(Result\Adapter $adapter, int $entityTypeId, array $options): void
	{
		if ($entityTypeId !== \CCrmOwnerType::Contact)
		{
			return;
		}

		$categoryId = $options['categoryId'] ?? 0;
		$extraCategoryIds = [];
		if (isset($options['extraCategoryIds']) && is_array($options['extraCategoryIds']) && !empty($options['extraCategoryIds']))
		{
			$extraCategoryIds = $options['extraCategoryIds'];
		}
		else
		{
			return;
		}

		$contactFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
		$smartDocumentContactCategory = $contactFactory->getCategoryByCode(\Bitrix\Crm\Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE);
		$defaultContactCategoryId = $contactFactory->getDefaultCategory()->getId();

		if (in_array($defaultContactCategoryId, $extraCategoryIds) && $smartDocumentContactCategory && $smartDocumentContactCategory->getId() == $categoryId)
		{
			$namingHelper = NamingHelper::getInstance();
			$langCode = 'CRM_CONTROLLER_SEARCH_LABEL_CATEGORY_TITLE';
			$adapter->addCategoryLabel($defaultContactCategoryId, $namingHelper->getLangPhrase($langCode, $defaultContactCategoryId));
			$adapter->addCategoryLabel($categoryId, $namingHelper->getLangPhrase($langCode, $categoryId));
		}
	}

	private function isMyCompanyFromOptions(array $options): bool
	{
		return (
			isset($options['isMyCompany'])
			&& mb_strtoupper($options['isMyCompany']) === 'Y'
		);
	}
}
