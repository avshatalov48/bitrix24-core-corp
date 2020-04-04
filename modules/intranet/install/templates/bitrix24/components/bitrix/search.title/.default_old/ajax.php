<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (
	isset($_REQUEST["FORMAT"])
	&& $_REQUEST["FORMAT"] == 'json'
)
{
	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJSObject($arResult);
}
else
{
if(!empty($arResult["CATEGORIES"])):?>
	<table class="title-search-result">
		<colgroup>
			<col width="150px">
			<col width="*">
		</colgroup>
		<tbody>
			<?foreach($arResult["CATEGORIES"] as $category_id => $arCategory):?>
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
						<td class="title-search-all"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
					<?elseif(isset($arItem["ICON"])):?>
						<td class="title-search-item"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
					<?else:?>
						<td class="title-search-more"><a href="<?echo $arItem["URL"]?>"><?echo $arItem["NAME"]?></td>
					<?endif;?>
				</tr>
				<?endforeach;?>
			<?endforeach;?>
			<tr>
				<th class="title-search-separator">&nbsp;</th>
				<td class="title-search-separator">&nbsp;</td>
			</tr>
		</tbody>
	</table>
<?endif;
}
//echo "<pre>",htmlspecialcharsbx(print_r($arResult,1)),"</pre>";
?>