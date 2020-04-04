<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 
?>
<?$this->SetViewTarget("bx-mobile-back-btn", 100);
?>
<a href="<?=SITE_DIR?>m/" class="ui-btn-left" data-iconpos="notext" data-role="button" data-icon="back" data-direction="reverse"><?=GetMessage("BM_TO_MAIN")?></a>
<?
$this->EndViewTarget();
?>
<form action="<?=$APPLICATION->GetCurPage()?>" method="get" data-ajax="false">
	<input type="search" placeholder="<?=GetMessage("BM_SEARCH_HOLDER")?>" name="name" value="<?=$arResult["search_name"]?>" />
	<input type="hidden" name="dep" value="<?=$arResult["cur_dep"]?>" />
</form>
<?
if($_REQUEST["AJAX_CALL"] == "Y")
	$APPLICATION->RestartBuffer();
?>
<div id="m-users">
<?if($_REQUEST["AJAX_CALL"] != "Y")
{
	?><h2><?=$arResult["deps"][$arResult["cur_dep"]]["NAME"]?></h2><?
}
if(!empty($arResult["users"]))
{
	$prL = "";
	foreach($arResult["users"] as $id => $item)
	{
		if(substr($item["LAST_NAME"], 0, 1) != $prL)
		{
			$prLL = $prL;
			$prL = substr($item["LAST_NAME"], 0, 1);
			if(!($_REQUEST["AJAX_CALL"] == "Y" && strlen($prLL) <= 0))
			{
				?>
				<div style="color: #333399; border-bottom: 2px #DDDDDD solid; margin: 5px; font-weight: bold; font-size: 16px;"><?=$prL?></div>
				<?
			}
		}
		?>
			<div class="m-line">
			<table width="100%">
				<tr>
					<td><a href="<?=$item["URL"]?>"><div class="bx-m-user-image"><img src="<?=($item["PERSONAL_PHOTO"]? $item["PERSONAL_PHOTO_S"]["src"]: "/bitrix/images/intranet/employees/nopic_user_50_noborder.gif")?>"/></div></a></td>
					<td style="padding-left: 10px;" width="100%">
						<?if ($item["CHAT_URL"]):?>
							<a class="bx-icon-message" href="<?= $item["CHAT_URL"] ?>" data-ajax="false"><?=GetMessage("BM_WRITE")?></a>
						<?endif;?>
						<b><a href="<?=$item["URL"]?>"<?if($item["IS_ONLINE"]):?> class="bx-user-online"<?endif;?>><?=$item["LAST_NAME"].' '.$item['NAME']?></a></b><br />
						<span style="font-size:11px;">
						<b><?=$item["WORK_POSITION"]?></b><br />
						E-mail: <a href="mailto:<?=$item["EMAIL"];?>"><?=$item["EMAIL"];?></a><br />
						<?=GetMessage("BM_DEPARTMENT")?>: <a href="<?=$arResult["deps"][$item["UF_DEPARTMENT"][0]]["URL"];?>"><?=$arResult["deps"][$item["UF_DEPARTMENT"][0]]["NAME"];?></a><br />
						</span>
					</td>
				</tr>
			</table>
			</div>
		<?
	}
	echo $arResult["NAV_STRING"];
}
else
	ShowError(GetMessage("BM_NO_USERS"));
?>
</div>
<?
if($_REQUEST["AJAX_CALL"] == "Y")
	die();
?>