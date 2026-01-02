<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Access;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Update\SortConverter;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;
use Bitrix\Main\Web\Json;

/**
 * @global CMain $APPLICATION
 * @var array $arResult
 */

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');

Extension::load([
	'ui.buttons',
	'ui.counter',
	'ui.fonts.opensans',
	'ui.hint',
	'ui.navigationpanel',
	'ui.actions-bar'
]);

$showViewMode = (isset($arParams['SHOW_VIEW_MODE']) && $arParams['SHOW_VIEW_MODE'] === 'Y');
$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24' || SITE_TEMPLATE_ID === 'air');
$isExtranetUser = (bool)Bitrix\Tasks\Integration\Extranet\User::isExtranet();
if ($isBitrix24Template)
{
	$this->SetViewTarget("below_pagetitle");
}

?>

<div class="ui-actions-bar task-interface-toolbar">
<?php if ($showViewMode && !($arParams['PROJECT_VIEW'] === 'Y' && !$arParams['GROUP_ID'])):?>
	<?php
		$template = ($arParams['GROUP_ID'] > 0 ? 'PATH_TO_GROUP_TASKS' : 'PATH_TO_USER_TASKS');
		$link = CComponentEngine::makePathFromTemplate($template, [
			'user_id' => $arParams['OWNER_ID'],
			'group_id' => $arParams['GROUP_ID'],
		]);

		$items = [];

		foreach ($arResult['VIEW_LIST'] as $viewKey => $view)
		{
			$item = [];
			// kanban only for group
			if ((int) $arParams['GROUP_ID'] <= 0 && $viewKey == 'VIEW_MODE_KANBAN')
			{
				continue;
			}

			$active = array_key_exists('SELECTED', $view) && $view['SELECTED'] === 'Y';
			$state = \Bitrix\Tasks\Ui\Filter\Task::getListStateInstance()->getState();
			$url = '?F_STATE=sV'.CTaskListState::encodeState($view['ID']);
			if (isset($_REQUEST['IFRAME']))
			{
				$url .= '&IFRAME='.($_REQUEST['IFRAME'] == 'Y' ? 'Y' : 'N');
			}

			$item['active'] = (bool)$active;
			$item['id'] = 'tasks_' . mb_strtolower($viewKey);
			$item['title'] = $view['SHORT_TITLE'];
			$item['link']['href'] = $url;
			$items[] = $item;
		}
	?>
	<div class="ui-actions-bar__panel" id="tasks-nav-panel"></div>
<?php endif?>

<?php if (!$isBitrix24Template):?>
	<div class="tasks-interface-toolbar-container">
<?php endif ?>
	<?php
	if ($arResult['SHOW_COUNTERS'])
	{
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.interface.counters',
			'',
			[
				'USER_ID' => (int)$arResult['OWNER_ID'],
				'GROUP_ID' => (int)$arResult['GROUP_ID'],
				'ROLE' => $arResult['ROLE'],
				'GRID_ID' => $arParams['GRID_ID'],
				'COUNTERS' => ($arParams['COUNTERS'] ?? []),
				'FILTER_FIELD' => $arParams['FILTER_FIELD'],
			],
			$component
		);
	}
	?>
<?php if (!$isExtranetUser):?>
		<div class="ui-actions-bar__buttons" id="task-interface-toolbar__buttons">
			<?php
			$robotBtnIgnoreList = [
				ScopeDictionary::SCOPE_SCRUM_PROJECTS_GRID,
			];
			if (
				($arResult['USER_ID'] === $arResult['OWNER_ID'])
				&& $arResult['SHOW_COUNTERS']
				&& !in_array($arParams['SCOPE'], $robotBtnIgnoreList, true)
				&& Loader::includeModule('bizproc')
				&& Access\TaskAccessController::can($arResult['USER_ID'], Access\ActionDictionary::ACTION_TASK_ROBOT_EDIT)
			)
			{
				$groupId = (int) ($arParams['GROUP_ID'] ?? 0);
				$projectId = ($showViewMode ? $groupId : 'this.getAttribute(\'data-project-id\')');

				$showLimitSlider = !Factory::canUseAutomation();
				$openLimitSliderAction = Limit::getLimitLockClick(FeatureDictionary::TASK_ROBOTS);
				if (!Factory::isAutomationEnabled())
				{
					$showLimitSlider = true;
					$robotsSliderId = RestrictionUrl::TASK_LIMIT_CRM_RULES_OFF_SLIDER_URL;
					$openLimitSliderAction = "top.BX.UI.InfoHelper.show('{$robotsSliderId}', {isLimit: true, limitAnalyticsLabels: {module: 'tasks'}})";
				}

				$openRobotSliderAction = "BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id='+{$projectId}, {cacheable: false, customLeftBoundary: 0, loader: 'bizproc:automation-loader'});";

				$lockClass = ($showLimitSlider ? 'ui-btn-icon-lock' : '');
				$uiClass = 'ui-btn ui-btn-xs ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round';
				$onClick = ($showLimitSlider ? $openLimitSliderAction : $openRobotSliderAction);

				?><button class="<?=$uiClass?> <?=$lockClass?> task-interface-btn-toolbar --robots --small ui-btn-icon-robots"
					<?=($showViewMode ? '' : "data-project-id='{$groupId}'")?> onclick="<?=$onClick?>">
					<span class="ui-btn-text"><?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?></span>
				</button><?php
			}

			if (Loader::includeModule('intranet'))
			{
				$APPLICATION->includeComponent(
					'bitrix:intranet.binding.menu',
					'',
					[
						'SECTION_CODE' => 'tasks_switcher',
						'MENU_CODE' => ((array_key_exists('GROUP_ID', $arParams) && $arParams['GROUP_ID']) ? 'group' : 'user'),
						'CONTEXT' => (
								(array_key_exists('GROUP_ID', $arParams) && $arParams['GROUP_ID'])
								? ['GROUP_ID' => $arParams['GROUP_ID']]
								: ['USER_ID' => $arParams['OWNER_ID']]
						),
					]
				);
			}
			?>
		</div>
<!---->
	<script>
		(new BX.UI.ActionsBar.RightButtons({
			buttonsContainer: BX('task-interface-toolbar__buttons'),
			collapsable: true,
		})).init();
	</script>
<?php endif;?>
<?php
if (!$isBitrix24Template):?>
	</div>
<?php endif?>

</div>

<?php
if ($isBitrix24Template)
{
    $this->EndViewTarget();
}
?>

<div class="tasks-interface-toolbar__steps"
	 style="<?= (isset($state['VIEW_SELECTED']['CODENAME']) && $state['VIEW_SELECTED']['CODENAME'] == 'VIEW_MODE_GANTT')
		 ? 'margin:-15px -15px 15px  -15px'
		 : ''?>"><?php
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\FullTasksIndexer'],
			GetMessage('TASKS_FULL_TASK_INDEXING_TITLE')
		);
		echo Stepper::getHtml([
			'tasks' => [
				'Bitrix\Tasks\Update\LivefeedIndexTask',
				'Bitrix\Tasks\Update\TasksFilterConverter',
			]
		]);
		echo SortConverter::getHtml();
	?><?php if (\Bitrix\Tasks\Internals\Counter\Queue\Queue::isInQueue((int) $arParams['USER_ID'])): ?>
		<?php \CJSCore::Init(array('update_stepper')); ?>
		<div class="main-stepper-block">
			<div class="main-stepper main-stepper-show" >
				<div class="main-stepper-info"><?= GetMessage('TASKS_FULL_TASK_INDEXING_TITLE'); ?></div>
				<div class="main-stepper-inner">
					<div class="main-stepper-bar">
						<div class="main-stepper-bar-line" style="width:0%;"></div>
					</div>
					<div class="main-stepper-error-text"></div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
if ($arResult['SHOW_COUNTERS'])
{
	$arResult['HELPER']->initializeExtension();
}
if ($arResult['SPOTLIGHT_SIMPLE_COUNTERS'])
{
	\CJSCore::init('spotlight');
}
?>
<script>
	BX.ready(function()
	{
		const navBlock = document.getElementById('tasks-nav-panel');
		if (navBlock)
		{
			(new BX.UI.NavigationPanel({
				target: navBlock,
				items: <?= Json::encode($items ?? []) ?>,
			})).init();
		}

		BX.message({
			_VIEW_TYPE: '<?= $state['VIEW_SELECTED']['CODENAME'] ?? "" ?>'
		});

		var robotsBtn = document.querySelector('button[data-project-id]');
		if (robotsBtn)
		{
			BX.addCustomEvent(window, 'BX.Kanban.ChangeGroup', function(newId) {
				robotsBtn.setAttribute('data-project-id', newId);
			});
		}

		<?if ($arResult['SPOTLIGHT_SIMPLE_COUNTERS']):?>
			var targetElement = BX('tasksSimpleCounters');
			if (targetElement)
			{
				var spotlight = new BX.SpotLight({
					id: 'tasks_simple_counters',
					targetElement: targetElement,
					content: '<?= \CUtil::jsEscape(GetMessage('TASKS_TEMPLATE_SPOTLIGHT_SIMPLE_COUNTERS_MSGVER_1'))?>',
					targetVertex: 'middle-left',
					left: 24,
					autoSave: true,
					lightMode: true
				});
				spotlight.show();
				spotlight.getPopup().getButtons()[0].setName('<?=GetMessage('TASKS_TEMPLATE_SPOTLIGHT_SIMPLE_COUNTERS_BUTTON')?>');
				BX.addCustomEvent(spotlight, 'spotLightOk', function() {
					if (top.BX.Helper)
					{
						top.BX.Helper.show(`redirect=detail&code=11330068`);
					}
				});
			}
		<?php endif;?>

		<?php if ($arResult['SHOW_COUNTERS']):?>
			BX.UI.Hint.init(BX('tasksCommentsReadAll'));
		<?php endif;?>

		var toolbarCounters = document.querySelector('.task-interface-toolbar');
		if(toolbarCounters)
		{
			var toolbarCountersItems = toolbarCounters.querySelectorAll('.ui-actions-bar__panel');
			var toolbarCountersRobots = toolbarCounters.querySelector('.ui-actions-bar__buttons');
			if(toolbarCountersRobots)
			{
				toolbarCountersRobots.classList.add('task-interface-toolbar--item--3');
			}
		}


		if (document.location.hash === '#robots')
		{
			BX.Runtime.loadExtension('ui.tour').then(
				(exports) =>
				{
					const {Guide} = exports;

					const robotsBtn = document.querySelector('.--robots');
					if (robotsBtn)
					{
						const guide = new Guide({
							steps: [
								{
									target: robotsBtn,
									title: '<?= GetMessageJs('TASKS_TOOLBAR_COMPONENT_ROBOTS_GUIDE_TEXT') ?>',
									text: '',
								}
							],
							onEvents: true,
						});

						guide.start();
						guide.getPopup().setAutoHide(true);
						guide.getPopup().setClosingByEsc(true);
					}
				}
			);
		}
	});
</script>
