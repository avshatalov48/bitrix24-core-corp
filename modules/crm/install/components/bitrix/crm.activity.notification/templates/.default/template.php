<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>

<div class="crm-task-list-call">
	<div class="crm-task-list-call-info">
		<?if ($arResult['MESSAGE']):?>
			<?if (isset($arResult['MESSAGE']['PHONE_NUMBER'])):?>
				<div class="crm-task-list-call-info-container">
					<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_NOTIFICATION_PHONE_NUMBER')?>:</span>
					<span class="crm-task-list-call-info-item"><?=htmlspecialcharsbx($arResult['MESSAGE']['PHONE_NUMBER'])?></span>
				</div>
			<?endif;?>

			<?if ($arResult['HISTORY_ITEMS']):?>
				<div class="crm-task-list-call-info-container">
					<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_ACTIVITY_NOTIFICATION_MESSAGE_STATUS')?>:</span>
					<span class="crm-task-list-call-info-item">
						<?=htmlspecialcharsbx($arResult['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION'])?>
						<?if ($arResult['HISTORY_ITEMS'][0]['REASON'] && $arResult['HISTORY_ITEMS'][0]['STATUS_DATA']['IS_FAILURE']):?>
							(<?=htmlspecialcharsbx($arResult['HISTORY_ITEMS'][0]['REASON'])?>)
						<?endif;?>
					</span>
				</div>
				<?if ($arResult['HISTORY_ITEMS'][0]['PROVIDER_CODE']):?>
					<div class="crm-task-list-call-info-container">
						<span class="crm-task-list-call-info-name">
							<?=GetMessage('CRM_ACTIVITY_NOTIFICATION_MESSAGE_CHANNEL')?>:
						</span>
						<span class="crm-task-list-call-info-item">
							<?=htmlspecialcharsbx($arResult['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION'])?>
						</span>
					</div>
				<?endif;?>
			<?endif?>
			<?if ($arResult['MESSAGE']['TEXT']):?>
				<div class="crm-task-list-call-info-container">
					<span class="crm-task-list-call-info-name">
						<?=GetMessage('CRM_ACTIVITY_NOTIFICATION_DESCRIPTION')?>:
					</span>
				</div>
				<span><?=htmlspecialcharsbx($arResult['MESSAGE']['TEXT'])?></span>
			<?else:?>
				<i><?=GetMessage('CRM_ACTIVITY_NOTIFICATION_TEMPLATE_TEXT_2')?></i>
			<?endif;?>
		<?endif;?>
	</div>
</div>
