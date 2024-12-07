<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load(["ui.tooltip", "ui.fonts.opensans"]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
?>
<script>
function crm_activity_task_delete_grid(title, message, btnTitle, path)
{
	var d;
	d = new BX.CDialog({
		title: title,
		head: '',
		content: message,
		resizable: false,
		draggable: true,
		height: 70,
		width: 300
	});

	var _BTN = [
		{
			title: btnTitle,
			id: 'crmOk',
			'action': function ()
			{
				window.location.href = path;
				BX.WindowManager.Get().Close();
			}
		},
		BX.CDialog.btnCancel
	];
	d.ClearButtons();
	d.SetButtons(_BTN);
	d.Show();
}
</script>
<?
	for ($i=0, $ic=sizeof($arResult['FILTER']); $i < $ic; $i++)
	{
		if ($arResult['FILTER'][$i]['type'] === 'user')
		{
			$userID = (isset($_REQUEST[$arResult['FILTER'][$i]['id']]))?intval($_REQUEST[$arResult['FILTER'][$i]['id']][0]):0;
			if ($userID === 0 || (isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y'))
			{
				$userID = '';
				$userName = '';
			}
			else
				$userName = __format_user4search($userID);

			ob_start();
			$APPLICATION->IncludeComponent('bitrix:intranet.user.selector', 'minimized', array(
				'INPUT_NAME' => $arResult['FILTER'][$i]['id'],
				'INPUT_NAME_STRING' => $arResult['FILTER'][$i]['id'].'_name',
				'INPUT_VALUE' => $userID,
				'INPUT_VALUE_STRING' => htmlspecialcharsback($userName),
				'EXTERNAL' => 'I',
				'MULTIPLE' => 'N',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
				), $component);
			$val = ob_get_clean();

			$arResult["FILTER"][$i]["type"] = "custom";
			$arResult['FILTER'][$i]['value'] = $val;
		}
	}

	$arResult['GRID_DATA'] = array();
	foreach($arResult['TASK'] as $sKey =>  $arTask)
	{
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_TASK_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_TASK_SHOW'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arTask['PATH_TO_TASK_SHOW'])."');",
			'DEFAULT' => true
		);

		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_TASK_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_TASK_EDIT'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arTask['PATH_TO_TASK_EDIT'])."');"
		);

		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_TASK_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_TASK_DELETE'),
			'ONCLICK' => "crm_activity_task_delete_grid('".CUtil::JSEscape(GetMessage('CRM_TASK_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_TASK_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_TASK_DELETE'))."', '".CUtil::JSEscape($arTask['PATH_TO_TASK_DELETE'])."')"
		);

		$arColumns = array(
			'TITLE' => '<a target="_self" href="'.$arTask['PATH_TO_TASK_SHOW'].'">'.$arTask['TITLE'].'</a>',
			'CREATED_DATE' => FormatDate('x', MakeTimeStamp($arTask['CREATED_DATE'])),
			'CHANGED_DATE' => FormatDate('x', MakeTimeStamp($arTask['CHANGED_DATE'])),
			'DATE_START' => !empty($arTask['DATE_START']) ? FormatDate('x', MakeTimeStamp($arTask['DATE_START'])) : '',
			'CLOSED_DATE' => !empty($arTask['CLOSED_DATE']) ? FormatDate('x', MakeTimeStamp($arTask['CLOSED_DATE'])) : '',
			'REAL_STATUS' => GetMessage('TASKS_STATUS_'.$arTask['REAL_STATUS']),
			'PRIORITY' => GetMessage('TASKS_PRIORITY_'.$arTask['PRIORITY']),
			'RESPONSIBLE_ID' => $arTask['~RESPONSIBLE_ID'] > 0 ?
				'<a href="'.$arTask['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arTask['ID'].'" bx-tooltip-user-id="'.$arTask['~RESPONSIBLE_ID'].'">'.$arTask['RESPONSIBLE_FORMATTED_NAME'].'</a>'
				: ''
		);
		if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
		{
			$arColumns['ENTITY_TYPE'] = !empty($arTask['ENTITY_TYPE'])? GetMessage('CRM_ENTITY_TYPE_'.$arTask['ENTITY_TYPE']): '';
			$arColumns['ENTITY_TITLE'] = !empty($arTask['ENTITY_TITLE'])?
				'<a href="'.$arTask['ENTITY_LINK'].'" bx-tooltip-user-id="'.$arTask['ENTITY_TYPE'].'_'.$arTask['ENTITY_ID'].'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.mb_strtolower($arTask['ENTITY_TYPE']).'.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon'.($arTask['ENTITY_TYPE'] == 'LEAD' || $arTask['ENTITY_TYPE'] == 'DEAL' || $arTask['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.mb_strtolower($arTask['ENTITY_TYPE'])).'">'.$arTask['ENTITY_TITLE'].'</a>'
				: '';
		}
		else
		{
			unset($arTask['ENTITY_TYPE']);
			unset($arTask['ENTITY_TITLE']);
		}
		$arResult['GRID_DATA'][] = array(
			'id' => $arTask['ID'],
			'actions' => $arActions,
			'data' => $arTask,
			'editable' => false,
			'columns' => $arColumns
		);
	}
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);

	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.grid',
		'',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => $arResult['SORT'],
			'SORT_VARS' => $arResult['SORT_VARS'],
			'ROWS' => $arResult['GRID_DATA'],
			'FOOTER' => array(array('title' => GetMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
			'EDITABLE' => 'N',
			'ACTIONS' => array(
				'delete' => true
			),
			'ACTION_ALL_ROWS' => true,
			'NAV_OBJECT' => $arResult['DB_LIST'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => $arResult['TAB_ID'],
			'AJAX_MODE' => $arResult['INTERNAL'] ? 'N' : 'Y',
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
		),
		$component
	);
?>