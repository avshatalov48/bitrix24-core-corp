<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Asr\Language;

CJSCore::RegisterExt('voximplant_config_edit', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/script.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));

CJSCore::Init([
	"voximplant.common", "voximplant_config_edit", "voximplant.numberrent", "ui.tilegrid", "ui.buttons",
	"ui.forms", "ui.alerts", "ui.hint", "player", "sidepanel", "phone_number", "ui.design-tokens",
]);

$melodiesToLoad = [];
?>

<?
if (!$arResult["IFRAME"])
{
	?>
	<div class="voximplant-page-menu-sidebar">
		<?
		$APPLICATION->ShowViewContent("left-panel"); ?>
	</div>
	<?
}

$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrappermenu", "", array(
	"ITEMS" => $arResult["CONFIG_MENU"],
	"TITLE_HTML" => Loc::getMessage("VOX_CONFIG_TELEPHONY_24") . "<span class=\"logo-color\"> 24</span>"
));
$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>

<div id="vi-editor-root" class="voximplant-config-root">
	<?if($arResult["ERROR"] != ""):?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?=$arResult["ERROR"]?></span>
		</div>
	<?endif;?>

	<form id="config_edit_form" method="POST">
		<?= bitrix_sessid_post() ?>
		<input type="hidden" name="action" value="save"/>
		<input type="hidden" name="ID" value="<?= $arResult["ITEM"]["ID"] ?>">

		<div class="voximplant-config-page <?= ($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_SIP ? "active" : "" )?>" data-role="page" data-page="sip">
			<div class="voximplant-container voximplant-container-split">
				<div class="voximplant-container-column">
					<div class="voximplant-title-dark"><?=GetMessage('VI_CONFIG_SIP_OUT_TITLE')?></div>
					<div class="voximplant-number-settings-wrap">
						<p><?=GetMessage('VI_CONFIG_SIP_C_CONFIG')?></p>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?=GetMessage("VI_CONFIG_SIP_C_NUMBER")?></div>
								<input type="text" name="SIP[PHONE_NAME]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PHONE_NAME'])?>" class="voximplant-control-input">
							<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_C_NUMBER_HINT") ?></div>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_SERVER')?></div>
							<input type="text" name="SIP[SERVER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['SERVER'])?>" class="voximplant-control-input">
							<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_T_SERVER_HINT_2") ?></div>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_LOGIN')?></div>
							<input type="text" name="SIP[LOGIN]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['LOGIN'])?>" class="voximplant-control-input">
							<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_T_LOGIN_HINT_2") ?></div>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_PASS')?></div>
							<input type="password" name="SIP[PASSWORD]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PASSWORD'])?>" class="voximplant-control-input">
							<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_T_PASS_HINT_2") ?></div>
						</div>
						<? if($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD): ?>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_AUTH_USER')?></div>
								<input type="text" name="SIP[AUTH_USER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['AUTH_USER'])?>" class="voximplant-control-input">
								<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_C_NUMBER_HINT") ?></div>
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_OUTBOUND_PROXY')?></div>
								<input type="text" name="SIP[OUTBOUND_PROXY]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['OUTBOUND_PROXY'])?>" class="voximplant-control-input">
								<div class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_SIP_C_NUMBER_HINT") ?></div>
							</div>
						<? endif ?>
					</div>
				</div>
				<div class="voximplant-container-column">
					<div class="voximplant-title-dark"><?=GetMessage("VI_CONFIG_SIP_IN_TITLE")?></div>
					<div class="voximplant-number-settings-wrap">
						<? if($arResult["SIP_CONFIG"]["TYPE"] == CVoxImplantSip::TYPE_CLOUD): ?>
							<p><?= GetMessage("VI_CONFIG_SIP_C_IN") ?></p>
							<div class="voximplant-control-row">
								<input id="sip-need-update" name="SIP[NEED_UPDATE]" value="N" type="hidden">
								<div class="voximplant-control-subtitle"><?= GetMessage("VI_CONFIG_SIP_C_STATUS")?></div>
								<div id="sip-status"></div>
								<div id="sip-status-text"></div>
							</div>
						<? else: ?>
							<p><?= GetMessage("VI_CONFIG_SIP_IN") ?></p>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_INC_SERVER')?></div>
								<input readonly type="text" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_SERVER'])?>" class="voximplant-control-input">
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_INC_LOGIN')?></div>
								<input readonly type="text" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_LOGIN'])?>" class="voximplant-control-input">
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?=GetMessage('VI_CONFIG_SIP_T_INC_PASS')?></div>
								<input readonly type="text" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_PASSWORD'])?>" class="voximplant-control-input">
							</div>
						<? endif ?>
						<p>
							<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="voximplant-config-page <?= ($arResult["ITEM"]["PORTAL_MODE"] !== CVoxImplantConfig::MODE_SIP ? "active" : "" )?>" data-role="page" data-page="routing">
			<div class="voximplant-container">
				<div class="voximplant-title-dark">
					<?= $arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_LINK ? Loc::getMessage("VOX_CONFIG_EDIT_CALLBACK_ROUTING"): Loc::getMessage("VOX_CONFIG_EDIT_CALL_ROUTING") ?>
				</div>
				<div class="voximplant-number-settings-wrap">

					<? if($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_SIP): ?>
						<div class="voximplant-number-settings-row">
							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<input id="sip_to" name="USE_SIP_TO" type="checkbox" value="Y" class="voximplant-number-settings-checkbox" <? if ($arResult["ITEM"]["USE_SIP_TO"] == "Y") { ?>checked<? } ?>>
								<label for="sip_to" class="voximplant-number-settings-label"><?=GetMessage("VI_CONFIG_EDIT_SIP_HEADER_PROCESSING")?></label>
							</div>
							<div class="voximplant-number-settings-inner">
								<div class="voximplant-number-settings-text"><?=GetMessage("VI_CONFIG_EDIT_SIP_HEADER_PROCESSING_TIP")?></div>
							</div>
						</div>
					<? endif ?>
					<? if($arResult["ITEM"]["PORTAL_MODE"] !== CVoxImplantConfig::MODE_LINK): ?>
						<div id="voximplant-hint" class="voximplant-number-settings-row">
							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<input id="play-welcome-melody" name="MELODY_WELCOME_ENABLE" type="checkbox"
									   class="voximplant-number-settings-checkbox" data-role="welcome-melody"
									   <? if ($arResult["ITEM"]["MELODY_WELCOME_ENABLE"] == "Y") { ?>checked<? } ?> value="Y"
								>
								<label for="play-welcome-melody" class="voximplant-number-settings-label">
									<?= Loc::getMessage("VI_CONFIG_EDIT_PLAY_WELCOME_MELODY") ?>
								</label>
							</div>
							<div class="voximplant-number-settings-inner tel-set-height-animated"
								 data-role="welcome-melody-settings" data-height="160px" style="max-height: <?=$arResult["ITEM"]["MELODY_WELCOME_ENABLE"] == "Y" ? "160px" : "0"?>">
								<div class="voximplant-number-settings-choice">
									<input id="direct-code" name="DIRECT_CODE" type="checkbox"
										   class="voximplant-number-settings-checkbox"
										   <? if ($arResult["ITEM"]["DIRECT_CODE"] == "Y") { ?>checked<? } ?> value="Y">
									<label for="direct-code"
										   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING") ?></label>
								</div>
								<div class="voximplant-number-settings-inner">
									<p class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_TIP") ?></p>
									<div class="voximplant-control-row">
										<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_OMITTED_CALL") ?></div>
										<select name="DIRECT_CODE_RULE" class="voximplant-control-select">
											<option value="<?= CVoxImplantIncoming::RULE_QUEUE ?>" <?= ($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_QUEUE ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1_2") ?></option>
											<option value="<?= CVoxImplantIncoming::RULE_PSTN ?>" <?= ($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_PSTN ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_2") ?></option>
											<option value="<?= CVoxImplantIncoming::RULE_VOICEMAIL ?>" <?= ($arResult["ITEM"]["DIRECT_CODE_RULE"] == CVoxImplantIncoming::RULE_VOICEMAIL ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2_1") ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
					<? endif ?>
					<? if($arResult["ITEM"]["PORTAL_MODE"] !== CVoxImplantConfig::MODE_LINK): ?>
						<div class="voximplant-number-settings-row">
							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<input id="vi-set-ivr" name="IVR" type="checkbox" data-role="enable-ivr"
									   data-locked="<?= (\Bitrix\Voximplant\Ivr\Ivr::isEnabled() ? "N" : "Y") ?>"
									   data-license-popup="limit_contact_center_telephony_ivr"
									   class="voximplant-number-settings-checkbox"
									   <? if ($arResult["ITEM"]["IVR"] == "Y") { ?>checked<? } ?> value="Y">
								<label for="vi-set-ivr" class="voximplant-number-settings-label"><?= Loc::getMessage("TELEPHONY_USE_IVR_2") ?></label>
								<? if (!\Bitrix\Voximplant\Ivr\Ivr::isEnabled()): ?>
									<div class="tel-lock-holder-select"
										 title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
										<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_ivr')"
											 class="tel-lock <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
									</div>
								<? endif ?>
							</div>
							<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="ivr-settings"
								 data-height="80px" style="max-height: <?= $arResult["ITEM"]["IVR"] == "Y" ? "80px" : "0" ?>">
								<div class="voximplant-control-row">
									<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_IVR_SELECTION") ?></div>
									<div class="voximplant-control-select-flexible">
										<select name="IVR_ID" class="voximplant-control-select" data-role="select-ivr">
											<option value="new"><?= GetMessage('VI_CONFIG_CREATE_IVR') ?></option>
											<option disabled>&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;</option>
											<? foreach ($arResult["IVR_MENUS"] as $ivr): ?>
												<option value="<?= htmlspecialcharsbx($ivr["ID"]) ?>"<?= ($ivr["ID"] == $arResult["ITEM"]["IVR_ID"] ? " selected" : "") ?>><?= htmlspecialcharsbx($ivr["NAME"]) ?></option>
											<? endforeach; ?>
										</select>
										<span id="vi-group-show-ivr" class="voximplant-link" data-role="show-ivr-config"><?= Loc::getMessage("VI_CONFIG_IVR_SETTINGS") ?></span>
									</div>
								</div>
							</div>
						</div>
					<? endif ?>

					<? if (IsModuleInstalled('crm')): ?>
						<div class="voximplant-number-settings-row">
							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<input id="vi_crm_forward" type="checkbox" data-role="enable-crm-forward"
									   class="voximplant-number-settings-checkbox" name="CRM_FORWARD"
									   <? if ($arResult["ITEM"]["CRM_FORWARD"] == "Y") { ?>checked<? } ?> value="Y">
								<label for="vi_crm_forward"
									   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_FORWARD_2") ?></label>
							</div>
							<div class="voximplant-number-settings-inner tel-set-height-animated"
								 data-role="crm-forward-settings" data-height="80px" style="max-height: <?= $arResult["ITEM"]["CRM_FORWARD"] == "Y" ? "80px" : "0" ?>">
								<div class="voximplant-control-row">
									<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CHECKING_OMITTED_CALL_NEW") ?></div>
									<select class="voximplant-control-select" name="CRM_RULE">
										<option value="<?= CVoxImplantIncoming::RULE_QUEUE ?>"<?= (CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1") ?></option>
										<option value="<?= CVoxImplantIncoming::RULE_PSTN ?>"<?= (CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_3") ?></option>
										<? if ($arResult['SHOW_RULE_VOICEMAIL']): ?>
											<option value="<?= CVoxImplantIncoming::RULE_VOICEMAIL ?>"<?= (CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2_1") ?></option>
										<? endif ?>
									</select>
								</div>
							</div>
						</div>
					<? endif; ?>

					<div class="voximplant-number-settings-row">
						<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
							<span class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_ROUTE_TO_GROUP") ?></span>
						</div>
						<div class="voximplant-number-settings-inner">
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_SELECT_GROUP") ?></div>
								<div class="voximplant-control-select-flexible">
									<select id="vi-group-id-select" class="voximplant-control-select" name="QUEUE_ID"
											data-role="select-group">
										<option value="new"><?= GetMessage('VI_CONFIG_CREATE_GROUP') ?></option>
										<option disabled>&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;</option>
										<? foreach ($arResult["QUEUES"] as $queue): ?>
											<option value="<?= htmlspecialcharsbx($queue["ID"]) ?>"<?= ($queue["ID"] == $arResult["ITEM"]["QUEUE_ID"] ? " selected" : "") ?>><?= htmlspecialcharsbx($queue["NAME"]) ?></option>
										<? endforeach; ?>
									</select>
									<span id="vi-group-show-config" class="voximplant-link"
										  data-role="show-group-config">
								<?= Loc::getMessage("VI_CONFIG_GROUP_SETTINGS") ?>
							</span>
								</div>
							</div>
							<div class="voximplant-number-settings-choice">
								<input id="vi_timeman" type="checkbox" class="voximplant-number-settings-checkbox"
									   name="TIMEMAN" <? if ($arResult["ITEM"]["TIMEMAN"] == "Y") { ?>checked<? } ?>
									   value="Y">
								<label for="vi_timeman"
									   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_TIMEMAN_SUPPORT") ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="voximplant-config-page" data-role="page" data-page="sip-numbers">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VI_CONFIG_YOUR_SIP_PBX_NUMBERS") ?></div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="DETECT_LINE_NUMBER" name="SIP[DETECT_LINE_NUMBER]" type="checkbox" data-role="enable-sip-detect-line-number"
							   value="Y"
							   <? if ($arResult["SIP_CONFIG"]["DETECT_LINE_NUMBER"] === "Y"): ?>checked="checked"<? endif ?>
							   class="voximplant-number-settings-checkbox">
						<label for="DETECT_LINE_NUMBER" class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_SIP_DETECT_INCOMING_NUMBER") ?></label>
					</div>

					<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="sip-detect-line-number-settings" data-height="800px" style="max-height: <?= $arResult["SIP_CONFIG"]["DETECT_LINE_NUMBER"] === "Y" ? "800px" : "0" ?>">
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_SIP_DETECTION_HEADER_ORDER") ?></div>
							<select name="SIP[LINE_DETECT_HEADER_ORDER]" class="voximplant-control-select">
								<option value="<?=CVoxImplantSip::HEADER_ORDER_DIVERSION_TO?>"<?= ($arResult["SIP_CONFIG"]["LINE_DETECT_HEADER_ORDER"] == CVoxImplantSip::HEADER_ORDER_DIVERSION_TO ? ' selected="selected"' : '') ?>><?= "diversion, to"?></option>
								<option value="<?=CVoxImplantSip::HEADER_ORDER_TO_DIVERSION?>"<?= ($arResult["SIP_CONFIG"]["LINE_DETECT_HEADER_ORDER"] == CVoxImplantSip::HEADER_ORDER_TO_DIVERSION ? ' selected="selected"' : '') ?>><?= "to, diversion"?></option>
							</select>
						</div>

						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_SIP_CONNECTION_NUMBERS") ?></div>
							<?
							$GLOBALS["APPLICATION"]->includeComponent(
								"bitrix:ui.tile.selector",
								"",
								[
									"ID" => "sip-numbers",
									"MULTIPLE" => true,
									"LIST" => $arResult["SIP_CONFIG"]["NUMBERS"],
									"CAN_REMOVE_TILES" => true,
									'SHOW_BUTTON_SELECT' => false,
									"SHOW_BUTTON_ADD" => true,
									"BUTTON_ADD_CAPTION" => Loc::getMessage("VI_CONFIG_SIP_ADD_CONNECTION_NUMBER")
								]
							);
							?>
						</div>
					</div>
				</div>

			</div>
		</div>

		<? if (IsModuleInstalled('crm')): ?>
			<div class="voximplant-config-page" data-role="page" data-page="crm">
				<div class="voximplant-container">
					<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_CONFIG_EDIT_CRM_INTEGRATION") ?></div>
					<div class="voximplant-number-settings-wrap">
						<div class="voximplant-number-settings-row">
							<div class="voximplant-number-settings-inner">
								<div class="voximplant-control-row">
									<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CREATE_NEW") ?></div>
									<div class="voximplant-control-select-flexible">
										<select id="vi_crm_create" name="CRM_CREATE" class="voximplant-control-select"
												data-role="crm-create">
											<? foreach (array("1" => CVoxImplantConfig::CRM_CREATE_NONE, "2" => CVoxImplantConfig::CRM_CREATE_LEAD) as $ii => $k): ?>
												<option value="<?= $k ?>"<?= ($k == $arResult["ITEM"]["CRM_CREATE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_CRM_CREATE_".$ii) ?></option>
											<? endforeach; ?>
										</select>
										<span class="voximplant-link"
											  data-role="show-crm-exception-list"><?= Loc::getMessage("VI_CONFIG_CONFIGURE_CRM_EXCEPTIONS_LIST") ?></span>
									</div>
								</div>
								<div class="voximplant-control-row">
									<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_NEW") ?></div>
									<select name="CRM_CREATE_CALL_TYPE" class="voximplant-control-select"
											data-role="crm-create-call-type">
										<option value="<?= CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING ?>"<?= (CVoxImplantConfig::CRM_CREATE_CALL_TYPE_INCOMING == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_INCOMING") ?></option>
										<option value="<?= CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING ?>"<?= (CVoxImplantConfig::CRM_CREATE_CALL_TYPE_OUTGOING == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_OUTGOING") ?></option>
										<option value="<?= CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL ?>"<?= (CVoxImplantConfig::CRM_CREATE_CALL_TYPE_ALL == $arResult["ITEM"]["CRM_CREATE_CALL_TYPE"] ? " selected" : "") ?>><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_CREATE_CALL_TYPE_ALL") ?></option>
									</select>
								</div>
								<div class="voximplant-control-row">
									<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_CRM_SOURCE_NEW") ?></div>
									<select id="vi_crm_source_select" name="CRM_SOURCE"
											class="voximplant-control-select" data-role="crm-source-select">
										<? foreach ($arResult['CRM_SOURCES'] as $ii => $k): ?>
											<option value="<?= $ii ?>"<?= ($ii == $arResult["ITEM"]["CRM_SOURCE"] ? " selected" : "") ?>><?= htmlspecialcharsbx($k) ?></option>
										<? endforeach; ?>
									</select>
									<? if (!\Bitrix\Voximplant\Limits::canSelectCallSource() || CVoxImplantAccount::IsDemo()): ?>
										<div class="tel-lock-holder-select"
											 title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
											<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_source')"
												 class="tel-lock <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
										</div>
									<? endif; ?>
									<? if (!\Bitrix\Voximplant\Limits::canSelectCallSource()): ?>
										<script type="text/javascript">
											viCrmSource = BX('vi_crm_source_select').options.selectedIndex;
											BX.bind(BX('vi_crm_source_select'), 'change', function (e)
											{
												BX.UI.InfoHelper.show('limit_contact_center_telephony_source');
												this.selectedIndex = viCrmSource;
											});
										</script>
									<? endif; ?>
								</div>
								<div class="voximplant-number-settings-choice">
									<input id="crm-transfer-change" name="CRM_TRANSFER_CHANGE" type="checkbox"
										   class="voximplant-number-settings-checkbox"
										   <? if ($arResult["ITEM"]["CRM_TRANSFER_CHANGE"] == "Y") { ?>checked<? } ?>
										   value="Y" data-role="crm-transfer-change">
									<label for="crm-transfer-change" class="voximplant-number-settings-label">
										<?= Loc::getMessage("VI_CONFIG_EDIT_CRM_TRANSFER_CHANGE") ?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<? endif; ?>

		<div class="voximplant-config-page" data-role="page" data-page="recording">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_CONFIG_EDIT_RECORDING_AND_RATING") ?></div>
				<div id="voximplant-hint2" class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="vi-recording" type="checkbox" name="RECORDING"
							   data-role="enable-recording" <?= ($arResult["ITEM"]["RECORDING"] == "Y" ? 'checked' : '') ?>
							   value="Y" class="voximplant-number-settings-checkbox">
						<label for="vi-recording"
							   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_RECORD") ?></label>

						<?if ($arResult['RECORD_LIMIT']['ENABLE']):?>
							<div class="tel-lock-holder-title" title="<?=GetMessage("VI_CONFIG_LOCK_RECORD_ALT", Array("#LIMIT#" => $arResult['RECORD_LIMIT']['LIMIT'], '#REMAINING#' => $arResult['RECORD_LIMIT']['REMAINING']))?>"><div onclick="BX.UI.InfoHelper.show('limit_record_100_call_month')"  class="tel-lock tel-lock-half <?=(CVoxImplantAccount::IsDemo()? 'tel-lock-demo': '')?>"></div></div>
						<?elseif (!$arResult['RECORD_LIMIT']['ENABLE'] && $arResult['RECORD_LIMIT']['DEMO']):?>
							<div class="tel-lock-holder-title" title="<?=GetMessage("VI_CONFIG_LOCK_ALT")?>"><div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_records')"  class="tel-lock tel-lock-demo"></div></div>
						<?endif;?>
					</div>
					<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="recording-settings"
						 data-height="470px" style="max-height: <?= $arResult["ITEM"]["RECORDING"] == "Y" ? "470px" : "0"?>">
						<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_EDIT_RECORD_TIP_3") ?></div>
						<div class="ui-alert ui-alert-warning">
							<span class="ui-alert-message"><?= Loc::getMessage("VI_CONFIG_EDIT_RECORD_TIP2") ?></span>
						</div>
						<div class="voximplant-number-settings-choice">
							<input id="vi_recording_notice" type="checkbox" name="RECORDING_NOTICE"
								   <? if ($arResult["ITEM"]["RECORDING_NOTICE"] == "Y") { ?>checked<? } ?> value="Y"
								   class="voximplant-number-settings-checkbox">
							<label for="vi_recording_notice"
								   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_RECORD_NOTICE") ?></label>
						</div>
						<div class="voximplant-number-settings-choice" id="vi-recording-stereo-container">
							<input id="vi_recording_stereo" type="checkbox" name="RECORDING_STEREO"
								   <? if ($arResult["ITEM"]["RECORDING_STEREO"] == "Y") { ?>checked<? } ?> value="Y"
								   class="voximplant-number-settings-checkbox">
							<label for="vi_recording_stereo"
								   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_RECORD_STEREO") ?>
								<span data-hint="<?= Loc::getMessage("VI_CONFIG_EDIT_RECORD_STEREO_HINT") ?>"></span>
							</label>
						</div>

						<? if ($arResult["SHOW_TRANSCRIPTION"]): ?>
							<div class="voximplant-number-settings-choice">
								<input id="vi_transcribe" type="checkbox" name="TRANSCRIBE" value="Y"
									   class="voximplant-number-settings-checkbox"
									   <? if ($arResult["ITEM"]["TRANSCRIBE"] == "Y") { ?>checked<? } ?>
									   <? if (!\Bitrix\Voximplant\Transcript::isEnabled()) { ?>disabled<? } ?>>
								<label for="vi_transcribe"
									   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_TRANSCRIBE") ?></label>
								<? if (!\Bitrix\Voximplant\Transcript::isEnabled() || \Bitrix\Voximplant\Transcript::isDemo()): ?>
									<div class="tel-lock-holder-select"
										 title="<?= Loc::getMessage("VI_CONFIG_LOCK_ALT") ?>">
										<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_call_transcription')"
											 class="tel-lock <?= (\Bitrix\Voximplant\Transcript::isDemo() ? 'tel-lock-demo' : '') ?>"></div>
									</div>
								<? endif; ?>
							</div>
							<div class="voximplant-number-settings-inner">
								<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_TRANSCRIPTION_HINT", array("#URL#" => CVoxImplantMain::getPricesUrl())) ?></div>
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_TRANSCRIBE_LANGUAGE") ?></div>
								<select name="TRANSCRIBE_LANG" class="voximplant-control-select" data-role="transcribe-language-select">
									<? foreach ($arResult['TRANSCRIBE_LANGUAGES'] as $languageId => $languageName): ?>
										<option value="<?= htmlspecialcharsbx($languageId) ?>" <?= ($arResult["ITEM"]["TRANSCRIBE_LANG"] == $languageId ? "selected" : "") ?>><?= htmlspecialcharsbx($languageName) ?></option>
									<? endforeach ?>
								</select>
							</div>
							<div class="voximplant-control-row tel-set-height-animated" data-role="transcribe-provider-wrap" data-height="120px" style="max-height: <?= $arResult["ITEM"]["TRANSCRIBE_LANG"] == Language::RUSSIAN_RU ? "120px" : "0"?>">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_TRANSCRIBE_PROVIDER") ?></div>
								<select name="TRANSCRIBE_PROVIDER" class="voximplant-control-select">
									<? foreach ($arResult['TRANSCRIBE_PROVIDERS'] as $providerId => $providerName): ?>
										<option value="<?= htmlspecialcharsbx($providerId) ?>" <?= ($arResult["ITEM"]["TRANSCRIBE_PROVIDER"] == $providerId ? "selected" : "") ?>><?= htmlspecialcharsbx($providerName) ?></option>
									<? endforeach ?>
								</select>
							</div>
						<? endif ?>

					</div>
				</div>

				<div id="voximplant-hint2" class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="vi_vote" name="VOTE" type="checkbox"
							   <? if ($arResult["ITEM"]["VOTE"] == "Y") { ?>checked<? } ?> value="Y"
							   class="voximplant-number-settings-checkbox">
						<label for="vi_vote" class="voximplant-number-settings-label">
							<?= Loc::getMessage("VI_CONFIG_VOTE") ?>
						</label>
						<? if (!\Bitrix\Voximplant\Limits::canVote() || CVoxImplantAccount::IsDemo()): ?>
							<div class="tel-lock-holder-title" title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
								<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_customer_rate')"
									 class="tel-lock <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
							</div>
						<? endif; ?>
					</div>
					<div class="voximplant-number-settings-inner">
						<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_VOTE_TIP") ?></div>
					</div>
					<? if (!\Bitrix\Voximplant\Limits::canVote()): ?>
						<script type="text/javascript">
							BX.bind(BX('vi_vote'), 'change', function (e)
							{
								BX('vi_vote').checked = false;
								BX.UI.InfoHelper.show('limit_contact_center_telephony_customer_rate');
							});
						</script>
					<? endif; ?>
				</div>
			</div>
		</div>

		<div class="voximplant-config-page" data-role="page" data-page="worktime">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME") ?></div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="WORKTIME_ENABLE" name="WORKTIME_ENABLE" type="checkbox" data-role="enable-worktime"
							   value="Y"
							   <? if ($arResult["ITEM"]["WORKTIME_ENABLE"] === "Y"): ?>checked="checked"<? endif ?>
							   class="voximplant-number-settings-checkbox">
						<label for="WORKTIME_ENABLE"
							   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_ENABLE") ?></label>
					</div>
					<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="worktime-settings"
						 data-height="800px" style="max-height: <?= $arResult["ITEM"]["WORKTIME_ENABLE"] === "Y" ? "800px" : "0" ?>">
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_TIMEZONE") ?></div>
							<select name="WORKTIME_TIMEZONE" class="voximplant-control-select">
								<? if (is_array($arResult["TIME_ZONE_LIST"]) && !empty($arResult["TIME_ZONE_LIST"])): ?>
									<? foreach ($arResult["TIME_ZONE_LIST"] as $tz => $tz_name): ?>
										<option value="<?= htmlspecialcharsbx($tz) ?>"<?= ($arResult["ITEM"]["WORKTIME_TIMEZONE"] == $tz ? ' selected="selected"' : '') ?>><?= htmlspecialcharsbx($tz_name) ?></option>
									<? endforeach ?>
								<? endif ?>
							</select>
						</div>
						<div class="voximplant-control-row">
							<? if (!empty($arResult["WORKTIME_LIST_FROM"]) && !empty($arResult["WORKTIME_LIST_TO"])): ?>
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_TIME") ?></div>
								<div class="voximplant-control-select-flexible voximplant-control-date">
									<select name="WORKTIME_FROM" class="voximplant-control-select">
										<? foreach ($arResult["WORKTIME_LIST_FROM"] as $key => $val): ?>
											<option value="<?= $key ?>" <? if ($arResult["ITEM"]["WORKTIME_FROM"] == $key) echo ' selected="selected" '; ?>><?= $val ?></option>
										<? endforeach; ?>
									</select>
									<div class="voximplant-control-divide"></div>
									<select name="WORKTIME_TO" class="voximplant-control-select">
										<? foreach ($arResult["WORKTIME_LIST_TO"] as $key => $val): ?>
											<option value="<?= $key ?>" <? if ($arResult["ITEM"]["WORKTIME_TO"] == $key) echo ' selected="selected" '; ?>><?= $val ?></option>
										<? endforeach; ?>
									</select>
								</div>
							<? endif; ?>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF") ?></div>
							<select name="WORKTIME_DAYOFF[]"
									class="voximplant-control-select voximplant-control-select-multiple" multiple
									size="7">
								<? foreach ($arResult["WEEK_DAYS"] as $day): ?>
									<option value="<?= $day ?>" <?= (is_array($arResult["ITEM"]["WORKTIME_DAYOFF"]) && in_array($day, $arResult["ITEM"]["WORKTIME_DAYOFF"]) ? ' selected="selected"' : '') ?>><?= GetMessage('VI_CONFIG_WEEK_'.$day) ?></option>
								<? endforeach; ?>
							</select>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS") ?></div>
							<input name="WORKTIME_HOLIDAYS" type="text"
								   value="<?= htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_HOLIDAYS"]) ?>"
								   class="voximplant-control-input">
							<div class="voximplant-control-description">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS_EXAMPLE_EXAMPLE") ?></div>
								<?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS_EXAMPLE_DAYS") ?>
							</div>
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_RULE") ?></div>
							<select id="WORKTIME_DAYOFF_RULE" name="WORKTIME_DAYOFF_RULE"
									class="voximplant-control-select">
								<? if ($arResult['SHOW_RULE_VOICEMAIL']): ?>
									<option value="<?= CVoxImplantIncoming::RULE_VOICEMAIL ?>"<?= (CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_2") ?></option>
								<? endif ?>
								<option value="<?= CVoxImplantIncoming::RULE_PSTN_SPECIFIC ?>"<?= (CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_5") ?></option>
								<option value="<?= CVoxImplantIncoming::RULE_HUNGUP ?>"<?= (CVoxImplantIncoming::RULE_HUNGUP == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "") ?>><?= GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_4") ?></option>
							</select>
						</div>

						<div id="vi_dayoff_number" class="voximplant-control-row"
							 <? if (CVoxImplantIncoming::RULE_PSTN_SPECIFIC != $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"]): ?>style="display: none"<? endif ?>>
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_NUMBER") ?></div>
							<input name="WORKTIME_DAYOFF_NUMBER" type="text"
								   value="<?= htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_DAYOFF_NUMBER"]) ?>"
								   class="voximplant-control-input">
						</div>

						<? if ($arResult['SHOW_MELODIES']): ?>
							<?
							$dayOffMelody = array(
								"MELODY" => (array_key_exists("~WORKTIME_DAYOFF_MELODY", $arResult["ITEM"]) ? $arResult["ITEM"]["~WORKTIME_DAYOFF_MELODY"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_VOICEMAIL"])),
								"MELODY_ID" => $arResult["ITEM"]["WORKTIME_DAYOFF_MELODY"],
								"DEFAULT_MELODY" => str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $arResult["DEFAULT_MELODIES"]["MELODY_VOICEMAIL"]),
								"INPUT_NAME" => "WORKTIME_DAYOFF_MELODY"
							);
							$id = "voximplant_dayoff";
							$melodiesToLoad[$id] = $dayOffMelody;
							?>

							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<label class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_MELODY") ?></label>
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-audio-player-box">
									<div class="voximplant-audio-player">
										<? $APPLICATION->IncludeComponent(
											"bitrix:player",
											"audio",
											array(
												"PLAYER_ID" => $id."player",
												"PLAYER_TYPE" => "videojs",
												"ADVANCED_MODE_SETTINGS" => "Y",
												"ALLOW_SWF" => "N",
												"AUTOSTART" => "N",
												"HEIGHT" => "30",
												"MUTE" => "N",
												"PATH" => $dayOffMelody["MELODY"],
												"TYPE" => "audio/mp3",
												"PLAYLIST_SIZE" => "180",
												"PRELOAD" => "Y",
												"PREVIEW" => "",
												"REPEAT" => "none",
												"SHOW_CONTROLS" => "Y",
												"SKIN" => "timeline_player.css",
												"SKIN_PATH" => "/bitrix/js/crm/",
												"USE_PLAYLIST" => "N",
												"VOLUME" => "100",
												"WIDTH" => "350",
												"COMPONENT_TEMPLATE" => "audio",
												"SIZE_TYPE" => "absolute",
												"START_TIME" => "0",
												"PLAYBACK_RATE" => "1"
											),
											false
										); ?>
									</div>
									<div class="voximplant-audio-player-control">
										<? $APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
											array(
												'INPUT_CAPTION' => GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE"),
												'INPUT_NAME' => $dayOffMelody["INPUT_NAME"],
												'INPUT_VALUE' => array($dayOffMelody["MELODY_ID"]),
												'MAX_FILE_SIZE' => 2097152,
												'MODULE_ID' => 'voximplant',
												'FORCE_MD5' => true,
												'CONTROL_ID' => $id,
												'MULTIPLE' => 'N',
												'ALLOW_UPLOAD' => 'F',
												'ALLOW_UPLOAD_EXT' => 'mp3'
											),
											$this->component,
											array("HIDE_ICONS" => true)
										); ?>
										<div id="<?= $id ?>span" class="voximplant-melody">
											<span class="voximplant-link"><?= Loc::getMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE") ?></span>
											<span id="<?= $id ?>notice" class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP") ?></span>
										</div>
										<span id="<?=$id?>default" class="voximplant-melody" <?if ($dayOffMelody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
											<span class="voximplant-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
										</span>
									</div>
								</div>
							</div>
						<? endif; ?>

					</div>
				</div>
			</div>
			<script>
				BX.ready(function ()
				{
					BX.bind(BX('WORKTIME_DAYOFF_RULE'), 'change', function (e)
					{
						if (this.value === '<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>')
						{
							BX('vi_dayoff_number').style.display = '';
							BX.removeClass(BX('vi-dayoff-melody'), 'tel-dayoff-melody-visible');
						}
						else if (this.value === '<?=CVoxImplantIncoming::RULE_VOICEMAIL?>')
						{
							BX('vi_dayoff_number').style.display = 'none';
							BX.addClass(BX('vi-dayoff-melody'), 'tel-dayoff-melody-visible');
						}
						else
						{
							BX.removeClass(BX('vi-dayoff-melody'), 'tel-dayoff-melody-visible');
							BX('vi_dayoff_number').style.display = 'none';
						}
					});
				});
			</script>
		</div>

		<div class="voximplant-config-page" data-role="page" data-page="other">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_CONFIG_EDIT_OTHER") ?></div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="vi_can_be_selected"
							   class="voximplant-number-settings-checkbox" value="Y"
							   type="checkbox"
							   name="CAN_BE_SELECTED"
							   <? if ($arResult["ITEM"]["CAN_BE_SELECTED"] === "Y"): ?>checked="checked"<? endif ?>
								data-role="number-selection"
							   data-locked="<?= (\Bitrix\Voximplant\Limits::canSelectLine() ? "N" : "Y") ?>"
							   data-license-popup="limit_contact_center_telephony_line_selection"
						/>
						<label for="vi_can_be_selected"
							   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_ALLOW_TO_SELECT_NUMBER_FOR_OUTGOING_CALL") ?></label>
						<? if (!\Bitrix\Voximplant\Limits::canSelectLine() || CVoxImplantAccount::IsDemo()): ?>
							<div class="tel-lock-holder-select" title="<?= GetMessage("VI_CONFIG_LOCK_ALT") ?>">
								<div onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_line_selection')"
									 class="tel-lock <?= (CVoxImplantAccount::IsDemo() ? 'tel-lock-demo' : '') ?>"></div>
							</div>
						<? endif; ?>
					</div>
					<div class="voximplant-number-settings-inner tel-set-height-animated"
						 data-role="number-selection-settings" data-height="250px" style="max-height: <?=$arResult["ITEM"]["CAN_BE_SELECTED"] == "Y" ? "250px" : "0"?>">
						<? if ($arResult["ITEM"]["PORTAL_MODE"] != CVoxImplantConfig::MODE_GROUP): ?>
							<div id="voximplant-hint3" class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_LINE_PREFIX") ?></div>
								<div class="voximplant-control-select-flexible">
									<input id="vi-line-prefix" name="LINE_PREFIX" type="text"
										   value="<?= htmlspecialcharsbx($arResult["ITEM"]["LINE_PREFIX"]) ?>"
										   class="voximplant-control-input" size="10" maxlength="10"
										   data-role="input-line-prefix">
									<span data-hint="<?= Loc::getMessage("VI_CONFIG_LINE_PREFIX_HINT") ?>"></span>
								</div>
							</div>
						<? endif ?>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_LINE_ALLOWED_USERS") ?></div>
							<div>
								<?
								$APPLICATION->IncludeComponent('bitrix:main.user.selector', '', [
									"ID" => "voximplant-line-access",
									"LIST" => $arResult["ITEM"]["LINE_ACCESS"],
									"INPUT_NAME" => "LINE_ACCESS[]",
									"USE_SYMBOLIC_ID" => true,
									"SELECTOR_OPTIONS" => [
										"departmentSelectDisable" => "N",
									]
								]);
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="vi_callback_redial" name="CALLBACK_REDIAL" type="checkbox" value="Y"
							   <? if ($arResult["ITEM"]["CALLBACK_REDIAL"] === "Y"): ?>checked="checked"<? endif ?>
							   class="voximplant-number-settings-checkbox" data-role="callback-redial">
						<label for="vi_callback_redial"
							   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_CALLBACK_REDIAL") ?></label>
					</div>
					<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="callback-redial-settings"
						 data-height="100px" style="max-height: <?= $arResult["ITEM"]["CALLBACK_REDIAL"] === "Y" ? "100px" : "0"?>">
						<div class="voximplant-control-multiple-inline voximplant-control-missed-call">
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_CALLBACK_REDIAL_ATTEMPTS") ?></div>
								<select name="CALLBACK_REDIAL_ATTEMPTS" class="voximplant-control-select">
									<? foreach (array(1, 2, 3, 4, 5) as $k): ?>
										<option value="<?= $k ?>"<?= ($k == $arResult["ITEM"]["CALLBACK_REDIAL_ATTEMPTS"] ? " selected" : "") ?>><?= $k ?></option>
									<? endforeach; ?>
								</select>
							</div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_CALLBACK_REDIAL_PERIOD") ?></div>
								<select name="CALLBACK_REDIAL_PERIOD" class="voximplant-control-select">
									<? foreach (array(60, 120, 180) as $k): ?>
										<option value="<?= $k ?>"<?= ($k == $arResult["ITEM"]["CALLBACK_REDIAL_PERIOD"] ? " selected" : "") ?>><?= $k ?> <?= GetMessage('VI_CONFIG_CALLBACK_REDIAL_PERIOD_SECONDS') ?></option>
									<? endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
						<input id="vi_use_specific_backup_number" name="USE_SPECIFIC_BACKUP_NUMBER" type="checkbox"
							   class="voximplant-number-settings-checkbox"
							   value="Y" <?= ($arResult["ITEM"]["BACKUP_NUMBER"] == "" ? "" : "checked") ?>
								data-role="backup-number">
						<label for="vi_use_specific_backup_number"
							   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_SET_USE_SPECIFIC_BACKUP_NUMBER_USE") ?></label>
					</div>
					<div class="voximplant-number-settings-inner tel-set-height-animated" data-role="backup-number-settings" data-height="230px"
						 style="max-height: <?=$arResult["ITEM"]["BACKUP_NUMBER"] == "" ? "0" : "230px"?>">
						<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_SET_BACKUP_NUMBER") ?></div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_BACKUP_NUMBER") ?></div>
							<input name="BACKUP_NUMBER" type="text"
								   value="<?= htmlspecialcharsbx($arResult["ITEM"]["BACKUP_NUMBER"]) ?>" size="15"
								   maxlength="20" class="voximplant-control-input">
						</div>
						<div class="voximplant-control-row">
							<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_BACKUP_LINE") ?></div>
							<select name="BACKUP_LINE" class="voximplant-control-select">
								<? foreach ($arResult['BACKUP_LINES'] as $k => $v): ?>
									<option value="<?= $k ?>" <?= ($arResult["ITEM"]["BACKUP_LINE"] == $k ? "selected" : "") ?>><?= $v ?></option>
								<? endforeach; ?>
							</select>
						</div>
					</div>
				</div>
				<? if ($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_SIP): ?>
					<div class="voximplant-number-settings-row">
						<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
							<input id="FORWARD_LINE_ENABLED" name="FORWARD_LINE_ENABLED" type="checkbox" value="Y"
								   <? if ($arResult["ITEM"]["FORWARD_LINE"] !== CVoxImplantConfig::FORWARD_LINE_DEFAULT): ?>checked="checked"<? endif ?>
								   class="voximplant-number-settings-checkbox">
							<label for="FORWARD_LINE_ENABLED"
								   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_EDIT_FORWARD_NUMBER") ?></label>
						</div>
						<div class="voximplant-number-settings-inner">
							<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_EDIT_FORWARD_NUMBER_TIP") ?></div>
							<div class="voximplant-control-row">
								<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_FORWARD_TITLE") ?></div>
								<select name="FORWARD_LINE" class="voximplant-control-select">
									<? foreach ($arResult['FORWARD_LINES'] as $k => $v): ?>
										<option value="<?= $k ?>" <? if ($arResult["ITEM"]["FORWARD_LINE"] == $k): ?> selected <? endif; ?>><?= $v ?></option>
									<? endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				<? endif ?>
				<? if ($arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_RENT || $arResult["ITEM"]["PORTAL_MODE"] === CVoxImplantConfig::MODE_GROUP): ?>
					<div class="voximplant-number-settings-row">
						<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
							<input id="redirect-with-client-number" name="REDIRECT_WITH_CLIENT_NUMBER" type="checkbox"
								   <? if ($arResult["ITEM"]["REDIRECT_WITH_CLIENT_NUMBER"] == "Y") { ?>checked<? } ?>
								   value="Y" class="voximplant-number-settings-checkbox">
							<label for="redirect-with-client-number"
								   class="voximplant-number-settings-label"><?= Loc::getMessage("VI_CONFIG_REDIRECT_WITH_CLIENT_NUMBER") ?></label>
						</div>
						<div class="voximplant-number-settings-inner">
							<div class="voximplant-number-settings-text"><?= Loc::getMessage("VI_CONFIG_REDIRECT_WITH_CLIENT_NUMBER_TIP") ?></div>
						</div>
					</div>
				<? endif; ?>
			</div>
		</div>

		<div class="voximplant-config-page" data-role="page" data-page="melodies">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VI_CONFIG_EDIT_TUNES") ?></div>
				<div class="voximplant-number-settings-row">
					<div class="voximplant-control-row">
						<div class="voximplant-control-subtitle"><?= Loc::getMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE2") ?></div>
						<select class="voximplant-control-select" name="MELODY_LANG">
							<?foreach (CVoxImplantConfig::GetMelodyLanguages() as $k):?>
								<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["MELODY_LANG"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE_".$k)?></option>
							<?endforeach;?>
						</select>
					</div>
					<? foreach ($arResult["MELODIES"] as $id => $melody): ?>
						<? $melodiesToLoad[$id] = $melody; ?>
						<div class="voximplant-melody-container" data-role="melody-container-<?=$id?>">
							<div class="voximplant-number-settings-choice voximplant-number-settings-bold-text">
								<span class="voximplant-number-settings-label"><?= htmlspecialcharsbx($melody["TITLE"])?></span>
							</div>
							<div class="voximplant-number-settings-text"><?= htmlspecialcharsbx($melody["TIP"])?></div>
							<div class="voximplant-control-row">
								<div class="voximplant-audio-player-box">
									<div class="voximplant-audio-player">
										<? $APPLICATION->IncludeComponent(
											"bitrix:player",
											"audio",
											array(
												"PLAYER_ID" => $id."player",
												"PLAYER_TYPE" => "videojs",
												"ADVANCED_MODE_SETTINGS" => "Y",
												"ALLOW_SWF" => "N",
												"AUTOSTART" => "N",
												"HEIGHT" => "30",
												"MUTE" => "N",
												"PATH" => $melody["MELODY"],
												"TYPE" => "audio/mp3",
												"PLAYLIST_SIZE" => "180",
												"PRELOAD" => "Y",
												"PREVIEW" => "",
												"REPEAT" => "none",
												"SHOW_CONTROLS" => "Y",
												"SKIN" => "timeline_player.css",
												"SKIN_PATH" => "/bitrix/js/crm/",
												"USE_PLAYLIST" => "N",
												"VOLUME" => "100",
												"WIDTH" => "350",
												"COMPONENT_TEMPLATE" => "audio",
												"SIZE_TYPE" => "absolute",
												"START_TIME" => "0",
												"PLAYBACK_RATE" => "1"
											),
											false
										); ?>
									</div>

									<div class="voximplant-audio-player-control">
										<? $APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
											array(
												'INPUT_CAPTION' => GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE"),
												'INPUT_NAME' => $melody["INPUT_NAME"],
												'INPUT_VALUE' => array($melody["MELODY_ID"]),
												'MAX_FILE_SIZE' => 2097152,
												'MODULE_ID' => 'voximplant',
												'FORCE_MD5' => true,
												'CONTROL_ID' => $id,
												'MULTIPLE' => 'N',
												'ALLOW_UPLOAD' => 'F',
												'ALLOW_UPLOAD_EXT' => 'mp3'
											),
											$this->component,
											array("HIDE_ICONS" => true)
										); ?>
										<div id="<?= $id ?>span" class="voximplant-melody">
											<span class="voximplant-link"><?= Loc::getMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE") ?></span>
											<span id="<?= $id ?>notice" class="voximplant-control-description"><?= Loc::getMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP") ?></span>
										</div>
										<span id="<?=$id?>default" class="voximplant-melody" <?if ($melody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
										<span class="voximplant-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
									</span>
									</div>
								</div>
							</div>
						</div>
					<? endforeach; ?>

					<div class="ui-btn ui-btn-sm ui-btn-light-border voximplant-audio-player-btn" data-role="more-tunes">
						<?= Loc::getMessage("VI_CONFIG_MORE_TONES")?>
					</div>
					<div class="ui-alert ui-alert-warning">
						<span class="ui-alert-message">
							<?= Loc::getMessage("VI_CONFIG_EDIT_TUNES_TIP")?>
						</span>
					</div>
				</div>
			</div>

		</div>

		<div class="voximplant-config-page" data-role="page" data-page="unlink">
			<div class="voximplant-container">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_CONFIG_NUMBER_DISCONNECTION") ?></div>
				<div class="voximplant-number-settings-row">
					<? if($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_RENT): ?>
						<? if($arResult["NUMBER"]["TO_DELETE"] == "Y"): ?>
							<div class="voximplant-number-settings-text">
								<?= Loc::getMessage("VOX_CONFIG_NUMBER_SET_TO_DELETE", [
									"#DATE#" => $arResult["NUMBER"]["DATE_DELETE"] ? $arResult["NUMBER"]["DATE_DELETE"]->toString() : "aa"
								]) ?>
							</div>
							<span class="ui-btn ui-btn-primary" data-role="cancel-number-delete" data-number="<?= htmlspecialcharsbx($arResult["NUMBER"]["NUMBER"]) ?>">
								<?= Loc::getMessage("VOX_CONFIG_CANCEL_DELETE_NUMBER") ?>
							</span>
						<? else: ?>
							<div class="voximplant-number-settings-text"><?= Loc::getMessage("VOX_CONFIG_NO_PAYBACK") ?></div>
							<span class="ui-btn ui-btn-danger" data-role="delete-number" data-number="<?= htmlspecialcharsbx($arResult["NUMBER"]["NUMBER"])?>">
								<?= Loc::getMessage("VOX_CONFIG_DELETE_NUMBER") ?>
							</span>
						<? endif ?>
					<? elseif($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_LINK): ?>
						<span class="ui-btn ui-btn-danger" data-role="delete-caller-id" data-number="<?= htmlspecialcharsbx($arResult["CALLER_ID"]["NUMBER"]) ?>">
							<?= Loc::getMessage("VOX_CONFIG_DELETE_NUMBER") ?>
						</span>
					<? endif ?>
				</div>
			</div>
		</div>

		<div data-role="button-panel">
			<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
				'BUTTONS' => [
					'save',
					'cancel' => '/telephony/'
				]
			]);?>
		</div>
	</form>
</div>

<script>
	BX.ready(function (e)
	{
		BX.Voximplant.ConfigEditor.setDefaults({
			isPaid: <?= CVoxImplantAccount::IsPro() ? 'true' : 'false' ?>,
			isDemo: <?= CVoxImplantAccount::IsDemo() ? 'true' : 'false' ?>,
			isTimemanInstalled: <?= IsModuleInstalled("timeman") ? 'true' : 'false' ?>,
			isBitrix24: <?= IsModuleInstalled("bitrix24") ? 'true' : 'false' ?>,
			ivrEnabled: <?= \Bitrix\Voximplant\Ivr\Ivr::isEnabled() ? 'true' : 'false' ?>,
			canSelectLine: <?= \Bitrix\Voximplant\Limits::canSelectLine() ? 'true' : 'false' ?>,
			maximumGroups: <?= (int)$arResult['DEFAULTS']['MAXIMUM_GROUPS']?>
		});

		BX.voximplantConfigEditor = new BX.Voximplant.ConfigEditor({
			node: BX('vi-editor-root'),
			melodies: <?= CUtil::PhpToJSObject($melodiesToLoad)?>,
			accessCodes: <?= CUtil::PhpToJSObject($arResult['ITEM']['LINE_ACCESS'])?>,
			portalMode: '<?= CUtil::JSEscape($arResult['ITEM']['PORTAL_MODE'])?>',
			sipConfig: <?= CUtil::PhpToJSObject($arResult['SIP_CONFIG'])?>
		});
		BX.UI.Hint.init(BX('vi-recording-stereo-container'));
	});
</script>