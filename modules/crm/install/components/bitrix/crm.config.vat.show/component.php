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

$arVatID = isset($arParams['VAT_ID']) ? strval($arParams['VAT_ID']) : '';
if($arVatID <= 0)
{
	$arVatIDParName = isset($arParams['VAT_ID_PAR_NAME']) ? intval($arParams['VAT_ID_PAR_NAME']) : 0;
	if($arVatIDParName <= 0)
		$arVatIDParName = 'vat_id';

	$arVatID = isset($_REQUEST[$arVatIDParName]) ? intval($_REQUEST[$arVatIDParName]) : 0;
}

$arVat = array();
if($arVatID <= 0 || !($arVat = CCrmVat::GetByID($arVatID)))
{
	ShowError(GetMessage('CRM_VAT_NOT_FOUND'));
	@define('ERROR_404', 'Y');
	if($arParams['SET_STATUS_404'] === 'Y')
	{
		CHTTP::SetStatus("404 Not Found");
	}
	return;
}

$arResult['VAT_ID'] = $arVatID;
$arResult['VAT'] = $arVat;
$isEditMode = $arVatID >= 0 ? true : false;

$arResult['FORM_ID'] = 'CRM_VAT_EDIT';
$arResult['GRID_ID'] = 'CRM_VAT_EDIT';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_VAT_LIST'],
	array()
);

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'vat_info',
	'name' => GetMessage('CRM_VAT_SECTION_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => GetMessage('CRM_VAT_FIELD_ID'),
	'value' => $arVatID,
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' =>  GetMessage('CRM_VAT_FIELD_NAME'),
	'value' => $arVat['NAME'],
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RATE',
	'name' =>  GetMessage('CRM_VAT_FIELD_RATE'),
	'value' => $arVat['RATE'],
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' =>  GetMessage('CRM_VAT_FIELD_ACTIVE'),
	'value' => $arVat['ACTIVE'] == 'Y' ? GetMessage('CRM_VAT_YES') : GetMessage('CRM_VAT_NO'),
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'C_SORT',
	'name' =>  GetMessage('CRM_VAT_FIELD_C_SORT'),
	'value' => $arVat['C_SORT'],
	'type' =>  'label',
);

$this->IncludeComponentTemplate();
?>