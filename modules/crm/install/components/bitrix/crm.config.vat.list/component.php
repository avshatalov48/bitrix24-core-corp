<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_CATALOG_MODULE_NOT_INSTALLED'));
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

$arParams['PATH_TO_VAT_LIST'] = CrmCheckPath('PATH_TO_VAT_LIST', $arParams['PATH_TO_VAT_LIST'], '');
$arParams['PATH_TO_VAT_SHOW'] = CrmCheckPath('PATH_TO_VAT_SHOW', $arParams['PATH_TO_VAT_SHOW'], '?vat_id=#vat_id#&show');
$arParams['PATH_TO_VAT_ADD'] = CrmCheckPath('PATH_TO_VAT_ADD', $arParams['PATH_TO_VAT_ADD'], '?add');
$arParams['PATH_TO_VAT_EDIT'] = CrmCheckPath('PATH_TO_VAT_EDIT', $arParams['PATH_TO_VAT_EDIT'], '?vat_id=#vat_id#&edit');

$arResult['GRID_ID'] = 'CRM_VAT_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['VAT_MODE'] = CCrmTax::isVatMode();
if ($arResult['VAT_MODE'])
{
	$arResult['PRODUCT_ROW_TAX_UNIFORM'] = COption::GetOptionString('crm', 'product_row_tax_uniform', 'Y');
	$arResult['AJAX_PARAM_NAME'] = 'PRODUCT_ROW_TAX_UNIFORM';

	if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$arResult['AJAX_PARAM_NAME']]) && check_bitrix_sessid())
	{
		$APPLICATION->RestartBuffer();
		header('Content-type: application/x-www-form-urlencoded; charset=UTF-8');
		$errNumber = 0;
		CUtil::JSPostUnescape();
		$arResult['PRODUCT_ROW_TAX_UNIFORM'] = ($_POST[$arResult['AJAX_PARAM_NAME']] === 'Y') ? 'Y' : 'N';
		COption::SetOptionString('crm', 'product_row_tax_uniform', $arResult['PRODUCT_ROW_TAX_UNIFORM']);
		$result = array('ERROR' => $errNumber);
		if ($errNumber === 0)
			$result[$arResult['AJAX_PARAM_NAME']] = $arResult['PRODUCT_ROW_TAX_UNIFORM'];
		echo CUtil::PhpToJSObject($result);
		exit();
	}
}

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => true, 'editable' => true),
	array('id' => 'RATE', 'name' => GetMessage('CRM_COLUMN_RATE'), 'sort' => 'rate', 'default' => true, 'editable' => true),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'active', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'C_SORT', 'name' => GetMessage('CRM_COLUMN_C_SORT'), 'sort' => 'c_sort', 'default' => true, 'editable' => true)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if($arResult['CAN_DELETE'] && $action === 'delete')
	{
		$deleteAll = $_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y';
		$IDs = !$deleteAll ? $_POST['ID'] : array();
		$allVats = CCrmVat::GetAll();
		foreach($allVats as $arVat)
		{
			$vatID = $arVat['ID'];

			if(!$deleteAll && !in_array($vatID, $IDs, true))
				continue;

			if(!CCatalogVat::Delete($vatID))
			{
				$error = '';

				if ($ex = $APPLICATION->GetException())
					$error = $ex->GetString();
				else
					$error = GetMessage('CRM_VAT_DELETION_GENERAL_ERROR');

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

			$arFields['ID'] = $ID;

			if(isset($arField['NAME']))
				$arFields['NAME'] = trim($arField['NAME']);

			if(isset($arField['C_SORT']))
				$arFields['C_SORT'] = $arField['C_SORT'];

			if(isset($arField['ACTIVE']))
				$arFields['ACTIVE'] = $arField['ACTIVE'];

			if(isset($arField['RATE']))
				$arFields['RATE'] = trim($arField['RATE']);

			if (count($arFields) > 0)
			{
				if(!CCatalogVat::Set($arFields))
					ShowError(GetMessage('CRM_VAT_UPDATE_GENERAL_ERROR'));
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
		$vatID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if($vatID > 0)
		{
			if(!CCatalogVat::Delete($vatID))
			{
				$error = '';

				if ($ex = $APPLICATION->GetException())
					$error = $ex->GetString();
				else
					$error = GetMessage('CRM_VAT_DELETION_GENERAL_ERROR');

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
		'sort' => array('c_sort' => 'desc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$vats = array();
$allVats = CCrmVat::GetAll();
foreach($allVats as $k => $v)
{
	$arVat = array();
	$arVat['ID'] = $arVat['~ID'] = intval($k); // Key is Currency ID
	$arVat['NAME'] = $v['NAME'];
	$arVat['C_SORT'] = $arVat['~C_SORT'] = intval($v['C_SORT']);
	$arVat['ACTIVE'] = $arVat['~ACTIVE'] = $v['ACTIVE'] == 'Y' ? 'Y' : 'N';
	$arVat['RATE'] = $arVat['~RATE'] = $v['RATE'];

	$arVat['PATH_TO_VAT_SHOW'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_VAT_SHOW'],
			array('vat_id' => $k)
		);

	$arVat['PATH_TO_VAT_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_VAT_EDIT'],
			array('vat_id' => $k)
		);

	$arVat['PATH_TO_VAT_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_VAT_LIST'],
				array('vat_id' => $k)
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $k, 'sessid' => bitrix_sessid())
		);

	$arVat['~NAME'] = htmlspecialcharsBack($arVat['NAME']);
	$vats[] = $arVat;
}

if(is_array($sort) && count($sort) > 0)
{
	// Process only first expression
	reset($sort);
	$by = key($sort);
	$order = $sort[$by] == 'asc' ? SORT_ASC : SORT_DESC;

	if(in_array($by, array('ID', 'NAME', 'RATE', 'ACTIVE', 'C_SORT'), true))
	{
		sortByColumn($vats, array($by => $order));
	}
}

$arResult['VATS'] = array();
$rowCount = $arResult['ROWS_COUNT'] = count($vats);
for($i = 0; $i < $rowCount; $i++)
{
	$arVat = $vats[$i];
	$arResult['VATS'][$arVat['ID']] = $arVat;
}

$this->IncludeComponentTemplate();
?>
