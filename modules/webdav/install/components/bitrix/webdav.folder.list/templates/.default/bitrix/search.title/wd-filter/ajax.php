<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(strlen($INPUT_ID) <= 0)
	$INPUT_ID = "title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

if(!empty($arResult["CATEGORIES"])):?>
	<table class="title-search-result webdav-title-search-result">
		<?foreach($arResult["CATEGORIES"] as $category_id => $arCategory):?>
		<?if ($category_id === 'all') continue; ?>
			<tr>
				<th class="title-search-separator">&nbsp;</th>
				<td class="title-search-separator">&nbsp;</td>
			</tr>
			<?foreach($arCategory["ITEMS"] as $i => $arItem):?>
			<tr>
				<?if($i == 0):?>
					<th>&nbsp;<?echo $arCategory["TITLE"]?></th>
				<?else:?>
					<th>&nbsp;</th>
				<?endif?>

				<?if($category_id === "all"):?>
					<td class="title-search-all"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></a></td>
				<?elseif(isset($arItem["ICON"])):?>
					<td class="title-search-item"><img src="<?echo $arItem["ICON"]?>"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></a></td>
				<?else:?>
					<td class="title-search-more"><a href="javascript:jsControl_<?=$INPUT_ID?>.INPUT.form.submit();"><?echo $arItem["NAME"]?></a></td>
				<?endif;?>
			</tr>
			<?endforeach;?>
		<?endforeach;?>
		<tr>
			<th class="title-search-separator">&nbsp;</th>
			<td class="title-search-separator">&nbsp;</td>
		</tr>
	</table>
<?endif;
//echo "<pre>",htmlspecialcharsbx(print_r($arResult,1)),"</pre>";
?>
