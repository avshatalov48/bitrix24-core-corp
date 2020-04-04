<?php
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

Loader::includeModule('ui');
Extension::load(array("ui.tilegrid", "ui.buttons"));

if ($arParams['NO_BACKGROUND'] == "Y")
{
	$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
	$bodyClasses = 'pagetitle-toolbar-field-view no-all-paddings no-background mp-slider-view';
	$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, $bodyClasses)));
}

if (is_array($arResult["ITEMS"])):
	$templateId = 'rest-'.md5($component->__name. $this->__name);
	?>

	<div class="rest-market-section">
		<? if(!empty($arParams['SECTION_TITLE'])):?>
			<div class="rest-market-section-title"><?=$arParams['SECTION_TITLE']?></div>
		<? endif;?>
		<div class="rest-market-grid-title"><?=Loc::getMessage('REST_MARKETPLACE_CATEGORY_BANNER_TITLE_APP')?></div>
		<div class="rest-market-grid" id="<?=$templateId?>"></div>
	</div>

	<script>
		BX.ready(function ()
		{
			BX.message(<?=Json::encode(
				[
					'REST_MARKETPLACE_CATEGORY_INSTALL_LINK_NAME' => Loc::getMessage("REST_MARKETPLACE_CATEGORY_INSTALL_LINK_NAME")
				]
			)?>);

			var gridPartners = new BX.TileGrid.Grid({
				id: 'grid_partners',
				container: document.getElementById('<?=$templateId?>'),
				itemHeight: 415,
				itemMinWidth: 270,
				itemType: 'BX.Rest.MarketPartners.TileGrid.Item',
				items: <?=Json::encode($arResult['ITEMS_JS'])?>
			});

			gridPartners.draw();
		});
	</script>

	<?
	if(
		isset($arParams['SHOW_BANNER_MARKETPLACE'])
		&& $arParams['SHOW_BANNER_MARKETPLACE'] == 'Y'
		&& !empty($arParams['MP_TAG_PATH'])
		&& !isset($arResult['SHOW_COMING_SOON'])
	):
	?>
		<a class="rest-configuration-banner" href="<?=$arParams['MP_TAG_PATH']?>">
			<div class="rest-configuration-banner-icon">
				<div class="rest-configuration-banner-icon-arrow"></div>
			</div>
			<div class="rest-configuration-banner-title">
				<span class="rest-configuration-banner-title-strong"><?= Loc::getMessage("REST_MARKETPLACE_CATEGORY_BANNER_TITLE") ?></span>
				<span class="rest-configuration-banner-title-light"><?= Loc::getMessage("REST_MARKETPLACE_CATEGORY_BANNER_DESCRIPTION") ?></span>
			</div>
		</a>
	<? endif;?>
<? endif; ?>
