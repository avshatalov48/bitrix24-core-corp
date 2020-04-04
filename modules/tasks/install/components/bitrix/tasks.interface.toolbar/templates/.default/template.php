<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Update\Stepper;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view tasks-pagetitle-view');

Extension::load("ui.buttons", "ui.fonts.opensans");

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

if ($isBitrix24Template)
{
	$this->SetViewTarget("below_pagetitle");
}

$showViewMode = $arParams['SHOW_VIEW_MODE'] == 'Y';
$isMyTasks = (is_object($GLOBALS['USER']) && (int)$GLOBALS['USER']->getId() === (int)$arParams['USER_ID']);
?>

<? if (!$isBitrix24Template):?>
<div class="tasks-interface-toolbar-container">
<? endif ?>
	<div id="counter_panel_container" class="tasks-counter">
		<div class="tasks-counter-title" id="<?=$arResult['HELPER']->getScopeId()?>"></div>
	</div>
<?php if(!$showViewMode && $isMyTasks && \Bitrix\Tasks\Integration\Bizproc\Automation\Factory::canUseAutomation()):?>
	<div class="tasks-counter-btn-container">
		<button class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round tasks-counter-btn"
				data-project-id="<?=(int)$arParams['GROUP_ID']?>"
				onclick="BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id='+this.getAttribute('data-project-id'))"
		><?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?></button>
	</div>
<? endif ?>
	
<?php if($showViewMode):?>
<div class="tasks-view-switcher pagetitle-align-right-container">
    <div class="tasks-view-switcher-list">
		<?if ($isMyTasks && \Bitrix\Tasks\Integration\Bizproc\Automation\Factory::canUseAutomation()):?>
		<div class="tasks-counter-btn-container">
			<button class="ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round tasks-counter-btn"
				onclick="BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id=<?=(int)$arParams['GROUP_ID']?>')"
			><?=GetMessage('TASKS_SWITCHER_ITEM_ROBOTS')?></button>
		</div>
		<?endif;?>
        <?php
        $template = $arParams['GROUP_ID'] > 0 ? 'PATH_TO_GROUP_TASKS' : 'PATH_TO_USER_TASKS';
        $link = CComponentEngine::makePathFromTemplate($template, array('user_id'=>$arParams['USER_ID'], 'group_id'=>$arParams['GROUP_ID']));
        foreach($arResult['VIEW_LIST'] as $viewKey => $view):
			if($viewKey == 'VIEW_MODE_KANBAN' && (int)$arParams['GROUP_ID'] == 0)
			{
				continue;
			}

	        $active = array_key_exists('SELECTED', $view) && $view['SELECTED'] == 'Y';

	    $state = \Bitrix\Tasks\Ui\Filter\Task::getListStateInstance()->getState();
//	    if(!empty($state['SPECIAL_PRESET_SELECTED']) && $state['SPECIAL_PRESET_SELECTED']['ID'] == -10) // favorite
//        {
//	        $url = '?F_STATE[]=sV' . CTaskListState::encodeState($view['ID']).'&F_CANCEL=Y&F_FILTER_SWITCH_PRESET=-10&F_STATE[]=sCb0000';
//        }
//        else
//        {
			$url = '?F_STATE=sV'.CTaskListState::encodeState($view['ID']);
//        }
        ?>
        <a href="<?=$url?>" id="tasks_<?=strtolower($viewKey)?>"   class="tasks-view-switcher-list-item <?=$active ? 'tasks-view-switcher-list-item-active' : '';?>"><?=$view['SHORT_TITLE']?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif?>

<? if (!$isBitrix24Template):?>
	</div>
<? endif ?>

<?if($isBitrix24Template)
{
    $this->EndViewTarget();
}
?>
<script>
	BX(function(){
		BX.message({
			_VIEW_TYPE: '<?=$state['VIEW_SELECTED']['CODENAME']?>'
		});
	});
</script>
<div style="<?=$state['VIEW_SELECTED']['CODENAME'] == 'VIEW_MODE_GANTT' ? 'margin:-15px -15px 15px  -15px' : ''?>">
    <?=
		\Bitrix\Main\Update\Stepper::getHtml(
			[
				'tasks' => 'Bitrix\Tasks\Update\FullTasksIndexer'
			],
			GetMessage('TASKS_FULL_TASK_INDEXING_TITLE')
		);
		\Bitrix\Main\Update\Stepper::getHtml(
			[
				'tasks' => [
					'Bitrix\Tasks\Update\LivefeedIndexTask',
					'Bitrix\Tasks\Update\TasksFilterConverter',
					'Bitrix\Tasks\Update\TasksFulltextIndexer'
				]
			]
		);
	?>
</div>

<?$arResult['HELPER']->initializeExtension();?>

<script>
	BX.ready(function()
	{
		var robotsBtn = document.querySelector('button[data-project-id]');
		if (robotsBtn)
		{
			BX.addCustomEvent(window, 'BX.Kanban.ChangeGroup', function(newId)
			{
				robotsBtn.setAttribute('data-project-id', newId);
			});
		}
	});
</script>
