 <?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

 use Bitrix\Main\Loader;
 use Bitrix\Main\Localization\Loc;
 use Bitrix\Main\UI\Extension;
 use Bitrix\Tasks\Integration\Recyclebin\Task;

 Extension::load([
	'ui.design-tokens',
	'popup',
	'ui.buttons',
	'ui.buttons.icons',
]);

Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

 if ($arParams['SHOW_AHA_START_FLOW_TASK'])
 {
	 Extension::load('tasks.clue');
 }

$helper = $arResult['HELPER'];

$taskId = $arParams["TASK_ID"];
$can = $arParams["TASK"]["ACTION"];
$taskData = $arParams["TASK"];

 if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
 {
	 $APPLICATION->IncludeComponent(
		 'bitrix:app.placement',
		 'menu',
		 array(
			 'PLACEMENT'         => "TASK_LIST_CONTEXT_MENU",
			 "PLACEMENT_OPTIONS" => array(),
			 //			'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
			 'MENU_EVENT_MODULE' => 'tasks',
			 'MENU_EVENT'        => 'onTasksBuildContextMenu',
		 ),
		 null,
		 array('HIDE_ICONS' => 'Y')
	 );
 }


?>

<div id="<?=$helper->getScopeId()?>" class="task-view-buttonset <?=implode(' ', $arResult['CLASSES'])?>">

	<span data-bx-id="task-view-b-timer" class="task-timeman-link">
		<span class="task-timeman-icon"></span>
		<span id="task_details_buttons_timer_<?=$taskId?>_text" class="task-timeman-text">

		<span data-bx-id="task-view-b-time-elapsed"><?=\Bitrix\Tasks\UI::formatTimeAmount($taskData['TIME_ELAPSED']);?></span>

		<?if ($taskData["TIME_ESTIMATE"] > 0):?>
			/ <?=\Bitrix\Tasks\UI::formatTimeAmount($taskData["TIME_ESTIMATE"]);?>
		<?endif?>
		</span>
		<span class="task-timeman-arrow"></span>
	</span>

	<span data-bx-id="task-view-b-buttonset">

		<span data-bx-id="task-view-b-button" data-action="START_TIMER" class="task-view-button timer-start ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_START_TASK_TIMER")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="PAUSE_TIMER" class="task-view-button timer-pause ui-btn ui-btn-light-border">
			<?=Loc::getMessage("TASKS_PAUSE_TASK_TIMER")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="START" class="task-view-button start ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_START_TASK")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="TAKE" class="task-view-button take ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_TAKE_TASK")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="PAUSE" class="task-view-button pause ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_PAUSE_TASK")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="COMPLETE"  class="task-view-button complete pause ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_CLOSE_TASK")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="APPROVE"  class="task-view-button approve ui-btn ui-btn-success">
			<?=Loc::getMessage("TASKS_APPROVE_TASK")?>
		</span><?

		?><span data-bx-id="task-view-b-button" data-action="DISAPPROVE" class="task-view-button disapprove ui-btn ui-btn-danger">
			<?=Loc::getMessage("TASKS_REDO_TASK_MSGVER_1")?>
		</span><?

		?><span data-bx-id="task-view-b-open-menu" class="task-more-button ui-btn ui-btn-light-border ui-btn-dropdown">
			<?=Loc::getMessage("TASKS_MORE")?>
		</span><?

		?><a href="<?=$arResult['EDIT_URL']?>" class="task-view-button edit ui-btn ui-btn-link" data-slider-ignore-autobinding="true">
			<?=GetMessage("TASKS_EDIT_TASK")?>
		</a>

		<script type="text/html" data-bx-id="task-view-b-timeman-confirm-title">
			<span><?=Loc::getMessage('TASKS_TASK_CONFIRM_START_TIMER_TITLE');?></span>
		</script>
		<script type="text/html" data-bx-id="task-view-b-timeman-confirm-body">
			<div style="width: 400px; padding: 25px;"><?=Loc::getMessage('TASKS_TASK_CONFIRM_START_TIMER');?></div>
		</script>

	</span>
</div>

	<script>
		BX.message({
			TASKS_REST_BUTTON_TITLE_2: '<?=Loc::getMessage('TASKS_REST_BUTTON_TITLE_2')?>',
			TASKS_DELETE_SUCCESS: '<?= Loader::includeModule('recyclebin') ? Task::getDeleteMessage((int)$arParams['USER_ID']) : Loc::getMessage('TASKS_DELETE_SUCCESS') ?>'
		});
	</script>
<?$helper->initializeExtension();?>