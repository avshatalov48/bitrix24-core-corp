<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;
if(!CCrmQuote::LocalComponentCausedUpdater())
	return;

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arDefaultUrlTemplates404 = array(
	'perms_list' => '',
	'role_edit' => '#role_id#/edit/'
);

$arDefaultVariableAliases404 = array();

$arDefaultVariableAliases = array();

$arComponentVariables = array(
	'role_id',
	'mode'
);

if($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if(!$componentPage)
		$componentPage = 'perms_list';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	$arResult = array(
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}
else
{
	$arVariables = array();
	if(!isset($arParams['VARIABLE_ALIASES']['ID']))
		$arParams['VARIABLE_ALIASES']['ID'] = 'ID';

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'perms_list'; //default page

	if(isset($arVariables['mode']))
	{
		switch($arVariables['mode'])
		{
			case 'edit':
				if(isset($arVariables['role_id']))
					$componentPage = 'role_edit';
			break;
			case 'list':
				$componentPage = 'perms_list';
			break;
		}
	}

	$curPage = $APPLICATION->GetCurPage();
	$arResult = array(
		'FOLDER' => '',
		'URL_TEMPLATES' => array(
			'entity_list' => $curPage,
			'role_edit' => $curPage
				.'?'.$arVariableAliases['mode'].'=edit'
				.'&'.$arVariableAliases['role_id'].'=#role_id#'
		),
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}

$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_CONTACT_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = true;
}

$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = true;
}

$this->IncludeComponentTemplate($componentPage);
?>