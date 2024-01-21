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
	'ui.dialogs.messagebox',
	'ui.hint',
	'pull.client',
	'ui.icons',
]);

?>

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

?>
</div>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.SupersetDashboardGridManager.Instance = new BX.BIConnector.SupersetDashboardGridManager(<?=CUtil::PhpToJSObject([
			'gridId' => $grid?->getId(),
		])?>);
		BX.UI.Hint.init(BX('biconnector-dashboard-grid'));
	});
</script>
