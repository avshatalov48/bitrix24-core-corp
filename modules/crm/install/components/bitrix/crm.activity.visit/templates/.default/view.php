<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>
<div class="crm-task-list-call">
	<?foreach($arResult['RECORDS'] as $k => $record):?>
		<div class="crm-task-list-call-walkman">
			<span class="crm-task-list-call-walkman-item" style="height: 30px; overflow: hidden;">
				<?
				$APPLICATION->IncludeComponent(
					"bitrix:player",
					"audio",
					Array(
						"PLAYER_TYPE" => "",
						"PROVIDER" => "video",
						"CHECK_FILE" => "N",
						"USE_PLAYLIST" => "N",
						"PATH" => $record['URL'],
						"WIDTH" => 250,
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
						"SHOW_STOP" => "Y",
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
						"PLAYER_ID" => "bitrix_vi_record_".$arResult['CALL']["ID"]."_".$k,
						"TYPE" => "audio/mp3",
					),
					false,
					Array("HIDE_ICONS" => "Y")
				);
				?>
			</span>
			<span class="crm-task-list-call-walkman-link-container">
				<a href="<?=$record["URL"]?>" class="crm-task-list-call-walkman-link" target="_blank">
					<?=GetMessage('CRM_ACTIVITY_VISIT_DOWNLOAD_RECORD')?>
				</a>
			</span>
		</div>
	<?endforeach?>
	<div class="crm-task-list-call-info">
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name">
				<?=GetMessage('CRM_ACTIVITY_VISIT_FIELD_DESCRIPTION')?>:
			</span>
		</div>
		<span>
			<?=$arResult['ACTIVITY']['DESCRIPTION_HTML']?>
		</span>
	</div>
</div>

<? if (!empty($arResult['PHOTO'])): ?>
	<br><?=GetMessage('CRM_ACTIVITY_FACEID_PHOTO_FIELD_DESCRIPTION')?>:<br>
	<img src="<?=$arResult['PHOTO']['URL']?>">
<? endif ?>