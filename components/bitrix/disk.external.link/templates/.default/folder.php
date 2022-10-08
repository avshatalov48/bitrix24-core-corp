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

Loc::loadMessages(__DIR__ . '/template.php');
Loc::loadMessages(__FILE__);

global $APPLICATION;

Extension::load([
	'disk',
	'ui.viewer',
	'sidepanel',
	'disk.viewer.document-item',
	'ui.notification',
	'ui.fonts.opensans',
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
<html lang="en">
<head>
	<meta charset="<?= LANG_CHARSET ?>">
	<title><?= $component->getMessage('DISK_EXT_LINK_FOLDER_TITLE') ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?=SITE_CHARSET?>" />
	<? if(!$arResult['PROTECTED_BY_PASSWORD']){ ?>
		<meta content="<?= $arResult['FOLDER']['VIEW_URL'] ?>" property="og:url"/>
		<meta content="<?= $arResult['SITE_NAME'] ?>" property="og:site_name"/>
		<meta content="<?= htmlspecialcharsbx($arResult['FOLDER']['NAME']) ?>" property="og:title"/>
		<meta content="website" property="og:type"/>
		<meta content="<?= $component->getMessage('DISK_EXT_LINK_OPEN_FOLDER_GRAPH_MADE_BY_B24') ?>" property="og:description"/>
	<? } ?>
	<?
	$APPLICATION->ShowCSS();
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
	?>
</head>
<body style="background: none;">
	<div class="bx-shared-wrap">
		<div class="bx-shared-header">
			<div class="bx-shared-logo">
				<?= $component->getMessage('DISK_EXT_LINK_B24') ?>
			</div>
		</div>
		<? if (!($arResult['PROTECTED_BY_PASSWORD']) || $arResult['VALID_PASSWORD']){ ?>
		<div class="disk-object-container">
			<div class="disk-object-header-container">
				<h2 class="disk-object-header"><?= htmlspecialcharsbx($arResult['FOLDER']['NAME']) ?></h2>
			</div>
			<div class="disk-object-info-btn-wrap">
				<div class="disk-object-info-container">
					<div class="disk-object-size-container">
						<span class="disk-object-size"><?= $component->getMessage('DISK_EXT_LINK_FILE_SIZE') ?>:</span>
						<span class="disk-object-size-number"><?= CFile::formatSize($arResult['FOLDER']['SIZE']) ?></span>
					</div>
					<div class="disk-object-changed-container">
						<span class="disk-object-changed"><?= $component->getMessage('DISK_EXT_LINK_FILE_UPDATE_TIME') ?>:</span>
						<span class="disk-object-changed-number"><?= $arResult['FOLDER']['UPDATE_TIME'] ?></span>
					</div>
				</div>
				<? if(!empty($arResult['ENABLED_MOD_ZIP']) && !empty($arResult['FOLDER']['CREATED_BY']) && !empty($arResult['FOLDER']['SIZE'])) { ?>
				<div class="disk-object-button-container">
					<a href="<?= $arResult['FOLDER']['DOWNLOAD_URL'] ?>" class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green disk-object-download-button"><?= $component->getMessage('DISK_EXT_LINK_FOLDER_DOWNLOAD') ?></a>
				</div>
				<? } ?>
			</div>

			<div class="bx-shared-body">
			<?
			$APPLICATION->includeComponent('bitrix:disk.interface.toolbar', '', array(
				'CLASS_NAME' => 'disk-fake-toolbar',
				'TOOLBAR_ID' => 'fake_toolbar',
				'BUTTONS' => array(),
			));

			$APPLICATION->IncludeComponent(
				'bitrix:disk.breadcrumbs',
				'',
				array(
					'STORAGE_ID' => $arResult['FOLDER']['STORAGE_ID'],
					'CLASS_NAME' => 'disk-external-link-breadcrumbs',
					'BREADCRUMBS_ROOT' => $arResult['BREADCRUMBS_ROOT'],
					'BREADCRUMBS' => $arResult['BREADCRUMBS'],
					'MAX_BREADCRUMBS_TO_SHOW' => 100,
					'ENABLE_DROPDOWN' => false,
				)
			);
			?>

				<div class="bx-disk-interface-filelist">
				<?
				$APPLICATION->IncludeComponent(
					'bitrix:disk.interface.grid',
					'',
					array(
						'DATA_FOR_PAGINATION' => $arResult['GRID']['DATA_FOR_PAGINATION'],
						'GRID_ID' => $arResult['GRID']['ID'],
						'HEADERS' => $arResult['GRID']['HEADERS'],
						'SORT' => $arResult['GRID']['SORT'],
						'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
						'ROWS' => $arResult['GRID']['ROWS'],
						'FOOTER' => array(
							array(
								'title' => $component->getMessage('DISK_LABEL_GRID_TOTAL'),
								'value' => $arResult['GRID']['ROWS_COUNT'],
								'id' => 'bx-disk-total-grid-item',
							),
							array(
								'place_for_pagination' => true,
							),
							array(
								'custom_html' => '<td class="tar" style="width: 100%;">&nbsp;</td>',
							),
						),
						'DISABLE_SETTINGS' => true,
						'EDITABLE' => false,
						'ALLOW_EDIT' => false,
						'ALLOW_INLINE_EDIT' => false,
						'ACTION_ALL_ROWS' => false,
					),
					$component
				);
				?>
				</div>
			</div>
		</div>
		<script type="text/javascript">
		BX(function () {
			BX.message({disk_document_service: 'gdrive'});

			BX.remove(BX.findChildByClassName(BX('<?=$arResult['GRID']['ID']?>'), 'bx-disk-action'));
			BX.remove(BX.findChildByClassName(BX('<?=$arResult['GRID']['ID']?>'), 'bx-head-advanced-more'));

			<?php if($arResult['SESSION_EXPIRED']): ?>
				BX.UI.Notification.Center.notify({
					content: '<?= GetMessageJS('DISK_EXT_SESSION_EXPIRED') ?>',
				});
			<?php endif; ?>
		});
		</script>
		<? } elseif($arResult['PROTECTED_BY_PASSWORD']){ ?>
			<? $this->getComponent()->includeComponentTemplate('protected_by_password'); ?>
		<? } ?>
</div>
</body>
</html>