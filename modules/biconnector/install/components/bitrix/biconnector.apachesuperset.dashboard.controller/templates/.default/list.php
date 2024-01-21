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

if (\CCrmPerms::IsAccessEnabled())
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		[
			'ID' => 'BI_REPORT_LIST',
			'ACTIVE_ITEM_ID' => 'BI_REPORT_LIST',
		],
		$component
	);
}

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	if ($arResult['FEATURE_AVAILABLE'] === false || $arResult['TOOLS_AVAILABLE'] === false)
	{
		echo '<script>top.BX.UI.InfoHelper.show("' . \CUtil::JSEscape($arResult['HELPER_CODE']) . '")</script>';
	}

	return;
}

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
