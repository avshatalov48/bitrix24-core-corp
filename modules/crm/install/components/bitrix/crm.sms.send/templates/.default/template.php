<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
]);
\CJSCore::init(["loader", "popup", "sidepanel"]);
$this->IncludeLangFile();
?>
	<div class="crm-sms-send" id="<?=\CUtil::JSEscape($arResult['containerId']);?>">
		<?php if(!$arResult['canSendMessage']): ?>
			<div class="sms-conditions-container">
				<div class="sms-conditions">
					<strong><?=Loc::getMessage("CRM_SMS_MANAGE_TEXT_1")?></strong><br>
					<?=Loc::getMessage("CRM_SMS_MANAGE_TEXT_2")?><br>
					<?=Loc::getMessage("CRM_SMS_MANAGE_TEXT_3_MSGVER_1")?>
				</div>
			</div>
			<div class="new-sms-btn-container">
				<a href="<?=htmlspecialcharsbx($arResult['SMS_MANAGE_URL'])?>" target="_top" class="new-sms-connect-link"><?=Loc::getMessage("CRM_SMS_MANAGE_URL")?></a>
			</div>
		<?php else: ?>
			<form>
				<div class="crm-sms-send-block crm-sms-send-block-option">
					<div class="crm-sms-send-option" data-role="sender-container">
						<span data-role="sender-selector-block">
							<?=Loc::getMessage('CRM_SMS_SENDER');?>
							<span class="crm-sms-send-option-value" data-role="sender-selector">sender</span>
						</span>
						<span data-role="from-container"><?=GetMessage('CRM_SMS_FROM')?>
							<span class="crm-sms-send-option-value" data-role="from-selector" href="#">from_number</span>
						</span>
					</div>
					<div class="crm-sms-send-option" data-role="client-container">
						<?=Loc::getMessage('CRM_SMS_TO');?>
						<span class="crm-sms-send-option-value crm-sms-send-option-value-recipient" data-role="client-selector">client_caption</span>
						<span class="crm-sms-send-option-value" data-role="to-selector">to_number</span>
					</div>
				</div>
				<div class="crm-sms-send-block crm-sms-send-block-textarea">
					<textarea
						data-role="input"
						class="crm-sms-send-textarea <?= $arResult['isEditable'] ? '' : '--readonly' ?>"
						rows='1'
						placeholder="<?= Loc::getMessage('CRM_SMS_SEND_MESSAGE') ?>"
						<?= $arResult['isEditable'] ? '' : 'readonly="readonly"'  ?>
					><?= htmlspecialcharsbx($arResult['text']) ?></textarea>
				</div>
				<div class="crm-sms-send-block crm-sms-send-block-buttons">
					<div class="crm-sms-send-buttons-inner">
						<button type="button" data-role="button-save" class="ui-btn ui-btn-sm ui-btn-primary"><?=Loc::getMessage('CRM_SMS_SEND');?></button>
						<button type="button" data-role="button-cancel" class="ui-btn ui-btn-sm ui-btn-link"><?=Loc::getMessage('CRM_SMS_CANCEL');?></button>
					</div>
					<div class="crm-sms-send-symbol <?= $arResult['isEditable'] ? '' : '--hidden' ?>">
						<span class="crm-sms-send-symbol-text">
							<?=Loc::getMessage('CRM_SMS_SYMBOLS');?>
							<span class="crm-sms-send-symbol-sum" data-role="message-length-counter" data-length-max="200">0</span>
							<?=Loc::getMessage('CRM_SMS_SYMBOLS_FROM');?>
							<span class="crm-sms-send-symbol-sum">200</span>
						</span>
					</div>
				</div>
			</form>
		<?php endif;?>
	</div>
	<script>
		BX.ready(function()
		{
			<?='BX.message('.\CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)).');'?>
			var smsEditor = new BX.Crm.Component.CrmSmsSend(BX('<?=CUtil::JSEscape($arResult['containerId']);?>'));
			smsEditor.init(<?=CUtil::PhpToJSObject($arResult);?>);
		});
	</script>
