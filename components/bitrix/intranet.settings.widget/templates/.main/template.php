<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
/**
 * @var array $arResult
 * @var \CMain $APPLICATION
 */

\Bitrix\Main\UI\Extension::load([
	'ui.icon-set.actions',
	'ui.icon-set.main',
	'ui.icon-set.crm',
	'ui.icons.b24',
	'ui.analytics',
	'ui.icons.crm',
	'ui.popupcomponentsmaker',
	'ui.label',
	'ui.buttons',
]);

$additionalArguments = $this->getComponent()->getAdditionalArguments();

$widgetArguments = [
	'marketUrl' => $additionalArguments['MARKET_URL'],
	'requisite' => $additionalArguments['REQUISITE'] ?? null,
	'isBitrix24' => $arResult['IS_BITRIX24'],
	'isAdmin' => $arResult['IS_ADMIN'],
	'theme' => $additionalArguments['THEME'],
	'otp' => $additionalArguments['OTP'],
	'settingsPath' => $additionalArguments['SETTINGS_PATH'],
	'mainPage' => $additionalArguments['MAIN_PAGE'],
];
if ($arResult['IS_BITRIX24'])
{
	$widgetArguments['isFreeLicense'] = $additionalArguments['IS_FREE_LICENSE'];
	$widgetArguments['holding'] = $additionalArguments['HOLDING'];
	$widgetArguments['isRenameable'] = $additionalArguments['IS_RENAMEABLE'];

	$APPLICATION->IncludeComponent(
		'bitrix:bitrix24.holding',
		'.default', [],
		false,
		['HIDE_ICONS' => 'Y']
	);
}

?>
<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.Intranet.SettingsWidget.init(<?= Json::encode($widgetArguments) ?>);
	});
</script>
