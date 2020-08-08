<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if (count($arParams["BUTTONS"]) < 1)
	return;

?>
<div class="bx-interface-toolbar">
<table cellpadding="0" cellspacing="0" border="0" class="bx-interface-toolbar">
	<tr class="bx-top">
		<td class="bx-left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="bx-right"><div class="empty"></div></td>
	</tr>
	<tr>
		<td class="bx-left"><div class="empty"></div></td>
		<td class="bx-content">
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>

<?
$newBar = false;
$arMoreButtons = Array();
foreach($arParams["BUTTONS"] as $index=>$item):

	if(!empty($item["NEWBAR"]))
	{
		$newBar = true;
		continue;
	}

	if(!empty($item["SEPARATOR"]))
		continue;

	if($newBar || !empty($item["ALIGN"])):

		$onclick = "";
		if (mb_substr(mb_strtolower($item["LINK"]), 0, 11) == 'javascript:')
			$onclick = mb_substr($item["LINK"], 11);
		else
			$onclick = "jsUtils.Redirect([], '".CUtil::JSUrlEscape($item["LINK"])."')";

		$arMoreButtons[] = Array(
			"ICONCLASS"=> $item["ICON"],
			"TEXT"=> $item["TEXT"],
			"ONCLICK" => $onclick,
			"MENU" => array_key_exists("MENU", $item) ? $item["MENU"] : false
		);

	elseif(!empty($item["MENU"])):?>
			<td>
				<script type="text/javascript">
				var jsMnu_<?=$arParams["TOOLBAR_ID"].'_'.$index?> = <?=CUtil::PhpToJSObject($item["MENU"])?>;
				</script>
				<a href="javascript:void(0);" hidefocus="true" 
					onclick="this.blur(); jsPopup_<?=$arParams["TOOLBAR_ID"]?>.ShowMenu(this, jsMnu_<?=$arParams["TOOLBAR_ID"].'_'.$index?>); return false;" 
					title="<?=$item["TITLE"]?>" class="bx-context-button"><span class="bx-context-button-left"></span><?if(!empty($item["ICON"])):?><span class="bx-context-button-icon <?=$item["ICON"]?>"></span><?endif?><span class="bx-context-button-text"><?=$item["TEXT"]?></span><span class="bx-arrow" alt=""></span><span class="bx-context-button-right"></span></a></td>
<?		
	elseif($item["HTML"] <> ""):
?>
				<td><?=$item["HTML"]?></td>
<?
	else:
?>
				<td><a href="<?=$item["LINK"]?>" hidefocus="true" title="<?=$item["TITLE"]?>" <?=$item["LINK_PARAM"]?> class="bx-context-button"><span class="bx-context-button-left"></span><?if(!empty($item["ICON"])):?><span class="bx-context-button-icon <?=$item["ICON"]?>"></span><?endif?><span class="bx-context-button-text"><?=$item["TEXT"]?></span><span class="bx-context-button-right"></span></a></td>
<?
	endif;
endforeach;?>

				</tr>
			</table>
		</td>
		<td class="bx-right"><div class="empty"></div></td>
	</tr>
	<tr class="bx-bottom">
		<td class="bx-left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="bx-right"><div class="empty"></div></td>
	</tr>

	<tr class="bx-bottom-all">
		<td class="bx-left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="bx-right"><div class="empty"></div></td>
	</tr>
</table>

<script type="text/javascript">
var jsPopup_<?=$arParams["TOOLBAR_ID"]?> = new PopupMenu('Popup<?=$arParams["TOOLBAR_ID"]?>');
</script>


<?if (!empty($arMoreButtons)):?>

			<div class="bx-context-more-buttons">
			<script type="text/javascript">
				var jsMnu_<?=$arParams["TOOLBAR_ID"].'_more_buttons'?> = <?=CUtil::PhpToJSObject($arMoreButtons)?>;
				</script>
				<a href="javascript:void(0);" hidefocus="true"
					onclick="this.blur(); jsPopup_<?=$arParams["TOOLBAR_ID"]?>.ShowMenu(this, jsMnu_<?=$arParams["TOOLBAR_ID"].'_more_buttons'?>); return false;"
					class="bx-context-button"><span class="bx-context-button-left"></span><span class="bx-context-button-text"><?=GetMessage("TOOLBAR_MORE_BUTTONS")?></span><span class="bx-arrow" alt=""></span><span class="bx-context-button-right"></span></a>
			</div>

		<?endif;?>


</div>
