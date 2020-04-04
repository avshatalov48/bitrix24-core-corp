<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
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
					<?=GetMessage('CRM_ACTIVITY_CALL_DOWNLOAD_RECORD')?>
				</a>
			</span>
		</div>
	<?endforeach?>
	<div class="crm-task-list-call-info">
		<?if($arResult['CALL']):?>
			<div class="crm-task-list-call-info-container">
				<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_CALL_TYPE')?>:</span>
				<span class="crm-task-list-call-info-item"><?=htmlspecialcharsbx($arResult['CALL']['CALL_TYPE_TEXT'])?></span>
			</div>
			<?if($arResult['CALL']['CALL_FAILED_CODE'] == '200'):?>
				<div class="crm-task-list-call-info-container">
					<span class="crm-task-list-call-info-name">
						<?=GetMessage('CRM_ACTIVITY_CALL_DURATION')?>:
					</span>
					<span class="crm-task-list-call-info-item">
						<?=htmlspecialcharsbx($arResult['CALL']['CALL_DURATION_TEXT'])?>
					</span>
				</div>
				<?if($arResult['CALL']['CALL_VOTE'] > 0):?>
					<div class="crm-task-list-call-info-container">
						<span class="crm-task-list-call-info-name">
							<?=GetMessage('CRM_ACTIVITY_CALL_VOTE')?>:
						</span>
						<span class="crm-task-list-call-info-item">
							<?=htmlspecialcharsbx($arResult['CALL']['CALL_VOTE'])?>
						</span>
					</div>
				<?endif?>
			<?else:?>
				<div class="crm-task-list-call-info-container">
						<span class="crm-task-list-call-info-name">
							<?=GetMessage('CRM_ACTIVITY_CALL_FAILED')?>:
						</span>
						<span class="crm-task-list-call-info-item">
							<?=htmlspecialcharsbx($arResult['CALL']['CALL_FAILED_REASON'])?>
						</span>
				</div>
			<?endif?>
		<?endif?>
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name">
				<?=GetMessage('CRM_ACTIVITY_CALL_DESCRIPTION')?>:
			</span>
		</div>
		<div class="crm-task-list-call-info-container">
			<?=$arResult['ACTIVITY']['DESCRIPTION_HTML']?>
		</div>
		<? if($arResult['CALL']['COMMENT'] != ''): ?>
			<div class="crm-task-list-call-info-container">
				<span class="crm-task-list-call-info-name">
					<?=GetMessage('CRM_ACTIVITY_CALL_COMMENT')?>:
				</span>
			</div>
			<div class="crm-task-list-call-info-container">
				<?=htmlspecialcharsbx($arResult['CALL']['COMMENT'])?>
			</div>
		<? endif ?>
	</div>
</div>
