<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Main\UI\PageNavigation;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmDedupeGridComponent extends CBitrixComponent
{
	/** @var string */
	protected $guid = '';
	/** @var int */
	private $userID = 0;
	/** @var  CCrmPerms|null */
	private $userPermissions = null;
	/** @var bool */
	private $enablePermissionCheck = true;
	/** @var int */
	private $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var string */
	private $entityTypeName = '';
	/** @var string */
	private $scope = '';
	/** @var array */
	private $selectedTypes = [];
	/** @var array */
	private $typeScopeMap = null;
	/** @var array */
	private $indexedTypeScopeMap = null;
	/** @var string */
	private $matchHash = '';
	/** @var int */
	private $layoutID = \CCrmOwnerType::Undefined;
	/** @var array */
	private $entityIDs = null;
	/** @var array */
	private $entityInfos = null;
	/** @var bool */
	private $isAutomatic = false;

	protected $navParamName = 'page';

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->userID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$this->enablePermissionCheck = !\CCrmPerms::IsAdmin($this->userID);
	}

	protected function getIndexedTypeMap()
	{
		if($this->indexedTypeScopeMap === null)
		{
			$this->indexedTypeScopeMap = Crm\Integrity\DuplicateManager::getExistedTypeScopeMap(
				$this->entityTypeID,
				$this->userID,
				$this->isAutomatic
			);
		}

		return $this->indexedTypeScopeMap;
	}
	protected function isTypeIndexed($typeID, $scope)
	{
		$map = $this->getIndexedTypeMap();
		return isset($map[$typeID]) && is_array($map[$typeID]) && in_array($scope, $map[$typeID], true);
	}

	protected function isTypeSelected($typeID)
	{
		return in_array($typeID, $this->selectedTypes, true);
	}

	protected function prepareEntityInfos()
	{
		$this->entityInfos = $this->loadEntityInfos($this->entityIDs);
		$this->arResult['ENTITY_INFOS'] = $this->entityInfos;
	}
	protected function loadEntityInfos($entityIds)
	{
		$entityInfos = [];
		if($entityIds !== null)
		{
			foreach($entityIds as $entityID)
			{
				if(!isset($entityInfos[$entityID]))
				{
					$entityInfos[$entityID] = array();
				}
			}

			$entityInfoOptions = array(
				'ENABLE_EDIT_URL' => false,
				'ENABLE_RESPONSIBLE' => true,
				'ENABLE_RESPONSIBLE_PHOTO' => true,
				'USER_PROFILE_PATH' => $this->arResult['PATH_TO_USER_PROFILE']
			);
			if($this->entityTypeID === CCrmOwnerType::Lead)
			{
				$entityInfoOptions[$this->layoutID === CCrmOwnerType::Company ? 'TREAT_AS_COMPANY' : 'TREAT_AS_CONTACT'] = true;
			}

			\CCrmOwnerType::PrepareEntityInfoBatch($this->entityTypeID, $entityInfos, $this->enablePermissionCheck, $entityInfoOptions);

			foreach($entityInfos as $entityID => $entityInfo)
			{
				$entityInfos[$entityID]['IS_VALID'] = !empty($entityInfo);
			}

			$multiFieldResult = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $this->entityTypeName, 'ELEMENT_ID' => array_keys($entityInfos))
			);
			while($multiField = $multiFieldResult->Fetch())
			{
				$entityID = $multiField['ELEMENT_ID'];
				$key = $multiField['COMPLEX_ID'];
				if(!isset($entityInfos[$entityID][$key]))
				{
					$entityInfos[$entityID][$key] = [];
				}
				$entityInfos[$entityID][$key][] = $multiField['VALUE'];
			}

			if($this->scope !== Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE)
			{
				foreach($this->typeScopeMap as $typeID => $scopes)
				{
					if(($typeID & Crm\Integrity\DuplicateIndexType::REQUISITE) === 0
						&& ($typeID & Crm\Integrity\DuplicateIndexType::BANK_DETAIL) === 0)
					{
						continue;
					}

					foreach($scopes as $scope)
					{
						if ($scope !== $this->scope)
						{
							continue;
						}

						$typeName = Crm\Integrity\DuplicateIndexType::resolveName($typeID);
						if(($typeID & Crm\Integrity\DuplicateIndexType::REQUISITE) === $typeID)
						{
							Crm\EntityRequisite::prepareEntityInfoBatch($this->entityTypeID, $entityInfos, $scope, $typeName);
						}
						elseif(($typeID & Crm\Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
						{
							Crm\EntityBankDetail::prepareEntityInfoBatch($this->entityTypeID, $entityInfos, $scope, $typeName);
						}
					}
				}
			}
			return $entityInfos;
		}
	}

	protected function prepareIdentifier($entityID, $typeID, $matchHash)
	{
		return implode(
			'|',
			[
				$this->entityTypeName,
				$entityID,
				Crm\Integrity\DuplicateIndexType::resolveName($typeID),
				$matchHash,
				$this->scope
			]
		);
	}

	protected function parseIdentifier($ID)
	{
		$result = [];
		$parts = explode('|', $ID);
		if(count($parts) >= 5)
		{
			$result['ENTITY_TYPE_NAME'] = $parts[0];
			$result['ENTITY_ID'] = $parts[1];
			$result['TYPE_NAME'] = $parts[2];
			$result['MATCH_HASH'] = $parts[3];
			$result['SCOPE'] = $parts[4];
		}

		return $result;
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmPerms::IsAccessEnabled())
		{
			ShowError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		//region Params
		$this->guid = isset($this->arParams['~GUID']) ? $this->arParams['~GUID'] : '';
		if($this->guid === '')
		{
			$this->guid = 'dedupe_grid';
		}
		$this->arResult['GRID_ID'] = $this->guid;

		$this->arResult['PATH_TO_USER_PROFILE'] = isset($this->arParams['~PATH_TO_USER_PROFILE'])
			? $this->arParams['~PATH_TO_USER_PROFILE'] : '/company/personal/user/#user_id#/';

		$this->entityTypeID = $this->arResult['ENTITY_TYPE_ID'] = isset($this->arParams['~ENTITY_TYPE_ID'])
			? (int)$this->arParams['~ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityTypeName = $this->arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($this->entityTypeID);

		$this->isAutomatic = ($this->request->get('is_automatic') === 'yes');

		$progressData = null;
		if ($this->isAutomatic)
		{
			$autosearchSettings = \Bitrix\Crm\Integrity\AutoSearchUserSettings::getForUserByEntityType($this->entityTypeID, $this->userID);
			if ($autosearchSettings && $autosearchSettings->getStatusId() === Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING)
			{
				$progressData = $autosearchSettings->getProgressData();
				$this->scope = $progressData['CURRENT_SCOPE'];
				$this->selectedTypes = $progressData['TYPE_IDS'];
			}
		}
		else
		{
			$this->scope = isset($this->arParams['~SCOPE']) ? $this->arParams['~SCOPE'] : $this->request->get('scope');

			$selectedTypes = isset($this->arParams['~TYPES']) ? $this->arParams['~TYPES'] : $this->request->get('types');
			if (!empty($selectedTypes))
			{
				$this->selectedTypes = Crm\Integrity\DuplicateIndexType::splitType($selectedTypes);
			}
			else
			{
				$typeNames = $this->request->get('typeNames');
				$typeNames = is_string($typeNames) ? explode(',', $typeNames) : [];
				$this->selectedTypes = array_map(['\Bitrix\Crm\Integrity\DuplicateIndexType', 'resolveID'], $typeNames);
			}

			if (empty($this->selectedTypes))
			{
				$this->selectedTypes = [
					Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
					Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL
				];

				if ($this->entityTypeID === CCrmOwnerType::Company)
				{
					$this->selectedTypes[] = Crm\Integrity\DuplicateIndexType::ORGANIZATION;
				}
				else
				{
					$this->selectedTypes[] = Crm\Integrity\DuplicateIndexType::PERSON;
				}
			}
		}

		if (!is_string($this->scope))
		{
			$this->scope = Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE;
		}
		//endregion

		if(!CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			ShowError(Loc::getMessage('CRM_DEDUPE_GRID_ENTITY_TYPE_NOT_DEFINED'));
			return;
		}

		if($this->entityTypeID !== CCrmOwnerType::Contact
			&& $this->entityTypeID !== CCrmOwnerType::Company
			&& $this->entityTypeID !== CCrmOwnerType::Lead)
		{
			ShowError(
				Loc::getMessage(
					'CRM_DEDUPE_GRID_INVALID_ENTITY_TYPE',
					array('#TYPE_NAME#' => CCrmOwnerType::ResolveName($this->entityTypeID))
				)
			);
			return;
		}

		/* Followed messages are used for title:
		 * CRM_DEDUPE_GRID_CONTACT_PAGE_TITLE
		 * CRM_DEDUPE_GRID_COMPANY_PAGE_TITLE
		 * CRM_DEDUPE_GRID_LEAD_PAGE_TITLE
		 */
		//$APPLICATION->SetTitle(Loc::getMessage("CRM_DEDUPE_GRID_{$this->entityTypeName}_PAGE_TITLE"));
		$APPLICATION->SetTitle(Loc::getMessage("CRM_DEDUPE_GRID_PAGE_TITLE"));

		$this->typeScopeMap = Crm\Integrity\DuplicateManager::getDedupeTypeScopeMap($this->entityTypeID);
		if($this->entityTypeID !== CCrmOwnerType::Lead)
		{
			$this->layoutID = $this->entityTypeID;
		}
		else
		{
			$isOrganizationSelected =  $this->isTypeSelected(Crm\Integrity\DuplicateIndexType::ORGANIZATION);
			$isPersonSelected = $this->isTypeSelected(Crm\Integrity\DuplicateIndexType::PERSON);
			$isPersonIndexed = $this->isTypeIndexed(Crm\Integrity\DuplicateIndexType::PERSON, $this->scope);

			$this->layoutID = !$isPersonSelected && ($isOrganizationSelected || !$isPersonIndexed)
				? CCrmOwnerType::Company : CCrmOwnerType::Contact;
		}
		$this->arResult['LAYOUT_ID'] = $this->layoutID;

		$this->arResult['HEADERS'] = [];
		if($this->layoutID === \CCrmOwnerType::Company)
		{
			$this->arResult['HEADERS'][] = [
				'id' => 'ORGANIZATION',
				'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_ORGANIZATION'),
				'sort' => false,
				'default' => true,
				'shift' => true,
				'extras' => [
					'sortTypeId' => Crm\Integrity\DuplicateIndexType::ORGANIZATION,
					'typeId' => Crm\Integrity\DuplicateIndexType::ORGANIZATION
				]
			];
		}
		else
		{
			$this->arResult['HEADERS'][] = [
				'id' => 'PERSON',
				'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_PERSON'),
				'sort' => false,
				'default' => true,
				'shift' => true,
				'extras' => [
					'sortTypeId' => Crm\Integrity\DuplicateIndexType::PERSON,
					'typeId' => Crm\Integrity\DuplicateIndexType::PERSON
				]
			];
		}

		$this->arResult['HEADERS'][] = [
			'id' => 'MATCH',
			'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_MATCH'),
			'sort' => false,
			'default' => true
		];

		$this->arResult['HEADERS'][] = [
			'id' => 'PHONE',
			'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_PHONE'),
			'sort' => false,
			'default' => true,
			'extras' => [
				'sortTypeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
				'typeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE
			]
		];

		$this->arResult['HEADERS'][] = [
			'id' => 'EMAIL',
			'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_EMAIL'),
			'sort' => false,
			'default' => true,
			'extras' => [
				'sortTypeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL,
				'typeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL
			]
		];

		$this->arResult['HEADERS'][] = [
			'id' => 'RESPONSIBLE',
			'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_RESPONSIBLE'),
			'sort' => false,
			'default' => true
		];

		$gridOptions = new Main\Grid\Options($this->guid);
		//Suppress processing of expanded rows
		$gridOptions->setExpandedRows([]);
		$gridSorting = $gridOptions->GetSorting(
			array(
				'sort' => $this->layoutID === CCrmOwnerType::Company ? array('organization' => 'asc') : array('person' => 'asc'),
				'vars' => array('by' => 'by', 'order' => 'order')
			)
		);
		$this->arResult['SORT'] = $gridSorting['sort'];
		$this->arResult['SORT_VARS'] = $gridSorting['vars'];

		$gridNavParams = $gridOptions->GetNavParams();
		$gridNavParams['bShowAll'] = false;
		if(isset($gridNavParams['nPageSize']) && $gridNavParams['nPageSize'] > 100)
		{
			$gridNavParams['nPageSize'] = 100;
		}
		$itemsPerPage = $gridNavParams['nPageSize'];

		$this->entityIDs = [];
		if (Main\Grid\Context::isInternalRequest() &&
			$this->request->get('action') === Main\Grid\Actions::GRID_GET_CHILD_ROWS
		)
		{
			$parentID = $this->request->get('parent_id');
			$params = $this->parseIdentifier($parentID);

			if(isset($params['TYPE_NAME']))
			{
				$typeID = Crm\Integrity\DuplicateIndexType::resolveID($params['TYPE_NAME']);
				$this->selectedTypes = $typeID !== Crm\Integrity\DuplicateIndexType::UNDEFINED ? [ $typeID ] : [];
			}

			if(isset($params['MATCH_HASH']))
			{
				$this->matchHash = $params['MATCH_HASH'];
			}

			if(isset($params['SCOPE']))
			{
				$this->scope = $params['SCOPE'];
			}

			$items = null;
			if(!empty($this->selectedTypes))
			{
				$list = \Bitrix\Crm\Integrity\DuplicateListFactory::create(
					$this->isAutomatic,
					Crm\Integrity\DuplicateIndexType::joinType($this->selectedTypes),
					$this->entityTypeID,
					$this->userID,
					$this->enablePermissionCheck
				);
				$list->setScope($this->scope);
				$list->setMatchHash($this->matchHash);

				$items = $list->getRootItems();
			}

			if(!empty($items))
			{
				$item = $items[0];

				$criterion = $item->getCriterion();
				$rootEntityID = $item->getRootEntityID();

				$dup = $criterion->createDuplicate(
					$this->entityTypeID,
					$rootEntityID,
					$this->userID,
					$this->enablePermissionCheck,
					false
				);

				if($dup)
				{
					$entities = $dup->getEntitiesByType($this->entityTypeID);
					foreach($entities as $entity)
					{
						$this->entityIDs[] = $entity->getEntityID();
					}
					$this->prepareEntityInfos();

					$typeID = $criterion->getIndexTypeID();
					$matchHash = $criterion->getMatchHash();

					$this->arResult['ROW_DATA'] = [];
					foreach($this->entityIDs as $entityID)
					{
						$this->arResult['ROW_DATA'][] = [
							'ID' => $this->prepareIdentifier($entityID, $typeID, $matchHash),
							'ENTITY_ID' => $entityID,
							'ROOT_ENTITY_ID' => $rootEntityID,
							'TYPE_ID' => $typeID,
							'MATCH_HASH' => $matchHash,
							'GROUP_KEY' => $parentID
						];
					}
				}
			}
		}
		elseif ($this->isAutomatic && !($autosearchSettings &&
			$autosearchSettings->getStatusId() === Crm\Integrity\AutoSearchUserSettings::STATUS_CONFLICTS_RESOLVING))
		{
			// empty list
		}
		else
		{
			$list = \Bitrix\Crm\Integrity\DuplicateListFactory::create(
				$this->isAutomatic,
				Crm\Integrity\DuplicateIndexType::joinType($this->selectedTypes),
				$this->entityTypeID,
				$this->userID,
				$this->enablePermissionCheck
			);
			$list->setScope($this->scope);
			$list->enableNaturalSort(true);

			$totalRowsCount = $list->getRootItemCount();
			$this->arResult['TOTAL_ROWS_COUNT'] = $totalRowsCount;

			$nav = $this->getPageNavigation($totalRowsCount, $itemsPerPage);
			$this->arResult['NAV_OBJECT'] = $nav;
			$this->arResult['NAV_PARAMS'] = [
				'SHOW_ALWAYS' => false,
				'BASE_LINK' => $APPLICATION->GetCurPageParam(
					'', ['apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'])
			];
			$this->arResult['NAV_PARAM_NAME'] = $this->navParamName;

			$items = $list->getRootItems(($nav->getCurrentPage() - 1) * $itemsPerPage, $itemsPerPage);

			/** @var Bitrix\Crm\Integrity\Duplicate $item **/
			foreach($items as $item)
			{
				$this->entityIDs[] = $item->getRootEntityID();
			}
			$this->prepareEntityInfos();

			$invalidEntityIds = [];
			foreach ($this->entityIDs as $entityID)
			{
				if (!$this->entityInfos[$entityID] ||
					!$this->entityInfos[$entityID]['IS_VALID'])
				{
					$invalidEntityIds[] = $entityID;
				}
			}

			if (!empty($invalidEntityIds))
			{
				$extraEntityIds = [];
				/** @var Bitrix\Crm\Integrity\Duplicate $item */
				foreach ($items as $itemIndex => $item)
				{
					if (in_array($item->getRootEntityID(), $invalidEntityIds))
					{
						$criterion = $item->getCriterion();

						$items[$itemIndex] = $this
							->getDuplicateIndexBuilder($criterion->getIndexTypeID())
							->buildDuplicateByMatchHash($criterion->getMatchHash());

						if (!$items[$itemIndex])
						{
							unset($items[$itemIndex]);
						}
						else
						{
							$this->entityIDs[] = $items[$itemIndex]->getRootEntityID();
							$extraEntityIds[] = $items[$itemIndex]->getRootEntityID();
						}
					}
				}
				if (!empty($extraEntityIds))
				{
					$extraEntityInfos = $this->loadEntityInfos($extraEntityIds);
					$this->entityInfos += $extraEntityInfos;
					$this->arResult['ENTITY_INFOS'] = $this->entityInfos;
				}
			}

			$this->arResult['ROW_DATA'] = [];
			foreach($items as $item)
			{
				$criterion = $item->getCriterion();
				if(!$criterion)
				{
					continue;
				}

				$rootEntityID = $item->getRootEntityID();
				$typeID = $criterion->getIndexTypeID();
				$matchHash = $criterion->getMatchHash();

				$rowID = $this->prepareIdentifier($rootEntityID, $typeID, $matchHash);
				$this->arResult['ROW_DATA'][] = [
					'ID' => $rowID,
					'ENTITY_ID' => $rootEntityID,
					'ROOT_ENTITY_ID' => $rootEntityID,
					'TYPE_ID' => $typeID,
					'MATCH_TEXT' => $criterion->getSummary(),
					'MATCH_HASH' => $matchHash,
					'GROUP_KEY' => $rowID,
				];
			}
		}

		$this->includeComponentTemplate();
	}

	protected function getPageNavigation(int $totalCount, int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation
			->allowAllRecords(false)
			->setPageSize($pageSize)
			->setRecordCount($totalCount)
			->initFromUri();

		return $pageNavigation;
	}

	protected function getDuplicateIndexBuilder($typeID)
	{
		if ($this->isAutomatic)
		{
			$builder = Crm\Integrity\DuplicateManager::createAutomaticIndexBuilder(
				$typeID,
				$this->entityTypeID,
				$this->userID,
				$this->enablePermissionCheck,
				array('SCOPE' => $this->scope)
			);
		}
		else
		{
			$builder = Crm\Integrity\DuplicateManager::createIndexBuilder(
				$typeID,
				$this->entityTypeID,
				$this->userID,
				$this->enablePermissionCheck,
				array('SCOPE' => $this->scope)
			);
		}

		return $builder;
	}
}
