<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(["ui.tilegrid", "ui.buttons", "ui.forms", "ui.alerts", "ui.hint", "voximplant.callerid", "voximplant.numberrent"]);
CJSCore::Init(["voximplant.common", "sidepanel", "applayout"]);

$APPLICATION->SetPageProperty('BodyClass', 'no-all-paddings no-background no-hidden');

?>

<div class="voximplant-start-wrap">
	<div class="voximplant-start-head-box-container">
		<div class="voximplant-start-head-box">
			<div class="voximplant-start-head-box-title">
				<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_START_ACCOUNT_BALANCE") ?></div>
				<? if($arResult['HAS_BALANCE'] && $arResult["LINK_TO_BUY"] && $arResult["SHOW_PAY_BUTTON"]): ?>
					<a href="<?=$arResult["LINK_TO_BUY"]?>" target="_blank" class="ui-btn ui-btn-sm ui-btn-primary"><?= Loc::getMessage("VOX_START_TOP_UP") ?></a>
				<? endif ?>
			</div>
			<div class="voximplant-start-head-box-content">
				<div class="voximplant-start-head-box-inner">
					<div class="voximplant-start-head-box-row-amount">
						<? if(isset($arResult['SIP'])): ?>
							<div class="voximplant-start-head-info voximplant-start-sip-info">
								<div class="voximplant-start-head-info-item voximplant-start-head-subtitle"><?= Loc::getMessage("VOX_START_SIP_CONNECTOR") ?></div>
								<? if($arResult['SIP']['PAID']): ?>
									<div class="voximplant-start-head-info-item voximplant-start-head-entity">
										<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_PAID_UNTIL", [
											"#DATE#" => $arResult["SIP"]["PAID_UNTIL"]
										])?>
									</div>
								<? else: ?>
									<div class="voximplant-start-head-info-item voximplant-start-head-entity">
										<?= Loc::getMessage("VOX_START_SIP_CONNECTOR_FREE_MINUTES", [
											"#MINUTES#" => "<strong>" . $arResult["SIP"]["FREE_MINUTES"] . "</strong>"
										]) ?>
									</div>
								<? endif ?>
							</div>
						<? endif ?>
						<div class="voximplant-start-head-box-info-sum">
							<? if($arResult['HAS_BALANCE']): ?>
								<div class="voximplant-start-head-subtitle"><?= Loc::getMessage("VOX_START_CURRENT_BALANCE") ?></div>
								<div class="voximplant-start-head-box-amount currency-<?=$arResult["BALANCE_CURRENCY"]?>" title="<?=$arResult['ACCOUNT_BALANCE_FORMATTED']?>">
									<?= $arResult['ACCOUNT_BALANCE_FORMATTED']?>
								</div>
							<? else: ?>
								<div class="voximplant-start-head-box-no-balance"><?= GetMessage("VOX_START_NO_BALANCE")?></div>
							<? endif ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<? if($arResult["SHOW_LINES"]): ?>
			<div class="voximplant-start-head-box voximplant-start-head-payment-box">
				<div class="voximplant-start-head-box-title">
					<div class="voximplant-title-dark"><?= Loc::getMessage("VOX_START_MY_NUMBERS") . (count($arResult["NUMBERS_LIST"]) > 0 ? " (" . count($arResult["NUMBERS_LIST"]) . ")" : "")  ?></div>
					<? if (count($arResult["NUMBERS_LIST"]) > 0): ?>
						<div id="my-numbers" class="ui-btn ui-btn-sm ui-btn-light-border"><?= Loc::getMessage("VOX_START_SET_UP") ?></div>
					<? endif ?>
				</div>
				<div class="voximplant-start-head-box-content">
					<? if (count($arResult["NUMBERS_LIST"]) > 0): ?>
						<?
						$hasRentedNumbers = false;
						$arResult["NUMBERS_LIST"] = array_slice($arResult["NUMBERS_LIST"], 0, 3);
						foreach ($arResult["NUMBERS_LIST"] as $item): ?>
							<?
							switch ($item["TYPE"])
							{
								case CVoxImplantConfig::MODE_RENT:
									$class = "voximplant-start-payment voximplant-start-payment-rented-number";
									$hasRentedNumbers = true;
									break;
								case CVoxImplantConfig::MODE_LINK:
									$class = "voximplant-start-payment voximplant-start-payment-anchored-number";
									break;
								case CVoxImplantConfig::MODE_SIP:
									$class = "voximplant-start-payment voximplant-start-payment-sip-connector";
									break;
								default:
									$class = "voximplant-start-payment";
							}
							?>
							<div class="<?=$class?>">
								<div class="voximplant-start-payment-icon"></div>
								<div class="voximplant-start-text-dark-bold"><?= htmlspecialcharsbx($item["NAME"])?></div>
								<div class="voximplant-start-division"></div>
								<div class="voximplant-start-text-darkgrey"><?= htmlspecialcharsbx($item["DESCRIPTION"])?></div>
							</div>
						<? endforeach; ?>

						<? if($hasRentedNumbers): ?>
						<div class="voximplant-start-payment-btn-box">
							<div class="voximplant-start-text-darkgrey"><?= Loc::getMessage("VOX_START_AUTO_PROLONG") ?></div>
						</div>
						<? endif ?>
					<? else: ?>
						<div class="voximplant-start-head-box-info"><?= Loc::getMessage("VOX_START_RENT_OR_LINK_NUMBER") ?></div>
					<? endif ?>
				</div>
			</div>
		<? endif ?>
	</div>

	<? if(count($arResult['MENU']['MAIN'])): ?>
		<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_TELEPHONY") ?></div>
		<div id="voximplant-grid-block" class="voximplant-grid"></div>
	<? endif ?>
	<? if(count($arResult['MENU']['SETTINGS'])): ?>
		<div class="voximplant-title-light"><?= Loc::getMessage("VOX_START_TELEPHONY_SETTINGS") ?></div>
		<div id="voximplant-grid-settings-block" class="voximplant-grid"></div>
	<? endif ?>
	<? if(count($arResult['MENU']['PARTNERS'])): ?>
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
	});
	BX.Voximplant.Start.init({
		mainMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['MAIN'])?>,
		settingsMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['SETTINGS'])?>,
		partnersMenuItems: <?= CUtil::PhpToJSObject($arResult['MENU']['PARTNERS'])?>,
		applicationUrlTemplate: '<?= CUtil::JSEscape($arResult['MARKETPLACE_DETAIL_URL_TPL']) ?>',
		isRestOnly: '<?= $arResult['IS_REST_ONLY'] ? 'Y' : 'N' ?>'
	});
</script>
