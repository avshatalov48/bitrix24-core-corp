<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var string $templateFolder
 */

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\BIConnector\Integration\Superset\Integrator\ServiceLocation;

Loader::includeModule('biconnector');
Loader::includeModule('ui');

Extension::load([
	'biconnector.apache-superset-analytics',
	'ui.lottie',
]);

$analyticSource = Context::getCurrent()->getRequest()->get('openFrom') ?? 'grid';
if (isset($arResult['OPEN_LOGIN_POPUP']) && $arResult['OPEN_LOGIN_POPUP'])
{
	$analyticSource = 'copy';
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

	?>
		<script>
			BX.ready(() => {
				BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('view', 'report_view', {
					c_element: '<?= CUtil::JSEscape($analyticSource) ?>',
					status: 'error',
				});
			});
		</script>
	<?php

	return;
}

Extension::load([
	'biconnector.apache-superset-embedded-loader',
	'biconnector.apache-superset-dashboard-manager',
	'biconnector.apache-superset-dashboard-selector',
	'biconnector.apache-superset-feedback-form',
	'biconnector.dashboard-export-master',
	'ui.buttons',
	'ui.entity-selector',
	'ui.feedback.form',
	'ui.icons',
	'ui.notification',
	'ui.icon-set.actions',
	'ui.hint',
	'loc',
	'sidepanel',
]);

$dashboardTitle = htmlspecialcharsbx($arResult['DASHBOARD_TITLE']);
$APPLICATION->SetTitle($dashboardTitle);

$supersetServiceLocation = $arResult['SUPERSET_SERVICE_LOCATION'];
if ($supersetServiceLocation === ServiceLocation::DATACENTER_LOCATION_REGION_EN)
{
	$biBuilderLogo = $templateFolder . '/images/bi-builder-logo-en.svg';
}
else
{
	$biBuilderLogo = $templateFolder . '/images/bi-builder-logo-ru.svg';
}

$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
$limitManager->setIsSuperset();
if (!$limitManager->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '', [
		'SUPERSET_LIMIT' => 'Y',
	]);
}

?>
<style>
	.dashboard-header {
		--forward-icon: url("<?= $templateFolder . '/images/forward.svg' ?>");
		--more-icon: url("<?= $templateFolder . '/images/more.svg' ?>");
	}

	.dashboard-header-logo-svg-url {
		background-image: url("<?= $biBuilderLogo ?>");
	}

	.icon-forward i {
		background-image: var(--forward-icon, var(--ui-icon-service-bg-image)) !important;
	}

	.icon-more i {
		background-image: var(--more-icon, var(--ui-icon-service-bg-image)) !important;
	}
</style>

<div id="dashboard">
	<div class="dashboard-header">
		<div class="dashboard-header-title-section">
			<div class="dashboard-header-logo">
				<div class="dashboard-header-logo-svg dashboard-header-logo-svg-url"></div>
			</div>
			<div class="dashboard-header-selector-container" id="dashboard-selector">
				<div class="dashboard-header-selector-text" id="dashboard-selector-text"><?= $dashboardTitle ?></div>
				<div class="ui-icon-set --chevron-down dashboard-header-selector-icon"></div>
			</div>
		</div>
		<div class="dashboard-header-buttons">
			<button id="edit-btn" class="ui-btn ui-btn-primary ui-btn-round dashboard-header-buttons-edit"><?= Loc::getMessage('SUPERSET_DASHBOARD_DETAIL_HEADER_EDIT') ?></button>
			<button id="download-btn" class="ui-btn ui-btn-primary ui-btn-round dashboard-header-buttons-download"><?= Loc::getMessage('SUPERSET_DASHBOARD_DETAIL_HEADER_DOWNLOAD') ?></button>
			<div id="more-btn" class="ui-icon ui-icon-service-light-other icon-more dashboard-header-buttons-more"><i></i></div>
		</div>
	</div>
	<div class='dashboard-iframe'></div>
</div>


<script>
	BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
	BX.ready(() => {
		new BX.BIConnector.ApacheSuperset.Dashboard.Detail.create(
			<?= Json::encode([
				'appNodeId' => 'dashboard',
				'openLoginPopup' => $arResult['OPEN_LOGIN_POPUP'],
				'canExport' => $arResult['CAN_EXPORT'],
				'canEdit' => $arResult['CAN_EDIT'],
				'analyticSource' => $analyticSource,
				'dashboardEmbeddedParams' => [
					'guestToken' => $arResult['GUEST_TOKEN'],
					'uuid' => $arResult['DASHBOARD_UUID'],
					'id' => $arResult['DASHBOARD_ID'],
					'title' => $dashboardTitle,
					'nativeFilters' => $arResult['NATIVE_FILTERS'],
					'urlParams' => $arResult['URL_PARAMS'],
					'editUrl' => $arResult['DASHBOARD_EDIT_URL'],
					'supersetDomain' => \CUtil::JSEscape($arResult['SUPERSET_DOMAIN']),
					'type' => $arResult['DASHBOARD_TYPE'],
					'appId' => $arResult['DASHBOARD_APP_ID'],
					'paramsCompatible' => $arResult['PARAMS_COMPATIBLE'],
				],
				'embeddedDebugMode' => $arResult['EMBEDDED_DEBUG_MODE'],
			]) ?>
		);

		new BX.BIConnector.SupersetDashboardSelector(<?= Json::encode([
			'containerId' => 'dashboard-selector',
			'textNodeId' => 'dashboard-selector-text',
			'dashboardId' => $arResult['DASHBOARD_ID'],
			'marketCollectionUrl' => $arResult['MARKET_COLLECTION_URL'],
			'isMarketInstalled' => Loader::includeModule('market'),
			'dashboardUrlParams' => $arResult['URL_PARAMS'],
		]) ?>);
	});
</script>
