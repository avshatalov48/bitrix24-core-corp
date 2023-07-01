<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
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
if(!$arResult['IS_PERMITTED'])
{
	$arResult['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
}

CJSCore::Init(array('access', 'window'));

$arParams['PATH_TO_ROLE_EDIT'] = CrmCheckPath('PATH_TO_ROLE_EDIT', $arParams['PATH_TO_ROLE_EDIT'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_ENTITY_LIST'] = CrmCheckPath('PATH_TO_ENTITY_LIST', $arParams['PATH_TO_ENTITY_LIST'], $APPLICATION->GetCurPage());

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'save' && check_bitrix_sessid() && $arResult['IS_PERMITTED'])
{
	$arPerms = isset($_POST['PERMS'])? $_POST['PERMS']: array();
	$CCrmRole = new CcrmRole();
	$CCrmRole->SetRelation($arPerms);

	CCrmSaleHelper::updateShopAccess();

	$cache = new \CPHPCache;
	$cache->CleanDir("/crm/list_crm_roles/");

	LocalRedirect($APPLICATION->GetCurPage());
}

// get role list
$arResult['PATH_TO_ROLE_ADD'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => 0
	)
);
$arResult['ROLE'] = array();
$obRes = CCrmRole::GetList(['ID' => 'DESC',], ['=IS_SYSTEM' => 'N']);
while ($arRole = $obRes->Fetch())
{
	$arRole['PATH_TO_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
		array(
			'role_id' => $arRole['ID']
		)
	);
	$arRole['PATH_TO_DELETE'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
		array(
			'role_id' => $arRole['ID']
		)),
		array('delete' => '1', 'sessid' => bitrix_sessid())
	);
	$arRole['NAME'] = htmlspecialcharsbx($arRole['NAME']);
	$arResult['ROLE'][$arRole['ID']] = $arRole;
}

// get role relation
$arResult['RELATION'] = array();
$arResult['RELATION_ENTITY'] = array();
$obRes = CCrmRole::GetRelation();
while ($arRelation = $obRes->Fetch())
{
	if (isset($arResult['ROLE'][$arRelation['ROLE_ID']]))
	{
		$arResult['RELATION'][$arRelation['RELATION']] = $arRelation;
		$arResult['RELATION_ENTITY'][$arRelation['RELATION']] = true;
	}
}

$CAccess = new CAccess();
$arNames = $CAccess->GetNames(array_keys($arResult['RELATION_ENTITY']));
foreach ($arResult['RELATION'] as &$arRelation)
{
	//Issue #43598
	$arRelation['NAME'] = htmlspecialcharsbx($arNames[$arRelation['RELATION']]['name']);
	$providerName = $arNames[$arRelation['RELATION']]['provider'];
	if(!empty($providerName))
	{
		$arRelation['NAME'] = '<b>'.htmlspecialcharsbx($providerName).':</b> '.$arRelation['NAME'];
	}
}
unset($arRelation);

//Issue #38744
/*if(IsModuleInstalled('bitrix24'))
{
	$arResult['DISABLED_PROVIDERS'] = array('group');
}*/

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage('CRM_PERMS_ENTITY_LIST'));
$APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);

?>
