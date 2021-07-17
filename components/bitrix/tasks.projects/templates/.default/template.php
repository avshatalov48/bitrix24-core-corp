<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\Theme;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.actionpanel',
	'ui.alerts',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.icons',
	'ui.label',
]);

CJSCore::Init('tasks_integration_socialnetwork');

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION
 */

$title = Loc::getMessage('TASKS_PROJECTS_TITLE');
$APPLICATION->SetPageProperty('title', $title);
$APPLICATION->SetTitle($title);

if (is_array($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
	$isUiIncluded = \Bitrix\Main\Loader::includeModule('ui');
	foreach ($arResult['ERRORS'] as $error)
	{
		$message = $error['MESSAGE'];
		if ($isUiIncluded)
		{
			?>
			<div class="ui-alert ui-alert-danger">
				<span class="ui-alert-message"><?= htmlspecialcharsbx($message) ?></span>
			</div>
			<?php
		}
		else
		{
			ShowError($message);
		}
	}
	return;
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', "{$bodyClass} transparent-workarea");

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],
		'SECTION_URL_PREFIX' => '',

		'MARK_SECTION_PROJECTS_LIST' => 'Y',
		'USE_AJAX_ROLE_FILTER' => 'N',

		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
	],
	$component,
	['HIDE_ICONS' => true]
);

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}
?>

<div class="pagetitle-container pagetitle-flexible-space">
	<?php
	if (
		CSocNetUser::IsCurrentUserModuleAdmin()
		|| $GLOBALS['APPLICATION']->GetGroupRight('socialnetwork', false, 'Y', 'Y', [SITE_ID, false]) >= 'K'
	)
	{
		?>
		<div class="pagetitle-container tasks-projects-filter-btn-add">
			<a class="ui-btn ui-btn-success ui-btn-icon-add" href="<?= $arParams['PATH_TO_GROUP_CREATE'] ?>" id="projectAddButton">
				<?= Loc::getMessage('TASKS_PROJECTS_ADD_PROJECT') ?>
			</a>
		</div>
		<?php
	}
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty(
		'BodyClass',
		($bodyClass ? $bodyClass . ' ' : '') . ' pagetitle-toolbar-field-view '
	);
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.filter',
		'',
		[
			'FILTER_ID' => $arResult['GRID_ID'],
			'GRID_ID' => $arResult['GRID_ID'],
			'FILTER' => $arResult['FILTERS'],
			'FILTER_PRESETS' => $arResult['PRESETS'],
			'ENABLE_LABEL' => true,
			'ENABLE_LIVE_SEARCH' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'THEME' => Theme::LIGHT,
		],
		$component,
		['HIDE_ICONS' => true]
	);
	?>
</div>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.toolbar',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'FILTER_ID' => $arResult['GRID_ID'],
		'SCOPE' => 'projects_grid',
		'FILTER_FIELD' => 'COUNTERS',
		'COUNTERS' => [
			CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
			CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
			CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
			CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
		],
	],
	$component,
	['HIDE_ICONS' => true]
);

if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<?php
ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.pagenavigation',
	'',
	[
		'PAGE_NUM' => $arResult['CURRENT_PAGE'],
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'URL' => $APPLICATION->GetCurPage(),
		'ENABLE_LAST_PAGE' => false,
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
$navigationHtml = ob_get_clean();
?>

<script type="text/javascript">
	var actionsPanel;
	BX.ready(function() {
		BX.addCustomEvent('BX.UI.ActionPanel:created', function(panel) {
			actionsPanel = panel;
			actionsPanel.removeLeftPosition = true;
			actionsPanel.parentPosition = 'bottom';
			actionsPanel.maxHeight = 50;
			actionsPanel.buildPanelContainer();
		});
	});
</script>

<?php
$stub = ($arResult['CURRENT_PAGE'] > 1 ? null : $arResult['STUB']);
$stub = (count($arResult['ROWS']) > 0 ? null : $stub);

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => ($arResult['HEADERS'] ?? []),
		'ROWS' => $arResult['ROWS'],
		'SORT' => ($arResult['SORT'] ?? []),
		'STUB' => $stub,

		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',

		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'ALLOW_PIN_HEADER' => true,
		'ALLOW_CONTEXT_MENU' => true,

		'SHOW_CHECK_ALL_CHECKBOXES' => true,
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_ACTION_PANEL' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'SHOW_PAGESIZE' => false,

		'NAV_OBJECT' => $arResult['NAV'],
		'NAV_PARAMS' => [
			'SEF_MODE' => 'N',
		],

		'TOTAL_ROWS_COUNT' => $arResult['NAV']->getRecordCount(),
		'DEFAULT_PAGE_SIZE' => 10,

		'TOP_ACTION_PANEL_RENDER_TO' => '.pagetitle-below',
		'ACTION_PANEL' => $arResult['GRID']->prepareGroupActions(),

		'NAV_PARAM_NAME' => 'page',
		'NAV_STRING' => $navigationHtml,
		'SHOW_MORE_BUTTON' => true,
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'CURRENT_PAGE' => $arResult['CURRENT_PAGE'],
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>

<script type="text/javascript">
	BX.ready(function() {
		BX.message({
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_EMPTY: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_EMPTY') ?>'
		});
		var options = <?= Json::encode([
			'signedParameters' => $this->getComponent()->getSignedParameters(),
			'gridId' => $arResult['GRID_ID'],
			'gridStub' => $arResult['STUB'],
			'filterId' => $arResult['GRID_ID'],
			'userId' => $arParams['USER_ID'],
			'sort' => $arResult['SORT'],
			'items' => $arResult['GROUPS'],
			'userOptions' => [
				'pinned' => UserOptionTypeDictionary::OPTION_PINNED,
			],
			'tours' => $arResult['TOURS'],
			'groupTaskPath' => $arParams['PATH_TO_GROUP_TASKS'],
		]) ?>;
		options.actionsPanel = actionsPanel;
		BX.Tasks.ProjectsInstance = new BX.Tasks.Projects.Controller(options);
	});
</script>
