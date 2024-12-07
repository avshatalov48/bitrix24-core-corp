<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;

/** @global \CMain $APPLICATION */
/** @var array $arResult */

Loader::includeModule('ui');

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:ui.info.error',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'TITLE' => $arResult['ERROR_MESSAGES'][0],
			],
			'USE_UI_TOOLBAR' => 'N',
			'PAGE_MODE' => false,
			'PAGE_MODE_OFF_BACK_URL' => '/bi/dashboard/',
			'USE_PADDING' => false,
			'PLAIN_VIEW' => true,
		],
	);

	if ($arResult['FEATURE_AVAILABLE'] === false || $arResult['TOOLS_AVAILABLE'] === false)
	{
		echo '<script>top.BX.UI.InfoHelper.show("' . \CUtil::JSEscape($arResult['HELPER_CODE']) . '")</script>';
	}

	return;
}

if (
	isset($arResult['VARIABLES']['dashboardId'])
	&& (int)$arResult['VARIABLES']['dashboardId']  === 0
)
{
	$appId = \Bitrix\BIConnector\Superset\SystemDashboardManager::resolveMarketAppId((string)$arResult['VARIABLES']['dashboardId']);

	$row = \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable::getList([
		'select' => ['ID', 'APP_ID'],
		'filter' => [
			'=APP_ID' => $appId,
		],
	])->fetch();

	if ($row)
	{
		$dashboardId = (int)$row['ID'];
		$redirectLink = "/bi/dashboard/detail/{$dashboardId}/";
		$uri = new \Bitrix\Main\Web\Uri($redirectLink);
		$uri->addParams(Context::getCurrent()->getRequest()->getQueryList()->toArray());
		$arResult['VARIABLES']['dashboardId'] = $dashboardId;

		?>
			<script>
				window.history.pushState({}, null, '<?=$uri->getPathQuery()?>');
			</script>
		<?php
	}
}

$dashboardId = (int)($arResult['VARIABLES']['dashboardId'] ?? 0);


$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.apachesuperset.dashboard.detail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'DASHBOARD_ID' => $dashboardId,
			'URL_PARAMS' => $arResult['URL_PARAMS'],
		],
		'USE_UI_TOOLBAR' => 'N',
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/bi/dashboard/',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => true,
	]
);

Extension::load([
	'biconnector.apache-superset-analytics',
]);

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
	});
</script>
