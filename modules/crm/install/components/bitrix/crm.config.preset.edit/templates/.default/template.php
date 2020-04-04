<?
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
foreach($arResult['LIST_DATA'] as $key => &$listRow)
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
	$presetEditUrl = str_replace(
		array('#entity_type#', '#preset_id#'),
		array($arResult['ENTITY_TYPE_ID'], $key),
		$arResult['PRESET_EDIT_URL']
	);
	$gridDataItem = array(
		'id' => $key,
		'data' => $row,
		'actions' => array(
			/*array(
				'ICONCLASS' => 'edit',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_EDIT'),
				'ONCLICK' => "jsUtils.Redirect(arguments, '".CUtil::JSEscape($presetEditUrl)."')",
				'DEFAULT' => true
			),
			array('SEPARATOR' => true),*/
			array(
				'ICONCLASS' => 'edit',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_EDIT'),
				'ONCLICK' => 'javascript:BX.Crm["'.CUtil::JSEscape($presetFieldListManagerId).'"].editField('.$key.');'
			),
			array(
				'ICONCLASS' => 'delete',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_DELETE'),
				'ONCLICK' => "javascript:bxGrid_".$arResult['GRID_ID'].".DeleteItem('".$key."', '".GetMessage("CRM_PRESET_LIST_ACTION_MENU_DELETE_CONF")."')"
			)
		),
		'columns' => array(
			'FIELD_ETITLE' => '<span style="color: #2067b0; cursor: pointer;" onclick="'.htmlspecialcharsbx('javascript:BX.Crm["'.CUtil::JSEscape($presetFieldListManagerId).'"].editField('.$key.');').'">'.htmlspecialcharsbx($listRow['FIELD_ETITLE']).'</span>'
		),
		'editable' => array()
	);
	$rows[] = $gridDataItem;
	unset($gridDataItem);
}
unset($listRow);

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
		'AJAX_OPTION_SHADOW' => $arResult['AJAX_OPTION_SHADOW'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP']
	),
	$component, array('HIDE_ICONS' => 'Y')
);?>
<form id="<?= $presetFieldAddFormId ?>" action="<?= POST_FORM_ACTION_URI ?>" method="POST" enctype="multipart/form-data">
	<?= bitrix_sessid_post(); ?>
	<input type="hidden" name="ID" value="0">
	<input type="hidden" name="FIELD_NAME" value="">
	<input type="hidden" name="FIELD_TITLE" value="">
	<input type="hidden" name="IN_SHORT_LIST" value="">
	<input type="hidden" name="SORT" value="">
	<input type="hidden" name="action" value="">
</form>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	var presetFieldListManagerId = "<?= CUtil::JSEscape($presetFieldListManagerId) ?>";
	BX.Crm[presetFieldListManagerId] = new BX.Crm.PresetFieldListManagerClass({
		id: presetFieldListManagerId,
		componentId: "<?= CUtil::JSEscape($arResult['COMPONENT_ID']) ?>",
		formId: "<?= CUtil::JSEscape($presetFieldAddFormId) ?>",
		entityFieldsForSelect: <?= CUtil::PhpToJSObject($arResult['ENTITY_FIELDS_FOR_SELECT']) ?>,
		fieldData: <?= CUtil::PhpToJSObject($arResult['LIST_DATA']) ?>,
		userFieldEntityId: "<?= (isset($arResult['USER_FIELD_ENTITY_ID']) ? $arResult['USER_FIELD_ENTITY_ID'] : '') ?>",
		userFieldServiceUrl: "<?= ('/bitrix/components/bitrix/crm.requisite.edit/uf.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()) ?>",
		messages: {
			"fieldAddDialogTitle": "<?= GetMessageJS('CRM_PRESET_TOOLBAR_FIELD_ADD') ?>",
			"fieldEditDialogTitle": "<?= GetMessageJS('CRM_PRESET_TOOLBAR_FIELD_EDIT') ?>",
			"fieldNameFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_FIELD_NAME_TITLE') ?>",
			"fieldNameSelectFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_FIELD_NAME_SELECT_TITLE') ?>",
			"fieldTypeFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_FIELD_TYPE_TITLE') ?>",
			"fieldTitleTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_FIELD_TITLE_TITLE') ?>",
			"sortFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_SORT_TITLE') ?>",
			"inShortListFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_IN_SHORT_LIST_TITLE') ?>",
			"addBtnText": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_BTN_ADD_TEXT') ?>",
			"editBtnText": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_BTN_EDIT_TEXT') ?>",
			"cancelBtnText": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_BTN_CANCEL_TEXT') ?>",
			"emptyFieldNameError": "<?= GetMessageJS('CRM_PRESET_FIELD_EMPTY_NAME_ERROR') ?>",
			"emptyFieldTypeError": "<?= GetMessageJS('CRM_PRESET_FIELD_EMPTY_TYPE_ERROR') ?>",
			"longFieldTitleError": "<?= GetMessageJS('CRM_PRESET_FIELD_LONG_FIELD_TITLE_ERROR') ?>",
			"createNewTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_NEW_TITLE') ?>",
			"createSelectedTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_CREATE_SELECTED_TITLE') ?>",
			"emptyNewFieldTitleError": "<?= GetMessageJS('CRM_PRESET_FIELD_EMPTY_NEW_FIELD_TITLE_ERROR') ?>",
			"defaultFieldTitle": "",
			"defaultSort": "500",
			"defaultInShortList": "Y",
			"newFieldGroupTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_NEW_FIELD_GROUP_TITLE') ?>",
			"availableFieldGroupTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_AVAILABLE_FIELD_GROUP_TITLE') ?>",
			"newStringFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_STRING_FIELD_TITLE') ?>",
			"newDoubleFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_DOUBLE_FIELD_TITLE') ?>",
			"newBooleanFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_BOOLEAN_FIELD_TITLE') ?>",
			"newDatetimeFieldTitle": "<?= GetMessageJS('CRM_PRESET_FIELD_ADD_DIALOG_DATETIME_FIELD_TITLE') ?>"
		}
	});
</script>