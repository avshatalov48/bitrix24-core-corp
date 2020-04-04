<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (strlen($arResult["FatalError"]) > 0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>
	<form method="post" action="<?= POST_FORM_ACTION_URI ?>" name="rms_filter_form">
	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<thead>
		<tr class="intask-row">
			<th colspan="2" class="intask-cell"><?= GetMessage("INTASK_C31T_SEARCH") ?></th>
		</tr>
		</thead>
		<tbody>
			<tr class="intask-row">
				<td class="intask-cell" align="right"><?= GetMessage("INTASK_C31T_ROOM") ?>:</td>
				<td class="intask-cell">
					<select name="flt_id">
						<option value=""><?= GetMessage("INTASK_C31T_ANY") ?></option>
						<?foreach ($arResult["MEETINGS_ALL"] as $key => $value):?>
							<option value="<?= $key ?>"<?= ($key == $_REQUEST["flt_id"] ? " selected" : "") ?>><?= $value ?></option>
						<?endforeach;?>
					</select>
				</td>
			</tr>
			<tr class="intask-row">
				<td class="intask-cell" align="right"><?= GetMessage("INTASK_C31T_DURATION") ?>:</td>
				<td class="intask-cell">
					<select name="flt_duration">
						<?
						for ($i = 0; $i < 12; $i++)
						{
							if ($i > 0)
							{
								?><option value="<?= $i ?>.0"<?= ($_REQUEST["flt_duration"] == $i ? " selected" : "") ?>><?= $i ?>.0</option><?
							}
							?><option value="<?= $i ?>.5"<?= ($_REQUEST["flt_duration"] == $i.".5" ? " selected" : "") ?>><?= $i ?>.5</option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr class="intask-row">
				<td class="intask-cell" align="right"><?= GetMessage("INTASK_C31T_SPLACE") ?>:</td>
				<td class="intask-cell">
					<input type="text" name="flt_uf_place" <? if (is_scalar($_REQUEST['flt_uf_place'])): ?>value="<?= HtmlSpecialCharsbx($_REQUEST["flt_uf_place"]) ?>"<? endif ?> size="5">
				</td>
			</tr>
			<tr class="intask-row">
				<td class="intask-cell" align="right"><?= GetMessage("INTASK_C31T_SDATE") ?>:</td>
				<td class="intask-cell">
					<?= GetMessage("INTASK_C31T_FROM") ?>
					<?
					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:main.calendar',
						'',
						array(
							'SHOW_INPUT' => 'Y',
							'FORM_NAME' => "rms_filter_form",
							'INPUT_NAME' => "flt_date_from",
							'INPUT_VALUE' => (is_scalar($_REQUEST['flt_date_from']) && StrLen($_REQUEST["flt_date_from"]) > 0 ? $_REQUEST["flt_date_from"] : Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE))),
							'SHOW_TIME' => 'N',
							'INPUT_ADDITIONAL_ATTR' => $strAdd,
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
					<?= GetMessage("INTASK_C31T_TO") ?>
					<?
					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:main.calendar',
						'',
						array(
							'SHOW_INPUT' => 'Y',
							'FORM_NAME' => "rms_filter_form",
							'INPUT_NAME' => "flt_date_to",
							'INPUT_VALUE' => (is_scalar($_REQUEST['flt_date_to']) && StrLen($_REQUEST["flt_date_to"]) > 0 ? $_REQUEST["flt_date_to"] : Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE))),
							'SHOW_TIME' => 'N',
							'INPUT_ADDITIONAL_ATTR' => $strAdd,
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</td>
			</tr>
			<tr class="intask-row">
				<td class="intask-cell" align="right"><?= GetMessage("INTASK_C31T_STIME") ?>:</td>
				<td class="intask-cell">
					<?= GetMessage("INTASK_C31T_FROM") ?>
					<select name="flt_time_from">
						<?
						$s = (is_scalar($_REQUEST['flt_time_from']) && StrLen($_REQUEST["flt_time_from"]) > 0 ? $_REQUEST["flt_time_from"] : "08:00");
						for ($i = 0; $i < 48; $i++)
						{
							$t = IntVal($i / 2);
							if ($t < 10)
								$t = "0".$t;
							$t .= ":";
							if ($i % 2 == 0)
								$t .= "00";
							else
								$t .= "30";
							?><option value="<?= $t ?>"<?= ($s == $t ? " selected" : "") ?>><?= $t ?></option><?
						}
						?>
					</select>
					<?= GetMessage("INTASK_C31T_TO") ?>
					<select name="flt_time_to">
						<?
						$s = (is_scalar($_REQUEST['flt_time_to']) && StrLen($_REQUEST["flt_time_to"]) > 0 ? $_REQUEST["flt_time_to"] : "19:00");
						for ($i = 0; $i < 48; $i++)
						{
							$t = IntVal($i / 2);
							if ($t < 10)
								$t = "0".$t;
							$t .= ":";
							if ($i % 2 == 0)
								$t .= "00";
							else
								$t .= "30";
							?><option value="<?= $t ?>"<?= ($s == $t ? " selected" : "") ?>><?= $t ?></option><?
						}
						?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	<table width="100%">
	<tr>
		<td align="center"><input type="submit" value="<?= GetMessage("INTASK_C31T_SEARCH") ?>"></td>
	</tr>
	</table>
	</form>
	<br>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<thead>
		<tr class="intask-row">
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FDATE") ?></th>
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FROOM") ?></th>
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FFLOOR") ?></th>
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FPLACE") ?></th>
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FFREE") ?></th>
			<th class="intask-cell"><?= GetMessage("INTASK_C31T_FRESERVE") ?></th>
		</tr>
		</thead>
		<tbody>
		<?if (Count($arResult["ITEMS"]) > 0):?>
			<?foreach ($arResult["ITEMS"] as $arItem):?>
				<tr class="intask-row<?=(($iCount % 2) == 0 ? " selected" : "")?>" onmouseover="this.className+=' intask-row-over';" onmouseout="this.className=this.className.replace(' intask-row-over', '');" ondblclick="window.location='<?= CUtil::addslashes($arItem["URI"]) ?>'">
					<td class="intask-cell" valign="top"><?= $arItem["FREE_DATE"] ?></td>
					<td class="intask-cell" valign="top"><?= $arResult["MEETINGS_LIST"][$arItem["MEETING_ID"]]["NAME"] ?></td>
					<td class="intask-cell" valign="top" align="right"><?= $arResult["MEETINGS_LIST"][$arItem["MEETING_ID"]]["UF_FLOOR"] ?></td>
					<td class="intask-cell" valign="top" align="right"><?= $arResult["MEETINGS_LIST"][$arItem["MEETING_ID"]]["UF_PLACE"] ?></td>
					<td class="intask-cell" valign="top"><?= $arItem["FREE_FROM"] ?> - <?= $arItem["FREE_TO"] ?></td>
					<td class="intask-cell" valign="top" align="center"><a href="<?= $arItem["URI"] ?>"><img src="/bitrix/components/bitrix/intranet.reserve_meeting.search/templates/.default/images/element_edit.gif" width="20" height="20" border="0" alt=""></a></td>
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

	<?
}
?>