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
	'ui.design-tokens',
	'ui.fonts.opensans',
	'disk',
	'disk.document',
	'ui.forms',
	'main.loader',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.icons.b24',
]);

$containerId = 'templates'.$this->randString();

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-hidden no-background disk-file-editor-workarea");

?>

<div class="disk-file-editor-templates" data-id="<?= $containerId ?>">
	<div class="disk-file-editor-templates-title">
		<div class="disk-file-editor-templates-title-name"><?= Loc::getMessage('DISK_FILE_EDITOR_TEMPLATES_TITLE') ?></div>
		<div class="disk-file-editor-templates-title-desc"><?= Loc::getMessage('DISK_FILE_EDITOR_TEMPLATES_SUBTITLE') ?></div>
	</div>
	<div class="disk-file-editor-templates-content">
		<? foreach ($arResult['TEMPLATES'] as $template):?>
			<div class="disk-file-editor-templates-item disk-file-editor-templates-item--template-<?= $template['ID'] ?>">
				<div class="disk-file-editor-templates-card-box" data-template-id="<?= $template['ID'] ?>">
					<div class="disk-file-editor-templates-item-card">
						<div class="disk-file-editor-templates-item-preview"></div>
						<div class="disk-file-editor-templates-item-icon"></div>
					</div>
				</div>
				<div class="disk-file-editor-templates-item-name"><?= $template['NAME'] ?></div>
			</div>
		<? endforeach; ?>
	</div>
	<div class="ui-btn-container ui-btn-container-center disk-file-editor-templates-btn-box"></div>
</div>

<script>
	<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>

	new BX.Disk.EditorTemplates.Component({
		container: document.querySelector('[data-id="<?= $containerId ?>"]'),
		buttonClass: 'disk-file-editor-templates-card-box',
		call: {
			id: <?= (int)$arResult['MESSENGER']['CALL']['ID'] ?>,
		}
	});
</script>
