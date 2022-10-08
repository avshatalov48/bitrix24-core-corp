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
/** @var CDiskExternalLinkComponent $component */
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

if (!empty($arResult['FOLDER']))
{
	$helloMessage = $component->getMessage('DISK_EXT_LINK_FOLDER_PROTECT_BY_PASSWORD');
}
else
{
	$helloMessage = $component->getMessage('DISK_EXT_LINK_PROTECT_BY_PASSWORD');
}
?>
<div class="bx-shared-body">
	<form id="form-pass" action="" method="POST">
		<div class="bx-disk-pass-popup-wrap">
			<div class="bx-disk-popup-content">
				<div class="bx-disk-popup-content-inner">
					<div class="bx-disk-pass-popup-title"><?= $helloMessage ?></div>
					<? if($arResult['VALID_PASSWORD'] === false) { ?>
						<div class="bx-disk-pass-popup-title-descript" style="color: red;"><?= $component->getMessage('DISK_EXT_LINK_PROTECT_BY_WRONG_PASSWORD') ?></div>
					<? } else { ?>
						<div class="bx-disk-pass-popup-title-descript"><?= $component->getMessage('DISK_EXT_LINK_PROTECT_BY_PASSWORD_DESCR') ?></div>
					<? } ?>
					<label class="bx-disk-popup-label"><?= $component->getMessage('DISK_EXT_LINK_LABEL_PASSWORD') ?>:</label>
					<input id="bx-disk-popup-input-pass" class="bx-disk-popup-input" name="PASSWORD" type="password">
				</div>
			</div>
			<div class="bx-disk-popup-buttons">
				<a onclick="document.getElementById('form-pass').submit();" class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green"><?= $component->getMessage('DISK_EXT_LINK_LABEL_BTN') ?></a>
			</div>
		</div>
	</form>
</div>


