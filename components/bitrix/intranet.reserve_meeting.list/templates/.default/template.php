<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["FatalError"] <> '')
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if ($arResult["ErrorMessage"] <> '')
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>

	<?
	$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
	$APPLICATION->AddHeadScript('/bitrix/components/bitrix/intranet.reserve_meeting.list/js/dialogs.js');
	?>

	<script>
	//<![CDATA[
		if (typeof(phpVars) != "object")
			phpVars = {};
		if (!phpVars.titlePrefix)
			phpVars.titlePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - ';
		if (!phpVars.messLoading)
			phpVars.messLoading = '<?=CUtil::JSEscape(GetMessage("INTASK_C23T_LOAD"))?>';
		if (!phpVars.ADMIN_THEME_ID)
			phpVars.ADMIN_THEME_ID = '.default';

		if (typeof oObjectITS != "object")
			var oObjectITS = {};
	//]]>
	</script>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<thead>
		<tr class="intask-row">
			<th class="intask-cell" width="0%">&nbsp;</th>
			<th class="intask-cell" width="30%"><?= $arResult["ALLOWED_FIELDS"]["NAME"]["NAME"] ?></th>
			<th class="intask-cell" width="60%"><?= $arResult["ALLOWED_FIELDS"]["DESCRIPTION"]["NAME"] ?></th>
			<th class="intask-cell" width="0%"><?= $arResult["ALLOWED_FIELDS"]["UF_FLOOR"]["NAME"] ?></th>
			<th class="intask-cell" width="0%"><?= $arResult["ALLOWED_FIELDS"]["UF_PLACE"]["NAME"] ?></th>
			<th class="intask-cell" width="10%"><?= $arResult["ALLOWED_FIELDS"]["UF_PHONE"]["NAME"] ?></th>
		</tr>
		</thead>
		<tbody>
		<?if (Count($arResult["MEETINGS_LIST"]) > 0):?>
			<?foreach ($arResult["MEETINGS_LIST"] as $arMeeting):?>
				<tr class="intask-row<?=(($iCount % 2) == 0 ? " selected" : "")?>" onmouseover="this.className+=' intask-row-over';" onmouseout="this.className=this.className.replace(' intask-row-over', '');" ondblclick="window.location='<?= $arMeeting["URI"] ?>'" title="<?= $arMeeting["NAME"] ?>">
					<td class="intask-cell" valign="top" align="center">
						<script>
						function HideThisMenuS<?= $arMeeting["ID"] ?>()
						{
							if (window.ITSDropdownMenu != null)
							{
								window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_s<?= $arMeeting["ID"] ?>'], document.getElementById('intask_s<?= $arMeeting["ID"] ?>'))
								window.ITSDropdownMenu.PopupHide();
							}
							else
							{
								alert('NULL');
							}
						}
						oObjectITS['intask_s<?= $arMeeting["ID"] ?>'] = <?= CUtil::PhpToJSObject($arMeeting["ACTIONS"]) ?>;
						</script>
						<table cellpadding="0" cellspacing="0" border="0" class="intask-dropdown-pointer" onmouseover="this.className+=' intask-dropdown-pointer-over';" onmouseout="this.className=this.className.replace(' intask-dropdown-pointer-over', '');" onclick="if(window.ITSDropdownMenu != null){window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_s<?= $arMeeting["ID"] ?>'], document.getElementById('intask_s<?= $arMeeting["ID"] ?>'))}" title="<?= GetMessage("INTDT_ACTIONS") ?>" id="intask_table_s<?= $arMeeting["ID"] ?>"><tr>
							<td>
								<div class="controls controls-view show-action">
									<a href="javascript:void(0);" class="action">
										<div id="intask_s<?= $arMeeting["ID"] ?>" class="empty"></div>
									</a>
								</div></td>
						</tr></table>
					</td>
					<td class="intask-cell" valign="top"><a href="<?=$arMeeting["URI"]?>"><?= $arMeeting["NAME"] ?></a></td>
					<td class="intask-cell" valign="top"><?=$arMeeting['DESCRIPTION'] ?></td>
					<td class="intask-cell" valign="top" align="right"><?= $arMeeting["UF_FLOOR"] ?></td>
					<td class="intask-cell" valign="top" align="right"><?= $arMeeting["UF_PLACE"] ?></td>
					<td class="intask-cell" valign="top"><?= $arMeeting["UF_PHONE"] ?></td>
				</tr>
				<?$iCount++;?>
			<?endforeach;?>
		<?else:?>
			<tr class="intask-row">
				<td class="intask-cell" colspan="6" valign="top"><?= GetMessage("INTDT_NO_TASKS") ?></td>
			</tr>
		<?endif;?>
		</tbody>
	</table>
	<br />

	<script>
	setTimeout(
		function()
		{
			window.ITSDropdownMenu = new ITSDropdownMenu();
		},
		10
	);
	</script>
	<?
}
?>