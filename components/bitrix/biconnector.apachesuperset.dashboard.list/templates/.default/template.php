<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

$APPLICATION->SetTitle(Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_LIST_TITLE'));

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

if (\Bitrix\Main\Loader::includeModule('pull'))
{
	global $USER;
	\CPullWatch::Add($USER->getId(), "superset_dashboard", true);
}

Extension::load([
	'biconnector.apache-superset-dashboard-manager',
	'biconnector.apache-superset-analytics',
	'biconnector.apache-superset-cleaner',
	'biconnector.dashboard-export-master',
	'ui.dialogs.messagebox',
	'ui.hint',
	'ui.buttons',
	'pull.client',
	'ui.icons',
	'ui.icon-set.actions',
	'ui.feedback.form',
	'ui.alerts',
	'ui.tour',
	'ui.switcher',
	'spotlight',
]);

if ($arResult['SHOW_DELETE_INSTANCE_BUTTON']):
?>

<div class='ui-alert ui-alert-danger'>
	<span class='ui-alert-message'><?= Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_LOCK_NOTIFICATION') ?></span>
</div>

<?php endif; ?>

<div id="biconnector-dashboard-grid">
<?php

/** @var \Bitrix\Main\Grid\Grid $grid */
$grid = $arResult['GRID'];

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	\Bitrix\Main\Grid\Component\ComponentParams::get($grid, [
		'CURRENT_PAGE' => $grid->getPagination()?->getCurrentPage(),
	])
);

$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
$limitManager->setIsSuperset();
if (!$limitManager->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '', [
		'SUPERSET_LIMIT' => 'Y',
	]);
}

?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.SupersetDashboardGridManager.Instance = new BX.BIConnector.SupersetDashboardGridManager(<?= Json::encode([
			'gridId' => $grid?->getId(),
			'isNeedShowTopMenuGuide' => $arResult['NEED_SHOW_TOP_MENU_GUIDE'] ?? false,
			'isNeedShowDraftGuide' => $arResult['NEED_SHOW_DRAFT_GUIDE'] ?? false,
		])?>);

		BX.BIConnector.ApacheSupersetTariffCleaner.Instance = new BX.BIConnector.ApacheSupersetTariffCleaner();
	});
</script>
