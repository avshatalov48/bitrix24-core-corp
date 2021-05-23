<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var array $params */
$params = $arResult['activity']['PROVIDER_PARAMS'];
$smsMessage = $arResult['SMS_MESSAGE'];
$statuses = $arResult['SMS_MESSAGE_STATUS_DESCRIPTIONS'];
?>
<div class="crm-task-list-call">
	<div class="crm-task-list-call-info">
		<?if ($arResult['IS_ROBOT']):?>
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_SMS_SENDER_ROBOT')?>:</span>
			<span class="crm-task-list-call-info-item"><?=GetMessage(
					!empty($arResult['recipientName'])?'CRM_ACTIVITY_SMS_RECIPIENT_USER':'CRM_ACTIVITY_SMS_RECIPIENT_CLIENT'
				)?></span>
		</div>
		<?endif?>
		<?if (!empty($arResult['recipientName']) && !empty($arResult['recipientUrl'])):?>
			<div class="crm-task-list-call-info-container">
				<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_SMS_RECIPIENT_NAME')?>:</span>
				<span class="crm-task-list-call-info-item">
					<a href="<?=htmlspecialcharsbx($arResult['recipientUrl'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['recipientName'])?></a>
				</span>
			</div>
		<?endif?>
		<?if ($smsMessage && isset($smsMessage['MESSAGE_TO'])):?>
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_SMS_PHONE_NUMBER')?>:</span>
			<span class="crm-task-list-call-info-item"><?=htmlspecialcharsbx($smsMessage['MESSAGE_TO'])?></span>
		</div>
		<?endif;?>
		<?if ($smsMessage && isset($smsMessage['STATUS_ID']) && isset($statuses[$smsMessage['STATUS_ID']])):?>
			<div class="crm-task-list-call-info-container">
				<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_SMS_MESSAGE_STATUS')?>:</span>
				<span class="crm-task-list-call-info-item"><?=htmlspecialcharsbx($statuses[$smsMessage['STATUS_ID']])?>
					<?if ($arResult['SMS_MESSAGE_STATUS_IS_ERROR'] && !empty($smsMessage['EXEC_ERROR']))
					{
						echo ' (', htmlspecialcharsbx($smsMessage['EXEC_ERROR']), ')';
					}
					?>
				</span>
			</div>
		<?endif?>
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name">
				<?=GetMessage('CRM_ACTIVITY_SMS_DESCRIPTION')?>:
			</span>
		</div>
		<span>
			<?=$arResult['activity']['DESCRIPTION_HTML']?>
		</span>
	</div>
</div>
