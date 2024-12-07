<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\UI\Editor;
use Bitrix\Tasks\Update\TemplateConverter;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util;

/**
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var array $arResult
 */

Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
$APPLICATION->SetAdditionalCSS('/bitrix/js/intranet/intranet-common.css');

Loc::loadMessages(__FILE__);

$taskObserversParticipantsEnabled = \Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(
	FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS
);

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '').'no-all-paddings'
);

/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'templates',
	]);

	return;
}

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

<?php if (TemplateConverter::isProceed())
{
	$APPLICATION->IncludeComponent("bitrix:tasks.interface.emptystate", "", [
		'TITLE' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT_TITLE'),
		'TEXT' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT'),
	]);

	return;
}

$this->SetViewTarget('pagetitle', 100);
?>

	<div class="task-list-toolbar">
		<div class="task-list-toolbar-actions">
			<button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting webform-cogwheel" id="templateEditPopupMenuOptions"></button>
		</div>
	</div>

<?php
$this->EndViewTarget();
$helper->displayFatals();

if($helper->checkHasFatals())
{
	return;
}

$helper->displayWarnings();
/** @var Template $template */
$template = $arResult['ITEM'];
$inputPrefix = 'ACTION[0][ARGUMENTS][data]';
$editMode = (bool)$template->getId();
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
	\Bitrix\Main\UI\Extension::load('ui.info-helper');
}

$taskUrlTemplate = str_replace(
	['#task_id#', '#action#'],
	['{{VALUE}}', 'view'],
	($arParams['PATH_TO_TASKS_TASK_ORIGINAL'] ?? '')
);
$userProfileUrlTemplate = str_replace('#user_id#', '{{VALUE}}', $arParams['PATH_TO_USER_PROFILE']);
?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?php
		if($arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE'])
		{
			?><div class="tasks-success-message"></div><?php
			return;
		}?>

		<form action="<?=$arParams['ACTION_URI']?>" method="post" id="task-form-<?=htmlspecialcharsbx($helper->getId())?>" name="task-form" class="js-id-task-template-edit-form">
			<input type="hidden" id="checklistAnalyticsData" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][analyticsData]" value=""/>

			<input type="hidden" id="checklistFromDescription" name="ACTION[0][ARGUMENTS][data][SE_CHECKLIST][fromDescription]" value=""/>

			<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />

			<?php if(isset($arResult['TEMPLATE_DATA']['SCENARIO'])):?>
				<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[SCENARIO_NAME]" value="<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['SCENARIO'])?>" />
			<?php endif?>

			<?php if($request->get('IFRAME')):?>
				<input type="hidden" name="IFRAME" value="<?= $request->get('IFRAME') === 'Y' ? 'Y':'N' ?>" />
			<?php endif?>

			<input class="js-id-task-template-edit-csrf" type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />
			<input type="hidden" name="EMITTER" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['ID'])?>" />

			<input type="hidden" name="BACKURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['BACKURL']))?>" />
			<input type="hidden" name="CANCELURL" value="<?=htmlspecialcharsbx(Util::secureBackUrl($arResult['TEMPLATE_DATA']['CANCELURL']))?>" />

			<input type="hidden" name="ACTION[0][OPERATION]" value="task.template.add" />
			<input type="hidden" name="ACTION[0][PARAMETERS][CODE]" value="task_template_action" />

			<?php
			if(Type::isIterable($arResult['COMPONENT_DATA']['DATA_SOURCE'] ?? null)):?>
				<input type="hidden" name="ADDITIONAL[DATA_SOURCE][TYPE]" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['DATA_SOURCE']['TYPE'])?>" />
				<input type="hidden" name="ADDITIONAL[DATA_SOURCE][ID]" value="<?= (int)$arResult['COMPONENT_DATA']['DATA_SOURCE']['ID'] ?>" />
			<?php
			endif?>

			<?php
			if (is_array($arResult['COMPONENT_DATA']['HIT_STATE'] ?? null)):?>
				<?php
				foreach($arResult['COMPONENT_DATA']['HIT_STATE'] as $field => $value):?>
					<input type="hidden" name="HIT_STATE[<?=htmlspecialcharsbx(str_replace('.', '][', $field))?>]" value="<?=htmlspecialcharsbx($value)?>" />
				<?php
				endforeach?>
			<?php
			endif;

			ob_start();
			?>

			<input class="js-id-task-template-edit-title" type="text" name="<?=$inputPrefix?>[TITLE]" value="<?=htmlspecialcharsbx($template['TITLE'])?>" placeholder="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_WHAT_TO_BE_DONE')?>"/>
			<?php
			$blocks = [];
			$blocks['HEAD_TOP_LEFT'] = [
				'HTML' => ob_get_clean(),
			];

			ob_start();
			?>
			<input
				class="js-id-task-template-edit-flag"
				type="checkbox"
				id="tasks-task-priority-cb"
				data-target="priority"
				data-flag-name="PRIORITY"
				data-yes-value="<?= Priority::HIGH ?>"
				data-no-value="<?= Priority::LOW ?>"
			/>
			<label for="tasks-task-priority-cb"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PRIORITY')?></label>
			<input class="js-id-task-template-edit-priority" type="hidden" name="<?=$inputPrefix?>[PRIORITY]" value="<?= (int)$template['PRIORITY'] ?>" />
			<?php
			$blocks['HEAD_TOP_RIGHT'] = [
				'HTML' => ob_get_clean(),
			];

			$blocks['HEAD'] = [
				'HTML' => Editor::getHTML(
					[
						'ID' => $helper->getId(),
						'ENTITY_ID' => $template->getId(),
						'ENTITY_DATA' => $template,
						'CONTENT' => $template['DESCRIPTION'],
						'BBCODE_MODE' => 'Y',
						'EXTRA_BUTTONS' => [
							'Checklist' => [
								'HTML' => '<span class="js-id-wfr-edit-form-toggler tasks-task-mpf-link" data-target="se_checklist">'
									. Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_CHECKLIST')
									. '</span>',
							],
							'ToCheckList' => [
								'HTML' => '<span class="js-id-task-template-edit-to-checklist tasks-task-mpf-link">'
									. Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TO_CHECKLIST')
									. '</span>',
							],
						],
						'USER_NAME_FORMAT' => $helper->findParameterValue('NAME_TEMPLATE'),
						'USER_FIELDS' => $template->getUserFieldScheme(
							true, ['COLLECTION_VALUE_TO_ARRAY' => true]
						)->toArray(),
						'INPUT_PREFIX' => $inputPrefix,
					]
				),
			];

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.checklist.new',
				'',
				[
					'ENTITY_ID' => $template->getId(),
					'ENTITY_TYPE' => 'TEMPLATE',
					'DATA' => $arResult['TEMPLATE_DATA']['SE_CHECKLIST'],
					'INPUT_PREFIX' => $inputPrefix . '[SE_CHECKLIST]',
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'CONVERTED' => $arResult['CHECKLIST_CONVERTED'],
					'CAN_ADD_ACCOMPLICE' => $taskObserversParticipantsEnabled,
				],
				$helper->getComponent(),
				[
					"HIDE_ICONS" => "Y",
					"ACTIVE_COMPONENT" => "Y"
				]
			);

			$blocks['HEAD_BOTTOM'][] = [
				'CODE' => 'SE_CHECKLIST',
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => $blockData['SE_CHECKLIST']['FILLED'],
			];

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				[
					'TEMPLATE_CONTROLLER_ID' => $helper->getId() . '-accomplice',
					'MAX_WIDTH' => 786,
					'TYPES' => ['USER', 'USER.EXTRANET', 'USER.MAIL'],
					'INPUT_PREFIX' => $inputPrefix . '[ACCOMPLICES]',
					'ATTRIBUTE_PASS' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'],
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ACCOMPLICE'],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
					'CONTEXT' => 'template',
					'viewSelectorEnabled' => $taskObserversParticipantsEnabled,
				],
				$helper->getComponent(),
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y'
				]
			);

			$blocks['STATIC'][] = [
				'CODE' => 'SE_ACCOMPLICE',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ACCOMPLICES'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => true,
				'RESTRICTED' => !$taskObserversParticipantsEnabled,
				'RESTRICTED_FEATURE_ID' => FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS,
			];

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.member.selector',
				'',
				[
					'TEMPLATE_CONTROLLER_ID' => $helper->getId() . '-auditor',
					'MAX_WIDTH' => 786,
					'TYPES' => ['USER', 'USER.EXTRANET', 'USER.MAIL'],
					'INPUT_PREFIX' => $inputPrefix . '[AUDITORS]',
					'ATTRIBUTE_PASS' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'],
					'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_AUDITOR'],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
					'CONTEXT' => 'template',
					'viewSelectorEnabled' => $taskObserversParticipantsEnabled,
				],
				$helper->getComponent(),
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y'
				]
			);

			$blocks['STATIC'][] = [
				'CODE' => 'SE_AUDITOR',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_AUDITORS'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => true,
				'RESTRICTED' => !$taskObserversParticipantsEnabled,
				'RESTRICTED_FEATURE_ID' => FeatureDictionary::TASK_OBSERVERS_PARTICIPANTS,
			];


			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.userfield.panel',
				'',
				[
					'EXCLUDE' => array_keys($arResult['TEMPLATE_DATA']['IGNORED_USER_FIELDS']),
					'DATA' => $template,
					'ENTITY_CODE' => 'TASK_TEMPLATE',
					'INPUT_PREFIX' => $inputPrefix,
					'RELATED_ENTITIES' => ['TASK']
				],
				null,
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y'
				]
			);

			$blocks['DYNAMIC'][] = [
				'CODE' => 'USER_FIELDS',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_TITLE_USER_FIELDS'),
				'TITLE_SHORT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_HEADER_USER_FIELDS'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => ($blockData['USER_FIELDS']['FILLED'] ?? null),
			];

			ob_start();
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.tag.selector',
				'',
				[
					'INPUT_PREFIX' => $inputPrefix.'[SE_TAG]',
					'DATA' => $template['SE_TAG'],
				],
				$helper->getComponent(),
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y'
				]
			);
			$blocks['DYNAMIC'][] = [
				'CODE' => 'TAG',
				'TITLE' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_TITLE_TAG'),
				'TITLE_SHORT' => Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_BLOCK_HEADER_TAG'),
				'HTML' => ob_get_clean(),
				'IS_PINABLE' => false,
				'FILLED' => ($blockData['TAG']['FILLED'] ?? null),
			];

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
						'IS_PINABLE' => false,
						'BUTTONS' => ['save', 'cancel'],
					],
					'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
				],
				null,
				[
					'HIDE_ICONS' => 'Y',
					'ACTIVE_COMPONENT' => 'Y'
				]
			);
			$userId = (int)CurrentUser::get()->getId();
			$accessCode = 'U' . $userId;
			?>

			<input type="hidden" name="ACTION[0][ARGUMENTS][data][RESPONSIBLES][<?= $accessCode ?>][ID]" value="<?= $userId ?>">

			<input type="hidden" name="ACTION[0][ARGUMENTS][data][CREATED_BY]" value="<?= $userId ?>">

			<input type="hidden" name="ACTION[0][ARGUMENTS][data][REPLICATE]" value="N">

		</form>
	</div>
<?php
$helper->initializeExtension();
?>