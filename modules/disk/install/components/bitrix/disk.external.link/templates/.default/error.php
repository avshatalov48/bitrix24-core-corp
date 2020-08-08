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

$langId = $component->getLangId();
switch(mb_strtolower($langId))
{
	case 'en':
	case 'de':
	case 'ru':
	case 'ua':
	$langForBanner = mb_strtolower($langId);
		break;
	default:
		$langForBanner = Loc::getDefaultLang($langId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="windows-1251">
	<title><?= $component->getMessage('DISK_EXT_LINK_TITLE') ?></title>
	<link rel="stylesheet" href="<?= $this->getFolder() ?>/style.css">
</head>
<body>

	<div class="bx-shared-wrap">

		<div class="bx-shared-header">
			<div class="bx-shared-logo">
				<?= $component->getMessage('DISK_EXT_LINK_B24') ?>
			</div>
		</div>
		<div class="bx-shared-body">
			<table class="bx-shared-body-container">
				<tr>
					<td class="bx-shared-body-previewblock tac">

						<div class="bx-file-icon-container-big m0a icon-non">
							<div class="bx-file-icon-cover">
								<div class="bx-file-icon-corner"></div>
								<div class="bx-file-icon-corner-fix"></div>
								<div class="bx-file-icon-images"></div>
							</div>
							<div class="bx-file-icon-label"></div>
						</div>

						<br>
						<br>

						<h1 class="bx-shared-body-filename" style="color: #535c69;"><?= $component->getMessage('DISK_EXT_LINK_TEXT') ?></h1>
						<div style="font-size: 14px;color: #535c69;"><?= $arResult['ERROR_MESSAGE']?: $component->getMessage('DISK_EXT_LINK_DESCRIPTION') ?></div>
					</td>
				</tr>
			</table>
		</div>
		<?php if(isModuleInstalled('bitrix24') && \Bitrix\Main\Loader::includeModule('intranet')) { ?>
			<div class="banner_b24" style="">
				<a target="_blank" href="<?= CIntranetUtils::getB24Link('file') . '&utm_source=fileshare_button&utm_medium=referral&utm_campaign=fileshare_button'; ?>" class="banner-b24-link-container">
					<span class="banner-b24-link-container-cyrcle-logo <?= $langForBanner ?>"></span>
					<span class="banner-b24-link-container-cyrcle-desc"><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_TEXT') ?></span>
					<span class="banner-b24-link-container-cyrcle-title l1"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_1') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l2"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_2') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l3"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_3') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l4"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_4') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l5"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_5') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l6"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_6') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-button"><span><?= $component->getMessage('DISK_EXT_LINK_B24_ADV_CREATE_LINK_TEXT') ?></span></span>
				</a>
			</div>
		<?php } ?>
	</div>
</body>
</html>