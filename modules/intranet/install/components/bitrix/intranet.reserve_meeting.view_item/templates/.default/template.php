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
		<td colspan="2"><?=$arResult["MEETING"]["DESCRIPTION"] ?></td>
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
	<br />

	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<tbody>
			<tr>
				<td align="right" width="40%"><?= GetMessage("INTASK_C29T_DATE_FROM") ?>:</td>
				<td width="60%"><?=formatDate($arParams['DATE_TIME_FORMAT'], makeTimeStamp($arResult['ITEM']['DATE_ACTIVE_FROM'])) ?></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_DATE_TO") ?>:</td>
				<td><?=formatDate($arParams['DATE_TIME_FORMAT'], makeTimeStamp($arResult['ITEM']['DATE_ACTIVE_TO'])) ?></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_AUTHOR") ?>:</td>
				<td>
				<?
				$APPLICATION->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $arResult["ITEM"]["CREATED_BY_ID"],
						"HTML_ID" => "reserve_meeting_view_item".$arResult["ITEM"]["CREATED_BY_ID"],
						"NAME" => htmlspecialcharsback($arResult["ITEM"]["CREATED_BY_FIRST_NAME"]),
						"LAST_NAME" => htmlspecialcharsback($arResult["ITEM"]["CREATED_BY_LAST_NAME"]),
						"SECOND_NAME" => htmlspecialcharsback($arResult["ITEM"]["CREATED_BY_SECOND_NAME"]),
						"LOGIN" => htmlspecialcharsback($arResult["ITEM"]["CREATED_BY_LOGIN"]),
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
				<td align="right"><?= GetMessage("INTASK_C29T_NAME") ?>:</td>
				<td><?= $arResult["ITEM"]["NAME"] ?></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_TYPE") ?>:</td>
				<td><?= $arResult["ITEM"]["PROPERTY_UF_RES_TYPE_VALUE"] ?></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_PERSONS") ?>:</td>
				<td><?= $arResult["ITEM"]["PROPERTY_UF_PERSONS_VALUE"] ?></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?= GetMessage("INTASK_C29T_DESCR") ?>:</td>
				<td><?= $arResult["ITEM"]["DETAIL_TEXT"] ?></td>
			</tr>
			<tr>
				<td align="right"><?= GetMessage("INTASK_C29T_PREP") ?>:</td>
				<td><?= ("Y" == $arResult["ITEM"]["PROPERTY_UF_PREPARE_ROOM_VALUE"] ? GetMessage("INTASK_C29T_YES") : GetMessage("INTASK_C29T_NO")) ?></td>
			</tr>
			<tr>
				<td align="right" valign="top"><?= GetMessage("INTASK_C29T_REGULAR") ?>:</td>
				<td valign="top"><?
					if ($arResult["ITEM"]["PROPERTY_PERIOD_TYPE_VALUE"] != "NONE")
					{
						echo Str_Replace("#VAL#", $arResult["ITEM"]["PROPERTY_PERIOD_COUNT_VALUE"], GetMessage("INTASK_C29T_TRANSS_".$arResult["ITEM"]["PROPERTY_PERIOD_TYPE_VALUE"]));
						echo "<br />";
						if ($arResult["ITEM"]["PROPERTY_PERIOD_TYPE_VALUE"] == "WEEKLY")
						{
							$ar = Explode(",", $arResult["ITEM"]["PROPERTY_PERIOD_ADDITIONAL_VALUE"]);
							for ($i = 0; $i < Count($ar); $i++)
							{
								if ($i > 0)
									echo ", ";
								echo GetMessage("INTASK_C29T_RWD_".($ar[$i] + 1));
							}
							echo "<br />";
						}
						echo GetMessage("INTASK_C29T_REGULARITY_UNTIL")." ";
						echo formatDate($arParams['DATE_TIME_FORMAT'], makeTimeStamp($arResult['ITEM']['DATE_ACTIVE_TO_FINISH']));
					}
					else
					{
						echo GetMessage("INTASK_C29T_REGULARITY_NONE");
					}
				?></td>
			</tr>
		</tbody>
	</table>
	<br />
	<?
}
?>