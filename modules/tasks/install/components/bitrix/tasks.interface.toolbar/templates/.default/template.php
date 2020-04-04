<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass? $bodyClass.' ' : '').'pagetitle-toolbar-field-view tasks-pagetitle-view');

Extension::load([
	"ui.buttons",
	"ui.fonts.opensans",
]);

$isMyTasks = (is_object($GLOBALS['USER']) && (int)$GLOBALS['USER']->getId() === (int)$arParams['USER_ID']);
$showViewMode = $arParams['SHOW_VIEW_MODE'] == 'Y';
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

if ($isBitrix24Template)
{
	$this->SetViewTarget("below_pagetitle");
}
?>

<?php if ($showViewMode):?>
<div class="tasks-view-switcher">
    <div class="tasks-view-switcher-list">
        <?php
			// temporary we show agile parts only by option
			$optionName = 'agile_enabled_group_'.$arParams['GROUP_ID'];
			$showSprint = Option::get('tasks', $optionName, 'N') === 'Y';
			$template = ($arParams['GROUP_ID'] > 0 ? 'PATH_TO_GROUP_TASKS' : 'PATH_TO_USER_TASKS');
			$link = CComponentEngine::makePathFromTemplate($template, [
				'user_id' => $arParams['USER_ID'],
				'group_id' => $arParams['GROUP_ID'],
			]);

			foreach ($arResult['VIEW_LIST'] as $viewKey => $view)
			{
				$hideSprint = ($viewKey === 'VIEW_MODE_SPRINT' && !$showSprint);
				$isOfKanbanType = in_array($viewKey, ['VIEW_MODE_KANBAN', 'VIEW_MODE_SPRINT']);

				// kanban and sprint only for group
				if ($hideSprint || (int)$arParams['GROUP_ID'] <= 0 && $isOfKanbanType)
				{
					continue;
				}

				$active = array_key_exists('SELECTED', $view) && $view['SELECTED'] === 'Y';
				$state = \Bitrix\Tasks\Ui\Filter\Task::getListStateInstance()->getState();
				$url = '?F_STATE=sV'.CTaskListState::encodeState($view['ID']);
				if ($_REQUEST['IFRAME'])
				{
					$url .= '&IFRAME='.($_REQUEST['IFRAME'] == 'Y' ? 'Y' : 'N');
				}

				?><a class="tasks-view-switcher-list-item <?=($active ? 'tasks-view-switcher-list-item-active' : '')?>"
					 href="<?=$url?>" id="tasks_<?=strtolower($viewKey)?>">
					<?=$view['SHORT_TITLE']?>
				</a><?php
			}
		?></div>
	</div>
<?php endif?>

<? if (!$isBitrix24Template):?>
	<div class="tasks-interface-toolbar-container">
<?php endif ?>

	<div class="tasks-counter" id="counter_panel_container">
		<div class="tasks-counter-title" id="<?=$arResult['HELPER']->getScopeId()?>"></div>
	</div>
		<?php
		if ($arParams['GROUP_ID'] <= 0 && \Bitrix\Main\Loader::includeModule('intranet'))
		{
			$APPLICATION->includeComponent(
				'bitrix:intranet.binding.menu',
				'',
				array(
					'SECTION_CODE' => 'tasks_switcher',
					'MENU_CODE' => 'user'
				)
			);
		}
		?>
	<?php if ($isMyTasks && $arResult['COUNTERS_SHOW'] && Factory::canUseAutomation()):?>
		<?php if ($showViewMode):?>
			<div class="tasks-counter-btn-container">
				<button class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round tasks-counter-btn"
						onclick="BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id=<?=(int)$arParams['GROUP_ID']?>')"
				><?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?></button>
			</div>
		<?php else:?>
			<div class="tasks-counter-btn-container">
				<button class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round tasks-counter-btn"
						data-project-id="<?=(int)$arParams['GROUP_ID']?>"
						onclick="BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id='+this.getAttribute('data-project-id'))"
				><?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?></button>
			</div>
		<?php endif?>
	<?php endif?>

<? if (!$isBitrix24Template):?>
	</div>
<? endif ?>

<?if ($isBitrix24Template)
{
    $this->EndViewTarget();
}
?>

<div style="<?=$state['VIEW_SELECTED']['CODENAME'] == 'VIEW_MODE_GANTT' ? 'margin:-15px -15px 15px  -15px' : ''?>">
    <?php
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\FullTasksIndexer'],
			GetMessage('TASKS_FULL_TASK_INDEXING_TITLE')
		);
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\TemplateCheckListConverter'],
			GetMessage('TASKS_TEMPLATE_CHECKLIST_CONVERTING_TITLE')
		);
		echo Stepper::getHtml(
			['tasks' => 'Bitrix\Tasks\Update\TaskCheckListConverter'],
			GetMessage('TASKS_TASK_CHECKLIST_CONVERTING_TITLE')
		);
		echo Stepper::getHtml([
			'tasks' => [
				'Bitrix\Tasks\Update\LivefeedIndexTask',
				'Bitrix\Tasks\Update\TasksFilterConverter',
				'Bitrix\Tasks\Update\TasksFulltextIndexer',
			]
		]);
	?>
</div>

<?
if ($arResult['COUNTERS_SHOW'])
{
	$arResult['HELPER']->initializeExtension();
}

if ($arResult['SPOTLIGHT_TIMELINE'])
{
	\CJSCore::init('spotlight');
}
?>
<script>
	BX.ready(function()
	{
		BX.message({
			_VIEW_TYPE: '<?=$state['VIEW_SELECTED']['CODENAME']?>'
		});

		var robotsBtn = document.querySelector('button[data-project-id]');
		if (robotsBtn)
		{
			BX.addCustomEvent(window, 'BX.Kanban.ChangeGroup', function(newId) {
				robotsBtn.setAttribute('data-project-id', newId);
			});
		}
		<?if ($arResult['SPOTLIGHT_TIMELINE']):?>
		var spotlight = new BX.SpotLight({
			id: 'tasks_timeline',
			targetElement: BX('tasks_view_mode_timeline'),
			content: '<?= \CUtil::jsEscape(GetMessage('TASKS_TEMPLATE_SPOTLIGHT_TIMELINE'))?>',
			targetVertex: 'middle-center',
			autoSave: true,
			lightMode: true
		});
		spotlight.show();
		<?endif;?>
	});
</script>
