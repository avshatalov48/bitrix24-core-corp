<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

\Bitrix\Main\UI\Extension::load(["ui.icons"]);
\CJSCore::Init("loader");
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
				?>
					<div class="intranet-contact-center-item-block">
						<div class="intranet-contact-item<?=($item["SELECTED"] ? " intranet-contact-item-selected "  . $item["COLOR_CLASS"] : "")?>"
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
</div>

<?
if ($arResult["SHOW_APP_BANNER"])
{
	?>
	<a class="intranet-contact-app-banner" href="javascript:void(0)" id="app-banner">
		<div class="intranet-contact-app-banner-icon">
			<div class="intranet-contact-app-banner-icon-arrow"></div>
		</div>
		<div class="intranet-contact-app-banner-title">
			<?= Loc::getMessage("CONTACT_CENTER_APP_BANNER_TITLE");?>
		</div>
	</a>
	<?
}
?>