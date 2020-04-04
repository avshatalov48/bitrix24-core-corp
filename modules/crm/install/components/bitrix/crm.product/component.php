<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
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

global $APPLICATION;

$componentPage = '';
$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'product_list' => 'list/#section_id#/',
	'product_edit' => 'edit/#product_id#/',
	'product_show' => 'show/#product_id#/',
	'section_list' => 'section_list/#section_id#/',
	'product_file' => 'file/#product_id#/#field_id#/#file_id#/',
	'product_import' => 'import/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('product_id', 'section_id', 'field_id', 'file_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_'.strtoupper($url);
		$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$arComponentVariables = array(
		isset($arParams['VARIABLE_ALIASES']['product_id']) ? $arParams['VARIABLE_ALIASES']['product_id'] : 'product_id',
		isset($arParams['VARIABLE_ALIASES']['section_id']) ? $arParams['VARIABLE_ALIASES']['section_id'] : 'section_id'
	);

	$arDefaultVariableAliases = array(
		'product_id' => 'product_id',
		'section_id' => 'section_id',
		'field_id' => 'field_id',
		'file_id' => 'file_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
		$componentPage = 'product_edit';
	elseif (isset($_REQUEST['show']))
		$componentPage = 'product_show';
	elseif (isset($_REQUEST['sections']))
		$componentPage = 'section_list';
	elseif (isset($_REQUEST['file']))
		$componentPage = 'product_file';
	elseif (isset($_REQUEST['import']))
		$componentPage = 'product_import';

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_INDEX'] = $curPage;
	$arResult['PATH_TO_PRODUCT_LIST'] = $curPage.'?'.$arVariableAliases['section_id'].'=#section_id#';
	$arResult['PATH_TO_PRODUCT_EDIT'] = $curPage.'?'.$arVariableAliases['product_id'].'=#product_id#&edit';
	$arResult['PATH_TO_PRODUCT_SHOW'] = $curPage.'?'.$arVariableAliases['product_id'].'=#product_id#&show';
	$arResult['PATH_TO_SECTION_LIST'] = $curPage.'?'.$arVariableAliases['section_id'].'=#section_id#&sections';
	$arResult['PATH_TO_PRODUCT_FILE'] = $curPage.'?'.$arVariableAliases['product_id'].'=#product_id#&'.
		$arVariableAliases['field_id'].'=#field_id#&'.$arVariableAliases['file_id'].'=#file_id#&'.'file';
	$arResult['PATH_TO_PRODUCT_IMPORT'] = $APPLICATION->GetCurPage()."?import";
}

$catalogID = CCrmCatalog::EnsureDefaultExists();

// sync iblock url fields with params
if (IsModuleInstalled('iblock') && CModule::IncludeModule('iblock'))
{
	$iblock = new CIBlock();

	if (isset($arResult['PATH_TO_PRODUCT_LIST']))
	{
		// list page
		$listPageUrl = CComponentEngine::makePathFromTemplate($arResult['PATH_TO_PRODUCT_LIST'], array('section_id' => ''));
		if ($arParams['SEF_MODE'] === 'Y')
			$listPageUrl = str_replace('//', '/', $listPageUrl);
		else
			$listPageUrl = str_replace('?'.$arVariableAliases['section_id'].'=', '/', $listPageUrl);
		//$listPageUrl = '#SITE_ID#'.$listPageUrl;
		$curListPageUrl = COption::GetOptionString('crm', 'product_list_page_url', '');
		if ($listPageUrl !== $curListPageUrl)
		{
			$iblock->Update($catalogID, array('LIST_PAGE_URL' => $listPageUrl));
			COption::SetOptionString('crm', 'product_list_page_url', $listPageUrl);
		}

		// section page
		$sectionPageUrl = CComponentEngine::makePathFromTemplate($arResult['PATH_TO_PRODUCT_LIST'], array('section_id' => '#SECTION_ID#'));
		//$sectionPageUrl = '#SITE_ID#'.$sectionPageUrl;
		$curSectionPageUrl = COption::GetOptionString('crm', 'product_section_page_url', '');
		if ($sectionPageUrl !== $curSectionPageUrl)
		{
			$iblock->Update($catalogID, array('SECTION_PAGE_URL' => $sectionPageUrl));
			COption::SetOptionString('crm', 'product_section_page_url', $sectionPageUrl);
		}
	}
	if (isset($arResult['PATH_TO_PRODUCT_SHOW']))
	{
		// detail page
		$detailPageUrl = CComponentEngine::makePathFromTemplate($arResult['PATH_TO_PRODUCT_SHOW'], array('product_id' => '#ID#'));
		//$detailPageUrl = '#SITE_ID#'.$detailPageUrl;
		$curDetailPageUrl = COption::GetOptionString('crm', 'product_detail_page_url', '');
		if ($detailPageUrl !== $curDetailPageUrl)
		{
			$iblock->Update($catalogID, array('DETAIL_PAGE_URL' => $detailPageUrl));
			COption::SetOptionString('crm', 'product_detail_page_url', $detailPageUrl);
		}
	}

	unset($iblock);
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'CATALOG_ID' => $catalogID,
			'PRODUCT_ID' => isset($arVariables['product_id']) ? intval($arVariables['product_id']) : 0,
			'SECTION_ID' => isset($arVariables['section_id']) ? intval($arVariables['section_id']) : 0,
			'FIELD_ID' => isset($arVariables['field_id']) ? trim($arVariables['field_id']) : '',
			'FILE_ID' => isset($arVariables['file_id']) ? intval($arVariables['file_id']) : 0
		),
		$arResult
	);

$this->IncludeComponentTemplate($componentPage);
?>