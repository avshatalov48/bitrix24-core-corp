<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;
?>

<?
CJSCore::Init(array('disk'));
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.grid/templates/.default/bitrix/main.interface.grid/.default/style.css');
?>

<div class="bx-disk-interface-toolbar-container" style="max-height: 60px; overflow: hidden;">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.breadcrumbs',
		'',
		array(
			'STORAGE_ID' => $arResult['STORAGE']['ID'],
			'BREADCRUMBS_ROOT' => $arResult['ROOT_OBJECT'],
			'BREADCRUMBS' => array(),
		)
	);
	?>

	<div style="clear: both;"></div>
</div>
<div class="bx-disk-interface-filelist">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'AJAX_MODE' => 'Y',
			//Strongly required
			'AJAX_OPTION_JUMP'    => 'N',
			'AJAX_OPTION_STYLE'   => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
			
			'MODE' => $arResult['GRID']['MODE'],
			'GRID_ID' => $arResult['GRID']['ID'],
			'HEADERS' => $arResult['GRID']['HEADERS'],
			'SORT' => $arResult['GRID']['SORT'],
			'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
			'ROWS' => $arResult['GRID']['ROWS'],
			'TOTAL_ROWS_COUNT' => $arResult['GRID']['TOTAL_ROWS_COUNT'],

			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_ROW_CHECKBOXES" => true,
			"SHOW_ROW_ACTIONS_MENU" => true,
			"SHOW_GRID_SETTINGS_MENU" => true,
			"SHOW_NAVIGATION_PANEL" => true,
			"SHOW_PAGINATION" => true,
			"SHOW_SELECTED_COUNTER" => true,
			"SHOW_TOTAL_COUNTER" => true,
			"SHOW_PAGESIZE" => true,
			"SHOW_ACTION_PANEL" => true,

			"ACTION_PANEL" => $arResult['GRID']['ACTION_PANEL'],
		),
		$component
	);
	?>
</div>
<script type="text/javascript">
BX(function () {
	BX.Disk.storePathToUser('<?= CUtil::JSUrlEscape($arParams['PATH_TO_USER']) ?>');
	BX.Disk['ExternalLinkListClass_<?= $component->getComponentId() ?>'] = new BX.Disk.ExternalLinkListClass({
		gridId: "<?= $arResult['GRID']['ID'] ?>"
	});
});
BX.message({
	DISK_EXTERNAL_LINK_LIST_DELETE_TITLE: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_TITLE")?>',
	DISK_EXTERNAL_LINK_LIST_CANCEL_DELETE_BUTTON: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_CANCEL_DELETE_BUTTON")?>',
	DISK_EXTERNAL_LINK_LIST_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_GROUP_CONFIRM")?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_1: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_1') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_21: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_21') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_2_4: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_2_4') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_5_20: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_5_20') ?>',
	DISK_EXTERNAL_LINK_LIST_SHOW_LINK_WINDOW: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SHOW_LINK_WINDOW') ?>',
	DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON")?>'
});


</script>