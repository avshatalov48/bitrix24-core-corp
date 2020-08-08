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

	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td colspan="2"><?=$arResult['MEETING']['DESCRIPTION'] ?></td>
	</tr>
	<?if ($arResult["MEETING"]["UF_FLOOR"] <> ''):?>
	<tr>
		<td width="10%"><?= GetMessage("INTASK_C29T_FLOOR") ?>:</td>
		<td width="90%"><?= $arResult["MEETING"]["UF_FLOOR"]; ?></td>
	</tr>
	<?endif;?>
	<?if ($arResult["MEETING"]["UF_PLACE"] <> ''):?>
	<tr>
		<td><?= GetMessage("INTASK_C29T_PLACE") ?>:</td>
		<td><?= $arResult["MEETING"]["UF_PLACE"]; ?></td>
	</tr>
	<?endif;?>
	<?if ($arResult["MEETING"]["UF_PHONE"] <> ''):?>
	<tr>
		<td><?= GetMessage("INTASK_C29T_PHONE") ?>:</td>
		<td><?= $arResult["MEETING"]["UF_PHONE"]; ?></td>
	</tr>
	<?endif;?>
	</table>
	<br>

	<form method="post" action="<?= POST_FORM_ACTION_URI ?>" enctype="multipart/form-data" name="meeting_reserve">
	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<tbody>
			<tr>
				<td align="right"><span class="red_star">*</span><?= GetMessage("INTASK_C29T_DATE") ?>:</td>
				<td><?
					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:main.calendar',
						'',
						array(
							'SHOW_INPUT' => 'Y',
							'FORM_NAME' => "meeting_reserve",
							'INPUT_NAME' => "start_date",
							'INPUT_VALUE' => $arResult["Item"]["StartDate"],
							'SHOW_TIME' => 'N',
							'INPUT_ADDITIONAL_ATTR' => $strAdd,
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_AUTHOR") ?>:</td>
				<td>
				<?
				$APPLICATION->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $arResult["Item"]["Author_ID"],
						"HTML_ID" => "reserve_meeting_reserve".$arResult["Item"]["Author_ID"],
						"NAME" => htmlspecialcharsback($arResult["Item"]["Author_NAME"]),
						"LAST_NAME" => htmlspecialcharsback($arResult["Item"]["Author_LAST_NAME"]),
						"SECOND_NAME" => htmlspecialcharsback($arResult["Item"]["Author_SECOND_NAME"]),
						"LOGIN" => htmlspecialcharsback($arResult["Item"]["Author_LOGIN"]),
						"USE_THUMBNAIL_LIST" => "Y",
						"THUMBNAIL_LIST_SIZE" => 30,
						"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
						"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PM_URL"],
						"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				?>				
				</td>
			</tr>
			<tr>
				<td align="right"><span class="red_star">*</span><?= GetMessage("INTASK_C29T_FROM_TIME") ?>:</td>
				<td>
					<select name="start_time" onchange="RMR_CalcEndTime()">
						<?
						$aMpM = IsAmPmMode();
						$s = ($arResult["Item"]["StartTime"] <> '' ? $arResult["Item"]["StartTime"] : "08:00");
						
						for ($i = 0; $i < 48; $i++)
						{
							$t = intval($i / 2);
							if ($aMpM)
							{
								if ($t >= 12)
								{
									$mt = 'pm';
									if ($t > 12)
									{
										$t -= 12;
									}
								}
								else
								{
									if ($t == 0)
									{
										$t = 12;
									}
									$mt = 'am';
								}
							}
							else
							{
								if ($t < 10)
								{
									$t = "0".$t;
								}
							}
							$t .= ":";
							if ($i % 2 == 0)
								$t .= "00";
							else
								$t .= "30";

							if (!empty($mt))
							{
								$t .= ' '.$mt;
							}
							?><option value="<?= $t ?>"<?= ($s == $t ? " selected" : "") ?>><?= $t ?></option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right"><span class="red_star">*</span><?= GetMessage("INTASK_C29T_DURATION") ?>:</td>
				<td>
					<select name="timeout_time" onchange="RMR_CalcEndTime()">
						<?
						for ($i = 0; $i < 12; $i++)
						{
							if ($i > 0)
							{
								?><option value="<?= $i ?>.0"<?= ($arResult["Item"]["TimeoutTime"] == $i ? " selected" : "") ?>><?= $i ?>.0</option><?
							}
							?><option value="<?= $i ?>.5"<?= ($arResult["Item"]["TimeoutTime"] == $i.".5" ? " selected" : "") ?>><?= $i ?>.5</option><?
						}
						?>
					</select> <?= GetMessage("INTASK_C29T_HOURS") ?>
				</td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_TO_TIME") ?>:</td>
				<td>
					<input type="text" name="end_time" readonly value="" size="5">
					<script language="JavaScript">
					<!--
					function RMR_CalcEndTime()
					{
						var aMpM = BX.isAmPmMode();
						if (!document.meeting_reserve.start_time || !document.meeting_reserve.timeout_time)
							return;

						var s = document.meeting_reserve.start_time[document.meeting_reserve.start_time.selectedIndex].value;
						var t = document.meeting_reserve.timeout_time[document.meeting_reserve.timeout_time.selectedIndex].value;

						var h = s.split(":");
						if (aMpM)
						{
							h[1] = h[1].split(' ');
							var mt = h[1][1];
							h[1] = h[1][0];
							if (h[0] < 12 && mt == 'pm')
							{
								h[0] = parseInt(h[0], 10) + 12;
							}
							else if (h[0] == 12 && mt == 'am')
							{
								h[0] = 0;
							}
						}
						var r = t.split(".");
						var n1 = parseInt(h[0], 10) + parseInt(r[0], 10);
						var n2 = h[1];
						if (r[1] == "5")
						{
							if (n2 == "30")
							{
								n1 = n1 + 1;
								n2 = "00";
							}
							else
							{
								n2 = "30";
							}
						}
						if (n1 > 23)
							n1 = n1 - 24;
						if (n1 < 10 && !aMpM)
							n1 = "0" + n1;
						if (aMpM)
						{
							if (n1 >= 12)
							{
								mt = 'pm';
								if (n1 > 12)
								{
									n1 = n1 - 12;
								}
							}
							else
							{
								mt = 'am';
							}
							if (n1 == 0)
							{
								n1 = 12;
							}
						}

						document.meeting_reserve.end_time.value = n1 + ":" + n2 + (mt!=undefined ? ' '+ mt : '');
					}

					var RMR_Transs = {
						"DAILY1" : "<?= GetMessage("INTASK_C29T_TRANSS_D1") ?>", 
						"DAILY2" : "<?= GetMessage("INTASK_C29T_TRANSS_D2") ?>", 
						"WEEKLY1" : "<?= GetMessage("INTASK_C29T_TRANSS_W1") ?>", 
						"WEEKLY2" : "<?= GetMessage("INTASK_C29T_TRANSS_W2") ?>", 
						"MONTHLY1" : "<?= GetMessage("INTASK_C29T_TRANSS_M1") ?>", 
						"MONTHLY2" : "<?= GetMessage("INTASK_C29T_TRANSS_M2") ?>", 
						"YEARLY1" : "<?= GetMessage("INTASK_C29T_TRANSS_Y1") ?>", 
						"YEARLY2" : "<?= GetMessage("INTASK_C29T_TRANSS_Y2") ?>"
					};

					function RMR_RegularityChange(val)
					{
						var drc = document.getElementById('div_regularity_common');
						var drw = document.getElementById('div_regularity_weekly');
						if (val != "NONE")
						{
							drc.style.display = 'block';

							if (val == "WEEKLY")
								drw.style.display = 'block';
							else
								drw.style.display = 'none';

							document.getElementById('span_regularity_count_pref').innerText = RMR_Transs[val + "1"];
							document.getElementById('span_regularity_count_postf').innerText = RMR_Transs[val + "2"];
						}
						else
						{
							drc.style.display = 'none';
							drw.style.display = 'none';
						}
					}
					//-->
					</script>
				</td>
			</tr>
			<tr>
				<td align="right"><span class="red_star">*</span><?= GetMessage("INTASK_C29T_NAME") ?>:</td>
				<td><input type="text" name="name" value="<?= $arResult["Item"]["Name"] ?>" size="50"></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_PERSONS") ?>:</td>
				<td><input type="text" name="persons" value="<?= $arResult["Item"]["Persons"] ?>" size="5"></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_TYPE") ?>:</td>
				<td>
					<select name="res_type">
						<?
						$propEnums = CIBlockProperty::GetPropertyEnum($arResult["ALLOWED_ITEM_PROPERTIES"]["UF_RES_TYPE"]["ID"]);
						while ($arEnum = $propEnums->GetNext())
						{
							?><option value="<?= $arEnum["ID"] ?>"<?= ($arEnum["ID"] == $arResult["Item"]["ResType"] ? " selected" : "") ?>><?= $arEnum["VALUE"] ?></option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right" valign="top"><?= GetMessage("INTASK_C29T_DESCR") ?>:</td>
				<td><textarea name="description" rows="5" cols="50"><?= $arResult["Item"]["Description"] ?></textarea></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_PREP") ?>:</td>
				<td><input type="checkbox" name="prepare_room" value="Y" <?= ("Y" == $arResult["Item"]["PrepareRoom"] ? "checked" : "") ?>></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?= GetMessage("INTASK_C29T_REGULARITY") ?>:</td>
				<td valign="top">
					<select name="regularity" onchange="RMR_RegularityChange(this[this.selectedIndex].value)">
						<option value="NONE"<?= ($arResult["Item"]["Regularity"] == "NONE" ? " selected" : "") ?>><?= GetMessage("INTASK_C29T_REGULARITY_NONE") ?></option>
						<option value="DAILY"<?= ($arResult["Item"]["Regularity"] == "DAILY" ? " selected" : "") ?>><?= GetMessage("INTASK_C29T_REGULARITY_DAILY") ?></option>
						<option value="WEEKLY"<?= ($arResult["Item"]["Regularity"] == "WEEKLY" ? " selected" : "") ?>><?= GetMessage("INTASK_C29T_REGULARITY_WEEKLY") ?></option>
						<option value="MONTHLY"<?= ($arResult["Item"]["Regularity"] == "MONTHLY" ? " selected" : "") ?>><?= GetMessage("INTASK_C29T_REGULARITY_MONTHLY") ?></option>
						<option value="YEARLY"<?= ($arResult["Item"]["Regularity"] == "YEARLY" ? " selected" : "") ?>><?= GetMessage("INTASK_C29T_REGULARITY_YEARLY") ?></option>
					</select>
					<div id="div_regularity_common" style="display:none;">
						<br />

						<span id="span_regularity_count_pref"></span>
						<select name="regularity_count">
							<?for ($i = 1; $i < 35; $i++):?>
								<option value="<?= $i ?>"<?= ($arResult["Item"]["RegularityCount"] == $i ? " selected" : "") ?>><?= $i ?></option>
							<?endfor;?>
						</select>
						<span id="span_regularity_count_postf"></span><br />

						<div id="div_regularity_weekly" style="display:none;">
							<?
							$ar = array(GetMessage("INTASK_C29T_RWD_1"), GetMessage("INTASK_C29T_RWD_2"), GetMessage("INTASK_C29T_RWD_3"), GetMessage("INTASK_C29T_RWD_4"), GetMessage("INTASK_C29T_RWD_5"), GetMessage("INTASK_C29T_RWD_6"), GetMessage("INTASK_C29T_RWD_7"));
							$ar1 = Explode(",", $arResult["Item"]["RegularityAdditional"]);
							?>
							<?for ($i = 0; $i < 7; $i++):?>
								<input type="checkbox" name="regularity_additional[]" id="regularity_additional_<?= $i ?>" value="<?= $i ?>"<?= (In_Array($i, $ar1) ? " checked" : "") ?>><label for="regularity_additional_<?= $i ?>"><?= $ar[$i] ?></label>
							<?endfor;?>
						</div>

						<?= GetMessage("INTASK_C29T_REGULARITY_UNTIL") ?>:
						<?
						$GLOBALS["APPLICATION"]->IncludeComponent(
							'bitrix:main.calendar',
							'',
							array(
								'SHOW_INPUT' => 'Y',
								'FORM_NAME' => "meeting_reserve",
								'INPUT_NAME' => "regularity_end",
								'INPUT_VALUE' => $arResult["Item"]["RegularityEnd"],
								'SHOW_TIME' => 'N',
								'INPUT_ADDITIONAL_ATTR' => $strAdd,
							),
							null,
							array('HIDE_ICONS' => 'Y')
						);
						?>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<br>
	<input type="submit" name="save" value="<?= GetMessage("INTASK_C29T_SAVE") ?>">
	<?=bitrix_sessid_post()?>
	</form>
	<br />
	<script language="JavaScript">
	RMR_CalcEndTime();
	RMR_RegularityChange("<?= ($arResult["Item"]["Regularity"] == '' ? "NONE" : $arResult["Item"]["Regularity"]) ?>");
	</script>
	<?
}
?>