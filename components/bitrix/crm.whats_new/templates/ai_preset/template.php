<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\UI\Extension;

Extension::load('crm.integration.ui.banner-dispatcher');

Bitrix\Main\UI\Extension::load([
	'crm.ai.whatsnew.recognition-promo',
	'ui.banner-dispatcher',
	'ui.analytics',
	'crm.integration.analytics',
]);

$options = $arParams['OPTIONS'];
$recognitionPromoShowCount = $options['numberOfViews'] + 1;
$now = time();
$recognitionPromoShowTime = strtotime('+1 days', $now);
?>

<script>
	BX.ready(function() {
		const analyticsDictionary = BX.Crm.Integration.Analytics.Dictionary;
		let analytics = {
			tool: analyticsDictionary.TOOL_CRM,
			category: analyticsDictionary.CATEGORY_POPUP_OPERATIONS,
			type: analyticsDictionary.TYPE_POPUP_AI_TRANSCRIPT,
			c_section: analyticsDictionary.SECTION_DEAL,
			c_sub_section: analyticsDictionary.SUB_SECTION_KANBAN,
			p1: BX.Crm.Integration.Analytics.getCrmMode(),
		};

		const recognitionPromo = new BX.Crm.AI.Whatsnew.RecognitionPromo({
			events: {
				onClickOnConnectButton: (button) => {
					BX.SidePanel.Instance.open('/telephony/');

					analytics.event = 'popup_click';
					analytics.c_element = 'info_button';
					BX.UI.Analytics.sendData(analytics);
				},
				onClickOnRemindLaterButton: (button) => {
					recognitionPromo.hide();

					analytics.event = 'popup_close';
					analytics.c_element = 'skip_button';
					if (!recognitionPromo.shouldShowAgain())
					{
						analytics.p3 = 'skipMark';
					}
					BX.UI.Analytics.sendData(analytics);
				},
				onClickOnClosePopup: () => {
					recognitionPromo.hide();

					analytics.event = 'popup_close';
					analytics.c_element = 'close_button';
					if (!recognitionPromo.shouldShowAgain())
					{
						analytics.p3 = 'skipMark';
					}
					BX.UI.Analytics.sendData(analytics);
				},
			},
		});

		recognitionPromo.subscribe('onAfterClose', (event) => {
			if (!recognitionPromo.shouldShowAgain())
			{
				BX.userOptions.save('<?=$options['optionCategory']?>', '<?=$options['optionNameShowTime']?>', null, 'N');
			}
			else
			{
				BX.userOptions.save('<?=$options['optionCategory']?>', '<?=$options['optionNameShowTime']?>', null, <?=$recognitionPromoShowTime?>);
			}
			BX.userOptions.save('<?=$options['optionCategory']?>', '<?=$options['optionNameShowCount']?>', null, <?=$recognitionPromoShowCount?>);
		});

		BX.UI.BannerDispatcher.high.toQueue((onDone) => {
			recognitionPromo.subscribe('onAfterClose', (event) => {
				onDone();
			});
			recognitionPromo.show();

			analytics.event = 'popup_view';
			BX.UI.Analytics.sendData(analytics);
		});
	});
</script>