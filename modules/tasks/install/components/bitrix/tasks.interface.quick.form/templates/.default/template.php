<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var array $arParams */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Integration\Network\MemberSelector;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ProjectLimit;

Extension::load([
	'ui.design-tokens',
	'ui.entity-selector',
]);

$isProjectEnabled = (ProjectLimit::isFeatureEnabled() || ProjectLimit::canTurnOnTrial());

$projectId = 0;
$projectName = Loc::getMessage('TASKS_QUICK_IN_GROUP');
if (is_array($arResult['GROUP']))
{
	$projectId = (int)$arResult['GROUP']['ID'];
	$projectName = $arResult['GROUP']['NAME'];
}

$formattedUserName = CUser::FormatName(
	CSite::GetNameFormat(),
	[
		'LOGIN' => $arResult['USER']['LOGIN'],
		'NAME' => $arResult['USER']['NAME'],
		'LAST_NAME' => $arResult['USER']['LAST_NAME'],
		'SECOND_NAME' => $arResult['USER']['SECOND_NAME'],
	],
	true,
);
?>

<div class="task-top-notification" id="task-new-item-notification">
	<div class="task-top-notification-inner">
		<?= Loc::getMessage(
			'TASKS_QUICK_FORM_AFTER_SAVE_MESSAGE',
			['#TASK_NAME#' => '<span class="task-top-notification-message" id="task-new-item-message"></span>'])
		?>
		<a href="" class="task-top-notification-link" id="task-new-item-open"><?= Loc::getMessage('TASKS_QUICK_FORM_OPEN_TASK') ?></a>
		<span class="task-top-notification-link" id="task-new-item-highlight"><?= Loc::getMessage('TASKS_QUICK_FORM_HIGHLIGHT_TASK') ?></span>
	</div>
	<span
		class="task-top-panel-tab-close task-top-panel-tab-close-active task-top-notification-hide"
		id="task-new-item-notification-hide"></span>
</div>

<div class="task-top-panel-righttop" id="task-new-item">
	<form id="task-new-item-form" action="">
		<span class="task-top-panel-create-container">
			<input type="text" autocomplete="off" placeholder="<?= Loc::getMessage('TASKS_QUICK_ASSIGNEE') ?>"
				   tabindex="3" id="task-new-item-responsible" name="task-new-item-responsible"
				   value="<?= $formattedUserName ?>">
			<input type="hidden" id="task-new-item-responsible-id" value="<?= $arParams['USER_ID'] ?>">
		</span>
		<span class="task-top-panel-create-container">
			<input
				type="text" autocomplete="off" placeholder="<?= Loc::getMessage('TASKS_QUICK_DEADLINE') ?>" tabindex="2"
				id="task-new-item-deadline"
				name="task-new-item-deadline"
				data-default-hour="<?= (int)$arParams['COMPANY_WORKTIME']['END']['H'] ?>"
				data-default-minute="<?= (int)$arParams['COMPANY_WORKTIME']['END']['M'] ?>"
			>
		</span>
		<span class="task-top-panel-create-container task-top-panel-create-container-big">
			<input
				type="text" placeholder="<?= Loc::getMessage('TASKS_QUICK_FORM_TITLE_PLACEHOLDER') ?>"
				tabindex="1" id="task-new-item-title"
			>
		</span>
		<span class="task-top-panel-middle">
			<span class="task-top-panel-leftmiddle" id="task-new-item-description-block">
				<span id="task-new-item-project-link" class="task-top-panel-tab">
					<?= $projectName ?>
					<?php if (!$isProjectEnabled): ?>
						<span class="tasks-tariff-lock"></span>
					<?php endif; ?>
				</span>
				<span
					class="task-top-panel-tab-close<?= ($projectId > 0 ? " task-top-panel-tab-close-active" : "") ?>"
					id="task-new-item-project-clearing">
				</span>
				<span
					class="task-top-panel-tab task-top-panel-leftmiddle-description"
					id="task-new-item-description-link" href=""><?= Loc::getMessage('TASKS_QUICK_DESCRIPTION') ?>
				</span>

				<input type="hidden" id="task-new-item-project-id" value="<?= $projectId ?>">

				<textarea
					cols="30" rows="10" placeholder="<?= Loc::getMessage('TASKS_QUICK_FORM_DESC_PLACEHOLDER') ?>"
					tabindex="4" id="task-new-item-description"></textarea>
			</span>
			<span
				class="ui-btn ui-btn-light-border" id="task-new-item-save"><?= Loc::getMessage('TASKS_QUICK_SAVE') ?>
			</span>
			<span class="ui-btn ui-btn-link" id="task-new-item-cancel"><?= Loc::getMessage('TASKS_QUICK_CANCEL') ?></span>
		</span>
	</form><?php

	$pathToTask = $projectId > 0 ? RouteDictionary::PATH_TO_GROUP_TASK : RouteDictionary::PATH_TO_USER_TASK;

	?>
	<script>
		new BX.Tasks.QuickForm('task-new-item', {
			button: 'task-quick-form-button',
			gridId: "<?=CUtil::JSEscape($arParams['GRID_ID'])?>",
			getListParams: "<?=CUtil::JSEscape(serialize($arParams['GET_LIST_PARAMS']))?>",
			ganttMode: <?= (isset($arParams['SCOPE']) && $arParams['SCOPE'] === ScopeDictionary::SCOPE_TASKS_GANTT ? 'true' : 'false')?>,
			groupByProject: <?=CUtil::PhpToJSObject($arParams['GROUP_BY_PROJECT'])?>,
			destination: <?=CUtil::PhpToJSObject($arResult['DESTINATION'])?>,
			nameTemplate: "<?=CUtil::JSEscape($arParams['NAME_TEMPLATE'])?>",
			canAddMailUsers: <?=CUtil::PhpToJSObject($arResult['CAN']['addMailUsers'])?>,
			canManageTask: <?=CUtil::PhpToJSObject($arResult['CAN']['manageTask'])?>,
			pathToTask: "<?=CUtil::JSEscape($pathToTask)?>",
			currentGroupId: <?=$projectId?>,
			calendarSettings: <?=CUtil::PhpToJSObject($arResult['CALENDAR_SETTINGS'])?>,
			messages: {
				taskInProject: "<?=Loc::getMessage('TASKS_QUICK_IN_GROUP')?>",
			},
			networkEnabled: <?= MemberSelector::isNetworkEnabled() ? 'true' : 'false' ?>,
			scope: '<?= CUtil::JSEscape($arParams['SCOPE']) ?>',
			personalContext: '<?= $projectId === 0 ?>',
			isProjectEnabled: <?= \Bitrix\Main\Web\Json::encode($isProjectEnabled); ?>,
			projectFeatureId: '<?= ProjectLimit::getFeatureId() ?>',
		});
	</script>
</div>