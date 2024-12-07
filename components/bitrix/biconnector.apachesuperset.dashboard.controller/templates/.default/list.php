<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;

/**
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var array $arResult
 */

Loader::includeModule('ui');

/** @var array $arResult */

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
			'DESCRIPTION' => $arResult['ERROR_DESCRIPTIONS'][0] ?? null,
		],
	);

	if ($arResult['FEATURE_AVAILABLE'] === false)
	{
		Extension::load([
			'biconnector.apache-superset-cleaner',
		]);
		$helperCode = \CUtil::JSEscape($arResult['HELPER_CODE']);

		echo <<<HTML
			<script>top.BX.UI.InfoHelper.show("{$helperCode}")
			BX.ready(() => {
				BX.BIConnector.ApacheSupersetTariffCleaner.Instance = new BX.BIConnector.ApacheSupersetTariffCleaner();
			})
			</script>
		HTML;
	}

	if ($arResult['TOOLS_AVAILABLE'] === false)
	{
		echo '<script>top.BX.UI.InfoHelper.show("' . \CUtil::JSEscape($arResult['HELPER_CODE']) . '")</script>';
	}

	return;
}

$this->setViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.control_panel',
	'',
	[
		'ID' => 'DASHBOARD_LIST',
		'ACTIVE_ITEM_ID' => 'DASHBOARD_LIST',
	],
	$component
);
$this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:biconnector.apachesuperset.dashboard.list',
	'',
	[],
	$component
);

Extension::load([
	'biconnector.apache-superset-analytics',
]);
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$analyticSource = $request->get('openFrom') ?? 'other';
$isCanSendStartupMetric = $arResult['CAN_SEND_STARTUP_METRIC'] ? 'true' : 'false';
?>

<script>
	BX.ready(() => {
		if (<?=$isCanSendStartupMetric?>)
		{
			BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('infrastructure', 'start', {
				c_element: 'system',
				status: 'success',
			});
			BX.ajax.runAction('biconnector.superset.onStartupMetricSend');
		}

		BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('view', 'grid_view', {
			c_element: '<?=CUtil::JSEscape($analyticSource)?>',
		});
	});
</script>
