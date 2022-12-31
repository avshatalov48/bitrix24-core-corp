<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult["ITEMS"])):?>
<ul class="mp_top_nav_ul">
	<?foreach($arResult["ITEMS"] as $index => $arItem):?>
	<li class="mp_top_nav_ul_li <?=$arItem["PARAMS"]["class"]?> <?if ($arItem["SELECTED"] && !isset($_GET["app"]) && !isset($_GET["category"])):?>active<?endif?>">
		<a href="<?=($arItem["PARAMS"]["class"] == "category" ? "javascript:void(0)" : $arItem["LINK"])?>" <?if ($arItem["PARAMS"]["class"] == "category"):?>onclick="BX.addClass(this.parentNode, 'active');ShowCategoriesPopup(this);"<?endif?>>
			<span class="leftborder"></span><span class="icon"></span><?=$arItem["TEXT"]?>
			<?if ($arItem["PARAMS"]["class"] == "category"):?>
				<span class="arrow"></span>
			<?elseif($arItem["PARAMS"]["class"] == "updates"):?>
				<?
				$numUpdates = COption::GetOptionInt("bitrix24", "mp_num_updates", "");
				?>
				<span id="menu_num_updates">
					<?if ($numUpdates) echo " (".$numUpdates.")";?>
				</span>
			<?elseif($arItem["PARAMS"]["class"] == "sale" && $arResult["UNINSTALLED_PAID_APPS_COUNT"] > 0):?>
				(<?=$arResult["UNINSTALLED_PAID_APPS_COUNT"]?>)
			<?endif?>
			<span class="rightborder"></span>
		</a>
	</li>
	<?endforeach?>
</ul>
<?endif?>
