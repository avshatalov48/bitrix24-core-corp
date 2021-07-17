<?php

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var array $entity
 */

$entity = $arResult['ENTITY'];
$photo = null;
foreach ($entity['CONTACTS'] as $contact)
{
	if (array_key_exists('PHOTO', $contact))
	{
		$photo = $contact['PHOTO']['src'];
		break;
	}
}
if ($photo === null
	&& array_key_exists('COMPANY', $entity)
	&& array_key_exists('LOGO', $entity['COMPANY'])
)
{
	$photo = $contact['COMPANY']['LOGO']['src'];
}
$photo = ($photo === null ? $this->GetFolder(). '/images/avatar.png' : $photo);

Main\UI\Extension::load(['ui.icons', 'mobile_crm']);

if ($arResult['ACTIVITY']['PHONE_NUMBER'] != '')
{
	$title = $arResult['ACTIVITY']['PHONE_NUMBER'];
	$subTitle = $entity['TITLE'];
}
else
{
	$title = $entity['TITLE'];
	$subTitle = '';
}
$formId = intval($entity["ID"]);
$currencyPositionBeforeAmount = (mb_strpos($arResult['CURRENCY']['FORMAT_STRING'], '#') !== 0);
?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" name="FORM_DEAL_<?=$formId?>" id="FORM_DEAL_<?=$formId?>">
<?=bitrix_sessid_post();?>
<input type="hidden" name="ID" value="<?=$formId?>">
<div class="crm-phonetracker-detail-wrapper">
	<div class="crm-phonetracker-detail">
		<div class="crm-phonetracker-detail-control-container">
			<div class="crm-phonetracker-detail-control-icon crm-phonetracker-detail-control-icon-wallet"></div>
			<div class="crm-phonetracker-detail-control-inner">
				<div class="crm-phonetracker-detail-control-title"><?=GetMessage('CRM_AMOUNT_AND_CURRENCY')?></div>
				<div class="crm-phonetracker-detail-control-field-container">
					<?php
					$isOpportunityReadonly = ($entity["OPPORTUNITY"] > 0 && $entity["IS_MANUAL_OPPORTUNITY"] !== 'Y');

					if($currencyPositionBeforeAmount)
					{
						?><div class="crm-phonetracker-detail-control-field-currency"><?=htmlspecialcharsbx($entity["CURRENCY_ID"])?></div><?
					}?>
					<input
						type="number"
						name="OPPORTUNITY"
						value="<?=htmlspecialcharsbx($entity["OPPORTUNITY"])?>"<?
						if ($isOpportunityReadonly):
							?> disabled
						<?else
							:?> onchange="BX.onCustomEvent('onCrmCallTrackerNeedToSendForm<?=$formId?>')"
						<?endif?>
						class="crm-phonetracker-detail-control-field">
					<?php
					if (!$currencyPositionBeforeAmount)
					{
						?><div class="crm-phonetracker-detail-control-field-currency"><?=htmlspecialcharsbx($entity["CURRENCY_ID"])?></div><?
					}?>
				</div>
			</div>
		</div>
		<div id="crm-phonetracker-detail-contacts"></div>
		<div id="crm-phonetracker-detail-company"></div>
	</div>
</div>
</form>

<script>
	BX.ready()
	{
		BX.message(<?=CUtil::phpToJsObject(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);
		app.pullDown({
			enable: true,
			'pulltext': '',
			'downtext': '',
			'loadtext': '',
			callback: function()
			{
				sendForm();
				app.reload();
			}
		});
		var titlebar = BX.Mobile.Crm.Calltracker.Titlebar.create(<?=CUtil::PhpToJSObject([
			'title' => $title,
			'subTitle' => $subTitle,
			'photo' => $photo
		])?>);
		<?php
		if (CCrmDeal::CheckUpdatePermission($entity['ID']))
		{
			$canAddToIgnored = \Bitrix\Crm\Exclusion\Manager::checkCreatePermission();
			?>
			titlebar.setMenu([
				{
					name: '<?=CUtil::JSEscape(Loc::getMessage('CRM_CALL_TRACKER_POSTPONE'))?>',
					action: function() {
						BX.Mobile.Crm.Calltracker.Action.postpone(<?=(int)$entity['ID']?>);
					}
				}<?
				if ($canAddToIgnored):
				?>,
				{
					name: '<?=CUtil::JSEscape(Loc::getMessage('CRM_CALL_TRACKER_TO_IGNORED'))?>',
					action: function() {
						BX.Mobile.Crm.Calltracker.Action.addToIgnored(<?=(int)$entity['ID']?>);
					}
				}
				<?endif?>
			]);
			BXMobileApp.addCustomEvent('onCrmCallTrackerItemCommentAdded', (data) => {
				titlebar.removeMenu(); // disable actions if activity was complete
			});

			var latestFormData;
			setTimeout(function() {
				latestFormData = JSON.stringify(BX.ajax.prepareForm(document.forms['FORM_DEAL_<?=$formId?>']).data);
			}, 100);
			var sendForm = function() {
				var data = BX.ajax.prepareForm(document.forms['FORM_DEAL_<?=$formId?>']).data;
				var jsonData = JSON.stringify(data);
				if (jsonData === latestFormData)
				{
					return;
				}
				latestFormData = jsonData;
				var eventData = <?=CUtil::PhpToJSObject(['ID' => $entity['ID']])?>;
				BXMobileApp.Events.postToComponent('onCrmCallTrackerItemStartUpdate', eventData);
				BX.ajax.runComponentAction(
					'<?=CUtil::JSEscape($this->getComponent()->getName())?>',
					'save',
					{
						mode: 'class',
						signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
						data: {
							entityId: <?=$formId?>,
							data: data
						}
					})
					.then(function(response) {
						BXMobileApp.Events.postToComponent('onCrmCallTrackerItemUpdated', eventData);
					}, function(err) {
						alert(err.message)
					});
			};
			BXMobileApp.addCustomEvent('onCrmCallTrackerNeedToSendForm<?=$formId?>', BX.debounce(sendForm, 100));

			BX.addCustomEvent('onHidePageBefore', () => {
				sendForm();
				var eventData = <?=CUtil::PhpToJSObject(['ID' => $entity['ID']])?>;
				BXMobileApp.Events.postToComponent('onCrmCallTrackerDetailPageClose', eventData);
			});
			<?
		}
		else
		{
			?>
				setTimeout(function() {
					var inputs = document.forms['FORM_DEAL_<?=$formId?>'].elements;
					for (var i = 0; i < inputs.length; i++) {
						inputs[i].setAttribute('readonly', 'readonly');
					}
				}, 500);
			<?
		}
		?>
		BX.Mobile.Crm.Calltracker.Contact.selectorUrl = '<?=CUtil::JSEscape(SITE_DIR.'mobile/crm/entity/?entity=contact')?>';
		BX.Mobile.Crm.Calltracker.Contact.bind(
			BX('crm-phonetracker-detail-contacts'),
			'<?=CUtil::JSEscape($formId)?>',
			<?=CUtil::PhpToJSObject(array_values(array_map(function($contact) {
				$result = [
					'id' => $contact['ID'],
					'name' => $contact['FULL_NAME'],
					'avatar' => $contact['PHOTO'] ? $contact['PHOTO']['src'] : '',
				];
				return $result;
			}, $entity['CONTACTS'])))?>
		);
		BX.Mobile.Crm.Calltracker.Company.selectorUrl = '<?=CUtil::JSEscape(SITE_DIR.'mobile/crm/entity/?entity=company')?>';
		BX.Mobile.Crm.Calltracker.Company.bind(
			BX('crm-phonetracker-detail-company'),
			'<?=CUtil::JSEscape($formId)?>',
			<?=CUtil::PhpToJSObject($entity['COMPANY'] ? [
				'id' => $entity['COMPANY']['ID'],
				'title' => $entity['COMPANY']['TITLE'],
				'logo' => ($entity['COMPANY']['LOGO'] ? $entity['COMPANY']['LOGO']['src'] : ''),
			] : [])?>
		);
	}
</script>
<?php
$APPLICATION->IncludeComponent(
	'bitrix:mobile.crm.calltracker.timeline',
	'',
	[
		'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
		'ENTITY_ID' => $entity['ID']
	]
);?>