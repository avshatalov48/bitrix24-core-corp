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
		<td width="10%"><?= GetMessage("INTASK_C25T_FLOOR") ?>:</td>
		<td width="90%"><?= $arResult["MEETING"]["UF_FLOOR"] ?></td>
	</tr>
	<?endif;?>
	<?if ($arResult["MEETING"]["UF_PLACE"] <> ''):?>
	<tr>
		<td><?= GetMessage("INTASK_C25T_PLACE") ?>:</td>
		<td><?= $arResult["MEETING"]["UF_PLACE"] ?></td>
	</tr>
	<?endif;?>
	<?if ($arResult["MEETING"]["UF_PHONE"] <> ''):?>
	<tr>
		<td><?= GetMessage("INTASK_C25T_PHONE") ?>:</td>
		<td><?= $arResult["MEETING"]["UF_PHONE"] ?></td>
	</tr>
	<?endif;?>
	</table>
	<br>

	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom"><a href="<?= $arResult["PRIOR_WEEK_URI"] ?>">&lt;&lt; <?= GetMessage("INTASK_C25T_PRIOR_WEEK") ?></a></td>
		<form method="GET" action="<?= $arResult["MEETING_URI"] ?>" name="meeting_date_select">
		<input type="hidden" name="<?= $arParams["PAGE_VAR"] ?>" value="meeting">
		<input type="hidden" name="<?= $arParams["MEETING_VAR"] ?>" value="<?= $arResult["MEETING"]["ID"] ?>">
		<td align="center"><?
			$GLOBALS["APPLICATION"]->IncludeComponent(
				'bitrix:main.calendar',
				'',
				array(
					'SHOW_INPUT' => 'Y',
					'FORM_NAME' => "meeting_date_select",
					'INPUT_NAME' => "week_start",
					'INPUT_VALUE' => $arResult["WEEK_START"],
					'SHOW_TIME' => 'N',
					'INPUT_ADDITIONAL_ATTR' => $strAdd,
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		?> &nbsp;<input type="submit" value="<?= GetMessage("INTASK_C25T_SET") ?>"></td>
		</form>
		<td align="right" valign="bottom"><a href="<?= $arResult["NEXT_WEEK_URI"] ?>"><?= GetMessage("INTASK_C25T_NEXT_WEEK") ?> &gt;&gt;</a></td>
	</tr>
	</table>
	<br>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<thead>
			<tr class="intask-row">
				<th class="intask-cell" align="center" width="0%">&nbsp;</th>
				<?
				$ar = array(GetMessage("INTASK_C25T_D1"), GetMessage("INTASK_C25T_D2"), GetMessage("INTASK_C25T_D3"), GetMessage("INTASK_C25T_D4"), GetMessage("INTASK_C25T_D5"), GetMessage("INTASK_C25T_D6"), GetMessage("INTASK_C25T_D7"));
				?>
				<?for ($i = 0; $i < 7; $i++):?>
					<?
					if (In_Array($i, $arParams["WEEK_HOLIDAYS"]))
						continue;
					?>
					<th class="intask-cell" align="center" width="<?= intval(100 / (7 - Count($arParams["WEEK_HOLIDAYS"]))) ?>%"><?= $ar[$i] ?></th>
				<?endfor;?>
			</tr>
			<tr class="intask-row">
				<th align="center" class="intask-cell"><?= GetMessage("INTASK_C25T_TIME") ?></th>
				<?for ($i = 0; $i < 7; $i++):?>
					<?
					if (In_Array($i, $arParams["WEEK_HOLIDAYS"]))
						continue;
					?>
					<th align="center" class="intask-cell"><?= FormatDate($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, $arResult["WEEK_START_ARRAY"]["m"], $arResult["WEEK_START_ARRAY"]["d"] + $i, $arResult["WEEK_START_ARRAY"]["Y"])) ?></th>
				<?endfor;?>
			</tr>
		</thead>
		<tbody>
			<?for ($i = $arResult["LIMITS"]["FROM"]; $i < $arResult["LIMITS"]["TO"]; $i++):?>
				<tr class="intask-row">
					<td class="intask-cell selected" nowrap><?= __RM_MkT($i) ?></td>
					<?
					for ($j = 1; $j <= 7; $j++)
					{
						if (In_Array($j - 1, $arParams["WEEK_HOLIDAYS"]))
							continue;

						$currentDay = ConvertTimeStamp(time()) == ConvertTimeStamp(MkTime(0, 0, 0, $arResult["WEEK_START_ARRAY"]["m"], $arResult["WEEK_START_ARRAY"]["d"] + $j - 1, $arResult["WEEK_START_ARRAY"]["Y"]));

						if ($arResult["ITEMS_MATRIX"][$j][$i])
						{
							if ($i == 0 || !$arResult["ITEMS_MATRIX"][$j][$i - 1] || $arResult["ITEMS_MATRIX"][$j][$i - 1] != $arResult["ITEMS_MATRIX"][$j][$i])
							{
								$cnt = 0;
								for ($k = $i; $k < 48; $k++)
								{
									if ($arResult["ITEMS_MATRIX"][$j][$i] == $arResult["ITEMS_MATRIX"][$j][$k])
										$cnt++;
									else
										break;
								}
								?><td class="intask-cell reserved<?=$currentDay ? ' current' : ''?>" valign="top" rowspan="<?= $cnt ?>">
									<a href="<?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["VIEW_ITEM_URI"] ?>"><b><?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["NAME"] ?></b></a><br />
									(<?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["DATE_ACTIVE_FROM_TIME"] ?> -
									<?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["DATE_ACTIVE_TO_TIME"] ?>)<br />
									<?= GetMessage("INTASK_C25T_RESERVED_BY") ?>:
									<?
									$APPLICATION->IncludeComponent("bitrix:main.user.link",
										'',
										array(
											"ID" => $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY"],
											"HTML_ID" => "reserve_meeting_meeting".$arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY"],
											"NAME" => htmlspecialcharsback($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY_FIRST_NAME"]),
											"LAST_NAME" => htmlspecialcharsback($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY_LAST_NAME"]),
											"SECOND_NAME" => htmlspecialcharsback($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY_SECOND_NAME"]),
											"LOGIN" => htmlspecialcharsback($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CREATED_BY_LOGIN"]),
											"USE_THUMBNAIL_LIST" => "N",
											"INLINE" => "Y",
											"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
											"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PM_URL"],
											"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
											"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
											"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
											"SHOW_YEAR" => $arParams["SHOW_YEAR"],
											"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE_WO_NOBR"],
											"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
										),
										false,
										array("HIDE_ICONS" => "Y")
									);
									?>
									<br />
									<?if ($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["EDIT_ITEM_URI"] <> ''):?>
										<br /><a href="<?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["EDIT_ITEM_URI"] ?>"><?= GetMessage("INTASK_C25T_EDIT") ?></a>
									<?endif;?>
									<?if ($arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CLEAR_URI"] <> ''):?>
										<br /><a onclick="if(confirm('<?= GetMessage("INTASK_C25T_CLEAR_CONF") ?>'))window.location='<?= $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$j][$i]]["CLEAR_URI"] ?>';" href="javascript:void(0)"><?= GetMessage("INTASK_C25T_CLEAR") ?></a>
									<?endif;?>
								</td><?
							}
						}
						else
						{
							?><td class="intask-cell notreserved<?=$currentDay ? ' current' : ''?>" title="<?= GetMessage("INTASK_C25T_DBL_CLICK") ?>" ondblclick="window.location='<?= CUtil::addslashes($arResult["CellClickUri"]) ?>start_date=<?= Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, $arResult["WEEK_START_ARRAY"]["m"], $arResult["WEEK_START_ARRAY"]["d"] + $j - 1, $arResult["WEEK_START_ARRAY"]["Y"])) ?>&amp;start_time=<?
							$h1 = intval($i / 2);
							if ($h1 < 10)
								$h1 = "0".$h1;
							$i1 = ($i % 2 != 0 ? "30" : "00");
							echo $h1.":".$i1;
							?>'">&nbsp;</td><?
						}
					}
					?>
				</tr>
			<?endfor;?>
		</tbody>
	</table>
	<br />
	<?
}
?>