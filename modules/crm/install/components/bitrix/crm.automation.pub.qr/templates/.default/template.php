<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var \CrmAutomationPubQrComponent $component $qr */

$qr = $arResult['QR'];

$description = $component->convertBBtoText($qr['DESCRIPTION']);
$completeLabel = $qr['COMPLETE_ACTION_LABEL'] ?: (string)Loc::getMessage('CRM_AUTOMATION_QR_DEFAULT_ACTION_LABEL');
$completeLabel = $component->convertBBtoText($completeLabel);

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$logoClass = in_array(LANGUAGE_ID, ['ru', 'kz', 'by']) ? '' : 'crm-automation-pub-qr__popup_logo--en';

?>
<div class="crm-automation-pub-qr__wrap crm-automation-pub-qr--scope">
	<div class="crm-automation-pub-qr__popup" id="qr-popup">
		<!--		<h1 class="crm-automation-pub-qr__popup_title">Title1</h1>-->
		<!--		<h2 class="crm-automation-pub-qr__popup_subtitle">Title2</h2>-->
		<div class="crm-automation-pub-qr__popup_info_box">
			<div class="crm-automation-pub-qr__popup_info"><?= $description ?></div>
		</div>

		<div class="ui-btn-container ui-btn-container-center">
			<button id="do-complete" class="ui-btn ui-btn-round ui-btn-lg crm-automation-pub-qr__popup_btn"

			><?= $completeLabel ?>
			</button>
		</div>

		<div class="crm-automation-pub-qr__popup_logo_box <?= $logoClass ?>">
			<div class="crm-automation-pub-qr__popup_logo"><?= Loc::getMessage('CRM_AUTOMATION_QR_LOGO') ?><span></span>
			</div>
		</div>
	</div>

	<div class="crm-automation-pub-qr__popup crm-automation-pub-qr__success --hidden" id="qr-success">
		<div class="crm-automation-pub-qr__popup__icon_box">
			<div class="crm-automation-pub-qr__popup__icon"></div>
		</div>
		<h1 class="crm-automation-pub-qr__popup_title"><?= Loc::getMessage('CRM_AUTOMATION_QR_SUCCESS') ?></h1>
		<div class="crm-automation-pub-qr__popup_logo_box <?= $logoClass ?>">
			<div class="crm-automation-pub-qr__popup_logo"><?= Loc::getMessage('CRM_AUTOMATION_QR_LOGO') ?><span></span>
			</div>
		</div>
	</div>
</div>
<script>
	BX.ready(function()
	{
		const actionNode = BX('do-complete');
		if (actionNode)
		{
			BX.bind(actionNode, 'click', function()
			{
				BX.ajax.runComponentAction(
					'bitrix:crm.automation.pub.qr',
					'complete',
					{
						mode: 'class',
						signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
						data: {},
					}
				).then(function(response)
				{
					BX.Dom.remove(BX('qr-popup'));
					BX.Dom.removeClass(BX('qr-success'), '--hidden');
					setTimeout(function() {
						BX.Dom.addClass(BX('qr-success'), '--animate');
					}, 100);
				}).catch(function(response)
				{
					window.alert(response.errors[0].message);
				});
			});
		}
	});
</script>
