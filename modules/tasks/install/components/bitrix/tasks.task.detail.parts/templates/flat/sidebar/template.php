<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

$templateData = $arParams["TEMPLATE_DATA"];
$taskData = $templateData["DATA"]["TASK"];
$can = $templateData["DATA"]["TASK"]["ACTION"];
$workingTime = $templateData["AUX_DATA"]["COMPANY_WORKTIME"];
$stages = isset($arParams['TEMPLATE_DATA']['DATA']['STAGES']) ? $arParams['TEMPLATE_DATA']['DATA']['STAGES'] : array();
$taskLimitExceeded = $arResult['TASK_LIMIT_EXCEEDED'];

$canReadGroupTasks = (
	array_key_exists('GROUP_ID', $taskData)
	&& \Bitrix\Main\Loader::includeModule('socialnetwork')
	&& Group::canReadGroupTasks(\Bitrix\Tasks\Util\User::getId(), $taskData['GROUP_ID'])
);

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>

<div class="task-detail-sidebar">

	<div class="task-detail-sidebar-content">
		<div class="task-detail-sidebar-status">
			<div class="task-detail-sidebar-status-content">
				<span id="task-detail-status-name" class="task-detail-sidebar-status-text"><?=Loc::getMessage("TASKS_STATUS_".$taskData["REAL_STATUS"])?></span>
				<span id="task-detail-status-date" class="task-detail-sidebar-status-date"><?
					if ($taskData["REAL_STATUS"] != 4 && $taskData["REAL_STATUS"] != 5)
					{
						echo Loc::getMessage("TASKS_SIDEBAR_START_DATE")." ";
					}

					echo $templateData["STATUS_CHANGED_DATE"];

				?></span>
			</div>
		</div>

		<? if ($can["EDIT"] || $can["EDIT.PLAN"] || $templateData["DEADLINE"]): ?>
			<div class="task-detail-sidebar-item task-detail-sidebar-item-deadline">
				<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_QUICK_DEADLINE")?>:</div>
				<div class="task-detail-sidebar-item-value"><?
					if ($can["EDIT"] || $can["EDIT.PLAN"]):
						?><span id="task-detail-deadline"><?=($templateData["DEADLINE"] ? $templateData["DEADLINE"] : Loc::getMessage("TASKS_SIDEBAR_DEADLINE_NO"))?></span><?
						?><span id="task-detail-deadline-clear" class="task-detail-sidebar-item-value-del"<?if (!$templateData["DEADLINE"]):?> style="display: none"<?endif?>></span><?
					else:
						echo $templateData["DEADLINE"];
					endif ?>
				</div>
				<? if ($taskData["STATUS"] == CTasks::METASTATE_EXPIRED):?>
					<div class="task-detail-sidebar-item-delay">
						<div class="task-detail-sidebar-item-delay-message">
							<span class="task-detail-sidebar-item-delay-message-icon"></span>
							<span class="task-detail-sidebar-item-delay-message-text"><?=Loc::getMessage("TASKS_SIDEBAR_TASK_OVERDUE")?></span>
						</div>
					</div>
				<? endif ?>
			</div>
		<? endif ?>

		<?php foreach ((array)$taskData['SE_PARAMETER'] as $param): ?>
			<?php if ((int)$param['CODE'] !== \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED || $param['VALUE'] !== 'Y') { continue; } ?>
			<div class="task-detail-sidebar-item task-detail-sidebar-item-report">
				<div class="task-detail-sidebar-item-report-content">
					<div class="ui-icon ui-icon-common-info"><i></i></div>
					<div class="task-detail-sidebar-item-report-text"><?= Loc::getMessage('TASK_RESULT_SIDEBAR_HINT_MSGVER_1'); ?></div>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="task-detail-sidebar-item task-detail-sidebar-item-reminder">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_REMINDER")?>:</div>
			<div class="task-detail-sidebar-item-value"><span id="task-detail-reminder-add"><?=Loc::getMessage("TASKS_REMINDER_TITLE")?></span></div>
			<?$APPLICATION->IncludeComponent(
				"bitrix:tasks.task.detail.parts",
				"flat",
				array(
					"MODE" => "VIEW TASK",
					"BLOCKS" => array("reminder"),
					"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
					"TEMPLATE_DATA" => array(
						"ITEMS" => array(
							"DATA" => $taskData["SE_REMINDER"],
							"CAN" => ($arResult["CAN"]["TASK"]["SE_REMINDER"] ?? null),
						),
						"TASK_ID" => $taskData["ID"],
						"TASK_DEADLINE" => $taskData["DEADLINE"],
						"AUTO_SYNC" => true,
						"COMPANY_WORKTIME" => array(
							"HOURS" => $arResult["TEMPLATE_DATA"]["AUX_DATA"]["COMPANY_WORKTIME"]["HOURS"]
						)
					),
					"CALENDAR_SETTINGS" => $arResult['CALENDAR_SETTINGS'],
				),
				false,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

		<?if (!empty($stages)):?>
			<div class="task-detail-sidebar-item">
				<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_STAGE")?>:</div>
				<div class="task-detail-sidebar-item-value">
					<div class="task-section task-section-status-wrap" id="tasksStagesWrap">
						<div class="task-section-status-container">
							<div class="task-section-status-container-flex" id="tasksStages"></div>
						</div>
					</div>
				</div>
			</div>
		<?endif;?>

		<?if (
				!$arParams["PUBLIC_MODE"]
				&& \Bitrix\Tasks\Integration\Bizproc\Automation\Factory::canUseAutomation()
				&& \Bitrix\Tasks\Access\TaskAccessController::can($arParams['USER']['ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_ROBOT_EDIT, $taskData["ID"])
		):?>
			<div class="task-detail-sidebar-item task-detail-sidebar-item-robot">
				<div class="task-detail-sidebar-item-title"><?=Loc::getMessage('TASKS_SIDEBAR_AUTOMATION')?>:</div>
				<div class="task-detail-sidebar-item-value">
					<span onclick="BX.SidePanel.Instance.open('/bitrix/components/bitrix/tasks.automation/slider.php?site_id='+BX.message('SITE_ID')+'&amp;project_id=<?=(int)$taskData['GROUP_ID']?>&amp;task_id=<?=(int)$taskData['ID']?>', {cacheable: false, customLeftBoundary: 0, loader: 'bizproc:automation-loader'})"><?=Loc::getMessage('TASKS_SIDEBAR_ROBOTS_1')?></span>
				</div>
			</div>
		<?endif;?>

		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_CREATED_DATE")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["CREATED_DATE"]?></div>
		</div>

		<? if ($taskData["ALLOW_TIME_TRACKING"] === "Y"): ?>
			<div class="task-detail-sidebar-item">
				<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_TIME_SPENT_IN_LOGS")?>:</div>
				<div class="task-detail-sidebar-item-value" id="task-detail-spent-time-<?=$taskData["ID"]?>">
					<?=\Bitrix\Tasks\UI::formatTimeAmount($taskData["TIME_ELAPSED"]);?>
				</div>
			</div>

			<?if($taskData["TIME_ESTIMATE"] > 0):?>
				<div class="task-detail-sidebar-item">
					<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_TIME_ESTIMATE")?>:</div>
					<div class="task-detail-sidebar-item-value" id="task-detail-estimate-time-<?=$taskData["ID"]?>">
						<?=\Bitrix\Tasks\UI::formatTimeAmount($taskData["TIME_ESTIMATE"]);?>
					</div>
				</div>
			<?endif?>
		<?endif?>

		<? if ($templateData["START_DATE_PLAN"]): ?>
		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_START")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["START_DATE_PLAN"]?></div>
		</div>
		<? endif ?>

		<? if ($templateData["END_DATE_PLAN"]): ?>
		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_FINISH")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["END_DATE_PLAN"]?></div>
		</div>
		<? endif ?>

		<div class="task-detail-sidebar-item task-detail-sidebar-item-mark">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_MARK_MSGVER_1")?>:</div>
			<div class="task-detail-sidebar-item-value<?if(!$can["RATE"]):?> task-detail-sidebar-item-readonly<?php endif?>">
				<?php
					if ($taskLimitExceeded)
					{
						$lockClassName = 'tariff-lock';
						$onLockClick =
							"top.BX.UI.InfoHelper.show('"
							. RestrictionUrl::TASK_RATE_SLIDER_URL
							. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks',}});"
						;
						$lockClassStyle = "cursor: pointer;";
				?>
					<span class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>"></span>
				<?php
				}
				?>

				<span class="task-detail-sidebar-item-mark-<?= mb_strtolower($taskData["MARK"] ?? '')?>" id="task-detail-mark">
					<?=Loc::getMessage(($taskData["MARK"] ? "TASKS_MARK_".$taskData["MARK"] : "TASKS_MARK_NONE"))?>
				</span>
			</div>
		</div>

		<div class="task-detail-sidebar-item-videocall" id="task-detail-sidebar-item-videocall"></div>

		<?$APPLICATION->IncludeComponent(
			'bitrix:tasks.widget.member.selector',
			'view',
			array(
				'DATA' => array($taskData["SE_ORIGINATOR"]),
				'READ_ONLY' => true,
				'ROLE' => 'ORIGINATOR',
				'MAX' => 1,
				'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_ORIGINATOR'),
				'PUBLIC_MODE' => $arParams["PUBLIC_MODE"],
				'PATH_TO_USER_PROFILE' => $arParams["PATH_TO_USER_PROFILE"],
				'PATH_TO_TASKS' => $arParams["PATH_TO_TASKS"],
				'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
				'HIDE_IF_EMPTY' => 'N',
				'DISABLE_JS_IF_READ_ONLY' => 'Y',
				'ENTITY_ID' => $taskData["ID"],
				'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
				'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_DIRECTOR
			),
			null,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			'bitrix:tasks.widget.member.selector',
			'view',
			array(
				'DATA' => $taskData["SE_RESPONSIBLE"],
				'READ_ONLY' => ( \Bitrix\Tasks\Access\TaskAccessController::can(\Bitrix\Tasks\Util\User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $taskData['ID']) ? 'N' : 'Y'),
				'ROLE' => 'RESPONSIBLE',
				'FIELD_NAME' => 'SE_RESPONSIBLE',
				'MIN' => 1,
				'MAX' => 1,
				'ENABLE_SYNC' => true,
				'ENTITY_ID' => $taskData["ID"],
				'ENTITY_ROUTE' => 'task',
				'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_RESPONSIBLE'),
				'PUBLIC_MODE' => $arParams["PUBLIC_MODE"],
				'PATH_TO_USER_PROFILE' => $arParams["PATH_TO_USER_PROFILE"],
				'PATH_TO_TASKS' => $arParams["PATH_TO_TASKS"],
				'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
				'HIDE_IF_EMPTY' => 'N',
				'DISABLE_JS_IF_READ_ONLY' => 'Y',
				'CHECK_ABSENCE'=>'Y',
				'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
				'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_RESPONSIBLE
			),
			null,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			'bitrix:tasks.widget.member.selector',
			'view',
			array(
				'DATA' => $taskData["SE_ACCOMPLICE"],
				'READ_ONLY' => ( \Bitrix\Tasks\Access\TaskAccessController::can(\Bitrix\Tasks\Util\User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $taskData['ID']) ? 'N' : 'Y'),
				'ROLE' => 'ACCOMPLICES',
				'FIELD_NAME' => 'SE_ACCOMPLICE',
				'ENABLE_SYNC' => true,
				'ENTITY_ID' => $taskData["ID"],
				'ENTITY_ROUTE' => 'task',
				'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_ACCOMPLICES'),
				'PUBLIC_MODE' => $arParams["PUBLIC_MODE"],
				'PATH_TO_USER_PROFILE' => $arParams["PATH_TO_USER_PROFILE"],
				'PATH_TO_TASKS' => $arParams["PATH_TO_TASKS"],
				'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],
				'HIDE_IF_EMPTY' => ( \Bitrix\Tasks\Access\TaskAccessController::can(\Bitrix\Tasks\Util\User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $taskData['ID']) ? 'N' : 'Y'),
				'DISABLE_JS_IF_READ_ONLY' => 'Y',
				'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
				'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
				'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_ACCOMPLICE
			),
			null,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			'bitrix:tasks.widget.member.selector',
			'view',
			array(
				'TEMPLATE_CONTROLLER_ID' => 'auditor-selector',
				'DATA' => $taskData["SE_AUDITOR"],
				'READ_ONLY' => !$can["EDIT"],
				'ROLE' => 'AUDITORS',
				'FIELD_NAME' => 'SE_AUDITOR',
				'ENABLE_SYNC' => true,
				'ENTITY_ID' => $taskData["ID"],
				'ENTITY_ROUTE' => 'task',
				'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_AUDITORS'),
				'PUBLIC_MODE' => $arParams["PUBLIC_MODE"],
				'PATH_TO_USER_PROFILE' => $arParams["PATH_TO_USER_PROFILE"],
				'PATH_TO_TASKS' => $arParams["PATH_TO_TASKS"],
				'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"],

				'HIDE_IF_EMPTY' => 'N',
				'DISABLE_JS_IF_READ_ONLY' => 'N',
				'HEADER_BUTTON_LABEL_IF_READ_ONLY' => Loc::getMessage(
					$arParams['TEMPLATE_DATA']['I_AM_AUDITOR'] ?
					'TASKS_TTDP_TEMPLATE_USER_VIEW_LEAVE_AUDITOR' :
					'TASKS_TTDP_TEMPLATE_USER_VIEW_ENTER_AUDITOR'
				),
				'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
				'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
				'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_AUDITOR
			),
			null,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?//replication?>
		<?if(!$arParams["PUBLIC_MODE"]):?>

			<?if($taskData["REPLICATE"] != 'N'):?>

				<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_SIDEBAR_REGULAR_TASK")?></div>
				<div class="task-detail-sidebar-info">
					<?$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.replication',
						'view',
						array(
							'DATA' => $taskData["SE_TEMPLATE"]["REPLICATE_PARAMS"],
							'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
							'REPLICATE' => $taskData["REPLICATE"],
							'ENABLE_SYNC' => true,
							'ENTITY_ID' => $taskData["SE_TEMPLATE"]["ID"],
							'PATH_TO_TEMPLATES_TEMPLATE' => $arParams['PATH_TO_TEMPLATES_TEMPLATE'],
							'TEMPLATE_CREATED_BY' => $taskData["SE_TEMPLATE"]['CREATED_BY'],
						),
						null,
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);?>
				</div>

			<?endif?>

			<?if(!empty($taskData["SE_TEMPLATE.SOURCE"])):?>
				<div class="task-detail-sidebar-info template-source">
					<?=Loc::getMessage("TASKS_SIDEBAR_TASK_CREATED_BY_TEMPLATE")?><br />
					(<a href="<?=$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE_SOURCE"]?>" target="_top"><?=Loc::getMessage("TASKS_SIDEBAR_TEMPLATE")?></a>)
				</div>
			<?endif?>
		<?endif?>

		<?php if (!$arParams["PUBLIC_MODE"]): ?>
			<div
				id="tasksEpicTitle"
				class="task-detail-sidebar-info-title <?= $arParams["IS_SCRUM_TASK"] && $canReadGroupTasks ? '' : 'hide' ?>"
			><?=Loc::getMessage("TASKS_TASK_EPIC")?></div>
			<div
				id="tasksEpicContainer"
				class="task-detail-sidebar-info <?= $arParams["IS_SCRUM_TASK"] && $canReadGroupTasks ? '' : 'hide' ?>"
			>
				<div class="task-detail-sidebar-info-tag">
					<?php
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.scrum.epic.selector',
						'',
						[
							'epic' => $arParams['TEMPLATE_DATA']['EPIC'],
							'canEdit' => $can['EDIT'],
							'groupId' => $taskData['GROUP_ID'],
							'taskId' => $taskData['ID'],
						],
						null,
						['HIDE_ICONS' => 'Y']
					);
					?>
				</div>
			</div>
		<? endif ?>

		<? if (!$arParams["PUBLIC_MODE"] && ($can["EDIT"] || $arParams["TEMPLATE_DATA"]["TAGS"] !== "")):?>
			<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_TASK_TAGS")?></div>
			<div class="task-detail-sidebar-info">
				<div class="task-detail-sidebar-info-tag">
					<?php
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.tags.selector',
						'selector',
						[
							'NAME' => 'TAGS',
							'VALUE' => $arParams['TEMPLATE_DATA']['TAGS'],
							'PATH_TO_TASKS' => $arParams['PATH_TO_TASKS'],
							'CAN_EDIT' => $can['EDIT'],
							'GROUP_ID' => $taskData['GROUP_ID'],
							'TASK_ID' => $taskData['ID'],
							'IS_SCRUM_TASK' => $arParams['IS_SCRUM_TASK'],
						],
						null,
						['HIDE_ICONS' => 'Y']
					);
					?>
				</div>
			</div>
		<? endif ?>

	</div>

	<?if($arParams['SHOW_COPY_URL_LINK'] == 'Y'):?>
		<div class="task-iframe-get-link-container">
			<span class="task-iframe-get-link-btn js-id-copy-page-url"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_COPY_CURRENT_URL')?></span>
		</div>
	<?endif?>

</div>

<?php
// получить список встроенных приложений
if(\Bitrix\Main\Loader::includeModule('rest'))
{
	$restPlacementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\CTaskRestService::PLACEMENT_TASK_VIEW_SIDEBAR);
	$restPlacementHandlerList = array_filter($restPlacementHandlerList, function($placement) use ($taskData) {
		$groupIdOption = (string)$placement['OPTIONS']['groupId'];
		if ($groupIdOption == '')
		{
			return true;
		}
		$allowedGroups = array_map('trim', explode(',', $groupIdOption));

		return $taskData['GROUP_ID'] > 0 && in_array($taskData['GROUP_ID'], $allowedGroups, false);
	});

	foreach($restPlacementHandlerList as $app):?>
		<div class="task-detail-sidebar-placement task-sidebar-restapp-<?=$app['APP_ID']?>" id="task-sidebar-restapp-<?=$app['APP_ID']?>">
			<?php
			$placementSid = $APPLICATION->includeComponent(
				'bitrix:app.layout',
				'',
				[
					'ID' => $app['APP_ID'],
					'PLACEMENT' => \CTaskRestService::PLACEMENT_TASK_VIEW_SIDEBAR,
					'PLACEMENT_ID' => $app['ID'],
					"PLACEMENT_OPTIONS" => ['taskId'=>$taskData["ID"]],
					'SET_TITLE' => 'N'
				],
				null,
				array('HIDE_ICONS' => 'Y')
			);
			?>
		</div>
	<?php endforeach;
}
?>

<script>
	new BX.Tasks.Component.TaskViewSidebar({
		taskId: <?=$taskData["ID"]?>,
		groupId: <?= (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0 ?>,
		deadline: "<?=CUtil::JSEscape($taskData["DEADLINE"])?>",
		mark: "<?=CUtil::JSEscape($taskData["MARK"])?>",
		workingTime: {
			start : {
				hours: <?=intval($workingTime["HOURS"]["START"]["H"])?>,
				minutes: <?=intval($workingTime["HOURS"]["START"]["M"])?>
			},
			end : {
				hours: <?=intval($workingTime["HOURS"]["END"]["H"])?>,
				minutes: <?=intval($workingTime["HOURS"]["END"]["M"])?>
			}
		},
		can: <?=CUtil::PhpToJSObject($can)?>,
		allowTimeTracking: <?=CUtil::PhpToJSObject($taskData["ALLOW_TIME_TRACKING"] === "Y")?>,
		messages: {
			emptyDeadline: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_SIDEBAR_DEADLINE_NO"))?>",
		},
		user: <?=CUtil::PhpToJSObject($arParams['USER'])?>,
		iAmAuditor: <?=($arParams['TEMPLATE_DATA']['I_AM_AUDITOR'] ? 'true' : 'false')?>,
		showIntranetControl: <?= ($arParams['TEMPLATE_DATA']['SHOW_INTRANET_CONTROL'] ? 'true' : 'false') ?>,
		pathToTasks: "<?=CUtil::JSEscape($arParams["PATH_TO_TASKS"])?>",
		stageId: <?=$taskData["STAGE_ID"]?>,
		stages: <?= \CUtil::PhpToJSObject(array_values($stages), false, false, true)?>,
		taskLimitExceeded: <?=CUtil::PhpToJSObject($taskLimitExceeded)?>,
		calendarSettings: <?=CUtil::PhpToJSObject($arResult['CALENDAR_SETTINGS'])?>,
		isScrumTask: '<?= $arParams['IS_SCRUM_TASK'] ? 'Y' : 'N' ?>',
		parentId: <?= (int) $taskData['PARENT_ID'] ?>
	});
</script>