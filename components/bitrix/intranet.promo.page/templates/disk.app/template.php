<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);
?>

<div class="intranet-promo-block">
	<div class="intranet-promo-block-title"><?=Loc::getMessage("INTRANET_DISK_PROMO_HEADER")?></div>
	<div class="intranet-promo-block-description"><?=Loc::getMessage("INTRANET_DISK_PROMO_DESC_MSGVER_1")?></div>
	<div class="intranet-promo-block-description"><?=Loc::getMessage("INTRANET_DISK_PROMO_DESC_SUB")?></div>
	<div class="intranet-promo-section intranet-promo-step-1">
		<div class="intranet-promo-step-num">1</div>
		<div class="intranet-promo-section-title">
			<?php if ($arResult['PAGE'] === 'MACOS'):
				$macosIntelLink = !empty($arResult['DOWNLOAD_PATH']['macos']) && filter_var($arResult['DOWNLOAD_PATH']['macos'], FILTER_VALIDATE_URL)
					? htmlspecialcharsbx($arResult['DOWNLOAD_PATH']['macos'])
					: '#';
				$macosArmLink = !empty($arResult['DOWNLOAD_PATH']['macosArm']) && filter_var($arResult['DOWNLOAD_PATH']['macosArm'], FILTER_VALIDATE_URL)
					? htmlspecialcharsbx($arResult['DOWNLOAD_PATH']['macosArm'])
					: '#';
				?>
			<?= Loc::getMessage(
				'INTRANET_DISK_PROMO_STEP1_TITLE_MACOS',
				[
					'#INTEL_LINK_START#' => '<a href="'. $macosIntelLink .'" class="intranet-promo-section-link">',
					"#INTEL_LINK_END#" => '</a>',
					'#ARM64_LINK_START#' => '<a href="'. $macosArmLink .'" class="intranet-promo-section-link">',
					'#ARM64_LINK_END#' => '</a>'
				]
			) ?>
			<?php else:
				$windowsLink = !empty($arResult['DOWNLOAD_PATH']['windows']) && filter_var($arResult['DOWNLOAD_PATH']['windows'], FILTER_VALIDATE_URL)
					? htmlspecialcharsbx($arResult['DOWNLOAD_PATH']['windows'])
					: '#';
				?>
			<?= Loc::getMessage(
				'INTRANET_DISK_PROMO_STEP1_TITLE',
				[
					'#LINK_START#' => '<a href="'. $windowsLink .'" class="intranet-promo-section-link">',
					'#LINK_END#' => '</a>'
				]
			) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="intranet-promo-section intranet-promo-step-2">
		<div class="intranet-promo-step-num">2</div>
		<div class="intranet-promo-section-title"><?=Loc::getMessage("INTRANET_DISK_PROMO_STEP2_TITLE_MSGVER_1")?></div>
		<div class="intranet-promo-section-desc"><?=Loc::getMessage("INTRANET_DISK_PROMO_STEP2_DESC")?></div>
		<div class="intranet-promo-screenshot">
			<img class="intranet-promo-section-img" src="<?=$arResult["IMAGE_PATH"]?>">
		</div>
	</div>
</div>