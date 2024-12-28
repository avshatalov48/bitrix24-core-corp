<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmDealDetailsComponent $component */

if ($arResult['ENTITY_ID'])
{
	$notifier = new \Bitrix\Crm\Integration\Rest\MangoAppNotifier(new \Bitrix\Crm\ItemIdentifier(CCrmOwnerType::Deal, (int)$arResult['ENTITY_ID']));
	if ($notifier->needShow())
	{
		\Bitrix\Main\UI\Extension::load([
			'ui.dialogs.messagebox',
			'ui.analytics',
		]);

		$timelineId = $notifier->getCallTimelineId();
		if ($timelineId)
		{
			$notifier->setSkippedToNextDay();
			$notifier->sendAnalyticsEvent();

			$popupType = $notifier->getPopupType();
			$skipNotificationTo = $notifier->getNextPopupTypeTs();
	?>
		<script>
			BX.ready(function() {
				const timelineItem = document.querySelector('[data-id="<?php echo htmlspecialcharsbx(CUtil::JSEscape($timelineId))?>"]');
				if (timelineItem)
				{
					const analyticsP1 = '<?php echo  htmlspecialcharsbx(CUtil::JSEscape(\Bitrix\Crm\Settings\LeadSettings::isEnabled() ? 'crmMode_classic' : 'crmMode_simple'))?>';
					const analyticsP2 = 'popType_<?php echo $popupType?>';
					const skipNotificationTo = '<?php echo $skipNotificationTo?>';

					const analyticsParams = {
						tool: 'crm',
						category: 'popup_operations',
						type: 'popup_mango',
						c_section: 'deal_section',
						c_sub_section: 'details',
						p1: analyticsP1,
						p2: analyticsP2,
					};

					BX.UI.Analytics.sendData({
						...analyticsParams,
						event: 'popup_view',
					});

					BX.message({
						'CRM_DEAL_MANGO_NOTIFICATION_TITLE': '<?=GetMessageJS('CRM_DEAL_MANGO_NOTIFICATION_TITLE')?>',
						'CRM_DEAL_MANGO_NOTIFICATION_TEXT': '<?=GetMessageJS('CRM_DEAL_MANGO_NOTIFICATION_TEXT')?>',
						'CRM_DEAL_MANGO_NOTIFICATION_DETAILS_BTN': '<?=GetMessageJS('CRM_DEAL_MANGO_NOTIFICATION_DETAILS_BTN')?>',
						'CRM_DEAL_MANGO_NOTIFICATION_CANCEL_BTN': '<?=GetMessageJS('CRM_DEAL_MANGO_NOTIFICATION_CANCEL_BTN')?>'
					});
					let sendAnalyticsEventOnClose = true;
					const isTimelineVisible = (BX.Dom.getPosition(timelineItem).bottom - 100) < window.innerHeight;
					const messageBox = new BX.UI.Dialogs.MessageBox({
						title: BX.message('CRM_DEAL_MANGO_NOTIFICATION_TITLE'),
						message: BX.message('CRM_DEAL_MANGO_NOTIFICATION_TEXT'),
						modal: true,
						minWidth: 500,
						buttons: [
							new BX.UI.Button({
								color: BX.UI.Button.Color.PRIMARY,
								text: BX.message('CRM_DEAL_MANGO_NOTIFICATION_DETAILS_BTN'),
								events: {
									click: () => {
										BX.UI.Analytics.sendData({
											...analyticsParams,
											event: 'popup_click',
											c_element: 'info_button',
										});
										top.BX.UI.InfoHelper.show('info_mango_office_popup');
									}
								}
							}),
							new BX.UI.CancelButton({
								text:  BX.message('CRM_DEAL_MANGO_NOTIFICATION_CANCEL_BTN'),
								events: {
									click: () => {
										sendAnalyticsEventOnClose = false;
										BX.UI.Analytics.sendData({
											...analyticsParams,
											event: 'popup_close',
											c_element: 'skip_button',
										});
										BX.userOptions.save('crm', 'mango_notification_skip_to_v2', 'value', skipNotificationTo);
										messageBox.close();
									}
								}
							})
						],
					});
					const popupWindow = messageBox.getPopupWindow();
					if (isTimelineVisible)
					{
						popupWindow.setBindElement(timelineItem);
						popupWindow.setAngle({offset: 50});
					}
					popupWindow.subscribe('onClose', () => {
						if (sendAnalyticsEventOnClose)
						{
							BX.UI.Analytics.sendData({
								...analyticsParams,
								event: 'popup_close',
								c_element: 'close_button',
							});
						}
					});

					popupWindow.show();
				}
			});
		</script>
	<?php
		}
	}
}
