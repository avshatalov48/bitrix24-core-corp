<? if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
/** @var array $arResult */

CJSCore::Init(array('popup'));
use Bitrix\Main\Localization\Loc;

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => '',
		'ACTIVE_ITEM_ID' => '',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

/** @var \Bitrix\Crm\Integration\Sms\Provider\Twilio $provider */
$provider = $arResult['provider'];

if ($provider->isRegistered())
{
	$asset = Bitrix\Main\Page\Asset::getInstance();
	$asset->addJs('/bitrix/js/crm/common.js');
	if (SITE_TEMPLATE_ID === 'bitrix24')
	{
		$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
		$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
		$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view flexible-layout crm-toolbar crm-pagetitle-view');
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'title',
		array(
			'TOOLBAR_ID' => "crmconfigsms_toolbar",
			'BUTTONS'    => array(
				array(
					'NEWBAR' => true
				),
				array(
					'TEXT'    => Loc::getMessage("CRM_CONFIG_SMS_CLEAR_OPTIONS"),
					'ONCLICK' => 'BX.CrmConfigSms&&BX.CrmConfigSms.clearOptions?BX.CrmConfigSms.clearOptions():null;'
				)
			)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
?>
<div class="sms-settings">
	<h2 class="sms-settings-title"><?= Loc::getMessage("CRM_CONFIG_SMS_TITLE")?></h2>
	<h3 class="sms-settings-title-paragraph"><?= Loc::getMessage("CRM_CONFIG_SMS_TITLE_2")?></h3>
	<div class="sms-settings-cover-container">
		<div class="sms-settings-cover"></div>
	</div>
	<div class="sms-settings-futures-rings-container">
		<div class="sms-settings-futures-rings">
			<div class="sms-settings-futures-rings-item sms-settings-futures-rings-item-first"><?= Loc::getMessage("CRM_CONFIG_SMS_RING_1") ?></div>
			<div class="sms-settings-futures-rings-item sms-settings-futures-rings-item-second"><?= Loc::getMessage("CRM_CONFIG_SMS_RING_2") ?></div>
			<div class="sms-settings-futures-rings-item sms-settings-futures-rings-item-third"><?= Loc::getMessage("CRM_CONFIG_SMS_RING_3") ?></div>
		</div>
	</div>
	<div class="sms-settings-border"></div>
	<h3 class="sms-settings-title-paragraph"><?= Loc::getMessage("CRM_CONFIG_SMS_FEATURES_TITLE")?></h3>
	<div class="sms-settings-description">
		<p><?= Loc::getMessage("CRM_CONFIG_SMS_FEATURES_LIST_DESCRIPTION")?></p>
		<ul class="sms-settings-futures-list">
			<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_FEATURES_LIST_1")?></li>
			<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_FEATURES_LIST_2")?></li>
			<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_FEATURES_LIST_3")?></li>
		</ul>
	</div>
	<div class="sms-settings-quick-start" id="sms-settings-steps">
		<!---->
		<?php if (!$provider->isRegistered()):?>
			<div class="sms-settings-step-section">
				<div class="sms-settings-step-number">1</div>
				<div class="sms-settings-step-title"><?= Loc::getMessage("CRM_CONFIG_SMS_RULES_LIST_TITLE")?></div>
				<ul class="sms-settings-futures-list">
					<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_RULES_LIST_1", array(
							'#A1#' => '<a href="https://www.twilio.com/console" target="_blank">',
							'#A2#' => '</a>'
						))?></li>
					<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_RULES_LIST_2")?></li>
					<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_RULES_LIST_3", array(
							'#A1#' => '<a href="https://www.twilio.com/console" target="_blank">',
							'#A2#' => '</a>'
						))?></li>
					<li class="sms-settings-futures-list-item"><?= Loc::getMessage("CRM_CONFIG_SMS_RULES_LIST_4")?></li>
				</ul>
				<form action="" method="post" class="sms-settings-step-form" name="form_sms_registration">
					<?=bitrix_sessid_post()?>
					<input type="hidden" name="action" value="registration">
					<div class="sms-settings-input-container">
						<label for="" class="sms-settings-input-label"><?= Loc::getMessage("CRM_CONFIG_SMS_LABEL_ACCOUNT_SID")?>*</label>
						<input type="text" name="account_sid" class="sms-settings-input">
					</div>
					<div class="sms-settings-input-container">
						<label for="" class="sms-settings-input-label"><?= Loc::getMessage("CRM_CONFIG_SMS_LABEL_ACCOUNT_TOKEN")?>*</label>
						<input type="text" name="account_token" class="sms-settings-input">
					</div>
					<div class="sms-settings-input-container">
						<button type="submit" class="webform-small-button webform-small-button-blue" data-role="sms-registration-submit">
							<span class="webform-small-button-text"><?= Loc::getMessage("CRM_CONFIG_SMS_ACTION_REGISTRATION")?></span>
						</button>
					</div>
				</form>
			</div>
		<?else:
			$ownerInfo = $provider->getOwnerInfo();
			?>
			<!---->
			<div class="sms-settings-step-section sms-settings-step-section-active">
				<div class="sms-settings-step-number">1</div>
				<div class="sms-settings-step-title"><?= Loc::getMessage("CRM_CONFIG_SMS_REGISTRATION_INFO") ?></div>
				<div class="sms-settings-step-contact-info">
					<div class="sms-settings-step-contact-info-block">
						<div class="sms-settings-step-contact-info-name"><?= Loc::getMessage("CRM_CONFIG_SMS_LABEL_ACCOUNT_SID")?>:</div>
						<div class="sms-settings-step-contact-info-value"><?=htmlspecialcharsbx($ownerInfo['sid'])?></div>
					</div>
					<div class="sms-settings-step-contact-info-block">
						<div class="sms-settings-step-contact-info-name"><?= Loc::getMessage("CRM_CONFIG_SMS_LABEL_ACCOUNT_FRIENDLY_NAME") ?>:</div>
						<div class="sms-settings-step-contact-info-value"><?=htmlspecialcharsbx($ownerInfo['friendly_name'])?></div>
					</div>
				</div>
			</div>
			<!---->
		<?endif;?>
		<?php if ($provider->canUse()):?>
			<div class="sms-settings-step-section sms-settings-step-section-active">
				<div class="sms-settings-step-number">2</div>
				<div class="sms-settings-step-title"><?= Loc::getMessage("CRM_CONFIG_SMS_CABINET_TITLE")?></div>
					<div class="sms-settings-step-description"><?=Loc::getMessage("CRM_CONFIG_SMS_DEMO_IS_DISABLED", array(
							'#A1#' => '<a href="'.htmlspecialcharsbx($provider->getExternalManageUrl()).'" target="_blank">',
							'#A2#' => '</a>'
						))?></div>
			</div>
		<?endif;?>
	</div>
</div>
<script>
	BX.ready(function()
	{
		var serviceUrl = '/bitrix/components/bitrix/crm.config.sms/ajax.php?sessid='
			+BX.bitrix_sessid()
			+'&site_id='+BX.message('SITE_ID')
			+'&provider_id=<?=CUtil::JSEscape($provider->getId())?>';

		var registrationForm = document.forms['form_sms_registration'];
		if (registrationForm)
		{
			var registrationSubmit = document.querySelector('[data-role="sms-registration-submit"]');
			BX.bind(registrationForm, 'submit', function(e)
			{
				e.preventDefault();

				var sid = registrationForm.elements['account_sid'].value;
				var token = registrationForm.elements['account_token'].value;

				if (!sid.length || !token.length)
				{
					window.alert('<?=GetMessageJS('CRM_CONFIG_SMS_REGISTRATION_ERROR')?>');
					return false;
				}

				BX.addClass(registrationSubmit, 'webform-small-button-wait');
				BX.ajax({
					method: 'POST',
					dataType: 'json',
					url: serviceUrl,
					data: {
						action: registrationForm.elements['action'].value,
						account_sid: sid,
						account_token: token
					},
					onsuccess: function (response)
					{
						if (!response.success)
						{
							alert(response.errors[0]);
						}
						else
						{
							window.location = window.location.href;
						}
						BX.removeClass(registrationSubmit, 'webform-small-button-wait');
					}
				});
			});
		}

		BX.namespace('BX.CrmConfigSms');
		BX.CrmConfigSms.clearOptions = function()
		{
			if (window.confirm('<?=CUtil::JSEscape(Loc::getMessage('CRM_CONFIG_SMS_CLEAR_CONFIRMATION'))?>'))
			{
				BX.ajax({
					method: 'POST',
					dataType: 'json',
					url: serviceUrl,
					data: {
						action: 'clear_options'
					},
					onsuccess: function (response)
					{
						window.location = window.location.href;
					}
				});
			}
		};
		<?if ($provider->isRegistered()):?>
		var steps = BX('sms-settings-steps');
		if (steps)
		{
			BX.scrollToNode(steps);
		}
		<?endif?>
	});
</script>