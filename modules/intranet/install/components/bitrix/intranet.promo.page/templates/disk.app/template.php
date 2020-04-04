<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

?>

<div class="intranet-promo-block">
	<div class="intranet-promo-block-title"><?=Loc::getMessage("INTRANET_DISK_PROMO_HEADER")?></div>
	<div class="intranet-promo-block-description"><?=Loc::getMessage("INTRANET_DISK_PROMO_DESC")?></div>
	<div class="intranet-promo-block-description"><?=Loc::getMessage("INTRANET_DISK_PROMO_DESC_SUB")?></div>
	<div class="intranet-promo-section intranet-promo-step-1">
		<div class="intranet-promo-step-num">1</div>
		<div class="intranet-promo-section-title">
			<?=Loc::getMessage(
				"INTRANET_DISK_PROMO_STEP1_TITLE",
				array(
					"#LINK_START#" => '<a href="'.$arResult["DOWNLOAD_PATH"].'" class="intranet-promo-section-link">',
					"#LINK_END#" => "</a>"
				)
			)?>
		</div>
	</div>
	<div class="intranet-promo-section intranet-promo-step-2">
		<div class="intranet-promo-step-num">2</div>
		<div class="intranet-promo-section-title"><?=Loc::getMessage("INTRANET_DISK_PROMO_STEP2_TITLE")?></div>
		<div class="intranet-promo-section-desc"><?=Loc::getMessage("INTRANET_DISK_PROMO_STEP2_DESC")?></div>
		<div class="intranet-promo-screenshot">
			<img class="intranet-promo-section-img" src="<?=$arResult["IMAGE_PATH"]?>">
		</div>
	</div>
</div>