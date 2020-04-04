<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
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
 * PATH_TO_CURRENCY_LIST
 * PATH_TO_CURRENCY_SHOW
 * PATH_TO_CURRENCY_EDIT
 * CURRENCY_ID
 * CURRENCY_ID_PAR_NAME
 */

$arParams['PATH_TO_CURRENCY_LIST'] = CrmCheckPath('PATH_TO_CURRENCY_LIST', $arParams['PATH_TO_CURRENCY_LIST'], '');
$arParams['PATH_TO_CURRENCY_SHOW'] = CrmCheckPath('PATH_TO_CURRENCY_SHOW', $arParams['PATH_TO_CURRENCY_SHOW'], '?currency_id=#currency_id#&show');
$arParams['PATH_TO_CURRENCY_EDIT'] = CrmCheckPath('PATH_TO_CURRENCY_EDIT', $arParams['PATH_TO_CURRENCY_EDIT'], '?currency_id=#currency_id#&edit');

$baseCurrencyID = CCrmCurrency::GetBaseCurrencyID();
$currencyID = isset($arParams['CURRENCY_ID']) ? strval($arParams['CURRENCY_ID']) : '';
if($currencyID === '')
{
	$currencyIDParName = isset($arParams['CURRENCY_ID_PAR_NAME']) ? strval($arParams['CURRENCY_ID_PAR_NAME']) : '';
	if(strlen($currencyIDParName) == 0)
	{
		$currencyIDParName = 'currency_id';
	}

	$currencyID = isset($_REQUEST[$currencyIDParName]) ? strval($_REQUEST[$currencyIDParName]) : '';
}

$currency = array();
$isEditMode = false;
if($currencyID !== '')
{
	if(!($currency = CCrmCurrency::GetByID($currencyID)))
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
	$isEditMode = true;
}
$isBaseCurrency = $currencyID !== '' && $currencyID === $baseCurrencyID;

$currency['IS_ACCOUNTING_CURRENCY'] = $currencyID !== '' && $currencyID === CCrmCurrency::GetAccountCurrencyID();
$currency['IS_INVOICE_CURRENCY'] = $currencyID !== '' && $currencyID === CCrmCurrency::getInvoiceDefault();

$arResult['CURRENCY_ID'] = $currencyID;
$arResult['CURRENCY'] = $currency;

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

$currencyLocs = array();
if($currencyID !== '')
{
	$rs = CCurrencyLang::GetList(($by = ''), ($order = ''), $currencyID);
	while ($ary = $rs->Fetch())
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

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$currencyID = isset($_POST['currency_id']) ? $_POST['currency_id'] : '';
		$fields = array();

		if(!isset($currencyID[0]) && isset($_POST['ID']))
		{
			$currencyID = trim($_POST['ID']);
		}

		$currencyID = strtoupper($currencyID);

		if($currencyID !== $baseCurrencyID)
		{
			if(isset($_POST['AMOUNT_CNT']))
			{
				$fields['AMOUNT_CNT'] = max(intval($_POST['AMOUNT_CNT']), 1);
			}

			if(isset($_POST['DEFAULT_EXCH_RATE']))
			{
				$defaultExchRate = doubleval($_POST['DEFAULT_EXCH_RATE']);
				if($defaultExchRate <= 0)
				{
					$defaultExchRate = 1; //default
				}

				$fields['AMOUNT'] = $defaultExchRate;
			}
		}

		if(isset($_POST['SORT']))
		{
			$fields['SORT'] = max(intval($_POST['SORT']), 0);
		}

		$result = false;
		$presentFields = CCrmCurrency::GetByID($currencyID);
		if(is_array($presentFields))
		{
			$arResult['CURRENCY'] = $currency = array_merge($presentFields, $fields);

			$result = CCrmCurrency::Update($currencyID, $fields);
			if(!$result)
			{
				$err = CCrmCurrency::GetLastError();
				$arResult['ERROR'] = $err !== '' ? $err : GetMessage('CRM_CURRENCY_UPDATE_UNKNOWN_ERROR');

			}
		}
		else
		{
			$arResult['CURRENCY'] = $currency = $fields;

			$fields['CURRENCY'] = $currencyID;
			$fields['AMOUNT_CNT'] = 1; //Default
			$currencyID = CCrmCurrency::Add($fields);
			$result = is_string($currencyID) && $currencyID !== '';
			if(!$result)
			{
				$err = CCrmCurrency::GetLastError();
				$arResult['ERROR'] = $err !== '' ? $err : GetMessage('CRM_CURRENCY_ADD_UNKNOWN_ERROR');
				$currencyID = $fields['CURRENCY'];
			}
		}

		if(isset($_POST['ACCOUNTING']))
		{
			$currency['IS_ACCOUNTING_CURRENCY'] = $_POST['ACCOUNTING'] === 'Y';
		}

		if(isset($_POST['INVOICES_DEF']))
		{
			$currency['IS_INVOICE_CURRENCY'] = $_POST['INVOICES_DEF'] === 'Y';
		}

		foreach($langs as $k => $v)
		{
			$lid = strtoupper($k);

			$locFields = array();

			$param = 'FULL_NAME_'.$lid;
			if(isset($_POST[$param]))
			{
				$locFields['FULL_NAME'] = strip_tags($_POST[$param]);
			}

			$param = 'FORMAT_STRING_'.$lid;
			if(isset($_POST[$param]))
			{
				$locFields['FORMAT_STRING'] = strip_tags($_POST[$param]);
			}

			$param = 'DEC_POINT_'.$lid;
			if(isset($_POST[$param]))
			{
				$locFields['DEC_POINT'] = $_POST[$param];
			}

			$param = 'THOUSANDS_VARIANT_'.$lid;
			if(isset($_POST[$param]))
			{
				$locFields['THOUSANDS_VARIANT'] = trim($_POST[$param]);
			}

			$param = 'THOUSANDS_SEP_'.$lid;
			if(!isset($locFields['THOUSANDS_VARIANT'])
				&& isset($_POST[$param]))
			{
				$locFields['THOUSANDS_SEP'] = trim($_POST[$param]);
			}

			if(count($locFields) == 0)
			{
				continue;
			}

			if(!(isset($locFields['FULL_NAME']) && strlen($locFields['FULL_NAME']) !== 0))
			{
				$locFields['FULL_NAME'] = $currencyID;
			}

			$currencyLocs[$k] = $locFields;

			if($result)
			{
				$locFields['CURRENCY'] = $currencyID;
				$locFields['LID'] = $k;
				if(is_array(CCurrencyLang::GetByID($currencyID, $k)))
				{
					CCurrencyLang::Update($currencyID, $k, $locFields);
				}
				else
				{
					$locFields['DECIMALS'] = 2; //Default decimals
					CCurrencyLang::Add($locFields);
				}
			}
		}

		if($result)
		{
			if($currency['IS_ACCOUNTING_CURRENCY'] && $currencyID !== CCrmCurrency::GetAccountCurrencyID())
			{
				CCrmCurrency::SetAccountCurrencyID($currencyID);
			}
			elseif(!$currency['IS_ACCOUNTING_CURRENCY'] && $currencyID === CCrmCurrency::GetAccountCurrencyID())
			{
				CCrmCurrency::SetAccountCurrencyID(CCrmCurrency::GetBaseCurrencyID());
			}

			if($currency['IS_INVOICE_CURRENCY'] && $currencyID !== CCrmCurrency::getInvoiceDefault())
			{
				CCrmCurrency::setInvoiceDefault($currencyID);
			}

			LocalRedirect(
				isset($_POST['apply'])
					? CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_CURRENCY_EDIT'],
					array('currency_id' => $currencyID)
				)
					: CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_CURRENCY_LIST'],
					array('currency_id' => $currencyID)
				)
			);
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$currencyID = isset($arParams['CURRENCY_ID']) ? strval($arParams['CURRENCY_ID']) : '';
		$currency = isset($currencyID[0]) ? CCrmCurrency::GetByID($currencyID) : null;
		if($currency)
		{
			if(!CCrmCurrency::Delete($currencyID))
			{
				$err = CCrmCurrency::GetLastError();
				ShowError(isset($err[0]) ? $err : GetMessage('CRM_CURRENCY_DELETE_UNKNOWN_ERROR'));
				return;
			}
		}

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CURRENCY_LIST'],
				array()
			)
		);
	}
}

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'currency_info',
	'name' => GetMessage('CRM_CURRENCY_SECTION_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => GetMessage('CRM_CURRENCY_FIELD_ID'),
	'params' => array('size' => 10),
	'value' => htmlspecialcharsbx($currencyID),
	'type' =>  $isEditMode ? 'label' : 'text'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'AMOUNT_CNT',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_AMOUNT_CNT'),
	'params' => array('size' => 10),
	'value' => isset($currency['AMOUNT_CNT']) ? $currency['AMOUNT_CNT'] : '1',
	'type' =>  $isBaseCurrency ? 'label' : 'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DEFAULT_EXCH_RATE',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_DEFAULT_EXCH_RATE'),
	'params' => array('size' => 10),
	'value' => isset($currency['AMOUNT']) ? $currency['AMOUNT'] : '1',
	'type' =>  $isBaseCurrency ? 'label' : 'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_SORT'),
	'params' => array('size' => 10),
	'value' => isset($currency['SORT']) ? $currency['SORT'] : '10',
	'type' =>  'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BASE',
	'name' =>  GetMessage('CRM_CURRENCY_EDIT_BASE'),
	'value' => $isBaseCurrency ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'type' =>  'label'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACCOUNTING',
	'name' =>  GetMessage('CRM_CURRENCY_FIELD_ACCOUNTING'),
	'value' => $currency['IS_ACCOUNTING_CURRENCY'] ? 'Y' : 'N',
	'type' =>  'checkbox'
);

if($isEditMode && $currency['IS_INVOICE_CURRENCY'])
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'INVOICES_DEF',
		'name' =>  GetMessage('CRM_CURRENCY_INVOICES_DEFAULT'),
		'value' => GetMessage('MAIN_YES'),
		'type' =>  'label'
	);
}
else
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'INVOICES_DEF',
		'name' =>  GetMessage('CRM_CURRENCY_INVOICES_DEFAULT'),
		'value' => $currency['IS_INVOICE_CURRENCY'] ? 'Y' : 'N',
		'type' =>  'checkbox'
	);
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
		'params' => array('size' => 50),
		'value' => isset($currencyLoc['FULL_NAME']) ? htmlspecialcharsbx($currencyLoc['FULL_NAME']) : '',
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'FORMAT_STRING_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_FORMAT_STRING'),
		'params' => array('size' => 10),
		'value' => isset($currencyLoc['FORMAT_STRING']) ? htmlspecialcharsbx($currencyLoc['FORMAT_STRING']) : '#',
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DEC_POINT_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_DEC_POINT'),
		'params' => array('size' => 10),
		'value' => isset($currencyLoc['DEC_POINT']) ? htmlspecialcharsbx($currencyLoc['DEC_POINT']) : '.',
		'type' =>  'text'
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'THOUSANDS_VARIANT_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT'),
		'value' => isset($currencyLoc['THOUSANDS_VARIANT']) ? $currencyLoc['THOUSANDS_VARIANT'] : '.',
		'type' =>  'list',
		'value' => isset($currencyLoc['THOUSANDS_VARIANT']) ? $currencyLoc['THOUSANDS_VARIANT'] : 'N',
		'items' => array(
			'N' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_N'),
			'D' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_D'),
			'C' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_C'),
			'S' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_S'),
			'B' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_B'),
			'' => GetMessage('CRM_CURRENCY_THOUSANDS_VARIANT_ANOTHER')
		)
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'THOUSANDS_SEP_'.$lid,
		'name' =>  GetMessage('CRM_CURRENCY_THOUSANDS_SEP'),
		'params' => array('size' => 10),
		'value' => isset($currencyLoc['THOUSANDS_SEP']) ? htmlspecialcharsbx($currencyLoc['THOUSANDS_SEP']) : '',
		'type' =>  'text'
	);
}
$this->IncludeComponentTemplate();
