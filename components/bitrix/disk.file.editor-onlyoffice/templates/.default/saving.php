<?php

/** @var $this CBitrixComponentTemplate */
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
/** @var BaseComponent $component */

use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
Extension::load([
	'disk',
	'im',
	'ui.fonts.opensans',
	'main.loader',
	'pull.client',
]);

$containerId = 'editorForm'.$this->randString();
?>

<div id="test" class="disk-file-editor-onlyoffice-loader">
	<div class="disk-file-editor-onlyoffice-loader-text"><?= Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_WAITING_NEW_VERSION_TITLE') ?></div>
	<div class="disk-file-editor-onlyoffice-loader-text--sm"><?= Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_WAITING_NEW_VERSION_DESC') ?></div>
</div>

<script>
	<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>

	new BX.Disk.Editor.Waiting({
		documentSession: {
			id: <?= $arResult['DOCUMENT_SESSION']['ID'] ?>,
			hash: '<?= $arResult['DOCUMENT_SESSION']['HASH'] ?>',
		},
		object: {
			id: <?= $arResult['OBJECT']['ID'] ?>,
			name: '<?= \CUtil::JSEscape($arResult['OBJECT']['NAME']) ?>'
		},
	});
</script>
