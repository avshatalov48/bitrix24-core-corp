<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\Theme;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Project\UserOption\UserOptionTypeDictionary;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ScrumLimit;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\SettingsButton;

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.actionpanel',
	'ui.alerts',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.icons',
	'ui.label',
	'ui.info-helper',
	'socialnetwork.common',
	'tasks_integration_socialnetwork',
]);

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION
 * @var $component
 * @var $templateFolder
 */

$title = Loc::getMessage(($arResult['isScrumList'] ? 'TASKS_PROJECTS_SCRUM_TITLE' : 'TASKS_PROJECTS_TITLE'));
$APPLICATION->SetPageProperty('title', $title);
$APPLICATION->SetTitle($title);

if (is_array($arResult['ERRORS']) && !empty($arResult['ERRORS']))
{
	$isUiIncluded = Loader::includeModule('ui');
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

$scope = ($arResult['isScrumList'] ? ScopeDictionary::SCOPE_SCRUM_PROJECTS_GRID : ScopeDictionary::SCOPE_PROJECTS_GRID);

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],
		'SECTION_URL_PREFIX' => '',

		'MARK_SECTION_PROJECTS_LIST' => $arParams['MARK_SECTION_PROJECTS_LIST'],
		'MARK_SECTION_SCRUM_LIST' => $arParams['MARK_SECTION_SCRUM_LIST'],
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
		'SCOPE' => $scope,
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
	if (Workgroup::canCreate())
	{
		$btnText =
			$arResult['isScrumList']
				? Loc::getMessage('TASKS_PROJECTS_SCRUM_ADD_PROJECT')
				: Loc::getMessage('TASKS_PROJECTS_CREATE_PROJECT')
		;

		$createProjectUrl = $arParams['PATH_TO_GROUP_CREATE'];
		if ($arResult['isScrumList'])
		{
			$isScrumLimited = ScrumLimit::isLimitExceeded();
			$scrumLimitSidePanelId = ScrumLimit::getSidePanelId();
			if ($isScrumLimited)
			{
				$createProjectUrl = "javascript:BX.UI.InfoHelper.show('{$scrumLimitSidePanelId}', {isLimit: true, limitAnalyticsLabels: {module: 'tasks', source: 'scrumList'}});";
			}
			else
			{
				$uri = new Uri($createProjectUrl);
				$uri->addParams([
					'PROJECT_OPTIONS' => [
						'scrum' => true,
					]
				]);

				$createProjectUrl = $uri->getUri();
			}
		}
		?>
		<div class="pagetitle-container tasks-projects-filter-btn-add">
			<a class="ui-btn ui-btn-success" href="<?= $createProjectUrl ?>" id="projectAddButton">
				<?= $btnText ?>
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
			'THEME' => Theme::MUTED,
		],
		$component,
		['HIDE_ICONS' => true]
	);
	?>
</div>

<?php if ($arResult['isScrumList'] && Loader::includeModule('ui')):
	$settingsButton = new SettingsButton([
		'classList' => ['ui-btn-themes'],
	]);
	$settingsButton->setMenu([
			'items' => [
				[
					'text' => Loc::getMessage('TASKS_PROJECTS_SCRUM_MIGRATION'),
					'href' => '/marketplace/?tag[]=migrator&tag[]=tasks',
					'onclick' => new JsCode('arguments[1].getMenuWindow().close();'),
				],
			],
		]
	);
	?>

	<div class="pagetitle-container pagetitle-align-right-container">
		<?= $settingsButton->render(); ?>
	</div>
<?php endif; ?>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}

if ($arResult['isScrumList'])
{
	$counters = [
		CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS,
		CounterDictionary::COUNTER_SCRUM_FOREIGN_COMMENTS,
	];
}
else
{
	$counters = [
		CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
		CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
		CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
		CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
	];
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.toolbar',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'FILTER_ID' => $arResult['GRID_ID'],
		'SCOPE' => $scope,
		'FILTER_FIELD' => 'COUNTERS',
		'COUNTERS' => $counters,
	],
	$component,
	['HIDE_ICONS' => true]
);
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

if ($arResult['isScrumList'] && is_array($stub) && count($stub) > 2)
{
	$jiraIcon = $templateFolder. '/images/tasks-projects-jira.svg';
	$asanaIcon = $templateFolder. '/images/tasks-projects-asana.svg';
	$trelloIcon = $templateFolder. '/images/tasks-projects-trello.svg';

	$stub = <<<HTML
		<div class="tasks-scrum__transfer--contant">
			<div class="tasks-scrum__transfer--title">{$stub['title']}</div>
			<div class="tasks-scrum__transfer--description">{$stub['description']}</div>
			<div class="tasks-scrum__transfer--content">
				<div class="tasks-scrum__transfer--info">
					<div class="tasks-scrum__transfer--info-text">
						{$stub['migrationTitle']}
					</div>
					<div class="tasks-scrum__transfer--info-systems">
						<div class="tasks-scrum__transfer--info-systems-item">
							<img src="{$jiraIcon}" alt="Jira">
						</div>
						<div class="tasks-scrum__transfer--info-systems-item">
							<img src="{$asanaIcon}" alt="Asana">
						</div>
						<div class="tasks-scrum__transfer--info-systems-item">
							<img src="{$trelloIcon}" alt="Trello">
						</div>
						<div class="tasks-scrum__transfer--info-systems-item">{$stub['migrationOther']}</div>
					</div>
				</div>
				<div class="tasks-scrum__transfer--btn-block">
					<a href="/marketplace/?tag[]=migrator&tag[]=tasks" class="ui-btn ui-btn-primary ui-btn-round">
						{$stub['migrationButton']}
					</a>
				</div>
			</div>
		</div>
	HTML;
}

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

		'TOP_ACTION_PANEL_RENDER_TO' => (
			isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y' ? '.ui-side-panel-wrap-below' : '.pagetitle-below'
		),
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
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_ALL: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_ALL') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_HEADS') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_MEMBERS') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_EMPTY: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_EMPTY') ?>',
			TASKS_PROJECTS_ENTITY_SELECTOR_TAG_SEARCH_FOOTER_ADD: '<?= GetMessageJS('TASKS_PROJECTS_ENTITY_SELECTOR_TAG_SEARCH_FOOTER_ADD') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_TEAM: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_TEAM') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_MEMBERS: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_TITLE_SCRUM_MEMBERS') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_OWNER: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_OWNER') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_MASTER: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_MASTER') ?>',
			TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_TEAM: '<?= GetMessageJS('TASKS_PROJECTS_MEMBERS_POPUP_LABEL_SCRUM_TEAM') ?>'
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
			'isScrumList' => $arResult['isScrumList'] ? 'Y' : 'N',
			'createProjectUrl' => $createProjectUrl ?: '',
			'scrumLimitSidePanelId' => $scrumLimitSidePanelId ?: ''
		]) ?>;
		options.actionsPanel = actionsPanel;
		BX.Tasks.ProjectsInstance = new BX.Tasks.Projects.Controller(options);
	});
</script>