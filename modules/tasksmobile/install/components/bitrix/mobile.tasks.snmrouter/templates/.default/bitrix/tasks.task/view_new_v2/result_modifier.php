<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var TasksBaseComponent $component
 * @global CUser $USER
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Util;

$templateData = [];

if (!empty($arResult['ERROR']) && is_array($arResult['ERROR']))
{
	foreach ($arResult['ERROR'] as $error)
	{
		if ($error['TYPE'] === 'FATAL')
		{
			$templateData['ERROR'] = $error;
			$arResult['TEMPLATE_DATA'] = $templateData;
			return;
		}
	}
}

$taskData = $arResult['DATA']['TASK'];
if (empty($taskData) || !isset($taskData['ID']))
{
	$templateData['ERROR'] = [
		'TYPE' => 'FATAL',
		'MESSAGE' => Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'),
	];
	$arResult['TEMPLATE_DATA'] = $templateData;
	return;
}

$component = $this->__component;

// User Name Template
$nameTemplate = empty($arParams['NAME_TEMPLATE'])
	? CSite::GetNameFormat(false)
	: str_replace(['#NOBR#','#/NOBR#'], ['', ''], $arParams['NAME_TEMPLATE'])
;
$arParams['NAME_TEMPLATE'] = $templateData['NAME_TEMPLATE'] = $nameTemplate;

$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?? null);
$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?: 58);

// Task Paths
if ($arParams['GROUP_ID'] > 0)
{
	$arParams['PATH_TO_TASKS'] = str_replace(
		['#group_id#', '#GROUP_ID#'],
		$arParams['GROUP_ID'],
		$arParams['PATH_TO_GROUP_TASKS']
	);
	$arParams['PATH_TO_TASKS_TASK'] = str_replace(
		['#group_id#', '#GROUP_ID#'],
		$arParams['GROUP_ID'],
		$arParams['PATH_TO_GROUP_TASKS_TASK']
	);
	$templateData['TASK_TYPE'] = 'group';
}
else
{
	$arParams['PATH_TO_TASKS'] = str_replace(
		['#user_id#', '#USER_ID#'],
		$arParams['USER_ID'],
		$arParams['PATH_TO_USER_TASKS']
	);
	$arParams['PATH_TO_TASKS_TASK'] = str_replace(
		['#user_id#', '#USER_ID#'],
		$arParams['USER_ID'],
		$arParams['PATH_TO_USER_TASKS_TASK']
	);
	$templateData['TASK_TYPE'] = 'user';
}

if (
	!empty($arParams['TASK_URL_PARAMETERS'])
	&& is_array($arParams['TASK_URL_PARAMETERS'])
	&& (string)$arParams['PATH_TO_TASKS_TASK'] !== ''
)
{
	$arParams['PATH_TO_TASKS_TASK'] = Util::replaceUrlParameters(
		$arParams['PATH_TO_TASKS_TASK'],
		$arParams['TASK_URL_PARAMETERS']
	);
}

// Template Paths
$arParams['PATH_TO_TEMPLATES_TEMPLATE'] = str_replace(
	['#user_id#', '#USER_ID#'],
	$arParams['USER_ID'],
	$arParams['PATH_TO_USER_TEMPLATES_TEMPLATE']
);
$arParams['PATH_TO_TASKS_TEMPLATES'] = str_replace(
	['#user_id#', '#USER_ID#'],
	$arParams['USER_ID'],
	$arParams['PATH_TO_USER_TASKS_TEMPLATES']
);

// New Task Path
$templateData['NEW_TASK_PATH'] = CComponentEngine::makePathFromTemplate(
	$arParams['PATH_TO_TASKS_TASK'],
	[
		'task_id' => 0,
		'action' => 'edit',
	]
);
$templateData['NEW_SUBTASK_PATH'] = $templateData['NEW_TASK_PATH']
	.(mb_strpos($templateData['NEW_TASK_PATH'], '?') === false ? '?' : '&').'PARENT_ID='.$taskData['ID'];

// Rating
$arResult['RATING'] = $templateData['RATING'] = CRatings::GetRatingVoteResult('TASK', $taskData['ID']);

// Group
if (array_key_exists('GROUP', $arResult['DATA']) && is_array($arResult['DATA']['GROUP']))
{
	$groups = $arResult['DATA']['GROUP'];
	foreach ($groups as $id => $data)
	{
		$avatar = null;

		if ($data['IMAGE_ID'])
		{
			$arFileTmp = CFile::ResizeImageGet(
				$data['IMAGE_ID'],
				[
					'width' => $arParams['AVATAR_SIZE'],
					'height' => $arParams['AVATAR_SIZE'],
				],
				BX_RESIZE_IMAGE_EXACT
			);
			$avatar = $arFileTmp['src'];
		}
		else if ($data['AVATAR_TYPE'])
		{
			$avatarTypes = \Bitrix\Socialnetwork\Helper\Workgroup::getAvatarTypes();
			$avatar = $avatarTypes[$data['AVATAR_TYPE']]['mobileUrl'];
		}
		$groups[$id]['AVATAR'] = $avatar;
	}
	$arResult['DATA']['GROUP'] = $groups;
}
$templateData['GROUP_URL_TEMPLATE'] = CComponentEngine::makePathFromTemplate(
	$arParams['PATH_TO_GROUP'],
	['group_id' => '{{VALUE}}']
);
$templateData['GROUP'] = null;
if (
	$taskData['GROUP_ID']
	&& isset($arResult['DATA']['GROUP'][$taskData['GROUP_ID']])
	&& CSocNetGroup::CanUserViewGroup($USER->getID(), $taskData['GROUP_ID'])
)
{
	$group = $arResult['DATA']['GROUP'][$taskData['GROUP_ID']];
	$templateData['GROUP'] = [
		'ID' => $group['ID'],
		'NAME' => $group['NAME'],
		'AVATAR' => $group['AVATAR'],
		'URL' => CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_GROUP'],
			['group_id' => $taskData['GROUP_ID']]
		),
	];
}

// Parent Task
$templateData['RELATED_TASK'] = [];
if (isset($arResult['DATA']['RELATED_TASK'][$taskData['PARENT_ID']]))
{
	$templateData['RELATED_TASK'] = $arResult['DATA']['RELATED_TASK'][$taskData['PARENT_ID']];
	$templateData['RELATED_TASK']['URL'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_TASKS_TASK'],
		[
			'task_id' => $taskData['PARENT_ID'],
			'action' => 'view',
		]
	);
}

// SubTasks
$subtasks = CTasks::GetList(['GROUP_ID' => 'ASC'], ['PARENT_ID' => $taskData['ID']], [], ['nPageTop' => 1]);
$templateData['SUBTASKS_EXIST'] = $subtasks->Fetch() !== false;

// Predecessors
$templateData['PREDECESSORS'] = [];
foreach ($taskData['SE_PROJECTDEPENDENCE'] as $dependency)
{
	$depTaskId = $dependency['SE_DEPENDS_ON']['ID'];
	if (isset($arResult['DATA']['RELATED_TASK'][$depTaskId]))
	{
		$depTask = $arResult['DATA']['RELATED_TASK'][$depTaskId];
		$depTask['TASK_URL'] = CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_TASKS_TASK'],
			[
				'task_id' => $depTaskId,
				'action' => 'view',
			]
		);

		$type = (int)$dependency['TYPE'];
		$dependencyTypeMap = [
			0 => ['TASKS_DEPENDENCY_START', 'TASKS_DEPENDENCY_START'],
			1 => ['TASKS_DEPENDENCY_START', 'TASKS_DEPENDENCY_END'],
			2 => ['TASKS_DEPENDENCY_END', 'TASKS_DEPENDENCY_START'],
			3 => ['TASKS_DEPENDENCY_END', 'TASKS_DEPENDENCY_END'],
		];
		if (isset($dependencyTypeMap[$type]))
		{
			[$start, $end] = $dependencyTypeMap[$type];
			$depTask['DEPENDENCY_TYPE'] = Loc::getMessage($start).'-'.Loc::getMessage($end);
		}

		$templateData['PREDECESSORS'][] = $depTask;
	}
}

// Previous Tasks
$templateData['PREV_TASKS'] = [];
$prevTaskIds = [];
$prevTasks = CTaskDependence::getList([], ['TASK_ID' => $taskData['ID']]);
while ($item = $prevTasks->Fetch())
{
	$prevTaskIds[] = (int)$item['DEPENDS_ON_ID'];
}
if (!empty($prevTaskIds))
{
	$prevTasks = CTasks::GetList(['GROUP_ID' => 'ASC'], ['ID' => $prevTaskIds]);
	while ($item = $prevTasks->Fetch())
	{
		$item['RESPONSIBLE_URL'] = CComponentEngine::makePathFromTemplate(
			$arParams['~PATH_TO_USER_PROFILE'],
			['user_id' => $item['RESPONSIBLE_ID']]
		);
		$item['TASK_URL'] = CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_TASKS_TASK'],
			[
				'task_id' => $item['ID'],
				'action' => 'view',
			]
		);
		$item['RESPONSIBLE_FORMATTED_NAME'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'],
			[
				'NAME' => $item['RESPONSIBLE_NAME'],
				'LAST_NAME' => $item['RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME' => $item['RESPONSIBLE_SECOND_NAME'],
				'LOGIN' => $item['RESPONSIBLE_LOGIN'],
			],
			true,
			false
		);

		$templateData['PREV_TASKS'][] = $item;
	}
}

$isTimerRunning = ($taskData['TIMER_IS_RUNNING'] ?? null);
$templateData['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = ($isTimerRunning ? 'Y' : 'N');

// Description
$taskData['~DESCRIPTION'] = $taskData['DESCRIPTION'];
if ($taskData['DESCRIPTION_IN_BBCODE'] === 'Y')
{
	// convert to bbcode to html to show inside a document body
	$taskData['DESCRIPTION'] = Util\UI::convertBBCodeToHtml(
		$taskData['DESCRIPTION'],
		[
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'USER_FIELDS' => $arResult['AUX_DATA']['USER_FIELDS'],
		]
	);
}
else
{
	$taskData['DESCRIPTION'] = htmlspecialcharsbx($taskData['DESCRIPTION']);
}
if (!empty($taskData['DESCRIPTION']))
{
	$taskData['DESCRIPTION'] = preg_replace_callback(
		'|<a href="/bitrix/tools/bizproc_show_file.php\?([^"]+)[^>]+>|',
		static function($matches) {
			parse_str(htmlspecialcharsback($matches[1]), $query);
			$filename = '';
			if (isset($query['f']))
			{
				$query['hash'] = md5($query['f']);
				$filename = $query['f'];
				unset($query['f']);
			}
			$query['mobile_action'] = 'bp_show_file';
			$query['filename'] = $filename;

			return '<a href="#" data-url="'.SITE_DIR.'mobile/ajax.php?'.http_build_query($query)
				.'" data-name="'.htmlspecialcharsbx($filename)
				.'" onclick="BXMobileApp.UI.Document.open({url: this.getAttribute(\'data-url\'), filename: this.getAttribute(\'data-name\')}); return false;">';
		},
		$taskData['DESCRIPTION']
	);
}

// todo: remove this when tasksRenderJSON() removed
if (
	isset($arResult['DATA']['EVENT_TASK'])
	&& is_array($arResult['DATA']['EVENT_TASK'])
)
{
	// It seems DESCRIPTION is not used anywhere, so to avoid security problems, simply dont pass DESCRIPTION.
	unset($arResult['DATA']['EVENT_TASK']['DESCRIPTION']);
	// the rest of array should be safe, as expected by tasksRenderJSON()
	$arResult['DATA']['EVENT_TASK_SAFE'] = Util::escape($arResult['DATA']['EVENT_TASK']);
}

if (!array_key_exists('ID', $taskData))
{
	$taskData['ID'] = 0;
	$taskData['TITLE'] = '';
	$taskData['DESCRIPTION'] = '';
	$taskData['DECLINE_REASON'] = '';
	$taskData['STATUS'] = 0;
}

$users = [
	$taskData['RESPONSIBLE_ID'] => [
		'ID' => $taskData['RESPONSIBLE_ID'],
		'NAME' => $taskData['RESPONSIBLE_NAME'],
		'LAST_NAME' => $taskData['RESPONSIBLE_LAST_NAME'],
		'SECOND_NAME' => $taskData['RESPONSIBLE_SECOND_NAME'],
		'LOGIN' => $taskData['RESPONSIBLE_LOGIN'],
		'PERSONAL_PHOTO' => $taskData['RESPONSIBLE_PHOTO'],
	],
	$taskData['CREATED_BY'] => [
		'ID' => $taskData['CREATED_BY'],
		'NAME' => $taskData['CREATED_BY_NAME'],
		'LAST_NAME' => $taskData['CREATED_BY_LAST_NAME'],
		'SECOND_NAME' => $taskData['CREATED_BY_SECOND_NAME'],
		'LOGIN' => $taskData['CREATED_BY_LOGIN'],
		'PERSONAL_PHOTO' => $taskData['CREATED_BY_PHOTO'],
	],
];
foreach ($taskData['SE_ACCOMPLICE'] as $user)
{
	$users[$user['ID']] = $user;
}
foreach ($taskData['SE_AUDITOR'] as $user)
{
	$users[$user['ID']] = $user;
}

foreach ($users as $id => $data)
{
	$user = $users[$id];
	$user['NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'], $user, true, false);
	$user['AVATAR'] = '';
	if ($user['PERSONAL_PHOTO'] && ($file = CFile::GetFileArray($user['PERSONAL_PHOTO'])) && $file !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$file,
			[
				'width'  => $arParams['AVATAR_SIZE'],
				'height' => $arParams['AVATAR_SIZE'],
			],
			BX_RESIZE_IMAGE_EXACT
		);
		$user['AVATAR'] = $arFileTmp['src'];
	}
	$users[$id] = $user;
}

$taskData['SE_RESPONSIBLE'] = $users[$taskData['RESPONSIBLE_ID']];
$taskData['SE_ORIGINATOR'] = $users[$taskData['CREATED_BY']];
$taskData['SE_ACCOMPLICE'] = [];
$taskData['SE_AUDITOR'] = [];

foreach ($taskData['ACCOMPLICES'] as $id)
{
	$taskData['SE_ACCOMPLICE'][$id] = $users[$id];
}
foreach ($taskData['AUDITORS'] as $id)
{
	$taskData['SE_AUDITOR'][$id] = $users[$id];
}

$checklistItems = (is_array($taskData['SE_CHECKLIST']) ? $taskData['SE_CHECKLIST'] : []);
$taskData['SE_CHECKLIST'] = $checklistItems;

// sonet log
$templateData['LOG_ID'] = false;

if (Loader::includeModule('socialnetwork'))
{
	$res = CSocNetLog::getList(
		[],
		[
			'EVENT_ID' => 'tasks',
			'SOURCE_ID' => $taskData['ID'],
		],
		false,
		false,
		['ID']
	);
	if ($item = $res->Fetch())
	{
		$templateData['LOG_ID'] = (int)$item['ID'];
	}

	if (!$templateData['LOG_ID'] && Loader::includeModule('crm'))
	{
		$res = CCrmActivity::getList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Task,
				'ASSOCIATED_ENTITY_ID' => $taskData['ID'],
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['ID']
		);
		if ($crmActivity = $res->Fetch())
		{
			$res = CSocNetLog::getList(
				[],
				[
					'EVENT_ID' => 'crm_activity_add',
					'ENTITY_ID' => $crmActivity['ID'],
				],
				false,
				false,
				['ID']
			);
			if ($item = $res->Fetch())
			{
				$templateData['LOG_ID'] = (int)$item['ID'];
			}
		}
	}
}
$templateData['CURRENT_TS'] = time();

if (!empty($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($templateData['LOG_ID']))
{
	$ratingData = ComponentHelper::getLivefeedRatingData([
		'topCount' => 10,
		'logId' => [$templateData['LOG_ID']],
	]);
	if (!empty($ratingData) && !empty($ratingData[$templateData['LOG_ID']]))
	{
		$arResult['TOP_RATING_DATA'] = $ratingData[$templateData['LOG_ID']];
	}
}

$arResult['TEMPLATE_DATA'] = $templateData;
$arResult['DATA']['TASK'] = $taskData;

$arResult['CAN']['TASK']['ACTION']['EDIT.RESPONSIBLE'] = \Bitrix\Tasks\Access\TaskAccessController::can((int)$USER->getID(), \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, (int)$taskData['ID']);