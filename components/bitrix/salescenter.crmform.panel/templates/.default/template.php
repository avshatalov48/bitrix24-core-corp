<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\UI\Toolbar\Facade\Toolbar;

$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-all-paddings no-hidden no-background salescenter-crmform-panel');

$APPLICATION->SetTitle(Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_TITLE'));

Toolbar::deleteFavoriteStar();

$b24Manager = Bitrix24Manager::getInstance();
$b24Manager->addIntegrationRequestButtonToToolbar(
	[
		Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_CRM_FORM
	]
);
$b24Manager->addFeedbackButtonToToolbar();

Extension::load([
	'ui.tilegrid',
	'ui.fonts.opensans',
	'applayout',
	'sidepanel',
	'popup',
	'salescenter.manager',
	'salescenter.app',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);
?>

<div class="salescenter-crmform-panel-description">
	<?=Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_DESCRIPTION')?>
	<a href="<?=$arResult['HELPDESK_PAGE_URL']?>" onclick="BX.Salescenter.Manager.openHowCrmFormsWorks(arguments[0]);">
		<?=Loc::getMessage('SALESCENTER_CRM_FORM_PANEL_MORE_INFO')?>
	</a>
</div>

<div id="salescenter-crmform-panel-app" class="salescenter-crmform-panel-app"></div>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);

		var gridParams = <?=CUtil::PhpToJSObject($arResult['crmFormsPanelParams']);?>;
		gridParams.container = document.getElementById('salescenter-crmform-panel-app');
		gridParams.sizeRatio = "55%";
		gridParams.itemMinWidth = 200;
		gridParams.tileMargin = 7;
		gridParams.itemType = 'BX.Salescenter.CrmFormPanel.TileGrid';

		BX.Salescenter.CrmFormPanel.init({
			gridParams: gridParams,
			signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		});
	});
</script>
