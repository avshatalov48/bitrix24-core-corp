<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var string $templateFolder
 * @var \Bitrix\BIConnector\Superset\UI\SettingsPanel\SettingsPanel $arResult['SETTINGS_PANEL']
 */

use Bitrix\Main\Loader;

Loader::includeModule('biconnector');
Loader::includeModule('ui');

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	if ($arResult['FEATURE_AVAILABLE'] === false)
	{
		echo '<script>top.BX.UI.InfoHelper.show("limit_crm_BI_analytics")</script>';
	}

	return;
}

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
$APPLICATION->SetTitle($arResult['TITLE']);

/** @var \Bitrix\BIConnector\Superset\UI\SettingsPanel\SettingsPanel $settingsPanel */
$settingsPanel = $arResult['SETTINGS_PANEL'];
$settingsPanel->show();
