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
use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.viewer',
	'ui.notification',
]);

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
<html lang="<?= mb_strtolower($langId)?>">
<head>
	<meta charset="<?= LANG_CHARSET ?>">
	<title><?= Loc::getMessage('DISK_EXT_LINK_TITLE') ?></title>

	<? if(!$arResult['PROTECTED_BY_PASSWORD']){ ?>
		<meta content="<?= $arResult['FILE']['VIEW_URL'] ?>" property="og:url"/>
		<meta content="<?= htmlspecialcharsbx($arResult['SITE_NAME']) ?>" property="og:site_name"/>
		<meta content="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" property="og:title"/>
		<meta content="website" property="og:type"/>
		<meta content="<?= $component->getMessage('DISK_EXT_LINK_OPEN_GRAPH_MADE_BY_B24') ?>" property="og:description"/>
		<? if($arResult['FILE']['IS_IMAGE'] && $arResult['FILE']['IMAGE_DIMENSIONS']){ ?>
			<meta content="<?= $arResult['FILE']['ABSOLUTE_SHOW_FILE_URL'] ?>" property="og:image"/>
			<meta content="<?= $arResult['FILE']['IMAGE_DIMENSIONS']['WIDTH'] ?>" property="og:image:width"/>
			<meta content="<?= $arResult['FILE']['IMAGE_DIMENSIONS']['HEIGHT'] ?>" property="og:image:height"/>
		<? } ?>
	<? }
	$APPLICATION->ShowCSS();
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
	?>
</head>
<body style="background: none;">
<script>
	BX.ready(function(){
		let inlineController = new BX.UI.Viewer.InlineController({baseContainer: BX('test-content')});
		inlineController.renderItemByNode(BX('test-content'));
		<?php if($arResult['SESSION_EXPIRED']): ?>
			BX.UI.Notification.Center.notify({
				content: '<?= GetMessageJS('DISK_EXT_SESSION_EXPIRED') ?>',
			});

			let url = window.location.href;
			url = url.replace(/\&session=expired/, '');
			window.history.replaceState({}, '', url);

		<?php endif; ?>
	});
</script>
	<div class="bx-shared-wrap">

		<div class="bx-shared-header">
			<div class="bx-shared-logo">
				<?= $component->getMessage('DISK_EXT_LINK_B24') ?>
			</div>
		</div>
<? if(!($arResult['PROTECTED_BY_PASSWORD']) || $arResult['VALID_PASSWORD']){ ?>
		<div class="bx-shared-body">
			<table class="bx-shared-body-container">
				<tr>
					<td class="bx-shared-body-previewblock">
					<? if($arResult['FILE']['PREVIEW']) { ?>
						<iframe src="<?= $arResult['FILE']['PREVIEW']['VIEW_URL'] ?>" frameborder="0" style="height: 520px;width: 720px;"></iframe>
					<? } elseif($arResult['FILE']['IS_IMAGE']) { ?>
						<div class="bx-shared-preview-images">
							<a href="<?= $arResult['FILE']['SHOW_FILE_URL'] ?>" target="_blank"><img src="<?= $arResult['FILE']['SHOW_PREVIEW_URL'] ?>" alt="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" title="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>"></a>
						</div>
					<? } elseif($arResult['FILE']['VIEWER']) {
						echo $arResult['FILE']['VIEWER'];
					  } else { ?>
						<div class="bx-file-icon-container-big <?= $arResult['FILE']['ICON_CLASS'] ?>">
							<div class="bx-file-icon-cover">
								<div class="bx-file-icon-corner"></div>
								<div class="bx-file-icon-corner-fix"></div>
								<div class="bx-file-icon-images"></div>
							</div>
							<div class="bx-file-icon-label"></div>
						</div>
					<?  } ?>
					</td>
					<td class="bx-shared-body-fileinfoblock">
						<h1 class="bx-shared-body-filename"><?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?></h1>
						<table>
							<tbody>
								<tr>
									<td class="bx-shared-body-fileinfo-param"><?= $component->getMessage('DISK_EXT_LINK_FILE_SIZE') ?>:</td>
									<td class="bx-shared-body-fileinfo-value"><?= CFile::formatSize($arResult['FILE']['SIZE']) ?></td>
								</tr>
								<tr>
									<td class="bx-shared-body-fileinfo-param"><?= $component->getMessage('DISK_EXT_LINK_FILE_UPDATE_TIME') ?>:</td>
									<td class="bx-shared-body-fileinfo-value"><?= $arResult['FILE']['UPDATE_TIME'] ?></td>
								</tr>
								<tr class="bx-shared-body-fileinfo-buttons first">
									<td colspan="2">
										<a class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green" href="<?= $arResult['FILE']['DOWNLOAD_URL'] ?>"><?= $component->getMessage('DISK_EXT_LINK_FILE_DOWNLOAD') ?></a>
									</td>
								</tr>
								<tr class="bx-shared-body-fileinfo-buttons ">
									<td colspan="2">

										<div class="bx-disk-sidebar-shared-title"><?= $component->getMessage('DISK_EXT_LINK_FILE_COPY_LINK') ?></div>
										<div class="bx-disk-sidebar-shared-inlink-container">
											<div class="bx-disk-sidebar-shared-inlink-input-container">
												<input id="external-link-copy" class="bx-disk-sidebar-shared-inlink-input" value="<?= $arResult['FILE']['VIEW_URL'] ?>" type="text">
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
		</div>
<? } elseif($arResult['PROTECTED_BY_PASSWORD']){ ?>
	<? $this->getComponent()->includeComponentTemplate('protected_by_password'); ?>
<? } ?>

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