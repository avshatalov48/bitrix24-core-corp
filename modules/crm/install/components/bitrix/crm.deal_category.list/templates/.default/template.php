<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;

$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addCss('/bitrix/themes/.default/crm-entity-show.css');
$asset->addJs('/bitrix/js/crm/common.js');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$jsGridID = CUtil::JSEscape($arResult['GRID_ID']);
foreach($arResult['ITEMS'] as $item)
{
	$arActions = array();

	if($arResult['CAN_EDIT'] && $item['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_DEAL_CATEGORY_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_CATEGORY_EDIT'),
			'ONCLICK' => 'BX.CrmDealCategoryList.items["'.$jsGridID.'"].edit('.$item['ID'].');',
			'DEFAULT' => false
		);
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_DEAL_CATEGORY_STATUS_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_CATEGORY_STATUS_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($item['PATH_TO_STATUS_EDIT']).'\');',
			'DEFAULT' => false
		);
	}

	if ($arResult['CAN_DELETE'] && $item['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_DEAL_CATEGORY_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_CATEGORY_DELETE'),
			'ONCLICK' => 'BX.CrmDealCategoryList.items["'.$jsGridID.'"].delete("'.CUtil::JSEscape($item['NAME']).'", "'.CUtil::JSEscape($item['PATH_TO_DELETE']).'");'
		);
	}

	$isDefault = isset($item['IS_DEFAULT']) && $item['IS_DEFAULT'] === true;

	$arResult['GRID_DATA'][] = array(
		'id' => $item['ID'],
		'actions' => $arActions,
		'data' => $item,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'emphasized' => $isDefault,
		'columns' => array('CREATED_DATE' => !$isDefault ? FormatDate('SHORT', MakeTimeStamp($item['CREATED_DATE'])) : '')
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
		'EDITABLE' => $arResult['CAN_EDIT'],
		'ACTIONS' =>
			array(
				'delete' => $arResult['CAN_DELETE'],
				'list' => array()
			),
		'ACTION_ALL_ROWS' => false,
		'NAV_OBJECT' => $arResult['ITEMS'],
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
			BX.CrmDealCategoryEditDialog.messages =
			{
				fieldName: "<?=GetMessageJS('CRM_DEAL_CATEGORY_FIELD_NAME')?>",
				fieldSort: "<?=GetMessageJS('CRM_DEAL_CATEGORY_FIELD_SORT')?>",
				defaultName: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DEFAULT_NAME')?>",
				createTitle: "<?=GetMessageJS('CRM_DEAL_CATEGORY_TITLE_CREATE')?>",
				editTitle: "<?=GetMessageJS('CRM_DEAL_CATEGORY_TITLE_EDIT')?>",
				saveButton: "<?=GetMessageJS('CRM_DEAL_CATEGORY_BUTTON_SAVE')?>",
				cancelButton: "<?=GetMessageJS('CRM_DEAL_CATEGORY_BUTTON_CANCEL')?>",
				errorTitle: "<?=GetMessageJS('CRM_DEAL_CATEGORY_ERROR_TITLE')?>",
				fieldNameNotAssignedError: "<?=GetMessageJS('CRM_DEAL_CATEGORY_FIELD_NAME_NOT_ASSIGNED_ERROR')?>"
			};

			BX.CrmDealCategoryDeleteDialog.messages =
			{
				title: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DELETE_TITLE')?>",
				confirm: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DELETE_CONFIRM')?>",
				deleteButton: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DELETE')?>"
			};

			BX.CrmDealCategoryList.create(
				"<?=$jsGridID?>",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.deal_category.list/ajax.php?'.bitrix_sessid_get()?>"
				}
			);

		<?if(isset($arResult['OPEN_EDIT']) && $arResult['OPEN_EDIT'] <= 0):?>
			BX.CrmDealCategoryList.items["<?=$jsGridID?>"].add();
		<?elseif(isset($arResult['OPEN_EDIT']) && $arResult['OPEN_EDIT'] > 0):?>
			BX.CrmDealCategoryList.items["<?=$jsGridID?>"].edit(<?=$arResult['OPEN_EDIT']?>);
		<?endif;?>

		}
	);
</script><?