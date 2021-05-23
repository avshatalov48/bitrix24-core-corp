<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/** @var \CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CCrmActivityCustomTypeComponent $component */
global $APPLICATION;

$canEdit = $arResult['CAN_EDIT'];
$canDelete = $arResult['CAN_DELETE'];

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$jsGridID = CUtil::JSEscape($arResult['GRID_ID']);
foreach($arResult['ITEMS'] as $item)
{
	$actions = array();
	$canEditItem = $canEdit && $item['CAN_EDIT'];
	if($canEditItem)
	{
		$actions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_ACT_CUST_TYPE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_ACT_CUST_TYPE_EDIT'),
			'ONCLICK' => 'BX.CrmActivityCustomTypeList.items["'.$jsGridID.'"].edit('.$item['ID'].');',
			'DEFAULT' => false
		);

		$actions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_ACT_CUST_TYPE_USER_FIELD_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_ACT_CUST_TYPE_USER_FIELD_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($item['PATH_TO_USER_FIELD_EDIT']).'\');',
			'DEFAULT' => false
		);
	}

	if ($canDelete && $item['CAN_DELETE'])
	{
		$actions[] = array('SEPARATOR' => true);
		$actions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_ACT_CUST_TYPE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_ACT_CUST_TYPE_DELETE'),
			'ONCLICK' => 'BX.CrmActivityCustomTypeList.items["'.$jsGridID.'"].delete("'.CUtil::JSEscape($item['NAME']).'", "'.CUtil::JSEscape($item['PATH_TO_DELETE']).'");'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $item['ID'],
		'actions' => $actions,
		'data' => $item,
		'editable' => $canEditItem ? true : $arColumns,
		'columns' => array(
			'CREATED_DATE' => FormatDate('SHORT', MakeTimeStamp($item['CREATED_DATE']))
		)
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' =>
			array(
				array(
					'title' => GetMessage('CRM_ALL'),
					'value' => $arResult['ROWS_COUNT']
				)
			),
		'EDITABLE' => $canEdit,
		'ACTIONS' =>
			array(
				'delete' => $canDelete,
				'list' => array()
			),
		'ACTION_ALL_ROWS' => false,
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'NAV_PARAMS'=>array('SEF_MODE' => 'Y'),
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY']
	),
	$component
);
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmActivityCustomTypeEditDialog.messages =
			{
				fieldName: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_FIELD_NAME')?>",
				fieldSort: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_FIELD_SORT')?>",
				defaultName: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_DEFAULT_NAME')?>",
				createTitle: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_TITLE_CREATE')?>",
				editTitle: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_TITLE_EDIT')?>",
				saveButton: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_BUTTON_SAVE')?>",
				cancelButton: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_BUTTON_CANCEL')?>",
				errorTitle: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_ERROR_TITLE')?>",
				fieldNameNotAssignedError: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_FIELD_NAME_NOT_ASSIGNED_ERROR')?>"
			};


			BX.CrmActivityCustomTypeList.messages =
			{
				deleteTitle: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_DELETE_TITLE')?>",
				deleteConfirm: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_DELETE_CONFIRM')?>",
				deleteButton: "<?=GetMessageJS('CRM_ACT_CUST_TYPE_DELETE')?>"
			};

			BX.CrmActivityCustomTypeList.create(
				"<?=$jsGridID?>",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.activity.custom_type.list/ajax.php?'.bitrix_sessid_get()?>"
				}
			);

			<?if(isset($arResult['OPEN_EDIT']) && $arResult['OPEN_EDIT'] <= 0):?>
			BX.CrmActivityCustomTypeList.items["<?=$jsGridID?>"].add();
			<?elseif(isset($arResult['OPEN_EDIT']) && $arResult['OPEN_EDIT'] > 0):?>
			BX.CrmActivityCustomTypeList.items["<?=$jsGridID?>"].edit(<?=$arResult['OPEN_EDIT']?>);
			<?endif;?>
		}
	);
</script><?