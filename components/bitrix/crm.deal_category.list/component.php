<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Entry\DeleteException;
use Bitrix\Crm\Entry\UpdateException;

$arResult['CAN_EDIT'] = $arResult['CAN_DELETE'] = true;

global $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], $curPageUrl);
$arParams['PATH_TO_STATUS_EDIT'] = SITE_DIR.'crm/configs/status/?ACTIVE_TAB=status_tab_#status_id#';

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);



$userID = CCrmPerms::GetCurrentUserID();
$arResult['USER_ID'] = $userID;

$arResult['GRID_ID'] = 'CRM_DEAL_CATEGORY_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : 'N';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_DEAL_CATEGORY_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_DEAL_CATEGORY_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true, 'params' => array('size' => 60)),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_DEAL_CATEGORY_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
	array('id' => 'CREATED_DATE', 'name' => GetMessage('CRM_COLUMN_DEAL_CATEGORY_CREATED_DATE'), 'sort' => 'CREATED_DATE', 'default' => false, 'editable' => false),
	//array('id' => 'LAST_UPDATED', 'name' => GetMessage('CRM_COLUMN_DEAL_CATEGORY_LAST_UPDATED'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false)
);

if(check_bitrix_sessid())
{
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_button_'.$arResult['GRID_ID']]))
	{
		$errors = array();
		$action = $_POST['action_button_'.$arResult['GRID_ID']];
		if($arResult['CAN_DELETE'] && $action === 'delete')
		{
			if($_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y')
			{
				//Delete items
				foreach(DealCategory::getAll(false) as $entry)
				{
					try
					{
						DealCategory::delete($entry['ID']);
					}
					catch(DeleteException $ex)
					{
						$errors[] = $ex->getLocalizedMessage();
					}
				}
			}
			else
			{
				$IDs = isset($_POST['ID']) ? $_POST['ID'] : array();
				foreach($IDs as $ID)
				{
					if($ID <= 0)
					{
						continue;
					}

					try
					{
						DealCategory::delete($ID);
					}
					catch(DeleteException $ex)
					{
						$errors[] = $ex->getLocalizedMessage();
					}
				}
			}
			unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}
		elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
		{
			foreach($_POST['FIELDS'] as $ID => &$data)
			{
				if($ID === 0)
				{
					if(isset($data['NAME']))
					{
						Bitrix\Crm\Category\DealCategory::setDefaultCategoryName(trim($data['NAME']));
					}
					continue;
				}

				if(!DealCategory::exists($ID))
				{
					continue;
				}

				$fields = array();
				//NAME, SORT
				if(isset($data['NAME']))
				{
					$name = trim($data['NAME']);
					if($name !== '')
					{
						$fields['NAME'] = $name;
					}
				}

				if(isset($data['SORT']))
				{
					$sort = (int)$data['SORT'];
					if($sort >= 0)
					{
						$fields['SORT'] = $sort;
					}
				}

				if (!empty($fields))
				{
					try
					{
						DealCategory::update($ID, $fields);
					}
					catch(UpdateException $ex)
					{
						$errors[] = $ex->getLocalizedMessage();
					}
				}
			}
		}

		if(!isset($_POST['AJAX_CALL']))
		{
			if(!empty($errors))
			{
				ShowError(implode("\r\n", $errors));
			}
			else
			{
				$_SESSION['DEAL_CATEGORY_LIST_ERROR'] = implode("\r\n", $errors);
				LocalRedirect($APPLICATION->GetCurPage());
			}
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$arResult['GRID_ID']]))
	{
		$errors = array();
		if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
		{
			$ID = isset($_GET['ID']) ? (int)$_GET['ID'] : 0;
			if($ID > 0)
			{
				try
				{
					DealCategory::delete($ID);
				}
				catch(DeleteException $ex)
				{
					$errors[] = $ex->getLocalizedMessage();
				}
			}
			unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if(!isset($_GET['AJAX_CALL']))
		{
			if(!empty($errors))
			{
				$_SESSION['DEAL_CATEGORY_LIST_ERROR'] = implode("\r\n", $errors);
			}
			LocalRedirect($arParams['PATH_TO_DEAL_CATEGORY_LIST']);
		}
		elseif(!empty($errors))
		{
			ShowError(implode("\r\n", $errors));
		}
	}
}

if(isset($_SESSION['DEAL_CATEGORY_LIST_ERROR']))
{
	ShowError($_SESSION['DEAL_CATEGORY_LIST_ERROR']);
	unset($_SESSION['DEAL_CATEGORY_LIST_ERROR']);
}

if(isset($_GET['open_edit']))
{
	$arResult['OPEN_EDIT'] = (int)$_GET['open_edit'];
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('SORT' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);
$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

if(isset($sort['CREATED_DATE']))
{
	$sort['ID'] = $sort['CREATED_DATE'];
	unset($sort['CREATED_DATE']);
}

$items = DealCategory::getAll(true, $sort);
$arResult['ROWS_COUNT'] = count($items);
for($i = 0; $i < $arResult['ROWS_COUNT']; $i++)
{
	$ID = (int)$items[$i]['ID'];
	if($ID > 0)
	{
		$items[$i]['CAN_EDIT'] = $items[$i]['CAN_DELETE'] = true;
	}
	else
	{
		//Is default category
		$items[$i]['IS_DEFAULT'] = true;

		$items[$i]['CAN_EDIT'] = true;
		$items[$i]['CAN_DELETE'] = false;
	}

	$items[$i]['PATH_TO_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_CATEGORY_LIST'],
				array('category_id' => $ID)
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $ID, 'sessid' => bitrix_sessid())
		);

	$items[$i]['PATH_TO_STATUS_EDIT'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_STATUS_EDIT'],
		array('status_id' => DealCategory::getStatusEntityID($ID))
	);

	//HACK for interface grid inline editor
	$items[$i]['~NAME'] = $items[$i]['NAME'];
	$items[$i]['NAME'] = htmlspecialcharsbx($items[$i]['NAME']);
	$items[$i]['~SORT'] = $items[$i]['SORT'];
}
$arResult['ITEMS'] = &$items;

$this->IncludeComponentTemplate();
