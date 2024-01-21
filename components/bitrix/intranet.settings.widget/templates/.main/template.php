<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
/**
 * @var array $arParams
 * @var array $arResult
 * @var \CMain $APPLICATION
 */

\Bitrix\Main\UI\Extension::load([
	'ui.icon-set.actions',
	'ui.icon-set.main',
	'ui.icon-set.crm',
	'ui.analytics',
]);

$widgetArguments = [
	'marketUrl' => $arParams['MARKET_URL'],
	'requisite' => $arParams['REQUISITE'],
	'isBitrix24' => $arParams['IS_BITRIX24'],
	'isAdmin' => $arParams['IS_ADMIN'],
	'theme' => $arParams['THEME'],
	'otp' => $arParams['OTP'],
	'settingsPath' => $arParams['SETTINGS_PATH']
];
if ($arParams['IS_BITRIX24'])
{
	$widgetArguments['isFreeLicense'] = $arParams['IS_FREE_LICENSE'];
	$widgetArguments['holding'] = $arParams['HOLDING'];

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
		BX.Intranet.SettingsWidget.init(<?= \CUtil::PhpToJSObject($widgetArguments) ?>);
	});
</script>
