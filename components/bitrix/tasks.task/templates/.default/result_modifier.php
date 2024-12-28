<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Integration\SocialNetwork\Collab\Url\UrlManager;
use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Component\Task\TasksTaskFormState;

Loc::loadMessages(__DIR__.'/template.php');

// js extension to be registered instead of script.js

$folder = $this->GetFolder();

// todo: use templateHelper here instead of the following:
$id = trim((string) ($this->__component->arParams['TEMPLATE_CONTROLLER_ID'] ?? null));
if($id)
{
	$id = mb_strtolower($id);
	if(!preg_match('#^[a-z0-9_-]+$#', $id))
	{
		$this->__component->getErrors()->addWarning('ILLEGAL_CONTROLLER_ID', 'Illegal TEMPLATE_CONTROLLER_ID passed');
		$id = false;
	}
}

if(!$id)
{
	$id = $this->__component->getSignature();
}

$arResult['TEMPLATE_DATA'] = array(
	'ID' => $id,
	'EDIT_MODE' => !!($arResult['DATA']['TASK']['ID'] ?? null),
	'INPUT_PREFIX' => 'ACTION[0][ARGUMENTS][data]',
);

$extensionId = 'tasks_component_ext_'.$arResult['TEMPLATE_DATA']['ID'];

CJSCore::RegisterExt(
	$extensionId,
	array(
		'js'  => $folder.'/logic.js',
		'rel' =>  array(
			'tasks_util_datepicker',
			'popup',
			'fx',
			'tasks_util',
			'tasks_util_widget',
			'tasks_util_itemset',
			'tasks_itemsetpicker',
			'tasks_util_query',
			'tasks_shared_form_projectplan',
			'task_calendar',
			'tasks',
			'ui.design-tokens',
		),
		'lang' => $folder.'/lang/'.LANGUAGE_ID.'/template.php'
	)
);
CJSCore::Init($extensionId);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->toArray();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? \Bitrix\Tasks\Util\Site::getUserNameFormat() : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

// wysiwyg editor parameters

// in the middle of the paleozoic era we could get tasks with description in HTML format. now we have to live with that
$bbCode = isset($arResult['DATA']['TASK']['DESCRIPTION_IN_BBCODE']) ? $arResult['DATA']['TASK']['DESCRIPTION_IN_BBCODE'] == 'Y' : true;

$description = (string) ($arResult['DATA']['TASK']['DESCRIPTION'] ?? null) != '' ? $arResult['DATA']['TASK']['DESCRIPTION'] : '';
/*
if(!$bbCode && $description != '')
{
	$arResult['DATA']['TASK']['DESCRIPTION'] = CTasksTools::SanitizeHtmlDescriptionIfNeed($arResult['DATA']['TASK']['DESCRIPTION']);
	$description = $arResult['DATA']['TASK']['DESCRIPTION'];
}
*/

// make pictures inserted to the text visible
$editorProps = array();
$ufCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
if(is_array($arResult['AUX_DATA']['USER_FIELDS'][$ufCode] ?? null))
{
	$editorProps[$ufCode] = $arResult['AUX_DATA']['USER_FIELDS'][$ufCode];
}
$description = str_replace("\r\n", "\n", $description); // avoid input containing double amount of <br>

$buttons = array(
	"UploadImage",
	"UploadFile",
	"CreateLink",
	//(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
	//"InputTag",
);

$htmlButtons = [
	"Checklist" => '<span data-bx-id="task-edit-toggler" data-target="checklist">'.Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHECKLIST').'</span>',
	"ToCheckList" => '<span data-bx-id="task-edit-to-checklist">' . Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TO_CHECKLIST') . '</span>',
];

if ($arResult['CAN_SHOW_AI_CHECKLIST_BUTTON'] ?? false)
{
	$htmlButtons['AiCheckList'] = '<span data-bx-id="task-edit-ai-checklist" class="tasks-btn-ai-checklist">' . Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHECKLIST') . '</span>';
}

if($bbCode)
{
	$buttons[] = "Quote";
	$buttons[] = "MentionUser";
}
$buttons[] = "Checklist";
$buttons[] = "ToCheckList";
$buttons[] = 'Copilot';

$arResult['AUX_TEMPLATE_DATA']['EDITOR_PARAMETERS'] = [
	"FORM_ID" => 'task-form',
	"SHOW_MORE" => "N",
	"PARSER" => [
		"Bold",
		"Italic",
		"Underline",
		"Strike",
		"ForeColor",
		"FontList",
		"FontSizeList",
		"RemoveFormat",
		"Quote",
		"Code",
		//(($arParams["USE_CUT"] == "Y") ? "InsertCut" : ""),
		"CreateLink",
		"Image",
		"Table",
		"Justify",
		"InsertOrderedList",
		"InsertUnorderedList",
		"SmileList",
		"Source",
		"UploadImage",
		//(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
		"MentionUser",
	],
	"BUTTONS" => $buttons,
	"BUTTONS_HTML" => $htmlButtons,
	"FILES" => [
		"VALUE" => [],
		"DEL_LINK" => '',
		"SHOW" => "N"
	],
	"TEXT" => [
		"INPUT_NAME" => "ACTION[0][ARGUMENTS][data][DESCRIPTION]",
		"VALUE" => $description,
		"HEIGHT" => "120px"
	],
	"PROPERTIES" => [],//$editorProps,
	"UPLOAD_FILE" => true,
	"UPLOAD_FILE_PARAMS" => ['width' => 400, 'height' => 400],
	/*
	"TAGS" => Array(
		"ID" => "TAGS",
		"NAME" => "TAGS",
		"VALUE" => explode(",", trim($arResult["PostToShow"]["CategoryText"])),
		"USE_SEARCH" => "Y",
		"FILTER" => "blog",
	),
	*/
	//"SMILES" => array("VALUE" => $arSmiles),
	"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
	//"AT_THE_END_HTML" => $htmlAfterTextarea,
	"LHE" => [
		"id" => $arResult['TEMPLATE_DATA']['ID'],
		"iframeCss" => "body { padding: 12px 18px 8px 12px !important; color: #151515; line-height: var(--ui-font-line-height-lg, 22px); }",
		"fontSize" => "14.5px",
		"bInitByJS" => false,
		"lazyLoad" => 'N',
		"bbCode" => $bbCode, // set editor mode: bbcode or html
		"setFocusAfterShow" => (bool)(int)($arResult['DATA']['TASK']['ID'] ?? null), // when creating task, we should not
		'isCopilotImageEnabledBySettings' => \Bitrix\Tasks\Integration\AI\Settings::isImageAvailable(),
		'isCopilotTextEnabledBySettings' => \Bitrix\Tasks\Integration\AI\Settings::isTextAvailable(),
		'copilotParams' => [
			'moduleId' => 'tasks',
			'contextId' => 'tasks_' . $arResult['TEMPLATE_DATA']['ID'],
			'category' => 'tasks',
		],
	],
	//"USE_CLIENT_DATABASE" => "Y",
	//"ALLOW_EMAIL_INVITATION" => ($arResult["ALLOW_EMAIL_INVITATION"] ? 'Y' : 'N')
];

if(is_array($arResult['AUX_DATA']['USER_FIELDS'] ?? null))
{
	foreach($arResult['AUX_DATA']['USER_FIELDS'] as &$uf)
	{
		$uf['FIELD_NAME_ORIG'] = $uf['FIELD_NAME'];
		$uf['FIELD_NAME'] = $arResult['TEMPLATE_DATA']['INPUT_PREFIX'].'['.$uf['FIELD_NAME'].']';
	}
	unset($uf);

	if(is_array($arResult['AUX_DATA']['USER_FIELDS'][$ufCode]))
	{
		$ufDesc = $arResult['AUX_DATA']['USER_FIELDS'][$ufCode];
		$arResult['AUX_TEMPLATE_DATA']['EDITOR_PARAMETERS']['UPLOAD_WEBDAV_ELEMENT'] = $ufDesc;
	}
}

// template parameters
$arResult['TEMPLATE_DATA']['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'];

$taskType = $arParams["GROUP_ID"] > 0 ? "group" : "user";

// user paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_PROFILE"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS"], COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID));
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS_TASK"], COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID));

// group paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS"], COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID));
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS_TASK"], COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID));

// template paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS_TEMPLATES"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"], '');
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);
$arParams["PATH_TO_TASKS_TEMPLATES"] = str_replace("#user_id#",$arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

$this->__component->tryParseURIParameter($arParams["ACTION_URI"], POST_FORM_ACTION_URI, true, false);

// tune
$this->__component->tryParseBooleanParameter($arParams["SET_TITLE"], true);
$this->__component->tryParseBooleanParameter($arParams["SET_NAVCHAIN"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FORM"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FOOTER"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FOOTER_UNPIN"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_CANCEL_BUTTON"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_MENU_TOOLBAR"], true);

if ($taskType === "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
}

$arParams['PATH_TO_TASKS_TASK_ORIGINAL'] = $arParams['PATH_TO_TASKS_TASK'];
if (is_array($arParams['TASK_URL_PARAMETERS'] ?? null) && !empty($arParams['TASK_URL_PARAMETERS']) && (string)$arParams['PATH_TO_TASKS_TASK'] !== '')
{
	$arParams['PATH_TO_TASKS_TASK'] = \Bitrix\Tasks\Util::replaceUrlParameters($arParams['PATH_TO_TASKS_TASK'], $arParams['TASK_URL_PARAMETERS']);
}

// title & nav chain
$sTitle = "";
if ((int)$arParams["ID"]) // edit
{
	$sTitle = str_replace("#TASK_ID#", $arParams["ID"], GetMessage("TASKS_TASK_COMPONENT_TEMPLATE_EDIT_TASK_TITLE"));
}
else
{
	$sTitle = GetMessage("TASKS_TASK_COMPONENT_TEMPLATE_NEW_TASK_TITLE");

}
if ($arParams["SET_TITLE"])
{
	$APPLICATION->SetTitle($sTitle);
}

if ($arParams["SET_NAVCHAIN"])
{
	if ($taskType === "user")
	{
		$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult['DATA']['USER'][$arParams["USER_ID"]] ?? null), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
	else
	{
		$APPLICATION->AddChainItem($arResult['DATA']['GROUP'][$arParams["GROUP_ID"]]["NAME"] ?? null, CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])));
		$APPLICATION->AddChainItem($sTitle);
	}
}

//Body Class
$ownClass = "task-form-page";
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$bodyClass = $bodyClass ? $bodyClass." ".$ownClass : $ownClass;
$APPLICATION->SetPageProperty("BodyClass", $bodyClass);

// URLs

// backurl (aka success url)
if(!empty($arResult['COMPONENT_DATA']['BACKURL']))
{
    $backUrl = $arResult['COMPONENT_DATA']['BACKURL'];
}
else
{
    $backUrl = $arParams['PATH_TO_TASKS_TASK'];
}
if((int)($arResult['DATA']['TASK']['ID'] ?? null))
{
    $backUrl = str_replace('#task_id#', (int)$arResult['DATA']['TASK']['ID'], $backUrl);
}
$arResult['TEMPLATE_DATA']['BACKURL'] = $backUrl;

// cancelurl
$cancelUrl = $arParams['PATH_TO_TASKS'];
if (!empty($request['CANCELURL']))
{
	$cancelUrl = $request['CANCELURL'];
}
elseif(!empty($request['BACKURL']))
{
	$cancelUrl = $request['BACKURL'];
}
$arResult['TEMPLATE_DATA']['CANCELURL'] = $cancelUrl;

// component url (for ajax)
$arResult['TEMPLATE_DATA']['COMPONENTURL'] = $this->__component->__path.'/ajax.php';

// get really chosen blocks
$arResult['TEMPLATE_DATA']['BLOCKS']['CLASSES'] = array();

$allAdditionalChosen = true;
$hasAdditionalUnchosenFilled = false;
$additionalBlocks = array();
if(
	isset($arResult['COMPONENT_DATA']['STATE']['BLOCKS'])
	&& is_array($arResult['COMPONENT_DATA']['STATE']['BLOCKS'])
	&& !empty($arResult['DATA']['TASK'])
	&& is_array($arResult['DATA']['TASK'])
)
{
	$baseBlocks = array(
		'SE_CHECKLIST' => true,
		'SE_ORIGINATOR' => true,
		'SE_AUDITOR' => true,
		'SE_ACCOMPLICE' => true,
		'DATE_PLAN' => true,
		'OPTIONS' => true,
	);

    $taskData = $arResult['DATA']['TASK'];

	foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $block => $state)
	{
		$chosen = $state[TasksTaskFormState::O_CHOSEN];

		$filled = false;
		if(array_key_exists(TasksTaskFormState::O_CHOSEN, $state))
		{
			// one of real task fields
			// except for originator: he is always present, but we dont want to see him every time because usually its the current user
			if(array_key_exists($block, $taskData) && !empty($taskData[$block]) && $block !== 'SE_ORIGINATOR')
			{
				if($block === 'SE_TEMPLATE')
				{
					$filled = $taskData['REPLICATE'] === 'Y';
				}
				elseif($block === 'UF_CRM_TASK')
				{
					// this value can come as a single-empty-element array, must normalize first
					$normalData = \Bitrix\Tasks\Util\Type::normalizeArray($taskData[$block]);
					$filled = !empty($normalData);
				}
				elseif ($block === 'REGULAR')
				{
					$filler = $taskData['IS_REGULAR'] === 'Y';
				}
				else
				{
					$filled = true;
				}
			}
			else // "special" cases
			{
				if($block === 'SE_ORIGINATOR')
				{
					// field value is interesting to view only if originator != you
					$filled = $taskData['SE_ORIGINATOR']['ID'] != \Bitrix\Tasks\Util\User::getId();
				}
				if($block === 'DATE_PLAN')
				{
					$filled = !empty($taskData['START_DATE_PLAN']) || !empty($taskData['END_DATE_PLAN']);
				}
                if($block === 'OPTIONS')
                {
                    $filled = false; // always hidden, unsure if this is correct
	                //$taskData['ALLOW_CHANGE_DEADLINE'] == 'Y' || $taskData['MATCH_WORK_TIME'] == 'Y' || $taskData['TASK_CONTROL'] == 'Y' || $taskData['ADD_TO_FAVORITE'] == 'Y' || $taskData['ADD_TO_TIMEMAN'] == 'Y';
                }
                if($block === 'USER_FIELDS')
                {
                    foreach($taskData as $field => $value)
                    {
                        if(\Bitrix\Tasks\Util\UserField::isUFKey($field) && !empty($value))
                        {
                            $filled = true;
                            break;
                        }
                    }
                }
                if($block === 'TIMEMAN')
                {
                    $filled = intval($taskData['TIME_ESTIMATE'] ?? null) > 0;
                }
			}
		}

		if(isset($baseBlocks[$block])) // is NOT "additional" block
		{
			$classes = array();
			if($chosen)
			{
				$classes[] = 'pinned';
			}
			if(!($chosen || $filled))
			{
				$classes[] = 'invisible';
			}
			$arResult['TEMPLATE_DATA']['BLOCKS']['CLASSES'][$block] = implode(' ', $classes);
		}
		else
		{
			if(!$chosen)
			{
				$allAdditionalChosen = false;
				if($filled)
				{
					$hasAdditionalUnchosenFilled = true;
				}
			}

            $additionalBlocks[] = $block;
		}
	}
}

// additional panel
$arResult['TEMPLATE_DATA']['ADDITIONAL_BLOCKS'] = $additionalBlocks;
$arResult['TEMPLATE_DATA']['ADDITIONAL_OPENED'] = false; //$hasAdditionalUnchosenFilled;
$arResult['TEMPLATE_DATA']['ADDITIONAL_DISPLAYED'] = !$allAdditionalChosen;

$arResult['TEMPLATE_DATA']['FOOTER_PINNED'] =
	isset($arParams['ENABLE_FOOTER_UNPIN'])
	&& $arParams['ENABLE_FOOTER_UNPIN']
	&& isset($arResult['COMPONENT_DATA']['STATE']['FLAGS']['FORM_FOOTER_PIN'])
	&& $arResult['COMPONENT_DATA']['STATE']['FLAGS']['FORM_FOOTER_PIN'];
$arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE'] =
	isset($arResult['COMPONENT_DATA']['ACTION']['SUCCESS'])
	&& $arResult['COMPONENT_DATA']['ACTION']['SUCCESS']
	&&
	(
		!isset($arParams['REDIRECT_ON_SUCCESS'])
		|| !$arParams['REDIRECT_ON_SUCCESS']
	)
	&&
	(
		!isset($arResult['COMPONENT_DATA']['EVENT_OPTIONS']['STAY_AT_PAGE'])
		|| !$arResult['COMPONENT_DATA']['EVENT_OPTIONS']['STAY_AT_PAGE']
	);

// etc
$arResult['TEMPLATE_DATA']['TAG_STRING'] = \Bitrix\Tasks\UI\Task\Tag::formatTagString($arResult['DATA']['TASK']['SE_TAG'] ?? '');

// todo: remove this when tasksRenderJSON() removed
if(
	isset($arResult['DATA']['EVENT_TASK'])
	&& Type::isIterable($arResult['DATA']['EVENT_TASK'])
)
{
	// It seems DESCRIPTION is not used anywhere, so to avoid security problems, simply dont pass DESCRIPTION.
	unset($arResult['DATA']['EVENT_TASK']['DESCRIPTION']);
	// the rest of array should be safe, as expected by tasksRenderJSON()
	$arResult['DATA']['EVENT_TASK_SAFE'] = \Bitrix\Tasks\Util::escape($arResult['DATA']['EVENT_TASK']);
}

// checklist pre-format
// todo: remove this when use object with array access instead of ['ITEMS']['DATA']
$code = \Bitrix\Tasks\Manager\Task\CheckList::getCode(true);
if(Type::isIterable($arResult['DATA']['TASK'][$code] ?? null))
{
	foreach($arResult['DATA']['TASK'][$code] as &$item)
	{
		if (!is_array($item))
		{
			continue;
		}
		$item['TITLE_HTML'] = \Bitrix\Tasks\UI::convertBBCodeToHtmlSimple($item['TITLE']);
	}
}
unset($item);

// current user
$arResult['AUX_DATA']['USER']['DATA'] = \Bitrix\Tasks\Util\User::extractPublicData($arResult['AUX_DATA']['USER']['DATA'] ?? null);

$arResult['DATA']['CURRENT_TASKS'] = [
	'DEPENDS' => [],
	'PREVIOUS' => [],
	'PARENT' => []
];
$relatedTasks = $arResult['DATA']['RELATED_TASK'] ?? null;

if (
	isset($arResult['DATA']['TASK']['SE_RELATEDTASK'])
	&& is_array($arResult['DATA']['TASK']['SE_RELATEDTASK'])
)
{
	foreach ($arResult['DATA']['TASK']['SE_RELATEDTASK'] as $item)
	{
		$taskId = $item['ID'];
		$arResult['DATA']['TASK']['DEPENDS_ON'] = ($arResult['DATA']['TASK']['DEPENDS_ON'] ?? []);

		if (!in_array($taskId, $arResult['DATA']['TASK']['DEPENDS_ON']))
		{
			$arResult['DATA']['TASK']['DEPENDS_ON'][] = $taskId;
		}
		if ($relatedTasks[$taskId])
		{
			$arResult['DATA']['CURRENT_TASKS']['DEPENDS'][] = $relatedTasks[$taskId];
		}
	}
}

if (
	isset($arResult['DATA']['TASK']['SE_PROJECTDEPENDENCE'])
	&& is_array($arResult['DATA']['TASK']['SE_PROJECTDEPENDENCE'])
)
{
	foreach ($arResult['DATA']['TASK']['SE_PROJECTDEPENDENCE'] as $item)
	{
		$taskId = $item['DEPENDS_ON_ID'];

		if ($relatedTasks[$taskId])
		{
			$arResult['DATA']['CURRENT_TASKS']['PREVIOUS'][] = $relatedTasks[$taskId];
		}
	}
}

$parentTaskId = $arResult['DATA']['TASK']['PARENT_ID'] ?? 0;
if (
	$parentTaskId
	&& isset($relatedTasks[$parentTaskId])
	&& $relatedTasks[$parentTaskId]
)
{
	$arResult['DATA']['CURRENT_TASKS']['PARENT'][] = $relatedTasks[$parentTaskId];
}

$validParams = ParameterTable::getLegacyMap();

$params = array();
if(Bitrix\Tasks\Util\Type::isIterable($taskData['SE_PARAMETER'] ?? null))
{
	foreach($taskData['SE_PARAMETER'] as $param)
	{
		if (!isset($param['CODE']))
		{
			continue;
		}
		$params[$param['CODE']] = $param;
	}
}

foreach ($validParams as $propCode => $property)
{
	$params[$propCode]['TITLE'] = Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PARAMETER_' . $property);
	$params[$propCode]['HINT'] = Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PARAMETER_HINT_' . $property . 'a') ?? Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PARAMETER_HINT_' . $property);
	$params[$propCode]['CODE'] = $propCode;

	if (
		!isset($params[$propCode]['CODE'])
		|| !intval($params[$propCode]['CODE'])
	)
	{
		$params[$propCode]['CODE'] = rand(100, 999) . rand(100, 999);
	}

	if (
		!array_key_exists('VALUE', $params[$propCode])
		&& isset($arResult['COMPONENT_DATA']['STATE']['FLAGS'])
		&& is_array($arResult['COMPONENT_DATA']['STATE']['FLAGS'])
		&& array_key_exists('TASK_PARAM_' . $propCode, $arResult['COMPONENT_DATA']['STATE']['FLAGS'])
	)
	{
		$params[$propCode]['VALUE'] = $arResult['COMPONENT_DATA']['STATE']['FLAGS']['TASK_PARAM_' . $propCode] ? 'Y'
			: 'N';
	}

	$arResult['TEMPLATE_DATA']['PARAMS'][$propCode] = $params[$propCode];
}

$project = array();
if(
	isset($taskData['SE_PROJECT'])
	&& $taskData['SE_PROJECT']
	&& count($taskData['SE_PROJECT'])
)
{
	$project = $taskData['SE_PROJECT'];
	$project['ENTITY_TYPE'] = \Bitrix\Tasks\Integration\SocialNetwork::getGroupEntityPrefix();
	$project['URL'] = UrlManager::getUrlByType((int)$project['ID'], $project['TYPE'] ?? null);

	$project = array($project);
}
$arResult['DATA']['TASK']['SE_PROJECT'] = $project;

$lastTasks = [];

$order = ['STATUS' => 'ASC', 'DEADLINE' => 'DESC', 'PRIORITY' => 'DESC', 'ID' => 'DESC'];
$filter = [
	'DOER' => User::getId(),
	'STATUS' => [
		MetaStatus::UNSEEN,
		MetaStatus::EXPIRED,
		Status::NEW,
		Status::PENDING,
		Status::IN_PROGRESS,
	]
];
$select = ['ID', 'TITLE', 'STATUS'];
$params = [
	'MAKE_ACCESS_FILTER' => false,
	'NAV_PARAMS' => ['nTopCount' => 15]
];

$tasksDdRes = CTasks::GetList($order, $filter, $select, $params);
while ($task = $tasksDdRes->Fetch())
{
	$task['TITLE'] = \Bitrix\Main\Text\Emoji::decode($task['TITLE']);
	$lastTasks[] = $task;
}

$arResult['DATA']['LAST_TASKS'] = $lastTasks;