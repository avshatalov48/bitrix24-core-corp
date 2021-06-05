<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\CloseButton;
use Bitrix\UI\Buttons\SaveButton;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
Extension::load([
    'disk',
	'ui.forms',
	'ui.alerts',
	'ui.fonts.opensans',
	'main.loader',
	'pull.client',
]);

Asset::getInstance()->addString('<script type="text/javascript" src="' . $arResult['SERVER'] . '/web-apps/apps/api/documents/api.js"></script>');
$containerId = 'editor-form-'.$this->randString();
?>

<div data-id="<?= $containerId ?>-wrapper">
    <div data-id="<?= $containerId ?>">
        <div id="<?= $containerId ?>-editor" data-id="<?= $containerId ?>-editor"></div>
    </div>
</div>

<script>
    <?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>

	new BX.Disk.Editor.OnlyOffice({
		targetNode: document.querySelector('[data-id="<?=$containerId?>"]'),
        editorJson: <?=$arResult['EDITOR_JSON']?>,
        editorNode: document.querySelector('[data-id="<?=$containerId?>-editor"]'),
        editorWrapperNode: document.querySelector('[data-id="<?=$containerId?>-wrapper"]'),
        documentSession: {
			id: <?=$arResult['DOCUMENT_SESSION']['ID'] ?>,
			hash: '<?=$arResult['DOCUMENT_SESSION']['HASH'] ?>'
        },
        object: {
        	id: <?=$arResult['OBJECT']['ID'] ?>
        }
	});
</script>