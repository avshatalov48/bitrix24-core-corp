<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;
use Bitrix\Tasks\Util;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans', 'tasks.comment-action-controller']);

$templateData = $arResult["TEMPLATE_DATA"];

if (isset($templateData["ERROR"]))
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "", []);
	return;
}

if ($arResult["LIKE_TEMPLATE"] == 'like_react')
{
	UI\Extension::load("main.rating");
}

$taskData = $arResult["DATA"]["TASK"] ?? [];
$can = $arResult["CAN"]["TASK"]["ACTION"] ?? [];
$userFields = $arResult["AUX_DATA"]["USER_FIELDS"] ?? [];
$diskUfCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
$mailUfCode = \Bitrix\Tasks\Integration\Mail\UserField::getMainSysUFCode();
$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
$taskLimitExceeded = $arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'];
$taskRecurrentRestrict = $arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'];

if ($taskLimitExceeded || $taskRecurrentRestrict)
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}

if (isset($arResult['CAN_SHOW_MOBILE_QR_POPUP']) && $arResult['CAN_SHOW_MOBILE_QR_POPUP'] === true)
{
	\Bitrix\Main\UI\Extension::load(['ui.qrauthorization']);

	$this->SetViewTarget('mobileqrpopup'); ?>
	<span class="task-page__mobile-divider"></span>
	<span class="task-page__mobile" title="<?=Loc::getMessage('TASKS_TASK_CREATED_IN_MOBILE_TITLE') ?>">
		<?= Loc::getMessage('TASKS_TASK_CREATED_IN_MOBILE_CONTENT', [
			'#HTML_START#' => '<span class="task-page__mobile_name">',
			'#HTML_UNDERLINE_START#' => '<span class="task-page__mobile_value" onclick="showPopupWithQRCode()">',
			'#HTML_UNDERLINE_END#' => '</span>',
			'#HTML_END#' => '</span>',
		]) ?>
	</span>
	<script>
		function showPopupWithQRCode()
		{
			const popup = new BX.UI.QrAuthorization({
				title: {
					text: '<?=Loc::getMessage('TASKS_TASK_POPUP_TITLE')?>',
					size: 'sm'
				},
				bottomText: {
					text: '<?=Loc::getMessage('TASKS_TASK_POPUP_BOTTOM_TEXT')?>',
					size: 'sm'
				},
				popupParam: {
					overlay: true
				}
			});
			popup.show();
		}
	</script>
	<?php $this->EndViewTarget();
}

$APPLICATION->ShowViewContent("task_menu");

//Menu and Page Title Buttons
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
			'ENTITY_ID' => $taskData['ID'],
			'SECTION' => 'VIEW_TASK',
			'ADD_BUTTON' => [
				'NAME' => Loc::getMessage('TASKS_ADD_TASK_SHORT'),
				'ID' => 'task-detail-create-button',
				'URL' => CComponentEngine::makePathFromTemplate(
					($arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']),
					[
						'user_id' => $arParams['USER_ID'],
						'group_id' => $arParams['GROUP_ID'],
						'action' => 'edit',
						'task_id' => 0,
					]
				),
			],
		]
	);
}

if (
	$arResult['DATA']['IS_NETWORK_TASK']
	&& (
		Util::getOption('test_tasks_network_disabled') === 'Y'
		|| time() > mktime(0, 0, 0, 9, 1, 2021)
	)
)
{
	$message = str_replace(
		['#LINK_START#', '#LINK_END#'],
		[
			'<span class="ui-link ui-link-primary ui-link-dotted" onclick="top.BX.Helper.show(\'redirect=detail&code=14137758\');">',
			'</span>',
		],
		Loc::getMessage('TASKS_TASK_NETWORK_DISABLING_WARNING')
	);
	?>
	<div class="ui-alert ui-alert-warning ui-alert-icon-warning">
		<span class="ui-alert-message"><?= $message ?></span>
	</div>
	<?php
}
?>

<div class="task-detail">
	<div class="task-detail-info">
		<div class="task-detail-header">
			<?if($can["EDIT"] || $taskData["PRIORITY"] == CTasks::PRIORITY_HIGH):?>
				<div id="task-detail-important-button" class="task-info-panel-important <?if($taskData["PRIORITY"] != CTasks::PRIORITY_HIGH):?>no<?endif?> <?if($can["EDIT"]):?>mutable<?endif?>" data-priority="<?=intval($taskData["PRIORITY"])?>">
					<span class="if-no"><?=Loc::getMessage("TASKS_TASK_COMPONENT_TEMPLATE_MAKE_IMPORTANT")?></span>
					<span class="if-not-no"><?=Loc::getMessage("TASKS_IMPORTANT_TASK")?></span>
				</div>
			<?endif?>

			<? if ($arParams["PUBLIC_MODE"]): ?>
				<div class="task-detail-header-title"><?=htmlspecialcharsbx($taskData["TITLE"])?></div>
			<? else:
				$expired = $taskData["STATUS"] == CTasks::METASTATE_EXPIRED;?>
				<div class="task-detail-subtitle-status <?if($expired):?>task-detail-subtitle-status-delay-message<?endif?>"><?=Loc::getMessage('TASKS_TTV_SUB_TITLE', array(
						'#ID#' => intval($taskData['ID']),
					))?> - <span id="task-detail-status-below-name"><?=\Bitrix\Tasks\UI::toLowerCaseFirst(Loc::getMessage("TASKS_TASK_STATUS_".$taskData["REAL_STATUS"]))?><?if($expired):?></span>, <?=\Bitrix\Tasks\UI::toLowerCaseFirst(Loc::getMessage('TASKS_STATUS_OVERDUE'))?><?endif?>
				</div>
			<? endif ?>
		</div>
		<div class="task-detail-content" id="task-detail-content">
		<? if (!empty($taskData['SE_ORIGINATOR'])):?>
			<span class="task-detail-author-info" id="task-detail-author-info" bx-post-author-id="<?=intval($taskData['SE_ORIGINATOR']['ID'])?>" bx-post-author-gender="<?=htmlspecialcharsbx($taskData['SE_ORIGINATOR']['PERSONAL_GENDER'])?>"><?=htmlspecialcharsbx($taskData["SE_ORIGINATOR"]['NAME_FORMATTED'])?></span>
		<? endif ?>
		<? if (!$arParams["PUBLIC_MODE"]):?>
			<div id="task-detail-favorite"
				class="task-detail-favorite<?if ($taskData["FAVORITE"] === "Y"):?> task-detail-favorite-active<?endif?>"
				title="<?=Loc::getMessage("TASKS_TASK_ADD_TO_FAVORITES")?>"><div class="task-detail-favorite-star"></div>
			</div>
		<? endif ?>

		<?/* if (strlen($taskData["DESCRIPTION"])):*/
			$extraDesc =
				$can["EDIT"] ||
				!empty($taskData["SE_CHECKLIST"]) ||
				(isset($userFields[$diskUfCode]) && !empty($userFields[$diskUfCode]["VALUE"]));
		?>
			<div class="task-detail-description<?if (!$extraDesc):?> task-detail-description-only<?endif?>"
				id="task-detail-description"><?=$taskData["DESCRIPTION"]?></div>
		<? /*endif*/ ?>

		<?if ($can["EDIT"] || $can["CHECKLIST.ADD"] || !empty($taskData["SE_CHECKLIST"])):?>
			<div class="task-detail-checklist">
				<?
					$APPLICATION->IncludeComponent(
						'bitrix:tasks.widget.checklist.new',
						'',
						[
							'ENTITY_ID' => $taskData['ID'],
							'ENTITY_TYPE' => 'TASK',
							'DATA' => $taskData['SE_CHECKLIST'],
							'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
							'CONVERTED' => $arResult['DATA']['CHECKLIST_CONVERTED'],
							'CAN_ADD_ACCOMPLICE' => $can['EDIT'] && !$taskLimitExceeded,
						],
						null,
						['HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y']
					);
				?>
			</div>
		<?endif?>

		<?// files\images ?>
		<?if (isset($userFields[$diskUfCode]) && !empty($userFields[$diskUfCode]["VALUE"])):?>
			<div class="task-detail-files" id="task-detail-files">
				<?\Bitrix\Tasks\Util\UserField\UI::showView($userFields[$diskUfCode], array(
					"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
					"ENABLE_AUTO_BINDING_VIEWER" => false // file viewer cannot work in the iframe (see logic.js)
				));?>
			</div>
		<?endif?>

		<? $mailUf = $arResult['AUX_DATA']['USER_FIELDS'][$mailUfCode]; ?>
		<? if (!empty($mailUf['VALUE'])): ?>
			<div style="padding: 15px 0; border-bottom: 1px solid rgba(234,235,237,0.78); ">
				<? $mailUf['FIELD_NAME'] = $inputPrefix.'['.$mailUfCode.']'; ?>
				<? \Bitrix\Tasks\Util\UserField\UI::showView($mailUf); ?>
			</div>
		<? endif ?>

		<?php if (!$arParams["PUBLIC_MODE"]): ?>
			<div class="task-detail-extra<?= (!empty($templateData["RELATED_TASK"]) ? ' --flex-wrap' : '') ?>"><?
				if($can["EDIT"] || !empty($templateData["GROUP"])):?>
				<div class="task-detail-group-wrap">
					<div class="task-detail-group --flex-center">
						<span class="task-detail-group-label"><?=Loc::getMessage("TASKS_TTDP_PROJECT_TASK_IN")?>:</span>
						<?$APPLICATION->IncludeComponent(
							'bitrix:tasks.widget.member.selector',
							'projectlink',
							array(
								'TYPES' => array('PROJECT'),
								'DATA' => array(
									$templateData["GROUP"]
								),
								'READ_ONLY' => !$can["EDIT"],
								'ENTITY_ID' => $taskData["ID"],
								'ENTITY_ROUTE' => 'task',
								'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'],
								'GROUP_ID' => (array_key_exists('GROUP_ID', $taskData)) ? $taskData['GROUP_ID'] : 0,
								'ROLE_KEY' => \Bitrix\Tasks\Access\Role\RoleDictionary::ROLE_AUDITOR
							),
							null,
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>
					</div>
				</div>
				<?endif?>
				<div class="task-detail-extra-right"><?php

					if ($arResult["LIKE_TEMPLATE"] == 'like_react')
					{
						?><div class="task-detail-like --flex"><?

							$voteId = "TASK".'_'.$taskData["ID"].'-'.(time()+rand(0, 1000));
							$emotion = (!empty($templateData["RATING"]["USER_REACTION"])? mb_strtoupper($templateData["RATING"]["USER_REACTION"]) : 'LIKE');

							?><span id="bx-ilike-button-<?=htmlspecialcharsbx($voteId)?>" class="feed-inform-ilike feed-new-like "><?
								?><span class="bx-ilike-left-wrap<?=(isset($templateData["RATING"]) && isset($templateData["RATING"]["USER_HAS_VOTED"]) && $templateData["RATING"]["USER_HAS_VOTED"] == "Y" ? ' bx-you-like-button' : '')?>"><a href="#like" class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></a></span><?
							?></span><?
						?></div><?

						?><div class="feed-post-emoji-top-panel-outer"><?

							?><div id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>" class="feed-post-emoji-top-panel-box <?=((int)($templateData["RATING"]["TOTAL_POSITIVE_VOTES"] ?? null) > 0 ? 'feed-post-emoji-top-panel-container-active' : '')?>"><?

							$APPLICATION->IncludeComponent(
								"bitrix:rating.vote",
								"like_react",
								array(
									"ENTITY_TYPE_ID" => "TASK",
									"ENTITY_ID" => $taskData["ID"],
									"OWNER_ID" => $taskData["CREATED_BY"],
									"USER_VOTE" => ($templateData["RATING"]["USER_VOTE"] ?? null),
									"USER_HAS_VOTED" => ($templateData["RATING"]["USER_HAS_VOTED"] ?? null),
									"TOTAL_VOTES" => ($templateData["RATING"]["TOTAL_VOTES"] ?? null),
									"TOTAL_POSITIVE_VOTES" => ($templateData["RATING"]["TOTAL_POSITIVE_VOTES"] ?? null),
									"TOTAL_NEGATIVE_VOTES" => ($templateData["RATING"]["TOTAL_NEGATIVE_VOTES"] ?? null),
									"TOTAL_VALUE" => ($templateData["RATING"]["TOTAL_VALUE"] ?? null),
									"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
									"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
									"LIKE_TEMPLATE" => "light_no_anim",
									"TOP_DATA" => (!empty($arResult['TOP_RATING_DATA']) ? $arResult['TOP_RATING_DATA'] : false),
									"REACTIONS_LIST" => ($templateData["RATING"]["REACTIONS_LIST"] ?? null),
									"VOTE_ID" => $voteId,
								),
								$component,
								array("HIDE_ICONS" => "Y")
							);

							?></div><?

						?></div><?
					}
					else
					{
						?><div class="task-detail-like"><?
						$APPLICATION->IncludeComponent(
							"bitrix:rating.vote",
							$arParams["RATING_TYPE"],
							array(
								"ENTITY_TYPE_ID" => "TASK",
								"ENTITY_ID" => $taskData["ID"],
								"OWNER_ID" => $taskData["CREATED_BY"],
								"USER_VOTE" => $templateData["RATING"]["USER_VOTE"],
								"USER_HAS_VOTED" => $templateData["RATING"]["USER_HAS_VOTED"],
								"TOTAL_VOTES" => $templateData["RATING"]["TOTAL_VOTES"],
								"TOTAL_POSITIVE_VOTES" => $templateData["RATING"]["TOTAL_POSITIVE_VOTES"],
								"TOTAL_NEGATIVE_VOTES" => $templateData["RATING"]["TOTAL_NEGATIVE_VOTES"],
								"TOTAL_VALUE" => $templateData["RATING"]["TOTAL_VALUE"],
								"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
								"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
								"LIKE_TEMPLATE" => "light_no_anim",
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);
						?></div><?
					}

					if (!empty($arResult['CONTENT_ID']))
					{
						?><div class="task-detail-contentview"><?php

						$APPLICATION->IncludeComponent(
							'bitrix:socialnetwork.contentview.count',
							'',
							[
								'CONTENT_ID' => $arResult['CONTENT_ID'],
								'CONTENT_VIEW_CNT' => $arResult['CONTENT_VIEW_CNT'],
								'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
							],
							$component,
							[ 'HIDE_ICONS' => 'Y' ]
						);

						?></div><?php
					}

				?></div>

				<? if (!empty($templateData["RELATED_TASK"])):?>
					<div class="task-detail-supertask"><?
						?><span class="task-detail-supertask-label"><?=Loc::getMessage("TASKS_PARENT_TASK")?>:</span><?
						?><span class="task-detail-supertask-name"><a href="<?=$templateData["RELATED_TASK"]["URL"]?>"
																	  class="task-detail-group-link"><?=htmlspecialcharsbx($templateData["RELATED_TASK"]["TITLE"])?></a></span>
					</div>
				<? endif ?>

				<?if ($arResult["AUX_DATA"]['MAIL']['FORWARD'] ?? null):?>
					<div class="task-detail-group"><?
						?><span class="task-detail-group-label"><?=Loc::getMessage("TASKS_MAIL_FORWARD")?>:</span><?
						?><span class="task-detail-group-name">
							<?=htmlspecialcharsbx($arResult["AUX_DATA"]['MAIL']['FORWARD'])?>
						</span>
					</div>
				<?endif?>
			</div>
		<? endif ?>

		<? if ($templateData["SHOW_USER_FIELDS"]): ?>
			<div class="task-detail-properties">
				<table class="task-detail-properties-layout">
				<?//todo: uf managing form here (as a component)?>
				<?
				foreach ($userFields as $userField)
				{
					if (
						(empty($userField["VALUE"]) && $userField["VALUE"] !== '0' && $userField["USER_TYPE_ID"] !== 'boolean') ||
						in_array($userField['FIELD_NAME'], array($diskUfCode, $mailUfCode)) ||
						!\Bitrix\Tasks\Util\UserField\UI::isSuitable($userField)
					)
					{
						continue;
					}
					?>
					<tr>
						<td class="task-detail-property-name"><?=htmlspecialcharsbx($userField["EDIT_FORM_LABEL"])?></td>
						<td class="task-detail-property-value">
							<?\Bitrix\Tasks\Util\UserField\UI::showView($userField, array(
								"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
								"ENABLE_AUTO_BINDING_VIEWER" => true,
							));?>
						</td>
					</tr>
					<?
				}?>
				</table>
			</div>
		<?endif?>

		</div>
	</div>

	<div class="task-detail-buttons"><?
		$APPLICATION->IncludeComponent(
			"bitrix:tasks.widget.buttons",
			"task",
			array(
				"GROUP_ID" => $arParams["GROUP_ID"],
				"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
				"PATH_TO_TASKS_TASK_COPY" => \Bitrix\Tasks\UI\Task::makeCopyUrl($arParams["PATH_TO_TASKS_TASK"], $taskData["ID"]),
				"PATH_TO_TASKS_TASK_CREATE_SUBTASK" => \Bitrix\Tasks\UI\Task::makeCreateSubtaskUrl($arParams["PATH_TO_TASKS_TASK"], $taskData["ID"]),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
				"CAN" => $can,
				"TASK_ID" => $taskData["ID"],

				"IS_SCRUM_TASK" => $arParams['IS_SCRUM_TASK'],
				"TASK" => $taskData,
				"TIMER_IS_RUNNING_FOR_CURRENT_USER" => !!$templateData["TIMER_IS_RUNNING_FOR_CURRENT_USER"],
				"TIMER" => ($templateData["TIMER"] ?? null),
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"REDIRECT_TO_LIST_ON_DELETE" => $arParams['REDIRECT_TO_LIST_ON_DELETE'] ?? null,
				"TASK_LIMIT_EXCEEDED" => $taskLimitExceeded,
			),
			null,
			array("HIDE_ICONS" => "Y")
		);
		?>
	</div>

	<?php if ($templateData["SUBTASKS_EXIST"]):?>
		<div class="task-detail-list">
			<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_SUBTASKS")?></div>
			<?php
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.list',
				'',
				[
					'HIDE_VIEWS' => 'Y',
					'HIDE_MENU' => 'Y',
					'HIDE_GROUP_ACTIONS' => 'Y',
					'FORCE_LIST_MODE' => 'Y',
					'PREVENT_PAGE_ONE_COLUMN' => 'Y',
					'PREVENT_FLEXIBLE_LAYOUT' => (($arResult['IS_IFRAME'] ?? null) ? 'N' : 'Y'),
					'COMMON_FILTER' => [],
					'ORDER' => ['GROUP_ID'  => 'ASC'],
					'PREORDER' => ['REAL_STATUS' => 'ASC'],
					'FILTER' => ['PARENT_ID' => $taskData['ID']],
					'VIEW_STATE' => [],
					'CONTEXT_ID' => CTaskColumnContext::CONTEXT_TASK_DETAIL,
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
					'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK'],
					'TASKS_ALWAYS_EXPANDED' => 'Y',
					'PUBLIC_MODE' => $arParams['PUBLIC_MODE'],
					'SET_TITLE' => 'N',
					'SKIP_GROUP_SORT' => 'Y',
				],
				null,
				['HIDE_ICONS' => 'Y']
			);
			?>
		</div>
	<?php endif; ?>

	<? if (count($templateData["PREDECESSORS"])):?>

		<div class="task-detail-list">
			<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_PREDECESSORS")?></div>
			<?$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.related.selector',
				'staticgrid',
				array(
					'DATA' => $templateData["PREDECESSORS"],
					'USERS' => $arResult['DATA']['USER'],
					'TYPES' => array('TASK'),
					'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_WO_GROUP'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'COLUMNS' => array(
						'TITLE', 'START_DATE_PLAN', 'END_DATE_PLAN',
						'DEPENDENCY_TYPE' => array(
							'SOURCE' => 'DEPENDENCY_TYPE',
							'TITLE' => Loc::getMessage('TASKS_DEPENDENCY_TYPE')
						)
					),
				),
				null,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

	<? endif ?>


	<? if (count($templateData["PREV_TASKS"])):?>

		<div class="task-detail-list">
			<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_LINKED_TASKS")?></div>
			<?$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.related.selector',
				'staticgrid',
				array(
					'DATA' => $templateData["PREV_TASKS"],
					'USERS' => $arResult['DATA']['USER'],
					'TYPES' => array('TASK'),
					'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_WO_GROUP'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				),
				null,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

	<? endif ?>

	<?php
		$hasRestPlacement = false;
		// получить список встроенных приложений
		if(\Bitrix\Main\Loader::includeModule('rest'))
		{
			$restPlacementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\CTaskRestService::PLACEMENT_TASK_VIEW_TAB);
			$restPlacementHandlerList = array_filter($restPlacementHandlerList, function($placement) use ($arParams) {
				$groupIdOption = (string)$placement['OPTIONS']['groupId'];
				if ($groupIdOption == '')
				{
					return true;
				}
				$allowedGroups = array_map('trim', explode(',', $groupIdOption));

				return $arParams['GROUP_ID'] > 0 && in_array($arParams['GROUP_ID'], $allowedGroups, false);
			});

			$hasRestPlacement = !empty($restPlacementHandlerList);
		}
	?>

	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.widget.result',
		'.default',
		[
			'TASK_ID' => $taskData['ID'],
			'RESPONSIBLE' => (int) $taskData['RESPONSIBLE_ID'],
			'ACCOMPLICES' => $taskData['ACCOMPLICES'],
		],
		$component,
	);
	?>

	<div class="task-detail-comments">
		<div class="task-comments-and-log">
			<div class="task-comments-log-switcher" id="task-switcher">
				<span class="task-switcher task-switcher-selected" id="task-comments-switcher" data-id="comments">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_TASK_COMMENTS")?>
							<span class="task-switcher-text-counter"><?= (($taskData["COMMENTS_COUNT"] ?? 0) - ($taskData["SERVICE_COMMENTS_COUNT"] ?? 0)) ?></span>
						</span>
					</span>
				</span>
				<span class="task-switcher" id="task-log-switcher" data-id="log">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_TASK_LOG_SHORT")?>
							<span class="task-switcher-text-counter" id="task-switcher-text-log-count"><?=count($taskData["SE_LOG"] ?? [])?></span>
						</span>
					</span>
				</span>
				<span class="task-switcher" id="task-time-switcher" data-id="time">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_ELAPSED_TIME_SHORT")?>
							<span class="task-switcher-text-counter" id="task-switcher-elapsed-time"><?=\Bitrix\Tasks\UI::formatTimeAmount($templateData["ELAPSED"]['TIME'])?></span>
						</span>
					</span>
				</span>
				<?php if($arResult['DATA']['EFFECTIVE']['COUNT']>0):?>
				<span class="task-switcher" id="task-effective-switcher" data-id="effective">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_EFFECTIVE_TAB")?>
							<span class="task-switcher-text-counter" id="task-switcher-effective"><?=$arResult['DATA']['EFFECTIVE']['COUNT']?></span>
						</span>
					</span>
				</span>
				<?php endif?>
				<? if ($templateData["FILES_IN_COMMENTS"] > 0): ?>
				<span class="task-switcher" id="task-files-switcher" data-id="files">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner"><?=Loc::getMessage("TASKS_FILES_FROM_COMMENTS")?>
							<span class="task-switcher-text-counter"><?=$templateData["FILES_IN_COMMENTS"]?></span>
						</span>
					</span>
				</span>
				<? endif ?>

				<?php if($hasRestPlacement)
				{?>
				<?php foreach($restPlacementHandlerList as $app):?>
					<span class="task-switcher" id="task-restapp-block-app-<?=$app['APP_ID']?>-switcher" data-id="task-restapp-block-app-<?=$app['APP_ID']?>">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner"><?=trim($app['TITLE']) ? $app['TITLE']
								: $app['APP_NAME']?></span>
					</span>
				</span>
				<?php endforeach?>
				<?php
				}?>
			</div>

			<div class="task-switcher-block task-comments-block task-switcher-block-selected" id="task-comments-block"><?
				if (intval($taskData["FORUM_ID"]))
				{
					$APPLICATION->IncludeComponent(
						"bitrix:forum.comments",
						"bitrix24",
						array(
							"FORUM_ID" => $taskData["FORUM_ID"],
							"ENTITY_TYPE" => "TK",
							"ENTITY_ID" => $taskData["ID"],
							"ENTITY_XML_ID" => "TASK_".$taskData["ID"],
							"URL_TEMPLATES_PROFILE_VIEW" => $arParams["PATH_TO_USER_PROFILE"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"CACHE_TIME" => ($arParams["CACHE_TIME"] ?? null),
							"IMAGE_HTML_SIZE" => 400,
							"MESSAGES_PER_PAGE" => ($arParams["ITEM_DETAIL_COUNT"] ?? null),
							"PAGE_NAVIGATION_TEMPLATE" => "arrows",
							"DATE_TIME_FORMAT" => \Bitrix\Tasks\UI::getDateTimeFormat(),
							"PATH_TO_SMILE" => ($arParams["PATH_TO_FORUM_SMILE"] ?? null),
							"EDITOR_CODE_DEFAULT" => "N",
							"SHOW_MODERATION" => "Y",
							"SHOW_AVATAR" => "Y",
							"SHOW_RATING" => $arParams["SHOW_RATING"],
							"RATING_TYPE" => $arParams["RATING_TYPE"],
							"SHOW_MINIMIZED" => "N",
							"USE_CAPTCHA" => "N",
							"PREORDER" => "N",
							"SHOW_LINK_TO_FORUM" => "N",
							"SHOW_SUBSCRIBE" => "N",
							"FILES_COUNT" => 10,
							"SHOW_WYSIWYG_EDITOR" => "Y",
							"BIND_VIEWER" => "N", // Viewer cannot work in the iframe (see logic.js)
							"AUTOSAVE" => true,
							"PERMISSION" => "M", //User already have access to task, so user have access to read/create comments
							"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
							"MESSAGE_COUNT" => 3,
							"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
							"ALLOW_MENTION" => $arParams["PUBLIC_MODE"] ? "N" : "Y",
							'ALLOW_MENTION_EMAIL_USER' => 'Y',
							"TEMPLATE_DATA" => [
								"DATA" => [
									"GROUP_VIEWED" => $arResult["DATA"]["GROUP_VIEWED"]
								],
							],
							"USER_FIELDS_SETTINGS" =>
								$arParams["PUBLIC_MODE"]
								? array(
									"UF_FORUM_MESSAGE_DOC" => array(
										"DISABLE_CREATING_FILE_BY_CLOUD" => true,
										"DISABLE_LOCAL_EDIT" => true
									)
								)
								: array()
						),
						($component->__parent ? $component->__parent : $component),
						array("HIDE_ICONS" => "Y")
					);
				}
				?>
			</div>

			<div class="task-switcher-block task-log-block" id="task-log-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("log"),
						"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
						"TEMPLATE_DATA" => array(
							"DATA" => $arResult["DATA"],
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>

			<div class="task-switcher-block task-time-block" id="task-time-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("time"),
						"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
						"TASK_ID" => $taskData["ID"],
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
						"TEMPLATE_DATA" => array(
							"DATA" => $arResult["DATA"],
							"CAN" => $arResult["CAN"],
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>

			<?php if($arResult['DATA']['EFFECTIVE']['COUNT']>0):?>
			<div class="task-switcher-block task-effective-block" id="task-effective-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("effective"),
						"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
						"TASK_ID" => $taskData["ID"],
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
						"TEMPLATE_DATA" => array(
							"DATA" => $arResult["DATA"],
							"CAN" => $arResult["CAN"],
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>
			<? endif ?>

			<? if ($templateData["FILES_IN_COMMENTS"] > 0): ?>
			<div class="task-switcher-block task-files-block" id="task-files-block"></div>
			<? endif ?>


			<?php if($hasRestPlacement)
			{?>
				<?php foreach($restPlacementHandlerList as $app):?>
				<div class="task-switcher-block task-restapp-block-app-<?=$app['APP_ID']?>" id="task-restapp-block-app-<?=$app['APP_ID']?>">
					<?php
					$placementSid = $APPLICATION->includeComponent(
						'bitrix:app.layout',
						'',
						[
							'ID' => $app['APP_ID'],
							'PLACEMENT' => \CTaskRestService::PLACEMENT_TASK_VIEW_TAB,
							'PLACEMENT_ID' => $app['ID'],
							"PLACEMENT_OPTIONS" => ['taskId'=>$taskData["ID"]],
							'SET_TITLE' => 'N'
						],
						null,
						array('HIDE_ICONS' => 'Y')
					);
					?>
				</div>
				<?php endforeach?>
			<?php
			}?>
		</div>
	</div>
	<div class="task-footer-wrap" id="footerWrap">
		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				[
					'TYPE' => 'save',
					'ID' => 'saveButton',
				],
				[
					'TYPE' => 'custom',
					'LAYOUT' => '<a class="ui-btn ui-btn-link" id="cancelButton">'.Loc::getMessage("TASKS_TASK_CANCEL_BUTTON_TEXT").'</a>',
				],
			],
		]);?>
	</div>
</div>


<?
$this->SetViewTarget("sidebar", 100);
$APPLICATION->IncludeComponent(
	"bitrix:tasks.task.detail.parts",
	"flat",
	array(
		"MODE" => "VIEW TASK",
		"BLOCKS" => array("sidebar"),
		"GROUP_ID" => $arParams["GROUP_ID"],
		"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
		"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
		"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"PATH_TO_TEMPLATES_TEMPLATE" => $arParams["PATH_TO_TEMPLATES_TEMPLATE"],
		"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
		"TASK_ID" => $taskData["ID"],
		"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
		"USER" => $arResult['DATA']['USER'][\Bitrix\Tasks\Util\User::getId()],
		"TEMPLATE_DATA" => array(
			"DATA" => $arResult["DATA"],
			"AUX_DATA" => $arResult["AUX_DATA"],
			"TIMER_IS_RUNNING_FOR_CURRENT_USER" => $templateData["TIMER_IS_RUNNING_FOR_CURRENT_USER"],
			"TIMER" => ($templateData["TIMER"] ?? null),
		),
		"SHOW_COPY_URL_LINK" => $arParams['SHOW_COPY_URL_LINK'] ?? null,
		"TASK_LIMIT_EXCEEDED" => $taskLimitExceeded,
		'CALENDAR_SETTINGS' => $arResult['CALENDAR_SETTINGS'],
		'IS_SCRUM_TASK' => $arParams['IS_SCRUM_TASK'],
	),
	null,
	array("HIDE_ICONS" => "Y")
);

$this->EndViewTarget();
?>

<script>
	<?/*todo: move php function tasksRenderJSON() to javascript, use CUtil::PhpToJSObject() here for EVENT_TASK, and then remove the following code*/?>
	<?if (is_array($arResult["DATA"]["EVENT_TASK"] ?? null)):?>
		<?CJSCore::Init("CJSTask"); // ONLY to make BX.CJSTask.fixWin() available?>
		var eventTaskUgly = <?tasksRenderJSON(
			$arResult["DATA"]["EVENT_TASK_SAFE"],
			intval($arResult["DATA"]["EVENT_TASK"]["CHILDREN_COUNT"]),
			array(
				"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"]
			),
			true,
			true,
			true,
			CSite::GetNameFormat(false)
		)?>;
	<?else:?>
		var eventTaskUgly = null;
	<?endif?>

	new BX.Tasks.Component.TaskView({
		messages: {
			TASKS_NOTIFY_TASK_CREATED: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_NOTIFY_TASK_CREATED"))?>",
			TASKS_NOTIFY_TASK_DO_VIEW: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_NOTIFY_TASK_DO_VIEW"))?>",
			addTask: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ADD_TASK"))?>",
			addTaskByTemplate: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ADD_TASK_BY_TEMPLATE"))?>",
			addSubTask: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ADD_SUBTASK_2"))?>",
			listTaskTemplates: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_LIST_TEMPLATES"))?>",

			//Need for sidebar ajax update
			TASKS_STATUS_1: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_1"))?>",
			TASKS_STATUS_2: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_2"))?>",
			TASKS_STATUS_3: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_3"))?>",
			TASKS_STATUS_4: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_4"))?>",
			TASKS_STATUS_5: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_5"))?>",
			TASKS_STATUS_6: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_6"))?>",
			TASKS_STATUS_7: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_7"))?>",

			tasksAjaxEmpty: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_AJAX_EMPTY_TEMPLATES'))?>",
			tasksAjaxErrorLoad: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_AJAX_ERROR_LOAD_TEMPLATES'))?>",
			tasksAjaxLoadTemplates: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_AJAX_LOAD_TEMPLATES'))?>",

			TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_CLOSE_SLIDER_CONFIRMATION_POPUP_HEADER'))?>",
			TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_CLOSE_SLIDER_CONFIRMATION_POPUP_CONTENT'))?>",
			TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CLOSE'))?>",
			TASKS_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_CLOSE_SLIDER_CONFIRMATION_POPUP_BUTTON_CANCEL'))?>",
			TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_HEADER: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_DISABLE_CHANGES_CONFIRMATION_POPUP_HEADER'))?>",
			TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_CONTENT: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_DISABLE_CHANGES_CONFIRMATION_POPUP_CONTENT'))?>",
			TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_YES: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_YES'))?>",
			TASKS_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_NO: "<?=CUtil::JSEscape(Loc::getMessage('TASKS_TASK_DISABLE_CHANGES_CONFIRMATION_POPUP_BUTTON_NO'))?>"
		},
		paths: {
			newTask: "<?=CUtil::JSEscape($templateData["NEW_TASK_PATH"])?>",
			newSubTask: "<?=CUtil::JSEscape($templateData["NEW_SUBTASK_PATH"])?>",
			taskTemplates: "<?=CUtil::JSEscape($templateData["TASK_TEMPLATES_PATH"])?>",
			taskView: "<?=CUtil::JSEscape($templateData["TASK_VIEW_PATH"])?>"
		},
		taskId: <?=$taskData["ID"]?>,
		userId: <?=$arParams["USER_ID"]?>,
		project: <?=CUtil::PhpToJSObject($taskData["SE_PROJECT"])?>,
		groupId: <?= (int)$arParams['GROUP_ID'] ?>,
		eventTaskUgly: eventTaskUgly,
		componentData: {
			EVENT_TYPE: "<?=CUtil::JSEscape($arResult["COMPONENT_DATA"]["EVENT_TYPE"] ?? null)?>",
			EVENT_OPTIONS: <?=CUtil::PhpToJsObject($arResult["COMPONENT_DATA"]["EVENT_OPTIONS"] ?? null)?>,
			OPEN_TIME: <?=CUtil::PhpToJsObject($arResult["COMPONENT_DATA"]["OPEN_TIME"])?>
		},
		can: {
			TASK: <?= CUtil::PhpToJsObject($arResult['CAN']['TASK']) ?>
		},
		paramsToLazyLoadTabs: <?= \Bitrix\Main\Web\Json::encode($arResult['PARAMS_TO_LAZY_LOAD_TABS']) ?>,
		workHours: {
			start: {
				hours: <?= (int)$arResult['WORK_TIME']['START']['H'] ?>,
				minutes: <?= (int)$arResult['WORK_TIME']['START']['M'] ?>
			},
			end: {
				hours: <?= (int)$arResult['WORK_TIME']['END']['H'] ?>,
				minutes: <?= (int)$arResult['WORK_TIME']['END']['M'] ?>
			}
		},
		workSettings: <?= CUtil::PhpToJSObject($arResult['WORK_SETTINGS']) ?>
	});

	if (window.B24) {
		B24.updateCounters({"tasks_total": <?=(int)CUserCounter::GetValue($USER->GetID(), 'tasks_total')?>});
	}
</script>