<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_SALE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/*
 * PATH_TO_TAX_LIST
 * PATH_TO_TAX_SHOW
 * PATH_TO_TAX_EDIT
 * TAX_ID
 * TAX_ID_PAR_NAME
 */

$arParams['PATH_TO_TAX_LIST'] = CrmCheckPath('PATH_TO_TAX_LIST', $arParams['PATH_TO_TAX_LIST'], '');
$arParams['PATH_TO_TAX_SHOW'] = CrmCheckPath('PATH_TO_TAX_SHOW', $arParams['PATH_TO_TAX_SHOW'], '?tax_id=#tax_id#&show');
$arParams['PATH_TO_TAX_EDIT'] = CrmCheckPath('PATH_TO_TAX_EDIT', $arParams['PATH_TO_TAX_EDIT'], '?tax_id=#tax_id#&edit');

$taxID = isset($arParams['TAX_ID']) ? intval($arParams['TAX_ID']) : 0;
if($taxID <= 0)
{
	$taxIDParName = isset($arParams['TAX_ID_PAR_NAME']) ? strval($arParams['TAX_ID_PAR_NAME']) : '';

	if(strlen($taxIDParName) == 0)
		$taxIDParName = 'tax_id';

	$taxID = isset($_REQUEST[$taxIDParName]) ? intval($_REQUEST[$taxIDParName]) : 0;
}

$tax = array();

if($taxID > 0)
{
	if(!($tax = CCrmTax::GetByID($taxID)))
	{
		ShowError(GetMessage('CRM_TAX_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}
}

$arResult['TAX_ID'] = $taxID;
$arResult['TAX'] = $tax;
$isEditMode = $taxID > 0 ? true : false;

$arResult['FORM_ID'] = 'CRM_TAX_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_TAX_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_TAX_LIST'],
	array()
);

$langs = array();
$rsLang = CLangAdmin::GetList(($by = 'sort'), ($order = 'asc'));
while ($arLang = $rsLang->Fetch())
{
	$lid = $arLang['LID'];

	$langs[$lid] = array(
		//'LID' => $lid,
		'NAME' => $arLang['NAME']
	);
}
$arResult['LANGS'] = $langs;

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{

		$taxID = isset($_POST['tax_id']) ? intval($_POST['tax_id']) : 0;
		$fields = array();

		if( $taxID <= 0 && isset($_POST['ID']))
			$taxID = intval(trim($_POST['ID']));

		if(isset($_POST['NAME']))
			$fields['NAME'] = $_POST['NAME'];

		if(isset($_POST['DESCRIPTION']))
			$fields['DESCRIPTION'] = $_POST['DESCRIPTION'];


		if(isset($_POST['LID']))
			$fields['LID'] = $_POST['LID'];
		else
			$fields['LID'] = SITE_ID;

		if(isset($_POST['CODE']))
			$fields['CODE'] = $_POST['CODE'];

		$tax = CCrmTax::GetByID($taxID);

		if(is_array($tax))
		{
			if(!CSaleTax::Update($taxID, $fields))
				ShowError(GetMessage('CRM_TAX_UPDATE_UNKNOWN_ERROR'));

		}
		else
		{
			$fields['TAX'] = $taxID;
			$taxID = CSaleTax::Add($fields);

			if(intval($taxID) <= 0)
				ShowError(GetMessage('CRM_TAX_ADD_UNKNOWN_ERROR'));

		}

		LocalRedirect(
			isset($_POST['apply'])
				? CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_TAX_EDIT'],
				array('tax_id' => $taxID)
			)
				: CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_TAX_LIST'],
				array('tax_id' => $taxID)
			)
		);
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$taxID = isset($arParams['TAX_ID']) ? intval($arParams['TAX_ID']) : 0;
		$tax = $taxID > 0 ? CCrmTax::GetByID($taxID) : null;
		if($tax)
		{
			if(!CSaleTax::Delete($taxID))
				ShowError(GetMessage('CRM_TAX_DELETE_UNKNOWN_ERROR'));
		}

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_TAX_LIST'],
				array()
			)
		);
	}
}

$arResult['FIELDS'] = array();
/*
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'tax_info',
	'name' => GetMessage('CRM_TAX_SECTION_MAIN'),
	'type' => 'section'
);
*/
if(strlen($arParams['TAX_ID']) > 0)
{
	$arResult['FIELDS']['tab_props'][] = array(
		'id' => 'ID',
		'name' => GetMessage('CRM_TAX_FIELD_ID'),
		'value' => $taxID,
		'type' =>  'label'
	);

	$arResult['FIELDS']['tab_props'][] = array(
		'id' => 'TIMESTAMP_X',
		'name' =>  GetMessage('CRM_TAX_FIELD_TIMESTAMP_X'),
		'value' => CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($tax['TIMESTAMP_X']))),
		'type' =>  'label'
	);
}
/*
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'LID',
	'name' =>  GetMessage('CRM_TAX_FIELD_LID'),
	'value' => $tax['LID'],
	'type' =>  'list',
	'items' => CCrmTax::getSitesList()
);
*/
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'NAME',
	'name' =>  GetMessage('CRM_TAX_FIELD_NAME'),
	'value' => htmlspecialcharsbx($tax['NAME']),
	'type' =>  'text'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'CODE',
	'name' =>  GetMessage('CRM_TAX_FIELD_CODE'),
	'value' => htmlspecialcharsbx($tax['CODE']),
	'type' =>  'text'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'DESCRIPTION',
	'name' =>  GetMessage('CRM_TAX_FIELD_DESCRIPTION'),
	'value' => $tax['DESCRIPTION'],
	'type' =>  'textarea'
);

$toolbarButtons = array();

if(Loader::includeModule('sale') && CSaleLocation::isLocationProEnabled())
{
	$width = 1024;
	$height = 500;
	$resizable = 'true';
}
else
{
	$width = 498;
	$height = 275;
	$resizable = 'false';
}

$toolbarButtons[] = array(
	'TEXT' => GetMessage('CRM_TAX_RATE_ADD'),
	'TITLE' => GetMessage('CRM_TAX_RATE_ADD_TITLE'),
	'ONCLICK' => "javascript:(new BX.CDialog({'content_url':'/bitrix/components/bitrix/crm.config.tax.rate.edit/box.php?FORM_ID=".strtoupper($arResult['FORM_ID'])."&TAX_ID=".$arResult['TAX_ID']."', 'width':'".$width."', 'height':'".$height."', 'resizable':".$resizable." })).Show()",
	'ICON' => 'btn-new'
);

$toolbarID = "CRM_TAX_RATE_TB";

if(intval($taxID) > 0)
{
	ob_start();

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => $toolbarID,
			'BUTTONS' => $toolbarButtons
		),
		'',
		array('HIDE_ICONS' => 'Y')
	);

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
			'EDIT_MODE' => 'Y'
		),
		''
	);

	$sVal = ob_get_contents();
	ob_end_clean();
/*
	$arResult['FIELDS']['tab_rateslist'][] = array(
		'id' => 'section_rates_grid',
		'name' => GetMessage('CRM_TAX_RATE_LIST').' "'.$tax['NAME'].'"',
		'type' => 'section'
	);
*/
	$arResult['FIELDS']['tab_rateslist'][] = array(
		'id' => 'TAX_RATES',
		'value' => $sVal,
		'type' =>  'custom',
		'colspan' => true
	);
}
$this->IncludeComponentTemplate();
?>