<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Entity\Index\CompanyTable;
use Bitrix\Crm\Entity\Index\ContactTable;
use Bitrix\Crm\Entity\Index\DealTable;
use Bitrix\Crm\Entity\Index\LeadTable;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Controller\EntitySearchScope;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ORM\Query\Filter\Helper;
use Bitrix\Main\Search;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SearchAction
 * @package Bitrix\Crm\Controller\Action
 * @example BX.ajax.runAction("crm.api.entity.search", { data: { searchQuery: "John Smith", options: { scope: "denomination", types: [ BX.CrmEntityType.names.contact ] } } });
 */
class SearchAction extends Search\SearchAction
{
	protected const LIMIT = 20;

	protected $limit;
	protected $userId;
	protected $permissionSqls = [];

	public function __construct($name, Controller $controller, $config = [])
	{
		$this->userId = \CCrmSecurityHelper::GetCurrentUserID();
		parent::__construct($name, $controller, $config);
	}

	public function provideData($searchQuery, array $options = null, PageNavigation $pageNavigation = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		//region Resolve Entity type IDs to Entity Type Names if required.
		$types = isset($options['types']) && is_array($options['types']) ? $options['types'] : array();
		for($i = 0, $length = count($types); $i < $length; $i++)
		{
			if(is_numeric($types[$i]))
			{
				$types[$i] = \CCrmOwnerType::ResolveName($types[$i]);
			}
		}
		//endregion

		$typeMap = array_fill_keys($types, true);
		$enableAllTypes = empty($typeMap);

		//region Resolve Search scope
		$scope = isset($options['scope'])
			? EntitySearchScope::resolveID($options['scope']) : EntitySearchScope::UNDEFINED;
		if($scope === EntitySearchScope::UNDEFINED)
		{
			$scope = EntitySearchScope::DENOMINATION;
		}
		//endregion

		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		$items = [];
		$priorityIds = [];

		$this->limit = static::LIMIT;
		$results = [];

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Lead)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::LeadName]))
		)
		{
			if($scope === EntitySearchScope::INDEX)
			{
				[$items, $priorityIds] = $this->getSearchByIndexResults(
					$searchQuery,
					LeadTable::class,
					\Bitrix\Crm\LeadTable::class,
					\CCrmOwnerType::Lead
				);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$filter = [
					'LOGIC' => 'OR',
					'%FULL_NAME' => $searchQuery,
					'%TITLE' => $searchQuery,
				];

				$dbResult = \CCrmLead::GetListEx(
					[],
					$filter,
					false,
					['nTopCount' => static::LIMIT],
					['ID']
				);

				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$items[] = [
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
							'ENTITY_ID' => $fields['ID'],
						];
					}
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
			$this->limit = static::LIMIT - count($results);
		}

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Contact)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::ContactName]))
			&& $this->limit > 0
		)
		{
			if($scope === EntitySearchScope::INDEX)
			{
				[$items, $priorityIds] = $this->getSearchByIndexResults(
					$searchQuery,
					ContactTable::class,
					\Bitrix\Crm\ContactTable::class,
					\CCrmOwnerType::Contact
				);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$parts = preg_split ('/[\s]+/', $searchQuery, 2, PREG_SPLIT_NO_EMPTY);
				if(count($parts) < 2)
				{
					$filter = array('%FULL_NAME' => $searchQuery);
				}
				else
				{
					$filter = array('LOGIC' => 'AND');
					for($i = 0; $i < 2; $i++)
					{
						$filter["__INNER_FILTER_NAME_{$i}"] = array('%FULL_NAME' => $parts[$i]);
					}
				}

				$dbResult = \CCrmContact::GetListEx(
					[],
					$filter,
					false,
					['nTopCount' => static::LIMIT],
					['ID']
				);

				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$items[] = [
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
							'ENTITY_ID' => $fields['ID'],
						];
					}
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
			$this->limit = static::LIMIT - count($results);
		}

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Company)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::CompanyName]))
			&& $this->limit > 0
		)
		{
			if($scope === EntitySearchScope::INDEX)
			{
				$isMyCompany = (
					isset($options['isMyCompany'])
					&& mb_strtoupper($options['isMyCompany']) === 'Y'
						? 'Y'
						: 'N'
				);
				$additionalSelectParams = [];
				$additionalSelectParams['IS_MY_COMPANY'] = $isMyCompany;

				[$items, $priorityIds] = $this->getSearchByIndexResults(
					$searchQuery,
					CompanyTable::class,
					\Bitrix\Crm\CompanyTable::class,
					\CCrmOwnerType::Company,
					$additionalSelectParams
				);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$filter = array('%TITLE' => $searchQuery);
				$filter['=IS_MY_COMPANY'] = isset($options['isMyCompany']) && mb_strtoupper($options['isMyCompany']) === 'Y'
					? 'Y' : 'N';

				$dbResult = \CCrmCompany::GetListEx(
					[],
					$filter,
					false,
					[
						'nTopCount' => static::LIMIT,
					],
					['ID']
				);

				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$items[] = [
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
							'ENTITY_ID' => $fields['ID'],
						];
					}
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
			$this->limit = static::LIMIT - count($results);
		}

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Deal)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::DealName]))
			&& $this->limit > 0
		)
		{
			if($scope === EntitySearchScope::INDEX)
			{
				[$items, $priorityIds] = $this->getSearchByIndexResults(
					$searchQuery,
					DealTable::class,
					\Bitrix\Crm\DealTable::class,
					\CCrmOwnerType::Deal
				);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$filter = array('%TITLE' => $searchQuery);
				$dbResult = \CCrmDeal::GetListEx(
					[],
					$filter,
					false,
					['nTopCount' => static::LIMIT],
					['ID']
				);

				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$items[] = [
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
							'ENTITY_ID' => $fields['ID'],
						];
					}
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
			$this->limit = static::LIMIT - count($results);
		}

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Quote)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::QuoteName]))
			&& $this->limit > 0
		)
		{
			$items = [];
			$priorityIds = [];

			if($scope === EntitySearchScope::INDEX)
			{
				$filter = array('FIND' => $searchQuery);
				SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Quote, $filter);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$filter = array('%TITLE' => $searchQuery);
			}

			$dbResult = \CCrmQuote::GetList(
				array(),
				$filter,
				false,
				array('nTopCount' => static::LIMIT),
				array('ID')
			);

			if(is_object($dbResult))
			{
				while($fields = $dbResult->Fetch())
				{
					$items[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Quote, 'ENTITY_ID' => $fields['ID']);
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
			$this->limit = static::LIMIT - count($results);
		}

		if(
			!$searchRestriction->isExceeded(\CCrmOwnerType::Invoice)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::InvoiceName]))
			&& $this->limit > 0
		)
		{
			$items = [];
			$priorityIds = [];

			if($scope === EntitySearchScope::INDEX)
			{
				$filter = array('FIND' => $searchQuery);
				SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Quote, $filter);
			}
			else //if($scope === EntitySearchScope::DENOMINATION)
			{
				$filter = array('%ORDER_TOPIC' => $searchQuery);
			}

			$dbResult = \CCrmInvoice::GetList(
				array(),
				$filter,
				false,
				array('nTopCount' => static::LIMIT),
				array('ID')
			);

			if(is_object($dbResult))
			{
				while($fields = $dbResult->Fetch())
				{
					$items[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice, 'ENTITY_ID' => $fields['ID']);
				}
			}

			$results = array_merge($results, self::prepareSearchResults($items, $priorityIds));
		}

		return $results;
	}

	/**
	 * @param string $searchQuery
	 * @param string $indexTableClass
	 * @param string $entityTableClass
	 * @param int $entityTypeId
	 * @param array|null $additionalSelectParams
	 * @return array
	 */
	protected function getSearchByIndexResults(
		string $searchQuery,
		string $indexTableClass,
		string $entityTableClass,
		int $entityTypeId,
		?array $additionalSelectParams = []
	): array
	{
		$searchQuery = SearchEnvironment::prepareSearchContent($searchQuery);

		$params = $this->getShortIndexResults(
			['SEARCH_CONTENT' => $searchQuery],
			$indexTableClass
		);

		$items = ($params['items'] ?? []);
		$priorityIds = $params['ids'];

		$itemsCount = count($items);

		if ($itemsCount < $this->limit)
		{
			$filter = $params['filter'];

			if (!empty($additionalSelectParams))
			{
				foreach($additionalSelectParams as $name => $value)
				{
					$filter->where($name, $value);
				}
			}

			$permissionSql = $this->getPermissionSql(\CCrmOwnerType::ResolveName($entityTypeId));
			if ($permissionSql === false)
			{
				return [$items, $priorityIds];
			}

			if ($permissionSql !== '')
			{
				$filter->whereIn('ID', new SqlExpression($permissionSql));
			}

			$list = call_user_func_array(
				[
					$entityTableClass,
					'getList'
				],
				[
					[
						'select' => ['ID'],
						'filter' => $filter,
						'limit' => $this->limit - $itemsCount,
					],
				]
			);
			foreach($list as $item)
			{
				$items[] = [
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => (int)$item['ID'],
				];
			}
		}

		return [$items, $priorityIds];
	}

	/**
	 * @param array $filterParams
	 * @param string $indexTableClass
	 * @return array
	 */
	protected function getShortIndexResults(array $filterParams, string $indexTableClass): array
	{
		$filter = Main\Entity\Query::filter();
		$items = [];
		$ids = [];

		if (!empty($filterParams['SEARCH_CONTENT']))
		{
			$searchContent = trim($filterParams['SEARCH_CONTENT']);
			if (Search\Content::isIntegerToken($searchContent))
			{
				$searchContent = Search\Content::prepareIntegerToken($searchContent);
			}
			else
			{
				$searchContent = Search\Content::prepareStringToken($searchContent);
			}

			if (Search\Content::canUseFulltextSearch($searchContent, Search\Content::TYPE_MIXED))
			{
				$searchContent = Helper::matchAgainstWildcard($searchContent);
				$filter->whereMatch('SEARCH_CONTENT', $searchContent);
				$entityTypeId = \CCrmOwnerType::Undefined;

				if ($indexTableClass === ContactTable::class)
				{
					$collection = $this->getItemCollection(
						$indexTableClass,
						'CONTACT_ID',
						$searchContent,
						\CCrmOwnerType::ContactName
					);

					$ids = ($collection ? $collection->getContactIdList() : []);
					$entityTypeId =  \CCrmOwnerType::Contact;
				}
				elseif ($indexTableClass === CompanyTable::class)
				{
					$collection = $this->getItemCollection(
						$indexTableClass,
						'COMPANY_ID',
						$searchContent,
						\CCrmOwnerType::CompanyName
					);

					$ids = ($collection ? $collection->getCompanyIdList() : []);
					$entityTypeId =  \CCrmOwnerType::Company;
				}
				elseif ($indexTableClass === LeadTable::class)
				{
					$collection = $this->getItemCollection(
						$indexTableClass,
						'LEAD_ID',
						$searchContent,
						\CCrmOwnerType::LeadName
					);

					$ids = ($collection ? $collection->getLeadIdList() : []);
					$entityTypeId = \CCrmOwnerType::Lead;
				}
				elseif ($indexTableClass === DealTable::class)
				{
					$collection = $this->getItemCollection(
						$indexTableClass,
						'DEAL_ID',
						$searchContent,
						\CCrmOwnerType::DealName
					);

					$ids = ($collection ? $collection->getDealIdList() : []);
					$entityTypeId =  \CCrmOwnerType::Deal;
				}

				if ($ids)
				{
					foreach($ids as $id)
					{
						$items[] = [
							'ENTITY_TYPE_ID' => $entityTypeId,
							'ENTITY_ID' => $id
						];
					}
					$filter->whereNotIn('ID', $ids);
				}
			}
		}

		return [
			'filter' => $filter,
			'items' => $items,
			'ids' => $ids
		];
	}

	protected function getItemCollection(
		string $tableClass,
		string $columnName,
		string $searchContent,
		string $entityType
	)
	{
		$permissionSql = $this->getPermissionSql($entityType);
		if($permissionSql === false)
		{
			return [];
		}

		$query = $tableClass::query()
			->setSelect([$columnName])
			->whereMatch('SEARCH_CONTENT', $searchContent)
			->setLimit($this->limit);

		if ($permissionSql !== '')
		{
			$query->addFilter('@'.$columnName, new SqlExpression($permissionSql));
		}

		return $query->fetchCollection();
	}

	/**
	 * @param string $entityType
	 * @return false|string
	 */
	protected function getPermissionSql(string $entityType)
	{
		if (isset($this->permissionSqls[$entityType]))
		{
			return $this->permissionSqls[$entityType];
		}

		$permissionSql = '';

		if(!\CCrmPerms::IsAdmin())
		{
			if ($entityType === \CCrmOwnerType::DealName)
			{
				$entityTypes = array_merge(
					[
						\CCrmOwnerType::DealName,
					],
					DealCategory::getPermissionEntityTypeList()
				);
			}
			else
			{
				$entityTypes = [$entityType];
			}

			$permissionSql = \CCrmPerms::BuildSqlForEntitySet(
				$entityTypes,
				'',
				'READ',
				[
					'RAW_QUERY' => true,
					'PERMS'=> \CCrmPerms::GetUserPermissions($this->userId)
				]
			);
		}

		$this->permissionSqls[$entityType] = $permissionSql;
		return $permissionSql;
	}

	protected function provideLimits($searchQuery, array $options = null)
	{
		if($options === null)
		{
			$options = array();
		}

		$types = isset($options['types']) && is_array($options['types']) ? $options['types'] : array();
		$typeMap = array_fill_keys($types, true);
		$enableAllTypes = empty($typeMap);

		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		$limits = array();
		if($searchRestriction->isExceeded(\CCrmOwnerType::Lead)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::LeadName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Lead);
		}
		if($searchRestriction->isExceeded(\CCrmOwnerType::Contact)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::ContactName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Contact);
		}
		if($searchRestriction->isExceeded(\CCrmOwnerType::Company)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::CompanyName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Company);
		}
		if($searchRestriction->isExceeded(\CCrmOwnerType::Deal)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::DealName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Deal);
		}
		if($searchRestriction->isExceeded(\CCrmOwnerType::Quote)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::QuoteName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Quote);
		}
		if($searchRestriction->isExceeded(\CCrmOwnerType::Invoice)
			&& ($enableAllTypes || isset($typeMap[\CCrmOwnerType::InvoiceName]))
		)
		{
			$limits[] = self::prepareLimitExceededError(\CCrmOwnerType::Invoice);
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
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'CONTENT' => Loc::getMessage("CRM_CONTROLLER_SEARCH_ACTION_{$entityTypeName}_LIMIT_EXCEEDED"),
				'GLOBAL_SEARCH' => true,
			)
		);

		$resultLimit = new Search\ResultLimit(
			\CCrmOwnerType::ResolveName($entityTypeID),
			$info['TITLE'],
			$info['DESCRIPTION']
		);
		$resultLimit->setButtons($info['BUTTONS']);

		return $resultLimit;
	}
	public static function prepareSearchResults(array $items, ?array $priorityIds = [])
	{
		/** @var int[] $map */
		$map = array();
		/** @var Search\ResultItem[] $results */
		$results = array();
		$isSortByTitle = true;

		foreach($items as $item)
		{
			$entityTypeID = isset($item['ENTITY_TYPE_ID']) ? (int)$item['ENTITY_TYPE_ID'] : 0;
			$entityID = isset($item['ENTITY_ID']) ? (int)$item['ENTITY_ID'] : 0;

			if(!\CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
			{
				continue;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			if(!isset($map[$entityTypeName]))
			{
				$map[$entityTypeName] = array();
			}
			$map[$entityTypeName][] = $entityID;
		}

		foreach($map as $entityTypeName => $entityIDs)
		{
			if($entityTypeName === \CCrmOwnerType::LeadName)
			{
				$dbResult = \CCrmLead::GetListEx(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				);

				if(is_object($dbResult))
				{

					while($fields = $dbResult->Fetch())
					{
						$entityID = (int)$fields['ID'];

						$resultItem = new Search\ResultItem(
							$fields['TITLE'],
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Lead,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);
						$resultItem->setSubTitle(\CCrmLead::PrepareFormattedName($fields));

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;
					}
				}
			}
			elseif($entityTypeName === \CCrmOwnerType::ContactName)
			{
				$dbResult = \CCrmContact::GetListEx(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'ORIGINATOR_ID')
				);
				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$entityID = (int)$fields['ID'];

						$resultItem = new Search\ResultItem(
							\CCrmContact::PrepareFormattedName($fields),
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Contact,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);

						if(isset($fields['COMPANY_TITLE']))
						{
							$resultItem->setSubTitle($fields['COMPANY_TITLE']);
						}
						if($icon = \Bitrix\Crm\Integration\Originator::getIcon($fields['ORIGINATOR_ID']))
						{
							$resultItem->setAttribute(
								'icon',
								[
									'src' => $icon['SRC'],
									'width' => $icon['WIDTH'],
									'height' => $icon['HEIGHT'],
								]
							);
						}

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;

						/*
						if(isset($fields['PHOTO']) && $fields['PHOTO'] > 0)
						{
							$fileInfo = \CFile::ResizeImageGet(
								$fields['PHOTO'],
								array('width' => 100, 'height' => 100),
								BX_RESIZE_IMAGE_EXACT
							);
							if(is_array($fileInfo))
							{
								$item['imageUrl'] = $fileInfo['src'];
							}
						}
						*/
					}
				}
			}
			elseif($entityTypeName === \CCrmOwnerType::CompanyName)
			{
				$dbResult = \CCrmCompany::GetListEx(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'ORIGINATOR_ID')
				);
				if(is_object($dbResult))
				{
					$typeList = \CCrmStatus::GetStatusList('COMPANY_TYPE');
					$industryList = \CCrmStatus::GetStatusList('INDUSTRY');

					while($fields = $dbResult->Fetch())
					{
						$descriptions = array();
						if(isset($typeList[$fields['COMPANY_TYPE']]))
						{
							$descriptions[] = $typeList[$fields['COMPANY_TYPE']];
						}
						if(isset($industryList[$fields['INDUSTRY']]))
						{
							$descriptions[] = $industryList[$fields['INDUSTRY']];
						}

						$entityID = (int)$fields['ID'];

						$resultItem = new Search\ResultItem(
							$fields['TITLE'],
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Company,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);

						if(!empty($descriptions))
						{
							$resultItem->setSubTitle(implode(', ', $descriptions));
						}

						if($icon = \Bitrix\Crm\Integration\Originator::getIcon($fields['ORIGINATOR_ID']))
						{
							$resultItem->setAttribute(
								'icon',
								[
									'src' => $icon['SRC'],
									'width' => $icon['WIDTH'],
									'height' => $icon['HEIGHT'],
								]
							);
						}

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;

						/*
						if(isset($fields['LOGO']) && $fields['LOGO'] > 0)
						{
							$fileInfo = \CFile::ResizeImageGet(
								$fields['LOGO'],
								array('width' => 100, 'height' => 100),
								BX_RESIZE_IMAGE_EXACT
							);
							if(is_array($fileInfo))
							{
								$item['imageUrl'] = $fileInfo['src'];
							}
						}
						*/
					}
				}
			}
			elseif($entityTypeName === \CCrmOwnerType::DealName)
			{
				$dbResult = \CCrmDeal::GetListEx(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
				);
				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$descriptions = array();
						if(isset($fields['COMPANY_TITLE']) && $fields['COMPANY_TITLE'] != '')
						{
							$descriptions[] = $fields['COMPANY_TITLE'];
						}

						$descriptions[] =\CCrmContact::PrepareFormattedName(
							array(
								'LOGIN' => '',
								'HONORIFIC' => isset($fields['CONTACT_HONORIFIC']) ? $fields['CONTACT_HONORIFIC'] : '',
								'NAME' => isset($fields['CONTACT_NAME']) ? $fields['CONTACT_NAME'] : '',
								'SECOND_NAME' => isset($fields['CONTACT_SECOND_NAME']) ? $fields['CONTACT_SECOND_NAME'] : '',
								'LAST_NAME' => isset($fields['CONTACT_LAST_NAME']) ? $fields['CONTACT_LAST_NAME'] : ''
							)
						);

						$entityID = (int)$fields['ID'];

						$resultItem = new Search\ResultItem(
							$fields['TITLE'],
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Deal,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);

						if(!empty($descriptions))
						{
							$resultItem->setSubTitle(implode(', ', $descriptions));
						}

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;
					}
				}
			}
			elseif($entityTypeName === \CCrmOwnerType::QuoteName)
			{
				$dbResult = \CCrmQuote::GetList(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
				);
				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$descriptions = array();
						if(isset($fields['COMPANY_TITLE']) && $fields['COMPANY_TITLE'] != '')
						{
							$descriptions[] = $fields['COMPANY_TITLE'];
						}

						$descriptions[] =\CCrmContact::PrepareFormattedName(
							array(
								'LOGIN' => '',
								'HONORIFIC' => isset($fields['CONTACT_HONORIFIC']) ? $fields['CONTACT_HONORIFIC'] : '',
								'NAME' => isset($fields['CONTACT_NAME']) ? $fields['CONTACT_NAME'] : '',
								'SECOND_NAME' => isset($fields['CONTACT_SECOND_NAME']) ? $fields['CONTACT_SECOND_NAME'] : '',
								'LAST_NAME' => isset($fields['CONTACT_LAST_NAME']) ? $fields['CONTACT_LAST_NAME'] : ''
							)
						);

						$entityID = (int)$fields['ID'];

						$resultItem = new Search\ResultItem(
							$fields['TITLE'],
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Quote,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);

						if(!empty($descriptions))
						{
							$resultItem->setSubTitle(implode(', ', $descriptions));
						}

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;
					}
				}
			}
			elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
			{
				$dbResult = \CCrmInvoice::GetList(
					array(),
					array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'ORDER_TOPIC', 'UF_COMPANY_ID', 'UF_CONTACT_ID')
				);
				if(is_object($dbResult))
				{
					while($fields = $dbResult->Fetch())
					{
						$descriptions = array();
						if(isset($fields['UF_COMPANY_ID']) && $fields['UF_COMPANY_ID'] > 0)
						{
							$companyTitle = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $fields['UF_COMPANY_ID']);
							if($companyTitle !== '')
							{
								$descriptions[] = $companyTitle;
							}
						}

						if(isset($fields['UF_CONTACT_ID']) && $fields['UF_CONTACT_ID'] > 0)
						{
							$contactName = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $fields['UF_CONTACT_ID']);
							if($contactName !== '')
							{
								$descriptions[] = $contactName;
							}
						}

						$entityID = (int)$fields['ID'];
						$resultItem = new Search\ResultItem(
							$fields['ORDER_TOPIC'],
							new Uri(
								\CCrmOwnerType::GetEntityShowPath(
									\CCrmOwnerType::Invoice,
									$entityID,
									false
								)
							)
						);

						$resultItem->setModule('crm');
						$resultItem->setType($entityTypeName);
						$resultItem->setId($entityID);

						if(!empty($descriptions))
						{
							$resultItem->setSubTitle(implode(', ', $descriptions));
						}

						$results["{$entityTypeName}:{$fields['ID']}"] = $resultItem;
					}
				}
			}
		}

		foreach($map as $entityTypeName => $entityIDs)
		{
			if($entityTypeName === \CCrmOwnerType::DealName)
			{
				continue;
			}

			$dbResult = \CCrmFieldMulti::GetListEx(
				array(),
				array(
					'=ENTITY_ID' => $entityTypeName,
					'@ELEMENT_ID' => $entityIDs,
					'@TYPE_ID' => ['PHONE', 'EMAIL']
				)
			);

			$attributes = array();
			while($fields = $dbResult->Fetch())
			{
				$entityKey = "{$fields['ENTITY_ID']}:{$fields['ELEMENT_ID']}";
				if(!isset($results[$entityKey]))
				{
					continue;
				}

				if(!isset($attributes[$entityKey]))
				{
					$attributes[$entityKey] = array();
				}

				$key = mb_strtolower($fields['TYPE_ID']);
				if(!isset($attributes[$entityKey][$key]))
				{
					$attributes[$entityKey][$key] = array();
				}

				$attributes[$entityKey][$key][] = array(
					'type' => $fields['VALUE_TYPE'],
					'value' => $fields['VALUE']
				);
			}

			foreach($attributes as $entityKey => $data)
			{
				if(!isset($results[$entityKey]))
				{
					continue;
				}

				foreach($data as $key => $items)
				{
					$results[$entityKey]->setAttribute($key, $items);
				}
			}
		}

		self::sortByTitle($results, $priorityIds);

		return $results;
	}

	/**
	 * @param array $items
	 * @return array
	 */
	public static function prepareSearchResultsJson(array $items)
	{
		return array_map(
			function($item)
			{
				/** @var Search\ResultItem $item */
				return $item->jsonSerialize();
			},
			self::prepareSearchResults($items)
		);
	}

	/**
	 * @param array $results
	 * @param array $priorityIds
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected static function sortByTitle(array &$results, array $priorityIds = []): void
	{
		$results = array_values($results);
		if(count($results) > 1)
		{
			if (count($priorityIds))
			{
				$priorityResults = [];
				$otherResults = [];
				foreach($results as $result)
				{
					if (in_array($result['id'], $priorityIds, true))
					{
						$priorityResults[] = $result;
					}
					else
					{
						$otherResults[] = $result;
					}
				}

				Main\Type\Collection::sortByColumn(
					$priorityResults,
					['title' => SORT_ASC]
				);
				Main\Type\Collection::sortByColumn(
					$otherResults,
					['title' => SORT_ASC]
				);
				$results = array_merge($priorityResults, $otherResults);
			}
			else
			{
				Main\Type\Collection::sortByColumn(
					$results,
					['title' => SORT_ASC]
				);
			}
		}
	}
}
