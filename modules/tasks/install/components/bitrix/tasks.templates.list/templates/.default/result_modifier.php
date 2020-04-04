<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Main\Grid;
use Bitrix\Tasks\Util\UserField;

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.templates.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");
CJSCore::Init(array('tasks_util_query', 'task_popups'));

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent(
)->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if ($helper->checkHasFatals())
{
	return;
}

if ($arParams['SET_TITLE'] != 'N')
{
	//region TITLE
	$sTitle = $sTitleShort = GetMessage("TASKS_TEMPLATES_TITLE");
	$APPLICATION->SetPageProperty("title", $sTitle);
	$APPLICATION->SetTitle($sTitleShort);
	//endregion TITLE
}

$arResult['TEMPLATE_DATA'] = array(// contains data generated in result_modifier.php
);

$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATE'];

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
		),
		'view' => CComponentEngine::MakePathFromTemplate(
			$taskTemplateUrlTemplate,
			array(
				"user_id" => '\d+',
				"template_id" => '\d+',
				"action" => "view"
			)
		),
	]
];

/**
 * @param $row
 * @param $arParams
 *
 * @return array
 */
function prepareTaskRowActions($row, $arParams)
{
	$userId = Util\User::getId();

	$urlPath = $arParams['PATH_TO_USER_TASKS_TEMPLATES'];
	$urlPathAction = $arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'];
	$urlTaskPath = $arParams['PATH_TO_USER_TASKS_TASK'];

	$actions = [];

	if ($row['ALLOWED_ACTIONS']['READ'])
	{
		$actions[] = [
			"text" => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_VIEW'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlPathAction,
				[
					'user_id'     => $userId,
					'action'      => 'view',
					'template_id' => $row['ID']
				]
			)
		];
		$actions[] = [
			"text" => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_COPY'),
			'href' => CComponentEngine::MakePathFromTemplate(
					$urlPathAction,
					[
						'user_id'     => $userId,
						'action'      => 'edit',
						'template_id' => 0
					]
				).'?COPY='.$row['ID']
		];
	}

	if ($row['ALLOWED_ACTIONS']['UPDATE'])
	{
		$actions[] = [
			"text" => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_CREATE_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate(
					$urlTaskPath,
					[
						'user_id' => $userId,
						'action'  => 'edit',
						'task_id' => 0
				]
				).'?TEMPLATE='.$row['ID']
		];
		$actions[] = [
			"text" => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_CREATE_SUB_TEMPLATE'),
			'href' => CComponentEngine::MakePathFromTemplate(
					$urlPathAction,
					[
						'user_id'     => $userId,
						'action'      => 'edit',
						'template_id' => 0
					]
				).'?BASE_TEMPLATE='.$row['ID']
		];
		$actions[] = [
			"text" => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_EDIT'),
			'href' => CComponentEngine::MakePathFromTemplate(
				$urlPathAction,
				[
					'user_id'     => $userId,
					'action'      => 'edit',
					'template_id' => $row['ID']
				]
			)
		];
	}

	if ($row['ALLOWED_ACTIONS']['DELETE'])
	{
		$actions[] = [
			"text"    => GetMessageJS('TASKS_TEMPLATES_ROW_ACTION_DELETE'),
			'onclick' => 'DeleteTemplate('.$row['ID'].');'
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
		return GetMessage('TASKS_TEMPLATE_REGULAR_NO');
	}

	return \Bitrix\Tasks\UI\Task\Template::makeReplicationPeriodString($row['REPLICATE_PARAMS']);
}

function prepareCRM($row, $arParams)
{
	$collection = array();
	if (!array_key_exists('UF_CRM_TASK', $row) || !is_array($row['UF_CRM_TASK']))
	{
		return;
	}

	sort($row['UF_CRM_TASK']);
	foreach ($row['UF_CRM_TASK'] as $value)
	{
		$crmElement = explode('_', $value);
		$type = $crmElement[0];
		$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
		$title = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);
		$url = CCrmOwnerType::GetShowUrl($typeId, $crmElement[1]);

		if (!isset($collection[$type]))
		{
			$collection[$type] = array();
		}

		if ($title)
		{
			$collection[$type][] = '<a href="'.$url.'">'.htmlspecialcharsbx($title).'</a>';
		}
	}

	ob_start();
	if ($collection)
	{
		echo '<div class="tasks-list-crm-div">';
		$prevType = null;
		foreach ($collection as $type => $items)
		{
			if (empty($items))
			{
				continue;
			}

			echo '<div class="tasks-list-crm-div-wrapper">';
			if ($type !== $prevType)
			{
				echo '<span class="tasks-list-crm-div-type">'.GetMessage('TASKS_LIST_CRM_TYPE_'.$type).':</span>';
			}

			$prevType = $type;

			echo implode(', ', $items);
			echo '</div>';
		}
		echo '</div>';
	}

	return ob_get_clean();
}

function prepareUF($fieldName, $row, $arParams)
{
	$fieldValue = $row[$fieldName];
	$userFieldData = $arParams['UF'][$fieldName];

	if ($userFieldData['USER_TYPE_ID'] !== 'boolean' && empty($fieldValue) && $fieldValue !== '0')
	{
		return GetMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_NOT_PRESENTED');
	}

	if ($fieldName == 'UF_CRM_TASK')
	{
		return prepareCRM($row, $arParams);
	}

	if ($userFieldData['USER_TYPE_ID'] == 'boolean')
	{
		$fieldValue = (empty($fieldValue)? GetMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_NO') : GetMessage('TASKS_TEMPLATE_UF_FIELD_BOOLEAN_YES'));
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
		return GetMessageJS('TASKS_TEMPLATES_NO');
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
			'.($user['AVATAR'] ? 'style="background-image: url(\''.$user['AVATAR'].'\')"' : '').'></span>';

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
	$list = array();

	if (!array_key_exists('TAGS', $row) || !is_array($row['TAGS']))
	{
		return '';
	}

	foreach ($row['TAGS'] as $tag)
	{
		//		$list[] = '<a href="javascript:;" onclick="BX.Tasks.GridActions.filter(\''.$tag.'\')">#'.$tag.'</a>';
		$list[] = '<a href="javascript:;">#'.$tag.'</a>';
	}

	return join(', ', $list);
}

function prepareTaskTemplateRow($row, $arParams)
{
	$group = SocialNetwork\Group::getData(array($row['GROUP_ID']));
	$groupName = htmlspecialcharsbx($group[$row['GROUP_ID']]['NAME']);

	$originatorUrl = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array("user_id" => $row["CREATED_BY"])
	);
	$responsibleUrl = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array("user_id" => $row["RESPONSIBLE_ID"])
	);

	$groupUrl = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_GROUP'],
		array("group_id" => $row["GROUP_ID"])
	);

	$resultRow = array(
		'ID' => $row['ID'],
		'PARENT_ID' => $row['PARENT_ID'],
		'TASKS_TEMPLATE_TITLE' => prepareTaskTemplateRowTitle($row, $arParams),

		'TASKS_TEMPLATE_DEADLINE_AFTER' => $row['DEADLINE_AFTER'] > 0 ? GetMessage(
			'TASKS_TEMPLATE_DEADLINE_AFTER',
			[
				'#DATE#' => getDateAfter(new DateTime(), $row['DEADLINE_AFTER']),
				'#AFTER#' => FormatDate(
					array('i' => 'idiff', 'H' => 'Hdiff', 'd' => 'ddiff', 'm' => 'mdiff', 'Y' => 'Ydiff'),
					time() - $row['DEADLINE_AFTER']
				)
			]
		) : GetMessage('TASKS_TEMPLATES_NO'),

		'TASKS_TEMPLATE_CREATED_BY' => prepareTaskRowUserBaloonHtml(
			array(
				'PREFIX'           => 'TASKS_TEMPLATE_CREATED_BY_'.$row['ID'],
				'USER_NAME'        => $arParams['~USER_NAMES'][$row['CREATED_BY']],
				'USER_PROFILE_URL' => $originatorUrl,
				'USER_ID'          => $row['CREATED_BY'],
				'TEMPLATE_ID'      => $row['ID']
			)
		),
		'TASKS_TEMPLATE_RESPONSIBLE_ID' => prepareTaskRowUserBaloonHtml(
			array(
				'PREFIX'           => 'TASKS_TEMPLATE_RESPONSIBLE_ID_'.$row['ID'],
				'USER_NAME'        => $arParams['~USER_NAMES'][$row['RESPONSIBLE_ID']],
				'USER_PROFILE_URL' => $responsibleUrl,
				'USER_ID'          => $row['RESPONSIBLE_ID'],
				'TEMPLATE_ID'      => $row['ID']
			)
		),
		'TASKS_TEMPLATE_GROUP_ID' => '<a href="'.$groupUrl.'" target="_blank">'.$groupName.'</a>',
		'TASKS_TEMPLATE_REGULAR' => prepareTaskTemplateRegular($row, $arParams),
		'TASKS_TEMPLATE_FOR_NEW_USER' => $row['RESPONSIBLE_ID'] == 0
			? GetMessage('TASKS_TEMPLATES_YES')
			: GetMessage(
				'TASKS_TEMPLATES_NO'
			),

		//		'PRIORITY' => GetMessage('TASKS_PRIORITY_'.$row['PRIORITY']),
		'TASKS_TEMPLATE_TAGS' => prepareTag($row, $arParams),
	);

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
			array('NAME' => GetMessage('TASKS_TEMPLATE_LIST_CHOOSE_ACTION'), 'VALUE' => 'none')
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
			'NAME' => GetMessage('TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE'),
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
							"TEXT" => GetMessage("TASKS_TEMPLATE_LIST_GROUP_ACTION_REMOVE"),
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
			'has_child' => $row['CHILDS_COUNT'] > 0 && !$arResult['IS_SEARCH_MODE'],
			'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() ? $row['BASE_TEMPLATE_ID'] : 0,
			"parent_group_id" => $row["GROUP_ID"],
			'actions' => prepareTaskRowActions($row, $arParams),
			'attrs' => array(
				"data-type" => "task-template",
				"data-group-id" => $row['GROUP_ID'],
				//				"data-can-edit" => true,//$row['ACTION']['EDIT'] === true ? "true" : "false"
			),
			'columns' => prepareTaskTemplateRow($row, $arParams)
		);

		$arResult['ROWS'][] = $rowItem;

		$prevGroupId = (int)$row['GROUP_ID'];
	}
}
$arResult['GROUP_ACTIONS'] = prepareTaskTemplateroupActions($arResult, $arParams);