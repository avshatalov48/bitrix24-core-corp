<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

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
			$this->indexedTypeScopeMap = Crm\Integrity\DuplicateIndexBuilder::getExistedTypeScopeMap($this->entityTypeID, $this->userID);
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
		$this->entityInfos = [];
		if($this->entityIDs !== null)
		{
			foreach($this->entityIDs as $entityID)
			{
				if(!isset($this->entityInfos[$entityID]))
				{
					$this->entityInfos[$entityID] = array();
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

			\CCrmOwnerType::PrepareEntityInfoBatch($this->entityTypeID, $this->entityInfos, $this->enablePermissionCheck, $entityInfoOptions);

			$multiFieldResult = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $this->entityTypeName, 'ELEMENT_ID' => array_keys($this->entityInfos))
			);
			while($multiField = $multiFieldResult->Fetch())
			{
				$entityID = $multiField['ELEMENT_ID'];
				$key = $multiField['COMPLEX_ID'];
				if(!isset($this->entityInfos[$entityID][$key]))
				{
					$this->entityInfos[$entityID][$key] = [];
				}
				$this->entityInfos[$entityID][$key][] = $multiField['VALUE'];
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
							Crm\EntityRequisite::prepareEntityInfoBatch($this->entityTypeID, $this->entityInfos, $scope, $typeName);
						}
						elseif(($typeID & Crm\Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
						{
							Crm\EntityBankDetail::prepareEntityInfoBatch($this->entityTypeID, $this->entityInfos, $scope, $typeName);
						}
					}
				}
			}
			$this->arResult['ENTITY_INFOS'] = $this->entityInfos;
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

		$this->scope = isset($this->arParams['~SCOPE']) ? $this->arParams['~SCOPE'] : $this->request->get('scope');
		if(!is_string($this->scope))
		{
			$this->scope = Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE;
		}

		$selectedTypes = isset($this->arParams['~TYPES']) ? $this->arParams['~TYPES'] : $this->request->get('types');
		if(!empty($selectedTypes))
		{
			$this->selectedTypes = Crm\Integrity\DuplicateIndexType::splitType($selectedTypes);
		}
		else
		{
			$typeNames = $this->request->get('typeNames');
			$typeNames = is_string($typeNames) ? explode(',', $typeNames) : [];
			$this->selectedTypes = array_map(['\Bitrix\Crm\Integrity\DuplicateIndexType', 'resolveID'], $typeNames);
		}

		if(empty($this->selectedTypes))
		{
			$this->selectedTypes = [
				Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
				Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL
			];

			if($this->entityTypeID === CCrmOwnerType::Company)
			{
				$this->selectedTypes[] = Crm\Integrity\DuplicateIndexType::ORGANIZATION;
			}
			else
			{
				$this->selectedTypes[] = Crm\Integrity\DuplicateIndexType::PERSON;
			}
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
				'sort' => 'organization',
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
				'sort' => 'person',
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
			'sort' => 'communication_phone',
			'default' => true,
			'extras' => [
				'sortTypeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
				'typeId' => Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE
			]
		];

		$this->arResult['HEADERS'][] = [
			'id' => 'EMAIL',
			'name' => Loc::getMessage('CRM_DEDUPE_GRID_COL_EMAIL'),
			'sort' => 'communication_email',
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

		if($this->scope !== Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE)
		{
			$typeDescriptions = Crm\Integrity\DuplicateIndexType::getAllDescriptions();
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

					$name = Crm\Integrity\DuplicateIndexType::resolveName($typeID);
					$this->arResult['HEADERS'][] =[
						'id' => $name,
						'name' => isset($typeDescriptions[$typeID][$scope]) ? $typeDescriptions[$typeID][$scope] : $name,
						'sort' => mb_strtolower($name),
						'extras' => [
							'sortTypeId' => $typeID,
							'typeId' => $typeID,
							'scope' => $scope
						]
					];
				}
			}
		}

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

		$sortTypeID = Crm\Integrity\DuplicateIndexType::UNDEFINED;
		$sortOrder = SORT_ASC;
		if(!empty($this->arResult['SORT']))
		{
			$sortBy = array_keys($this->arResult['SORT'])[0];
			$sortTypeID= Crm\Integrity\DuplicateIndexType::resolveID($sortBy);
			$sortOrder = strcasecmp($this->arResult['SORT'][$sortBy], 'DESC') ? SORT_DESC : SORT_ASC;
		}

		if($sortTypeID === Crm\Integrity\DuplicateIndexType::UNDEFINED)
		{
			$sortTypeID = $this->layoutID === CCrmOwnerType::Company
				? Crm\Integrity\DuplicateIndexType::ORGANIZATION : Crm\Integrity\DuplicateIndexType::PERSON;
		}

		$gridNavParams = $gridOptions->GetNavParams([ 'nPageSize' => 10 ]);
		$gridNavParams['bShowAll'] = false;
		if(isset($gridNavParams['nPageSize']) && $gridNavParams['nPageSize'] > 100)
		{
			$gridNavParams['nPageSize'] = 100;
		}

		$pageNum = (int)$this->request->get('page');
		if($pageNum <= 0)
		{
			$pageNum = 1;
		}

		$itemsPerPage = $gridNavParams['nPageSize'];
		$enableNextPage = false;

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
				$list = new Crm\Integrity\DuplicateList(
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
		else
		{
			$list = new Crm\Integrity\DuplicateList(
				Crm\Integrity\DuplicateIndexType::joinType($this->selectedTypes),
				$this->entityTypeID,
				$this->userID,
				$this->enablePermissionCheck
			);

			$list->setScope($this->scope);
			if(Crm\Integrity\DuplicateIndexType::isSingle($sortTypeID))
			{
				$list->setSortTypeID($sortTypeID);
			}
			$list->setSortOrder($sortOrder);

			$items = $list->getRootItems(($pageNum - 1) * $itemsPerPage, $itemsPerPage + 1);
			if(count($items) > $itemsPerPage)
			{
				$enableNextPage = true;
				array_pop($items);
			}

			$this->arResult['PAGINATION'] = [
				'PAGE_NUM' => $pageNum,
				'ENABLE_NEXT_PAGE' => $enableNextPage,
				'URL' => $APPLICATION->GetCurPageParam(
					'',
					[ 'apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal' ]
				)
			];

			/** @var Bitrix\Crm\Integrity\Duplicate $item **/
			foreach($items as $item)
			{
				$this->entityIDs[] = $item->getRootEntityID();
			}
			$this->prepareEntityInfos();

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

				$criterion->getTextTotals(1);

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
}
