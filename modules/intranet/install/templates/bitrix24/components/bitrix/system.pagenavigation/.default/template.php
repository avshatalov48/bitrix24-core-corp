<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);

$ClientID = 'navigation_'.$arResult['NavNum'];

if (!$arResult["NavShowAlways"])
{
	if ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false))
	{
		return;
	}
}
?>

<div class="navigation">
<?
$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
if($arResult["bDescPageNumbering"] === true)
{
	// to show always first and last pages
	$arResult["nStartPage"] = $arResult["NavPageCount"];
	$arResult["nEndPage"] = 1;

	$sPrevHref = '';
	if ($arResult["NavPageNomer"] < $arResult["NavPageCount"])
	{
		$bPrevDisabled = false;
		if ($arResult["bSavePage"])
		{
			$sPrevHref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]+1);
		}
		else
		{
			if ($arResult["NavPageCount"] == ($arResult["NavPageNomer"]+1))
			{
				$sPrevHref = $arResult["sUrlPath"].$strNavQueryStringFull;
			}
			else
			{
				$sPrevHref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]+1);
			}
		}
	}
	else
	{
		$bPrevDisabled = true;
	}

	$sNextHref = '';
	if ($arResult["NavPageNomer"] > 1)
	{
		$bNextDisabled = false;
		$sNextHref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]-1);
	}
	else
	{
		$bNextDisabled = true;
	}
	?>

	<div class="navigation-pages">
		<span class="navigation-title"><?=GetMessage("pages")?></span>

	<?
	$bFirst = true;
	$bPoints = false;
	do
	{
		$NavRecordGroupPrint = $arResult["NavPageCount"] - $arResult["nStartPage"] + 1;
		if ($arResult["nStartPage"] <= 2 || $arResult["NavPageCount"]-$arResult["nStartPage"] <= 1 || abs($arResult['nStartPage']-$arResult["NavPageNomer"])<=2)
		{

			if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):
				?><span class="navigation-current-page"><?=$NavRecordGroupPrint?></span><?
			elseif($arResult["nStartPage"] == $arResult["NavPageCount"] && $arResult["bSavePage"] == false):
				?>
				<a data-slider-ignore-autobinding="true" class="navigation-page-numb" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><?=$NavRecordGroupPrint?></a><?
			else:
				?>
				<a data-slider-ignore-autobinding="true" class="navigation-page-numb" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$NavRecordGroupPrint?></a><?
			endif;
			$bFirst = false;
			$bPoints = true;
		}
		else
		{
			if ($bPoints)
			{
				?><span class="navigation-points">...</span><?
				$bPoints = false;
			}
		}

		$arResult["nStartPage"]--;
	}
	while ($arResult["nStartPage"] >= $arResult["nEndPage"]);

	if ($arResult["bShowAll"])
	{
		if ($arResult["NavShowAll"])
		{
			?><a
			data-slider-ignore-autobinding="true"
			class="navigation-page-numb navigation-page-all"
			href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=0"
			><?=GetMessage("nav_paged")
			?></a><?
		}
		else
		{
			?><a
			data-slider-ignore-autobinding="true"
			class="navigation-page-numb navigation-page-all"
			href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=1"
			><?=GetMessage("nav_all")
			?></a><?
		}
	}
	?>
	</div>
	<div class="navigation-arrows">
		<<?
		if (!$bPrevDisabled):?>a href="<?=$sPrevHref;?>" data-slider-ignore-autobinding="true" id="<?=$ClientID?>_previous_page"<? else:?>span<?endif ?> class="navigation-button<?
		if ($bPrevDisabled):?> navigation-disabled<?endif ?>"><span class="navigation-text"><span class="navigation-ctrl-before">Ctrl</span><span class="navigation-text-cont"><?=GetMessage(
						"nav_prev"
					)?></span></span><?
			if (!$bPrevDisabled): ?></a><?
		else: ?></span><?
		endif ?><<?
		if (!$bNextDisabled):?>a href="<?=$sNextHref;?>" data-slider-ignore-autobinding="true" id="<?=$ClientID?>_next_page"<? else:?>span<?endif ?> class="navigation-button<?
		if ($bNextDisabled):?> navigation-disabled<?endif ?>"><span class="navigation-text"><span class="navigation-text-cont"><?=GetMessage(
						"nav_next"
					)?></span><span class="navigation-ctrl-after">Ctrl</span></span><?
			if (!$bNextDisabled): ?></a><?
	else:?></span><?
	endif ?>
	</div>
	<?
}
else
{
	// to show always first and last pages
	$arResult["nStartPage"] = 1;
	$arResult["nEndPage"] = $arResult["NavPageCount"];

	$sPrevHref = '';
	if ($arResult["NavPageNomer"] > 1)
	{
		$bPrevDisabled = false;

		if ($arResult["bSavePage"] || $arResult["NavPageNomer"] > 2)
		{
			$sPrevHref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]-1);
		}
		else
		{
			$sPrevHref = $arResult["sUrlPath"].$strNavQueryStringFull;
		}
	}
	else
	{
		$bPrevDisabled = true;
	}

	$sNextHref = '';
	if ($arResult["NavPageNomer"] < $arResult["NavPageCount"])
	{
		$bNextDisabled = false;
		$sNextHref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]+1);
	}
	else
	{
		$bNextDisabled = true;
	}
	?>

	<div class="navigation-pages">
		<span class="navigation-title"><?=GetMessage("pages")?></span><?

	$bFirst = true;
	$bPoints = false;
	do
	{
		if ($arResult["nStartPage"] <= 2 || $arResult["nEndPage"]-$arResult["nStartPage"] <= 1 || abs($arResult['nStartPage']-$arResult["NavPageNomer"])<=2)
		{
			if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):
				?><span class="navigation-current-page"><?=$arResult["nStartPage"]?></span><?
			elseif ($arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false):
				?><a
				data-slider-ignore-autobinding="true"
				class="navigation-page-numb"
				href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><?=$arResult["nStartPage"]?></a><?
			else:
				?><a
				data-slider-ignore-autobinding="true"
				class="navigation-page-numb"
				href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$arResult["nStartPage"]?></a><?
			endif;
			$bFirst = false;
			$bPoints = true;
		}
		else
		{
			if ($bPoints)
			{
				?><span class="navigation-points">...</span><?
				$bPoints = false;
			}
		}

		$arResult["nStartPage"]++;
	}
	while ($arResult["nStartPage"] <= $arResult["nEndPage"]);

	if ($arResult["bShowAll"])
	{
		if ($arResult["NavShowAll"])
		{
			?><a
			data-slider-ignore-autobinding="true"
			class="navigation-page-numb navigation-page-all"
			href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=0"
				><?=GetMessage("nav_paged")
			?></a><?
		}
		else
		{
			?><a
			data-slider-ignore-autobinding="true"
			class="navigation-page-numb navigation-page-all"
			href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=1"
				><?=GetMessage("nav_all")
			?></a><?
		}
	}
?>
	</div>
	<div class="navigation-arrows">
		<<?
		if (!$bPrevDisabled):?>a href="<?=$sPrevHref;?>" data-slider-ignore-autobinding="true" id="<?=$ClientID?>_previous_page"<? else:?>span<?endif ?> class="navigation-button<?
		if ($bPrevDisabled):?> navigation-disabled<?endif ?>"><span class="navigation-text"><span class="navigation-ctrl-before">Ctrl</span><span class="navigation-text-cont"><?=GetMessage(
						"nav_prev"
					)?></span></span><?
			if (!$bPrevDisabled): ?></a><?
		else: ?></span><?
		endif ?><<?
		if (!$bNextDisabled):?>a href="<?=$sNextHref;?>" data-slider-ignore-autobinding="true" id="<?=$ClientID?>_next_page"<? else:?>span<?endif ?> class="navigation-button<?
		if ($bNextDisabled):?> navigation-disabled<?endif ?>"><span class="navigation-text"><span class="navigation-text-cont"><?=GetMessage(
						"nav_next"
					)?></span><span class="navigation-ctrl-after">Ctrl</span></span><?
			if (!$bNextDisabled): ?></a><?
	else:?></span><?
	endif ?>
	</div>
	<?
}
?>

</div>

<?CJSCore::Init()?>
<script type="text/javascript">
	BX.bind(document, "keydown", function (event) {

		event = event || window.event;
		if (!event.ctrlKey)
			return;

		var target = event.target || event.srcElement;
		if (target && target.nodeName && (target.nodeName.toUpperCase() == "INPUT" || target.nodeName.toUpperCase() == "TEXTAREA"))
			return;

		var key = (event.keyCode ? event.keyCode : (event.which ? event.which : null));
		if (!key)
			return;

		var link = null;
		if (key == 39)
			link = BX('<?=$ClientID?>_next_page');
		else if (key == 37)
			link = BX('<?=$ClientID?>_previous_page');

		if (link && link.href)
			document.location = link.href;
	});
</script>