<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Integration\Sender\Rc;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.buttons.icons',
	'main.d3js',
	'main.kanban',
	'sidepanel',
	'ui.notification',
	'dd',
]);

if (!Rc\Service::isAvailable())
{
	Rc\Service::initJsExtensions();
}

?>

<div class="crm-st">
	<div class="crm-st-container">
		<div class="crm-st-categories"> </div>
		<svg class="crm-st-svg-root"> </svg>
		<svg class="crm-st-svg-links-root"> </svg>
	</div>
	<div class="crm-st-footer">
		<? if ($arResult['canEditTunnels']) : ?>
			<button class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-add crm-st-add-category-btn"><?=Loc::getMessage('CRM_ST_ADD_NEW_CATEGORY_BUTTON_LABEL')?></button>
		<? endif; ?>
	</div>
</div>

<?php
$this->SetViewTarget('pagetitle', 100);
?>

<div class="pagetitle-container">
	<button class="ui-btn ui-btn-icon-info ui-btn-light-border crm-st-help-button"><?=Loc::getMessage('CRM_ST_HELP_BUTTON')?></button>
	<? if ($arResult['canEditTunnels']) : ?>
		<button class="ui-btn ui-btn-primary crm-st-add-category-btn-top"><?=Loc::getMessage('CRM_ST_ADD_FUNNEL_BUTTON')?></button>
	<? endif; ?>
</div>

<?php
$this->EndViewTarget();
?>


<?php
//load Bizproc Automation API
$APPLICATION->includeComponent(
	'bitrix:bizproc.automation',
	'',
	[
		'API_MODE' => 'Y',
		'DOCUMENT_TYPE' => \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Deal),
	]
);

if (Loader::includeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent("bitrix:bitrix24.limit.lock", "", array(
		"FEATURE_GROUP_NAME" => "crm_automation_deal"
	));
}

?>
<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	void new BX.Crm.SalesTunnels.Manager({
		container: document.querySelector('.crm-st'),
		addCategoryButtonTop: document.querySelector('.crm-st-add-category-btn-top'),
		helpButton: document.querySelector('.crm-st-help-button'),
		categories: <?=CUtil::phpToJsObject($arResult['categories'])?>,
		tunnelScheme: <?=CUtil::phpToJsObject($arResult['tunnelScheme'])?>,
		canAddCategory: <?=CUtil::phpToJsObject($arResult['canAddCategory'])?>,
		categoriesQuantityLimit: <?=CUtil::phpToJsObject($arResult['categoriesQuantityLimit'])?>,
		robotsUrl: '/crm/deal/automation/{category}/',
		generatorUrl: '<?=Rc\Service::getPathToAddDeal()?>',
		allowWrite: true,
		canEditTunnels: <?=CUtil::phpToJsObject($arResult['canEditTunnels'])?>,
		restrictionPopupCode: <?=CUtil::phpToJsObject($arResult['restrictionPopup'])?>,
		isAvailableGenerator: <?=CUtil::phpToJsObject(Rc\Service::isAvailable())?>,
		showGeneratorRestrictionPopup: function() {
			<?=Rc\Service::getJsAvailablePopupShower()?>
		},
		isAvailableRobots: <?=CUtil::phpToJsObject(Factory::isAutomationAvailable(\CCrmOwnerType::Deal))?>,
		showRobotsRestrictionPopup: function() {
			if (BX.Bitrix24 && BX.Bitrix24.LicenseInfoPopup)
			{
				BX.Bitrix24.LicenseInfoPopup.show(
					'crm_automation_deal',
					BX.message('CRM_ST_ROBOTS_POPUP_TITLE'),
					BX.message('CRM_ST_ROBOTS_POPUP_TEXT'),
				);
			}
		}
	});
</script>