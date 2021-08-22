<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\Controller\EntitySearchScope;
use Bitrix\Crm\Restriction\RestrictionManager;

use Bitrix\Main;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Search;
use Bitrix\Main\UI\PageNavigation;

use \Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Search\Result\Factory;
use Bitrix\Crm\Search\Result;

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
				$searchResultProvider = Factory::createProvider($entityTypeId);
				$searchResultProvider->setUserId($this->userId);
				$searchResultProvider->setLimit($this->limit);
				$searchResultProvider->setUseDenominationSearch($scope !== EntitySearchScope::INDEX);
				$searchResultProvider->setAdditionalFilter($this->getAdditionalFilter($entityTypeId, $options));

				$searchResult = $searchResultProvider->getSearchResult($searchQuery);

				$results = array_merge(
					$results,
					Factory::createResultAdapter($entityTypeId)->adapt($searchResult)
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
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$isMyCompany = (
				isset($options['isMyCompany'])
				&& mb_strtoupper($options['isMyCompany']) === 'Y'
			);

			return [
				'=IS_MY_COMPANY' => $isMyCompany ? 'Y' : 'N',
			];
		}

		return [];
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
		$info = $restriction->prepareStubInfo(
			[
				'ENTITY_TYPE_ID' => $entityTypeID,
				'CONTENT' => Loc::getMessage("CRM_CONTROLLER_SEARCH_ACTION_{$entityTypeName}_LIMIT_EXCEEDED"),
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
	public static function prepareSearchResultsJson(array $items)
	{
		$result = [];
		$supportedEntityTypeIds = Factory::getSupportedEntityTypeIds();
		$itemsByEntityType = [];

		foreach ($items as $item)
		{
			$entityTypeId = (int)$item['ENTITY_TYPE_ID'];
			if (!isset($itemsByEntityType[$entityTypeId]))
			{
				$itemsByEntityType[$entityTypeId] = [];
			}
			$itemsByEntityType[$entityTypeId][] = (int)$item['ENTITY_ID'];
		}

		foreach ($itemsByEntityType as $entityTypeId => $entityIds)
		{
			if (in_array($entityTypeId, $supportedEntityTypeIds, true))
			{
				$searchResult = new Result();
				$searchResult->addIds($entityIds);

				$result = array_merge(
					$result,
					Factory::createResultAdapter($entityTypeId)->adapt($searchResult)
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
}
