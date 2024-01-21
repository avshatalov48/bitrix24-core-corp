<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension,
	Bitrix\Main\Localization\Loc,
	Bitrix\SalesCenter\Integration\Bitrix24Manager;

$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SPP_SALESCENTER_TITLE'));
Extension::load([
	'ui.tilegrid',
	'ui.fonts.opensans',
	'applayout',
	'sidepanel',
	'popup',
	'salescenter.manager',
	'salescenter.app',
	'salescenter.lib',
	'ui.design-tokens',
	'ui.dialogs.messagebox',
	'ui.fonts.opensans',
]);
?>
<div class="salescenter-smsprovider-title">
	<?=Loc::getMessage('SPP_SALESCENTER_SMSPROVIDER_RECOMMENDATION_SUB_TITLE')?>
</div>
<div id="salescenter-smsprovider" class="salescenter-smsprovider"></div>

<?php if (!empty($arResult['smsProviderAppPanelParams']['items'])):?>
	<div class="salescenter-smsprovider-title">
		<?=Loc::getMessage('SPP_SALESCENTER_SMSPROVIDER_OTHER_SUB_TITLE')?>
	</div>
	<div id="salescenter-smsprovider-app" class="salescenter-smsprovider"></div>
<?php endif;?>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);

		// smsprovider grid
		var smsProviderParams = <?=CUtil::PhpToJSObject($arResult['smsProviderPanelParams']);?>;
		smsProviderParams.container = document.getElementById('salescenter-smsprovider');
		smsProviderParams.sizeRatio = "55%";
		smsProviderParams.itemMinWidth = 200;
		smsProviderParams.tileMargin = 7;
		smsProviderParams.itemType = 'BX.SaleCenterSmsProvider.TileGrid';


		// integration grid
		var smsProviderAppParams = <?=CUtil::PhpToJSObject($arResult['smsProviderAppPanelParams']);?>;
		smsProviderAppParams.container = document.getElementById('salescenter-smsprovider-app');
		smsProviderAppParams.sizeRatio = "55%";
		smsProviderAppParams.itemMinWidth = 200;
		smsProviderAppParams.tileMargin = 7;
		smsProviderAppParams.itemType = 'BX.SaleCenterSmsProvider.TileGrid';

		BX.SaleCenterSmsProvider.init({
			mode: 'main',
			smsProviderParams: smsProviderParams,
			smsProviderAppParams: smsProviderAppParams,
			signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		});
	});
</script>