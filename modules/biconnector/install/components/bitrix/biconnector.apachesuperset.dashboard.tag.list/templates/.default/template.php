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

$APPLICATION->SetTitle(Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_TAG_LIST_TITLE'));

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

Extension::load([
	'ui.dialogs.messagebox',
	'ui.hint',
	'pull.client',
	'ui.icons',
	'ui.notification',
]);

?>

<div id="biconnector-dashboard-tag-grid">
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
		BX.BIConnector.SupersetDashboardTagGridManager.Instance = new BX.BIConnector.SupersetDashboardTagGridManager(<?=Json::encode([
			'gridId' => $grid?->getId(),
		])?>);
	});
</script>
