<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load("ui.tooltip");

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

?>
<script type="text/javascript">
function crm_activity_calendar_delete_grid(title, message, btnTitle, path)
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
	foreach($arResult['CAL'] as $sKey =>  $arCal)
	{
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_CALENDAR_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_CALENDAR_SHOW'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCal['PATH_TO_CALENDAR_SHOW'])."');",
			'DEFAULT' => true
		);

/*		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_CALENDAR_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_CALENDAR_EDIT'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCal['PATH_TO_CALENDAR_EDIT'])."');"
		);*/

		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_CALENDAR_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_CALENDAR_DELETE'),
			'ONCLICK' => "crm_activity_calendar_delete_grid('".CUtil::JSEscape(GetMessage('CRM_CALENDAR_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_CALENDAR_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_CALENDAR_DELETE'))."', '".CUtil::JSEscape($arCal['PATH_TO_CALENDAR_DELETE'])."')"
		);

		$arColumns = array(
			'NAME' => '<a target="_self" href="'.$arCal['PATH_TO_CALENDAR_SHOW'].'">'.$arCal['NAME'].'</a>',
			'DT_FROM' => FormatDate('x', MakeTimeStamp($arCal['DT_FROM'])),
			'DT_TO' => !empty($arCal['DT_TO']) ? FormatDate('x', MakeTimeStamp($arCal['DT_TO'])) : '',
			'DESCRIPTION' => htmlspecialcharsback($arCal['DESCRIPTION']),
			'OWNER_ID' => $arCal['~OWNER_ID'] > 0 ?
				'<a href="'.$arCal['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arCal['ID'].'" bx-tooltip-user-id="'.$arCal['~OWNER_ID'].'">'.$arCal['OWNER_ID'].'</a>'
				: ''
		);
		if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
		{
			$arColumns['ENTITY_TYPE'] = !empty($arCal['ENTITY_TYPE'])? GetMessage('CRM_ENTITY_TYPE_'.$arCal['ENTITY_TYPE']): '';
			$arColumns['ENTITY_TITLE'] = !empty($arCal['ENTITY_TITLE'])?
				'<a href="'.$arCal['ENTITY_LINK'].'" bx-tooltip-user-id="'.$arCal['ENTITY_ID'].'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.mb_strtolower($arCal['ENTITY_TYPE']).'.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon'.($arCal['ENTITY_TYPE'] == 'LEAD' || $arCal['ENTITY_TYPE'] == 'DEAL' || $arCal['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.mb_strtolower($arCal['ENTITY_TYPE'])).'">'.$arCal['ENTITY_TITLE'].'</a>'
				: '';
		}
		else
		{
			unset($arCal['ENTITY_TYPE']);
			unset($arCal['ENTITY_TITLE']);
		}
		$arResult['GRID_DATA'][] = array(
			'id' => $arCal['ID'],
			'actions' => $arActions,
			'data' => $arCal,
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