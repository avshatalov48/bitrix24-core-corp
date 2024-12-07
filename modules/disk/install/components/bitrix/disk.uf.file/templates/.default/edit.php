<?php

/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load(['disk.uploader.user-field-widget']);

$ids = array_map(function($file) {
	if (is_string($file['ID']) && strlen($file['ID']) > 0 && $file['ID'][0] === 'n')
	{
		return $file['ID'];
	}

	return (int)$file['ID'];
}, $arResult['FILES']);

$files = DiskUploaderController::getFileInfo($ids);
$filesJson = \CUtil::phpToJSObject($files, false, false, true);

$allowDocumentEdit =
	!isset($arParams['PARAMS']['HIDE_CHECKBOX_ALLOW_EDIT'])
	|| $arParams['PARAMS']['HIDE_CHECKBOX_ALLOW_EDIT'] !== 'Y'
;
$allowDocumentFieldName = $allowDocumentEdit ? $arResult['INPUT_NAME_OBJECT_ALLOW_EDIT'] : '';

$newEntityCreation = empty($arParams['arUserField']['ENTITY_VALUE_ID']);
$photoTemplate =
	$newEntityCreation
		? 'grid'
		: (empty($arParams['TEMPLATE_VIEW']) ? 'grid' : $arParams['TEMPLATE_VIEW'])
;

$canChangePhotoTemplate =
	!isset($arParams['PARAMS']['HIDE_CHECKBOX_PHOTO_TEMPLATE'])
	|| $arParams['PARAMS']['HIDE_CHECKBOX_PHOTO_TEMPLATE'] !== 'Y'
;

$photoTemplateFieldName = $canChangePhotoTemplate ? $arResult['INPUT_NAME_TEMPLATE_VIEW'] : '';
$photoTemplateMode = $newEntityCreation ? 'auto' : 'manual';

$isMainPostForm = isset($arParams['MAIN_POST_FORM']) && $arParams['MAIN_POST_FORM'] === true;
$mainPostFormId = $arParams['MAIN_POST_FORM_ID'] ?? '';
$multiple = !isset($arParams['arUserField']['MULTIPLE']) || $arParams['arUserField']['MULTIPLE'] === 'Y';
?>

<?
// If request post data doesn't have this empty field (e.g you remove all files)
// A user field doesn't update entity files
?>
<input type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="">
<div id="disk-uf-file-container-<?=$arResult['UID']?>"></div>
<script>
(function() {
	const containerId = '<?= \CUtil::jsEscape('disk-uf-file-container-' . $arResult['UID']) ?>';
	const container = document.getElementById(containerId);
	const isMainPostForm = <?= Json::encode($isMainPostForm)?>;

	const widget = new BX.Disk.Uploader.UserFieldWidget(
		{
			id: 'disk-uf-file-<?=$arResult['UID']?>',
			hiddenFieldName: '<?= \CUtil::jsEscape(preg_replace('/\[\]$/', '', $arResult['controlName'])) ?>',
			hiddenFieldsContainer: `#${containerId}`,
			imagePreviewHeight: 1200, <? // double size (see html-parser.js and DiskUploaderController ?>
			imagePreviewWidth: 1200,
			imagePreviewQuality: 0.85,
			treatOversizeImageAsFile: true,
			ignoreUnknownImageTypes: true,
			multiple: <?= Json::encode($multiple)?>,
			events: {
				'onError': function(event) {
					console.error('File Uploader onError', event.getData().error);
				},
				'File:onError': function(event) {
					console.error(event.getData().error.toString());
				},
			},
		},
		{
			eventObject: isMainPostForm ? container.parentNode : null,
			mainPostFormId: <?= Json::encode($mainPostFormId)?>,
			files: <?=$filesJson?>,
			canCreateDocuments: <?= Json::encode($arResult['CAN_CREATE_FILE_BY_CLOUD'])?>,
			disableLocalEdit: <?= Json::encode($arResult['DISABLE_LOCAL_EDIT'])?>,
			allowDocumentFieldName: <?= Json::encode($allowDocumentFieldName)?>,
			photoTemplate: <?= Json::encode($photoTemplate)?>,
			photoTemplateFieldName: <?= Json::encode($photoTemplateFieldName)?>,
			photoTemplateMode: <?= Json::encode($photoTemplateMode)?>,
		}
	);

	widget.renderTo(container);

})();
</script>
