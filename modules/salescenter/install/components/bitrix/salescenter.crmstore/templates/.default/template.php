<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;

$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-background');

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.alerts',
	'salescenter.manager',
]);

\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('SC_CRM_STORE_TITLE_2'));
?>

<?php $this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="salescenter-main-header-feedback-container">
<?php
	Bitrix24Manager::getInstance()->renderIntegrationRequestButton([
		Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_CRM_STORE
	]);
	Bitrix24Manager::getInstance()->renderFeedbackPayOrderOfferButton(); ?>
</div>
<?php $this->endViewTarget(); ?>

<div class="salescenter-crmstore-container" id="salescenter-crmstore-container">
	<div class="salescenter-crmstore-title"><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_TITLE')?></div>
	<div class="salescenter-crmstore-sub-title"><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_SUB_TITLE_2')?></div>
	<div class="salescenter-crmstore-video">
		<iframe width="560" height="315" src="<?=$arResult['URL']?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>
	</div>
	<div class="salescenter-crmstore-contnet salescenter-crmstore-contnet--line"><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_CONTENT_2')?></div>
    <div class="salescenter-crmstore-contnet salescenter-crmstore-contnet--bg"><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_GO_TO_DEAL')?></div>
	<div class="salescenter-crmstore-contnet">
		<span class="ui-btn ui-btn-lg ui-btn-success ui-btn-round" id="start-sell-btn" ><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_START_SELL')?></span>
	</div>
	<div class="salescenter-crmstore-contnet">
		<div class="salescenter-crmstore-contnet-link" onclick="BX.Salescenter.Manager.openHowToSell(event);"><?=Loc::getMessage('SC_CRM_STORE_CONTAINER_LINK')?></div>
	</div>
</div>

<script>
	BX.ready(function() {
		BX.bind(BX("start-sell-btn"), "click", function() {
			top.open('<?=SITE_DIR?>crm/deal/');
			BX.SidePanel.Instance.close();
		});
		document.getElementById('salescenter-crmstore-container').style.minHeight = (window.innerHeight - 100) + 'px';
	})
</script>