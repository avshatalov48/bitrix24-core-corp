<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ENTITY_ID'] = isset($_REQUEST['entity_id']) ? $_REQUEST['entity_id']: $arParams['FIELS_ENTITY_ID'];

global $USER_FIELD_MANAGER;

$CCrmFields = new CCrmFields($USER_FIELD_MANAGER, $arResult['ENTITY_ID']);
if ($CCrmFields->CheckError())
{
	$ex = $APPLICATION->GetException();
	ShowError($ex->GetString());
	return;
}

$arEntityIds = CCrmFields::GetEntityTypes();
$arResult['ENTITY_NAME'] = $arEntityIds[$arResult['ENTITY_ID']]['NAME'];

$arResult['GRID_ID'] = 'fields_list';

$arResult['~ENTITY_LIST_URL'] = $arParams['~ENTITY_LIST_URL'];
$arResult['ENTITY_LIST_URL'] = htmlspecialcharsbx($arResult['~ENTITY_LIST_URL']);

$arResult['~FIELDS_LIST_URL'] = str_replace('#entity_id#', $arResult['ENTITY_ID'], $arParams['~FIELDS_LIST_URL']);
$arResult['FIELDS_LIST_URL'] = htmlspecialcharsbx($arResult['~FIELDS_LIST_URL']);

$arResult['~FIELD_EDIT_URL'] = str_replace(	array('#entity_id#', '#field_id#'),	array($arResult['ENTITY_ID'], '0'),	$arParams['~FIELD_EDIT_URL']);
$arResult['FIELD_EDIT_URL'] = htmlspecialcharsbx($arResult['~FIELD_EDIT_URL']);

$APPLICATION->SetTitle(GetMessage('CRM_FIELDS_LIST_TITLE_EDIT', array('#NAME#' => $arResult['ENTITY_NAME'])));

global $CACHE_MANAGER;

//Form submitted
if(
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& check_bitrix_sessid()
	&& isset($_POST['action_button_'.$arResult['GRID_ID']])
)
{


	if($_POST['action_button_'.$arResult['GRID_ID']] == 'delete' && isset($_POST['ID']) && is_array($_POST['ID']))
	{
		foreach($_POST['ID'] as $ID)
			$CCrmFields->DeleteField($ID);

		//Clear components cache
		$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$arResult['ENTITY_ID']);
	}

	if($_POST['action_button_'.$arResult['GRID_ID']] == 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		$gridID = CCrmGridOptions::GetDefaultGrigID(
			CCrmOwnerType::ResolveIDByUFEntityID($arResult['ENTITY_ID'])
		);

		foreach($_POST['FIELDS'] as $ID => &$arPostField)
		{
			$arPresetField = $CCrmFields->GetByID($ID);
			$arField = array();
			//Sanitaizing
			foreach($arPostField as $k => $v)
			{
				if(strpos($k, '~') === 0)
				{
					continue;
				}

				$arField[$k] = $v;
			}

			if(empty($arField))
			{
				continue;
			}

			if(!$CCrmFields->UpdateField($ID, $arField))
			{
				continue;
			}

			if(isset($arField['SHOW_IN_LIST']) && $arField['SHOW_IN_LIST'] !== $arPresetField['SHOW_IN_LIST'])
			{
				if($arField['SHOW_IN_LIST'] === 'Y')
				{
					CCrmGridOptions::AddVisibleColumn($gridID, $arPresetField['FIELD_NAME']);
				}
				else
				{
					CCrmGridOptions::RemoveVisibleColumn($gridID, $arPresetField['FIELD_NAME']);
				}
			}
		}
		unset($arPostField);

		//Clear components cache
		$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$arResult['ENTITY_ID']);
	}

	if($_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y' && $_POST['action_button_'.$arResult['GRID_ID']] == 'delete')
	{
		$arFields = $CCrmFields->GetFields();
		foreach($arFields as $ID => $arField)
			$CCrmFields->DeleteField($arField['ID']);

		//Clear components cache
		$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$arResult['ENTITY_ID']);
	}

	if(!isset($_POST['AJAX_CALL']))
		LocalRedirect($arResult['FIELDS_LIST_URL']);
}

if($this->StartResultCache(true))
{
	$CACHE_MANAGER->StartTagCache($this->GetCachePath());
	$CACHE_MANAGER->RegisterTag('crm_fields_list_'.$arResult['ENTITY_ID']);

	$arFields = $CCrmFields->GetFields();

	$arResult['ROWS'] = array();
	foreach($arFields as $ID => $arField)
	{
		$data = array();
		foreach($arField as $key => $value)
		{
			$data['~'.$key] = $value;
			if(is_array($value))
			{
				foreach($value as $key1=>$value1)
					if(!is_array($value1))
						$value[$key1] = htmlspecialcharsbx($value1);
				$data[$key] = $value;
			}
			else
			{
				$data[$key] = htmlspecialcharsbx($value);
			}
		}
		$data['~FIELD_EDIT_URL'] = str_replace(
			array('#entity_id#', '#field_id#'),
			array($arResult['ENTITY_ID'], $ID),
			$arParams['~FIELD_EDIT_URL']
		);

		$data['FIELD_EDIT_URL'] = htmlspecialcharsbx($data['~FIELD_EDIT_URL']);
		$aCols = array(
			'TYPE' => $data['USER_TYPE']['DESCRIPTION'],
			'MANDATORY' => $data['USER_TYPE']['DESCRIPTION'],
			'LIST_COLUMN_LABEL' => '<a target="_self" href="'.$data["FIELD_EDIT_URL"].'">'.$data["LIST_COLUMN_LABEL"].'</a>'
		);

		$aActions = array(
			array(
				'ICONCLASS' => 'edit',
				'TEXT' => GetMessage('CRM_FIELDS_LIST_ACTION_MENU_EDIT'),
				'ONCLICK' => "jsUtils.Redirect(arguments, '".CUtil::JSEscape($data["~FIELD_EDIT_URL"])."')",
				'DEFAULT' => true,
			),
		);

		$aActions[] = array('SEPARATOR' => true);
		$aActions[] = array(
			'ICONCLASS' => 'delete',
			'TEXT' => GetMessage('CRM_FIELDS_LIST_ACTION_MENU_DELETE'),
			'ONCLICK' => "bxGrid_".$arResult["GRID_ID"].".DeleteItem('".$arField["ID"]."', '".GetMessage("CRM_FIELDS_LIST_ACTION_MENU_DELETE_CONF")."')",
		);

		$aEditable = array();

		$arResult['ROWS'][] = array('id' => $arField['ID'], 'data'=>$data, 'actions'=>$aActions, 'columns'=>$aCols, 'editable'=>$aEditable);
	}

	$CACHE_MANAGER->EndTagCache();
	$this->EndResultCache();
}

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_FIELDS_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
$APPLICATION->AddChainItem($arResult['ENTITY_NAME'], $arResult['~FIELDS_LIST_URL']);
?>
