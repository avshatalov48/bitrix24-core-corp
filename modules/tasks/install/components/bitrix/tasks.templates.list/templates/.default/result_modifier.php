<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams*/
/** @var array $arResult*/
/** @global CMain $APPLICATION*/

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Slider\Path\TemplatePathMaker;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\UI\Component\TemplateHelper;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.templates.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");
CJSCore::Init(array('tasks_util_query', 'task_popups'));

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');
$arParams =& $helper->getComponent(
)->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if ($helper->checkHasFatals())
{
	return;
}

if ($arParams['SET_TITLE'] != 'N')
{
	//region TITLE
	$sTitle = $sTitleShort = Loc::getMessage('TASKS_TEMPLATES_TITLE');
	$APPLICATION->SetPageProperty('title', $sTitle);
	$APPLICATION->SetTitle($sTitleShort);
	//endregion TITLE
}

$arResult['TEMPLATE_DATA'] = array(// contains data generated in result_modifier.php
);

$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATE'];


$strIframe = '';
if(isset($_REQUEST['IFRAME']))
{
    $strIframe = '?IFRAME='.($_REQUEST['IFRAME'] == 'Y' ? 'Y' : 'N');
}
$arResult['JS_DATA'] = [
	'gridId' => $arParams['GRID_ID'],
	'patternsUrl' => [
		'create' => CComponentEngine::MakePathFromTemplate(
			$taskTemplateUrlTemplate,
			array(
				"user_id" => '\d+',
				"template_id" => 0,
				"action" => "edit"
			)
		).$strIframe,
		'view' => CComponentEngine::MakePathFromTemplate(
			$taskTemplateUrlTemplate,
			array(
				"user_id" => '\d+',
				"template_id" => '\d+',
				"action" => "view"
			)
		).$strIframe,
	]
];

/**
 * @param $row
 * @param $arParams
 * @param $arResult
 * @return array
 */
function prepareTaskRowActions($row, $arParams, $arResult)
{
    $strIframe = '';
    $strIframe2 = '';

    if (isset($_REQUEST['IFRAME']))
    {
		$iframe = ($_REQUEST['IFRAME'] === 'Y' ? 'Y' : 'N');

        $strIframe = "?IFRAME={$iframe}";
        $strIframe2 = "&IFRAME={$iframe}";
    }

	$userId = Util\User::getId();
	$urlPathAction = $arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'];
	$urlTaskPath = $arParams['PATH_TO_USER_TASKS_TASK'];

	$newTemplateLink = CComponentEngine::MakePathFromTemplate(
		$urlPathAction,
		[
			'user_id' => $userId,
			'action' => 'edit',
			'template_id' => 0,
		]
	);

	$allowedActions = $row['ALLOWED_ACTIONS'];
	$actions = [];

	if (
		array_key_exists(ActionDictionary::ACTION_TEMPLATE_READ, $allowedActions)
		&& $allowedActions[ActionDictionary::ACTION_TEMPLATE_READ]
	)
	{
		$actions[] = [
			'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_VIEW'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlPathAction,
				[
					'user_id' => $userId,
					'action' => 'view',
					'template_id' => $row['ID'],
				]
			) . $strIframe,
		];

		$canCreate =
			array_key_exists(ActionDictionary::ACTION_TEMPLATE_CREATE, $allowedActions)
			&& $allowedActions[ActionDictionary::ACTION_TEMPLATE_CREATE]
		;

		if ($canCreate)
		{
			$actions[] = [
				'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_COPY'),
				'href' => "{$newTemplateLink}?COPY={$row['ID']}{$strIframe2}",
			];
		}

		$actions[] = [
			'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_CREATE_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlTaskPath,
				[
					'user_id' => $userId,
					'action' => 'edit',
					'task_id' => 0,
				]
			) . "?TEMPLATE={$row['ID']}{$strIframe2}",
		];

		if ($canCreate)
		{
			$addSubTemplateAction = [
				'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_CREATE_SUB_TEMPLATE'),
			];
			if ($arResult['AUX_DATA']['TEMPLATE_SUBTASK_LIMIT_EXCEEDED'])
			{
				$addSubTemplateAction['onclick'] =
					"BX.UI.InfoHelper.show('" . RestrictionUrl::TEMPLATE_LIMIT_SUBTASKS_SLIDER_URL . "', {
					isLimit: true,
					limitAnalyticsLabels: {
						module: 'tasks',
						source: 'templateList'
					}});"
				;
				$addSubTemplateAction['className'] = 'tasks-list-menu-popup-item-lock';
			}
			else
			{
				$addSubTemplateAction['href'] = "{$newTemplateLink}?BASE_TEMPLATE={$row['ID']}{$strIframe2}";
			}

			$actions[] = $addSubTemplateAction;
		}
	}

	if (
		array_key_exists(ActionDictionary::ACTION_TEMPLATE_EDIT, $allowedActions)
		&& $allowedActions[ActionDictionary::ACTION_TEMPLATE_EDIT]
	)
	{
		$actions[] = [
			'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_EDIT'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlPathAction,
				[
					'user_id' => $userId,
					'action' => 'edit',
					'template_id' => $row['ID'],
				]
			) . $strIframe,
		];
	}

	if (
		array_key_exists(ActionDictionary::ACTION_TEMPLATE_REMOVE, $allowedActions)
		&& $allowedActions[ActionDictionary::ACTION_TEMPLATE_REMOVE]
	)
	{
		$actions[] = [
			'text' => Loc::getMessage('TASKS_TEMPLATES_ROW_ACTION_DELETE'),
			'onclick' => "DeleteTemplate({$row['ID']});",
		];
	}

	return $actions;
}

function prepareTaskTemplateRowTitle($row, $arParams)
{
	$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATE'];

	$taskTemplateUrl = CComponentEngine::MakePathFromTemplate(
		$taskTemplateUrlTemplate,
		array(
			"user_id" => $arParams['USER_ID'],
			"template_id" => $row["ID"],
			"action" => "view"
		)
	);

	$title = '<a href="'.$taskTemplateUrl.'" class="task-title">'.htmlspecialcharsbx($row['TITLE']).'</a> ';

	return $title;
}

function prepareTaskTemplateRegular($row, $arParams)
{
	if ($row['REPLICATE'] != 'Y')
	{
		return Loc::getMessage('TASKS_TEMPLATE_REGULAR_NO');
	}

	return \Bitrix\Tasks\UI\Task\Template::makeReplicationPeriodString($row['REPLICATE_PARAMS']);
}

function prepareCRM($row, $arParams)
{
	if (!array_key_exists('UF_CRM_TASK', $row) || !is_array($row['UF_CRM_TASK']))
	{
		return '';
	}

	sort($row['UF_CRM_TASK']);

	$collection = [];
	foreach ($row['UF_CRM_TASK'] as $value)
	{
		[$type, $id] = explode('_', $value);
		$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
		$title = CCrmOwnerType::GetCaption($typeId, $id);
		$url = CCrmOwnerType::GetShowUrl($typeId, $id);

		if (!isset($collection[$typeId]))
		{
			$collection[$typeId] = [];
		}

		if ($title)
		{
			$safeTitle = htmlspecialcharsbx($title);
			$collection[$typeId][] = "<a href=\"{$url}\">{$safeTitle}</a>";
		}
	}

	$html = [];
	if ($collection)
	{
		$html[] = '<div class="tasks-list-crm-div">';
		$previousTypeId = null;

		foreach ($collection as $typeId => $items)
		{
			if (empty($items))
			{
				continue;
			}

			$html[] = '<div class="tasks-list-crm-div-wrapper">';
			if ($typeId !== $previousTypeId)
			{
				$factory = Container::getInstance()->getFactory($typeId);
				$typeTitle = ($factory ? $factory->getEntityDescription() : '');
				$html[] = "<span class='tasks-list-crm-div-type'>{$typeTitle}:</span>";
			}
			$html[] = implode(', ', $items);
			$html[] = '</div>';

			$previousTypeId = $typeId;
		}
		$html[] = '</div>';
	}

	return implode('', $html);
}

function prepareUF($fieldName, $row, $arParams)
{
	$fieldValue = $row[$fieldName];
	$userFieldData = $arParams['UF'][$fieldName];

	if ($userFieldData['USER_TYPE_ID'] !== 'boolean' && empty($fieldValue) && $fieldValue !== '0')
	{
		return Loc::getMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_NOT_PRESENTED');
	}

	if ($fieldName == 'UF_CRM_TASK')
	{
		return prepareCRM($row, $arParams);
	}

	if ($userFieldData['USER_TYPE_ID'] == 'boolean')
	{
		$fieldValue = (empty($fieldValue)? Loc::getMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_NO') : Loc::getMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_YES'));
	}

	if (is_array($fieldValue))
	{
		return join(
			', ',
			array_map(
				function($item) {
					return htmlspecialcharsbx($item);
				},
				$fieldValue
			)
		);
	}
	else
	{
		return htmlspecialcharsbx($fieldValue);
	}
}

function prepareTaskRowUserBaloonHtml($arParams)
{
	if ($arParams['USER_ID'] == 0)
	{
		return Loc::getMessage('TASKS_TEMPLATES_NO');
	}

	$users = Util\User::getData(array($arParams['USER_ID']));
	$user = $users[$arParams['USER_ID']];

	$user['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
	$user['IS_EXTERNAL'] = Util\User::isExternalUser($user['ID']);
	$user['IS_CRM'] = array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY']);

	$arParams['USER_PROFILE_URL'] = $user['IS_EXTERNAL'] ? Socialnetwork\Task::addContextToURL(
		$arParams['USER_PROFILE_URL'],
		$arParams['TASK_ID']
	) : $arParams['USER_PROFILE_URL'];

	$userIcon = '';
	if ($user['IS_EXTERNAL'])
	{
		$userIcon = 'tasks-grid-avatar-extranet';
	}
	if ($user["EXTERNAL_AUTH_ID"] == 'email')
	{
		$userIcon = 'tasks-grid-avatar-mail';
	}
	if ($user["IS_CRM"])
	{
		$userIcon = 'tasks-grid-avatar-crm';
	}

	$userAvatar = 'tasks-grid-avatar-empty';
	if ($user['AVATAR'])
	{
		$userAvatar = '';
	}

	$userName = '<span class="tasks-grid-avatar  '.$userAvatar.' '.$userIcon.'" 
			'.($user['AVATAR'] ? 'style="background-image: url(\''.Uri::urnEncode($user['AVATAR']).'\')"' : '').'></span>';

	$userName .= '<span class="tasks-grid-username-inner '.
				 $userIcon.
				 '">'.
				 htmlspecialcharsbx($arParams['USER_NAME']).
				 '</span>';

	$profilePath = isset($arParams['USER_PROFILE_URL']) ? $arParams['USER_PROFILE_URL'] : '';

	return '<div class="tasks-grid-username-wrapper"><a href="'.
		   htmlspecialcharsbx($profilePath).
		   '" class="tasks-grid-username">'.
		   $userName.
		   '</a></div>';
}

function getDateAfter(DateTime $now, $seconds)
{
	$seconds = intval($seconds);

	if ($seconds)
	{
		$then = clone $now;
		$then->add('T'.$seconds.'S');
		$then->stripSeconds();

		return $then;
	}

	return '';
}

function prepareTag($row, $arParams)
{
	$list = [];

	if (!array_key_exists('TAGS', $row) || !is_array($row['TAGS']))
	{
		return '';
	}

	foreach ($row['TAGS'] as $tag)
	{
		$safeTag = htmlspecialcharsbx($tag);
		$encodedData = Json::encode([
			'tag' => $tag,
		]);
		$onClick = "BX.Tasks.Component.TasksTemplatesList.getInstance().toggleFilter({$encodedData})";
		$list[] = "<a class=\"tasks-templates-list-grid-tag\" onclick='{$onClick}'>#{$safeTag}</a>";
	}

	return implode(', ', $list);
}

function prepareTaskTemplateRow($row, $arParams)
{
	$templateId = $row['ID'];

	$groupId = $row['GROUP_ID'];
	$group = SocialNetwork\Group::getData([$groupId]);
	$groupName = empty($group) ? '' : htmlspecialcharsbx($group[$groupId]['NAME']);
	$groupUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_GROUP'], ['group_id' => $groupId]);

	$createdBy = (int)$row['CREATED_BY'];
	$responsibleId = (int)$row['RESPONSIBLE_ID'];
	$isForNewUser = ($responsibleId === 0 && (int)$row['TPARAM_TYPE'] === 1);

	$resultRow = [
		'ID' => $templateId,
		'PARENT_ID' => $row['PARENT_ID'],
		'TASKS_TEMPLATE_TITLE' => prepareTaskTemplateRowTitle($row, $arParams),
		'TASKS_TEMPLATE_DEADLINE_AFTER' =>
			$row['DEADLINE_AFTER'] > 0
				? Loc::getMessage(
					'TASKS_TEMPLATE_DEADLINE_AFTER2',
					['#AFTER#' => TemplateHelper::formatDateAfter(($row['MATCH_WORK_TIME'] === 'Y'), $row['DEADLINE_AFTER'])]
				)
				: Loc::getMessage('TASKS_TEMPLATES_NO')
		,
		'TASKS_TEMPLATE_CREATED_BY' => prepareTaskRowUserBaloonHtml([
			'PREFIX' => "TASKS_TEMPLATE_CREATED_BY_{$templateId}",
			'USER_NAME' => $arParams['~USER_NAMES'][$createdBy],
			'USER_PROFILE_URL' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_USER_PROFILE'],
				['user_id' => $createdBy]
			),
			'USER_ID' => $createdBy,
			'TEMPLATE_ID' => $templateId,
		]),
		'TASKS_TEMPLATE_RESPONSIBLE_ID' => prepareTaskRowUserBaloonHtml([
			'PREFIX' => "TASKS_TEMPLATE_RESPONSIBLE_ID_{$templateId}",
			'USER_NAME' => $arParams['~USER_NAMES'][$responsibleId],
			'USER_PROFILE_URL' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_USER_PROFILE'],
				['user_id' => $responsibleId]
			),
			'USER_ID' => $responsibleId,
			'TEMPLATE_ID' => $templateId,
		]),
		'TASKS_TEMPLATE_GROUP_ID' => "<a href='{$groupUrl}' target='_blank'>{$groupName}</a>",
		'TASKS_TEMPLATE_REGULAR' => prepareTaskTemplateRegular($row, $arParams),
		'TASKS_TEMPLATE_FOR_NEW_USER' => Loc::getMessage(('TASKS_TEMPLATES_' . ($isForNewUser ? 'YES' : 'NO'))),
		'TASKS_TEMPLATE_TAGS' => prepareTag($row, $arParams),
	];

	foreach ($arParams['UF'] as $ufName => $ufItem)
	{
		$resultRow[$ufName] = prepareUF($ufName, $row, $arParams);
	}

	return $resultRow;
}

if (!function_exists('prepareTaskTemplateroupActions'))
{
	function prepareTaskTemplateroupActions($arResult, $arParams)
	{
		$prefix = $arParams['GRID_ID'];
		$snippet = new Grid\Panel\Snippet();

		$actionList = array(
			array('NAME' => Loc::getMessage('TASKS_TEMPLATE_LIST_CHOOSE_ACTION'), 'VALUE' => 'none')
		);

		$applyButton = $snippet->getApplyButton(
			array(
				'ONCHANGE' => array(
					array(
						'ACTION' => Grid\Panel\Actions::CALLBACK,
						'DATA' => array(
							array(
								'JS' => 'BX.TasksTemplatesList.GridActions.confirmGroupAction(\''.$arParams['GRID_ID'].'\')'
							)
						)
					)
				)
			)
		);

		$actionList[] = array(
			'NAME' => Loc::getMessage('TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Grid\Panel\Actions::RESET_CONTROLS
				)
			)
		);  // remove task

		$groupActions = array(
			'GROUPS' => array(
				array(
					'ITEMS' => array(
						[
							"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
							"TEXT" => Loc::getMessage("TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE"),
							"VALUE" => "start_call_list",
							"ONCHANGE" => array(
								array(
									"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
									"DATA" => array(array('JS' => "BX.Tasks.Component.TasksTemplatesList.getInstance().deleteSelected();"))
								)
							)
						],
						$snippet->getForAllCheckbox()
					)
				)
			)
		);

		return $groupActions;
	}
}

$arResult['ROWS'] = array();
if (!empty($arResult['GRID']['DATA']))
{

	$users = [];
	foreach ($arResult['GRID']['DATA'] as $row)
	{
		$users[] = $row['CREATED_BY'];
		$users[] = $row['RESPONSIBLE_ID'];
	}
	$arParams['~USER_NAMES'] = \Bitrix\Tasks\Util\User::getUserName(array_unique($users));
	$arParams['~USER_DATA'] = Util\User::getData(array_unique($users));


	$prevGroupId = 0;
	foreach ($arResult['GRID']['DATA'] as $row)
	{
		if ($prevGroupId != (int)$row['GROUP_ID'])
		{
			$group = SocialNetwork\Group::getData(array($row['GROUP_ID']));
			$groupName = htmlspecialcharsbx($group[$row['GROUP_ID']]['NAME']);
			$groupUrl = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_GROUP'],
				array("group_id" => $row["GROUP_ID"])
			);

			$groupItem = array(
				"id" => 'group_'.$row["GROUP_ID"],
				"has_child" => true,
				"parent_id" => 0,
				"custom" => '<div class="tasks-grid-wrapper"><a href="'.
							$groupUrl.
							'" class="tasks-grid-group-link">'.
							$groupName.
							'</a></div>',
				"not_count" => true,
				"draggable" => false,
				"group_id" => $row["GROUP_ID"],
				"attrs" => array(
					"data-type" => "group",
					"data-group-id" => $row["GROUP_ID"],
					//					"data-can-create-tasks" => SocialNetwork\Group::can(
					//						$row["GROUP_ID"],
					//						SocialNetwork\Group::ACTION_CREATE_TASKS
					//					) ? "true" : "false",
					//					"data-can-edit-tasks" => SocialNetwork\Group::can(
					//						$row["GROUP_ID"],
					//						SocialNetwork\Group::ACTION_EDIT_TASKS
					//					) ? "true" : "false"
				)
			);

			$arResult['ROWS'][] = $groupItem;
		}

		$rowItem = array(
			"id" => $row["ID"],
			'has_child' => isset($row['CHILDS_COUNT']) && $row['CHILDS_COUNT'] > 0 && !$arResult['IS_SEARCH_MODE'],
			'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() ? $row['BASE_TEMPLATE_ID'] : 0,
			"parent_group_id" => $row["GROUP_ID"],
			'actions' => prepareTaskRowActions($row, $arParams, $arResult),
			'attrs' => array(
				"data-type" => "task-template",
				"data-group-id" => $row['GROUP_ID'],
				//				"data-can-edit" => true,//$row['ACTION']['EDIT'] === true ? "true" : "false"
			),
			'columns' => prepareTaskTemplateRow($row, $arParams),
		);

		$arResult['ROWS'][] = $rowItem;

		$prevGroupId = (int)$row['GROUP_ID'];
	}
}
$arResult['GROUP_ACTIONS'] = prepareTaskTemplateroupActions($arResult, $arParams);