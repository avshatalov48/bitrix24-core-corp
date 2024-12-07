<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var string $templateAddUrl */
/** @var CBitrixComponent $component */
/** @global array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Toolbar\ButtonLocation;

Loc::loadMessages(__FILE__);
Extension::load([
	"ui.buttons",
	"ui.buttons.icons",
	"ui.fonts.opensans",
	'ui.entity-selector',
]);

$isIFrame = isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y";
if($isIFrame && isset($templateAddUrl))
{
    $templateAddUrl .= '?IFRAME=Y';
}


$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$hideFilter = $arParams['HIDE_FILTER'] ?? 'N';
if ($hideFilter != 'Y')
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty(
		'BodyClass',
		($bodyClass ? $bodyClass.' ' : '').' no-all-paddings pagetitle-toolbar-field-view'
	);
}

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

$templateAddUrl = CComponentEngine::MakePathFromTemplate(
	$arParams["PATH_TO_USER_TASKS_TEMPLATE"],
	[
		"user_id" => $arParams["USER_ID"],
		"action" => 'edit',
		"template_id" => 0
	]
);

/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'templates',
	]);

	return;
}

?>

<?php
$hideMenu = $arParams['HIDE_MENU'] ?? 'N';
if ($hideMenu != 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.topmenu',
		'',
		array(
			'USER_ID' => $arParams['USER_ID'],

			'SECTION_URL_PREFIX' => '',
			'MARK_TEMPLATES'     => 'Y',

			'PATH_TO_USER_TASKS'                   => $arParams['PATH_TO_USER_TASKS'],
			'PATH_TO_USER_TASKS_TASK'              => $arParams['PATH_TO_USER_TASKS_TASK'],
			'PATH_TO_USER_TASKS_VIEW'              => $arParams['PATH_TO_USER_TASKS_VIEW'],
			'PATH_TO_USER_TASKS_REPORT'            => $arParams['PATH_TO_USER_TASKS_REPORT'],
			'PATH_TO_USER_TASKS_TEMPLATES'         => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
			'PATH_TO_CONPANY_DEPARTMENT'           => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		),
		$component,
		array('HIDE_ICONS' => true)
	);
} ?>

<?php
$hideFilter = $arParams['HIDE_FILTER'] ?? 'N';
if ($hideFilter !== 'Y')
{
	//region FILTER
	if (!$isBitrix24Template): ?>
		<div class="tasks-interface-filter-container">
	<?php
	endif ?>

	<div class="pagetitle-container pagetitle-flexible-space">
		<?php
		Toolbar::addFilter([
			'FILTER_ID' => $arParams['FILTER_ID'],
			'GRID_ID' => $arParams["GRID_ID"],
			'FILTER' => $arResult['FILTER']['FIELDS'],
			'FILTER_PRESETS' => $arResult['FILTER']['PRESETS'],
			'ENABLE_LABEL' => true,
			'ENABLE_LIVE_SEARCH' => (isset($arParams['USE_LIVE_SEARCH']) && $arParams['USE_LIVE_SEARCH'] === 'Y'),
			'RESET_TO_DEFAULT_MODE' => true,
		]);
		if ((TemplateAccessController::can($arParams['USER_ID'], ActionDictionary::ACTION_TEMPLATE_CREATE)))
		{
			$button = new Button([
				'link' => $templateAddUrl,
				'color' => Color::SUCCESS,
				'text' => Loc::getMessage('TASKS_TEMPLATE_CREATE'),
			]);
			Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);
		}
		?>
	</div>
	<?php
	if (!$isBitrix24Template): ?>
		</div>
	<?php
	endif; ?>
	<?php
}
?>

<?php if (\Bitrix\Tasks\Update\TemplateConverter::isProceed()): ?>
	<?php
		$APPLICATION->IncludeComponent("bitrix:tasks.interface.emptystate", "", [
			'TITLE' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT_TITLE'),
			'TEXT' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT'),
		]);
	?>
<?php else: ?>

	<? $helper->displayFatals(); ?>
	<? if (!$helper->checkHasFatals()): ?>

		<div id='<?=$helper->getScopeId()?>' class='tasks'>

			<? $helper->displayWarnings(); ?>

			<? // make dom node accessible in js controller like that: ?>
			<div class='js-id-grid'>
				<?php
				$APPLICATION->IncludeComponent(
					'bitrix:main.ui.grid',
					'',
					array(
						'GRID_ID'   => $arParams['GRID_ID'],
						'HEADERS'   => $arResult['GRID']['HEADERS'],
						'SORT'      => isset($arParams['SORT']) ? $arParams['SORT'] : array(),
						'SORT_VARS' => isset($arParams['SORT_VARS']) ? $arParams['SORT_VARS'] : array(),
						'ROWS'      => $arResult['ROWS'],

						'AJAX_MODE' => 'Y',
						//Strongly required
						"AJAX_OPTION_JUMP" => "N",
						"AJAX_OPTION_STYLE" => "N",
						"AJAX_OPTION_HISTORY" => "N",

						"ALLOW_COLUMNS_SORT"      => true,
						"ALLOW_ROWS_SORT"         => false,
						"ALLOW_COLUMNS_RESIZE"    => true,
						"ALLOW_HORIZONTAL_SCROLL" => true,
						"ALLOW_SORT"              => true,
						"ALLOW_PIN_HEADER"        => true,
						"ACTION_PANEL"            => $arResult['GROUP_ACTIONS'],

						"SHOW_CHECK_ALL_CHECKBOXES" => true,
						"SHOW_ROW_CHECKBOXES" => true,
						//					"SHOW_ROW_ACTIONS_MENU"     => true,
						"SHOW_GRID_SETTINGS_MENU" => true,
						"SHOW_NAVIGATION_PANEL" => true,
						"SHOW_PAGINATION" => true,
						//					"SHOW_SELECTED_COUNTER"     => true,
						//					"SHOW_TOTAL_COUNTER"        => true,
						"SHOW_PAGESIZE" => true,
						//					"SHOW_ACTION_PANEL"         => true,

						"MESSAGES" => $arResult['MESSAGES'] ?? null,

						"ENABLE_COLLAPSIBLE_ROWS" => true,
						//		'ALLOW_SAVE_ROWS_STATE'=>true,

						"SHOW_MORE_BUTTON" => false,
						'~NAV_PARAMS' => $arResult['GET_LIST_PARAMS']['NAV_PARAMS'] ?? '',
						'NAV_OBJECT' => $arResult['NAV_OBJECT'],
						'NAV_STRING' => $arResult['NAV_STRING'],

						"TOTAL_ROWS_COUNT" => $arResult['TOTAL_RECORD_COUNT'],
						//		"CURRENT_PAGE" => $arResult[ 'NAV' ]->getCurrentPage(),
						//		"ENABLE_NEXT_PAGE" => ($arResult[ 'NAV' ]->getPageSize() * $arResult[ 'NAV' ]->getCurrentPage()) < $arResult[ 'NAV' ]->getRecordCount(),
						"PAGE_SIZES" => $arResult['PAGE_SIZES'] ?? '',
						"DEFAULT_PAGE_SIZE" => 50,
					),
					$component,
					array('HIDE_ICONS' => 'Y')
				);
				?>

			</div>

		</div>

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
				<script><?
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

		<? $helper->initializeExtension(); ?>

		<script>
			var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.templates.list/ajax.php?SITE_ID=<?= SITE_ID?>";

			BX.message({
				TASKS_PATH_TO_USER_PROFILE: '<?= CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>',
				TASKS_PATH_TO_TASK: '<?= CUtil::JSEscape(
					str_replace('#template_id#', '#task_id#', ($arParams['PATH_TO_TEMPLATES_TEMPLATE'] ?? ''))
				)?>',
				TASKS_LIST_MENU_RESET_TO_DEFAULT_PRESET: '',
				TASKS_PATH_TO_TEMPLATES_TEMPLATE: '<?= CUtil::JSEscape($arParams['PATH_TO_TEMPLATES_TEMPLATE'] ?? '')?>',

				TASKS_LIST_CONFIRM_ACTION_FOR_ALL_ITEMS: '<?= GetMessageJS('TASKS_LIST_CONFIRM_ACTION_FOR_ALL_TEMPLATE_ITEMS'); ?>',
				TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS: '<?= GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_TEMPLATE_ITEMS'); ?>',
				TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS: '<?= GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_TEMPLATE_ITEMS'); ?>',

				TASKS_LIST_GROUP_ACTION_DELETE_ERROR: '<?= GetMessageJS('TASKS_LIST_GROUP_ACTION_DELETE_ERROR'); ?>',
				TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE_CONFIRM: '<?= GetMessageJS('TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE_CONFIRM'); ?>',
				TASKS_TEMPLATES_LIST_TITLE_ERROR: '<?= Loc::getMessage('TASKS_TEMPLATES_LIST_TITLE_ERROR')?>',
				TASKS_TEMPLATES_LIST_CLOSE: '<?= Loc::getMessage('TASKS_TEMPLATES_LIST_CLOSE')?>',
				TEMPLATE_MOVED_TO_RECYCLEBIN: '<?=  Bitrix\Tasks\Integration\Recyclebin\Template::getDeleteMessage($arParams["USER_ID"]) ?>',
			});
		</script>
	<? endif ?>

<?php endif;