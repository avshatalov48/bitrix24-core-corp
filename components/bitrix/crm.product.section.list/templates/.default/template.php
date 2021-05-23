<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$extMgrId = isset($arParams['PRODUCT_SECTION_MANAGER_ID']) ? $arParams['PRODUCT_SECTION_MANAGER_ID'] : '';
$mgrId = isset($extMgrId[0]) ? $extMgrId : 'CrmProductSectionManager';

$sectionRows = array();
foreach($arResult['SECTIONS'] as $arSection)
{
	$arCols = array(
		'NAME' => '<a href="'.str_replace(
			'#section_id#',
			$arSection['ID'],
			$arParams['PATH_TO_SECTION_LIST']
		).'">'.$arSection['NAME'].'</a>',
	);

	$arActions = array();

	if($arResult['CAN_EDIT'])
	{
		$arActions[] = array(
			'ICONCLASS' => 'edit',
			'TEXT' => GetMessage('CRM_PRODUCT_SECTION_ACTION_RENAME'),
			'ONCLICK' => 'BX.CrmProductSectionManager.items[\''.$mgrId.'\'].renameSection('.$arSection['ID'].', \''.CUtil::JSEscape($arSection['~NAME']).'\');',
			'DEFAULT' => true,
		);
	}

	if($arResult['CAN_DELETE'])
	{
		$arActions[] = array(
			'ICONCLASS' => 'delete',
			'TEXT' => GetMessage('CRM_PRODUCT_SECTION_ACTION_DELETE'),
			'ONCLICK' => 'bxGrid_'.$arResult['GRID_ID'].'.DeleteItem(\''.$arSection['ID'].'\', \''.GetMessage('CRM_PRODUCT_SECTION_ACTION_DELETE_PROPMT').'\')',
		);
	}

	$sectionRows[] =
		array(
			'id' => $arSection['ID'],
			'data' => $arSection,
			'actions' => $arActions,
			'columns' => $arCols
		);
}

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => array(
			array(
				'id' => 'NAME',
				'name' => GetMessage('CRM_SECTION_NAME'),
				'default' => true,
				'editable' => $arResult['CAN_WRITE']
			),
		),
		'ROWS' => $sectionRows,
		'ACTIONS' => array('delete' => $arResult['CAN_DELETE']),
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
<?
if(!isset($extMgrId[0]))
{?>
	<form name="form_section_add" id="form_section_add" action="<?= POST_FORM_ACTION_URI ?>" method="POST" enctype="multipart/form-data">
		<?= bitrix_sessid_post(); ?>
		<input type="hidden" id="sectionName" name="sectionName" value="">
		<input type="hidden" id="sectionID" name="sectionID" value="">
		<input type="hidden" id="action" name="action" value="">
	</form>
<?
}?>

<script type="text/javascript">
	<?
	if(!isset($extMgrId[0]))
	{?>
	BX.CrmProductSectionManager.create(
		'<?= $mgrId?>',
		{
			formID: 'form_section_add',
			actionField: 'action',
			nameField: 'sectionName',
			IDField: 'sectionID'
		}
	);
		<?
	}?>

	BX.CrmProductSectionManager.messages =
	{
		addDialogTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_ADD_DIALOG_TITLE')) ?>',
		renameDialogTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_RENAME_DIALOG_TITLE')) ?>',
		nameFieldTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_NAME_FIELD_TITLE')) ?>',
		defaultName: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_DEFAULT_NAME')) ?>',
		addBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_ADD_BTN_TEXT')) ?>',
		renameBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_RENAME_BTN_TEXT')) ?>',
		cancelBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_CANCEL_BTN_TEXT')) ?>',
		emptyNameError: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_EMPTY_NAME_ERROR')) ?>'
	};
</script>