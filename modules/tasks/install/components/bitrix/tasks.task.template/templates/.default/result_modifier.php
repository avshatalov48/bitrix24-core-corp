<?
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Site;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Disk;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams;

$template = $arResult['ITEM'];
$editMode = !!$template->getId();
$title = str_replace("#ID#", $template->getId(), Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_'.($editMode ? 'EDIT' : 'NEW').'_TASK_TITLE_V2'));

$helper->setTitle($title);
$helper->addBodyClass('task-form-page');

$this->__component->tryParseBooleanParameter($arParams["ENABLE_MENU_TOOLBAR"], true);

if($helper->checkHasFatals())
{
	return;
}

$type = $arParams["GROUP_ID"] > 0 ? "group" : "user";
$BBCodeMode = $arResult['ITEM']['DESCRIPTION_IN_BBCODE'] == 'Y';
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->toArray();
$groups = $arResult['DATA']['GROUP'];
$users = $arResult['DATA']['USER'];
$tasks = $arResult['DATA']['TASK'];
$templates = $arResult['DATA']['TEMPLATE'];

// template parameters
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_PROFILE"], '');

// task paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS"], COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID));
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS_TASK"], COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID));
if ($type == "user")
{
	$toTasks = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$toTasksTask = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
}
else
{
	$toTasks = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$toTasksTask = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
}

$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS"], $toTasks);
$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TASK"], $toTasksTask);

// group paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS"], COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID));
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS_TASK"], COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID));

// template paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS_TEMPLATES"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"], '');
$arParams["PATH_TO_TEMPLATES"] = str_replace("#user_id#",$arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);

$this->__component->tryParseURIParameter($arParams["ACTION_URI"], POST_FORM_ACTION_URI);

// tune
$this->__component->tryParseStringParameter($arParams["NAME_TEMPLATE"], Site::getUserNameFormat());
$this->__component->tryParseBooleanParameter($arParams["SET_TITLE"], true);
$this->__component->tryParseBooleanParameter($arParams["SET_NAVCHAIN"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FORM"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FOOTER"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_FOOTER_UNPIN"], true);
$this->__component->tryParseBooleanParameter($arParams["ENABLE_CANCEL_BUTTON"], true);

if(!$BBCodeMode)
{
	$arResult['ITEM']['DESCRIPTION'] = UI::convertHtmlToSafeHtml($arResult['ITEM']['DESCRIPTION']);
}

$helper->setNavChain(array(
	($type == "user" ?
		array(CUser::FormatName($arParams["NAME_TEMPLATE"], $users[$arParams["USER_ID"]]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"]))) :
		array($groups[$arParams["GROUP_ID"]]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])))
	),
	array($title, '')
));

// URLs

// backurl (aka success url)
if((string) $arResult['COMPONENT_DATA']['BACKURL'] != '')
{
	$backUrl = $arResult['COMPONENT_DATA']['BACKURL'];
}
else
{
	$backUrl = $arParams['PATH_TO_TEMPLATES_TEMPLATE'];
}
if($template->getId())
{
	$backUrl = str_replace('#template_id#', $template->getId(), $backUrl);
}
$arResult['TEMPLATE_DATA']['BACKURL'] = $backUrl;

// cancelurl
$cancelUrl = $arParams['PATH_TO_TEMPLATES'];
if((string) $request['CANCELURL'] != '')
{
	$cancelUrl = $request['CANCELURL'];
}
elseif((string) $request['BACKURL'] != '')
{
	$cancelUrl = $request['BACKURL'];
}
$arResult['TEMPLATE_DATA']['CANCELURL'] = $cancelUrl;

///////////////////////////////////////////////////////////////
// block schema

$arResult['TEMPLATE_DATA']['IGNORED_USER_FIELDS'] = $ignoredUfs = array(
	CRM\UserField::getMainSysUFCode() => true,
	Disk\UserField::getMainSysUFCode() => true,
);

$crm = Util\Type::normalizeArray($template[CRM\UserField::getMainSysUFCode()]);

$ufFilled = false;
$ufFields = $template->getUserFieldScheme(true);
foreach($ufFields as $fieldCode => $field)
{
	if(array_key_exists($fieldCode, $ignoredUfs))
	{
		continue;
	}

	if(Util\UserField::isUFKey($fieldCode) && !Util\UserField::isValueEmpty($field['VALUE']))
	{
		$ufFilled = true;
		break;
	}
}

$accessFilled = true;
if(is_object($template['SE_ACCESS']) && $template['SE_ACCESS']->count() == 1)
{
	$first = $template['SE_ACCESS']->first();
	$level = \Bitrix\Tasks\Util\User::getAccessLevel('task_template', 'full');
	if($level && $first['GROUP_CODE'] == 'U'.$template['CREATED_BY'] && $first['TASK_ID'] == $level['ID'])
	{
		// this is the default rights, do not show them
		$accessFilled = false;
	}
}

$arResult['TEMPLATE_DATA']['BLOCKS'] = array(
	'SE_CHECKLIST' => array(
		'FILLED' => array_key_exists('SE_CHECKLIST', $template)
					&& is_object($template['SE_CHECKLIST'])
					&& !$template['SE_CHECKLIST']->isEmpty(),
	),
	'SE_RESPONSIBLE' => array(
		'FILLED' => true, // responsible always filled and thus visible
	),
	'SE_ORIGINATOR' => array(
		'FILLED' => $template['CREATED_BY'] != Util\User::getId(),
	),
	'SE_AUDITOR' => array(
		'FILLED' => $template['AUDITORS'] && !$template['AUDITORS']->isEmpty(),
	),
	'SE_ACCOMPLICE' => array(
		'FILLED' => $template['ACCOMPLICES'] && !$template['ACCOMPLICES']->isEmpty(),
	),
	'DATE_PLAN' => array(
		'FILLED' => $template['DEADLINE_AFTER'] || $template['START_AFTER'] || $template['DURATION'],
	),
	'OPTIONS' => array(
		'FILLED' => false,
	),

	// dynamic
	'PROJECT' => array(
		'FILLED' => intval($template['GROUP_ID']) > 0,
	),
	'TIME_MANAGER' => array(
		'FILLED' => intval($template['TIME_ESTIMATE']) > 0,
	),
	'REPLICATION' => array(
		'FILLED' => $template['REPLICATE'] == 'Y',
	),
	'CRM' => array(
		'FILLED' => !Util\UserField::isValueEmpty($crm),
	),
	'PARENT' => array(
		'FILLED' => intval($template['PARENT_ID']) || intval($template['BASE_TEMPLATE_ID']),
	),
	'TAG' => array(
		'FILLED' => $template['SE_TAG'] && !$template['SE_TAG']->isEmpty(),
	),
	'USER_FIELDS' => array(
		'FILLED' => $ufFilled,
	),
	'RELATED_TASK' => array(
		'FILLED' => $template['DEPENDS_ON'] && !$template['DEPENDS_ON']->isEmpty()
	),
	'ACCESS' => array(
		'FILLED' => $accessFilled
	),
);

$arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE'] = $arResult['COMPONENT_DATA']['ACTION']['SUCCESS'] && !$arParams['REDIRECT_ON_SUCCESS'] && !$arResult['COMPONENT_DATA']['EVENT_OPTIONS']['STAY_AT_PAGE'];

$arResult['JS_DATA']['template'] = $template->export('~'); // export data to array
$arResult['JS_DATA']['isNewUserResponsible'] = false;

// prepare some additional data

$users = array_map(function($item){
	$item['ENTITY_TYPE'] = SocialNetwork::getUserEntityPrefix();
	return $item;
}, $users);
$groups = array_map(function($item){
	$item['ENTITY_TYPE'] = SocialNetwork::getGroupEntityPrefix();
	return $item;
}, $groups);

$seUser = array();
foreach($template['RESPONSIBLES'] as $uId)
{
	if(array_key_exists($uId, $users))
	{
		$seUser[] = $users[$uId];
	}
}
if (count($seUser) == 1 && $seUser[0]['ID'] == 0)
{
	$arResult['JS_DATA']['isNewUserResponsible'] = true;
	$seUser[0]['ID'] = 'Unfalse';
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_RESPONSIBLE'] = $seUser;

$seUser = array();
if(array_key_exists($template['CREATED_BY'], $users))
{
	$seUser[] = $users[$template['CREATED_BY']];
}

$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ORIGINATOR'] = $seUser;

$seUser = array();
foreach($template['AUDITORS'] as $uId)
{
	if(array_key_exists($uId, $users))
	{
		$seUser[] = $users[$uId];
	}
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_AUDITOR'] = $seUser;

$seUser = array();
foreach($template['ACCOMPLICES'] as $uId)
{
	if(array_key_exists($uId, $users))
	{
		$seUser[] = $users[$uId];
	}
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_ACCOMPLICE'] = $seUser;

$seProject = array();
if(array_key_exists($template['GROUP_ID'], $groups))
{
	$group = $groups[$template['GROUP_ID']];
	$group['ENTITY_TYPE'] = 'SG';
	$seProject[] = $group;
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PROJECT'] = $seProject;

$seRelatedTask = array();
foreach($template['DEPENDS_ON'] as $taskId)
{
	if(array_key_exists($taskId, $tasks))
	{
		$seRelatedTask[] = $tasks[$taskId];
	}
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_RELATEDTASK'] = $seRelatedTask;

$seParentItem = array();
if(intval($template['PARENT_ID']) && array_key_exists($template['PARENT_ID'], $tasks))
{
	$pItem = $tasks[$template['PARENT_ID']];
	$pItem['ENTITY_TYPE'] = 'T';

	$seParentItem[] = $pItem;
}
elseif(intval($template['BASE_TEMPLATE_ID']))
{
	if($template['TPARAM_TYPE'] != 1) // not for new user
	{
		$pItem = $templates->getItemById(intval($template['BASE_TEMPLATE_ID']));
		if($pItem)
		{
			$pItem = $pItem->export('~');
			$pItem['ENTITY_TYPE'] = 'TT';

			$seParentItem[] = $pItem;
		}
	}
}
$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PARENTITEM'] = $seParentItem;

if (isset($arResult['COPIED_FROM']))
{
	$copiedFrom = $arResult['COPIED_FROM'];

	$checkListItems = TemplateCheckListFacade::getItemsForEntity($copiedFrom, $arResult['USER_ID']);
	foreach (array_keys($checkListItems) as $id)
	{
		$checkListItems[$id]['COPIED_ID'] = $id;
		unset($checkListItems[$id]['ID']);
	}
}
else
{
	$checkListItems = TemplateCheckListFacade::getItemsForEntity($template->getId(), $template->getUserId());
}
$arResult['TEMPLATE_DATA']['SE_CHECKLIST'] = $checkListItems;

$matchWorkTime = $template['MATCH_WORK_TIME'] == 'Y';
$arResult['JS_DATA']['deadline'] = $helper->detectUnitType($matchWorkTime, $template['DEADLINE_AFTER']);
$arResult['JS_DATA']['startDate'] = $helper->detectUnitType($matchWorkTime, $template['START_DATE_PLAN_AFTER']);
$arResult['JS_DATA']['duration'] = $helper->detectUnitType($matchWorkTime, $template['END_DATE_PLAN_AFTER'] - $template['START_DATE_PLAN_AFTER']);
$arResult['JS_DATA']['auxData'] = $arResult['AUX_DATA'];
$arResult['JS_DATA']['currentUser'] = $users[$arParams['USER_ID']];