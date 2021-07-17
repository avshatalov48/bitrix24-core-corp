<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/requisite.js');

$isInternal = $arResult['INTERNAL'];

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'REQUISITE',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
$requisiteGridEditorId = 'RequisiteGridEditor_'.$arResult['GRID_ID'];

?>
<script type="text/javascript">
function crm_requisite_delete_grid(id, message)
{
	var f = BX.delegate(<?='bxGrid_'.$arResult['GRID_ID']?>.DeleteItem, <?='bxGrid_'.$arResult['GRID_ID']?>);
	f(id, message);
}
BX.ready(
	function()
	{
		if (BX('actallrows_<?=$arResult['GRID_ID']?>')) {
			BX.bind(BX('actallrows_<?=$arResult['GRID_ID']?>'), 'click', function () {
				var el_t = BX.findParent(this, {tagName : 'table'});
				var el_s = BX.findChild(el_t, {tagName : 'select'}, true, false);
				for (i = 0; i < el_s.options.length; i++)
				{
					if (el_s.options[i].value == 'tasks' || el_s.options[i].value == 'calendar')
						el_s.options[i].disabled = this.checked;
				}
				if (this.checked && (el_s.options[el_s.selectedIndex].value == 'tasks' || el_s.options[el_s.selectedIndex].value == 'calendar'))
					el_s.selectedIndex = 0;
			});
		}
	}
);
</script>
<?
$data = array();
$requisite = new \Bitrix\Crm\EntityRequisite();
foreach ($arResult['LIST_DATA'] as $sKey => $arRequisite)
{
	$row = array();
	foreach ($arRequisite as $fName => $fValue)
	{
		if(is_array($fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		elseif(preg_match("/[;&<>\"]/", $fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		else
			$row[$fName] = $fValue;
		$row['~'.$fName] = $fValue;
	}
	$data[$sKey] = &$row;
	unset($row);
}
unset($requisite, $fName, $fValue);

$arResult['GRID_DATA'] = array();
$isEditable = $arResult['PERMS']['WRITE'];
foreach($data as $sKey =>  $arRequisite)
{
	$arActions = array();
	$nameContent = $arRequisite['NAME'];
	$editUrl = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_REQUISITE_EDIT'],
		array('id' => $arRequisite['ID'])
	);
	$onClickJSEdit = 'BX.Crm["'.CUtil::JSEscape($requisiteGridEditorId).'"].onRequisiteEdit('.
		CUtil::JSEscape($arResult['ENTITY_TYPE_ID']).", ".
		CUtil::JSEscape($arResult['ENTITY_ID']).", ".
		'0, '.
		$arRequisite['ID'].', '.
		'"", '.
		'"", '.
		'-1, '.
		'false, '.
		($isEditable ? 'false' : 'true').');';
	$nameContent = '<a href="'.$editUrl.'" onclick="'.htmlspecialcharsbx($onClickJSEdit.' return BX.PreventDefault(event);').'">'.$nameContent.'</a>';
	if ($arResult['PERMS']['WRITE'])
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_REQUISITE_EDIT'],
				array('id' => $arRequisite['ID'])
			),
			array('copy' => 1)
		);
		if (!empty($arResult['BACK_URL']))
		{
			$editUrl = CHTTP::urlAddParams($editUrl, array('back_url' => urlencode($arResult['BACK_URL'])));
			$copyUrl = CHTTP::urlAddParams($copyUrl, array('back_url' => urlencode($arResult['BACK_URL'])));
		}

		$onClickJSCopy = 'BX.Crm["'.CUtil::JSEscape($requisiteGridEditorId).'"].onRequisiteEdit('.
			CUtil::JSEscape($arResult['ENTITY_TYPE_ID']).", ".
			CUtil::JSEscape($arResult['ENTITY_ID']).", ".
			'0, '.
			$arRequisite['ID'].', '.
			'"", '.
			'"", '.
			'-1, '.
			'true, '.
			($isEditable ? 'false' : 'true').');';

		$arActions[] = array(
			'ICONCLASS' => 'edit',
			'TITLE' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ?
				GetMessage('CRM_REQUISITE_EDIT_TITLE_COMPANY') : GetMessage('CRM_REQUISITE_EDIT_TITLE_CONTACT'),
			'TEXT' => GetMessage('CRM_REQUISITE_EDIT'),
			'ONCLICK' => $onClickJSEdit
		);
		$arActions[] = array(
			'ICONCLASS' => 'copy',
			'TITLE' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ?
				GetMessage('CRM_REQUISITE_COPY_TITLE_COMPANY') : GetMessage('CRM_REQUISITE_COPY_TITLE_CONTACT'),
			'TEXT' => GetMessage('CRM_REQUISITE_COPY'),
			'ONCLICK' => $onClickJSCopy
		);
	}

	if ($arResult['PERMS']['DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] = array(
			'ICONCLASS' => 'delete',
			'TITLE' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ?
				GetMessage('CRM_REQUISITE_DELETE_TITLE_COMPANY') : GetMessage('CRM_REQUISITE_DELETE_TITLE_CONTACT'),
			'TEXT' => GetMessage('CRM_REQUISITE_DELETE'),
			'ONCLICK' => 'crm_requisite_delete_grid("'.CUtil::JSEscape($arRequisite['ID']).'", "'.
				CUtil::JSEscape(GetMessage('CRM_REQUISITE_DELETE_CONFIRM')).'");'
		);
	}

	$resultItem = array(
		'id' => $arRequisite['ID'],
		'actions' => $arActions,
		'data' => $arRequisite,
		'editable' => $arResult['PERMS']['WRITE'] ? 'Y' : 'N',
		'columns' => array(
			'NAME' => $nameContent,
			'CREATED_BY_ID' => intval($arRequisite['CREATED_BY_ID']) > 0 ?
				'<a href="'.CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_USER_PROFILE'],
					array('user_id' => $arRequisite['CREATED_BY_ID'])
				).'" id="balloon_'.$arResult['FORM_ID'].'_'.$arResult['COMPONENT_ID'].'_CREATED_BY_ID_'.$arRequisite['ID'].'" bx-tooltip-user-id="'.$arRequisite['CREATED_BY_ID'].'">'.
				CCrmViewHelper::GetFormattedUserName(
					$arRequisite['CREATED_BY_ID'],
					$arParams['NAME_TEMPLATE'],
					true
				).'</a>'
				: '',
			'MODIFY_BY_ID' => intval($arRequisite['MODIFY_BY_ID']) > 0 ?
				'<a href="'.CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_USER_PROFILE'],
					array('user_id' => $arRequisite['MODIFY_BY_ID'])
				).'" id="balloon_'.$arResult['FORM_ID'].'_'.$arResult['COMPONENT_ID'].'_MODIFY_BY_ID_'.$arRequisite['ID'].'" bx-tooltip-user-id="'.$arRequisite['MODIFY_BY_ID'].'">'.
				CCrmViewHelper::GetFormattedUserName(
					$arRequisite['MODIFY_BY_ID'],
					$arParams['NAME_TEMPLATE'],
					true
				).'</a>'
				: ''/*,
			'ACTIVE' => $arRequisite['ACTIVE'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')*/
		)/* + $arResult['LIST_UF_DATA'][$sKey]*/
	);

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$actionHtml = '';
$arActionList = array();

$popupTitle = '';
switch ($arResult['ENTITY_TYPE_ID'])
{
	case CCrmOwnerType::Contact:
		$popupTitle = GetMessage('CRM_REQUISITE_POPUP_TITLE_CONTACT');
		break;
	case CCrmOwnerType::Company:
		$popupTitle = GetMessage('CRM_REQUISITE_POPUP_TITLE_COMPANY');
		break;
}

if($arResult['ENABLE_TOOLBAR'])
{
	if ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY')
	{
		$requisitePresetSelectorTitle = GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TITLE_COMPANY');
		$errPresetNotSelected = GetMessage('CRM_REQUISITE_POPUP_ERR_PRESET_NOT_SELECTED_COMPANY');
	}
	else
	{
		$requisitePresetSelectorTitle = GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TITLE_CONTACT');
		$errPresetNotSelected = GetMessage('CRM_REQUISITE_POPUP_ERR_PRESET_NOT_SELECTED_CONTACT');
	}

	$toolbarButtons = array();
	if ($isEditable)
	{
		$toolbarButtons[] = array(
			'TYPE' => 'crm-requisite-preset-selector',
			'PARAMS' => array(
				'GRID_ID' => $arResult['GRID_ID'],
				'REQUISITE_ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
				'REQUISITE_ENTITY_ID' => $arResult['ENTITY_ID'],
				'PRESET_LIST' => $arResult['PRESET_LIST'],
				'PRESET_LAST_SELECTED_ID' => $arResult['PRESET_LAST_SELECTED_ID'],
				'REQUISITE_DATA_LIST' => array(),
				'MESSAGES' => array(
					'CRM_JS_STATUS_ACTION_SUCCESS' => GetMessage('CRM_JS_STATUS_ACTION_SUCCESS'),
					'CRM_JS_STATUS_ACTION_ERROR' => GetMessage('CRM_JS_STATUS_ACTION_ERROR'),
					'CRM_REQUISITE_PRESET_SELECTOR_TITLE' => $requisitePresetSelectorTitle,
					'CRM_REQUISITE_PRESET_SELECTOR_TEXT' => GetMessage('CRM_REQUISITE_PRESET_SELECTOR_TEXT'),
					'POPUP_TITLE' => $popupTitle,
					'POPUP_SAVE_BUTTON_TITLE' => GetMessage('CRM_REQUISITE_POPUP_SAVE_BUTTON_TITLE'),
					'POPUP_CANCEL_BUTTON_TITLE' => GetMessage('CRM_REQUISITE_POPUP_CANCEL_BUTTON_TITLE'),
					'ERR_PRESET_NOT_SELECTED' => $errPresetNotSelected
				)
			)
		);
	}
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => $toolbarButtons
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	unset($toolbarButtons);
}

//region Navigation
$navigationHtml = '';
if(isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION']))
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.pagenavigation',
		'',
		isset($arResult['PAGINATION']) ? $arResult['PAGINATION'] : array(),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	$navigationHtml = ob_get_contents();
	ob_end_clean();
}
//endregion

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' => array(
			array(
				'type' => 'row_count',
				'title' => GetMessage('CRM_ALL'),
				'show_row_count' => GetMessage('CRM_SHOW_ROW_COUNT'),
				'service_url' => '/bitrix/components/bitrix/crm.requisite.list/list.ajax.php?'.bitrix_sessid_get()
			),
			array('custom_html' => '<td>'.$navigationHtml.'</td>')
		),
		'EDITABLE' =>  $isEditable ? 'Y' : 'N',
		'ACTIONS' => array(
			'delete' => $arResult['PERMS']['DELETE'],
			'custom_html' => $actionHtml,
			'list' => $arActionList
		),
		'ACTION_ALL_ROWS' => true,
		'NAV_OBJECT' => $arResult['DB_LIST'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => null/*$arResult['FILTER']*/,
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'MANAGER' => array(
			'ID' => $gridManagerID,
			'CONFIG' => $gridManagerCfg
		)
	),
	$component
);

?>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	BX.Crm["<?= CUtil::JSEscape($requisiteGridEditorId) ?>"] = new BX.Crm.RequisiteGridEditorClass({
		gridId: "<?= CUtil::JSEscape($arResult['GRID_ID']) ?>",
		requisiteEntityTypeId: <?= CUtil::PhpToJSObject($arResult['ENTITY_TYPE_ID']) ?>,
		requisiteEntityId: <?= CUtil::PhpToJSObject($arResult['ENTITY_ID']) ?>,
		readOnlyMode: <? echo $isEditable ? 'false' : 'true'; ?>,
		requisitePopupAjaxUrl: "/bitrix/components/bitrix/crm.requisite.edit/popup.ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
		requisiteAjaxUrl: "/bitrix/components/bitrix/crm.requisite.edit/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
		messages: {
			"popupTitle": "<?= $popupTitle ?>",
			"popupSaveBtnTitle": "<?= GetMessageJS('CRM_REQUISITE_POPUP_SAVE_BUTTON_TITLE') ?>",
			"popupCancelBtnTitle": "<?= GetMessageJS('CRM_REQUISITE_POPUP_CANCEL_BUTTON_TITLE') ?>"
		}
	});
</script>