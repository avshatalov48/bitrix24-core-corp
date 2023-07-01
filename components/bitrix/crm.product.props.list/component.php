<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

/*if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}*/

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/** @global CDatabase $DB */
global $DB;

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PRODUCTPROPS_LIST'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_LIST', $arParams['PATH_TO_PRODUCTPROPS_LIST'], '');
$arParams['PATH_TO_PRODUCTPROPS_ADD'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_ADD', $arParams['PATH_TO_PRODUCTPROPS_ADD'], '?add');
$arParams['PATH_TO_PRODUCTPROPS_EDIT'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_EDIT', $arParams['PATH_TO_PRODUCTPROPS_EDIT'], '?prop_id=#prop_id#&edit');

$arResult['GRID_ID'] = 'CRM_PRODUCTPROPS_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arPropType = array(
	'' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'),
	'S' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_S'),
	'N' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_N'),
	'L' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_L'),
	'F' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_F'),
	/*'G' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_G'),*/
	'E' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_PROP_E'),
);
$arUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'edit');
\Bitrix\Main\Type\Collection::sortByColumn($arUserTypeList, array('DESCRIPTION' => SORT_STRING));
foreach($arUserTypeList as $arUserType)
	$arPropType[$arUserType['PROPERTY_TYPE'].':'.$arUserType['USER_TYPE']] = $arUserType['DESCRIPTION'];

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_ID'), 'sort' => 'id', 'align' => 'right', 'default' => true, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_NAME'), 'sort' => 'name', 'default' => true, 'editable' => true),
	/*array('id' => 'CODE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_CODE'), 'default' => true, 'editable' => true),*/
	array('id' => 'PROPERTY_TYPE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_PROPERTY_TYPE'), 'default' => true, 'editable' => false/*array('items' => $arPropType)*/, 'type' => 'list'),
	array('id' => 'SORT', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_SORT'), 'sort' => 'sort', 'align' => 'right', 'default' => true, 'editable' => true),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_ACTIVE'), 'sort' => 'active', 'align' => 'center', 'default' => true, 'editable' => true, 'type' => 'checkbox'),
	array('id' => 'IS_REQUIRED', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_IS_REQUIRED'), 'align' => 'center', 'default' => true, 'editable' => true, 'type' => 'checkbox'),
	array('id' => 'MULTIPLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_MULTIPLE'), 'align' => 'center', 'default' => true, 'editable' => true, 'type' => 'checkbox'),
	/*array('id' => 'SEARCHABLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_SEARCHABLE'), 'sort' => 'searchable', 'align' => 'center', 'default' => true, 'editable' => true, 'type' => 'checkbox'),*/
	array('id' => 'FILTRABLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_FILTRABLE'), 'sort' => 'filtrable', 'align' => 'center', 'editable' => true, 'type' => 'checkbox'),
	array('id' => 'XML_ID', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_XML_ID'), 'editable' => true)/*,
	array('id' => 'WITH_DESCRIPTION', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_WITH_DESCRIPTION'), 'align' => 'center', 'editable' => true, 'type' => 'checkbox'),*/
	/*array('id' => 'HINT', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_HINT'), 'editable' => true)*/
); 

$arResult['FILTER_PRESETS'] = array();
$arResult['FILTER'] = array(
	array('id' => 'NAME', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_NAME'), 'default' => true),
	/*array('id' => 'CODE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_CODE')),*/
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_ACTIVE'), 'type' => 'list', 'items' => array('' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'), 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
	/*array('id' => 'SEARCHABLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_SEARCHABLE'), 'type' => 'list', 'items' => array('' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'), 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),*/
	array('id' => 'FILTRABLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_FILTRABLE'), 'type' => 'list', 'items' => array('' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'), 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
	array('id' => 'IS_REQUIRED', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_IS_REQUIRED'), 'type' => 'list', 'items' => array('' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'), 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
	array('id' => 'MULTIPLE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_MULTIPLE'), 'type' => 'list', 'items' => array('' => GetMessage('CRM_PRODUCT_PROP_IBLOCK_ALL'), 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
	array('id' => 'XML_ID', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_XML_ID')),
	array('id' => 'PROPERTY_TYPE', 'name' => GetMessage('CRM_PRODUCT_PROP_PL_PROPERTY_TYPE'), 'type' => 'list', 'items' => $arPropType)
);

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arFilter = $CGridOptions->GetFilter($arResult['FILTER']);

$arStrFilters = array('NAME', 'CODE');
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arStrFilters, true))
	{
		$arFilter['?'.$k] = $v;
		unset($arFilter[$k]);
	}
}

$arSort = array();
$by = isset($_GET['by']) ? trim($_GET['by']) : 'ID';
$sort = isset($_GET['order']) ? trim($_GET['order']) : 'asc';
if(isset($_GET['by']) && isset($_GET['order']))
{
	$arSort = array($by => $sort);
}
$gridSorting = $CGridOptions->GetSorting(
	array(
		'sort' => array('sort' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);
if (empty($arSort))
{
	$arSort = $gridSorting['sort'];
}
if (!is_array($arSort))
{
	$arSort = array();
}
if (!isset($arSort['id']))
{
	if (!empty($arSort))
	{
		$arSort['id'] = (array_shift(array_slice($arSort, 0, 1)) === 'desc') ? 'desc' : 'asc';
	}
	else
	{
		$arSort['id'] = 'asc';
	}
}

$arResult['SORT'] = $arSort;
$arResult['SORT_VARS'] = $gridSorting['vars'];

$arOrder = $arSort;
$arFilter['IBLOCK_ID'] = intval(CCrmCatalog::EnsureDefaultExists());
$arFilter['CHECK_PERMISSIONS'] = 'N';
$arFilter['!PROPERTY_TYPE'] = 'G';

$errorMsg = '';

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = mb_strtolower($_GET['error']);
	if(preg_match('/^crm_err_/', $errorID) === 1)
	{
		if(!isset($_SESSION[$errorID]))
			LocalRedirect($APPLICATION->GetCurPage());

		$errorMsg = strval($_SESSION[$errorID]);
		unset($_SESSION[$errorID]);
		if($errorMsg !== '')
		{
			ShowError($errorMsg);
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if ($arResult['CAN_EDIT'] && $action === 'edit')
	{
		foreach($_POST['FIELDS'] as $id => $arFields)
		{
			$DB->StartTransaction();

			$id = intval($id);

			$propertyType = '';
			$res = CIBlockProperty::GetByID($id);
			if ($row = $res->Fetch())
			{
				if (isset($row['PROPERTY_TYPE']))
					$propertyType .= $row['PROPERTY_TYPE'];
				if (isset($row['USER_TYPE']))
					$propertyType .= ':'.$row['USER_TYPE'];
			}
			unset($res, $row);

			$arFields["USER_TYPE"] = "";
			if(mb_strpos($arFields["PROPERTY_TYPE"], ":"))
			{
				list($arFields["PROPERTY_TYPE"], $arFields["USER_TYPE"]) = explode(':', $arFields["PROPERTY_TYPE"], 2);
			}

			$allowedFields = array(
				//'ID',
				'ACTIVE',
				'CODE',
				'FILTRABLE',
				'HINT',
				'IS_REQUIRED',
				'MULTIPLE',
				'NAME',
				//'PROPERTY_TYPE',
				'SEARCHABLE',
				'SORT',
				//'USER_TYPE',
				'WITH_DESCRIPTION',
				'XML_ID'
			);
			$freshFields = array();
			foreach($allowedFields as $fieldName)
			{
				if ($propertyType === 'S:map_yandex' && $fieldName === 'MULTIPLE' && $arFields[$fieldName] !== 'N')
					continue;

				if (isset($arFields[$fieldName]))
					$freshFields[$fieldName] = $arFields[$fieldName];
			}

			$ibp = new CIBlockProperty;

			if(!$ibp->Update($id, $freshFields))
			{
				$errorMsg .= GetMessage("CRM_PRODUCT_PROP_PL_SAVE_ERROR", array("#ID#" => $id, "#ERROR_TEXT#" => $ibp->LAST_ERROR)).'<br>';
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
	}
	else if($arResult['CAN_DELETE'] && in_array($action, array('delete','activate','deactivate'), true))
	{
		$allSelected = ($_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y');
		$arID = !$allSelected ? $_POST['ID'] : array();

		if($allSelected)
		{
			$dbRes = CIBlockProperty::GetList($arOrder, $arFilter);
			while($arRow = $dbRes->Fetch())
				$arID[] = $arRow['ID'];
		}

		foreach($arID as $id)
		{
			if($id == '')
				continue;

			switch($action)
			{
				case "delete":
					if(!CIBlockProperty::Delete($id))
						$errorMsg .= GetMessage("CRM_PRODUCT_PROP_PL_DELETE_ERROR", array("#ID#"=>$id)).'<br>';
					break;
				case "activate":
				case "deactivate":
					$ibp = new CIBlockProperty();
					$arFields = array(
						"ACTIVE" => ($action=="activate"? "Y": "N"),
					);
					if(!$ibp->Update($id, $arFields))
						$errorMsg .= GetMessage("CRM_PRODUCT_PROP_PL_SAVE_ERROR", array("#ID#"=>$id, "#ERROR_TEXT#"=>$ibp->LAST_ERROR)).'<br>';
					break;
			}
		}
	}

	if (!empty($errorMsg))
	{
		$errorID = uniqid('crm_err_');
		$_SESSION[$errorID] = $errorMsg;

		LocalRedirect(CHTTP::urlAddParams($APPLICATION->GetCurPage(), array('error' => $errorID)));
	}
	else
	{
		if(!isset($_POST['AJAX_CALL']))
		{
			LocalRedirect($APPLICATION->GetCurPage());
		}
	}

	unset($_GET['ID'], $_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
}
elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && check_bitrix_sessid() && isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
	{
		$id = isset($_GET['ID']) ? $_GET['ID'] : 0;
		if($id > 0)
		{
			if(!CIBlockProperty::Delete($id))
				$errorMsg .= GetMessage("CRM_PRODUCT_PROP_PL_DELETE_ERROR", array("#ID#"=>$id)).'<br>';
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!empty($errorMsg))
	{
		$errorID = uniqid('crm_err_');
		$_SESSION[$errorID] = $errorMsg;

		LocalRedirect(CHTTP::urlAddParams($APPLICATION->GetCurPage(), array('error' => $errorID)));
	}
	else
	{
		if(!isset($_POST['AJAX_CALL']))
		{
			LocalRedirect($APPLICATION->GetCurPage());
		}
	}

	unset($_GET['ID'], $_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
}

$dbRes = CIBlockProperty::GetList($arOrder, $arFilter);
$dbRes->NavStart(20);
$dbRes->bShowAll = false;
$arProp = array();
$arResult['PROPS'] = array();
while($arRow = $dbRes->Fetch())
{
	if (isset($arRow['USER_TYPE']) && !empty($arRow['USER_TYPE'])
		&& !array_key_exists($arRow['USER_TYPE'], $arUserTypeList))
		continue;

	$arProp = array();

	if($arRow['USER_TYPE'])
		$arRow['PROPERTY_TYPE'] .= ':'.$arRow['USER_TYPE'];

	$arProp['~ID'] = $arProp['ID'] = $arRow['ID'];
	$arProp['~NAME'] = $arRow['NAME'];
	$arProp['NAME'] = htmlspecialcharsbx($arProp['~NAME']);
	$arProp['~CODE'] = $arRow['CODE'];
	$arProp['CODE'] = htmlspecialcharsbx($arProp['~CODE']);
	$arProp['~SORT'] = $arRow['SORT'];
	$arProp['SORT'] = htmlspecialcharsbx($arProp['~SORT']);
	$arProp['~ACTIVE'] = $arProp['ACTIVE'] = $arRow['ACTIVE'];
	$arProp['~MULTIPLE'] = $arProp['MULTIPLE'] = $arRow['MULTIPLE'];
	$arProp['~XML_ID'] = $arRow['XML_ID'];
	$arProp['XML_ID'] = htmlspecialcharsbx($arProp['~XML_ID']);
	$arProp['~WITH_DESCRIPTION'] = $arProp['WITH_DESCRIPTION'] = $arRow['WITH_DESCRIPTION'];
	$arProp['~SEARCHABLE'] = $arProp['SEARCHABLE'] = $arRow['SEARCHABLE'];
	$arProp['~FILTRABLE'] = $arProp['FILTRABLE'] = $arRow['FILTRABLE'];
	$arProp['~IS_REQUIRED'] = $arProp['IS_REQUIRED'] = $arRow['IS_REQUIRED'];
	$arProp['~HINT'] = $arRow['HINT'];
	$arProp['HINT'] = htmlspecialcharsbx($arProp['~HINT']);
	$arProp['~PROPERTY_TYPE'] = $arRow['PROPERTY_TYPE'];
	$arProp['PROPERTY_TYPE'] = htmlspecialcharsbx($arPropType[$arProp['~PROPERTY_TYPE']]);

	$arProp['PATH_TO_PRODUCTPROPS_EDIT'] =
		CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_PRODUCTPROPS_EDIT'],
			array('prop_id' => $arProp['ID'])
		);
	$arProp['PATH_TO_PRODUCTPROPS_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCTPROPS_LIST'],
				array('loc_id' => $arProp['ID'])
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arProp['ID'], 'sessid' => bitrix_sessid())
		);

	$arResult['PROPS'][$arProp['ID']] = $arProp;
}
unset($arProp, $arRow);

$navComponentObject = null;

$arResult['ROWS_COUNT'] = $dbRes->SelectedRowsCount();
$arResult['NAV_RESULT'] = $dbRes;

$this->IncludeComponentTemplate();
?>