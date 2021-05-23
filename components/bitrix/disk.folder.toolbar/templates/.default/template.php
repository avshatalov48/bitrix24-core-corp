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
<?php
use Bitrix\Disk\Ui;
CJSCore::Init(array('viewer', 'disk'));

$APPLICATION->includeComponent(
	'bitrix:disk.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => 'folder_toolbar',
		'BUTTONS' => $arResult['BUTTONS'],
		'DROPDOWN_FILTER' => $arResult['DROPDOWN_FILTER'],
		'DROPDOWN_FILTER_CURRENT_LABEL' => $arResult['DROPDOWN_FILTER_CURRENT_LABEL'],
	),
	$component,
	array(
		'HIDE_ICONS' => 'Y'
	)
);
?>

<script type="text/javascript">
BX.message({
	<? if(!empty($arResult['CLOUD_DOCUMENT'])){ ?>
	wd_service_edit_doc_default: '<?= CUtil::JSEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>',
	<? } ?>
	DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT: '<?= CUtil::JSEscape(\Bitrix\Disk\Document\LocalDocumentController::getName()) ?>',
	DISK_FOLDER_TOOLBAR_LABEL_NAME_CREATE_FOLDER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_NAME_CREATE_FOLDER")?>',
	DISK_FOLDER_TOOLBAR_LABEL_NAME_RIGHTS_USER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_NAME_RIGHTS_USER")?>',
	DISK_FOLDER_TOOLBAR_LABEL_NAME_ADD_RIGHTS_USER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_NAME_ADD_RIGHTS_USER")?>',
	DISK_FOLDER_TOOLBAR_LABEL_NAME_ALLOW_SHARING_RIGHTS_USER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_NAME_ALLOW_SHARING_RIGHTS_USER")?>',
	DISK_FOLDER_TOOLBAR_LABEL_RIGHT_READ: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_RIGHT_READ")?>',
	DISK_FOLDER_TOOLBAR_LABEL_RIGHT_EDIT: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_RIGHT_EDIT")?>',
	DISK_FOLDER_TOOLBAR_LABEL_RIGHT_FULL: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_RIGHT_FULL")?>',
	DISK_FOLDER_TOOLBAR_LABEL_NAME_RIGHTS: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_NAME_RIGHTS")?>',
	DISK_FOLDER_TOOLBAR_LABEL_RIGHTS_FOLDER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_RIGHTS_FOLDER")?>',
	DISK_FOLDER_TOOLBAR_TITLE_CREATE_FOLDER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_TITLE_CREATE_FOLDER")?>',
	DISK_FOLDER_TOOLBAR_BTN_CREATE_FOLDER: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_BTN_CREATE_FOLDER")?>',
	DISK_FOLDER_TOOLBAR_BTN_CLOSE: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_BTN_CLOSE")?>',
	DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TITLE: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TITLE")?>',
	DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TEXT: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TEXT")?>',
	DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_DOC: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_DOC")?>',
	DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_XLS: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_XLS")?>',
	DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_PPT	: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_PPT")?>',
	DISK_FOLDER_TOOLBAR_LABEL_TOOLTIP_SHARING	: '<?=GetMessageJS("DISK_FOLDER_TOOLBAR_LABEL_TOOLTIP_SHARING")?>'
});

var BXSocNetLogDestinationFormName = '<?=randString(6)?>';

BX.ready(function () {
	BX.Disk['FolderToolbarClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FolderToolbarClass({
		id: 'folder_toolbar',
		destFormName: BXSocNetLogDestinationFormName,
		<? if(!empty($arResult['CLOUD_DOCUMENT'])){ ?>
		defaultService: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>",
		defaultServiceLabel: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE_LABEL']) ?>",
		createBlankFileUrl: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['CREATE_BLANK_FILE_URL']) ?>",
		renameBlankFileUrl: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['RENAME_BLANK_FILE_URL']) ?>",
		<? } ?>
		targetFolderId: "<?= $arParams['FOLDER_ID'] ?>"
	});

	BX.message({
		'BX_FPD_LINK_1':'<?=GetMessageJS("EC_DESTINATION_1")?>',
		'BX_FPD_LINK_2':'<?=GetMessageJS("EC_DESTINATION_2")?>',
		disk_restriction: <?= (!\Bitrix\Disk\Integration\Bitrix24Manager::checkAccessEnabled('disk', $USER->getId())? 'true' : 'false') ?>
	});
});
</script>