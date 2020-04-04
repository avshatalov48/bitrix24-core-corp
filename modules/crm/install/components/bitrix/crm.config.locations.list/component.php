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

$arParams['PATH_TO_LOCATIONS_LIST'] = CrmCheckPath('PATH_TO_LOCATIONS_LIST', $arParams['PATH_TO_LOCATIONS_LIST'], '');
$arParams['PATH_TO_LOCATIONS_ADD'] = CrmCheckPath('PATH_TO_LOCATIONS_ADD', $arParams['PATH_TO_LOCATIONS_ADD'], '?add');
$arParams['PATH_TO_LOCATIONS_EDIT'] = CrmCheckPath('PATH_TO_LOCATIONS_EDIT', $arParams['PATH_TO_LOCATIONS_EDIT'], '?loc_id=#loc_id#&edit');

$arResult['GRID_ID'] = 'CRM_LOC_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => true, 'editable' => false),
	array('id' => 'COUNTRY_NAME', 'name' => GetMessage('CRM_COLUMN_COUNTRY_NAME'), 'sort' => 'country_name', 'default' => true, 'editable' => false),
	array('id' => 'REGION_NAME', 'name' => GetMessage('CRM_COLUMN_REGION_NAME'), 'sort' => 'region_name', 'default' => true, 'editable' => false),
	array('id' => 'CITY_NAME', 'name' => GetMessage('CRM_COLUMN_CITY_NAME'), 'sort' => 'city_name', 'default' => true, 'editable' => false),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'sort', 'default' => true, 'editable' => false)
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if($arResult['CAN_DELETE'] && $action === 'delete')
	{
		$deleteAll = $_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y';
		$IDs = !$deleteAll ? $_POST['ID'] : array();
		$errorMsg = '';

		if($deleteAll)
		{
			$dbLoc = CSaleLocation::GetList(array(), array(), false, false, array('ID'));

			while ($arLoc = $dbLoc->Fetch())
				CSaleLocation::Delete($arLoc['ID']);
		}
		else
		{
			foreach($IDs as $locID)
			{
				if(!CSaleLocation::Delete($locID))
				{

					if ($ex = $APPLICATION->GetException())
						$errorMsg .= $ex->GetString()."<br>";
					else
						$errorMsg .= GetMessage('CRM_LOC_DELETION_GENERAL_ERROR')."<br>";
				}
			}
		}

		if(strlen($errorMsg) > 0)
			ShowError($errorMsg);

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if(!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPage());
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && check_bitrix_sessid() && isset($_GET['action_'.$arResult['GRID_ID']]))
{
	$errorMsg = '';

	if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
	{
		$locID = isset($_GET['ID']) ? $_GET['ID'] : 0;
		if($locID > 0)
		{
			if(!CSaleLocation::Delete($locID))
			{
				if ($ex = $APPLICATION->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_LOC_DELETION_GENERAL_ERROR');

				ShowError($errorMsg);
			}
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if(!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPage());
	}
}

$arResult['FILTER_PRESETS'] = array();
$arResult['FILTER'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'default' => false),
	array('id' => 'COUNTRY_NAME', 'name' => GetMessage('CRM_COLUMN_COUNTRY_NAME'), 'default' => true),
	array('id' => 'REGION_NAME', 'name' => GetMessage('CRM_COLUMN_REGION_NAME'), 'default' => true),
	array('id' => 'CITY_NAME', 'name' => GetMessage('CRM_COLUMN_CITY_NAME'), 'default' => true),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'default' => false)
);

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arFilter = $CGridOptions->GetFilter($arResult['FILTER']);

$arMutableFilters = array('COUNTRY_NAME', 'REGION_NAME', 'CITY_NAME',);
foreach ($arFilter as $k => $v)
{
	if(!in_array($k, $arMutableFilters, true))
		continue;

	$arFilter['~'.$k] = $v;
	unset($arFilter[$k]);
}

$arSort = array();

$by = isset($_GET['by']) ? trim($_GET['by']) : 'ID';
$sort = isset($_GET['order']) ? trim($_GET['order']) : 'asc';

if(isset($_GET['by']) && isset($_GET['order']))
	$arSort = array($by => $sort);

$gridSorting = $CGridOptions->GetSorting(
	array(
		'sort' => array('ID' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];
$locations = array();
$arFilter['LID'] = LANGUAGE_ID;

$dbLocations = CSaleLocation::GetList(
									array(),
									$arFilter,
									false,
									false,
									array(
										'ID',
										'COUNTRY_NAME',
										'COUNTRY_NAME_ORIG',
										'REGION_NAME',
										'REGION_NAME_ORIG',
										'CITY_NAME',
										'CITY_NAME_ORIG',
										'SORT'
										)
									);

while($arLocation = $dbLocations->Fetch())
{
	$arLoc = array();
	$arLoc['ID'] = $arLoc['~ID'] = intval($arLocation['ID']);
	$arLoc['COUNTRY_NAME'] = $arLoc['~COUNTRY_NAME'] = $arLocation['COUNTRY_NAME'] != null ? $arLocation['COUNTRY_NAME'] : $arLocation['COUNTRY_NAME_ORIG'];
	$arLoc['REGION_NAME'] = $arLoc['~REGION_NAME'] = $arLocation['REGION_NAME'] != null ? $arLocation['REGION_NAME'] : $arLocation['REGION_NAME_ORIG'];
	$arLoc['CITY_NAME'] = $arLoc['~CITY_NAME'] = $arLocation['CITY_NAME'] != null ? $arLocation['CITY_NAME'] : $arLocation['CITY_NAME_ORIG'];
	$arLoc['SORT'] = $arLocation['SORT'];
	$locations[] = $arLoc;
}

$sort = $arResult['SORT'];
if(is_array($sort) && count($sort) > 0)
{
	// Process only first expression
	reset($sort);
	$by = key($sort);
	$order = $sort[$by] == 'asc' ? SORT_ASC : SORT_DESC;
	sortByColumn($locations, array( strtoupper($by) => $order));
}

$arResult['LOCS'] = array();
$dbRecordsList = new CDBResult;
$dbRecordsList->InitFromArray($locations);
$dbRecordsList->NavStart(20);
$dbRecordsList->bShowAll = false;

unset($locations);

while ($arLoc = $dbRecordsList->Fetch())
{
	$arLoc['PATH_TO_LOCATIONS_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_LOCATIONS_EDIT'],
			array('loc_id' => $arLoc['ID'])
		);

	$arLoc['PATH_TO_LOCATIONS_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_LOCATIONS_LIST'],
				array('loc_id' => $arLoc['ID'])
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arLoc['ID'], 'sessid' => bitrix_sessid())
		);

	$arResult['LOCS'][$arLoc['ID']] = $arLoc;
}

$arResult['ROWS_COUNT'] = $dbRecordsList->NavRecordCount;
$arResult["NAV_STRING"] = $dbRecordsList->GetPageNavStringEx($navComponentObject, GetMessage("CRM_INTS_TASKS_NAV"), "", false);
$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
$arResult["NAV_RESULT"] = $dbRecordsList;

$this->IncludeComponentTemplate();
?>