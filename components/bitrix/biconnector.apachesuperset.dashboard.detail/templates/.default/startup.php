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

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\BIConnector\Integration\Superset\Integrator\ServiceLocation;

Loader::includeModule('biconnector');
Loader::includeModule('ui');

if (\Bitrix\Main\Loader::includeModule('pull'))
{
	global $USER;
	\CPullWatch::Add($USER->getId(), "superset_dashboard", true);
}

Extension::load([
	'loc',
	'ui.buttons',
	'ui.icons',
	'biconnector.apache-superset-dashboard-manager',
	'pull.client',
	'ui.lottie',
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
			<div class="dashboard-header-title"><?= $dashboardTitle ?></div>
		</div>
		<div class="dashboard-header-buttons">
			<button id="edit-btn" disabled="disabled" class="ui-btn ui-btn-default ui-btn-round dashboard-header-buttons-edit disabled"><?= Loc::getMessage('SUPERSET_DASHBOARD_DETAIL_HEADER_EDIT') ?></button>
			<div id="more-btn" disabled="disabled" class="ui-icon ui-icon-service-light-other icon-more dashboard-header-buttons-more disabled"><i></i></div>
		</div>
	</div>
	<div class='biconnector-dashboard__loader'></div>
</div>



<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);

		new BX.BIConnector.ApacheSuperset.Dashboard.Detail.createSkeleton({
			container: document.querySelector('.biconnector-dashboard__loader'),
			dashboardId: <?= (int)$arResult['DASHBOARD_ID'] ?>,
			status: '<?= \CUtil::JSEscape($arResult['DASHBOARD_STATUS']) ?>',
			isSupersetAvailable: <?= Json::encode($arResult['IS_SUPERSET_AVAILABLE'] ?? true) ?>,
			paramsCompatible: <?= Json::encode($arResult['PARAMS_COMPATIBLE'] ?? true) ?>,
		})
	});
</script>
