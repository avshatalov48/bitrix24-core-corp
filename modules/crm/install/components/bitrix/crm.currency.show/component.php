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

$baseCurrencyID = CCrmCurrency::GetBaseCurrencyID();
$currencyID = isset($arParams['CURRENCY_ID']) ? strval($arParams['CURRENCY_ID']) : '';
if(!isset($currencyID[0]))
{
	$currencyIDParName = isset($arParams['CURRENCY_ID_PAR_NAME']) ? strval($arParams['CURRENCY_ID_PAR_NAME']) : '';
	if(strlen($currencyIDParName) == 0)
	{
		$currencyIDParName = 'currency_id';
	}

	$currencyID = isset($_REQUEST[$currencyIDParName]) ? strval($_REQUEST[$currencyIDParName]) : '';
}

$currency = array();
if(!isset($currencyID[0]) || !($currency = CCrmCurrency::GetByID($currencyID)))
{
	ShowError(GetMessage('CRM_CURRENCY_NOT_FOUND'));
	@define('ERROR_404', 'Y');
	if($arParams['SET_STATUS_404'] === 'Y')
	{
		CHTTP::SetStatus("404 Not Found");
	}
	return;
}
$currencyID = $currency['CURRENCY'];
$isBaseCurrency = $currencyID === $baseCurrencyID;

$arResult['CURRENCY_ID'] = $currencyID;
$arResult['CURRENCY'] = $currency;
$isEditMode = isset($currencyID[0]);

$arResult['FORM_ID'] = 'CRM_CURRENCY_EDIT';
$arResult['GRID_ID'] = 'CRM_CURRENCY_EDIT';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_CURRENCY_LIST'],
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

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'currency_info',
	'name' => GetMessage('CRM_CURRENCY_SECTION_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => GetMessage('CRM_CURRENCY_FIELD_ID'),
	'value' => $currencyID,
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'AMOUNT_CNT',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_AMOUNT_CNT'),
	'value' => isset($currency['AMOUNT_CNT']) ? $currency['AMOUNT_CNT'] : '',
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DEFAULT_EXCH_RATE',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_DEFAULT_EXCH_RATE'),
	'value' => isset($currency['AMOUNT']) ? $currency['AMOUNT'] : '',
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_SORT'),
	'value' => isset($currency['SORT']) ? $currency['SORT'] : '',
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BASE',
	'name' =>  GetMessage('CRM_CURRENCY_SHOW_BASE'),
	'value' => $isBaseCurrency ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'type' =>  'label'
);

$defaultForInvoices = CCrmCurrency::getInvoiceDefault() == $currencyID ? GetMessage("MAIN_YES") : GetMessage("MAIN_NO");

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INVOICES_DEFAULT',
	'name' =>  GetMessage('CRM_CURRENCY_INVOICES_DEFAULT'),
	'value' => $defaultForInvoices,
	'type' =>  'label'
);

$currencyLocs = array();
if(isset($currencyID[0]))
{
	$rs = CCurrencyLang::GetList(($by = ''), ($order = ''), $currencyID);
	while ($ary = $rs->GetNext())
	{
		$loc = array();
		$loc['FULL_NAME'] = $ary['FULL_NAME'];
		$loc['FORMAT_STRING'] = $ary['FORMAT_STRING'];
		$loc['DEC_POINT'] = $ary['DEC_POINT'];
		$loc['THOUSANDS_SEP'] = $ary['THOUSANDS_SEP'];
		$loc['THOUSANDS_VARIANT'] = $ary['THOUSANDS_VARIANT'];

		$currencyLocs[$ary['LID']] = $loc;
	}
}
$arResult['CURRENCY_LOCALIZATIONS'] = $currencyLocs;

foreach($langs as $k => $v)
{
	$lid = strtoupper($k);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'localization_info_'.strtolower($lid),
		'name' => $v['NAME'],
		'type' => 'section'
	);

	$currencyLoc = isset($currencyLocs[$k]) ? $currencyLocs[$k] : array();

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'FULL_NAME_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_FULL_NAME'),
		'value' => isset($currencyLoc['FULL_NAME']) ? $currencyLoc['FULL_NAME'] : '',
		'type' =>  'label'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'FORMAT_STRING_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_FORMAT_STRING'),
		'value' => isset($currencyLoc['FORMAT_STRING']) ? $currencyLoc['FORMAT_STRING'] : '#',
		'type' =>  'label'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DEC_POINT_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_DEC_POINT'),
		'value' => isset($currencyLoc['DEC_POINT']) ? $currencyLoc['DEC_POINT'] : '.',
		'type' =>  'label'
	);

	$thousandsVariant = isset($currencyLoc['THOUSANDS_VARIANT']) ? $currencyLoc['THOUSANDS_VARIANT'] : '';
	$thousandsVariant = isset($thousandsVariant[0])
		? GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_'.strtoupper($thousandsVariant))
		: (isset($currencyLoc['THOUSANDS_SEP']) ? $currencyLoc['THOUSANDS_SEP'] : '');

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'THOUSANDS_VARIANT_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT'),
		'value' => $thousandsVariant,
		'type' =>  'label'
	);
}

$this->IncludeComponentTemplate();
