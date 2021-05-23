<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"]) > 0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	$APPLICATION->AddHeadScript('/bitrix/components/bitrix/intranet.tasks/js/dialogs.js');
	$redStar = "<span class='red_star'>*</span>";

	$APPLICATION->IncludeComponent(
		"bitrix:intranet.tasks.view",
		$arResult["useTemplateId"],
		array(
			"arResult" => $arResult,
			"arParams" => $arParams,
		),
		false,
		array("HIDE_ICONS" => "Y")
	);

	?>
	<div id="intask_folder_dialog" class="intask-edit-dlg">
		<table class="intask-edit-dlg-frame">
			<tr>
				<td class="intask-title-cell" colspan="2">
					<table cellPadding="0" cellSpacing="0"><tr><td style="width: 10px; padding-left: 3px;"><img class="intask-iconkit intask-dd-dot" src="/bitrix/images/1.gif"></td><td class="intask-edit-dlg-title" id="intask_folder_dialog_title"></td><td id="intask_folder_dialog_close" class="intask-close" title="<?= GetMessage("INTST_CLOSE") ?>"><img class="intask-iconkit" src="/bitrix/images/1.gif"></td></tr></table>
				</td>
			<tr>
				<td align="left" class="intask-ed-lp" style="height: 23px"><?=$redStar?><?= GetMessage("INTST_FOLDER_NAME") ?>:</td><td class="intask-ed-lp"><input type="text" size="35" id="folder_name" /></td>
			</tr>
			<tr>
				<td colSpan="2" class="intask-edit-dlg-buttons">
					<a id="intask_folder_dialog_delete" href="javascript:void(0);"><?= GetMessage("INTST_DELETE") ?></a><input id="intask_folder_dialog_save" type="button" value="<?= GetMessage("INTST_SAVE") ?>"><input id="intask_folder_dialog_cancel" type="button" value="<?= GetMessage("INTST_CANCEL") ?>">
				</td>
			</tr>
		</table>
	</div>
	<?echo GetMessage('INTST_OUTLOOK_WARNING')?>
	<script>
	window.EC_MESS = <?= $arResult['BX_MESS'] ?>;
	setTimeout(
		function()
		{
			window.ITSDropdownMenu = new ITSDropdownMenu();
			window.ITSIntTaskDialog = new JSIntTaskDialog(<?=$arResult['JSConfig']?>, <?=$arResult['JSEvents']?>);
		},
		10
	);
	</script>
	<?
}
?>