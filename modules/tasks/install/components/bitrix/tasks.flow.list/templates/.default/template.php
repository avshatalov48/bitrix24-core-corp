<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */

use Bitrix\Main\HttpApplication;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Helper\RestrictionUrl;

Extension::load([
	'tasks.flow.edit-form',
	'tasks.flow.view-form',
	'tasks.flow.team-popup',
	'tasks.flow.task-queue',
	'tasks.flow.copilot-advice',
	'pull.queuemanager',
	'ui.icon-set.main',
	'ui.info-helper',
	'tasks.clue',
	'ui.hint',
	'ui.manual',
	'ui.counter',
	'ui.notification',
	'ai.copilot-chat.ui',
]);

/** intranet-settings-support */
if (($arResult['isToolAvailable'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_FLOWS_OFF_SLIDER_URL,
		'SOURCE' => 'flow_list',
	]);

	return;
}

$messages = Loc::loadLanguageFile(__FILE__);

if (!$arResult['isGridRequest'])
{
	require_once __DIR__.'/header.php';
}

$stub = ($arResult['currentPage'] > 1 ? null : $arResult['stub']);
$stub = (count($arResult['rows']) > 0 ? null : $stub);

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['gridId'],
		'COLUMNS' => $arResult['columns'],
		'ROWS' => $arResult['rows'],
		'NAV_OBJECT' => $arResult['navigation'],
		'TOTAL_ROWS_COUNT' => $arResult['totalRowsCount'],
		'CURRENT_PAGE' =>  $arResult['currentPage'],
		'STUB' => $stub,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'SHOW_NAVIGATION_PANEL' => true,
		'ALLOW_PIN_HEADER' => true,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_PAGESIZE' => true,
		'PAGE_SIZES' => $arResult['pageSizes'],
		'ALLOW_COLUMNS_SORT' => false,
	],
	$component,
	['HIDE_ICONS' => true]
);

$uri = new Uri(HttpApplication::getInstance()->getContext()->getRequest()->getRequestUri());
$currentUrl = $uri->getUri();

?>

<script type="text/javascript">
	BX.ready(() => {
		BX.message(<?= Json::encode($messages) ?>);

		BX.Tasks.Flow.Grid = new BX.Tasks.Flow.Grid({
			gridId: '<?= $arResult['gridId'] ?>',
			currentUserId: '<?= $arResult['currentUserId'] ?>',
			currentUrl: '<?= CUtil::JSEscape($currentUrl) ?>',
			isAhaShownOnMyTasksColumn: <?= $arResult['isAhaShownOnMyTasksColumn'] === true ? 'true' : 'false' ?>,
			isAhaShownCopilotAdvice: <?= $arResult['isAhaShownCopilotAdvice'] === true ? 'true' : 'false' ?>,
			flowLimitCode: '<?= \Bitrix\Tasks\Flow\FlowFeature::LIMIT_CODE ?>',
		});

		new BX.Tasks.Flow.Filter({
			filterId: '<?= $arResult['filterId'] ?>',
		});
	});
</script>
