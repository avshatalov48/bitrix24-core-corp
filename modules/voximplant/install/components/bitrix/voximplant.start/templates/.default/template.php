<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	"popup",
	"sidepanel",
	"applayout",
	"ui.tilegrid",
	"ui.buttons",
	"ui.forms",
	"ui.alerts",
	"ui.hint",
	"ui.dialogs.messagebox",
	"voximplant.callerid",
	"voximplant.numberrent",
	"voximplant.common",
	"currency",
]);

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
$bodyClass = $APPLICATION->getPageProperty("BodyClass");
$APPLICATION->setPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."no-all-paddings no-background no-hidden");
$voxStartRentOrLink =
	\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() === 'ua'
	|| \Bitrix\Main\Application::getInstance()->getContext()->getLanguage() === 'ua'
		? GetMessageJS("VOX_START_LINK_NUMBER")
		: GetMessageJS("VOX_START_RENT_OR_LINK_NUMBER")
;

$isRussianRegion = in_array(
	\Bitrix\Main\Application::getInstance()->getLicense()->getRegion(),
	\Bitrix\Voximplant\Limits::getRussianRegions()
);
?>

<? if ($arResult['ERROR_MESSAGE'] ?? null): ?>
	<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
		<span class="ui-alert-message"><?= htmlspecialcharsbx($arResult['ERROR_MESSAGE'])?></span>
	</div>
<? else: ?>
	<div class="voximplant-start-wrap">
		<? if ($arResult['SHOW_VOXIMPLANT']): ?>
			<div class="voximplant-start-head-box-container">
				<div class="voximplant-start-head-box">
					<div class="voximplant-start-head-box-title">
						<select id="balance-type" class="voximplant-control-select" name="BALANCE_TYPE">
							<option value="balance" <?= $arResult["BALANCE_TYPE"] === "balance" ? "selected": ""?>><?= Loc::getMessage("VOX_START_ACCOUNT_BALANCE_MSGVER_1")?></option>
							<option value="sip" <?= $arResult["BALANCE_TYPE"] === "sip" ? "selected": ""?>><?= Loc::getMessage("VOX_START_ACCOUNT_SIP_CONNECTOR")?></option>
						</select>
						<? if($arResult['HAS_BALANCE'] && $arResult["SHOW_PAY_BUTTON"]): ?>
							<div
								id="vox-charge-balance-button"
								style="display:none"
								<? if($isRussianRegion): ?>
								data-hint="<?=htmlspecialcharsbx(Loc::getMessage("VOX_NOT_BITRIX_BALANCE_HINT"))?>"
								data-hint-no-icon
								<? endif ?>
								data-for-balance-type="balance"
							>
								<div class="ui-btn-split ui-btn-primary ui-btn-sm">
									<button id="balance-top-up" class="ui-btn-main  "><?= Loc::getMessage("VOX_START_TOP_UP") ?></button>
									<button id="balance-menu" class="ui-btn-menu"></button>
								</div>
							</div>
						<? endif ?>
						<? if(isset($arResult['SIP'])): ?>
							<div style="display:none" data-for-balance-type="sip">
								<button id="sip-buy" class="ui-btn ui-btn-sm ui-btn-primary">
									<? if($arResult['SIP']['PAID']): ?>
										<?= Loc::getMessage("VOX_START_SIP_PROLONG") ?>
									<? else: ?>
										<?= Loc::getMessage("VOX_START_SIP_BUY") ?>
									<? endif ?>
								</button>
							</div>
						<? endif ?>
					</div>
					<div class="voximplant-start-head-box-content">
						<div class="voximplant-start-head-box-inner">
							<? if(isset($arResult['SIP'])): ?>
								<div class="voximplant-start-head-box-row-amount" data-for-balance-type="sip" style="display: none;">
									<div class="voximplant-start-head-info">
										<? if($arResult['SIP']['PAID']): ?>
											<div class="voximplant-start-head-info-item voximplant-start-head-entity">
												<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_PAID_UNTIL", [
														"#DATE#" => "<strong>" . $arResult["SIP"]["PAID_UNTIL"] . "</strong>"
												])?>
												<p class="voximplant-start-head-entity">
													<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_PAID_NOTICE")?>
												</p>
											</div>
										<? else: ?>
											<div class="voximplant-start-head-info-item voximplant-start-head-entity">
												<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_FREE_MINUTES", [
													"#MINUTES#" => "<strong>" . $arResult["SIP"]["FREE_MINUTES"] . "</strong>"
												]) ?>
											</div>
											<p class="voximplant-start-head-entity">
												<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_FREE_MINUTES_NOTICE") ?>
											</p>
										<? endif ?>
									</div>
								</div>
							<? endif ?>
							<div class="voximplant-start-head-box-row-amount right" data-for-balance-type="balance" style="display:none;">
								<div class="voximplant-start-head-box-info-sum">
									<? if($arResult['HAS_BALANCE']): ?>
										<div class="voximplant-start-head-subtitle"><?= Loc::getMessage("VOX_START_CURRENT_BALANCE") ?></div>
										<div id="voximplant-balance" class="voximplant-start-head-box-amount currency-<?=$arResult["BALANCE_CURRENCY"]?>" title="<?=$arResult['ACCOUNT_BALANCE_FORMATTED']?>">
											<?= $arResult['ACCOUNT_BALANCE_FORMATTED']?>
										</div>
									<? else: ?>
										<div class="voximplant-start-head-box-no-balance"><?= GetMessage("VOX_START_NO_BALANCE")?></div>
									<? endif ?>
								</div>
							</div>
						</div>
					</div>
					<? if($arResult['RECORD_LIMIT']['ENABLE']): ?>
						<div class="voximplant-start-record-limit-info">
							<?= Loc::getMessage("VOX_START_RECORD_LIMIT", [
								"#STRONG_START#" => "<strong>",
								"#STRONG_END#" => "</strong>",
								"#RECORDS_REMAINING#" => $arResult['RECORD_LIMIT']['USED'],
								"#RECORDS_TOTAL#" => $arResult['RECORD_LIMIT']['LIMIT'],
							])?>

						</div>
					<? endif; ?>
				</div>

				<? if($arResult["SHOW_LINES"]): ?>
					<div id="my-numbers-list" class="voximplant-start-head-box voximplant-start-head-payment-box"></div>
				<? endif ?>
			</div>
		<? endif ?>

		<? if(!empty($arResult['MENU']['MAIN']) && is_array($arResult['MENU']['MAIN'])): ?>
			<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_TELEPHONY") ?></div>
			<div id="voximplant-grid-block" class="voximplant-grid"></div>
		<? endif ?>
		<? if(!empty($arResult['MENU']['SETTINGS']) && is_array($arResult['MENU']['SETTINGS'])): ?>
			<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_TELEPHONY_SETTINGS") ?></div>
			<div id="voximplant-grid-settings-block" class="voximplant-grid"></div>
		<? endif ?>
		<?php if(!empty($arResult['MENU']['CRM']) && is_array($arResult['MENU']['CRM'])): ?>
			<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_TELEPHONY_CRM_INTEGRATION") ?></div>
			<div id="voximplant-grid-crm-block" class="voximplant-grid"></div>
		<?php endif;?>
		<? if(!empty($arResult['MENU']['PARTNERS']) && is_array($arResult['MENU']['PARTNERS'])): ?>
			<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_PARTNERS") ?></div>
			<div id="marketplace-grid-block" class="voximplant-grid"></div>
		<? endif ?>
	</div>

	<script>
		BX.message({
			"VOX_START_NUMBER_RENT": '<?=GetMessageJS("VOX_START_NUMBER_RENT")?>',
			"VOX_START_5_NUMBER_RENT": '<?=GetMessageJS("VOX_START_5_NUMBER_RENT")?>',
			"VOX_START_10_NUMBER_RENT": '<?=GetMessageJS("VOX_START_10_NUMBER_RENT")?>',
			"VOX_START_SIP_PBX": '<?=GetMessageJS("VOX_START_SIP_PBX")?>',
			"VOX_START_CALLER_ID": '<?=GetMessageJS("VOX_START_CALLER_ID")?>',
			"VOX_START_NUMBERS": '<?=GetMessageJS("VOX_START_NUMBERS")?>',
			"VOX_START_CALLER_IDS": '<?=GetMessageJS("VOX_START_CALLER_IDS")?>',
			"VOX_START_UPLOAD_DOCUMENTS": '<?=GetMessageJS("VOX_START_UPLOAD_DOCUMENTS")?>',
			"VOX_START_CONFIGURE_NUMBERS": '<?=GetMessageJS("VOX_START_CONFIGURE_NUMBERS")?>',
			"VOX_START_CONFIGURE_TELEPHONY": '<?=GetMessageJS("VOX_START_CONFIGURE_TELEPHONY")?>',
			"VOX_START_ACCESS_CONTROL": '<?=GetMessageJS("VOX_START_ACCESS_CONTROL")?>',
			"VOX_START_SIP_PHONES": '<?=GetMessageJS("VOX_START_SIP_PHONES")?>',
			"VOX_START_CONFIGURE_USERS": '<?=GetMessageJS("VOX_START_CONFIGURE_USERS")?>',
			"VOX_START_COMMON_SETTINGS": '<?=GetMessageJS("VOX_START_COMMON_SETTINGS")?>',
			"VOX_START_CONFIGURE_GROUPS": '<?=GetMessageJS("VOX_START_CONFIGURE_GROUPS")?>',
			"VOX_START_CONFIGURE_IVR": '<?=GetMessageJS("VOX_START_CONFIGURE_IVR")?>',
			"VOX_START_CONFIGURE_BLACK_LIST": '<?=GetMessageJS("VOX_START_CONFIGURE_BLACK_LIST")?>',
			"VOX_START_TARIFFS": '<?=GetMessageJS("VOX_START_TARIFFS")?>',
			"VOX_START_MY_NUMBERS": '<?=GetMessageJS("VOX_START_MY_NUMBERS")?>',
			"VOX_START_RENT_OR_LINK_NUMBER": '<?=$voxStartRentOrLink?>',
			"VOX_START_SET_UP": '<?=GetMessageJS("VOX_START_SET_UP")?>',
			"VOX_START_AUTO_PROLONG": '<?=GetMessageJS("VOX_START_AUTO_PROLONG")?>',
			"VOX_START_CRM_INTEGRATION_FORM_CREATE": '<?=GetMessageJS("VOX_START_CRM_INTEGRATION_FORM_CREATE")?>',
			"VOX_START_CRM_INTEGRATION_FORM_LIST": '<?=GetMessageJS("VOX_START_CRM_INTEGRATION_FORM_LIST")?>',
			"VOX_START_CRM_INTEGRATION_FORM_HELP": '<?=GetMessageJS("VOX_START_CRM_INTEGRATION_FORM_HELP")?>',
			"VOX_START_SIP_BUY_POPUP_TEXT": '<?=GetMessageJS("VOX_START_SIP_BUY_POPUP_TEXT")?>',
			"VOX_START_POPUP_BUTTON_I_AGREE": '<?=GetMessageJS("VOX_START_POPUP_BUTTON_I_AGREE")?>',
			"VOX_START_POPUP_BUTTON_CANCEL": '<?=GetMessageJS("VOX_START_POPUP_BUTTON_CANCEL")?>',
		});
		BX.Voximplant.Start.init({
			lines: <?= CUtil::PhpToJSObject($arResult['NUMBERS_LIST'])?>,
			mainMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['MAIN'])?>,
			settingsMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['SETTINGS'])?>,
			partnersMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['PARTNERS'])?>,
			crmIntegrationMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['CRM'])?>,
			crmFormListUrl: '<?= CUtil::JSEscape($arResult['CRM_CALLBACK_FORM_LIST_URL']) ?>',
			crmFormCreateUrl: '<?= CUtil::JSEscape($arResult['CRM_CALLBACK_FORM_CREATE_URL']) ?>',
			applicationUrlTemplate: '<?= CUtil::JSEscape($arResult['MARKETPLACE_DETAIL_URL_TPL']) ?>',
			tariffsUrl: '<?= CUtil::JSEscape($arResult['LINK_TO_TARIFFS']) ?>',
			linkToBuySip: '<?= CUtil::JSEscape($arResult["LINK_TO_BUY_SIP"]) ?>',
			isRestOnly: '<?= $arResult['IS_REST_ONLY'] ? 'Y' : 'N' ?>',
			isTelephonyAvailable: '<?= $arResult['TELEPHONY_AVAILABLE'] ? 'Y' : 'N' ?>',
			isShownPrivacyPolicy: '<?= $arResult['IS_SHOWN_PRIVACY_POLICY'] ? 'Y' : 'N' ?>',
			isRussianRegion: '<?= $isRussianRegion ? 'Y' : 'N' ?>',
		});
	</script>
<? endif ?>