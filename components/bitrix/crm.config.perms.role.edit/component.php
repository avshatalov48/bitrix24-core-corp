<?php

/**
 * @deprecated
 * This component will be deleted soon.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));

	return;
}

$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getPermissionControlRestriction();
$arResult['IS_PERMITTED'] = $restriction->hasPermission();
if (!$arResult['IS_PERMITTED'])
{
	$arResult['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
}

$arParams['PATH_TO_ROLE_EDIT'] = CrmCheckPath(
	'PATH_TO_ROLE_EDIT',
	$arParams['PATH_TO_ROLE_EDIT'] ?? '',
	$APPLICATION->GetCurPage()
);
$arParams['PATH_TO_ENTITY_LIST'] = CrmCheckPath(
	'PATH_TO_ENTITY_LIST',
	$arParams['PATH_TO_ENTITY_LIST'] ?? '',
	$APPLICATION->GetCurPage()
);

$arParams['ROLE_ID'] = (int)($arParams['ROLE_ID'] ?? 0);
$bVarsFromForm = false;

$arResult['PATH_TO_ROLE_EDIT'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_ROLE_EDIT'] ?? '',
	['role_id' => $arParams['ROLE_ID']]
);

if ($arResult['IS_PERMITTED'])
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])) && check_bitrix_sessid())
	{
		$permissions = (isset($_POST['ROLE_PERMS']) && is_array($_POST['ROLE_PERMS'])) ? $_POST['ROLE_PERMS'] : [];
		$bVarsFromForm = true;
		$arFields = array(
			'NAME' => $_POST['NAME'],
			'RELATION' => $permissions
		);

		$CCrmRole = new CcrmRole();
		if ($arParams['ROLE_ID'] > 0)
		{
			if (!$CCrmRole->Update($arParams['ROLE_ID'], $arFields))
				$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
		}
		else
		{
			$arParams['ROLE_ID'] = $CCrmRole->Add($arFields);
			if ($arParams['ROLE_ID'] === false)
				$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
		}

		CCrmSaleHelper::updateShopAccess();

		$cache = new \CPHPCache;
		$cache->CleanDir("/crm/list_crm_roles/");

		if (empty($arResult['ERROR_MESSAGE']))
		{
			if (isset($_POST['apply']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
					array(
						'role_id' => $arParams['ROLE_ID']
					)
				));
			else
				LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
		}
		else
			ShowError($arResult['ERROR_MESSAGE']);

		$arResult['ROLE'] = array(
			'ID' => $arParams['ROLE_ID'],
			'NAME' => $arFields['NAME']
		);
		$arResult['ROLE_PERMS'] = $arFields['RELATION'];
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete']) && check_bitrix_sessid() && $arParams['ROLE_ID'] > 0)
	{
		$CCrmRole = new CCrmRole();
		$CCrmRole->Delete($arParams['ROLE_ID']);
		LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
	}
}

if (!$bVarsFromForm)
{
	if ($arParams['ROLE_ID'] > 0)
	{
		$obRes = CCrmRole::GetList(array(), array('ID' => $arParams['ROLE_ID']));
		$arResult['ROLE'] = $obRes->Fetch();

		if ($arResult['ROLE']['IS_SYSTEM'] === 'Y')
		{
			$url = CComponentEngine::makePathFromTemplate('/crm/configs/perms/');
			LocalRedirect($url);
		}
		if ($arResult['ROLE'] == false)
			$arParams['ROLE_ID'] = 0;
	}

	if ($arParams['ROLE_ID'] <= 0)
	{
		$arResult['ROLE']['ID'] = 0;
		$arResult['ROLE']['NAME'] = '';
	}

	$arResult['ROLE_PERMS'] = array();

}
if ($arParams['ROLE_ID'] > 0 && !$bVarsFromForm)
	$arResult['~ROLE_PERMS'] = CCrmRole::GetRolePerms($arParams['ROLE_ID']);
if (!$bVarsFromForm)
	$arResult['ROLE_PERMS'] = $arResult['~ROLE_PERMS'];

$permissionSet = [
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_NONE),
	BX_CRM_PERM_SELF => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SELF),
	BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_DEPARTMENT),
	BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SUBDEPARTMENT),
	BX_CRM_PERM_OPEN => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_OPEN),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_ALL)
];
$operations = ['READ', 'ADD', 'WRITE', 'DELETE'];
$operationsWithImport = $operations;
$operationsWithImport[] = 'EXPORT';
$operationsWithImport[] = 'IMPORT';
$operationsWithAutomation = $operationsWithImport;
$operationsWithAutomation[] = 'AUTOMATION';

$entityOperationsMap = [
	'LEAD' => $operationsWithAutomation,
	'QUOTE' => $operationsWithImport,
	'INVOICE' => $operationsWithImport,
	'CONTACT' => $operationsWithImport,
	'COMPANY' => $operationsWithImport,
	'ORDER' => $operationsWithAutomation,
	'WEBFORM' => ['READ', 'WRITE'],
	'BUTTON' => ['READ', 'WRITE'],
	'SALETARGET' => ['READ', 'WRITE'],
	'EXCLUSION' => ['READ', 'WRITE'],
];

$arResult['ENTITY'] = [];

$arResult['ENTITY']['CONTACT'] = GetMessage('CRM_ENTITY_TYPE_CONTACT');
$factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Contact);

$hiddenContactCategories = [
	Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE,
];

foreach ($factory->getCategories() as $category)
{
	if ($category->getIsDefault())
	{
		continue;
	}
	if (in_array($category->getCode(), $hiddenContactCategories))
	{
		continue;
	}

	$entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId()));
	$entityTitle = $category->getSingleNameIfPossible();
	$arResult['ENTITY'][$entityName] =  htmlspecialcharsbx($entityTitle);
	$arResult['ROLE_PERM'][$entityName] = $permissionSet;
	$entityOperationsMap[$entityName] = $operationsWithImport;
}

$arResult['ENTITY']['COMPANY'] = GetMessage('CRM_ENTITY_TYPE_COMPANY');
$factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Company);
foreach ($factory->getCategories() as $category)
{
	if ($category->getIsDefault())
	{
		continue;
	}
	$entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId()));
	$entityTitle = $category->getSingleNameIfPossible();
	$arResult['ENTITY'][$entityName] = htmlspecialcharsbx($entityTitle);
	$arResult['ROLE_PERM'][$entityName] = $permissionSet;
	$entityOperationsMap[$entityName] = $operationsWithImport;
}

$dealCategoryConfigs = Bitrix\Crm\Category\DealCategory::getPermissionRoleConfigurationsWithDefault();
foreach($dealCategoryConfigs as $typeName => $config)
{
	$arResult['ENTITY'][$typeName] = isset($config['NAME']) ? htmlspecialcharsbx($config['NAME']) : $typeName;
}

$arResult['ENTITY'] = array_merge(
	$arResult['ENTITY'],
	[
		'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
		'QUOTE' => GetMessage('CRM_ENTITY_TYPE_QUOTE_MSGVER_1'),
	]
);

$typesMap = Service\Container::getInstance()->getDynamicTypesMap()->load();

if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
{
	$arResult['ENTITY'][\CCrmOwnerType::InvoiceName] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Invoice);
}

$arResult['ENTITY_FIELDS'] = array(
	'DEAL' => array('STAGE_ID' => CCrmStatus::GetStatusListEx('DEAL_STAGE')),
	'LEAD' => array('STATUS_ID' => CCrmStatus::GetStatusListEx('STATUS'))
);

if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
{
	$smartInvoiceFactory = Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
	if ($smartInvoiceFactory)
	{
		$isAutomationEnabled = $smartInvoiceFactory->isAutomationEnabled();
		foreach ($smartInvoiceFactory->getCategories() as $category)
		{
			$entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType(\CCrmOwnerType::SmartInvoice, $category->getId()));
			$entityTitle = \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice);
			if ($smartInvoiceFactory->isCategoriesEnabled())
			{
				$entityTitle .= ' ' . $category->getSingleNameIfPossible();
			}
			$arResult['ENTITY'][$entityName] = $entityTitle;
			foreach ($smartInvoiceFactory->getStages($category->getId()) as $stage)
			{
				$arResult['ENTITY_FIELDS'][$entityName][\Bitrix\Crm\Item::FIELD_NAME_STAGE_ID][htmlspecialcharsbx($stage->getStatusId())] = htmlspecialcharsbx($stage->getName());
			}
			$arResult['ROLE_PERM'][$entityName] = $permissionSet;
			$entityOperationsMap[$entityName] = $isAutomationEnabled ? $operationsWithAutomation : $operationsWithImport;
		}
	}
}

$arResult['ENTITY'] = array_merge(
	$arResult['ENTITY'],
	[
		'ORDER' => GetMessage('CRM_ENTITY_TYPE_ORDER'),
		'WEBFORM' => GetMessage('CRM_ENTITY_TYPE_WEBFORM'),
		'BUTTON' => GetMessage('CRM_ENTITY_TYPE_BUTTON'),
		'SALETARGET' => GetMessage('CRM_ENTITY_TYPE_SALETARGET'),
		'EXCLUSION' => GetMessage('CRM_ENTITY_TYPE_EXCLUSION'),
	]
);

$arResult['ROLE_PERM']['LEAD'] = $arResult['ROLE_PERM']['DEAL'] =
$arResult['ROLE_PERM']['QUOTE'] = $arResult['ROLE_PERM']['INVOICE'] =
$arResult['ROLE_PERM']['COMPANY'] = $arResult['ROLE_PERM']['CONTACT'] =
$arResult['ROLE_PERM']['ORDER'] = $permissionSet;
$arResult['ROLE_PERM']['WEBFORM'] = $arResult['ROLE_PERM']['BUTTON'] =
$arResult['ROLE_PERM']['EXCLUSION'] = array(
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_NONE),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_ALL)
);
$arResult['ROLE_PERM']['SALETARGET'] = array(
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_NONE),
	BX_CRM_PERM_SELF => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SELF),
	BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_DEPARTMENT),
	BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SUBDEPARTMENT),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_ALL)
);
$arResult['ROLE_PERM']['AUTOMATION'] = array(
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_AUTOMATION_NONE'),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_AUTOMATION_ALL')
);

foreach($dealCategoryConfigs as $typeName => $config)
{
	if(isset($config['FIELDS']) && is_array($config['FIELDS']))
	{
		$fields = $config['FIELDS'];
		foreach ($fields as $fieldType => $stages)
		{
			foreach ($stages as $stageId => $stageName)
			{
				unset($fields[$fieldType][$stageId]);
				$fields[$fieldType][htmlspecialcharsbx($stageId)]= htmlspecialcharsbx($stageName);
			}
		}
		$arResult['ENTITY_FIELDS'][$typeName] = $fields;
	}

	$arResult['ROLE_PERM'][$typeName] = $permissionSet;
}

foreach ($typesMap->getTypes() as $type)
{
	$isAutomationEnabled = $typesMap->isAutomationEnabled($type->getEntityTypeId());
	$stagesFieldName = htmlspecialcharsbx($typesMap->getStagesFieldName($type->getEntityTypeId()));
	foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category)
	{
		$entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($type->getEntityTypeId(), $category->getId()));
		$entityTitle = $type->getTitle();
		if ($type->getIsCategoriesEnabled())
		{
			$entityTitle .= ' ' . $category->getName();
		}
		$arResult['ENTITY'][$entityName] = htmlspecialcharsbx($entityTitle);
		if ($type->getIsStagesEnabled())
		{
			foreach ($typesMap->getStages($type->getEntityTypeId(), $category->getId()) as $stage)
			{
				$arResult['ENTITY_FIELDS'][$entityName][$stagesFieldName][htmlspecialcharsbx($stage->getStatusId())] = htmlspecialcharsbx($stage->getName());
			}
		}
		$arResult['ROLE_PERM'][$entityName] = $permissionSet;
		$entityOperationsMap[$entityName] = $isAutomationEnabled ? $operationsWithAutomation : $operationsWithImport;
	}
}

unset($arResult['ROLE_PERM']['INVOICE'][BX_CRM_PERM_OPEN]);
unset($arResult['ROLE_PERM']['ORDER'][BX_CRM_PERM_OPEN]);

$arResult['PATH_TO_ROLE_DELETE'] = CHTTP::urlAddParams(
	CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_ROLE_EDIT'] ?? '',
		[
			'role_id' => $arResult['ROLE']['ID']
		]
	),
	['delete' => '1', 'sessid' => bitrix_sessid()]
);

foreach ($operationsWithAutomation as $operation)
{
	foreach ($arResult['ENTITY'] as $entityType => $entityName)
	{
		if (
			!isset($entityOperationsMap[$entityType])
			|| in_array($operation, $entityOperationsMap[$entityType])
		)
		{
			$arResult['ENTITY_PERMS'][$entityType][] = $operation;
		}

		if (isset($arResult['ENTITY_FIELDS'][$entityType]))
		{
			foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue)
			{
				foreach ($arFieldValue as $fieldValueID => $fieldValue)
				{
					if (
						!isset($arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID])
						|| $arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID] === '-'
					)
					{
						$arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID] = $arResult['ROLE_PERMS'][$entityType][$operation]['-'];
					}
				}
			}
		}
	}
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage('CRM_PERMS_ROLE_EDIT'));
$APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);
$APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ROLE_EDIT'), $arResult['PATH_TO_ROLE_EDIT']);
