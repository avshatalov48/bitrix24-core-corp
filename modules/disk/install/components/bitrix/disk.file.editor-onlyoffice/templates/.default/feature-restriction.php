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
Loc::loadLanguageFile(__DIR__ . '/template.php');

/** @var array $arResult */
Extension::load([
	'disk',
	'ui.fonts.opensans',
	'main.loader',
	'ui.info-helper',
]);

$containerId = 'feature-restriction-'.$this->randString();
$headerText = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_MODE_VIEW');
?>


<div data-id="<?= $containerId ?>-wrapper">
	<div class="disk-fe-office-header">
		<div class="disk-fe-office-header-left">
			<a href="<?= $arResult['HEADER_LOGO_LINK'] ?>" class="disk-fe-office-header-logo" target="_blank"></a>
			<div class="disk-fe-office-header-mode">
				<span class="disk-fe-office-header-mode-text"><?= $headerText ?></span>
			</div>
		</div>
	</div>
	<div data-id="<?= $containerId ?>">
		<div data-id="<?= $containerId ?>-base" style="height: calc(100vh - 70px)"></div>
	</div>
</div>

<script>
	BX.ready(function () {

		top.BX.addCustomEvent("SidePanel.Slider:onClose", function (event) {
			/** @var {BX.SidePanel.Slider} */
			var slider = event.getSlider();
			if (slider && slider.getUrl() === BX.UI.InfoHelper.getSliderId())
			{
				/** @var {BX.SidePanel.Slider} */
				var currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
				if (currentSlider)
				{
					currentSlider.close();
				}
			}
		});

		BX.UI.InfoHelper.show('<?= $arResult['INFO_HELPER_CODE'] ?>');
	});
</script>
