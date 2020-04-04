<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

$taskId = $arParams["TASK_ID"];
$can = $arParams["TASK"]["ACTION"];
$taskData = $arParams["TASK"];
?>

<div id="task-view-buttons" class="task-view-buttonset <?=implode(' ', $arResult['CLASSES'])?>">

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

		<span data-bx-id="task-view-b-button" data-action="START_TIMER" class="task-view-button timer-start webform-small-button webform-small-button-accept">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_START_TASK_TIMER")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="PAUSE_TIMER" class="task-view-button timer-pause webform-small-button">
			<span class="webform-small-button-icon task-button-icon-pause"></span>
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_PAUSE_TASK_TIMER")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="START" class="task-view-button start webform-small-button webform-small-button-accept">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_START_TASK")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="PAUSE" class="task-view-button pause webform-small-button webform-small-button-accept">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_PAUSE_TASK")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="COMPLETE"  class="task-view-button complete webform-small-button webform-small-button-accept">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_CLOSE_TASK")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="APPROVE"  class="task-view-button approve webform-small-button webform-small-button-accept">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_APPROVE_TASK")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-button" data-action="DISAPPROVE" class="task-view-button disapprove webform-small-button webform-small-button-decline">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_REDO_TASK")?>
			</span>
		</span>

		<span data-bx-id="task-view-b-open-menu" class="task-more-button webform-small-button webform-small-button-transparent">
			<span class="webform-small-button-text">
				<?=Loc::getMessage("TASKS_MORE")?>
			</span>
		</span>

		<a href="<?=$arResult['EDIT_URL']?>" class="task-view-button edit webform-small-button-link task-button-edit-link">
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
	new BX.Tasks.Component.TaskDetailPartsButtons(<?=CUtil::PhpToJSObject(array(
		'scope' => 'task-view-buttons',
		'can' => $can,
		'taskId' => $taskId,
		'publicMode' => $arParams["PUBLIC_MODE"],
		'data' => array(
			'TIME_ESTIMATE' => $taskData['TIME_ESTIMATE'],
			'TIME_ELAPSED' => $taskData['TIME_ELAPSED'],
			'TIMER_IS_RUNNING_FOR_CURRENT_USER' => $taskData['TIMER_IS_RUNNING_FOR_CURRENT_USER']
		),
		'copyUrl' => $arResult['COPY_URL'],
		'createSubtaskUrl' => $arResult['CREATE_SUBTASK_URL'],
		'listUrl' => $arParams["PATH_TO_TASKS"],
	), false, false, true)?>);
</script>