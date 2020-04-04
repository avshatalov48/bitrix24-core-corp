<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

use Bitrix\Crm;

$showRecurring = \Bitrix\Crm\Recurring\Manager::isAllowedExpose(\Bitrix\Crm\Recurring\Manager::DEAL);

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'recur' => 'recur/',
	'category' => 'category/#category_id#/',
	'funnel' => 'funnel/',
	'import' => 'import/',
	'widget' => 'widget/',
	'analytics/list' => 'analytics/list/',
	'recur_edit' => 'recur/edit/#deal_id#/',
	'recur_show' => 'recur/show/#deal_id#/',
	'recur_expose' => 'recur/edit/#deal_id#/?expose=Y',
	'recur_category' => 'recur/category/#category_id#/',
	'widgetcategory' => 'widget/category/#category_id#/',
	'kanban' => 'kanban/',
	'kanbancategory' => 'kanban/category/#category_id#/',
	'calendarcategory' => 'calendar/category/#category_id#/',
	'edit' => 'edit/#deal_id#/',
	'show' => 'show/#deal_id#/',
	'merge' => 'merge/',
	'details' => 'details/#deal_id#/',
	'calendar' => 'calendar/',
	'automation' => 'automation/#category_id#/',
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('deal_id', 'category_id');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if ($showRecurring && in_array($componentPage, array("recur", "recur_edit", "recur_expose", "recur_show")))
		$arResult['IS_RECURRING'] = 'Y';

	if ($componentPage == "recur")
	{
		$componentPage = 'list';
	}
	elseif (($componentPage == "recur_edit" || $componentPage == "recur_expose"))
	{
		$componentPage = 'edit';
	}
	elseif ($componentPage == "recur_show")
	{
		$componentPage = 'show';
	}

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = 'index';
	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		if(strlen($arParams['PATH_TO_DEAL_'.strToUpper($url)]) <= 0)
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['SEF_FOLDER'].$value;
		else
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['PATH_TO_'.strToUpper($url)];
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['deal_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['recur']) && isset($_REQUEST['edit']) && $showRecurring)
	{
		$componentPage = 'edit';
		$arResult['IS_RECURRING'] = 'Y';
	}
	elseif (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['recur']) && isset($_REQUEST['show']) && $showRecurring)
	{
		$componentPage = 'show';
		$arResult['IS_RECURRING'] = 'Y';
	}
	else if (isset($_REQUEST['card']))
		$componentPage = 'card';
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['merge']))
		$componentPage = 'merge';
	else if (isset($_REQUEST['details']))
		$componentPage = 'details';
	else if (isset($_REQUEST['import']))
		$componentPage = 'import';
	else if (isset($_REQUEST['widget']))
		$componentPage = 'widget';
	else if (isset($_REQUEST['category']))
		$componentPage = 'category';
	else if (isset($_REQUEST['widgetcategory']))
		$componentPage = 'widgetcategory';
	else if (isset($_REQUEST['kanban']))
		$componentPage = 'kanban';
	else if (isset($_REQUEST['kanbancategory']))
		$componentPage = 'kanbancategory';
	else if (isset($_REQUEST['recur_category']))
		$componentPage = 'recur_category';
	else if (isset($_REQUEST['calendar']))
		$componentPage = 'calendar';
	else if (isset($_REQUEST['calendarcategory']))
		$componentPage = 'calendarcategory';
	else if (isset($_REQUEST['automation']))
		$componentPage = 'automation';
	else if (isset($_REQUEST['analytics/list']))
		$componentPage = 'analytics/list';
	else if (isset($_REQUEST['recur']))
	{
		$componentPage = 'list';
		if ($showRecurring)
			$arResult['IS_RECURRING'] = 'Y';
	}

	$arResult['PATH_TO_DEAL_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_DEAL_FUNNEL'] = $APPLICATION->GetCurPage().'&funnel';
	$arResult['PATH_TO_DEAL_DETAILS'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&details";
	$arResult['PATH_TO_DEAL_SHOW'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&show";
	$arResult['PATH_TO_DEAL_EDIT'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&edit";
	$arResult['PATH_TO_DEAL_RECUR_EDIT'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&edit&recur";
	$arResult['PATH_TO_DEAL_RECUR_SHOW'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&show&recur";
	$arResult['PATH_TO_DEAL_RECUR_EXPOSE'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&edit&recur&expose=Y";
	$arResult['PATH_TO_DEAL_IMPORT'] = $APPLICATION->GetCurPage()."?import";
	$arResult['PATH_TO_DEAL_WIDGET'] = $APPLICATION->GetCurPage()."?widget";
	$arResult['PATH_TO_DEAL_RECUR'] = $APPLICATION->GetCurPage()."?recur";
	$arResult['PATH_TO_DEAL_KANBAN'] = $APPLICATION->GetCurPage()."?kanban";
	$arResult['PATH_TO_DEAL_CALENDAR'] = $APPLICATION->GetCurPage()."?calendar";
	$arResult['PATH_TO_DEAL_CATEGORY'] = $APPLICATION->GetCurPage()."?category=#category_id#";
	$arResult['PATH_TO_DEAL_AUTOMATION'] = $APPLICATION->GetCurPage()."?category=#category_id#&automation";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_DEAL_CATEGORY_LIST' => CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], COption::GetOptionString('crm', 'path_to_deal_category_list')),
		'PATH_TO_DEAL_CATEGORY_EDIT' => CrmCheckPath('PATH_TO_DEAL_CATEGORY_EDIT', $arParams['PATH_TO_DEAL_CATEGORY_EDIT'], COption::GetOptionString('crm', 'path_to_deal_category_edit'))
	),
	$arResult
);

if(isset($_GET['redirect_to']))
{
	$viewName = strtoupper(trim($_GET['redirect_to']));
	if($viewName === '')
	{
		$viewName = Crm\Settings\EntityViewSettings::resolveName(
			Crm\Settings\DealSettings::getCurrent()->getCurrentListViewID()
		);
	}

	$currentCategoryID = (int)CUserOptions::GetOption('crm', 'current_deal_category', -1);
	if($currentCategoryID >= 0)
	{
		if($viewName === Crm\Settings\EntityViewSettings::LIST_VIEW_NAME)
		{
			$viewName = 'CATEGORY';
		}
		else
		{
			$viewName .= 'CATEGORY';
		}
	}

	$pathKey = "PATH_TO_DEAL_{$viewName}";
	if(isset($arResult[$pathKey]))
	{
		$redirectUrl = CHTTP::urlAddParams(
			CComponentEngine::makePathFromTemplate($arResult[$pathKey], array('category_id' => $currentCategoryID)),
			array_diff_key($_GET, array_flip(array('redirect_to'))),
			array('encode' => true)
		);
		LocalRedirect($redirectUrl);
	}
}

if($componentPage === 'list' || $componentPage === 'recur_category' || $componentPage === 'category' || $componentPage === 'kanbancategory' || $componentPage === 'calendarcategory' || $componentPage === 'calendar')
{
	$categoryID = isset($arResult['VARIABLES']['category_id']) ? (int)$arResult['VARIABLES']['category_id'] : -1;
	$currentCategoryID = (int)CUserOptions::GetOption('crm', 'current_deal_category', -1);
	if(($componentPage === 'list' || $componentPage === 'recur_category') && $currentCategoryID >= 0)
	{
		CUserOptions::DeleteOption('crm', 'current_deal_category');
	}
	elseif(($componentPage === 'category' || $componentPage === 'kanbancategory' || $componentPage === 'calendarcategory') && $categoryID >= 0 && $categoryID !== $currentCategoryID)
	{
		CUserOptions::SetOption('crm', 'current_deal_category', $categoryID);
	}
}

$arResult['NAVIGATION_CONTEXT_ID'] = 'DEAL';
if($componentPage === 'index' || $componentPage === 'category')
{
	$componentPage = 'list';
}
elseif($componentPage === 'widgetcategory')
{
	$componentPage = 'widget';
}
elseif($componentPage === 'recur_category')
{
	$componentPage = 'list';
	if ($showRecurring)
		$arResult['IS_RECURRING'] = 'Y';
}
elseif($componentPage === 'kanbancategory')
{
	$componentPage = 'kanban';
}
elseif($componentPage === 'calendarcategory')
{
	$componentPage = 'calendar';
}

if(isset($_GET['id']))
{
	$entityIDs = null;
	if(is_array($_GET['id']))
	{
		$entityIDs = $_GET['id'];
	}
	else
	{
		$entityIDs = explode(',', $_GET['id']);
	}
	$arResult['VARIABLES']['deal_ids'] = array_map('intval', $entityIDs);
}

if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled()
	&& ($componentPage === 'edit' || $componentPage === 'show')
)
{
	$redirectUrl = CComponentEngine::MakePathFromTemplate(
		$arResult['PATH_TO_DEAL_DETAILS'],
		array('deal_id' => $arResult['VARIABLES']['deal_id'])
	);

	if(isset($_SERVER['QUERY_STRING']))
	{
		parse_str($_SERVER['QUERY_STRING'], $queryParams);
		if(!empty($queryParams))
		{
			$redirectUrl = CHTTP::urlAddParams($redirectUrl, $queryParams, array('encode' => true));
		}
	}

	LocalRedirect($redirectUrl, '301 Moved Permanently');
}
else
{
	$this->IncludeComponentTemplate($componentPage);
}
?>