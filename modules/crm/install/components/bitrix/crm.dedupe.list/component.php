<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
use Bitrix\Crm\Integrity;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdminUser = CCrmPerms::IsAdmin($userID);
$userPermissions = CCrmPerms::GetUserPermissions($userID);
$enablePermissionCheck = !$isAdminUser;

$hasReadPermission = CCrmContact::CheckReadPermission(0, $userPermissions)
	|| CCrmCompany::CheckReadPermission(0, $userPermissions)
	|| CCrmLead::CheckReadPermission(0, $userPermissions);

if (!($hasReadPermission && \Bitrix\Crm\Restriction\RestrictionManager::isDuplicateControlPermitted()))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['USER_ID'] = $userID;

$listID = isset($arParams['LIST_ID']) ? $arParams['LIST_ID'] : '';
if($listID === '')
{
	$listID = uniqid('dedupe_');
}
$arResult['LIST_ID'] = $listID;
$arResult['ERRORS'] = array();
$arResult['MESSAGES'] = array();

$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
if($entityTypeID === CCrmOwnerType::Undefined && isset($arParams['ENTITY_TYPE']))
{
	$entityTypeID = CCrmOwnerType::ResolveID($arParams['ENTITY_TYPE']);
}

if($entityTypeID !== CCrmOwnerType::Contact
	&& $entityTypeID !== CCrmOwnerType::Company
	&& $entityTypeID !== CCrmOwnerType::Lead)
{
	ShowError(GetMessage('CRM_DEDUPE_LIST_INVALID_ENTITY_TYPE', array('#TYPE_NAME#' => CCrmOwnerType::ResolveName($entityTypeID))));
	return;
}

$arResult['ENTITY_TYPE_ID'] = $entityTypeID;
$entityTypeName = $arResult['ENTITY_TYPE_NAME'] = \CCrmOwnerType::ResolveName($entityTypeID);

//TYPE INDEX INFO
$typeScopeMap = array();
foreach(Integrity\DuplicateManager::getDedupeTypeScopeMap($entityTypeID) as $typeID => $scopes)
	$typeScopeMap[$typeID] = array_fill_keys($scopes, true);
$registeredScopes = array();
foreach ($typeScopeMap as $typeID => $scopes)
{
	foreach (array_keys($scopes) as $scope)
	{
		if (!isset($registeredScopes[$scope]))
			$registeredScopes[$scope] = true;
	}
}
foreach ($typeScopeMap as $typeID => $scopes)
{
	if (count($scopes) === 1 && isset($scopes['']))
		$typeScopeMap[$typeID] = $registeredScopes;
}

$arResult['TYPE_SCOPE_MAP'] = $typeScopeMap;

$indexedTypeScopeMap = array();
foreach(Integrity\DuplicateIndexBuilder::getExistedTypeScopeMap($entityTypeID, $userID) as $typeID => $scopes)
	$indexedTypeScopeMap[$typeID] = array_fill_keys($scopes, true);
$arResult['INDEXED_TYPE_SCOPE_MAP'] = $indexedTypeScopeMap;
$arResult['TYPES'] = array_keys($indexedTypeScopeMap);

$typeDescriptions = $arResult['TYPE_DESCRIPTIONS'] = Integrity\DuplicateIndexType::getAllDescriptions();
$selectedTypes = array();

$filterTypeID = isset($_GET['typeId']) ? (int)$_GET['typeId'] : Integrity\DuplicateIndexType::UNDEFINED;
$skippedTypeIDs = array();

$defaultScope = Integrity\DuplicateIndexType::DEFAULT_SCOPE;
$arResult['DEFAULT_SCOPE'] = $defaultScope;
$preferresScope = Integrity\DuplicateIndexType::getPreferredScope();
$scopeListItems = array($defaultScope => GetMessage('CRM_DEDUPE_LIST_DEFAUL_SCOPE_TITLE'));
$allScopes = Integrity\DuplicateIndexType::getAllScopeTitles();

$lastSelectedScope = null;
$lastSelectedScopeOption = CUserOptions::GetOption('crm', 'dedupe_list_last_selected_scope', null);
if (is_array($lastSelectedScopeOption) && isset($lastSelectedScopeOption[$entityTypeName])
	&& Integrity\DuplicateIndexType::checkScopeValue($lastSelectedScopeOption[$entityTypeName]))
{
	$lastSelectedScope = $lastSelectedScopeOption[$entityTypeName];
}

$filterScope = null;
if (isset($_GET['scope']))
{
	$scope = Integrity\DuplicateIndexType::DEFAULT_SCOPE;
	if ($_GET['scope'] !== Integrity\DuplicateIndexType::DEFAULT_SCOPE)
		$scope = substr($_GET['scope'], 0, 6);
	if (Integrity\DuplicateIndexType::checkScopeValue($scope))
	{
		$filterScope = $scope;
		if (!is_array($lastSelectedScopeOption))
			$lastSelectedScopeOption = array();
		$lastSelectedScopeOption[$entityTypeName] = $filterScope;
		CUserOptions::SetOption('crm', 'dedupe_list_last_selected_scope', $lastSelectedScopeOption);
	}
}

foreach ($allScopes as $scope => $title)
{
	if (!empty($scope) && isset($registeredScopes[$scope]))
		$scopeListItems[$scope] = $title;
}
$arResult['SCOPE_LIST_ITEMS'] = $scopeListItems;

$currentScope = $defaultScope;
if ($filterScope !== null && isset($registeredScopes[$filterScope]))
	$currentScope = $filterScope;
else if ($lastSelectedScope !== null && isset($registeredScopes[$lastSelectedScope]))
	$currentScope = $lastSelectedScope;
else if ($preferresScope !== $defaultScope && isset($registeredScopes[$preferresScope]))
	$currentScope = $preferresScope;
$arResult['CURRENT_SCOPE'] = $currentScope;

$typeInfos = array();
foreach ($typeScopeMap as $typeID => $scopes)
{
	foreach (array_keys($scopes) as $scope)
	{
		$typeLayoutID = CCrmOwnerType::Undefined;
		if($typeID === Integrity\DuplicateIndexType::ORGANIZATION)
		{
			$typeLayoutID = CCrmOwnerType::Company;
		}
		elseif($typeID === Integrity\DuplicateIndexType::PERSON)
		{
			$typeLayoutID = CCrmOwnerType::Contact;
		}

		$groupName = '';
		if($typeID === Integrity\DuplicateIndexType::PERSON
			|| $typeID === Integrity\DuplicateIndexType::ORGANIZATION)
		{
			$groupName = 'denomination';
		}
		elseif($typeID === Integrity\DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === Integrity\DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			$groupName = 'communication';
		}
		elseif(($typeID & Integrity\DuplicateIndexType::REQUISITE) === $typeID)
		{
			$groupName = 'requisite';
		}
		elseif(($typeID & Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
		{
			$groupName = 'bank_detail';
		}

		$postfix = !empty($scope) ? '|'.$scope : '';
		$namePostfix = !empty($scope) ? '_'.$scope : '';
		$extTypeID = $typeID.$postfix;

		$description = $extTypeID;
		if (isset($typeDescriptions[$typeID][$scope]))
			$description = $typeDescriptions[$typeID][$scope];
		else if (isset($typeDescriptions[$typeID][Integrity\DuplicateIndexType::DEFAULT_SCOPE]))
			$description = $typeDescriptions[$typeID][Integrity\DuplicateIndexType::DEFAULT_SCOPE];

		$isIndexed = isset($indexedTypeScopeMap[$typeID][$scope]);

		$typeInfos[$extTypeID] = array(
			'ID' => $extTypeID,
			'NAME' => Integrity\DuplicateIndexType::resolveName($typeID),
			'SCOPE' => $scope,
			'DESCRIPTION' => $description,
			'IS_INDEXED' => $isIndexed,
			'IS_SELECTED' => false,
			'IS_UNDERSTATED' => false,
			'LAYOUT_NAME' => CCrmOwnerType::ResolveName($typeLayoutID),
			'GROUP_NAME' => $groupName
		);

		if(($filterTypeID === Integrity\DuplicateIndexType::UNDEFINED || ($filterTypeID & $typeID) === $typeID))
		{
			if(!$isIndexed && $filterTypeID !== Integrity\DuplicateIndexType::UNDEFINED && $scope === $currentScope)
			{
				$skippedTypeIDs[] = $extTypeID;
			}
			if($isIndexed)
			{
				$selectedTypes[$extTypeID] = $extTypeID;
				$typeInfos[$extTypeID]['IS_SELECTED'] = true;
			}
		}
	}
}

if(!empty($skippedTypeIDs))
{
	$skippedTypeDescriptions = array();
	foreach($skippedTypeIDs as $extTypeID)
	{
		$parts = explode('|', $extTypeID, 2);
		$typeID = $parts[0];
		$scope = isset($parts[1]) ? $parts[1] : '';
		$description = $extTypeID;
		if (isset($typeDescriptions[$typeID][$scope]))
			$description = $typeDescriptions[$typeID][$scope];
		else if (isset($typeDescriptions[$typeID][Integrity\DuplicateIndexType::DEFAULT_SCOPE]))
			$description = $typeDescriptions[$typeID][Integrity\DuplicateIndexType::DEFAULT_SCOPE];
		$skippedTypeDescriptions[] = "'{$description}'";
	}
	if(count($skippedTypeDescriptions) > 1)
	{
		$arResult['MESSAGES'][] = GetMessage('CRM_DEDUPE_LIST_NOT_FOUND_MSG_PLURAL', array('#NAMES#' => implode(', ', $skippedTypeDescriptions)));
	}
	else
	{
		$arResult['MESSAGES'][] = GetMessage('CRM_DEDUPE_LIST_NOT_FOUND_MSG', array('#NAME#' => $skippedTypeDescriptions[0]));
	}
}
//LAYOUT_ID [CONTACT | COMPANY]
if($entityTypeID !== CCrmOwnerType::Lead)
{
	$enableLayout = false;
	$layoutID = $entityTypeID;
}
else
{
	$enableLayout = true;

	$isOrganizationSelected =  $typeInfos[Integrity\DuplicateIndexType::ORGANIZATION]['IS_SELECTED'];
	$isPersonSelected = $typeInfos[Integrity\DuplicateIndexType::PERSON]['IS_SELECTED'];
	$isPersonIndexed = $typeInfos[Integrity\DuplicateIndexType::PERSON]['IS_INDEXED'];

	$layoutID = !$isPersonSelected && ($isOrganizationSelected || !$isPersonIndexed)
		? CCrmOwnerType::Company : CCrmOwnerType::Contact;

	//REMOVING OF UNUSED INDEXED TYPES
	if($layoutID === CCrmOwnerType::Contact)
	{
		unset($selectedTypes[Integrity\DuplicateIndexType::ORGANIZATION]);
		$typeInfos[Integrity\DuplicateIndexType::ORGANIZATION]['IS_SELECTED'] = false;
		if($isPersonSelected)
		{
			$typeInfos[Integrity\DuplicateIndexType::ORGANIZATION]['IS_UNDERSTATED'] = true;
		}
	}
	elseif($layoutID === CCrmOwnerType::Company)
	{
		unset($selectedTypes[Integrity\DuplicateIndexType::PERSON]);
		$typeInfos[Integrity\DuplicateIndexType::PERSON]['IS_SELECTED'] = false;
		if($isOrganizationSelected)
		{
			$typeInfos[Integrity\DuplicateIndexType::PERSON]['IS_UNDERSTATED'] = true;
		}
	}
}

$arResult['ENABLE_LAYOUT'] = $enableLayout;
$arResult['LAYOUT_ID'] = $layoutID;
$arResult['TYPE_INFOS'] = $typeInfos;

$arResult['COLUMNS'] = array();
if($layoutID === CCrmOwnerType::Company)
{
	$arResult['COLUMNS']['ORGANIZATION'] = array(
		'NAME' => 'ORGANIZATION',
		'TITLE' => GetMessage('CRM_DEDUPE_LIST_COL_ORGANIZATION'),
		'COLSPAN' => 2,
		'SORTABLE' => true,
		'SORT_TYPE_ID' => Integrity\DuplicateIndexType::ORGANIZATION,
		'TYPE_ID' => Integrity\DuplicateIndexType::ORGANIZATION,
		'SCOPE' => $currentScope
	);
}
else
{
	$arResult['COLUMNS']['PERSON'] = array(
		'NAME' => 'PERSON',
		'TITLE' => GetMessage('CRM_DEDUPE_LIST_COL_PERSON'),
		'COLSPAN' => 2,
		'SORTABLE' => true,
		'SORT_TYPE_ID' => Integrity\DuplicateIndexType::PERSON,
		'TYPE_ID' => Integrity\DuplicateIndexType::PERSON,
		'SCOPE' => $currentScope
	);
}

$arResult['COLUMNS']['PHONE'] = array(
	'NAME' => 'PHONE',
	'TITLE' => GetMessage('CRM_DEDUPE_LIST_COL_PHONE'),
	'SORTABLE' => true,
	'SORT_TYPE_ID' => Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
	'TYPE_ID' => Integrity\DuplicateIndexType::COMMUNICATION_PHONE,
	'SCOPE' => $currentScope
);

$arResult['COLUMNS']['EMAIL'] = array(
	'NAME' => 'EMAIL',
	'TITLE' => GetMessage('CRM_DEDUPE_LIST_COL_EMAIL'),
	'SORTABLE' => true,
	'SORT_TYPE_ID' => Integrity\DuplicateIndexType::COMMUNICATION_EMAIL,
	'TYPE_ID' => Integrity\DuplicateIndexType::COMMUNICATION_EMAIL,
	'SCOPE' => $currentScope

);

foreach ($typeScopeMap as $typeID => $scopes)
{
	if(($typeID & Integrity\DuplicateIndexType::REQUISITE) === $typeID
		|| ($typeID & Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
	{
		foreach (array_keys($scopes) as $scope)
		{
			if ($scope !== $currentScope)
				continue;

			$columnName = Integrity\DuplicateIndexType::resolveName($typeID);
			$arResult['COLUMNS'][$columnName] = array(
				'NAME' => $columnName,
				'TITLE' => isset($typeDescriptions[$typeID][$scope]) ? $typeDescriptions[$typeID][$scope] : $columnName,
				'SORTABLE' => true,
				'SORT_TYPE_ID' => $typeID,
				'TYPE_ID' => $typeID,
				'SCOPE' => $scope

			);
		}
	}
}

$arResult['COLUMNS']['RESPONSIBLE'] = array(
	'NAME' => 'RESPONSIBLE',
	'TITLE' => GetMessage('CRM_DEDUPE_LIST_COL_RESPONSIBLE'),
	'SORTABLE' => false,
	'SORT_TYPE_ID' => Integrity\DuplicateIndexType::UNDEFINED,
	'TYPE_ID' => Integrity\DuplicateIndexType::UNDEFINED,
	'SCOPE' => $currentScope

);

$itemsPerPage = $arResult['ITEMS_PER_PAGE'] = isset($arParams['ITEMS_PER_PAGE']) ? intval($arParams['ITEMS_PER_PAGE']) : 0;
if($itemsPerPage <= 0)
{
	$itemsPerPage = 10;
}

$pageNum = isset($_GET['pageNum']) ? (int)$_GET['pageNum'] : 1;
if($pageNum <= 0)
{
	$pageNum = 1;
}
$arResult['PAGE_NUM'] = $pageNum;

$sortTypeID = Integrity\DuplicateIndexType::UNDEFINED;
$sortBy = isset($_GET['sortBy']) ? strtoupper($_GET['sortBy']) : '';
if($sortBy !== '' && isset($arResult['COLUMNS'][$sortBy]))
{
	$sortColumn = $arResult['COLUMNS'][$sortBy];
	if($sortColumn['SORT_TYPE_ID'] !== Integrity\DuplicateIndexType::UNDEFINED)
	{
		$sortTypeID = $sortColumn['SORT_TYPE_ID'];
	}
}

if($sortTypeID === Integrity\DuplicateIndexType::UNDEFINED)
{
	if($layoutID === CCrmOwnerType::Company)
	{
		$sortTypeID = Integrity\DuplicateIndexType::ORGANIZATION;
		$sortBy = 'ORGANIZATION';
	}
	else
	{
		$sortTypeID = Integrity\DuplicateIndexType::PERSON;
		$sortBy = 'PERSON';
	}
}
$arResult['SORT_TYPE_ID'] = $sortTypeID;
$arResult['SORT_BY'] = $sortBy;

$sortOrder = $arResult['SORT_ORDER'] = isset($_GET['sortOrder'])
	&& strtoupper($_GET['sortOrder']) === 'DESC'
	? SORT_DESC : SORT_ASC;

if(empty($selectedTypes))
{
	$arResult['ITEMS'] = array();
	$arResult['ENTITY_INFOS'] = array();
	$arResult['HAS_PREV_PAGE'] = false;
	$arResult['HAS_NEXT_PAGE'] = false;
}
else
{
	$list = new Integrity\DuplicateList(
		Integrity\DuplicateIndexType::joinType(array_keys($selectedTypes)),
		$entityTypeID,
		$userID,
		$enablePermissionCheck
	);

	$list->setScope($currentScope);
	$list->setSortTypeID($sortTypeID);
	$list->setSortOrder($sortOrder);

	$items = $list->getRootItems(($pageNum - 1) * $itemsPerPage, $itemsPerPage + 1);
	if(count($items) <= $itemsPerPage)
	{
		$arResult['HAS_NEXT_PAGE'] = false;
	}
	else
	{
		$arResult['HAS_NEXT_PAGE'] = true;
		array_pop($items);
	}
	$arResult['HAS_PREV_PAGE'] = $pageNum > 1;

	$arResult['ITEMS'] = $items;
	$entityInfos = array();
	/** @var Integrity\Duplicate $item **/
	foreach($items as $item)
	{
		$entityID = $item->getRootEntityID();
		if(!isset($entityInfos[$entityID]))
		{
			$entityInfos[$entityID] = array();
		}
	}

	$entityInfoOptions = array(
		'ENABLE_EDIT_URL' => false,
		'ENABLE_RESPONSIBLE' => true,
		'ENABLE_RESPONSIBLE_PHOTO' => false
	);
	if($entityTypeID === CCrmOwnerType::Lead)
	{
		$entityInfoOptions[$layoutID === CCrmOwnerType::Company ? 'TREAT_AS_COMPANY' : 'TREAT_AS_CONTACT'] = true;
	}

	\CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $entityInfos, $enablePermissionCheck, $entityInfoOptions);
	\CCrmFieldMulti::PrepareEntityInfoBatch('PHONE', $entityTypeName, $entityInfos, array('ENABLE_NORMALIZATION' => true));
	\CCrmFieldMulti::PrepareEntityInfoBatch('EMAIL', $entityTypeName, $entityInfos);
	foreach ($typeScopeMap as $typeID => $scopes)
	{
		foreach (array_keys($scopes) as $scope)
		{
			if ($scope !== $currentScope)
				continue;

			$typeName = Integrity\DuplicateIndexType::resolveName($typeID);
			if(($typeID & Integrity\DuplicateIndexType::REQUISITE) === $typeID)
			{
				Bitrix\Crm\EntityRequisite::prepareEntityInfoBatch($entityTypeID, $entityInfos, $scope, $typeName);
			}
			elseif(($typeID & Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
			{
				Bitrix\Crm\EntityBankDetail::prepareEntityInfoBatch($entityTypeID, $entityInfos, $scope, $typeName);
			}
		}
	}

	$arResult['ENTITY_INFOS'] = &$entityInfos;
	unset($entityInfos);

	if($arResult['HAS_PREV_PAGE'])
	{
		$arResult['PREV_PAGE_URL'] = $APPLICATION->GetCurPageParam("pageNum=".($pageNum - 1), array("pageNum"));
	}
	if($arResult['HAS_NEXT_PAGE'])
	{
		$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam("pageNum=".($pageNum + 1), array("pageNum"));
	}
}

if($isAdminUser)
{
	//~CRM_REBUILD_LEAD_DUP_INDEX, ~CRM_REBUILD_CONTACT_DUP_INDEX, ~CRM_REBUILD_COMPANY_DUP_INDEX
	if(COption::GetOptionString('crm', "~CRM_REBUILD_{$entityTypeName}_DUP_INDEX", 'N') === 'Y')
	{
		$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
	}
}

$this->IncludeComponentTemplate();