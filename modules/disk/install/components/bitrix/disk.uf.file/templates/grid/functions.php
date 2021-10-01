<?php
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->IncludeLangFile("functions.php");
?>
<script>
BX.message({
	DISK_FILE_LOADING : "<?=GetMessageJS('WD_FILE_LOADING')?>",
	DISK_FILE_EXISTS : "<?=GetMessage('WD_FILE_EXISTS')?>",
	DISK_ACCESS_DENIED : "<?=GetMessage('WD_ACCESS_DENIED')?>",
	DISK_CREATE_BLANK_URL : '<?= CUtil::JSUrlEscape($arResult['CREATE_BLANK_URL']) ?>',
	DISK_RENAME_FILE_URL : '<?= CUtil::JSUrlEscape($arResult['RENAME_FILE_URL']) ?>',
	DISK_THUMB_WIDTH : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["width"]?>',
	DISK_THUMB_HEIGHT : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["height"]?>',
	wd_service_edit_doc_default: "<?= CUtil::JSEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>"
});
</script>