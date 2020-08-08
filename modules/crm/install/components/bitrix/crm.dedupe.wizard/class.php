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

class CCrmDedupeWizardComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var int */
	protected $entityTypeID = CCrmOwnerType::Undefined;
	/** @var string */
	protected $entityTypeName = '';
	/** @var  CCrmPerms|null */
	private $userPermissions = null;

	public function executeComponent()
	{
		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		$this->entityTypeID = $this->arResult['ENTITY_TYPE_ID'] = isset($this->arParams['ENTITY_TYPE_ID']) ? (int)$this->arParams['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;

		$hasReadPermission = Crm\Security\EntityAuthorization::checkReadPermission(
			$this->entityTypeID,
			0,
			$this->userPermissions
		);
		$hasUpdatePermission = Crm\Security\EntityAuthorization::checkUpdatePermission(
			$this->entityTypeID,
			0,
			$this->userPermissions
		);
		$hasDeletePermission = Crm\Security\EntityAuthorization::checkDeletePermission(
			$this->entityTypeID,
			0,
			$this->userPermissions
		);

		if (!($hasReadPermission && $hasUpdatePermission && $hasDeletePermission &&
			Crm\Restriction\RestrictionManager::isDuplicateControlPermitted()))
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		$enableFlag = $this->request->get('enable');
		if(is_string($enableFlag) && $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			Crm\Settings\LayoutSettings::getCurrent()->enableDedupeWizard(strcasecmp($enableFlag, 'Y') == 0);
		}

		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'entity_merger';
		$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		$this->arResult['CONTEXT_ID'] = isset($this->arParams['CONTEXT_ID']) ? $this->arParams['CONTEXT_ID'] : $this->entityTypeName;

		$this->arResult['PATH_TO_MERGER'] = isset($this->arParams['PATH_TO_MERGER']) ? $this->arParams['PATH_TO_MERGER'] : '';
		if($this->arResult['PATH_TO_MERGER'] !== '')
		{
			$this->arResult['PATH_TO_MERGER'] = \CHTTP::urlAddParams($this->arResult['PATH_TO_MERGER'], array('queue' => mb_strtolower($this->entityTypeName).'_dedupe_queue'));
		}

		$this->arResult['PATH_TO_DEDUPE_LIST'] = isset($this->arParams['PATH_TO_DEDUPE_LIST'])
			? $this->arParams['PATH_TO_DEDUPE_LIST'] : '';

		$this->arResult['PATH_TO_ENTITY_LIST'] = isset($this->arParams['PATH_TO_ENTITY_LIST'])
			? $this->arParams['PATH_TO_ENTITY_LIST'] : '';
		
		$indexedTypeScopeMap = array();
		foreach(Crm\Integrity\DuplicateIndexBuilder::getExistedTypeScopeMap($this->entityTypeID, $this->userID) as
		        $typeID => $scopes
		)
		{
			$indexedTypeScopeMap[$typeID] = array_fill_keys($scopes, true);
		}
		$this->arResult['INDEXED_TYPE_SCOPE_MAP'] = $indexedTypeScopeMap;
		$this->arResult['TYPES'] = array_keys($indexedTypeScopeMap);

		$this->arResult['TYPE_DESCRIPTIONS'] = Crm\Integrity\DuplicateIndexType::getAllDescriptions();
		$selectedTypes = array();

		//TYPE INDEX INFO
		$typeScopeMap = array();
		foreach(Crm\Integrity\DuplicateManager::getDedupeTypeScopeMap($this->entityTypeID) as $typeID => $scopes)
		{
			$typeScopeMap[$typeID] = array_fill_keys($scopes, true);
		}
		$registeredScopes = array();
		foreach($typeScopeMap as $typeID => $scopes)
		{
			foreach(array_keys($scopes) as $scope)
			{
				if(!isset($registeredScopes[$scope]))
				{
					$registeredScopes[$scope] = true;
				}
			}
		}

		foreach($typeScopeMap as $typeID => $scopes)
		{
			if(count($scopes) === 1 && isset($scopes['']))
			{
				$typeScopeMap[$typeID] = $registeredScopes;
			}
		}

		$config = CUserOptions::GetOption('crm.dedupe.wizard', $this->guid, null);
		if(!$config)
		{
			$config = array(
				'scope' => Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE,
				'typeNames' => array()
			);

			if($this->entityTypeID === CCrmOwnerType::Contact || $this->entityTypeID === CCrmOwnerType::Lead)
			{
				$config['typeNames'][] = Crm\Integrity\DuplicateIndexType::PERSON_NAME;
			}
			if($this->entityTypeID === CCrmOwnerType::Company)
			{
				$config['typeNames'][] = Crm\Integrity\DuplicateIndexType::ORGANIZATION_NAME;
			}

			$config['typeNames'][] = Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE_NAME;
			$config['typeNames'][] = Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL_NAME;
		}
		$this->arResult['CONFIG'] = $config;

		$this->arResult['DEFAULT_SCOPE'] = Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE;
		$this->arResult['CURRENT_SCOPE'] = $config['scope'];

		$scopeListItems = array(
			$this->arResult['DEFAULT_SCOPE'] => Loc::getMessage('CRM_DEDUPE_WIZARD_DEFAULT_SCOPE_TITLE')
		);

		$allScopes = Crm\Integrity\DuplicateIndexType::getAllScopeTitles();
		foreach($allScopes as $scope => $title)
		{
			if(!empty($scope) && isset($registeredScopes[$scope]))
				$scopeListItems[$scope] = $title;
		}
		$this->arResult['SCOPE_LIST_ITEMS'] = $scopeListItems;

		$typeInfos = array();
		foreach($typeScopeMap as $typeID => $scopes)
		{
			foreach(array_keys($scopes) as $scope)
			{
				$typeLayoutID = \CCrmOwnerType::Undefined;
				if($typeID === Crm\Integrity\DuplicateIndexType::ORGANIZATION)
				{
					$typeLayoutID = \CCrmOwnerType::Company;
				}
				elseif($typeID === Crm\Integrity\DuplicateIndexType::PERSON)
				{
					$typeLayoutID = \CCrmOwnerType::Contact;
				}

				$groupName = '';
				if($typeID === Crm\Integrity\DuplicateIndexType::PERSON
					|| $typeID === Crm\Integrity\DuplicateIndexType::ORGANIZATION)
				{
					$groupName = 'denomination';
				}
				elseif($typeID === Crm\Integrity\DuplicateIndexType::COMMUNICATION_PHONE
					|| $typeID === Crm\Integrity\DuplicateIndexType::COMMUNICATION_EMAIL)
				{
					$groupName = 'communication';
				}
				elseif(($typeID & Crm\Integrity\DuplicateIndexType::REQUISITE) === $typeID)
				{
					$groupName = 'requisite';
				}
				elseif(($typeID & Crm\Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
				{
					$groupName = 'bank_detail';
				}

				$extTypeID = $this->getExtendedTypeID($typeID, $scope);
				if(isset($this->arResult['TYPE_DESCRIPTIONS'][$typeID][$scope]))
				{
					$description = $this->arResult['TYPE_DESCRIPTIONS'][$typeID][$scope];
				}
				elseif(isset($this->arResult['TYPE_DESCRIPTIONS'][$typeID][Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE]))
				{
					$description = $this->arResult['TYPE_DESCRIPTIONS'][$typeID][Crm\Integrity\DuplicateIndexType::DEFAULT_SCOPE];
				}
				else
				{
					$description = $extTypeID;
				}

				$isIndexed = isset($indexedTypeScopeMap[$typeID][$scope]);
				$typeInfos[$extTypeID] = array(
					'ID' => $extTypeID,
					'TYPE_ID' => $typeID,
					'NAME' => Crm\Integrity\DuplicateIndexType::resolveName($typeID),
					'SCOPE' => $scope,
					'DESCRIPTION' => $description,
					'IS_SELECTED' => false,
					'IS_INDEXED' => $isIndexed,
					'IS_UNDERSTATED' => false,
					'LAYOUT_NAME' => CCrmOwnerType::ResolveName($typeLayoutID),
					'GROUP_NAME' => $groupName
				);
			}
		}

		//LAYOUT_ID [CONTACT | COMPANY]
		if($this->entityTypeID !== \CCrmOwnerType::Lead)
		{
			$enableLayout = false;
			$layoutID = $this->entityTypeID;
		}
		else
		{
			$enableLayout = true;

			$isOrganizationSelected =  $typeInfos[Crm\Integrity\DuplicateIndexType::ORGANIZATION]['IS_SELECTED'];
			$isPersonSelected = $typeInfos[Crm\Integrity\DuplicateIndexType::PERSON]['IS_SELECTED'];
			$isPersonIndexed = $typeInfos[Crm\Integrity\DuplicateIndexType::PERSON]['IS_INDEXED'];

			$layoutID = !$isPersonSelected && ($isOrganizationSelected || !$isPersonIndexed)
				? \CCrmOwnerType::Company : \CCrmOwnerType::Contact;

			//REMOVING OF UNUSED INDEXED TYPES
			if($layoutID === \CCrmOwnerType::Contact)
			{
				unset($selectedTypes[Crm\Integrity\DuplicateIndexType::ORGANIZATION]);
				$typeInfos[Crm\Integrity\DuplicateIndexType::ORGANIZATION]['IS_SELECTED'] = false;
				if($isPersonSelected)
				{
					$typeInfos[Crm\Integrity\DuplicateIndexType::ORGANIZATION]['IS_UNDERSTATED'] = true;
				}
			}
			elseif($layoutID === \CCrmOwnerType::Company)
			{
				unset($selectedTypes[Crm\Integrity\DuplicateIndexType::PERSON]);
				$typeInfos[Crm\Integrity\DuplicateIndexType::PERSON]['IS_SELECTED'] = false;
				if($isOrganizationSelected)
				{
					$typeInfos[Crm\Integrity\DuplicateIndexType::PERSON]['IS_UNDERSTATED'] = true;
				}
			}
		}

		$this->arResult['ENABLE_LAYOUT'] = $enableLayout;
		$this->arResult['LAYOUT_ID'] = $layoutID;
		$this->arResult['TYPE_INFOS'] = $typeInfos;

		$this->includeComponentTemplate();
	}

	protected function getExtendedTypeID($typeID, $scope)
	{
		if($scope === '')
		{
			return $typeID;
		}
		return "{$typeID}|{$scope}";
	}
}