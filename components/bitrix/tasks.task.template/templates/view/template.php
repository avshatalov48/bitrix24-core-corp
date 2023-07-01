<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams;

$arParams['PUBLIC_MODE'] = ($arParams['PUBLIC_MODE'] ?? null);

/** @var Template $template */
$template = $arResult['ITEM'];
$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'] ?? null;
$templateSubtaskLimitExceeded = $arResult['AUX_DATA']['TEMPLATE_SUBTASK_LIMIT_EXCEEDED'] ?? null;
$templateTaskRecurrentLimitExceeded = $arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'] ?? null;

$toList = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '').'no-all-paddings'
);

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
$APPLICATION->SetAdditionalCSS('/bitrix/js/tasks/css/tasks.css');
?>

<?if($arParams["ENABLE_MENU_TOOLBAR"]):?>

	<?php
	if(!$_REQUEST['IFRAME']) {
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.interface.topmenu',
			'',
			array(
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

				'MARK_TEMPLATES' => 'Y',
				'MARK_ACTIVE_ROLE' => 'N'
			),
			$component,
			array('HIDE_ICONS' => true)
		);
	}?>

	<?$this->SetViewTarget("pagetitle", 100);?>
	<div class="task-list-toolbar">
		<div class="task-list-toolbar-actions">
			<?php
			if (!$_REQUEST['IFRAME'])
			{
				?><a href="<?=htmlspecialcharsbx($toList)?>" class="task-list-back">
				<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TO_LIST')?>
				</a><?php
			}

			$buttonIcon = (($taskLimitExceeded || $templateSubtaskLimitExceeded) ? 'ui-btn-icon-lock' : 'ui-btn-icon-add');
			$href = (($taskLimitExceeded || $templateSubtaskLimitExceeded) ? '' : htmlspecialcharsbx($arParams['PATH_TO_TASKS_TEMPLATE_CREATE_SUB'] ?? ''));
			?>
			<button class="ui-btn ui-btn-light-border ui-btn-icon-setting ui-btn-themes" id="templateViewPopupMenuOptions"></button>
			<?if (!$helper->checkHasFatals() && \Bitrix\Tasks\Access\TemplateAccessController::can(User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_CREATE)):?>
				<a class="ui-btn ui-btn-primary ui-btn-medium <?=$buttonIcon?>" id="subTemplateAdd" href="<?=$href?>">
					<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_ADD_SUBTEMPLATE')?>
				</a>
			<?php endif?>
		</div>
	</div>
	<?$this->EndViewTarget();?>

<?endif?>

<?php if (\Bitrix\Tasks\Update\TemplateConverter::isProceed()): ?>
	<?php
		$APPLICATION->IncludeComponent("bitrix:tasks.interface.emptystate", "", [
			'TITLE' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT_TITLE'),
			'TEXT' => Loc::getMessage('TASKS_TEMPLATE_MEMBER_CONVERT'),
		]);
	?>
<?php else: ?>

	<?$helper->displayFatals();?>
	<?if(!$helper->checkHasFatals()):?>

		<?php
		$diskUfCode = Integration\Disk\UserField::getMainSysUFCode();
		$templateData = $arResult['TEMPLATE_DATA'];
		$templateEData = $templateData['TEMPLATE'];
		$canCreate = \Bitrix\Tasks\Access\TemplateAccessController::can(User::getId(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_CREATE);
		$canUpdate = $template->canUpdate();
		$canDelete = $template->canDelete();
		$userFields = $arResult['TEMPLATE_DATA']['USER_FIELDS'];
		$matchWorkTime = $template['MATCH_WORK_TIME'] == 'Y';

		if (
			$taskLimitExceeded
			|| $templateSubtaskLimitExceeded
			|| $templateTaskRecurrentLimitExceeded
		)
		{
			$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
		}
		?>

		<div id="<?=$helper->getScopeId()?>" class="task-detail tasks">

			<?$helper->displayWarnings();?>

			<div class="js-id-task-template-view-file-area task-detail-info">
				<div class="task-detail-header">
					<?if($canUpdate):?>
						<div class="js-id-task-template-view-importance-switch task-info-panel-important <?if($template["PRIORITY"] != CTasks::PRIORITY_HIGH):?>no<?endif?> mutable" data-priority="<?=intval($template["PRIORITY"])?>">
							<span class="if-no"><?=Loc::getMessage("TASKS_TASK_COMPONENT_TEMPLATE_MAKE_IMPORTANT")?></span>
							<span class="if-not-no"><?=Loc::getMessage("TASKS_IMPORTANT_TASK")?></span>
						</div>
					<?elseif($template["PRIORITY"] == CTasks::PRIORITY_HIGH):?>
						<div class="task-info-panel-important">
							<span class="if-not-no"><?=Loc::getMessage("TASKS_IMPORTANT_TASK")?></span>
						</div>
					<?endif?>
					<div class="task-detail-subtitle-status">
						<?=Loc::getMessage('TASKS_TTV_SUB_TITLE', array('#ID#' => $template->getId()))?>
					</div>
				</div>
				<div class="task-detail-content">
					<?
					$checkListItems = $templateData['SE_CHECKLIST'];

					if($template["DESCRIPTION"] <> ''):
						$extraDesc = $canUpdate || !empty($checkListItems)
							|| (isset($userFields[$diskUfCode]) && !UserField::isValueEmpty($userFields[$diskUfCode]["VALUE"]))
						?>
						<div class="task-detail-description<? if(!$extraDesc):?> task-detail-description-only<? endif ?>"
							 id="task-detail-description"><?= $template["DESCRIPTION"] ?></div>
					<? endif ?>

					<?if ($canUpdate || !empty($checkListItems)):?>
						<div class="task-detail-checklist">
							<?$APPLICATION->IncludeComponent(
								'bitrix:tasks.widget.checklist.new',
								'',
								array(
									'ENTITY_ID' => $template->getId(),
									'ENTITY_TYPE' => 'TEMPLATE',
									'DATA' => $checkListItems,
									'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
									'CONVERTED' => $arResult['CHECKLIST_CONVERTED'],
									'CAN_ADD_ACCOMPLICE' => $canUpdate,
								),
								null,
								array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
							);?>
						</div>
					<?endif?>

					<?// files\pictures ?>
					<?if (isset($userFields[$diskUfCode]) && !Bitrix\Tasks\Util\UserField::isValueEmpty($userFields[$diskUfCode]["VALUE"])):?>
						<div class="task-detail-files">
							<?UserField\UI::showView($userFields[$diskUfCode], array(
								"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
								"ENABLE_AUTO_BINDING_VIEWER" => false // file viewer cannot work in the iframe (see logic.js)
							));?>
						</div>
					<?endif?>

					<? if (!$arParams["PUBLIC_MODE"]):?>
						<div class="task-detail-extra">

							<?if($canUpdate || $template['GROUP_ID']):?>
								<div class="task-detail-group">
									<span class="task-detail-group-label"><?=Loc::getMessage("TASKS_TTDP_PROJECT_TASK_IN")?>:</span>

									<?$APPLICATION->IncludeComponent(
										'bitrix:tasks.widget.member.selector',
										'projectlink',
										array(
											'TYPES' => array('PROJECT'),
											'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PROJECT'],
											'READ_ONLY' => !$canUpdate,
											'ENTITY_ID' => $template->getId(),
											'ENTITY_ROUTE' => 'task.template',
											'CONTEXT' => 'template',
										),
										$helper->getComponent(),
										array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
									);?>

								</div>
							<?endif?>

							<? if (!empty($arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PARENTITEM'])):?>
								<?$parentItem = $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PARENTITEM'][0];?>
								<div class="task-detail-supertask"><?
									?><span class="task-detail-supertask-label"><?=Loc::getMessage($parentItem['ENTITY_TYPE'] == 'T' ? 'TASKS_PARENT_TASK' : 'TASKS_PARENT_TEMPLATE')?>:</span><?
									?><span class="task-detail-supertask-name"><a href="<?=$parentItem["URL"]?>"
																				  class="task-detail-group-link"><?=htmlspecialcharsbx($parentItem["TITLE"])?></a></span>
								</div>
							<? endif ?>
						</div>
					<? endif ?>

					<?if(count($arResult['TEMPLATE_DATA']['USER_FIELDS_TO_SHOW'])):?>
						<div class="task-detail-properties">
							<table cellspacing="0" class="task-detail-properties-layout">
								<?
								foreach ($arResult['TEMPLATE_DATA']['USER_FIELDS_TO_SHOW'] as $userField)
								{
									$title = (string) $userField["EDIT_FORM_LABEL"] != '' ? $userField["EDIT_FORM_LABEL"] : $userField["FIELD_NAME"];
									?>
									<tr>
									<td class="task-detail-property-name"><?=htmlspecialcharsbx($title)?></td>
									<td class="task-detail-property-value">
										<?UserField\UI::showView($userField, array(
											"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
											"ENABLE_AUTO_BINDING_VIEWER" => true,
										));?>
									</td>
									</tr><?
								}
								?>
							</table>
						</div>
					<?endif?>

				</div>
			</div>

			<div class="task-detail-buttons">
				<?php
				$buttonsScheme = [
					[
						'CODE' => 'CREATE_BY',
						'GROUP' => 'MORE',
						'TITLE' => Loc::getMessage('TASKS_TEMPLATE_CREATE_TASK'),
						'TYPE' => 'link',
						'URL' => $arParams['PATH_TO_TASKS_TEMPLATE_CREATE_TASK'],
						'ACTIVE' => ((int)$template['TPARAM_TYPE'] !== 1),
						'MENU_CLASS' => 'menu-popup-item-create',
					],
					[
						'CODE' => 'CREATE_SUB',
						'GROUP' => 'MORE',
						'TITLE' => Loc::getMessage('TASKS_TEMPLATE_CREATE_SUB'),
						'TYPE' => 'link',
						'URL' => ($templateSubtaskLimitExceeded ? '' : $arParams['PATH_TO_TASKS_TEMPLATE_CREATE_SUB']),
						'MENU_CLASS' => (
							$templateSubtaskLimitExceeded
								? 'menu-popup-item-create tasks-tariff-lock'
								: 'menu-popup-item-create'
						),
						'ACTIVE' => $canCreate,
					],
					[
						'CODE' => 'COPY',
						'GROUP' => 'MORE',
						'TITLE' => Loc::getMessage('TASKS_TEMPLATE_COPY'),
						'TYPE' => 'link',
						'URL' => $arParams['PATH_TO_TASKS_TEMPLATE_COPY'],
						'MENU_CLASS' => 'menu-popup-item-copy',
						'ACTIVE' => $canCreate,
					],
					[
						'CODE' => 'DELETE',
						'GROUP' => 'MORE',
						'TITLE' => Loc::getMessage('TASKS_COMMON_DELETE'),
						'MENU_CLASS' => 'menu-popup-item-delete',
						'ACTIVE' => $canDelete,
					],
					[
						'CODE' => 'UPDATE',
						'TITLE' => Loc::getMessage('TASKS_COMMON_EDIT'),
						'TYPE' => 'link',
						'URL' => $arParams['PATH_TO_TASKS_TEMPLATE_EDIT'],
						'ACTIVE' => $canUpdate,
						'KEEP_SLIDER' => true,
					],
				];
				$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.buttons',
					'',
					[
						'TEMPLATE_CONTROLLER_ID' => $helper->getId() . '-buttons',
						'SCHEME' => $buttonsScheme,
						'TEMPLATE_SUBTASK_LIMIT_EXCEEDED' => $templateSubtaskLimitExceeded,
					],
					$helper->getComponent(),
					['HIDE_ICONS' => 'Y']
				);
				?>

			</div>

			<?if($arResult['TEMPLATE_DATA']['HAVE_SUB_TEMPLATES']):?>
				<div>
					<div class="task-detail-list">
						<div class="task-detail-list-title">
							<?=Loc::getMessage("TASKS_TASK_SUBTASKS")?>
						</div>
						<?
						$pathParams = array();
						if(is_array($arParams))
						{
							foreach($arParams as $param => $value)
							{
								if(mb_strpos($param, 'PATH_') == 0)
									$pathParams[$param] = $value;
							}
						}

						$APPLICATION->IncludeComponent(
							'bitrix:tasks.templates.list',
							'',
							array_merge($pathParams, array(
								'HIDE_MENU'        => 'Y',
								'HIDE_FILTER'      => 'Y',
								'BASE_TEMPLATE_ID' => $template->getId(),
								'SET_TITLE'        => 'N',
							)), null, array("HIDE_ICONS" => "Y")
						);
						?>
					</div>
				</div>
			<?endif?>

			<?//related tasks?>
			<? if (count($templateEData["SE_RELATEDTASK"])):?>
				<div class="task-detail-list tasks-related-static-grid">
					<div class="task-detail-list-title"><?=Loc::getMessage('TASKS_TASK_LINKED_TASKS')?></div>
					<?$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.related.selector',
						'staticgrid',
						array(
							'DATA' => $templateEData["SE_RELATEDTASK"],
							'USERS' => $arResult['DATA']['USER'],
							'TYPES' => array('TASK'),
							'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_WO_GROUP'],
							'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
						),
						$helper->getComponent(),
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);?>
				</div>
			<? endif ?>

			<?ob_start();?>
			<div class="task-detail-comments">
				<div class="task-comments-and-log">
					<div class="task-comments-log-switcher">
						<span class="task-switcher task-switcher-selected">
							<span class="task-switcher-text">
								<span class="task-switcher-text-inner">
									<?=Loc::getMessage('TASKS_CTT_SYS_LOG')?>
								</span>
							</span>
						</span>
					</div>

					<div class="task-switcher-block" style="display: block">

						<?$logResult = $APPLICATION->IncludeComponent(
							'bitrix:tasks.syslog',
							'',
							array(
								'ENTITY_TYPE' => 1,
								'ENTITY_ID' => $template->getId(),
								'PAGE_SIZE' => 7,
							),
							$helper->getComponent(),
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>

					</div>
				</div>
			</div>
			<?$html = ob_get_clean();?>

			<?
			if(Result::isA($logResult))
			{
				$resultData = $logResult->getData();
				if($resultData['COUNT'])
				{
					print($html);
				}
			}
			?>

			<div class="task-footer-wrap" id="footerWrap">
				<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
					'BUTTONS' => [
						[
							'TYPE' => 'save',
							'ID' => 'saveButton',
						],
						[
							'TYPE' => 'custom',
							'LAYOUT' => '<a class="ui-btn ui-btn-link" id="cancelButton">'.Loc::getMessage("TASKS_TTV_CANCEL_BUTTON_TEXT").'</a>',
						],
					],
				]);?>
			</div>

		</div>

		<?
		////////////////////////////////////////////////////////
		//// SIDEBAR

		$this->SetViewTarget("sidebar", 100);
		?>

		<div class="task-detail-sidebar">

			<div class="task-detail-sidebar-content">

				<?if($template["DEADLINE_AFTER"]
					|| $template["START_DATE_PLAN_AFTER"]
					|| $template["END_DATE_PLAN_AFTER"]
					|| ($template["ALLOW_TIME_TRACKING"] === "Y" && $template["TIME_ESTIMATE"] > 0)
				):?>

					<div class="task-detail-sidebar-status">
						<span id="task-detail-status-name" class="task-detail-sidebar-status-text"><?=Loc::getMessage('TASKS_TTDP_DATES');?></span>
					</div>

					<?if($template["DEADLINE_AFTER"]):?>
						<div class="task-detail-sidebar-item task-detail-sidebar-item-deadline">
							<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_FIELD_DEADLINE_AFTER")?>:</div>
							<div class="task-detail-sidebar-item-value"><?=Bitrix\Tasks\UI\Component\TemplateHelper::formatDateAfter($matchWorkTime, $template["DEADLINE_AFTER"])?></div>
						</div>
					<?endif?>

					<?if($template["START_DATE_PLAN_AFTER"]):?>
						<div class="task-detail-sidebar-item">
							<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_FIELD_START_DATE_PLAN_AFTER")?>:</div>
							<div class="task-detail-sidebar-item-value"><?=Bitrix\Tasks\UI\Component\TemplateHelper::formatDateAfter($matchWorkTime, $template["START_DATE_PLAN_AFTER"])?></div>
						</div>
					<?endif?>

					<?if($template["END_DATE_PLAN_AFTER"]):?>
						<div class="task-detail-sidebar-item">
							<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_FIELD_END_DATE_PLAN_AFTER")?>:</div>
							<div class="task-detail-sidebar-item-value"><?=Bitrix\Tasks\UI\Component\TemplateHelper::formatDateAfter($matchWorkTime, $template["END_DATE_PLAN_AFTER"])?></div>
						</div>
					<?endif?>

					<?if($template["ALLOW_TIME_TRACKING"] === "Y" && $template["TIME_ESTIMATE"] > 0):?>
						<div class="task-detail-sidebar-item">
							<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_FIELD_TIME_ESTIMATE")?>:</div>
							<div class="task-detail-sidebar-item-value" id="task-detail-estimate-time-<?=$template["ID"]?>">
								<?=\Bitrix\Tasks\UI::formatTimeAmount($template["TIME_ESTIMATE"]);?>
							</div>
						</div>
					<?endif?>

				<?endif?>

				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.member.selector',
					'view',
					array(
						'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ORIGINATOR'],
						'READ_ONLY' => true,
						'ROLE' => 'ORIGINATOR',
						'MAX' => 1,
						'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_ORIGINATOR'),
						'HIDE_IF_EMPTY' => 'N',
						'CONTEXT' => 'template',
					),
					$helper->getComponent(),
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>

				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.member.selector',
					'view',
					array(
						'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_RESPONSIBLE'],
						'READ_ONLY' => !$canUpdate || $template['TPARAM_TYPE'] == 1 /*for new user*/,
						'ROLE' => 'RESPONSIBLES',
						'MIN' => 1,
						'ENABLE_SYNC' => true,
						'ENTITY_ID' => $template->getId(),
						'ENTITY_ROUTE' => 'task.template',
						'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_RESPONSIBLE'),
						'HIDE_IF_EMPTY' => 'N',
						'CONTEXT' => 'template',
					),
					$helper->getComponent(),
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>

				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.member.selector',
					'view',
					array(
						'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ACCOMPLICE'],
						'READ_ONLY' => !$canUpdate,
						'ROLE' => 'ACCOMPLICES',
						'ENABLE_SYNC' => true,
						'ENTITY_ID' => $template->getId(),
						'ENTITY_ROUTE' => 'task.template',
						'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_ACCOMPLICES'),
						'HIDE_IF_EMPTY' => !$canUpdate,
						'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
						'CONTEXT' => 'template',
					),
					$helper->getComponent(),
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>

				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.member.selector',
					'view',
					array(
						'DATA' => $arResult['TEMPLATE_DATA']['TEMPLATE']['SE_AUDITOR'],
						'READ_ONLY' => !$canUpdate,
						'ROLE' => 'AUDITORS',
						'ENABLE_SYNC' => true,
						'ENTITY_ID' => $template->getId(),
						'ENTITY_ROUTE' => 'task.template',
						'TITLE' => Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_AUDITORS'),
						'USER' => $arResult['DATA']['USER'][User::getId()],
						'HIDE_IF_EMPTY' => !$canUpdate,
						'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded,
						'CONTEXT' => 'template',
					),
					$helper->getComponent(),
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>

				<?//replication?>
				<?if(
					!$arParams["PUBLIC_MODE"] &&
					$template['TPARAM_TYPE'] != 1 &&
					!$template['BASE_TEMPLATE_ID'] &&

					!(!$canUpdate && $template['REPLICATE'] == 'N')
				):?>

					<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_SIDEBAR_REGULAR_TASK")?></div>
					<div class="task-detail-sidebar-info">
						<?$APPLICATION->IncludeComponent(
							'bitrix:tasks.widget.replication',
							'view',
							array(
								'DATA' => $template['REPLICATE_PARAMS'],
								'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
								'REPLICATE' => $template["REPLICATE"],
								'ENABLE_SYNC' => $canUpdate,
								'ENTITY_ID' => $template->getId(),
								'ENABLE_TEMPLATE_LINK' => 'N',
								'TEMPLATE_CREATED_BY' => $template['CREATED_BY'],
								'TASK_LIMIT_EXCEEDED' => $taskLimitExceeded || $templateTaskRecurrentLimitExceeded,
							),
							$helper->getComponent(),
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>
					</div>

				<?endif?>

				<?//tags?>
				<?if(!$arParams["PUBLIC_MODE"]):?>

					<?$tags = $template['SE_TAG']?>
					<?if($canUpdate || count($tags)):?>

						<?$tagString = \Bitrix\Tasks\UI\Task\Tag::formatTagString($tags);?>

						<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_TASK_TAGS")?></div>
						<div class="task-detail-sidebar-info">
							<div class="task-detail-sidebar-info-tag">
								<?php
								$APPLICATION->IncludeComponent(
									'bitrix:tasks.tags.selector',
									'selector',
									[
										'NAME' => 'TAGS',
										'VALUE' => htmlspecialcharsback($tagString),
										'CAN_EDIT' => $canUpdate,
										'TEMPLATE_ID' => $template->getId(),
										'CONTEXT' => 'TEMPLATE',
										'PATH_TO_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
									],
									null,
									['HIDE_ICONS' => 'Y']
								);
								?>
							</div>
						</div>
					<?endif?>

				<?endif?>

				<?if(!$arParams["PUBLIC_MODE"] && $template['TPARAM_TYPE'] == 1):?>

					<div class="task-detail-sidebar-info task-detail-sidebar-info-type-new-hint">
						<?=Loc::getMessage('TASKS_TTV_TYPE_FOR_NEW_USER_HINT');?>
					</div>

				<?endif?>

			</div>
		</div>

		<?
		$this->EndViewTarget();
		?>

		<?$helper->initializeExtension();?>

	<?endif?>
	</div>
<?endif?>