<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Site;
use Bitrix\Tasks\Util\UserField;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', /*etc*/),
));

$arParams =& $helper->getComponent()->arParams;

/** @var \Bitrix\Tasks\Item\Task\Template $template */
$template = $arResult['ITEM'];

if ($arParams['SET_TITLE'] != 'N')
{
	$title = $template['TITLE'];
	$helper->setTitle(htmlspecialcharsbx($title));
	$helper->addBodyClass('no-paddings task-detail-page');
}

$this->__component->tryParseBooleanParameter($arParams["ENABLE_MENU_TOOLBAR"], true);

if($helper->checkHasFatals())
{
	return;
}

$type = (($arParams["GROUP_ID"] ?? null) > 0 ? 'group' : 'user');
$editMode = !!$template->getId();
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

$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_WO_GROUP"], str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]));
$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TASK_WO_GROUP"],  str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]));

// group paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS"], COption::GetOptionString("tasks", "paths_task_group", null, SITE_ID));
$this->__component->tryParseURIParameter($arParams["PATH_TO_GROUP_TASKS_TASK"], COption::GetOptionString("tasks", "paths_task_group_action", null, SITE_ID));

// template paths
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TASKS_TEMPLATES"], '');
$this->__component->tryParseURIParameter($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"], '');
$arParams["PATH_TO_TEMPLATES"] = str_replace("#user_id#",$arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
$arParams["PATH_TO_TEMPLATES_TEMPLATE"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]);

$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TEMPLATE_VIEW"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template->getId(), "action" => "view")));
$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TEMPLATE_EDIT"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template->getId(), "action" => "edit")));

$isIframe = $_REQUEST['IFRAME'] && $_REQUEST['IFRAME'] == 'Y';
$arParams["PATH_TO_TASKS_TEMPLATE_EDIT"] = Util::replaceUrlParameters($arParams["PATH_TO_TASKS_TEMPLATE_EDIT"].($isIframe?'?IFRAME=Y':''), array(
	'BACKURL' => $arParams['PATH_TO_TASKS_TEMPLATE_VIEW'],
), array(), array('encode' => true));

$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TEMPLATE_CREATE_SUB"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => 0, "action" => "edit")).'?BASE_TEMPLATE='.$template->getId());
$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TEMPLATE_COPY"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => 0, "action" => "edit")).'?COPY='.$template->getId());
$this->__component->tryParseStringParameter($arParams["PATH_TO_TASKS_TEMPLATE_CREATE_TASK"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit")).'?TEMPLATE='.$template->getId());

// tune
$this->__component->tryParseStringParameter($arParams["NAME_TEMPLATE"], Site::getUserNameFormat());
$this->__component->tryParseBooleanParameter($arParams["SET_TITLE"], true);
$this->__component->tryParseBooleanParameter($arParams["SET_NAVCHAIN"], true);

// backurl (aka success url)
if((string) $arResult['COMPONENT_DATA']['BACKURL'] != '')
{
	$backUrl = $arResult['COMPONENT_DATA']['BACKURL'];
}
else
{
	$backUrl = $arParams['PATH_TO_TEMPLATES'];
}
$arResult['TEMPLATE_DATA']['BACKURL'] = $backUrl;

$arResult['TEMPLATE_DATA']['USER_FIELDS'] = $template->getUserFieldScheme(true, array(
	'COLLECTION_VALUE_TO_ARRAY' => true,
))->toArray();

if($template["DESCRIPTION"] != '')
{
	if($template['DESCRIPTION_IN_BBCODE'] == 'Y')
	{
		// convert to bbcode to html to show inside a document body
		$template["DESCRIPTION"] = UI::convertBBCodeToHtml(
			$template["DESCRIPTION"],
			array(
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"USER_FIELDS" => $arResult['TEMPLATE_DATA']['USER_FIELDS'],
			)
		);
	}
	else
	{
		// make our description safe to display
		$template["DESCRIPTION"] = UI::convertHtmlToSafeHtml(
			$template["DESCRIPTION"]
		);
	}
}
if ($type === 'user')
{
	$navChainParams = [
		CUser::FormatName($arParams["NAME_TEMPLATE"], $users[$arParams["USER_ID"]]),
		CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"],
			["user_id" => $arParams["USER_ID"]]),
	];
}
else
{
	$navChainParams = [
		$groups[$arParams["GROUP_ID"]]["NAME"] ?? null,
		CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], ["group_id" => $arParams["GROUP_ID"]]),
	];
}

$helper->setNavChain([$navChainParams, [$title, '']]);

$users = array_map(function ($item){
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
if(intval($template['PARENT_ID']))
{
	if(array_key_exists($template['PARENT_ID'], $tasks))
	{
		$pItem = $tasks[$template['PARENT_ID']];
		$pItem['URL'] = CComponentEngine::makePathFromTemplate(
			$arParams["PATH_TO_USER_TASKS_TASK"],
			array(
				"task_id" => $pItem['ID'],
				"action" => "view",
				"user_id" => $arParams['USER_ID']
			)
		);
	}
	else
	{
		$pItem['ID'] = 0;
		$pItem['TITLE'] = Loc::getMessage('TASKS_TTV_TASK_INACCESSIBLE');
		$pItem['URL'] = 'javascript:void(0);';
	}
	$pItem['ENTITY_TYPE'] = 'T';

	$seParentItem[] = $pItem;
}
elseif(intval($template['BASE_TEMPLATE_ID']))
{
	if($template['TPARAM_TYPE'] != 1) // not for new user
	{
		$pItem = $templates[0];
		if($pItem)
		{
			$pItem['URL'] = CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_USER_TEMPLATES_TEMPLATE'],
				[
					'template_id' => $pItem['ID'],
					'action' => 'view',
					'user_id' => $arParams['USER_ID']
				],
			);
		}
		else
		{
			$pItem['ID'] = 0;
			$pItem['TITLE'] = Loc::getMessage('TASKS_TTV_TEMPLATE_INACCESSIBLE');
			$pItem['URL'] = 'javascript:void(0);';
		}
		$pItem['ENTITY_TYPE'] = 'TT';

		$seParentItem[] = $pItem;
	}
}

$arResult['TEMPLATE_DATA']['TEMPLATE']['SE_PARENTITEM'] = $seParentItem;

$ufToShow = array();
$diskUfCode = Integration\Disk\UserField::getMainSysUFCode();
foreach($arResult['TEMPLATE_DATA']['USER_FIELDS'] as $userField)
{
	$isEmpty = UserField::isValueEmpty($userField["VALUE"]) && $userField["USER_TYPE_ID"] !== 'boolean';

	if ($isEmpty || $userField["FIELD_NAME"] === $diskUfCode || !UserField\UI::isSuitable($userField))
	{
		continue;
	}

	$ufToShow[] = $userField;
}
$arResult['TEMPLATE_DATA']['USER_FIELDS_TO_SHOW'] = $ufToShow;

// we need to know if there are sub-templates...
// todo: remove this ugly call
$haveSub = false;
if($template->getId())
{
	$res = CTaskTemplates::GetList(array(), array('BASE_TEMPLATE_ID' => $template->getId()), array('nTopCount' => 1), false, array('ID'))->fetch();
	if($res)
	{
		$haveSub = true;
	}
}
$arResult['TEMPLATE_DATA']['HAVE_SUB_TEMPLATES'] = $haveSub;

$checkListItems = TemplateCheckListFacade::getItemsForEntity($template->getId(), $template->getUserId());
$arResult['TEMPLATE_DATA']['SE_CHECKLIST'] = $checkListItems;

$arResult['JS_DATA']= array(
	'data' => array(
		// todo: you may use $template->export('~'); here
		'ID' => $template->getId(),
		'USER_ID' => $arParams['USER_ID'],
		'PRIORITY' => $template['PRIORITY'],
	),
	'auxData' => $arResult['AUX_DATA'],
	'can' => array(
		'edit' => $template->canEdit(),
	),
	'backUrl' => Util::secureBackUrl($arResult['TEMPLATE_DATA']['BACKURL']),
);