<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$taxID = isset($arParams['TAX_ID']) ? strval($arParams['TAX_ID']) : '';
if($taxID[0] <=0 )
{
	$taxIDParName = isset($arParams['TAX_ID_PAR_NAME']) ? strval($arParams['TAX_ID_PAR_NAME']) : '';
	if(strlen($taxIDParName) == 0)
	{
		$taxIDParName = 'tax_id';
	}

	$taxID = isset($_REQUEST[$taxIDParName]) ? intval($_REQUEST[$taxIDParName]) : 0;
}

$arResult['SHOW_RATES'] = isset($_REQUEST['show_rates']) && $_REQUEST['show_rates'] == 'Y' ? true : false;

$tax = array();
if(($taxID <= 0) || !($tax = CCrmTax::GetByID($taxID)))
{
	ShowError(GetMessage('CRM_TAX_NOT_FOUND'));
	@define('ERROR_404', 'Y');
	if($arParams['SET_STATUS_404'] === 'Y')
	{
		CHTTP::SetStatus("404 Not Found");
	}
	return;
}

$arResult['TAX_ID'] = $taxID;
$arResult['TAX'] = $tax;
$isEditMode = $taxID > 0;

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) && strlen($arParams['FORM_ID']) > 0 ? $arParams['FORM_ID'] : 'CRM_TAX_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_TAX_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_TAX_LIST'],
	array()
);

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'tax_info',
	'name' => GetMessage('CRM_TAX_SECTION_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'ID',
	'name' => GetMessage('CRM_TAX_FIELD_ID'),
	'value' => $taxID,
	'type' =>  'label'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'TIMESTAMP_X',
	'name' =>  GetMessage('CRM_TAX_FIELD_TIMESTAMP_X'),
	'value' => $tax['TIMESTAMP_X'],
	'type' =>  'label'
);

$sitesList = CCrmTax::getSitesList();

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'LID',
	'name' =>  GetMessage('CRM_TAX_FIELD_LID'),
	'value' => $sitesList[$tax['LID']],
	'type' =>  'label'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'NAME',
	'name' =>  GetMessage('CRM_TAX_FIELD_NAME'),
	'value' => $tax['NAME'],
	'type' =>  'label'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'CODE',
	'name' =>  GetMessage('CRM_TAX_FIELD_CODE'),
	'value' => $tax['CODE'],
	'type' =>  'label'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'DESCRIPTION',
	'name' =>  GetMessage('CRM_TAX_FIELD_DESCRIPTION'),
	'value' => $tax['DESCRIPTION'],
	'type' =>  'label'
);

$toolbarID = "CRM_TAX_RATE_TB";

ob_start();

$APPLICATION->IncludeComponent(
	'bitrix:crm.config.tax.rate.list',
	'',
	array(
		'PATH_TO_TAXRATE_LIST' => $arResult['PATH_TO_TAXRATE_LIST'],
		'PATH_TO_TAXRATE_SHOW' => $arResult['PATH_TO_TAXRATE_SHOW'],
		'PATH_TO_TAXRATE_ADD' => $arResult['PATH_TO_TAXRATE_ADD'],
		'PATH_TO_TAXRATE_EDIT' => $arResult['PATH_TO_TAXRATE_EDIT'],
		'TAX_FORM_ID' => $arResult['FORM_ID'],
		'TAX_ID' => $arResult['TAX_ID'],
		'EDIT_MODE' => 'N'
	),
	''
);

$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_rateslist'][] = array(
	'id' => 'section_rates_grid',
	'name' => GetMessage('CRM_TAX_RATE_LIST').' "'.$tax['NAME'].'"',
	'type' => 'section'
);

$arResult['FIELDS']['tab_rateslist'][] = array(
	'id' => 'TAX_RATES',
	'value' => $sVal,
	'type' =>  'custom',
	'colspan' => true
);

$this->IncludeComponentTemplate();
?>