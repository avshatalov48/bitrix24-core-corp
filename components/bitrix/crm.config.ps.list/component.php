<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Sale\PaySystem;
use \Bitrix\Main\IO;
use \Bitrix\Main\Application;

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

$arResult['CAN_ADD'] = $arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PS_LIST'] = CrmCheckPath('PATH_TO_PS_LIST', $arParams['PATH_TO_PS_LIST'], '');
$arParams['PATH_TO_PS_ADD'] = CrmCheckPath('PATH_TO_PS_ADD', $arParams['PATH_TO_PS_ADD'], '?add');
if (!array_key_exists('PATH_TO_PS_EDIT', $arParams))
	$arParams['PATH_TO_PS_EDIT'] = '/crm/configs/ps/edit/#ps_id#/';
$arParams['PATH_TO_PS_EDIT'] = CrmCheckPath('PATH_TO_PS_EDIT', $arParams['PATH_TO_PS_EDIT'], '?ps_id=#ps_id#&edit');

$arResult['GRID_ID'] = 'CRM_PS_LIST_GRID';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['EDIT_FORM_ID'] = 'CRM_PS_EDIT_FORM';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'ACTIVE', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'PERSON_TYPE_NAME', 'name' => GetMessage('CRM_COLUMN_PERSON_TYPE_NAME'), 'sort' => false, 'default' => true, 'editable' => false),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	$IDs = isset($_POST['ID']) ? $_POST['ID'] : array();
	if($arResult['CAN_DELETE'] && $action === 'delete' && !empty($IDs))
	{
		foreach($IDs as $psID)
		{
			$result = \Bitrix\Sale\PaySystem\Manager::delete($psID);
			if (!$result->isSuccess())
				ShowError(implode('<br>', $result->getErrorMessages()));
		}

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}
	elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		foreach($_POST['FIELDS'] as $ID => $arField)
		{
			$arFields = array();

			if(isset($arField['NAME']))
				$arFields['NAME'] = trim($arField['NAME']);

			if(isset($arField['ACTIVE']))
				$arFields['ACTIVE'] = trim($arField['ACTIVE']);

			if(isset($arField['SORT']))
				$arFields['SORT'] = ($arField['SORT'] <> '') ? $arField['SORT'] : 100;

			if (count($arFields) > 0)
			{
				if(!CSalePaySystem::Update($ID, $arFields))
					ShowError(GetMessage('CRM_PS_UPDATE_GENERAL_ERROR'));
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
		$psID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if($psID > 0)
		{
			$result = \Bitrix\Sale\PaySystem\Manager::delete($psID);
			if (!$result->isSuccess())
				ShowError(implode('<br>', $result->getErrorMessages()));
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_product' : '');
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$arSort = array();

$by = isset($_GET['by']) ? trim($_GET['by']) : 'ID';
$sort = isset($_GET['order']) ? trim($_GET['order']) : 'asc';

if(isset($_GET['by']) && isset($_GET['order']))
	$arSort = array($by => $sort);

$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('ID' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$arResult['PAY_SYSTEMS'] = array();
$arCrmPt = CCrmPaySystem::getPersonTypeIDs();
$arCrmPtIDs = array_values($arCrmPt);
$dbPaySystems = \Bitrix\Sale\PaySystem\Manager::getList(
	array(
		'filter' => array(
			"!ID" => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId(),
			'@ENTITY_REGISTRY_TYPE' => [REGISTRY_TYPE_CRM_QUOTE, REGISTRY_TYPE_CRM_INVOICE]
		)
	)
);

while ($paySystem = $dbPaySystems->fetch())
{
	$pt2psList = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($paySystem['ID']);
	if ($pt2psList && !array_intersect($pt2psList, $arCrmPtIDs))
		continue;

	$tmpPS = array();
	$tmpPS['ID'] = $tmpPS['~ID'] = $paySystem['ID'];
	$tmpPS['~NAME'] = $tmpPS['NAME'] = htmlspecialcharsbx($paySystem['NAME']);
	$tmpPS['ACTIVE'] = $tmpPS['~ACTIVE'] = $paySystem['ACTIVE'];
	$tmpPS['DESCRIPTION'] = $tmpPS['~DESCRIPTION'] = $paySystem['DESCRIPTION'];
	$tmpPS['LOGOTIP'] = $tmpPS['~LOGOTIP'] = $paySystem['LOGOTIP'];
	$tmpPS['SORT'] = $tmpPS['~SORT'] = $paySystem['SORT'];
	$tmpPS["HANDLER"] = $paySystem["ACTION_FILE"];

	$postfix = '';
	if (mb_strpos($paySystem["ACTION_FILE"], 'quote') !== false)
	{
		$postfix = '_QUOTE';
	}
	elseif (
		mb_strpos($paySystem["ACTION_FILE"], 'bill') !== false
		|| mb_strpos($paySystem["ACTION_FILE"], 'invoicedocument') !== false
	)
	{
		$postfix = '_BILL';
	}

	$tmpPS['DESCRIPTION'] = array(
		'RETURN' => GetMessage('CRM_PS_DESCRIPTION_RETURN_DEFAULT'),
		'RESTRICTION' => GetMessage('CRM_PS_DESCRIPTION_RESTRICTION_DEFAULT'),
		'COMMISSION' => GetMessage('CRM_PS_DESCRIPTION_COMMISSION_DEFAULT_2'.$postfix),
	);

	$service = PaySystem\Manager::getObjectById($paySystem['ID']);
	$tmpPS['IS_TUNED'] = ($service->isTuned() === true) ? 'Y' : 'N';
	
	$path = PaySystem\Manager::getPathToHandlerFolder($tmpPS["HANDLER"]);
	if (IO\File::isFileExists(Application::getDocumentRoot().$path.'/.description.php'))
	{
		include Application::getDocumentRoot().$path.'/.description.php';

		if (isset($description) && is_array($description))
		{
			foreach ($description as $key => $value)
				$tmpPS['DESCRIPTION'][$key] = $value;
		}
		unset($description);
	}

	$map = CSalePaySystemAction::getOldToNewHandlersMap();
	$oldHandler = array_search($tmpPS["ACTION_FILE"], $map);
	if ($oldHandler !== false)
		$tmpPS["ACTION_FILE"] = $oldHandler;

	$ptID = array_shift($pt2psList);
	$ptName = $arCrmPt['COMPANY'] == intval($ptID) ?  GetMessage('CRM_COMPANY_PT') : GetMessage('CRM_CONTACT_PT');
	$tmpPS['PERSON_TYPE_NAME'] = $tmpPS['~PERSON_TYPE_NAME'] = $ptName;

	$tmpPS['PATH_TO_PS_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PS_EDIT'],
			array('ps_id' => $paySystem['ID'])
		);

	$tmpPS['PATH_TO_PS_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PS_LIST'],
				array('ps_id' => $paySystem['ID'])
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $paySystem['ID'], 'sessid' => bitrix_sessid())
		);

	$arResult['PAY_SYSTEMS'][$tmpPS['ID']] = $tmpPS;
}

$arResult['ROWS_COUNT'] = count($arResult['PAY_SYSTEMS']);
$arResult['PERSON_TYPE_LIST'] = CCrmPaySystem::getPersonTypesList();

$this->IncludeComponentTemplate();
?>