<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;

Loc::loadMessages(__FILE__);
$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SPP_SALESCENTER_TITLE'));

if(Loader::includeModule('rest'))
{
	CJSCore::Init(["marketplace"]);
}

Extension::load([
	'admin_interface',
	'ui.tilegrid',
	'ui.fonts.opensans',
	'sidepanel',
	'popup',
	'salescenter.manager',
	'applayout',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
Bitrix24Manager::getInstance()->addIntegrationRequestButtonToToolbar(
	[
		Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_PAYSYSTEM
	]
);

$paySystemTitle = Loc::getMessage('SPP_SALESCENTER_PAYSYSTEM_RECOMMENDATION_SUB_TITLE');
if (!$arResult["isMainMode"])
{
	$paySystemTitle = Loc::getMessage('SPP_SALESCENTER_PAYSYSTEM_SUB_TITLE');
}
?>
<div class="salescenter-paysystem-title"><?=$paySystemTitle?></div>
<div id="salescenter-paysystem" class="salescenter-paysystem"></div>

<?if ($arResult["isMainMode"]) :?>
	<div class="salescenter-paysystem-title-app"><?= Loc::getMessage('SPP_SALESCENTER_PAYSYSTEM_OTHER_SUB_TITLE') ?></div>
	<div id="salescenter-paysystem-app" class="salescenter-paysystem-app"></div>
<?endif;?>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);

		// paysystem grid
		var paySystemParams = <?=CUtil::PhpToJSObject($arResult['paySystemPanelParams']);?>;
		paySystemParams.container = document.getElementById('salescenter-paysystem');
		paySystemParams.sizeRatio = '55%';
		paySystemParams.itemMinWidth = 200;
		paySystemParams.tileMargin = 7;
		paySystemParams.itemType = 'BX.SaleCenterPaySystem.TileGrid';

		// integration grid
		var paySystemAppParams = <?=CUtil::PhpToJSObject($arResult['paySystemAppPanelParams']);?>;
		paySystemAppParams.container = document.getElementById('salescenter-paysystem-app');
		paySystemAppParams.sizeRatio = '55%';
		paySystemAppParams.itemMinWidth = 200;
		paySystemAppParams.tileMargin = 7;
		paySystemAppParams.itemType = 'BX.SaleCenterPaySystem.TileGrid';

		BX.SaleCenterPaySystem.init({
			mode: '<?=CUtil::JSEscape($arResult['mode'])?>',
			paySystemParams: paySystemParams,
			paySystemAppParams: paySystemAppParams,
			signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		});
	});
</script>