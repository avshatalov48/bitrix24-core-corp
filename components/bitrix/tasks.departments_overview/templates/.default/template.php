<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load(['ui.icons', 'ui.fonts.opensans']);

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '').' no-background no-all-paddings pagetitle-toolbar-field-view '
);
$isBitrix24Template = (SITE_TEMPLATE_ID === "bitrix24");
$taskLimitExceeded = $arResult['TASK_LIMIT_EXCEEDED'];

if ($taskLimitExceeded)
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}

if (isset($arResult["ERROR"]) && !empty($arResult["ERROR"]))
{
	foreach ($arResult["ERROR"] as $error)
	{
		?>
		<div class="ui-alert ui-alert-icon-warning ui-alert-danger">
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error['MESSAGE'])?></span>
		</div>
		<?
	}

	if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
	{
		require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		exit;
	}

	return;
}
?>

<?php
if (isset($arResult['FILTER']['FIELDS']) && is_array($arResult['FILTER']['FIELDS']))
{
	$selectors = array();

	foreach ($arResult['FILTER']['FIELDS'] as $filterItem)
	{
		if (!(isset($filterItem['type']) &&
			  $filterItem['type'] === 'custom_entity' &&
			  isset($filterItem['selector']) &&
			  is_array($filterItem['selector'])))
		{
			continue;
		}

		$selector = $filterItem['selector'];
		$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
		$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
		$selectorData['MODE'] = $selectorType;
		$selectorData['MULTI'] = $filterItem['params']['multiple'] && $filterItem['params']['multiple'] == 'Y';

		if (!empty($selectorData) && $selectorType == 'user')
		{
			$selectors[] = $selectorData;
		}
		if (!empty($selectorData) && $selectorType == 'group')
		{
			$selectors[] = $selectorData;
		}
	}

	if (!empty($selectors))
	{
		\CUtil::initJSCore(
			array(
				'tasks_integration_socialnetwork'
			)
		);
	}

	if (!empty($selectors))
	{
		?>
		<script type="text/javascript"><?
			foreach ($selectors as $groupSelector)
			{
			$selectorID = $groupSelector['ID'];
			$selectorMode = $groupSelector['MODE'];
			$fieldID = $groupSelector['FIELD_ID'];
			$multi = $groupSelector['MULTI'];
			?>BX.ready(
				function() {
					BX.FilterEntitySelector.create(
						"<?= \CUtil::JSEscape($selectorID)?>",
						{
							fieldId: "<?= \CUtil::JSEscape($fieldID)?>",
							mode: "<?= \CUtil::JSEscape($selectorMode)?>",
							multi: <?= $multi ? 'true' : 'false'?>
						}
					);
				}
			);<?
			}
			?></script><?
	}
}
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => $arParams['USER_ID'],

		'SECTION_URL_PREFIX' => '',
		'MARK_SECTION_MANAGE' => 'Y',

		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => ($arParams['PATH_TO_USER_TASKS_VIEW'] ?? null),
		'PATH_TO_USER_TASKS_REPORT' => ($arParams['PATH_TO_USER_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		'PATH_TO_CONPANY_DEPARTMENT' => ($arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null),
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>


<?php
	//region FILTER
	$this->SetViewTarget('inside_pagetitle');
?>
<div class="pagetitle-container pagetitle-flexible-space">
	<? $APPLICATION->IncludeComponent(
	"bitrix:main.ui.filter",
	"",
	array(
		"FILTER_ID" => $arParams['FILTER_ID'],
		"GRID_ID" => $arParams["GRID_ID"],

		"FILTER" => $arResult['FILTER']['FIELDS'],
		"FILTER_PRESETS" => $arResult['FILTER']['PRESETS'],

		"ENABLE_LABEL"          => true,
		'ENABLE_LIVE_SEARCH'    => ($arParams['USE_LIVE_SEARCH'] ?? null) != 'N',
		'RESET_TO_DEFAULT_MODE' => true,
	),
	$component,
	array("HIDE_ICONS" => true)
	); ?>
</div>
<?php
$this->EndViewTarget();
//endregion
?>

<? $helper->displayFatals(); ?>
<? if (!$helper->checkHasFatals()): ?>

	<div id="<?=$helper->getScopeId()?>" class="tasks<?=($taskLimitExceeded ? ' tasks-locked' : '')?>">

		<? $helper->displayWarnings(); ?>

		<div class='js-id-departments-overview-grid'>
			<?php
			$APPLICATION->IncludeComponent(
				'bitrix:main.ui.grid',
				'',
				array(
					'GRID_ID' => $arParams['GRID_ID'],
					'HEADERS' => $arResult['GRID']['HEADERS'],
					//					'SORT'      => isset($arParams['SORT']) ? $arParams['SORT'] : array(),
					//					'SORT_VARS' => isset($arParams['SORT_VARS']) ? $arParams['SORT_VARS'] : array(),
					'ROWS' => $arResult['ROWS'],

					'AJAX_MODE' => 'Y',
					"AJAX_OPTION_JUMP" => "N",
					"AJAX_OPTION_STYLE" => "N",
					"AJAX_OPTION_HISTORY" => "N",

					"ALLOW_COLUMNS_SORT" => true,
					"ALLOW_ROWS_SORT" => false,
					"ALLOW_COLUMNS_RESIZE" => true,
					"ALLOW_HORIZONTAL_SCROLL" => true,
					"ALLOW_SORT" => true,
					"ALLOW_PIN_HEADER" => true,
					//					"ACTION_PANEL"            => $arResult['GROUP_ACTIONS'],

					"SHOW_CHECK_ALL_CHECKBOXES" => false,
					"SHOW_ROW_CHECKBOXES" => false,
					"SHOW_ROW_ACTIONS_MENU" => false,
					"SHOW_GRID_SETTINGS_MENU" => true,
					"SHOW_NAVIGATION_PANEL" => true,
					"SHOW_PAGINATION" => true,
					"SHOW_SELECTED_COUNTER" => false,
					"SHOW_TOTAL_COUNTER" => false,
					"SHOW_PAGESIZE" => false,
					"SHOW_ACTION_PANEL" => false,

					"ENABLE_COLLAPSIBLE_ROWS" => false,
					//		'ALLOW_SAVE_ROWS_STATE'=>true,

					"SHOW_MORE_BUTTON" => false,
					//					'~NAV_PARAMS'       => $arResult['GET_LIST_PARAMS']['NAV_PARAMS'],
					'NAV_OBJECT' => $arResult['GRID']['NAV'],
					//					'NAV_STRING'       => $arResult['NAV_STRING'],
					//
					//					"TOTAL_ROWS_COUNT"  => $arResult['TOTAL_RECORD_COUNT'],
					//		"CURRENT_PAGE" => $arResult[ 'NAV' ]->getCurrentPage(),
					//		"ENABLE_NEXT_PAGE" => ($arResult[ 'NAV' ]->getPageSize() * $arResult[ 'NAV' ]->getCurrentPage()) < $arResult[ 'NAV' ]->getRecordCount(),
					"PAGE_SIZES" => ($arResult['PAGE_SIZES'] ?? null),
					"DEFAULT_PAGE_SIZE" => 50
				),
				$component,
				array('HIDE_ICONS' => 'Y')
			);
			?>

		</div>

	</div>

	<? $helper->initializeExtension(); ?>

<script type="text/javascript">
	BX.ready(function() {
		var taskLimitExceeded = <?= Json::encode($taskLimitExceeded) ?>;
		if (taskLimitExceeded)
		{
			BX.UI.InfoHelper.show('limit_tasks_supervisor_view', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: 'tasks',
					source: 'view'
				}
			});
		}
	});
</script>

<? endif ?>
