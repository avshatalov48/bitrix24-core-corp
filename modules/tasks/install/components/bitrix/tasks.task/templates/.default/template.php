<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Bitrix24\User;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Component\Task\TasksTaskFormState;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load(["ui.design-tokens", "ui.fonts.opensans", "ui.alerts"]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

if (!empty($arResult['ERROR']))
{
	foreach ($arResult['ERROR'] as $error)
	{
		if ($error['TYPE'] === 'FATAL')
		{
			$APPLICATION->IncludeComponent("bitrix:tasks.error", "", [

			]);
			return;
		}
	}
}
?>

<?php
if (
	($arResult['DATA']['FROM_TEMPLATE'] ?? null)
	&& \Bitrix\Tasks\Update\TemplateConverter::isProceed()
):
?>
	<?php
		$APPLICATION->IncludeComponent("bitrix:tasks.interface.emptystate", "", [
			'TITLE' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT_TITLE'),
			'TEXT' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT'),
		]);
	?>
<?php return; endif; ?>

<?php
$templateId = $arResult['TEMPLATE_DATA']['ID'];

if ($arParams["ENABLE_MENU_TOOLBAR"])
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.topmenu',
		'.default',
		[
			'USER_ID' => ($arParams['USER_ID'] ?? null),
			'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
			'SECTION_URL_PREFIX' => '',
			'PATH_TO_GROUP_TASKS' => ($arParams['PATH_TO_GROUP_TASKS'] ?? null),
			'PATH_TO_GROUP_TASKS_TASK' => ($arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null),
			'PATH_TO_GROUP_TASKS_VIEW' => ($arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null),
			'PATH_TO_GROUP_TASKS_REPORT' => ($arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null),
			'PATH_TO_USER_TASKS' => ($arParams['PATH_TO_USER_TASKS'] ?? null),
			'PATH_TO_USER_TASKS_TASK' => ($arParams['PATH_TO_USER_TASKS_TASK'] ?? null),
			'PATH_TO_USER_TASKS_VIEW' => ($arParams['PATH_TO_USER_TASKS_VIEW'] ?? null),
			'PATH_TO_USER_TASKS_REPORT' => ($arParams['PATH_TO_USER_TASKS_REPORT'] ?? null),
			'PATH_TO_USER_TASKS_TEMPLATES' => ($arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null),
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => ($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null),
			'PATH_TO_CONPANY_DEPARTMENT' => ($arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null),
			'PARENT_COMPONENT' => 'tasks.task',
		],
		$component,
		['HIDE_ICONS' => true]
	);

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.filter.buttons',
		'.default',
		[
			'SECTION' => 'EDIT_TASK',
			'TEMPLATES' => ($arResult['AUX_DATA']['TEMPLATE'] ?? null),
			'PATH_TO_TASKS_TASK' => ($arParams['PATH_TO_TASKS_TASK'] ?? null),
			'PATH_TO_TASKS_TEMPLATES' => ($arParams['PATH_TO_TASKS_TEMPLATES'] ?? null),
			'TEMPLATES_TOOLBAR_LABEL' => Loc::getMessage('TASKS_TIP_TEMPLATE_LINK_COPIED_TEMPLATE_BAR_TITLE'),
			'TEMPLATES_TOOLBAR_USE_SLIDER' => 'Y',
		]
	);
}

if (Type::isIterable($arResult['ERROR'] ?? null))
{
	$errors = [];
	$warnings = [];

	foreach ($arResult['ERROR'] as $error)
	{
		if ($error['TYPE'] === 'WARNING')
		{
			$warnings[] = htmlspecialcharsbx($error['MESSAGE']);
		}
		else
		{
			$errors[] = htmlspecialcharsbx($error['MESSAGE']);
		}
	}

	if (!empty($errors))
	{
		?><div class="task-message-label error"><?= implode("<br/>", $errors) ?></div><?php
	}
	if (!empty($warnings))
	{
		?><div class="task-message-label warning"><?= implode("<br/>", $warnings) ?></div><?php
	}
}
?>

<?php if (($arResult['COMPONENT_DATA']['EVENT_TYPE'] ?? null) === 'ADD' && !empty($arResult['DATA']['EVENT_TASK'])):?>
	<div class="task-message-label">
		<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_SAVED');?>.
		<?php if(!isset($arResult['DATA']['TASK']['ID']) || $arResult['DATA']['EVENT_TASK']['ID'] != $arResult['DATA']['TASK']['ID']):?>
			<a href="<?=\Bitrix\Tasks\UI\Task::makeActionUrl($arParams['PATH_TO_TASKS_TASK'], $arResult['DATA']['EVENT_TASK']['ID'], 'view');?>" target="_blank"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_VIEW_TASK');?></a>
		<?php endif?>
	</div>
<?php endif?>

<?php
$taskData = !empty($arResult['DATA']['TASK']) ? $arResult['DATA']['TASK'] : array();
$editMode = $arResult['TEMPLATE_DATA']['EDIT_MODE'];
$taskCan = $taskData['ACTION'];
$state = $arResult['COMPONENT_DATA']['STATE'];
$inputPrefix = $arResult['TEMPLATE_DATA']['INPUT_PREFIX'];
$taskUrlTemplate = str_replace(array('#task_id#', '#action#'), array('{{VALUE}}', 'view'), $arParams['PATH_TO_TASKS_TASK_ORIGINAL']);
$openedBlocks = ($arResult['TEMPLATE_DATA']['BLOCKS']['OPENED'] ?? null);
$blockClasses = ($arResult['TEMPLATE_DATA']['BLOCKS']['CLASSES'] ?? null);
$userProfileUrlTemplate = str_replace('#user_id#', '{{VALUE}}', $arParams['PATH_TO_USER_PROFILE']);
$className = ToLower($arResult['COMPONENT_DATA']['CLASS_NAME']);
$templateData = $arResult['TEMPLATE_DATA'];
$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'];
$taskRecurrentRestrict = $arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'];

if ($taskLimitExceeded || $taskRecurrentRestrict)
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}
?>

<div id="bx-component-scope-<?=$templateId?>" class="task-form tasks">

	<?php //no need to load html when we intend to close the interface?>
	<?php if($arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE']):?>
		<div class="tasks-success-message">
			<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGES_SAVED')?>
		</div>
	<?php else:?>

		<?php if($arParams["ENABLE_FORM"]):?>
			<form action="<?=$arParams['ACTION_URI']?>" method="post" id="task-form-<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['ID'])?>" name="task-form" data-bx-id="task-edit-form">
		<?php else:?>
			<div>
		<?php endif?>

		<?php if(!empty($taskData['DISK_ATTACHED_OBJECT_ALLOW_EDIT'])):?>
			<input type="hidden" name="TASKS_TASK_DISK_ATTACHED_OBJECT_ALLOW_EDIT" value="<?= $taskData['DISK_ATTACHED_OBJECT_ALLOW_EDIT']?>"/>
		<?php endif?>

		<?php if(isset($taskData['SCENARIO'])):?>
			<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SCENARIO_NAME]" value="<?=htmlspecialcharsbx($taskData['SCENARIO'])?>"/>
		<?php endif?>

		<input type="hidden" id="checklistAnalyticsData" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][analyticsData]" value=""/>
		<input type="hidden" id="checklistFromDescription" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][fromDescription]" value=""/>

		<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />
		<input data-bx-id="task-edit-csrf" type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />
		<input type="hidden" name="EMITTER" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['ID'])?>" /> <?php // a page-unique component id that performs the query ?>

		<input type="hidden" name="CALENDAR_EVENT_ID" value="<?= (int)$arResult['COMPONENT_DATA']['CALENDAR_EVENT_ID'] ?>">
		<input type="hidden" name="CALENDAR_EVENT_DATA" value="<?= htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['COMPONENT_DATA']['CALENDAR_EVENT_DATA'])) ?>">

		<input type="hidden" name="SOURCE_POST_ENTITY_TYPE" value="<?= htmlspecialcharsbx($arResult['COMPONENT_DATA']['SOURCE_POST_ENTITY_TYPE']) ?>">
		<input type="hidden" name="SOURCE_ENTITY_TYPE" value="<?= htmlspecialcharsbx($arResult['COMPONENT_DATA']['SOURCE_ENTITY_TYPE']) ?>">
		<input type="hidden" name="SOURCE_ENTITY_ID" value="<?= (int)$arResult['COMPONENT_DATA']['SOURCE_ENTITY_ID'] ?>">

		<input type="hidden" name="FIRST_GRID_TASK_CREATION_TOUR_GUIDE" value="<?= htmlspecialcharsbx($arResult['COMPONENT_DATA']['FIRST_GRID_TASK_CREATION_TOUR_GUIDE']) ?>">

		<?php // todo: move to hit state?>
		<input type="hidden" name="BACKURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['BACKURL']))?>" />
		<input type="hidden" name="CANCELURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['CANCELURL']))?>" />

		<?php if ((int)($taskData['ID'] ?? null)):?>
			<input type="hidden" name="ACTION[0][OPERATION]" value="task.update" />
			<input type="hidden" name="ACTION[0][ARGUMENTS][id]" value="<?=intval($taskData['ID'])?>" />
		<?php else:?>
			<input type="hidden" name="ACTION[0][OPERATION]" value="task.add" />
		<?php endif?>
		<input type="hidden" name="ACTION[0][PARAMETERS][CODE]" value="task_action" />

		<?php // todo: move to hit state?>
		<?php if(Type::isIterable($arResult['COMPONENT_DATA']['DATA_SOURCE'] ?? null)):?>
			<input type="hidden" name="ADDITIONAL[DATA_SOURCE][TYPE]" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['DATA_SOURCE']['TYPE'])?>" />
			<input type="hidden" name="ADDITIONAL[DATA_SOURCE][ID]" value="<?=intval($arResult['COMPONENT_DATA']['DATA_SOURCE']['ID'])?>" />
		<?php endif?>

		<?php if(is_array($arResult['COMPONENT_DATA']['HIT_STATE'])):?>
			<?foreach($arResult['COMPONENT_DATA']['HIT_STATE'] as $field => $value):?>
				<input type="hidden" name="HIT_STATE[<?=htmlspecialcharsbx(str_replace('.', '][', $field))?>]" value="<?=htmlspecialcharsbx($value)?>" />
			<?php endforeach?>
		<?php endif?>

		<?php if (array_key_exists('IM_CHAT_ID', $arResult['COMPONENT_DATA'])): ?>
			<input type="hidden" name="<?= htmlspecialcharsbx($inputPrefix); ?>[IM_CHAT_ID]" value="<?= $arResult['COMPONENT_DATA']['IM_CHAT_ID']; ?>" />
		<?php endif; ?>

		<?php if (array_key_exists('IM_MESSAGE_ID', $arResult['COMPONENT_DATA'])): ?>
			<input type="hidden" name="<?= htmlspecialcharsbx($inputPrefix); ?>[IM_MESSAGE_ID]" value="<?= $arResult['COMPONENT_DATA']['IM_MESSAGE_ID']; ?>" />
		<?php endif; ?>

		<div class="task-info">
			<div class="task-info-panel">
				<div class="task-info-panel-important">
					<input data-bx-id="task-edit-priority-cb" type="checkbox" id="tasks-task-priority-cb" <?=($taskData['PRIORITY'] == CTasks::PRIORITY_HIGH ? 'checked' : '')?>>
					<label for="tasks-task-priority-cb"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PRIORITY')?></label>
					<input data-bx-id="task-edit-priority" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[PRIORITY]" value="<?=intval($taskData['PRIORITY'])?>" />
				</div>
				<div class="task-info-panel-title"><input data-bx-id="task-edit-title" type="text" name="<?=htmlspecialcharsbx($inputPrefix)?>[TITLE]" value="<?=htmlspecialcharsbx($taskData['TITLE'] ?? null)?>" placeholder="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_WHAT_TO_BE_DONE')?>"/></div>
			</div>
			<div data-bx-id="task-edit-editor-container" class="task-info-editor">
				<?php $APPLICATION->IncludeComponent(
					'bitrix:main.post.form',
					'',
					$arResult['AUX_TEMPLATE_DATA']['EDITOR_PARAMETERS'],
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>
		</div>

		<?php $blockName = Manager\Task::SE_PREFIX.'CHECKLIST';?>
		<div data-bx-id="task-edit-checklist" data-block-name="<?=$blockName?>" class="task-openable-block <?=$blockClasses[$blockName]?>">
			<div class="task-checklist">
				<?php
				$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.checklist.new',
					'',
					[
						'ENTITY_ID' => ($taskData['ID'] ?? null),
						'ENTITY_TYPE' => 'TASK',
						'DATA' => ($taskData['SE_CHECKLIST'] ?? null),
						'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
						'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
						'CONVERTED' => $arResult['DATA']['CHECKLIST_CONVERTED'],
						'CAN_ADD_ACCOMPLICE' => !$taskLimitExceeded,
					],
					null,
					['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
				);
				?>
			</div>

			<span data-bx-id="task-edit-chooser" data-target="checklist" class="task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>

		</div>

		<?php $mailUf = $arResult['AUX_DATA']['USER_FIELDS']['UF_MAIL_MESSAGE']; ?>
		<?php if (!empty($mailUf['VALUE'])): ?>
			<div style="margin: 6px 0 15px 0; ">
				<?php $mailUf['FIELD_NAME'] = $inputPrefix.'[UF_MAIL_MESSAGE]'; ?>
				<?php $mailUf['EDIT_IN_LIST'] = 'Y'; // compatibility ?>
				<?php \Bitrix\Tasks\Util\UserField\UI::showEdit($mailUf); ?>
			</div>
		<?php endif ?>

		<div class="task-options task-options-main">

			<div class="task-options-item-destination-wrap">

				<div>
					<div class="task-options-item task-options-item-destination">
						<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_RESPONSIBLE')?></span>
						<div class="task-options-item-open-inner">
							<?php $APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.member.selector',
								'',
								[
									'TEMPLATE_CONTROLLER_ID' => $templateId . '-responsible',
									'DISPLAY' => 'inline',
									'MAX' => ($editMode? 1 : 99999),
									'MIN' => 1,
									'TYPES' => ['USER', 'USER.EXTRANET', 'USER.MAIL'],
									'INPUT_PREFIX' => $inputPrefix . '[SE_RESPONSIBLE]',
									'ATTRIBUTE_PASS' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'],
									'DATA' => $taskData['SE_RESPONSIBLE'],
									'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
									'READ_ONLY' => ( \Bitrix\Tasks\Access\TaskAccessController::can(Util\User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, ($taskData['ID'] ?? null)) ? 'N' : 'Y'),
									'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
									'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_RESPONSIBLE
								],
								false,
								["HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y"]
							) ?>

							<span class="task-dashed-link task-dashed-link-add tasks-additional-block-link inline">
								<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="originator">
									<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ORIGINATOR')?>
								</span>
								<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="accomplice">
									<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ACCOMPLICES')?>
								</span>
								<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="auditor">
									<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_AUDITORS')?>
								</span>
							</span>
							<div data-bx-id="task-edit-absence-message" class="task-absence-message"></div>
						</div>
					</div>
				</div>

				<?php $blockName = Manager\Task::SE_PREFIX.'ORIGINATOR';?>
				<div data-bx-id="task-edit-originator" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">
					<div class="task-options-item task-options-item-destination">
						<span data-bx-id="task-edit-chooser" data-target="originator" class="task-option-fixedbtn"></span>
						<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ORIGINATOR')?></span>
						<div class="task-options-item-open-inner">
							<?php
							$APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.member.selector',
								'',
								array(
									'TEMPLATE_CONTROLLER_ID' => $templateId.'-originator',
									'MAX' => 1,
									'MIN' => 1,
									'MAX_WIDTH' => 786,
									'TYPES' => array('USER', 'USER.EXTRANET'),
									'INPUT_PREFIX' => $inputPrefix.'[SE_ORIGINATOR][ID]',
									'SOLE_INPUT_IF_MAX_1' => 'Y',
									'DATA' => array($taskData['SE_ORIGINATOR']),
									'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
									'READ_ONLY' => !Util\User::isSuper() && (count($taskData['SE_RESPONSIBLE']) > 1) ? 'Y' : 'N',
									'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
									'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_DIRECTOR
								),
								false,
								array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
							);
							?>
						</div>
					</div>

				</div>
				<?php $blockName = Manager\Task::SE_PREFIX.'ACCOMPLICE';?>
				<div data-bx-id="task-edit-accomplice" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">

					<div class="task-options-item task-options-item-destination">
						<span data-bx-id="task-edit-chooser" data-target="accomplice" class="task-option-fixedbtn"></span>
						<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ACCOMPLICES')?></span>
						<?php
							$lockClassName = 'task-options-item-open-inner';
							$onLockClick = '';
							$lockClassStyle = '';
							if ($taskLimitExceeded)
							{
								$lockClassName .= ' tasks-btn-restricted';
								$onLockClick =
									"top.BX.UI.InfoHelper.show('"
									. RestrictionUrl::TASK_LIMIT_OBSERVERS_SLIDER_URL
									. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks',}});"
								;
								$lockClassStyle = "cursor: pointer;";
							}
						?>
						<div class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>">

							<?php
							$APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.member.selector',
								'',
								array(
									'TEMPLATE_CONTROLLER_ID' => $templateId.'-accomplice',
									'MAX_WIDTH' => 786,
									'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
									'INPUT_PREFIX' => $inputPrefix.'[SE_ACCOMPLICE]',
									'ATTRIBUTE_PASS' => array(
										'ID',
										'NAME',
										'LAST_NAME',
										'EMAIL',
									),
									'DATA' => $taskData['SE_ACCOMPLICE'],
									'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
									'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
									'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
									'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_ACCOMPLICE,
									'READ_ONLY' => ( \Bitrix\Tasks\Access\TaskAccessController::can(Util\User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, ($taskData['ID'] ?? null)) ? 'N' : 'Y'),
								),
								false,
								array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
							);
							?>

						</div>
					</div>

				</div>
				<?php $blockName = Manager\Task::SE_PREFIX.'AUDITOR';?>
				<div data-bx-id="task-edit-auditor" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">

					<div class="task-options-item task-options-item-destination">
						<span data-bx-id="task-edit-chooser" data-target="auditor" class="task-option-fixedbtn"></span>
						<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_AUDITORS')?></span>
						<?php
							$lockClassName = 'task-options-item-open-inner';
							$onLockClick = '';
							$lockClassStyle = '';
							if ($taskLimitExceeded)
							{
								$lockClassName .= ' tasks-btn-restricted';
								$onLockClick =
									"top.BX.UI.InfoHelper.show('"
									. RestrictionUrl::TASK_LIMIT_OBSERVERS_SLIDER_URL
									. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks',}});"
								;
								$lockClassStyle = "cursor: pointer;";
							}
						?>
						<div class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>">

							<?php
							$APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.member.selector',
								'',
								array(
									'TEMPLATE_CONTROLLER_ID' => $templateId.'-auditor',
									'MAX_WIDTH' => 786,
									'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
									'INPUT_PREFIX' => $inputPrefix.'[SE_AUDITOR]',
									'ATTRIBUTE_PASS' => array(
										'ID',
										'NAME',
										'LAST_NAME',
										'EMAIL',
									),
									'DATA' => $taskData['SE_AUDITOR'],
									'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
									'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
									'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_AUDITOR,
									'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
								),
								false,
								array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
							);
							?>
						</div>
					</div>

				</div>

			</div>

			<div>
				<?php $disabled = $taskCan['EDIT.PLAN'] ? '' : 'disabled="disabled"';?>
				<div data-bx-id="task-edit-date-plan-manager" class="mode-unit-selected-<?=htmlspecialcharsbx($taskData['DURATION_TYPE'])?> task-options-item task-options-item-open">
					<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DEADLINE')?></span>
						<div class="task-options-item-more">
							<span class="task-options-destination-wrap date">
								<span data-bx-id="dateplanmanager-deadline" class="task-options-inp-container task-options-date">
									<input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
									<span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
									<input data-bx-id="datepicker-value" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[DEADLINE]" value="<?=htmlspecialcharsbx($taskData['DEADLINE'] ?? null)?>" <?=$disabled?> />
								</span>
							</span>
							<span class="task-dashed-link task-dashed-link-terms task-dashed-link-add">
								<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="date-plan"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DATE_PLAN')?></span>
								<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="options"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ATTRIBUTES')?></span>
							</span>
						</div>
						<div class="task-options-item-open-inner task-options-item-open-inner-sh task-options-item-open-inner-sett">
						<?php $blockName = 'DATE_PLAN';?>
						<div data-bx-id="task-edit-date-plan" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?> <?=(($templateData['PARAMS'][1]['VALUE'] ?? null) == 'Y' ? 'disabled-block' : '')?>">
							<div class="task-options-sheduling-block">
								<div class="task-options-divider"></div>
								<div class="task-options-field task-options-field-left">
									<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_START_FROM')?></label>
									<span data-bx-id="dateplanmanager-start-date-plan" class="task-options-inp-container task-options-date">
										<input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
										<span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
										<input data-bx-id="datepicker-value" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[START_DATE_PLAN]" value="<?=htmlspecialcharsbx($taskData['START_DATE_PLAN'] ?? null)?>" <?=$disabled?> />
									</span>
									<div class="tasks-disabling-overlay-form" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PLAN_DATES_DISABLED')?>"></div>
								</div>
								<div class="task-options-field task-options-field-left task-options-field-duration">
									<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DURATION')?></label>
									<span class="task-options-inp-container">
										<input data-bx-id="dateplanmanager-duration" type="text" class="task-options-inp" value="">
									</span>
									<span class="task-dashed-link">
										<span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_DAY?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_DAYS')?></span><span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_HOUR?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_HOURS')?></span><span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_MINUTE?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_MINUTES')?></span>
										<input data-bx-id="dateplanmanager-duration-type-value" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[DURATION_TYPE]" value="<?=htmlspecialcharsbx($taskData['DURATION_TYPE'])?>" <?=$disabled?> />
									</span>
									<div class="tasks-disabling-overlay-form" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PLAN_DATES_DISABLED')?>"></div>
								</div>
								<div class="task-options-field task-options-field-left">
									<label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_END_WITH')?></label>
									<span data-bx-id="dateplanmanager-end-date-plan" class="task-options-inp-container task-options-date">
										<input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
										<span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
										<input data-bx-id="datepicker-value" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[END_DATE_PLAN]" value="<?=htmlspecialcharsbx($taskData['END_DATE_PLAN'] ?? null)?>" <?=$disabled?> />
									</span>
									<div class="tasks-disabling-overlay-form" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PLAN_DATES_DISABLED')?>"></div>
								</div>
								<span data-bx-id="task-edit-chooser" data-target="date-plan" class="task-option-fixedbtn"></span>
							</div>
						</div>

						<?php $blockName = 'OPTIONS';?>
						<div data-bx-id="task-edit-options" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">
							<div class="task-options-settings-block">
								<div class="task-options-divider"></div>
								<div class="task-options-field-container">

									<?php
									$canCustomizeCalendar =
										!($arResult['AUX_DATA']['USER']['IS_EXTRANET_USER'] ?? null)
										&& $arResult['COMPONENT_DATA']['MODULES']['bitrix24']
										&& User::isAdmin($arParams['USER_ID'])
									;
									$options = [
										[
											'CODE' => 'ALLOW_CHANGE_DEADLINE',
											'VALUE' => $taskData['ALLOW_CHANGE_DEADLINE'],
											'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ALLOW_CHANGE_DEADLINE'),
											'HELP_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ALLOW_CHANGE_DEADLINE'),
										],
										[
											'CODE' => 'MATCH_WORK_TIME',
											'VALUE' => $taskData['MATCH_WORK_TIME'],
											'LINK' => $canCustomizeCalendar ? [
												'URL' => Bitrix24::getSettingsURL(),
												'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CUSTOMIZE')
											] : [],
											'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_MATCH_WORK_TIME'),
											'HELP_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_MATCH_WORK_TIME'),
											'HINT_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PLAN_DATES_DISABLED'),
										],
										[
											'CODE' => 'TASK_CONTROL',
											'VALUE' => $taskData['TASK_CONTROL'],
											'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TASK_CONTROL_V2'),
											'HELP_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ALLOW_TASK_CONTROL_V2'),
										],
									];

									if(!$editMode)
									{
										$options[] = array(
											'CODE' => 'ADD_TO_FAVORITE',
											'VALUE' => ($taskData['ADD_TO_FAVORITE'] ?? null),
											'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TO_FAVORITE'),
											'HELP_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ADD_TO_FAVORITE'),
										);
										if($taskCan['DAYPLAN.ADD'])
										{
											$options[] = array(
												'CODE' => 'ADD_TO_TIMEMAN',
												'VALUE' => ($taskData['ADD_TO_TIMEMAN'] ?? null),
												'TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TO_TIMEMAN'),
												'HELP_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ADD_TO_TIMEMAN'),
												'HINT_TEXT' => Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_NO_ADD2TIMEMAN'),
												'DISABLED' => $taskData['SE_ORIGINATOR']['ID'] != Util\User::getId(),
											);
										}
									}
									?>

									<?php $APPLICATION->IncludeComponent(
										'bitrix:tasks.widget.optionbar',
										'',
										array(
											'TEMPLATE_CONTROLLER_ID' => 'options-'.$templateId,
											'INPUT_PREFIX' => $inputPrefix,
											'OPTIONS' => $options,
											'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
										),
										null,
										array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
									);?>

									<?php // todo: add the following to tasks.widget.optionbar?>
									<?php foreach($arResult['TEMPLATE_DATA']['PARAMS'] as $param):?>
										<?php $paramCode = $param['CODE'];?>
										<?php $checked = ($param['VALUE'] ?? null) === 'Y';?>
										<?php if($paramCode === \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED) {continue;} ?>
										<div class="task-options-field">
											<div class="task-options-field-inner">
												<label class="task-field-label"><span class="js-id-hint-help task-options-help tasks-icon-help tasks-help-cursor"><?=$param['HINT']?></span><input data-bx-id="task-edit-flag" data-target="task-param-<?=$paramCode?>" data-flag-name="TASK_PARAM_<?=$paramCode?>" class="task-field-checkbox" type="checkbox" <?=($checked? 'checked' : '')?>><?=$param['TITLE']?></label>
												<input data-bx-id="task-edit-task-param-<?=$paramCode?>" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][VALUE]" value="<?=($checked ? 'Y' : 'N')?>" />
												<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][ID]" value="<?= (int)($param['ID'] ?? null) ?>" />
												<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][CODE]" value="<?= (int)$paramCode ?>" />
											</div>
										</div>
									<?php endforeach?>

								</div>
								<span data-bx-id="task-edit-chooser" data-target="options" class="task-option-fixedbtn"></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div data-bx-id="task-edit-chosen-blocks" class="pinned">

				<?php foreach($arResult['TEMPLATE_DATA']['ADDITIONAL_BLOCKS'] as $blockName):?>

					<?php
					ob_start();
					$blockNameJs = ToLower(str_replace('_', '-', $blockName));

					$itemOpenClass = "";
					$openClassBlocks = array(
						Manager\Task::SE_PREFIX.'PROJECTDEPENDENCE',
						Manager\Task::SE_PREFIX.'TEMPLATE',
						'USER_FIELDS'
					);

					if (in_array($blockName, $openClassBlocks))
					{
						$itemOpenClass = " task-options-item-open";
					}
					?>

					<div data-bx-id="task-edit-<?=$blockNameJs?>-block" data-block-name="<?=$blockName?>" class="pinable-block task-options-item task-options-item-<?=$blockNameJs?><?=$itemOpenClass?>">

						<span data-bx-id="task-edit-chooser" data-target="<?=$blockNameJs?>-block" class="task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>
						<span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_BLOCK_TITLE_'.$blockName)?></span>

						<?php if($blockName == Manager\Task::SE_PREFIX.'REQUIRE_RESULT' && isset($arResult['TEMPLATE_DATA']['PARAMS'][\Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED])):?>
							<?php $param = $arResult['TEMPLATE_DATA']['PARAMS'][\Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED]; ?>
							<?php $paramCode = $param['CODE'];?>
							<?php $checked = (($param['VALUE'] ?? null) === 'Y');?>
							<div class="task-options-item-open-inner">
								<div class="tasks">
									<label class="task-field-label task-field-label-tm">
										<input class="task-options-checkbox" data-bx-id="task-edit-flag" data-target="task-param-<?=$paramCode?>" data-flag-name="TASK_PARAM_<?=$paramCode?>" type="checkbox" <?=($checked? 'checked' : '')?>><?=$param['HINT']?>
										<input data-bx-id="task-edit-task-param-<?=$paramCode?>" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][VALUE]" value="<?=($checked ? 'Y' : 'N')?>" />
										<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][ID]" value="<?= (int)($param['ID'] ?? null) ?>" />
										<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SE_PARAMETER][<?=intval($paramCode)?>][CODE]" value="<?= (int)$paramCode ?>" />
									</label>
								</div>
							</div>
						<?php elseif($blockName == Manager\Task::SE_PREFIX.'PROJECT'):?>

							<div class="task-options-item-open-inner">

								<?php
								$APPLICATION->IncludeComponent(
									'bitrix:tasks.widget.member.selector',
									'',
									array(
										'TEMPLATE_CONTROLLER_ID' => $templateId.'-project',
										'MAX' => 1,
										'MAX_WIDTH' => 786,
										'TYPES' => array('PROJECT'),
										'INPUT_PREFIX' => $inputPrefix.'[SE_PROJECT][ID]',
										'ATTRIBUTE_PASS' => array(
											'ID',
										),
										'DATA' => $taskData['SE_PROJECT'],
										'SOLE_INPUT_IF_MAX_1' => 'Y',
										'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'],
										'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
										'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_AUDITOR
									),
									false,
									array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
								);
								?>

							</div>

							<div class="" style="margin-left:24px; display:inline-block;">
								<a class="js-id-add-project" href="/company/personal/user/<?=$arParams['USER_ID']?>/groups/create/?firstRow=project&refresh=N">
									<?=GetMessage('TASKS_ADD_PROJECT')?>
								</a>
							</div>

						<?php elseif($blockName == 'TIMEMAN'):?>

							<div class="task-options-item-open-inner">

								<?php $APPLICATION->IncludeComponent(
									'bitrix:tasks.widget.timeestimate',
									'',
									array(
										'INPUT_PREFIX' => $inputPrefix,
										'ENTITY_DATA' => $taskData,
									),
									false,
									array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
								);?>

							</div>

						<?php elseif($blockName == Manager\Task::SE_PREFIX.'REMINDER'):?>

							<div class="task-options-item-open-inner">
								<div class="task-options-reminder">
									<?php $APPLICATION->IncludeComponent(
										'bitrix:tasks.task.detail.parts',
										'flat',
										array(
											'MODE' => 'VIEW TASK',
											'BLOCKS' => array('reminder'),
											'TEMPLATE_DATA' => array(
												'ID' => 'reminder-'.$templateId,
												'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
												'COMPANY_WORKTIME' => array(
													'HOURS' => $arResult['AUX_DATA']['COMPANY_WORKTIME']['HOURS']
												),
												'ENABLE_ADD_BUTTON' => 'Y',
												'ITEM_FX' => 'horizontal'
											)
										),
										false,
										array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
									);?>
								</div>
							</div>

						<?php elseif($blockName == Manager\Task::SE_PREFIX.'TEMPLATE'):?>

							<?php
							$template = ($arResult['DATA']['TASK'][$blockName] ?? null);
							$linkToTemplate = str_replace(
								['#action#', '#template_id#'],
								['view', (int)($template['ID'] ?? null)],
								$arParams['PATH_TO_TEMPLATES_TEMPLATE']
							);
							$replicationOn = ($taskData['REPLICATE'] === 'Y');
							?>

							<div data-bx-id="task-edit-replication-block" class="task-options-item-open-inner <?/*=($replicationOn ? '' : 'mode-replication-off')*/?>">
								<label class="task-field-label task-field-label-repeat <?= $taskLimitExceeded || $taskRecurrentRestrict ? 'tasks-btn-restricted' : ''?>">
									<input data-bx-id="task-edit-flag task-edit-flag-replication" data-target="replication" data-flag-name="REPLICATE" class="task-options-checkbox" type="checkbox" <?=($taskData['REPLICATE'] == 'Y' ? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_MAKE_REPLICABLE')?>
									<input data-bx-id="task-edit-replication" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[REPLICATE]" value="<?=htmlspecialcharsbx($taskData['REPLICATE'])?>" />
								</label>
								<div data-bx-id="task-edit-replication-panel" class="task-options-repeat task-openable-block<?=($replicationOn ? '' : ' invisible')?>">

									<?php $APPLICATION->IncludeComponent(
										'bitrix:tasks.widget.replication',
										'',
										array(
											'TEMPLATE_CONTROLLER_ID' => 'replication-'.$templateId,
											'INPUT_PREFIX' => $inputPrefix.'['.$blockName.'][REPLICATE_PARAMS]',
											'DATA' => ($arResult['DATA']['TASK'][$blockName]['REPLICATE_PARAMS'] ?? null),
											'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
											'TEMPLATE_CREATED_BY' => ($arResult['DATA']['TASK'][$blockName]['CREATED_BY'] ?? null),
										),
										false,
										array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
									);?>

									<?php if ((int)($template['ID'] ?? null)):?>
										<div class="task-options-field-fn task-options-field-norm">
											<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_CREATED')?> <a href="<?=htmlspecialcharsbx($linkToTemplate)?>" target="_blank"><?=htmlspecialcharsbx($template['TITLE'])?></a>
										</div>
									<?php else:?>
										<div class="task-options-field-fn task-options-field-norm">
											<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_WILL_BE_CREATED');?>
										</div>
									<?php endif?>
								</div>
								<?php if($editMode && (int)($template['ID'] ?? null)):?>
									<div class="task-options-field-fn task-options-field-norm task-repeat-warning">
										<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_WILL_BE_DELETED');?> <a href="<?=htmlspecialcharsbx($linkToTemplate)?>" target="_blank"><?=htmlspecialcharsbx($template['TITLE'])?></a>
									</div>
								<?php endif?>
							</div>

						<?php elseif($blockName == Manager\Task::SE_PREFIX.'PROJECTDEPENDENCE'):?>

							<div class="task-options-item-open-inner">
								<?php $APPLICATION->IncludeComponent(
									'bitrix:tasks.task.detail.parts',
									'flat',
									array(
										'MODE' => 'VIEW TASK',
										'BLOCKS' => array('projectdependence'),
										'TEMPLATE_DATA' => array(
											'ID' => 'projectdependence-'.$templateId,
											'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
											'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_ORIGINAL']
										),
										'DATA' => [
											'LAST_TASKS' => $arResult['DATA']['LAST_TASKS'],
											'CURRENT_TASKS' => $arResult['DATA']['CURRENT_TASKS']['PREVIOUS']
										]
									),
									false,
									array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
								);?>
							</div>

						<?php elseif($blockName == 'UF_CRM_TASK'):?>

							<div class="task-options-item-open-inner task-edit-crm-block">

								<?php
								$crmUf = $arResult['AUX_DATA']["USER_FIELDS"][$blockName] ?? [];
								$crmUf['FIELD_NAME'] = $inputPrefix.'['.$blockName.']';

								\Bitrix\Tasks\Util\UserField\UI::showEdit($crmUf);
								?>
							</div>

						<?php elseif($blockName == Manager\Task\ParentTask::getCode(true)):?>

							<div class="task-options-item-open-inner">

								<span id="bx-component-scope-parenttask-<?=$templateId?>" class="task-options-destination-wrap task-item-set-empty-true">

									<input data-bx-id="task-edit-parent-input" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[<?=$blockName?>][ID]" value="">

									<span data-bx-id="task-item-set-items">
										<script type="text/html" data-bx-id="task-item-set-item">
											<span data-bx-id="task-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
												<span class="task-options-destination task-options-destination-all-users">
													<a href="<?=htmlspecialcharsbx($taskUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span data-bx-id="task-item-set-item-delete" class="task-option-inp-del" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
												</span>
											</span>
										</script>
									</span>

									<span class="task-inline-selector-item">
										<a href="javascript:void(0)" data-bx-id="task-item-set-open-form" class="feed-add-destination-link">
											<span class="task-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
											<span class="task-item-set-empty-block-on"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGE')?></span>
										</a>
									</span>

									<div data-bx-id="task-item-set-picker-content" class="hidden-soft">
										<?php $APPLICATION->IncludeComponent(
											"bitrix:tasks.task.selector", ".default", array(
											"MULTIPLE" => "N",
											"NAME" => "parenttask",
											"VALUE" => ($taskData["PARENT_ID"] ?? null),
											"LAST_TASKS" => $arResult['DATA']['LAST_TASKS'],
											"CURRENT_TASKS" => $arResult['DATA']['CURRENT_TASKS']['PARENT'],
											"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK_ORIGINAL"],
											"SITE_ID" => SITE_ID,
											"SELECT" => array('ID', 'TITLE', 'STATUS'),
										), null, array("HIDE_ICONS" => "Y")
										);?>
									</div>
								</span>

							</div>

						<?php elseif($blockName == Manager\Task::SE_PREFIX.'TAG'):?>

							<div class="task-options-item-open-inner">

								<?php $APPLICATION->IncludeComponent(
									'bitrix:tasks.widget.tag.selector',
									'selector',
									array(
										'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
										'DATA' => ($taskData['SE_TAG'] ?? null),
										'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData))
											? $taskData['GROUP_ID']
											: 0
										,
										'TASK_ID' => (int)($taskData['ID'] ?? null),
										'IS_SCRUM_TASK' => $arParams['IS_SCRUM_TASK'],
									),
									null,
									array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
								);?>

							</div>

						<?php elseif($blockName == 'EPIC'):?>

							<div class="task-options-item-open-inner">

								<?php
								$APPLICATION->IncludeComponent(
									'bitrix:tasks.scrum.epic.selector',
									'',
									[
										'canEdit' => true,
										'groupId' => (array_key_exists('GROUP_ID', $taskData))
											? $taskData['GROUP_ID']
											: 0
										,
										'taskId' => (int)($taskData['ID'] ?? null),
										'epic' => $arParams['IS_SCRUM_TASK'] ? $arResult['DATA']['SCRUM']['EPIC'] : [],
										'mode' => 'edit',
										'inputName' => $inputPrefix.'['.$blockName.']',
									],
									null,
									['HIDE_ICONS' => 'Y']
								);
								?>

							</div>

						<?php elseif($blockName == 'USER_FIELDS'):?>

							<div class="task-options-item-open-inner">

								<?php $APPLICATION->IncludeComponent(
									"bitrix:tasks.userfield.panel",
									"",
									array(
										'EXCLUDE' => array(
											\Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode(),
											\Bitrix\Tasks\Integration\CRM\UserField::getMainSysUFCode(),
											\Bitrix\Tasks\Integration\Mail\UserField::getMainSysUFCode(),
										),
										'DATA' => $taskData,
										'ENTITY_CODE' => 'TASK',
										'INPUT_PREFIX' => $inputPrefix,
										'RELATED_ENTITIES' => array(
											'TASK_TEMPLATE',
										)
									),
									null,
									array("HIDE_ICONS" => "Y")
								);?>

							</div>

						<?php elseif($blockName == Manager\Task::SE_PREFIX.'RELATEDTASK'):?>

							<div class="task-options-item-open-inner">

								<span id="bx-component-scope-dependson-<?=$templateId?>" class="task-options-destination-wrap task-item-set-empty-true">

									<span data-bx-id="task-item-set-items">
										<script type="text/html" data-bx-id="task-item-set-item">
											<span data-bx-id="task-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
												<span class="task-options-destination task-options-destination-all-users">
													<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[<?=$blockName?>][{{VALUE}}][ID]" value="{{VALUE}}">
													<a href="<?=htmlspecialcharsbx($taskUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a>
													<span data-bx-id="task-item-set-item-delete" class="task-option-inp-del" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
												</span>
											</span>
										</script>
									</span>

									<span class="task-inline-selector-item">
										<a href="javascript:void(0)" data-bx-id="task-item-set-open-form" class="feed-add-destination-link">
											<span class="task-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
											<span class="task-item-set-empty-block-on">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE')?></span>
										</a>
									</span>

									<div data-bx-id="task-item-set-picker-content" class="hidden-soft">
										<?php $APPLICATION->IncludeComponent(
											"bitrix:tasks.task.selector",
											"",
											array(
												"MULTIPLE" => "Y",
												"NAME" => "dependson",
												"VALUE" => ($taskData["DEPENDS_ON"] ?? null),
												"LAST_TASKS" => $arResult['DATA']['LAST_TASKS'],
												"CURRENT_TASKS" => $arResult['DATA']['CURRENT_TASKS']['DEPENDS'],
												"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK_ORIGINAL"],
												"SITE_ID" => SITE_ID,
												"SELECT" => array('ID', 'TITLE', 'STATUS'),
											),
											null,
											array("HIDE_ICONS" => "Y")
										);
										?>
									</div>

									<?php // in case of all items removed, the field should be sent anyway?>
									<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[<?=$blockName?>][]" value="">
								</span>

							</div>

						<?php endif?>

					</div>

					<?php
					$blocks[$blockName] = ob_get_contents();
					ob_end_clean();
					?>

				<?php endforeach?>

				<?php foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
					<?php if(array_key_exists(TasksTaskFormState::O_CHOSEN, $block) && isset($blocks[$blockName])):?>
						<div data-bx-id="task-edit-<?=ToLower(str_replace('_', '-', $blockName))?>-block-place" class="task-edit-block-place">
							<?php if($block[TasksTaskFormState::O_CHOSEN]):?>
								<?=$blocks[$blockName]?>
							<?php endif?>
						</div>
					<?php endif?>
				<?php endforeach?>

			</div>
		</div>

		<?php $displayed = $arResult['TEMPLATE_DATA']['ADDITIONAL_DISPLAYED'];?>
		<?php $opened = $arResult['TEMPLATE_DATA']['ADDITIONAL_OPENED'];?>
		<div data-bx-id="task-edit-additional" class="task-additional-block <?=($displayed ? '' : 'hidden')?>">

			<div data-bx-id="task-edit-additional-header" class="task-additional-alt <?=($opened ? 'opened' : '') ?>">
				<div class="task-additional-alt-more">
					<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADDITIONAL_OPEN')?>
				</div>
				<div class="task-additional-alt-promo">
					<?php foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
						<?php $label = Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_BLOCK_HEADER_'.$blockName);?>
						<?php if((string) $label != ''):?>
							<span class="task-additional-alt-promo-text"><?=htmlspecialcharsbx($label);?></span>
						<?php endif?>
					<?php endforeach?>
				</div>
			</div>

			<div data-bx-id="task-edit-unchosen-blocks" class="task-options task-options-more task-openable-block <?=($opened ? '' : 'invisible')?>">

				<?php foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
					<?php if(array_key_exists(TasksTaskFormState::O_CHOSEN, $block) && isset($blocks[$blockName])):?>
						<?php
							$visibility = (
							(
								array_key_exists(TasksTaskFormState::O_HIDDEN, $block)
								&& $block[TasksTaskFormState::O_HIDDEN] === true
							) ?
								'hidden'
								: ''
							);
						?>
						<div
							data-bx-id="task-edit-<?=ToLower(str_replace('_', '-', $blockName))?>-block-place"
							data-block-name="<?=$blockName?>"
							data-group-id="<?=(array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0?>"
							class="task-edit-block-place <?= $visibility ?>"
						>
							<?php if(!$block[TasksTaskFormState::O_CHOSEN]):?>
								<?=$blocks[$blockName]?>
							<?php endif?>
						</div>
					<?php endif?>
				<?php endforeach?>

			</div>
		</div>

		<?php if($arParams['ENABLE_FOOTER']):?>

			<div data-bx-id="task-edit-footer" class="webform-buttons task-edit-footer-fixed pinable-block <?=($arResult['TEMPLATE_DATA']['FOOTER_PINNED'] ? 'pinned' : '')?>">

				<div class="tasks-form-footer-container">

					<?php $satChecked = (
						($request['ADDITIONAL']['SAVE_AS_TEMPLATE'] ?? null) === 'Y'
						|| $taskData['REPLICATE'] === 'Y'
					);?>
					<?php $satDisabled = $taskData['REPLICATE'] == 'Y';?>

					<?php if (\Bitrix\Tasks\Access\TemplateAccessController::can($arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_CREATE)):
						$lockClassName = 'task-edit-add-template-container';
						$onLockClick = '';
						$lockClassStyle = '';
						if ($taskLimitExceeded)
						{
							$lockClassName .= ' tasks-btn-restricted';
							$onLockClick =
								"top.BX.UI.InfoHelper.show('"
								. RestrictionUrl::TEMPLATE_LIMIT_SLIDER_URL
								. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks',}});"
							;
							$lockClassStyle = "cursor: pointer;";
						}
						?>
						<div class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>">
						<label class="task-edit-add-template-label"><input type="checkbox" class="task-edit-add-template-checkbox" data-bx-id="task-edit-flag task-edit-flag-save-as-template" data-target="task-param-save-as-template" data-flag-name="SAVE_AS_TEMPLATE" <?=($satDisabled? 'disabled' : '')?> <?=($satChecked? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_SAVE_AS_TEMPLATE')?></label>
						<input data-bx-id="task-edit-task-param-save-as-template" type="hidden" name="ADDITIONAL[SAVE_AS_TEMPLATE]" value="<?=($satChecked ? 'Y' : 'N')?>" />
					</div>
					<?php endif; ?>

					<?php if($arParams['ENABLE_FOOTER_UNPIN']):?>
						<span data-bx-id="task-edit-pin-footer" class="task-option-fixedbtn"></span>
					<?php endif?>

					<button data-bx-id="task-edit-submit" class="ui-btn ui-btn-success">
						<?php if ((int)($taskData['ID'] ?? null)):?>
							<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_SAVE_TASK')?>
						<?php else:?>
							<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TASK')?> <span>(<span data-bx-id="task-edit-cmd">Ctrl</span>+Enter)</span>
						<?php endif?>
					</button><?

					if (!(int)($taskData['ID'] ?? null)):
						?><button data-bx-id="task-edit-submit" name="STAY_AT_PAGE" value="1" class="ui-btn ui-btn-light-border">
							<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TASK_AND_OPEN_AGAIN')?>
						</button><?php
					endif;

					?><a
						class="ui-btn ui-btn-link"
						href="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['CANCELURL']))?>"
						data-bx-id="task-edit-cancel-button"
						data-slider-ignore-autobinding="true"
					><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CANCEL')?></a>

				</div>
			</div>

		<?php endif?>

		<input type="hidden" name="ACTION[1][OPERATION]" value="<?=htmlspecialcharsbx($className)?>.setstate" />
		<div data-bx-id="task-edit-state" class="task-edit-state-fixed">
			<script data-bx-id="task-edit-state-block" type="text/html">
				<input type="hidden" name="ACTION[1][ARGUMENTS][state][BLOCKS][{{NAME}}][{{TYPE}}]" value="{{VALUE}}" />
			</script>
			<script data-bx-id="task-edit-state-flag" type="text/html">
				<input type="hidden" name="ACTION[1][ARGUMENTS][state][FLAGS][{{NAME}}]" value="{{VALUE}}" />
			</script>
		</div>

		<?php if($arParams["ENABLE_FORM"]):?>
			</form>
		<?php else:?>
			</div>
		<?php endif?>
	<?php endif?>
</div>

<script>

	var options = <?=\Bitrix\Tasks\UI::toJSObject(array(
		'id' => $arResult['TEMPLATE_DATA']['ID'],

		// be careful here, do not "publish" entire data without filtering
		'data' => array(
			'TASK' => $arResult['DATA']['TASK'],
			'EVENT_TASK' => ($arResult['DATA']['EVENT_TASK'] ?? null),
		),
		'can' => array('TASK' => $arResult['CAN']['TASK']),
		'template' => $arResult['TEMPLATE_DATA'],
		'state' => $state,
		'componentData' => array(
			'EVENT_TYPE' => ($arResult['COMPONENT_DATA']['EVENT_TYPE'] ?? null),
			'EVENT_OPTIONS' => ($arResult['COMPONENT_DATA']['EVENT_OPTIONS'] ?? null),
			'MODULES' => $arResult['COMPONENT_DATA']['MODULES'],
		),
		'calendarSettings' => $arResult['CALENDAR_SETTINGS'],
		'auxData' => array( // currently no more, no less
			'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
			'HINT_STATE' => $arResult['AUX_DATA']['HINT_STATE'],
			'USER' => $arResult['AUX_DATA']['USER'],
			'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
			'TASK_RECURRENT_RESTRICT' => $taskRecurrentRestrict
		),
		'componentId' => $arResult['COMPONENT_DATA']['ID'],
		'doInit' => !$arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE'],
		'cancelActionIsEvent' => !!$arParams['CANCEL_ACTION_IS_EVENT'],
	))?>;

	<?php /*
	todo: move php function tasksRenderJSON() to javascript, use CUtil::PhpToJSObject() here for EVENT_TASK, and then remove the following code
	*/?>
	<?php if(Type::isIterable($arResult['DATA']['EVENT_TASK'] ?? null)):?>
		<?php CJSCore::Init('CJSTask'); // ONLY to make BX.CJSTask.fixWin() available?>
		options.data.EVENT_TASK_UGLY = <?php tasksRenderJSON(
			$arResult['DATA']['EVENT_TASK_SAFE'],
			intval($arResult['DATA']['EVENT_TASK']['CHILDREN_COUNT']),
			array(
				'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_ORIGINAL']
			),
			true,
			true,
			true,
			CSite::GetNameFormat(false)
		)?>;
	<?php endif?>

	new BX.Tasks.Component.Task(options);

	if (window.B24) {
		B24.updateCounters({"tasks_total": <?=(int)CUserCounter::GetValue($USER->GetID(), 'tasks_total')?>});
	}
</script>