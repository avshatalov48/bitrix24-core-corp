<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['VALUES']))
	return false;

foreach($arResult['VALUES'] as $arValue)
{
	switch ($arResult["TYPE_ID"])
	{
		case "PHONE":
			?>
			<div class="mobile-grid-title"><?= $arValue["COMPLEX_NAME"] ?></div>
			<div class="mobile-grid-block mobile-grid-data-phone-container" onclick="BX.MobileTools.phoneTo('<?= htmlspecialcharsbx($arValue['VALUE']) ?>')">
				<img class="mobile-grid-data-phone-icon" src="<?=$this->GetFolder()?>/images/icon-phone2x.png" srcset="<?=$this->GetFolder()?>/images/icon-phone2x.png 2x">
				<span class="mobile-grid-field-contact"><?= htmlspecialcharsbx($arValue['VALUE']) ?></span>
			</div>
			<?
			break;
		case "EMAIL":
			?>
			<div class="mobile-grid-title"><?= $arValue["COMPLEX_NAME"] ?></div>
			<div class="mobile-grid-block mobile-grid-data-mail-container">
				<a href="mailto:<?=$arValue['VALUE']?>" style="display: block;text-decoration: none">
					<img class="mobile-grid-data-mail-icon" src="<?=$this->GetFolder()?>/images/icon-email2x.png" srcset="<?=$this->GetFolder()?>/images/icon-email2x.png 2x">
					<span class="mobile-grid-field-contact"><?= $arValue['VALUE'] ?></span>
				</a>
			</div>
			<?
			break;
		default:
			?>
			<div class="mobile-grid-title"><?= ($lastName != $arResult["SHORT_NAMES"][$arValue["VALUE_TYPE"]] ? $arResult["SHORT_NAMES"][$arValue["VALUE_TYPE"]] : '') ?></div>
			<div class="mobile-grid-block mobile-grid-data-mail-container">
				<span class="mobile-grid-field-contact"><?=$arValue['TEMPLATE']?></span>
			</div>
			<?
	}
}
?>