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
?>

<?
CJSCore::Init(array('viewer', 'disk_page', 'disk'));
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.grid/templates/.default/bitrix/main.interface.grid/.default/style.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.toolbar/templates/.default/style.css');

$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'pagetitle-toolbar-field-view')));
?>

<? $this->setViewTarget("inside_pagetitle", 10); ?>
	<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
		<?
		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.filter',
			'',
			array(
				'GRID_ID' => $arResult['GRID']['ID'],
				'FILTER_ID' => $arResult['FILTER']['FILTER_ID'],
				'FILTER' => $arResult['FILTER']['FILTER'],
				'FILTER_PRESETS' => $arResult['FILTER']['FILTER_PRESETS'],
				'ENABLE_LIVE_SEARCH' => $arResult['FILTER']['ENABLE_LIVE_SEARCH'],
				'ENABLE_LABEL' => $arResult['FILTER']['ENABLE_LABEL'],
				'RESET_TO_DEFAULT_MODE' => $arResult['FILTER']['RESET_TO_DEFAULT_MODE'],
			),
			$component
		);

		?>
		<div class="pagetitle-container pagetitle-align-right-container">
			<span id="bx-disk-settings-change-btn" class="webform-small-button webform-small-button-transparent webform-cogwheel">
				<span class="webform-button-icon"></span>
			</span>
		</div>
	</div>
<? $this->endViewTarget(); ?>

<? $this->setViewTarget('below_pagetitle'); ?>
<div class="bx-disk-interface-toolbar-container">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.breadcrumbs',
		'',
		array(
			'STORAGE_ID' => $arResult['STORAGE']['ID'],
			'BREADCRUMBS_ROOT' => $arResult['BREADCRUMBS_ROOT'],
			'BREADCRUMBS' => $arResult['BREADCRUMBS'],
			'SHOW_ONLY_DELETED' => true,
		)
	);
	?>
	<div style="clear: both;"></div>
</div>
<? $this->endViewTarget(); ?>

<div class="bx-disk-interface-filelist">
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'DATA_FOR_PAGINATION' => $arResult['GRID']['DATA_FOR_PAGINATION'],
		'GRID_ID' => $arResult['GRID']['ID'],
		'HEADERS' => $arResult['GRID']['HEADERS'],
		'SORT' => $arResult['GRID']['SORT'],
		'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
		'ROWS' => $arResult['GRID']['ROWS'],

		'AJAX_MODE' => 'Y',
		//Strongly required
		'AJAX_OPTION_JUMP'    => 'N',
		'AJAX_OPTION_STYLE'   => 'N',
		'AJAX_OPTION_HISTORY' => 'N',

		'SHOW_CHECK_ALL_CHECKBOXES' => true,
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => true,
		'SHOW_TOTAL_COUNTER' => false,
		'SHOW_PAGESIZE' => true,
		'SHOW_ACTION_PANEL' => true,

		'ACTION_PANEL' => $arResult['GRID']['ACTION_PANEL'],
		'NAV_OBJECT' => $arResult['GRID']['NAV_OBJECT'],
		'~NAV_PARAMS' => array(
			'SHOW_COUNT' => 'N',
			'SHOW_ALWAYS' => false,
		),
	),
	$component
);
?>
</div>
<script type="text/javascript">
BX(function () {
	BX.Disk['TrashCanClass_<?= $component->getComponentId() ?>'] = new BX.Disk.TrashCanClass({
		trashcan: {
			link: '<?= $arParams['URL_TO_TRASHCAN_LIST'] ?>'
		},
		rootObject: {
			id: <?= $arResult['FOLDER']['ID'] ?>
		},
		gridId: "<?= $arResult['GRID']['ID'] ?>",
		filterId: "<?= $arResult['FILTER']['FILTER_ID'] ?>"
	});
});
	
BX.message({
	DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_EMPTY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DESTROY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DESTROY_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_TITLE")?>',
	DISK_TRASHCAN_TRASH_RESTORE_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_TITLE")?>',
	DISK_TRASHCAN_TRASH_EMPTY_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_TITLE")?>',
	DISK_TRASHCAN_TRASH_COUNT_ELEMENTS: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_COUNT_ELEMENTS")?>',
	DISK_TRASHCAN_TITLE_MODAL_MOVE_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MOVE_TO")?>',
	DISK_TRASHCAN_TITLE_MODAL_COPY_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_COPY_TO")?>',
	DISK_TRASHCAN_TITLE_MODAL_MANY_COPY_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MANY_COPY_TO")?>',
	DISK_TRASHCAN_TITLE_SIDEBAR_MANY_RESTORE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_SIDEBAR_MANY_RESTORE_BUTTON")?>',
	DISK_TRASHCAN_TITLE_SIDEBAR_MANY_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_SIDEBAR_MANY_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TITLE_MODAL_MOVE_TO_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MOVE_TO_BUTTON")?>',
	DISK_TRASHCAN_TITLE_MODAL_COPY_TO_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_COPY_TO_BUTTON")?>',
	DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON")?>',
	DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON")?>',
	DISK_TRASHCAN_SELECTED_OBJECT_1: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_1') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_21: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_21') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_2_4: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_2_4') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_5_20: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_5_20') ?>'
});	

</script>


