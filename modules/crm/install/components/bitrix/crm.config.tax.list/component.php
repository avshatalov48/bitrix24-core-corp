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

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_TAX_LIST'] = CrmCheckPath('PATH_TO_TAX_LIST', $arParams['PATH_TO_TAX_LIST'], '');
$arParams['PATH_TO_TAX_SHOW'] = CrmCheckPath('PATH_TO_TAX_SHOW', $arParams['PATH_TO_TAX_SHOW'], '?tax_id=#tax_id#&show');
$arParams['PATH_TO_TAX_ADD'] = CrmCheckPath('PATH_TO_TAX_ADD', $arParams['PATH_TO_TAX_ADD'], '?add');
$arParams['PATH_TO_TAX_EDIT'] = CrmCheckPath('PATH_TO_TAX_EDIT', $arParams['PATH_TO_TAX_EDIT'], '?tax_id=#tax_id#&edit');

$arResult['GRID_ID'] = 'CRM_TAX_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['EDIT_FORM_ID'] = 'CRM_TAX_EDIT_FORM';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true),
	array('id' => 'STAV', 'name' => GetMessage('CRM_COLUMN_RATES'), 'sort' => false, 'default' => true, 'editable' => false),
	array('id' => 'TIMESTAMP_X', 'name' => GetMessage('CRM_COLUMN_DATE'), 'sort' => 'TIMESTAMP_X', 'default' => true, 'editable' => false),
	array('id' => 'LID', 'name' => GetMessage('CRM_COLUMN_SITE'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'CODE', 'name' => GetMessage('CRM_COLUMN_CODE'), 'sort' => 'CODE', 'default' => false, 'editable' => true)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if($arResult['CAN_DELETE'] && $action === 'delete')
	{
		$deleteAll = $_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y';
		$IDs = !$deleteAll ? $_POST['ID'] : array();
		$allTaxes = CCrmTax::GetAll();
		foreach($allTaxes as $arTax)
		{
			$taxID = $arTax['ID'];

			if(!$deleteAll && !in_array($taxID, $IDs, true))
				continue;

			if(!CSaleTax::Delete($taxID))
			{
				$error = '';

				if ($ex = $APPLICATION->GetException())
					$error = $ex->GetString();
				else
					$error = GetMessage('CRM_TAX_DELETION_GENERAL_ERROR');

				ShowError($error);
			}
		}

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}
	elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		foreach($_POST['FIELDS'] as $ID => $arField)
		{
			$arFields = array();

			if(isset($arField['LID']))
				$arFields['LID'] = $arField['LID'];

			if(isset($arField['NAME']))
				$arFields['NAME'] = trim($arField['NAME']);

			if(isset($arField['CODE']))
				$arFields['CODE'] = (strlen($arField['CODE'])<=0) ? False : $arField['CODE'];

			if (count($arFields) > 0)
			{
				if(!CSaleTax::Update($ID, $arFields))
					ShowError(GetMessage('CRM_TAX_UPDATE_GENERAL_ERROR'));
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
		$taxID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if($taxID > 0)
		{
			if(!CSaleTax::Delete($taxID))
			{
				$error = '';

				if ($ex = $APPLICATION->GetException())
					$error = $ex->GetString();
				else
					$error = GetMessage('CRM_TAX_DELETION_GENERAL_ERROR');

				ShowError($error);
			}
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_product' : '');
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('NAME' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$arLangs = array();
$dbLangsList = CSite::GetList(($b = "sort"), ($o = "asc"));
while ($arLang = $dbLangsList->Fetch())
	$arLangs[$arLang["LID"]] = "[".$arLang["LID"]."]&nbsp;".$arLang["NAME"];

$taxies = array();
$allTaxies = CCrmTax::GetAll();
foreach($allTaxies as $k => $v)
{
	$tax = array();
	$tax['ID'] = $tax['~ID'] = $k; // Key is Currency ID
	$tax['TIMESTAMP_X'] = $tax['~TIMESTAMP_X'] = CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($v['TIMESTAMP_X'])));
	$tax['LID'] = $arLangs[$v['LID']];
	$tax['NAME'] = $v['NAME'];
	$tax['CODE'] = $v['CODE'];

	$tax['PATH_TO_TAX_SHOW'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAX_SHOW'],
			array('tax_id' => $k)
		);

	$tax['PATH_TO_TAX_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAX_EDIT'],
			array('tax_id' => $k)
		);

	$tax['PATH_TO_TAX_SHOW_RATES'] =
		CHTTP::urlAddParams(
			$tax['PATH_TO_TAX_EDIT'],
			array(
				"FORM_ID" => $arResult['EDIT_FORM_ID'],
				$arResult['EDIT_FORM_ID']."_active_tab" => 'tab_rateslist'
				)
		);

	$tax['PATH_TO_TAX_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_TAX_LIST'],
				array('tax_id' => $k)
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $k, 'sessid' => bitrix_sessid())
		);

	$rateNum = 0;
	$dbRes = CSaleTaxRate::GetList(array(), array("TAX_ID" => $tax['ID']));
	while ($dbRes->Fetch())
		$rateNum++;

	if ($rateNum > 0)
	{
		$rateNumShow = "<a href=\"".$tax['PATH_TO_TAX_SHOW_RATES']."\">".$rateNum."</a>";
	}
	else
		$rateNumShow = "0";

	$tax['STAV'] = $rateNumShow;
	$tax['~NAME'] = htmlspecialcharsBack($tax['NAME']);
	$tax['~LID'] = htmlspecialcharsBack($tax['LID']);
	$tax['~CODE'] = htmlspecialcharsBack($tax['CODE']);
	$taxies[] = $tax;
}

if(is_array($sort) && count($sort) > 0)
{
	// Process only first expression
	reset($sort);
	$by = key($sort);
	$order = $sort[$by] == 'asc' ? SORT_ASC : SORT_DESC;

	if(in_array($by, array('ID', 'NAME', 'CODE', 'TIMESTAMP_X'), true))
	{
		sortByColumn($taxies, array($by => $order));
	}
}

$arResult['TAXIES'] = array();
$rowCount = $arResult['ROWS_COUNT'] = count($taxies);
for($i = 0; $i < $rowCount; $i++)
{
	$tax = $taxies[$i];
	$arResult['TAXIES'][$tax['ID']] = $tax;
}

$this->IncludeComponentTemplate();

?>
