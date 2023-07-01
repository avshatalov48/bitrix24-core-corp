<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var $this \CBitrixComponentTemplate
 */
global $APPLICATION;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main;

Main\UI\Extension::load(['ui.textcrop', 'ui.design-tokens', 'ui.fonts.opensans']);

$this->setFrameMode(true);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

\CJSCore::Init([
	"loader",
	"voximplant.common"
]);
if(Loader::includeModule('rest'))
{
	\CJSCore::Init(["marketplace"]);
}

\Bitrix\Main\UI\Extension::load(["ui.icons", "applayout", "ui.hint"]);

if (Loader::includeModule('imopenlines'))
{
	\Bitrix\Main\UI\Extension::load(['imopenlines.create-line', 'ui.info-helper']);
}

if(!empty($arResult["ADDITIONAL_STYLES"]))
{
	echo "<style>";
	echo implode(PHP_EOL, $arResult["ADDITIONAL_STYLES"]);
	echo "</style>";
}
?>
<script id="intranet-appearance-script">
	/*Took from script.js for correct composite work*/
	var params = <?=CUtil::PhpToJSObject($arResult["JS_PARAMS"])?>;
	BX.ready(function() {
		new BX.ContactCenter.Init(params);
	})
</script>
<div class="intranet-contact-block">
	<div class="intranet-contact-wrap" id="intranet-contact-wrap">
		<div class="intranet-contact-list" id="intranet-contact-list">
			<?
			foreach ($arResult["ITEMS"] as $moduleId => $module)
			{
				foreach($module as $code => $item)
				{
				$cropTextID = \Bitrix\Main\Security\Random::getString(5);
				?>
					<div class="intranet-contact-center-item-block">
						<div class="intranet-contact-item<?=($item["SELECTED"] ? " intranet-contact-item-selected "  . ($item["COLOR_CLASS"] ?? '') : "")?>"
							title="<?=$item["NAME"]?>"
							data-module="<?=$moduleId?>"
							data-item="<?=$code?>"
							<? if (!empty($item["LIST"])): ?> id="feed-add-post-form-link-text-<?=$code?>" <? endif ?>
							<? if (!empty($item["ONCLICK"])): ?>onclick="<?=$item["ONCLICK"]?>" <? endif ?>
						>
							<div class="intranet-contact-logo-container">
								<span class="intranet-contact-logo <?=$item["LOGO_CLASS"]?>"><i></i></span>
							</div>
							<div class="intranet-contact-name">
								<div class="intranet-contact-name-text" data-crop="crop_<?=$cropTextID?>"><?=$item["NAME"]?></div>
							</div>
							<?php if (isset($item["IS_NEW"]) && $item["IS_NEW"] === true):
								$phrase = $item["NEW_PHRASE"] ?? 'CONTACT_CENTER_NEW_LABEL';
								$className = $item["SELECTED"]
									? 'intranet-contact-center-item-label-new-active'
									: 'intranet-contact-center-item-label-new';
								$textClassName = $item["SELECTED"]
									? 'intranet-contact-center-item-label-new-text-active'
									: 'intranet-contact-center-item-label-new-text';
								?>
								<div data-role="item-new-label" class="<?=$className?>">
									<div class="<?=$textClassName?>"><?=htmlspecialcharsbx(Loc::getMessage($phrase))?></div>
								</div>
							<?php endif;?>
						</div>
					</div>
					<script>
						BX.ready(function ()
						{
							let text = new BX.UI.TextCrop({
								rows: 2,
								target: document.querySelector('[data-crop="crop_<?=$cropTextID?>"] '),
							});
							text.init();
						});
					</script>
				<?
				}
			}
			?>
		</div>
		<div class="intranet-contact-center-title"><?=Loc::getMessage("CONTACT_CENTER_PARTNER_SOLUTIONS");?></div>
		<div class="intranet-contact-list" id="intranet-contact-rest-list">
			<?
			foreach ($arResult["REST_ITEMS"] as $moduleId => $module)
			{
				foreach($module as $code => $item)
				{
					?>
					<div class="intranet-contact-center-item-block">
						<div class="intranet-contact-item<?=($item["SELECTED"]
							? " intranet-contact-item-selected " . ($item["COLOR_CLASS"] ?? '')
							: "")
						?>"
							title="<?=\Bitrix\Main\Text\HtmlFilter::encode($item["NAME"])?>"
							data-module="<?=$moduleId?>"
							data-item="<?=$code?>"
							<? if (!empty($item["LIST"])): ?> id="feed-add-post-form-link-text-<?=$code?>" <? endif ?>
							<? if (!empty($item["ONCLICK"])): ?>onclick="<?=$item["ONCLICK"]?>" <? endif ?>
						>
							<?php if (isset($item['MARKETPLACE_APP'])): ?>
								<div class="intranet-contact-logo-container">
									<div class="intranet-marketplace-item-image" <?=$item['IMAGE'] ? "style=\"background-image: url(".$item['IMAGE'].");\"" : '';?>></div>
								</div>
							<?php else: ?>
								<div class="intranet-contact-logo-container">
									<span class="intranet-contact-logo <?=$item["LOGO_CLASS"]?>"><i <?= (!empty($item['IMAGE']) ? "style=\"background-image: url(".$item['IMAGE']."); background-color: ". $item['COLOR'] . "\"" : '')?>></i></span>
								</div>
							<?php endif; ?>
							<div class="intranet-contact-name <?=(isset($item['MARKETPLACE_APP']) ? 'intranet-contact-marketplace-name' : '')?>">
								<span class="intranet-contact-name-text"><?=$item["NAME"]?></span>
							</div>
						</div>
					</div>
					<?
				}
			}
			?>
		</div>
	</div>
	<?php if ($arResult['RULE_LINK'] !== ''): ?>
		<div class="intranet-contact-center-rules">
			<a class="intranet-contact-center-rules-link" href="<?= $arResult['RULE_LINK'] ?>" target="_blank"><?= Loc::getMessage("CONTACT_CENTER_RULES") ?></a>
		</div>
	<?php endif ?>
</div>
