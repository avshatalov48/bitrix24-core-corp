<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['EDIT_MODE'] = isset($arParams['EDIT_MODE']) && $arParams['EDIT_MODE'] == 'Y' ? true : false;
$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE') && $arResult['EDIT_MODE'];

$arParams['PATH_TO_TAXRATE_LIST'] = CrmCheckPath('PATH_TO_TAXRATE_LIST', $arParams['PATH_TO_TAXRATE_LIST'], '');
$arParams['PATH_TO_TAXRATE_SHOW'] = CrmCheckPath('PATH_TO_TAXRATE_SHOW', $arParams['PATH_TO_TAXRATE_SHOW'], '?taxrate_id=#taxrate_id#&show');
$arParams['PATH_TO_TAXRATE_ADD'] = CrmCheckPath('PATH_TO_TAXRATE_ADD', $arParams['PATH_TO_TAXRATE_ADD'], '?add');
$arParams['PATH_TO_TAXRATE_EDIT'] = CrmCheckPath('PATH_TO_TAXRATE_EDIT', $arParams['PATH_TO_TAXRATE_EDIT'], '?taxrate_id=#taxrate_id#&edit');

$arResult['GRID_ID'] = 'CRM_TAXRATE_LIST';
$arResult['FORM_ID'] = 'CRM_TAXRATE_FORM';
$arResult['TAX_FORM_ID'] = isset($arParams['TAX_FORM_ID']) ? $arParams['TAX_FORM_ID'] : '';

$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['TAX_ID'] = isset($arParams['TAX_ID']) ? $arParams['TAX_ID'] : 0;

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
	array('id' => 'VALUE', 'name' => GetMessage('CRM_COLUMN_VALUE').' %', 'sort' => 'VALUE', 'default' => true, 'editable' => true),
	array('id' => 'IS_IN_PRICE', 'name' => GetMessage('CRM_COLUMN_IS_IN_PRICE'), 'sort' => 'IS_IN_PRICE', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'PERSON_TYPE_ID', 'name' => GetMessage('CRM_COLUMN_PERSON_TYPE_ID'), 'sort' => 'PERSON_TYPE_ID', 'default' => true, 'editable' => false),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'ACTIVE', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'APPLY_ORDER', 'name' => GetMessage('CRM_COLUMN_APPLY_ORDER'), 'sort' => 'APPLY_ORDER', 'default' => true, 'editable' => true),
	array('id' => 'TIMESTAMP_X', 'name' => GetMessage('CRM_COLUMN_TIMESTAMP_X'), 'sort' => 'TIMESTAMP_X', 'default' => true, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'NAME', 'default' => false, 'editable' => false)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if($arResult['CAN_DELETE'] && $action === 'delete')
	{
		$deleteAll = $_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y';
		$IDs = !$deleteAll ? $_POST['ID'] : array();
		$arRates = CCrmTax::GetRatesById($arResult['TAX_ID']);
		foreach($arRates as $arRate)
		{
			$rateID = $arRate['ID'];

			if(!$deleteAll && !in_array($rateID, $IDs, true))
				continue;

			if(!CSaleTaxRate::Delete($rateID))
			{
				ShowError(GetMessage('CRM_TAXRATE_DELETION_GENERAL_ERROR'));
				return;
			}
		}

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}
	elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		foreach($_POST['FIELDS'] as $ID => $arField)
		{
			$arFields = array();

			if(isset($arField['ACTIVE']))
				$arFields['ACTIVE'] = $arField['ACTIVE'];

			if(isset($arField['NAME']))
				$arFields['NAME'] = trim($arField['NAME']);

			if(isset($arField['VALUE']))
				$arFields['VALUE'] = ($arField['VALUE'] == '') ? False : $arField['VALUE'];

			if(isset($arField['IS_IN_PRICE']))
				$arFields['IS_IN_PRICE'] = trim($arField['IS_IN_PRICE']);

			if(isset($arField['APPLY_ORDER']))
				$arFields['APPLY_ORDER'] = ($arField['APPLY_ORDER'] == '') ? False : $arField['APPLY_ORDER'];

			if (count($arFields) > 0)
			{
				if(!CSaleTaxRate::Update($ID, $arFields))
				{

					if ($ex = $GLOBALS['APPLICATION']->GetException())
						$error = $ex->GetString();
					else
						$error = GetMessage('CRM_TAXRATE_UPDATE_GENERAL_ERROR');

					ShowError($error);
					return;
				}
			}
		}
	}

	if(!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPage());
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && check_bitrix_sessid() && isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
	{
		$rateID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if($rateID > 0)
		{
			if(!CSaleTaxRate::Delete($rateID))
				ShowError(GetMessage('CRM_TAXRATE_DELETION_GENERAL_ERROR'));
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['TAX_FORM_ID'].'_active_tab=tab_props' : '');
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('ID' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$siteId = '';
$siteIterator = Bitrix\Main\SiteTable::getList(array(
	'select' => array('LID', 'LANGUAGE_ID'),
	'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
));
if ($defaultSite = $siteIterator->fetch())
{
	$siteId = $defaultSite['LID'];
}
unset($defaultSite, $siteIterator);

$dbPersonType = \Bitrix\Crm\Invoice\PersonType::getList([
	'select' => ['ID', 'CODE', 'LIDS' => 'PERSON_TYPE_SITE.SITE_ID'],
	'filter' => [
		"=PERSON_TYPE_SITE.SITE_ID" => $siteId,
	],
	'order' => [
		'SORT' => "ASC",
		'NAME' => 'ASC'
	]
]);
while ($arPersonType = $dbPersonType->fetch())
{
	if (isset($arPersonTypeList[$arPersonType["ID"]]))
	{
		$arPersonTypeList[$arPersonType["ID"]]['LID'] .= ', '.$arPersonType["LIDS"];
	}
	else
	{
		$arPersonTypeList[$arPersonType["ID"]] = array(
			"ID" => $arPersonType["ID"],
			"CODE" => htmlspecialcharsEx($arPersonType["CODE"]),
			"LID" => $arPersonType["LIDS"]
		);
	}
}

$arRates = array();
$arTaxRates = [];
$arRates = CCrmTax::GetRatesById($arResult['TAX_ID']);

foreach($arRates as $k => $v)
{
	$rate = array();
	$rate['ID'] = $rate['~ID'] = $k;
	$rate['ACTIVE'] = $rate['~ACTIVE'] = $v['ACTIVE'];
	$rate['TIMESTAMP_X'] = $rate['~TIMESTAMP_X'] = CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($v['TIMESTAMP_X'])));
	$rate['NAME'] = $v['NAME'];

	if(intval($v['PERSON_TYPE_ID']) > 0)
	{
		$arPerType = $arPersonTypeList[$v['PERSON_TYPE_ID']];
		$rate['PERSON_TYPE_ID'] = GetMessage($arPerType["CODE"].'_PT');
	}
	else
	{
		$rate['PERSON_TYPE_ID'] = $rate['~PERSON_TYPE_ID'] = '&nbsp;';
	}

	$rate['VALUE'] = intval($v['VALUE']);
	$rate['IS_IN_PRICE'] = $rate['~IS_IN_PRICE'] = $v['IS_IN_PRICE'];
	$rate['APPLY_ORDER'] = intval($v['APPLY_ORDER']);

	$rate['PATH_TO_TAXRATE_SHOW'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAXRATE_SHOW'],
			array('taxrate_id' => $k)
		);

	$rate['PATH_TO_TAXRATE_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAXRATE_EDIT'],
			array('taxrate_id' => $k)
		);

	$rate['PATH_TO_TAXRATE_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_TAXRATE_LIST'],
				array( 'taxrate_id' => $k)
			),
			array(
				'action_'.$arResult['GRID_ID'] => 'delete',
				'ID' => $k,
				'sessid' => bitrix_sessid(),
				$arResult['TAX_FORM_ID'].'_active_tab' => 'tab_rateslist'
				)
		);

	$rate['~VALUE'] = $v['VALUE'];
	$rate['~APPLY_ORDER'] = $v['APPLY_ORDER'];
	$rate['~NAME'] = htmlspecialcharsBack($rate['NAME']);
	$rate['~PERSON_TYPE_ID'] = htmlspecialcharsBack($rate['PERSON_TYPE_ID']);
	$arTaxRates[] = $rate;
}

if(count($arTaxRates) > 0 && is_array($sort) && count($sort) > 0)
{
	// Process only first expression
	reset($sort);
	$by = key($sort);
	$order = $sort[$by] == 'asc' ? SORT_ASC : SORT_DESC;

	if(in_array($by, array('ID', 'NAME', 'VALUE', 'IS_IN_PRICE', 'PERSON_TYPE_ID', 'ACTIVE', 'APPLY_ORDER', 'TIMESTAMP_X'), true))
	{
		sortByColumn($arTaxRates, array($by => $order));
	}
}

$arResult['TAX_RATES'] = array();
$rowCount = $arResult['ROWS_COUNT'] = count($arTaxRates);
for($i = 0; $i < $rowCount; $i++)
{
	$rate = $arTaxRates[$i];
	$arResult['TAX_RATES'][$rate['ID']] = $rate;
}

$this->IncludeComponentTemplate();

?>
