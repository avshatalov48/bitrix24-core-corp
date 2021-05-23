<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
/** @var \Bitrix\Disk\Folder $arParams["FOLDER"] */
include_once(__DIR__."/message.php");
CJSCore::Init(array('uploader'));
//$arParams["INPUT_CONTAINER"];
//$arParams["CID"];
//$arParams["DROPZONE"];
$statusStartBizProc = isset($arParams['STATUS_START_BIZPROC']) ? $arParams['STATUS_START_BIZPROC'] : '';
$bizProcParameters = isset($arParams['BIZPROC_PARAMETERS']) ? $arParams['BIZPROC_PARAMETERS'] : '';
$bizProcParametersRequired = isset($arParams['BIZPROC_PARAMETERS_REQUIRED']) ? $arParams['BIZPROC_PARAMETERS_REQUIRED'] : '';
?>
<script>
BX.ready(function() {
	BX.DiskUpload.initialize({
		bp: '<?= $statusStartBizProc ?>',
		bpParameters: '<?= $bizProcParameters ?>',
		bpParametersRequired: <?= \Bitrix\Main\Web\Json::encode($bizProcParametersRequired) ?>,
		storageId: <?= $arParams['STORAGE_ID'] ?>,
		CID : '<?=CUtil::JSEscape($arParams["CID"])?>',
		<?if (!empty($arParams["FILE_ID"])): ?>targetFileId : '<?=CUtil::JSEscape($arParams["FILE_ID"])?>',<?
		else: ?>targetFolderId : '<?=CUtil::JSEscape(($arParams["FOLDER"] ? $arParams["FOLDER"]->getId() : ''))?>',<? endif; ?>
		urlUpload : '/bitrix/components/bitrix/disk.file.upload/ajax.php',
		<?if (!empty($arParams["~INPUT_CONTAINER"])) { ?>inputContainer : <?=$arParams["~INPUT_CONTAINER"]?>,<? } ?>
		dropZone : <?=(isset($arParams["~DROPZONE"]) ? $arParams["~DROPZONE"] : 'null')?>});
});
</script>
<?
global $USER;
if(
	\Bitrix\Disk\Integration\Bitrix24Manager::isEnabled()
)
{
	?>
	<div id="bx-bitrix24-business-tools-info" style="display: none; width: 600px; margin: 9px;">
		<? $APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array()); ?>
	</div>
<?
}