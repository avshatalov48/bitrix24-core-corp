<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

$ind = RandString(8);

?><div class="crm-post-deal-wrap" id="crm_feed_activity_<?=$ind?>"><?

	if (!empty($arResult["FIELDS_FORMATTED"]))
	{
		if ($arResult["FORMAT"] == "table")
		{
			?><table class="crm-feed-info-table"><?
		}

		foreach($arResult["FIELDS_FORMATTED"] as $key => $value)
		{
			if (
				$key == "CLIENT_ID" 
				&& intval($arResult["COMMUNICATION_MORE_CNT"]) > 0
				&& !empty($arResult["CLIENTS_FOR_JS"])
			)
			{
				$strClientsMoreLink = '';

				if (
					($arResult["COMMUNICATION_MORE_CNT"] % 100) > 10
					&& ($arResult["COMMUNICATION_MORE_CNT"] % 100) < 20
				)
					$suffix = 5;
				else
				{
					$suffix = $arResult["COMMUNICATION_MORE_CNT"] % 10;
					if ($suffix == 1 && $arResult["COMMUNICATION_MORE_CNT"] > 1)
					{
						$suffix = 21;
					}
				}

				$strClientsMoreLink .= '<span class="crm-feed-client-more-link" id="crm_feed_activity_more_'.intval($arParams["ACTIVITY"]["ID"]).'">'.GetMessage("C_T_CRM_LFA_CLIENTS_MORE_LINK_".$suffix, array("#COUNT#" => $arResult["COMMUNICATION_MORE_CNT"])).'</span>';
			}
			else
			{
				unset($strClientsMoreLink);
			}
			
			if ($arResult["FORMAT"] == "table")
			{
				echo str_replace(
					array(
						"#row_begin#", 
						"#row_end#",
						"#cell_begin_left#",
						"#cell_begin_right#",
						"#cell_begin_colspan2#",
						"#cell_end#",
						"#clients_more_link#"
					),
					array(
						"<tr>", 
						"</tr>",
						'<td class="crm-feed-info-left-cell">',
						'<td class="crm-feed-info-right-cell">',
						'<td colspan="2" class="crm-feed-info-right-cell">',
						'</td>',
						(!empty($strClientsMoreLink) ? $strClientsMoreLink : '')
					),
					$value
				);
			}
		}

		if ($arResult["FORMAT"] == "table")
		{
			?></table><?
		}	
	}
	?><div class="feed-calendar-view-icon feed-crm-view-icon crm-feed-calendar-icon">
		<div class="feed-calendar-view-icon-fake-link"><img src="/bitrix/images/1.gif"></div>
		<div class="feed-calendar-view-icon-day"><?=$arResult["DATE_WEEK_DAY"]?></div>
		<div class="feed-calendar-view-icon-date"><?=$arResult["DATE_MONTH_DAY"]?></div>
	</div><?
	if (
		!empty($arResult["RECORDS"])
		&& is_array($arResult["RECORDS"])
	)
	{
		?><div class="crm-feed-deal-description">
			<div class="crm-feed-deal-descr-title"><?=GetMessage("C_T_CRM_LFA_RECORDS")?>:</div>
			<div class="crm-feed-deal-descr-text"><?
			$cnt = 0;
			foreach($arResult["RECORDS"] as $key => $arRecord)
			{
				?><div style="padding-top: <?=(!$cnt ? "0" : "10")?>px;"><?$APPLICATION->IncludeComponent(
					"bitrix:player",
					"audio",
					Array(
						"PLAYER_TYPE" => "",
						"CHECK_FILE" => (
							$arParams["ACTIVITY"]["STORAGE_TYPE_ID"] == CCrmActivityStorageType::WebDav
								|| $arParams["ACTIVITY"]["STORAGE_TYPE_ID"] == CCrmActivityStorageType::Disk)
							? "N" : "Y",
						"USE_PLAYLIST" => "N",
						"PATH" => $arRecord["URL"],
						"WIDTH" => 398,
						"HEIGHT" => 30,
						"PREVIEW" => false,
						"LOGO" => false,
						"FULLSCREEN" => "N",
						"SKIN" => "timeline_player.css",
						"SKIN_PATH" => "/bitrix/js/crm/",
						"CONTROLBAR" => "bottom",
						"WMODE" => "transparent",
						"WMODE_WMV" => "windowless",
						"HIDE_MENU" => "N",
						"SHOW_CONTROLS" => "Y",
						"SHOW_STOP" => "N",
						"SHOW_DIGITS" => "Y",
						"CONTROLS_BGCOLOR" => "FFFFFF",
						"CONTROLS_COLOR" => "000000",
						"CONTROLS_OVER_COLOR" => "000000",
						"SCREEN_COLOR" => "000000",
						"AUTOSTART" => "N",
						"REPEAT" => "N",
						"VOLUME" => "90",
						"DISPLAY_CLICK" => "play",
						"MUTE" => "N",
						"HIGH_QUALITY" => "N",
						"ADVANCED_MODE_SETTINGS" => "Y",
						"BUFFER_LENGTH" => "10",
						"DOWNLOAD_LINK" => false,
						"DOWNLOAD_LINK_TARGET" => "_self",
						"ALLOW_SWF" => "N",
						"ADDITIONAL_PARAMS" => array(
							'LOGO' => false,
							'NUM' => false,
							'HEIGHT_CORRECT' => false,
						),
						"PLAYER_ID" => "bitrix_crm_activity_".$arParams["ACTIVITY"]["ID"]."_".$key,
						"TYPE" => "audio/mp3",
					),
					false,
					Array("HIDE_ICONS" => "Y")
				);?></div><?
				$cnt++;				
			}
			?></div>
		</div><?
	}
	if (!empty($arResult["DESCRIPTION"]))
	{
		?><div class="crm-feed-deal-description">
			<div class="crm-feed-deal-descr-title"><?=GetMessage("C_T_CRM_LFA_DESCRIPTION")?>:</div>
			<div class="crm-feed-deal-descr-text"><?=$arResult["DESCRIPTION"]?></div>
		</div><?
	}	
?></div><?

/*
if ($arResult["IS_COMPLETED"])
{
	echo GetMessage("C_T_CRM_LFA_COMPLETED")."<br>";
}
*/
?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				var activityNode = BX('crm_feed_activity_<?=$ind?>');
				var logEntryNode = BX.findParent(activityNode, {'className': 'feed-post-cont-wrap'});				

				if (logEntryNode)
				{
					var oActivity = BX.CrmLiveFeedActivity.create(<?=$arParams["~ACTIVITY"]["ID"]?>, { 
						editorId: 'livefeed',
						logId: <?=intval($arParams["~PARAMS"]["LOG_ID"])?>,
						node: logEntryNode
					});
					
					<?
					if (!empty($arResult['CLIENTS_FOR_JS']))
					{
						?>
						var x1 = new oActivity.clientsPopupList(<?=CUtil::PhpToJsObject($arResult['CLIENTS_FOR_JS'])?>);
						BX.bind(BX('crm_feed_activity_more_<?=intval($arParams["ACTIVITY"]["ID"])?>'), "click", BX.proxy(x1.showClients, x1));
						<?
					}
					?>
				}
			}
	);
</script>