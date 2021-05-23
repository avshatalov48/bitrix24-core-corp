<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if (is_array($arResult['SORT_FIELDS']) && !empty($arResult['SORT_FIELDS']))
{
?>
	<div id="bx-mobile-interface-sort-block" class="mobile-grid-field-list">
		<span class="mobile-grid-field-title"><?=GetMessage("M_SORT_TITLE")?></span>
		<div class="mobile-grid-field">
			<div class="mobile-grid-button-panel mobile-grid-button-sort">
				<a data-role="asc" href="javascript:void(0)" ontouchstart="BX.Mobile.Grid.Sort.selectOrder('asc')" <?if ($arResult["CURRENT_SORT_ORDER"] == "asc") echo 'class="mobile-grid-button-sort-selected"'?>><span class="mobile-grid-button-sort-icon"></span><span><?=GetMessage("M_SORT_ASC")?></span></a>
				<span class="mobile-grid-button-panel-divider"></span>
				<a data-role="desc" href="javascript:void(0)" ontouchstart="BX.Mobile.Grid.Sort.selectOrder('desc')" <?if ($arResult["CURRENT_SORT_ORDER"] == "desc") echo 'class="mobile-grid-button-sort-selected"'?>><span class="mobile-grid-button-sort-icon"></span><span><?=GetMessage("M_SORT_DESC")?></span></a>
			</div>
		</div>
		<span class="mobile-grid-field-title"><?=GetMessage("M_SORT_FIELDS")?></span>

		<?foreach($arResult['SORT_FIELDS'] as $row):
			if (!$row["sort"])
				continue;
			?>
			<div data-role="mobile-sort-item" class="mobile-grid-field <?if ($arResult["CURRENT_SORT_BY"] == $row["id"]) echo 'mobile-grid-field-selected'?>" data-sort-id="<?=$row["id"]?>">
				<div class="mobile-grid-field-textarea"><span class="mobile-grid-field-textarea-select"></span><?=$row["name"]?></div>
			</div>
		<?endforeach?>
	</div>
<?
}
else
{
	echo GetMessage("M_SORT_NO_FIELDS");
}

$arJsParams = array(
	"gridId" => $arParams["GRID_ID"],
	"eventName" => $arResult['EVENT_NAME']
);
?>

<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_SORT_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_SORT_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_SORT_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});
	BXMobileApp.UI.Page.TopBar.title.setText('<?=GetMessageJS("M_SORT_TITLE")?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	BX.Mobile.Grid.Sort.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	window.BXMobileApp.UI.Page.TopBar.updateButtons({
		ok: {
			type: "back_text",
			callback: function(){
				BX.Mobile.Grid.Sort.apply();
			},
			name: "<?=GetMessageJS("M_SORT_BUTTON")?>",
			bar_type: "navbar",
			position: "right"
		}
	});
</script>
