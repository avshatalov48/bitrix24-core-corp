<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\UserTagTable;

if (!CModule::IncludeModule("socialnetwork"))
	return;

$APPLICATION->SetGroupRight("socialnetwork", 1, "W");
$APPLICATION->SetGroupRight("socialnetwork", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
$APPLICATION->SetGroupRight("socialnetwork", WIZARD_EMPLOYEES_GROUP, "K");
COption::SetOptionString("socialnetwork", "GROUP_DEFAULT_RIGHT", "D");
COption::SetOptionString("socialnetwork", "allow_frields", "N", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "subject_path_template", WIZARD_SITE_DIR."workgroups/group/search/#subject_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "group_path_template", WIZARD_SITE_DIR."workgroups/group/#group_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "messages_path", WIZARD_SITE_DIR."company/personal/messages/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "default_photo_operation_write_group", "K", false, WIZARD_SITE_ID);

CAgent::RemoveAgent("CSocNetLog::ClearOldAgent();", "socialnetwork");
COption::SetOptionString("socialnetwork", "log_cleanup_days", "0");

$arGroupSubjects = array();
$arGroupSubjectsId = array();

$arGroupSubjects[0] = array(
		"SITE_ID" => WIZARD_SITE_ID,
		"NAME" => GetMessage("SONET_GROUP_SUBJECT_0"),
	);
$arGroupSubjectsId[0] = 0;

$errorMessage = "";
foreach ($arGroupSubjects as $ind => $arGroupSubject)
{
	$rsSocNetGroupSubject = CSocNetGroupSubject::GetList(array(), $arGroupSubject);

	$idTmp = false;
	if ($arSocNetGroupSubject = $rsSocNetGroupSubject->Fetch())
	{
		$arGroupSubjectsId[$ind] = $arSocNetGroupSubject["ID"];
	}
	else
	{
		$idTmp = CSocNetGroupSubject::Add($arGroupSubject);
		if ($idTmp)
		{
			$arGroupSubjectsId[$ind] = intval($idTmp);
		}
		else
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage .= $e->GetString();
		}
	}
}
if ($errorMessage == '')
{
	$pathToImages = $WIZARD_SERVICE_ABSOLUTE_PATH."/images/";

	$arGroupsId = array();
	$arGroups = array(
/*
		1 => array(
			"SITE_ID" => WIZARD_SITE_ID,
			"NAME" => GetMessage("SONET_GROUP_NAME_NEW_1"),
			"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_NEW_1"),
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"ACTIVE" => "Y",
			"VISIBLE" => "Y",
			"OPENED" => "N",
			"SUBJECT_ID" => $arGroupSubjectsId[0],
			"OWNER_ID" => 1,
			"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_NEW_1"),
			"IMAGE_ID" => array(
				"name" => "1.png",
				"type" => "image/png",
				"tmp_name" => $pathToImages."/1.png",
				"error" => "0",
				"size" => @filesize($pathToImages."/1.png"),
				"MODULE_ID" => "socialnetwork"
			),
			"NUMBER_OF_MEMBERS" => 1,
			"INITIATE_PERMS" => "E",
			"SPAM_PERMS" => "N",
			"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
		),
		2 => array(
			"SITE_ID" => WIZARD_SITE_ID,
			"NAME" => GetMessage("SONET_GROUP_NAME_NEW_2"),
			"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_NEW_2"),
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"ACTIVE" => "Y",
			"VISIBLE" => "N",
			"OPENED" => "N",
			"SUBJECT_ID" => $arGroupSubjectsId[0],
			"OWNER_ID" => 1,
			"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_NEW_2"),
			"IMAGE_ID" => array(
				"name" => "2.png",
				"type" => "image/png",
				"tmp_name" => $pathToImages."/2.png",
				"error" => "0",
				"size" => @filesize($pathToImages."/2.png"),
				"MODULE_ID" => "socialnetwork"
			),
			"NUMBER_OF_MEMBERS" => 1,
			"SPAM_PERMS" => "N",
			"INITIATE_PERMS" => "E",
			"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
		),
		3 => array(
			"SITE_ID" => WIZARD_SITE_ID,
			"NAME" => GetMessage("SONET_GROUP_NAME_NEW_3"),
			"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_NEW_3"),
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"ACTIVE" => "Y",
			"VISIBLE" => "Y",
			"OPENED" => "Y",
			"SUBJECT_ID" => $arGroupSubjectsId[0],
			"OWNER_ID" => 1,
			"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_NEW_3"),
			"IMAGE_ID" => array(
				"name" => "3.png",
				"type" => "image/png",
				"tmp_name" => $pathToImages."/3.png",
				"error" => "0",
				"size" => @filesize($pathToImages."/3.png"),
				"MODULE_ID" => "socialnetwork"
			),
			"NUMBER_OF_MEMBERS" => 1,
			"SPAM_PERMS" => "N",
			"INITIATE_PERMS" => "K",
			"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
		)
*/
	);

	if (IsModuleInstalled("extranet"))
	{
/*
		$arGroups[] = array(
			"SITE_ID" => array(WIZARD_SITE_ID, "ex"),
			"NAME" => GetMessage("SONET_GROUP_NAME_NEW_4"),
			"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_NEW_41"),
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"ACTIVE" => "Y",
			"VISIBLE" => "N",
			"OPENED" => "N",
			"SUBJECT_ID" => $arGroupSubjectsId[0],
			"OWNER_ID" => 1,
			"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_NEW_4"),
			"IMAGE_ID" => array(
				"name" => "4.png",
				"type" => "image/jpeg",
				"tmp_name" => $pathToImages."/4.png",
				"error" => "0",
				"size" => @filesize($pathToImages."/4.png"),
				"MODULE_ID" => "socialnetwork"
			),
			"NUMBER_OF_MEMBERS" => 1,
			"INITIATE_PERMS" => "E",
			"SPAM_PERMS" => "N",
			"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
		);
*/
	}

	foreach ($arGroups as $ind => $arGroup)
	{
		$dbSubject = CSocNetGroup::GetList(
			array(),
			array(
				"NAME" => $arGroup["NAME"],
				"SITE_ID" => WIZARD_SITE_ID
			)
		);
		if (!$dbSubject->Fetch())
		{
			$idTmp = CSocNetGroup::Add($arGroup);
			if ($idTmp)
			{
				$arGroupsId[$ind] = intval($idTmp);
			}
			else
			{
				if ($e = $GLOBALS["APPLICATION"]->GetException())
					$errorMessage .= $e->GetString();
			}
		}
	}
}

if ($errorMessage == '')
{
	foreach ($arGroupsId as $ind => $val)
	{
		CSocNetUserToGroup::Add(
			array(
				"USER_ID" => 1,
				"GROUP_ID" => $val,
				"ROLE" => "A",
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
				"INITIATED_BY_USER_ID" => 1,
				"MESSAGE" => false,
			)
		);
		if (CModule::IncludeModule("disk"))
		{
			$groupStorage = \Bitrix\Disk\Driver::getInstance()->addGroupStorage($val);
			if($groupStorage)
			{
				$errorCollection = new Bitrix\Disk\Internals\Error\ErrorCollection;
				\Bitrix\Disk\Sharing::connectGroupToSelfUserStorage(1, $groupStorage, $errorCollection);
			}
		}
	}
}

if ($errorMessage == '')
{
	$userTagList = [
		Loc::getMessage('SONET_USER_TAG_1'),
		Loc::getMessage('SONET_USER_TAG_2'),
		Loc::getMessage('SONET_USER_TAG_3'),
		Loc::getMessage('SONET_USER_TAG_4')
	];

	foreach($userTagList as $userTag)
	{
		UserTagTable::add([
			'USER_ID' => 0,
			'NAME' => $userTag
		]);
	}
}

if ($errorMessage == '')
{
	// set EUV vor news
	$dbResult = CSocNetEventUserView::GetList(
					array("ENTITY_ID" => "ASC"),
					array(
						"ENTITY_TYPE" => "N",
					)
	);
	$arResult = $dbResult->Fetch();
	if (!$arResult)
	{
		CSocNetEventUserView::Add(
						array(
							"ENTITY_TYPE" => "N",
							"ENTITY_ID" => 0,
							"EVENT_ID" => "news",
							"USER_ID" => 0,
							"USER_ANONYMOUS" => "N"
						)
		);
		
		CSocNetEventUserView::Add(
						array(
							"ENTITY_TYPE" => "N",
							"ENTITY_ID" => 0,
							"EVENT_ID" => "news_comment",
							"USER_ID" => 0,
							"USER_ANONYMOUS" => "N"
						)
		);
	}
/*******
	// tasks 2.0
	$arTasks = array(
		array(
			"CREATED_BY" => 1,
			"RESPONSIBLE_ID" => 1,
			"PRIORITY" => 1,
			"STATUS" => 2,
			"TITLE" => GetMessage("SONET_TASK_TITLE_1"),
			"DESCRIPTION" => GetMessage("SONET_TASK_DESCRIPTION_1"),
			"SITE_ID" => WIZARD_SITE_ID,
			"XML_ID" => md5(GetMessage("SONET_TASK_TITLE_1").GetMessage("SONET_TASK_DESCRIPTION_1").WIZARD_SITE_ID)
		),
		array(
			"CREATED_BY" => 1,
			"RESPONSIBLE_ID" => 1,
			"PRIORITY" => 1,
			"STATUS" => 2,
			"TITLE" => GetMessage("SONET_TASK_TITLE_2"),
			"DESCRIPTION" => GetMessage("SONET_TASK_DESCRIPTION_2"),
			"SITE_ID" => WIZARD_SITE_ID,
			"XML_ID" => md5(GetMessage("SONET_TASK_TITLE_2").GetMessage("SONET_TASK_DESCRIPTION_2").WIZARD_SITE_ID)
		)
	);
	if (CModule::IncludeModule("tasks"))
	{
		foreach($arTasks as $task)
		{
			$obTask = new CTasks();
			$strSql = "SELECT ID FROM b_tasks WHERE XML_ID = '".$task["XML_ID"]."'";
			$rsTask = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($oldTask = $rsTask->Fetch())
			{
				$obTask->Update($oldTask["ID"], $task);
			}
			else
			{
				$obTask->Add($task);
			}
		}
	}
**********/
	// tasks
	$tasksForumId = 0;

	if (CModule::IncludeModule("forum"))
	{
		$forumCode = "intranet_tasks";
		$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => $forumCode));
		if ($arRes = $dbRes->Fetch())
		{
			$tasksForumId = $arRes["ID"];
		}
		else
		{
			$arGroupID = Array(
				"GENERAL" => 0,
				"COMMENTS" => 0,
				"HIDDEN" => 0,
			);
			$dbExistsGroup = CForumGroup::GetListEx(array(), array("LID" => LANGUAGE_ID));
			while ($arExistsGroup = $dbExistsGroup->Fetch())
			{
				foreach ($arGroupID as $xmlID => $ID)
				{
					if ($arExistsGroup["NAME"] == GetMessage($xmlID."_GROUP_NAME") )
						$arGroupID[$xmlID] = $arExistsGroup["ID"];
				}
			}

			$arFields = array(
				"XML_ID" => $forumCode,
				"NAME" => "Intranet Tasks",
				"DESCRIPTION" => false,
				"SORT" => 1,
				"ACTIVE" => "Y",
				"ALLOW_HTML" => "N",
				"ALLOW_ANCHOR" => "Y",
				"ALLOW_BIU" => "Y",
				"ALLOW_IMG" => "Y",
				"ALLOW_LIST" => "Y",
				"ALLOW_QUOTE" => "Y",
				"ALLOW_CODE" => "Y",
				"ALLOW_FONT" => "Y",
				"ALLOW_SMILES" => "Y",
				"ALLOW_UPLOAD" => "A",
				"ALLOW_NL2BR" => "N",
				"MODERATION" => "N",
				"ALLOW_MOVE_TOPIC" => "Y",
				"ORDER_BY" => "P",
				"DEDUPLICATION" => "N",
				"ORDER_DIRECTION" => "ASC",
				"LID" => LANGUAGE_ID,
				"PATH2FORUM_MESSAGE" => "",
				"ALLOW_UPLOAD_EXT" => "",
				"ASK_GUEST_EMAIL" => "N",
				"USE_CAPTCHA" => "N",
				"SITES" => Array(
					WIZARD_SITE_ID => WIZARD_SITE_DIR."community/forum/messages/forum#FORUM_ID#/topic#TOPIC_ID#/message#MESSAGE_ID#/#message#MESSAGE_ID#",
				),
				"EVENT1" => "forum",
				"EVENT2" => "message",
				"EVENT3" => "",
				"GROUP_ID" => Array(
					"2" => "E",
					WIZARD_PORTAL_ADMINISTRATION_GROUP => "Y",
					WIZARD_EMPLOYEES_GROUP => "M",
				),
				"FORUM_GROUP_ID" => $arGroupID["HIDDEN"],
			);

			$tasksForumId = CForumNew::Add($arFields);
		}
	}

	socialnetwork::__SetLogFilter(WIZARD_SITE_ID);
}
?>