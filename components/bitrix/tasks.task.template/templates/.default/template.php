<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Bitrix24\User;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Bitrix24;

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
$APPLICATION->SetAdditionalCSS('/bitrix/js/intranet/intranet-common.css');

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '').'no-all-paddings'
);

if ($arParams['ENABLE_MENU_TOOLBAR'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.topmenu',
		'',
		[
			'USER_ID' => $arParams['USER_ID'],
			'GROUP_ID' => $arParams['GROUP_ID'],
			'SECTION_URL_PREFIX' => '',
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
			'SHOW_SECTION_TEMPLATES' => 'Y',
			'MARK_TEMPLATES' => 'Y',
			'MARK_ACTIVE_ROLE' => 'N',
		],
		$component,
		['HIDE_ICONS' => true]
	);
}
?>

<?php if (\Bitrix\Tasks\Update\TemplateConverter::isProceed()): ?>
	<?php
		$APPLICATION->IncludeComponent("bitrix:tasks.interface.emptystate", "", [
			'TITLE' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT_TITLE'),
			'TEXT' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT'),
		]);
	?>
<?php else: ?>


	<?php $this->SetViewTarget('pagetitle', 100); ?>
		<div class="task-list-toolbar">
			<div class="task-list-toolbar-actions">
				<button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting webform-cogwheel" id="templateEditPopupMenuOptions"></button>
			</div>
		</div>
	<?php $this->EndViewTarget(); ?>

	<?$helper->displayFatals();?>
	<?if(!$helper->checkHasFatals()):?>
		<?$helper->displayWarnings();?>

		<?/*
		<?if($arResult['COMPONENT_DATA']['EVENT_TYPE'] == 'ADD' && !empty($arResult['DATA']['EVENT_TASK'])):?>
			<div class="task-message-label">
				<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_SAVED');?>.
				<?if($arResult['DATA']['EVENT_TASK']['ID'] != $arResult['DATA']['TASK']['ID']):?>
					<a href="<?=\Bitrix\Tasks\UI\Task::makeActionUrl($arParams['PATH_TO_TASKS_TASK'], $arResult['DATA']['EVENT_TASK']['ID'], 'view');?>" target="_blank"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_VIEW_TASK');?></a>
				<?endif?>
			</div>
		<?endif?>
		*/?>

		<?
		/** @var \Bitrix\Tasks\Item\Task\Template $template */
		$template = $arResult['ITEM'];
		$inputPrefix = 'ACTION[0][ARGUMENTS][data]';
		$editMode = !!$template->getId();
		$blockData = $arResult['TEMPLATE_DATA']['BLOCKS'];
		$jsData = $arResult['JS_DATA'];

		$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'];
		$templateSubtaskLimitExceeded = $arResult['AUX_DATA']['TEMPLATE_SUBTASK_LIMIT_EXCEEDED'];
		$templateTaskRecurrentLimitExceeded = $arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'];
		$lockClass = '';
		if (
			$taskLimitExceeded
			|| $templateSubtaskLimitExceeded
			|| $templateTaskRecurrentLimitExceeded
		)
		{
			$lockClass = 'tasks-btn-restricted';
			$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
		}

		$taskUrlTemplate = str_replace(
			['#task_id#', '#action#'],
			['{{VALUE}}', 'view'],
			($arParams['PATH_TO_TASKS_TASK_ORIGINAL'] ?? '')
		);
		$userProfileUrlTemplate = str_replace('#user_id#', '{{VALUE}}', $arParams['PATH_TO_USER_PROFILE']);
		?>

		<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?//no need to load html when we intend to close the interface?>
		<?if($arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE']):?>
			<div class="tasks-success-message">
				<?//=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_CHANGES_SAVED')?>
			</div>
		<?else:?>

			<?if($arParams["ENABLE_FORM"]):?>
				<form action="<?=$arParams['ACTION_URI']?>" method="post" id="task-form-<?=htmlspecialcharsbx($helper->getId())?>" name="task-form" class="js-id-task-template-edit-form">
			<?else:?>
				<div>
			<?endif?>

			<input type="hidden" id="checklistAnalyticsData" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][analyticsData]" value=""/>
			<input type="hidden" id="checklistFromDescription" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][fromDescription]" value=""/>

			<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />

			<?php if(isset($arResult['TEMPLATE_DATA']['SCENARIO'])):?>
				<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SCENARIO_NAME]" value="<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['SCENARIO'])?>" />
			<?php endif?>

			<?php if($_REQUEST['IFRAME']):?>
			<input type="hidden" name="IFRAME" value="<?=$_REQUEST['IFRAME']=='Y'?'Y':'N'?>" />
			<?php endif?>

			<input class="js-id-task-template-edit-csrf" type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />
			<input type="hidden" name="EMITTER" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['ID'])?>" /> <?// a page-unique component id that performs the query ?>

			<?// todo: move to hit state?>
			<input type="hidden" name="BACKURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['BACKURL']))?>" />
			<input type="hidden" name="CANCELURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['CANCELURL']))?>" />

			<?if(intval($template['ID'])):?>
				<input type="hidden" name="ACTION[0][OPERATION]" value="task.template.update" />
				<input type="hidden" name="ACTION[0][ARGUMENTS][id]" value="<?=$template->getId()?>" />
			<?else:?>
				<input type="hidden" name="ACTION[0][OPERATION]" value="task.template.add" />
			<?endif?>
			<input type="hidden" name="ACTION[0][PARAMETERS][CODE]" value="task_template_action" />

			<?// todo: move to hit state?>
			<?if(Type::isIterable($arResult['COMPONENT_DATA']['DATA_SOURCE'] ?? null)):?>
				<input type="hidden" name="ADDITIONAL[DATA_SOURCE][TYPE]" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['DATA_SOURCE']['TYPE'])?>" />
				<input type="hidden" name="ADDITIONAL[DATA_SOURCE][ID]" value="<?=intval($arResult['COMPONENT_DATA']['DATA_SOURCE']['ID'])?>" />
			<?endif?>

			<?if (is_array($arResult['COMPONENT_DATA']['HIT_STATE'] ?? null)):?>
				<?foreach($arResult['COMPONENT_DATA']['HIT_STATE'] as $field => $value):?>
					<input type="hidden" name="HIT_STATE[<?=htmlspecialcharsbx(str_replace('.', '][', $field))?>]" value="<?=htmlspecialcharsbx($value)?>" />
				<?endforeach?>
			<?endif?>

			<?$blocks = array();?>

			<?//////// TOP ///////////////////////////////////////////////?>

			<?ob_start();?>
			<input class="js-id-task-template-edit-title" type="text" name="<?=$inputPrefix?>[TITLE]" value="<?=htmlspecialcharsbx($template['TITLE'])?>" placeholder="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_WHAT_TO_BE_DONE')?>"/>
			<?
			$blocks['HEAD_TOP_LEFT'] = array(
				'HTML' => ob_get_clean(),
			);

			ob_start();
			?>
			<input
				class="js-id-task-template-edit-flag"
				type="checkbox"
				id="tasks-task-priority-cb" <?=($template['PRIORITY'] == CTasks::PRIORITY_HIGH ? 'checked' : '')?>
				data-target="priority"
				data-flag-name="PRIORITY"
				data-yes-value="<?=CTasks::PRIORITY_HIGH?>"
				data-no-value="<?=CTasks::PRIORITY_LOW?>"
			/>
			<label for="tasks-task-priority-cb"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PRIORITY')?></label>
			<input class="js-id-task-template-edit-priority" type="hidden" name="<?=$inputPrefix?>[PRIORITY]" value="<?=intval($template['PRIORITY'])?>" />
			<?
			$blocks['HEAD_TOP_RIGHT'] = array(
				'HTML' => ob_get_clean(),
			);

			$blocks['HEAD'] = array(
				'HTML' => \Bitrix\Tasks\UI\Editor::getHTML(array(
					'ID' => $helper->getId(),
					'ENTITY_ID' => $template->getId(),
					'ENTITY_DATA' => $template,
					'CONTENT' => $template['DESCRIPTION'],
					'BBCODE_MODE' => $template['DESCRIPTION_IN_BBCODE'] == 'Y',
					'EXTRA_BUTTONS' => array(
						'Checklist' => array(
							'HTML' => '<span class="js-id-wfr-edit-form-toggler tasks-task-mpf-link" data-target="se_checklist">'.Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_CHECKLIST').'</span>'
						),
						'ToCheckList' => [
							'HTML' => '<span class="js-id-task-template-edit-to-checklist tasks-task-mpf-link">'.Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TO_CHECKLIST').'</span>',
						],
					),
					'USER_NAME_FORMAT' => $helper->findParameterValue('NAME_TEMPLATE'),
					'USER_FIELDS' => $template->getUserFieldScheme(true, array(
						'COLLECTION_VALUE_TO_ARRAY' => true,
					))->toArray(),
					'INPUT_PREFIX' => $inputPrefix,
				)),
			);

			//////// HEAD ///////////////////////////////////////////////

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.checklist.new',
				'',
				array(
					'ENTITY_ID' => $template->getId(),
					'ENTITY_TYPE' => 'TEMPLATE',
					'DATA' => $arResult['TEMPLATE_DATA']['SE_CHECKLIST'],
					'INPUT_PREFIX' => $inputPrefix . '[SE_CHECKLIST]',
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'CONVERTED' => $arResult['CHECKLIST_CONVERTED'],
					'CAN_ADD_ACCOMPLICE' => true,
				),
				$helper->getComponent(),
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);
			$blocks['HEAD_BOTTOM'][] = array(
				'CODE' => 'SE_CHECKLIST',
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => $blockData['SE_CHECKLIST']['FILLED'],
			);

			//////// STATIC ///////////////////////////////////////////////

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				array(
					'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-responsible',
					'DISPLAY' => 'inline',
					'MIN' => 1,
					'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
					'INPUT_PREFIX' => $inputPrefix.'[RESPONSIBLES]',
					'ATTRIBUTE_PASS' => array(
						'ID',
						'NAME',
						'LAST_NAME',
						'EMAIL',
					),
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_RESPONSIBLE'],
					'READ_ONLY' => $template['TPARAM_TYPE'] == 1,
					'CONTEXT' => 'template',
				),
				$helper->getComponent(),
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);
			/*<input class="js-id-task-template-edit-multitask-flag" type="hidden" name="<?=$inputPrefix?>[MULTITASK]" value="<?=htmlspecialcharsbx($template['MULTITASK'])?>" />*/
			$blocks['STATIC']['MAIN'] = array(
				'CODE' => 'SE_RESPONSIBLE',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_RESPONSIBLE'),
				'HTML' => ob_get_clean(),
				'TOGGLE' => array(
					array(
						'TARGET' => 'SE_ORIGINATOR',
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ORIGINATOR'),
					),
					array(
						'TARGET' => 'SE_ACCOMPLICE',
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ACCOMPLICES'),
					),
					array(
						'TARGET' => 'SE_AUDITOR',
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_AUDITORS'),
					),
				),
				'IS_PINABLE' => false,
				'FILLED' => true,
			);

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				array(
					'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-originator',
					'MAX' => 1,
					'MIN' => 1,
					'MAX_WIDTH' => 786,
					'TYPES' => array('USER', 'USER.EXTRANET'),
					'INPUT_PREFIX' => $inputPrefix.'[CREATED_BY]',
					'SOLE_INPUT_IF_MAX_1' => 'Y',
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ORIGINATOR'],
					'CONTEXT' => 'template',
				),
				$helper->getComponent(),
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);
			$blocks['STATIC'][] = array(
				'CODE' => 'SE_ORIGINATOR',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ORIGINATOR'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => true,
				'FILLED' => $blockData['SE_ORIGINATOR']['FILLED'], // show only if this is not a current user
			);

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				array(
					'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-accomplice',
					'MAX_WIDTH' => 786,
					'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
					'INPUT_PREFIX' => $inputPrefix.'[ACCOMPLICES]',
					'ATTRIBUTE_PASS' => array(
						'ID',
						'NAME',
						'LAST_NAME',
						'EMAIL',
					),
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ACCOMPLICE'],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
					'CONTEXT' => 'template',
				),
				$helper->getComponent(),
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);
			$blocks['STATIC'][] = array(
				'CODE' => 'SE_ACCOMPLICE',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ACCOMPLICES'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => true,
				'FILLED' => $blockData['SE_ACCOMPLICE']['FILLED'],
				'RESTRICTED' => $taskLimitExceeded,
			);

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				array(
					'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-auditor',
					'MAX_WIDTH' => 786,
					'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
					'INPUT_PREFIX' => $inputPrefix.'[AUDITORS]',
					'ATTRIBUTE_PASS' => array(
						'ID',
						'NAME',
						'LAST_NAME',
						'EMAIL',
					),
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_AUDITOR'],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
					'CONTEXT' => 'template',
				),
				$helper->getComponent(),
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);
			$blocks['STATIC'][] = array(
				'CODE' => 'SE_AUDITOR',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_AUDITORS'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => true,
				'FILLED' => $blockData['SE_AUDITOR']['FILLED'],
				'RESTRICTED' => $taskLimitExceeded,
			);

			ob_start();
			?>

			<div class="js-id-task-template-edit-deadline task-options-field task-options-field-left task-options-field-duration task-options-field-duration-deadline mode-unit-selected-<?=$jsData['deadline']['UNIT']?>">
				<span class="task-options-inp-container">
					<input type="text" class="js-id-dateplanmanager-display task-options-inp" value="<?=$jsData['deadline']['VALUE']?>">
					<input type="hidden" class="js-id-dateplanmanager-value" name="<?=$inputPrefix?>[DEADLINE_AFTER]" value="<?=intval($template['DEADLINE_AFTER'])?>" />
				</span>
				<span class="task-dashed-link">
					<span data-unit="days" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_DAYS')?></span><span data-unit="hours" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_HOURS')?></span><span data-unit="mins" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_MINUTES')?></span>
				</span>
				<span class="tasks-deadline-label"><nobr><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_AFTER_CREATE');?></nobr></span>
			</div>

			<?
			$dates = array(
				'CODE' => 'DATES',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_DEADLINE_AFTER'),
				'HTML' => ob_get_clean(),
				'SUB' => array(),
				'TOGGLE' => array(
					array(
						'TARGET' => 'DATE_PLAN',
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_DATE_PLAN'),
					),
					array(
						'TARGET' => 'OPTIONS',
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ATTRIBUTES'),
					),
				),
				'IS_PINABLE' => false,
				'FILLED' => true,
			);

			ob_start();
			?>
			<div class="js-id-task-template-edit-start-date task-options-field task-options-field-left task-options-field-duration mode-unit-selected-<?=$jsData['startDate']['UNIT']?>">
				<label class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_START_AFTER')?></label>
				<span class="task-options-inp-container">
					<input type="text" class="js-id-dateplanmanager-display task-options-inp" value="<?=$jsData['startDate']['VALUE']?>">
					<input type="hidden" class="js-id-dateplanmanager-value" name="<?=$inputPrefix?>[START_DATE_PLAN_AFTER]" value="<?=intval($template['START_DATE_PLAN_AFTER'])?>" />
				</span>
				<span class="task-dashed-link">
					<span data-unit="days" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_DAYS')?></span><span data-unit="hours" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_HOURS')?></span><span data-unit="mins" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_MINUTES')?></span>
				</span>
			</div>
			<div class="js-id-task-template-edit-duration task-options-field task-options-field-left task-options-field-duration mode-unit-selected-<?=$jsData['duration']['UNIT']?>">
				<label class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_DURATION')?></label>
				<span class="task-options-inp-container">
					<input type="text" class="js-id-dateplanmanager-display task-options-inp" value="<?=$jsData['duration']['VALUE']?>">
				</span>
				<span class="task-dashed-link">
					<span data-unit="days" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_DAYS')?></span><span data-unit="hours" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_HOURS')?></span><span data-unit="mins" class="js-id-dateplanmanager-unit-setter task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_MINUTES')?></span>
				</span>
			</div>
			<input class="js-id-task-template-edit-end-date-input" type="hidden" name="<?=$inputPrefix?>[END_DATE_PLAN_AFTER]" value="<?=intval($template['END_DATE_PLAN_AFTER'])?>" />
			<?
			$dates['SUB'][] = array(
				'CODE' => 'DATE_PLAN',
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => true,
				'FILLED' => $blockData['DATE_PLAN']['FILLED'],
			);

			ob_start();
			?>
			<div class="task-options-field-container">

				<?$typeNewEnabled = !$template->getId() && !$template['BASE_TEMPLATE_ID'] && $template['REPLICATE'] != 'Y';?>
				<?php $canCustomizeCalendar =
					!($arResult['AUX_DATA']['USER']['IS_EXTRANET_USER'] ?? null)
					&& $arResult['COMPONENT_DATA']['MODULES']['bitrix24']
					&& User::isAdmin($arParams['USER_ID'])
				; ?>

				<?
				$options = array(
					array(
						'CODE' => 'ALLOW_CHANGE_DEADLINE',
						'VALUE' => $template['ALLOW_CHANGE_DEADLINE'],
						'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ALLOW_CHANGE_DEADLINE'),
						'HELP_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_HINT_ALLOW_CHANGE_DEADLINE'),
					),
					array(
						'CODE' => 'MATCH_WORK_TIME',
						'VALUE' => $template['MATCH_WORK_TIME'],
						'LINK' => $canCustomizeCalendar ? array(
							'URL' => Bitrix24::getSettingsURL(),
							'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_CUSTOMIZE')
						) : array(),
						'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_MATCH_WORK_TIME'),
						'HELP_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_HINT_MATCH_WORK_TIME'),
					),
					array(
						'CODE' => 'TASK_CONTROL',
						'VALUE' => $template['TASK_CONTROL'],
						'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TASK_CONTROL_V2'),
						'HELP_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_HINT_TASK_CONTROL_V2'),
					),
				);

				if($arResult['COMPONENT_DATA']['MODULES']['bitrix24'])
				{
					$options[] = array(
						'CODE' => 'TPARAM_TYPE',
						'YES_VALUE' => '1',
						'NO_VALUE' => '0',
						'VALUE' => $template['TPARAM_TYPE'],
						'DISABLED' => !$typeNewEnabled,
						'HINT_ENABLED' => !$typeNewEnabled,
						'HINT_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_NO_TYPE_NEW_TEMPLATE_NOTICE'),
						'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TPARAM_TYPE'),
						'HELP_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_HINT_TPARAM_TYPE'),
					);
				}

				$users = Util::getOption('propagate_to_sub_templates_users');
				if ($users && in_array($arParams['USER_ID'], unserialize($users, ['allowed_classes' => false])))
				{
					$templates = Util::getOption('propagate_to_sub_templates');
					if ($templates)
					{
						$templates = unserialize($templates, ['allowed_classes' => false]);
						$value = (in_array($arParams['ID'], $templates));

						$options[] = [
							'CODE' => 'PROPAGATE_TO_SUB_TEMPLATES',
							'YES_VALUE' => '1',
							'NO_VALUE' => '0',
							'VALUE' => $value,
							'TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PROPAGATE_TO_SUB_TEMPLATES'),
							'HELP_TEXT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_HINT_PROPAGATE_TO_SUB_TEMPLATES'),
						];
					}
				}
				?>

				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.optionbar',
					'',
					array(
						'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-options',
						'INPUT_PREFIX' => $inputPrefix,
						'OPTIONS' => $options,
						'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
					),
					$helper->getComponent(),
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>

			</div>
			<?
			$dates['SUB'][] = array(
				'CODE' => 'OPTIONS',
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => true,
				'FILLED' => false, // options always closed
			);

			$blocks['STATIC'][] = $dates;

			ob_start();
			$replicationEnabled = !$template['BASE_TEMPLATE_ID'] && $template['TPARAM_TYPE'] != 1;
			$lockClassStyle = '';
			if (!empty($lockClass))
			{
				$lockClassStyle = 'cursor: pointer;';
			}
			?>
					<label class="js-id-hint-help js-id-task-template-edit-hint-replication task-field-label task-field-label-repeat <?= $lockClass ?>"
						   data-hint-enabled="<?=!intval($replicationEnabled)?>"
						   data-hint-text="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_NO_REPLICATION_TEMPLATE_NOTICE', array(
							   '#TPARAM_FOR_NEW_USER#' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TPARAM_TYPE')
						   		)
						   )?>"
						   style="<?=$lockClassStyle?>"
					>
						<input class="js-id-task-template-edit-flag js-id-task-template-edit-flag-replication task-options-checkbox"
							   data-target="replication"
							   data-flag-name="REPLICATE"
							   type="checkbox" <?=($template['REPLICATE'] == 'Y' ? 'checked' : '')?>
							<?=($replicationEnabled ? '' : 'disabled')?>
						><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_MAKE_REPLICABLE')?>
						<input class="js-id-task-template-edit-replication"
							   type="hidden"
							   name="<?=$inputPrefix?>[REPLICATE]"
							   value="<?=htmlspecialcharsbx($template['REPLICATE'])?>"
						/>
					</label>
					<div class="js-id-task-template-edit-replication-panel task-options-repeat task-openable-block<?=($template['REPLICATE'] == 'Y' ? '' : ' invisible')?>">

						<?$APPLICATION->IncludeComponent(
							'bitrix:tasks.widget.replication',
							'',
							array(
								'INPUT_PREFIX' => $inputPrefix.'[REPLICATE_PARAMS]',
								'DATA' => $template['REPLICATE_PARAMS'],
								'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
								'TEMPLATE_CREATED_BY' => $template['CREATED_BY'],
							),
							$helper->getComponent(),
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>

					</div>
					<?
					$blockCode = 'REPLICATION';
					$blocks['STATIC'][] = array(
						'CODE' => $blockCode,
						'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_TITLE_'.$blockCode.'_2'),
						'TITLE_SHORT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_HEADER_'.$blockCode).'2',
						'HTML' => ob_get_clean(),
						'IS_PINABLE' => false,
						'FILLED' => true//$blockData[$blockCode]['FILLED'],
					);









			//////// DYNAMIC ///////////////////////////////////////////////

			$DYNAMICBlocks = array(
				'PROJECT', 'CRM', 'USER_FIELDS', 'TIME_MANAGER', 'TAG', 'RELATED_TASK', 'PARENT', 'ACCESS_TEMPLATE'
			);

			foreach($DYNAMICBlocks as $blockCode)
			{
				ob_start();
				if($blockCode == 'PROJECT')
				{
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.member.selector',
						'',
						array(
							'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-project',
							'MAX' => 1,
							'MAX_WIDTH' => 786,
							'TYPES' => array('PROJECT'),
							'INPUT_PREFIX' => $inputPrefix.'[GROUP_ID]',
							'ATTRIBUTE_PASS' => array(
								'ID',
							),
							'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PROJECT'],
							'SOLE_INPUT_IF_MAX_1' => 'Y',
							'CONTEXT' => 'template',
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				elseif($blockCode == 'CRM')
				{
					$crmUfCode = CRM\UserField::getMainSysUFCode();
					$crmUf = $template->getUserFieldScheme(true, array(
						'COLLECTION_VALUE_TO_ARRAY' => true,
					))->get($crmUfCode);
					$crmUf['FIELD_NAME'] = $inputPrefix.'['.$crmUfCode.']';
					?>
					<div class="tasks-crm-offset">
						<?\Bitrix\Tasks\Util\UserField\UI::showEdit($crmUf)?>
					</div>
					<?
				}
				elseif($blockCode == 'USER_FIELDS')
				{
					$APPLICATION->IncludeComponent(
						"bitrix:tasks.userfield.panel",
						"",
						array(
							'EXCLUDE' => array_keys($arResult['TEMPLATE_DATA']['IGNORED_USER_FIELDS']),
							'DATA' => $template,
							'ENTITY_CODE' => 'TASK_TEMPLATE',
							'INPUT_PREFIX' => $inputPrefix,
							'RELATED_ENTITIES' => array(
								'TASK',
							)
						),
						null,
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				elseif($blockCode == 'REPLICATION')
				{
					$replicationEnabled = !$template['BASE_TEMPLATE_ID'] && $template['TPARAM_TYPE'] != 1;
					?>
					<label class="js-id-hint-help js-id-task-template-edit-hint-replication task-field-label task-field-label-repeat" data-hint-enabled="<?=!intval($replicationEnabled)?>" data-hint-text="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_NO_REPLICATION_TEMPLATE_NOTICE', array('#TPARAM_FOR_NEW_USER#' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TPARAM_TYPE')))?>">
						<input class="js-id-task-template-edit-flag js-id-task-template-edit-flag-replication task-options-checkbox"
							   data-target="replication"
							   data-flag-name="REPLICATE"
							   type="checkbox" <?=($template['REPLICATE'] == 'Y' ? 'checked' : '')?>
							<?=($replicationEnabled ? '' : 'disabled')?>
						><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_MAKE_REPLICABLE')?>
						<input class="js-id-task-template-edit-replication"
							   type="hidden"
							   name="<?=$inputPrefix?>[REPLICATE]"
							   value="<?=htmlspecialcharsbx($template['REPLICATE'])?>"
						/>
					</label>
					<div class="js-id-task-template-edit-replication-panel task-options-repeat task-openable-block<?=($template['REPLICATE'] == 'Y' ? '' : ' invisible')?>">

						<?$APPLICATION->IncludeComponent(
							'bitrix:tasks.widget.replication',
							'',
							array(
								'INPUT_PREFIX' => $inputPrefix.'[REPLICATE_PARAMS]',
								'DATA' => $template['REPLICATE_PARAMS'],
								'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
								'TEMPLATE_CREATED_BY' => $template['CREATED_BY'],
							),
							$helper->getComponent(),
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>

					</div>
					<?
				}
				elseif($blockCode == 'TIME_MANAGER')
				{
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.timeestimate',
						'',
						array(
							'INPUT_PREFIX' => $inputPrefix,
							'ENTITY_DATA' => $template,
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				elseif($blockCode == 'TAG')
				{
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.tag.selector',
						'',
						array(
							'INPUT_PREFIX' => $inputPrefix.'[SE_TAG]',
							'DATA' => $template['SE_TAG'],
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				elseif($blockCode == 'RELATED_TASK')
				{
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.related.selector',
						'',
						array(
							'INPUT_PREFIX' => $inputPrefix.'[DEPENDS_ON]',
							'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_RELATEDTASK'],
							'TYPES' => array('TASK'),
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				elseif($blockCode == 'PARENT')
				{
					$baseTemplateEnabled = $template['TPARAM_TYPE'] != 1 && $template['REPLICATE'] != 'Y';
					$typeTask = intval($template['PARENT_ID']) || !$baseTemplateEnabled;
					?>

					<div class="js-id-task-template-edit-parent-type task-template-parent type-<?=($typeTask ? 'task' : 'template')?>">

						<div class="task-options-field">
							<span class="task-option-fn"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BASE')?></span>
							<span class="js-id-replication-period-type-selector tasks-option-tab-container">
								<span
									class="js-id-hint-help js-id-task-template-edit-hint-base-template js-id-task-template-edit-parent-type-option tasks-option-tab template<?=($baseTemplateEnabled ? '' : ' disabled')?>"

									data-type="template"
									data-hint-enabled="<?=!intval($baseTemplateEnabled)?>"
									data-hint-text="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_NO_BASE_TEMPLATE_TEMPLATE_NOTICE', array('#TPARAM_FOR_NEW_USER#' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TPARAM_TYPE')))?>"
								>
									<?=Loc::getMessage('TASKS_COMMON_TEMPLATE_LC')?>
								</span>
								<span
									class="
										js-id-task-template-edit-parent-type-option
										js-id-task-template-edit-parent-type-option-task
										tasks-option-tab
										task"
									data-type="task"
								>
									<?=Loc::getMessage('TASKS_COMMON_TASK_LC')?>
								</span>
							</span>
						</div>
						<input class="js-id-task-template-edit-parent-type-task" type="hidden" name="<?=$inputPrefix?>[PARENT_ID]" value="<?=intval($template['PARENT_ID'])?>" />
						<input class="js-id-task-template-edit-parent-type-template" type="hidden" name="<?=$inputPrefix?>[BASE_TEMPLATE_ID]" value="<?=(intval($template['PARENT_ID']) ? 0 : intval($template['BASE_TEMPLATE_ID']))?>" />
						<?php
							if (!empty($lockClass))
							{
								$lockClassName = "tasks-parent-selector {$lockClass}";
								$onLockClick =
									"top.BX.UI.InfoHelper.show('"
									. RestrictionUrl::TEMPLATE_LIMIT_SUBTASKS_SLIDER_URL
									. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks'}});"
								;
								$lockClassStyle = "cursor: pointer;";
						?>
								<div class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>">
						<?php
							}
							else
							{
						?>
								<div class="tasks-parent-selector">
						<?php
							}
								$APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.related.selector',
								'',
								[
									'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-parent',
									'MAX' => 1,
									'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PARENTITEM'],
									'TYPES' => [($typeTask ? 'TASK' : 'TASK_TEMPLATE')], // this could be changed at runtime on js
									'TEMPLATE_SUBTASK_LIMIT_EXCEEDED' => $templateSubtaskLimitExceeded,
								],
								$helper->getComponent(),
								[
									'HIDE_ICONS' => 'Y',
									'ACTIVE_COMPONENT' => 'Y',
								]
							);?>
						</div>
					</div>
					<?
				}
				elseif($blockCode == 'ACCESS_TEMPLATE')
				{
					if ($editMode)
					{
						$permissions = $template->getAccessPermissions();
					}
					else
					{
						$permissions = \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable::wakeUpCollection([
							[
								'ID' => 0,
								'ACCESS_CODE' => 'U' . (int) $arParams["USER_ID"],
								'PERMISSION_ID' => \Bitrix\Tasks\Access\Permission\PermissionDictionary::TEMPLATE_FULL,
								'VALUE' => 1
							]
						]);
					}

					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.template.access',
						'',
						array(
							'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-rights',
							'INPUT_PREFIX' => $inputPrefix.'[SE_TEMPLATE_ACCESS]',
							'ENTITY_CODE' => 'task_template',
							'CAN_READ' => 'Y',
							'CAN_UPDATE' => $template->canUpdateRights(),
							'USER_DATA' => $arResult['DATA']['USER'],
							'PERMISSIONS' => $permissions,
							'EDIT_MODE' => $editMode,
							'TEMPLATE_ID' => $template->getId(),
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);
				}
				$html = ob_get_clean();

				$blocks['DYNAMIC'][] = [
					'CODE' => $blockCode,
					'TITLE' => Loc::getMessage("TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_TITLE_{$blockCode}"),
					'TITLE_SHORT' => Loc::getMessage("TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_HEADER_{$blockCode}"),
					'HTML' => $html,
					'IS_PINABLE' => true,
					'FILLED' => ($blockData[$blockCode]['FILLED'] ?? null),
				];
			}

			//////// OUTPUT FRAME ////////////////////////////////////////////
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.frame',
				'',
				[
					'TEMPLATE_CONTROLLER_ID' => $helper->getId().'-frame',
					'INPUT_PREFIX' => 'ACTION[1]',
					'BLOCKS' => $blocks,
					'FRAME_ID' => 'task-template-edit',
					'FOOTER' => [
						'IS_ENABLED' => $arParams['ENABLE_FOOTER'],
						'IS_PINABLE' => $arParams['ENABLE_FOOTER_UNPIN'],
						'BUTTONS' => ['save', 'cancel'],
					],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
				],
				null, //$helper->getComponent(),
				["HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y"]
			);?>

			<?if($arParams["ENABLE_FORM"]):?>
				</form>
			<?else:?>
				</div>
			<?endif?>
		<?endif?>

		</div>

		<?$helper->initializeExtension();?>

	<?endif?>

<?endif?>

<?
if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    exit;
}
?>