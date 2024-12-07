<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var array $arParams */
/** @var array $arResult */
/** @var \Bitrix\Crm\PresetListComponent $component */
/** @global CMain $APPLICATION */
global $APPLICATION;

$presetFieldListManagerId = 'PresetFieldListManager_'.$arResult['COMPONENT_ID'];
$presetFieldAddFormId = 'PresetFieldListManager_'.$arResult['COMPONENT_ID'].'_FormFieldAdd';

$toolbarButtons = array(
	array(
		'TEXT'=>GetMessage('CRM_PRESET_TOOLBAR_LIST'),
		'TITLE'=>GetMessage('CRM_PRESET_TOOLBAR_LIST_TITLE',
			array('#NAME#' => htmlspecialcharsbx($arResult['ENTITY_TYPE_NAME']))),
		'LINK'=>str_replace(
			array('#entity_type#'),
			array($arResult['ENTITY_TYPE_ID']),
			$arResult['PRESET_LIST_URL']
		),
		'ICON'=>'btn-view-elements'
	)
);

$toolbarButtons[] = array(
	'TEXT' => GetMessage('CRM_PRESET_TOOLBAR_FIELD_ADD'),
	'TITLE' => GetMessage('CRM_PRESET_TOOLBAR_FIELD_ADD_TITLE'),
	'LINK' => htmlspecialcharsbx('javascript:BX.Crm["'.CUtil::JSEscape($presetFieldListManagerId).'"].addField();'),
	'ICON' => 'btn-add-field'
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.toolbar',
	'',
	array(
		'BUTTONS' => $toolbarButtons
	),
	$component, array('HIDE_ICONS' => 'Y')
);

$rows = array();
foreach($arResult['LIST_DATA'] as $index => $listRow)
{
	$row = array();
	foreach ($listRow as $fName => $fValue)
	{
		if(is_array($fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		elseif(preg_match("/[;&<>\"]/", $fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		else
			$row[$fName] = $fValue;
		$row["~".$fName] = $fValue;
	}
	$gridDataItem = array(
		'id' => $index,
		'data' => $row,
		'actions' => array(
			array(
				'ICONCLASS' => 'edit',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_EDIT'),
				'ONCLICK' => 'javascript:BX.Crm["'.CUtil::JSEscape($presetFieldListManagerId).'"].editField("'.$index.'");'
			),
			array(
				'ICONCLASS' => 'delete',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_DELETE'),
				'ONCLICK' => "javascript:bxGrid_".$arResult['GRID_ID'].".DeleteItem('".$index."', '".GetMessage("CRM_PRESET_LIST_ACTION_MENU_DELETE_CONF")."')"
			)
		),
		'columns' => array(
			'FIELD_ETITLE' => '<span style="color: #2067b0; cursor: pointer;" onclick="'.htmlspecialcharsbx('javascript:BX.Crm["'.CUtil::JSEscape($presetFieldListManagerId).'"].editField("'.$index.'");').'">'.htmlspecialcharsbx($listRow['FIELD_ETITLE']).'</span>'
		),
		'editable' => array()
	);
	$rows[] = $gridDataItem;
	unset($gridDataItem);
}
unset($listRow);

$params = [
	'presetFieldListManagerId' => $presetFieldListManagerId,
	'componentId' => $arResult['COMPONENT_ID'],
	'formId' => $presetFieldAddFormId,
	'entityFieldsForSelect' => $arResult['ENTITY_FIELDS_FOR_SELECT'],
	'fieldData' => $arResult['LIST_DATA'],
	'userFieldEntityId' => (isset($arResult['USER_FIELD_ENTITY_ID']) ? $arResult['USER_FIELD_ENTITY_ID'] : ''),
	'userFieldServiceUrl' => ('/bitrix/components/bitrix/crm.requisite.edit/uf.ajax.php?siteID='.SITE_ID.
		'&'.bitrix_sessid_get()),
	'messages' => [
		'fieldAddDialogTitle' => GetMessage('CRM_PRESET_TOOLBAR_FIELD_ADD'),
		'fieldEditDialogTitle' => GetMessage('CRM_PRESET_TOOLBAR_FIELD_EDIT'),
		'fieldNameFieldTitle' => GetMessage('CRM_PRESET_FIELD_FIELD_NAME_TITLE'),
		'fieldNameSelectFieldTitle' => GetMessage('CRM_PRESET_FIELD_FIELD_NAME_SELECT_TITLE'),
		'fieldTypeFieldTitle' => GetMessage('CRM_PRESET_FIELD_FIELD_TYPE_TITLE'),
		'fieldTitleTitle' => GetMessage('CRM_PRESET_FIELD_FIELD_TITLE_TITLE'),
		'sortFieldTitle' => GetMessage('CRM_PRESET_FIELD_SORT_TITLE'),
		'inShortListFieldTitle' => GetMessage('CRM_PRESET_FIELD_IN_SHORT_LIST_TITLE'),
		'addBtnText' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_BTN_ADD_TEXT'),
		'editBtnText' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_BTN_EDIT_TEXT'),
		'cancelBtnText' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_BTN_CANCEL_TEXT'),
		'emptyFieldNameError' => GetMessage('CRM_PRESET_FIELD_EMPTY_NAME_ERROR'),
		'emptyFieldTypeError' => GetMessage('CRM_PRESET_FIELD_EMPTY_TYPE_ERROR'),
		'longFieldTitleError' => GetMessage('CRM_PRESET_FIELD_LONG_FIELD_TITLE_ERROR'),
		'createNewTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_NEW_TITLE'),
		'createSelectedTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_CREATE_SELECTED_TITLE'),
		'emptyNewFieldTitleError' => GetMessage('CRM_PRESET_FIELD_EMPTY_NEW_FIELD_TITLE_ERROR'),
		'defaultFieldTitle' => "",
		'defaultSort' => "500",
		'defaultInShortList' => "Y",
		'newFieldGroupTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_NEW_FIELD_GROUP_TITLE'),
		'availableFieldGroupTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_AVAILABLE_FIELD_GROUP_TITLE'),
		'newStringFieldTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_STRING_FIELD_TITLE'),
		'newDoubleFieldTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_DOUBLE_FIELD_TITLE'),
		'newBooleanFieldTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_BOOLEAN_FIELD_TITLE'),
		'newDatetimeFieldTitle' => GetMessage('CRM_PRESET_FIELD_ADD_DIALOG_DATETIME_FIELD_TITLE')
	]
];
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $rows,
		'ACTIONS' => array('delete' => true),
		'ACTION_ALL_ROWS' => true,
		'FOOTER' => array(array('title' => GetMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
        'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_SHADOW' => $arResult['AJAX_OPTION_SHADOW'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP']
	),
	$component, array('HIDE_ICONS' => 'Y')
);?>
<form id="<?= $presetFieldAddFormId ?>" action="<?= POST_FORM_ACTION_URI ?>" method="POST" enctype="multipart/form-data"
 style="display: none;">
	<?= bitrix_sessid_post(); ?>
	<input type="hidden" name="ID" value="0">
	<input type="hidden" name="FIELD_NAME" value="">
	<input type="hidden" name="FIELD_TITLE" value="">
	<input type="hidden" name="IN_SHORT_LIST" value="">
	<input type="hidden" name="SORT" value="">
	<input type="hidden" name="action" value="">
    <input id="<?= $presetFieldAddFormId.'_save' ?>" type="submit" name="save" value="Y">
</form>
<script>
    BX.ready(function () {
        BX.namespace("BX.Crm");
        var presetFieldListManagerId = "<?= CUtil::JSEscape($params['presetFieldListManagerId']) ?>";
        BX.Crm[presetFieldListManagerId] = new BX.Crm.PresetFieldListManagerClass({
            id: presetFieldListManagerId,
            componentId: "<?= CUtil::JSEscape($params['componentId']) ?>",
            formId: "<?= CUtil::JSEscape($params['formId']) ?>",
            entityFieldsForSelect: <?= CUtil::PhpToJSObject($params['entityFieldsForSelect']) ?>,
            fieldData: <?= CUtil::PhpToJSObject($params['fieldData']) ?>,
            userFieldEntityId: "<?= CUtil::JSEscape($params['userFieldEntityId']) ?>",
            userFieldServiceUrl: "<?= CUtil::JSEscape($params['userFieldServiceUrl']) ?>",
            messages: {
                "fieldAddDialogTitle": "<?= CUtil::JSEscape($params['messages']['fieldAddDialogTitle']) ?>",
                "fieldEditDialogTitle": "<?= CUtil::JSEscape($params['messages']['fieldEditDialogTitle']) ?>",
                "fieldNameFieldTitle": "<?= CUtil::JSEscape($params['messages']['fieldNameFieldTitle']) ?>",
                "fieldNameSelectFieldTitle": "<?= CUtil::JSEscape($params['messages']['fieldNameSelectFieldTitle']) ?>",
                "fieldTypeFieldTitle": "<?= CUtil::JSEscape($params['messages']['fieldTypeFieldTitle']) ?>",
                "fieldTitleTitle": "<?= CUtil::JSEscape($params['messages']['fieldTitleTitle']) ?>",
                "sortFieldTitle": "<?= CUtil::JSEscape($params['messages']['sortFieldTitle']) ?>",
                "inShortListFieldTitle": "<?= CUtil::JSEscape($params['messages']['inShortListFieldTitle']) ?>",
                "addBtnText": "<?= CUtil::JSEscape($params['messages']['addBtnText']) ?>",
                "editBtnText": "<?= CUtil::JSEscape($params['messages']['editBtnText']) ?>",
                "cancelBtnText": "<?= CUtil::JSEscape($params['messages']['cancelBtnText']) ?>",
                "emptyFieldNameError": "<?= CUtil::JSEscape($params['messages']['emptyFieldNameError']) ?>",
                "emptyFieldTypeError": "<?= CUtil::JSEscape($params['messages']['emptyFieldTypeError']) ?>",
                "longFieldTitleError": "<?= CUtil::JSEscape($params['messages']['longFieldTitleError']) ?>",
                "createNewTitle": "<?= CUtil::JSEscape($params['messages']['createNewTitle']) ?>",
                "createSelectedTitle": "<?= CUtil::JSEscape($params['messages']['createSelectedTitle']) ?>",
                "emptyNewFieldTitleError": "<?= CUtil::JSEscape($params['messages']['emptyNewFieldTitleError']) ?>",
                "defaultFieldTitle": "",
                "defaultSort": "500",
                "defaultInShortList": "Y",
                "newFieldGroupTitle": "<?= CUtil::JSEscape($params['messages']['newFieldGroupTitle']) ?>",
                "availableFieldGroupTitle": "<?= CUtil::JSEscape($params['messages']['availableFieldGroupTitle']) ?>",
                "newStringFieldTitle": "<?= CUtil::JSEscape($params['messages']['newStringFieldTitle']) ?>",
                "newDoubleFieldTitle": "<?= CUtil::JSEscape($params['messages']['newDoubleFieldTitle']) ?>",
                "newBooleanFieldTitle": "<?= CUtil::JSEscape($params['messages']['newBooleanFieldTitle']) ?>",
                "newDatetimeFieldTitle": "<?= CUtil::JSEscape($params['messages']['newDatetimeFieldTitle']) ?>"
            }
        });
    });
</script>