<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALLED'));
	return;
}

$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
if (!CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] =
	CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions);

$arParams['PATH_TO_MEASURE_LIST'] = CrmCheckPath('PATH_TO_MEASURE_LIST', $arParams['PATH_TO_MEASURE_LIST'], '');
$arParams['PATH_TO_MEASURE_EDIT'] = CrmCheckPath('PATH_TO_MEASURE_EDIT', $arParams['PATH_TO_MEASURE_EDIT'], '');

$arResult['GRID_ID'] = 'CRM_MEASURE_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_MEASURE_EDIT';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => true, 'editable' => false),
	array('id' => 'CODE', 'name' => GetMessage('CRM_COLUMN_CODE'), 'sort' => 'CODE', 'default' => true, 'editable' => false),
	array('id' => 'MEASURE_TITLE', 'name' => GetMessage('CRM_COLUMN_MEASURE_TITLE'), 'sort' => 'MEASURE_TITLE', 'default' => true, 'editable' => true),
	array('id' => 'SYMBOL_RUS', 'name' => GetMessage('CRM_COLUMN_SYMBOL_RUS'), 'sort' => 'SYMBOL_RUS', 'default' => true, 'editable' => true),
	array('id' => 'SYMBOL_INTL', 'name' => GetMessage('CRM_COLUMN_SYMBOL_INTL'), 'sort' => 'SYMBOL_INTL', 'default' => true, 'editable' => true),
	array('id' => 'SYMBOL_LETTER_INTL', 'name' => GetMessage('CRM_COLUMN_SYMBOL_LETTER_INTL'), 'sort' => 'SYMBOL_LETTER_INTL', 'default' => false, 'editable' => true),
	array('id' => 'IS_DEFAULT', 'name' => GetMessage('CRM_COLUMN_IS_DEFAULT'), 'sort' => 'IS_DEFAULT', 'default' => true, 'editable' => false)
);

// Try to extract user action data -->
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);

if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];

	if ($actionData['METHOD'] == 'POST' && isset($_POST[$postAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_POST[$postAction];
		unset($_POST[$postAction], $_REQUEST[$postAction]);
		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
		}
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_GET['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
		}
	}
}
// <-- Try to extract user action data

// POST & GET actions processing -->
if($actionData['ACTIVE'] && $arResult['CAN_EDIT'])
{
	if ($actionData['METHOD'] === 'POST')
	{
		if($actionData['NAME'] === 'delete')
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				/*else
				{
					$arFilterDel += $arFilter;
				}*/

				$dbResult = CCatalogMeasure::getList(
					array(),
					$arFilterDel,
					false,
					false,
					array('ID')
				);

				while($measure = $dbResult->Fetch())
				{
					CCatalogMeasure::delete($measure['ID']);
				}
			}
		}
		elseif($actionData['NAME'] === 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						CCatalogMeasure::update($ID, $arUpdateData);
					}
				}
			}
		}
		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_MEASURE_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] === 'delete' && isset($actionData['ID']))
		{
			CCatalogMeasure::delete($actionData['ID']);
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_MEASURE_LIST']);
		}
	}
}
// <-- POST & GET actions processing

if (intval($arParams['MEASURE_COUNT']) <= 0)
{
	$arParams['MEASURE_COUNT'] = 20;
}
$arNavParams = array(
	'nPageSize' => $arParams['MEASURE_COUNT']
);
$arNavigation = CDBResult::GetNavParams($arNavParams);

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('CODE' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);
$arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$arNavParams = $gridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$sortField = isset($_REQUEST['by']) ? $_REQUEST['by'] : 'ID';
$sortDirection = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'ASC';

$select = array(
	'ID',
	'CODE',
	'MEASURE_TITLE',
	'SYMBOL_RUS',
	'SYMBOL_INTL',
	'SYMBOL_LETTER_INTL',
	'IS_DEFAULT',
);

$dbResult = CCatalogMeasure::getList(
	$arResult['SORT'],
	array(),
	false,
	$arNavParams,
	$select
);
$dbResult->NavStart($arNavParams['nPageSize'], false);

$arResult['MEASURES'] = array();
while($measure = $dbResult->GetNext())
{
	$ID = intval($measure['~ID']);
	$measure['PATH_TO_MEASURE_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_MEASURE_EDIT'],
			array('measure_id' => $ID)
		);

	$measure['PATH_TO_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_MEASURE_LIST']
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $ID, 'sessid' => bitrix_sessid())
		);
	$arResult['MEASURES'][$ID] = $measure;
}
$arResult['ROWS_COUNT'] = $dbResult->SelectedRowsCount();
$arResult['DB_LIST'] = $dbResult;

$this->IncludeComponentTemplate();