<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
if (!CModule::IncludeModule("forum"))
{
	return false;
}
elseif (is_set($_REQUEST["TASK_ID"]) && $arParams["TASK_ID"] != $_REQUEST["TASK_ID"])
{
	return false;
}
$this->IncludeComponentLang("action.php");

if (!check_bitrix_sessid())
{
	$arError[] = array(
		"code" => "session time is up",
		"title" => GetMessage("F_ERR_SESSION_TIME_IS_UP")
	);
}
elseif ($arParams['PERMISSION'] <= "E")
{
	$arError[] = array(
		"code" => "access denied",
		"title" => GetMessage("F_ERR_NOT_RIGHT_FOR_ADD")
	);
}
elseif (isset($_POST['remove_comment']) && ($_POST['remove_comment'] === 'Y'))
{
	try
	{
		CTaskComments::Remove(
			$arResult["TASK"]['ID'],
			$_REQUEST["COMMENT_ID"],
			$USER->getId(),
			array(
				'FORUM_ID'       =>  $arParams['FORUM_ID'],
				'FORUM_TOPIC_ID' =>  $arResult["FORUM_TOPIC_ID"],
				'APPROVED'       => 'Y'
			)
		);
		$arResult['TASK']['COMMENTS_COUNT']--;

		LocalRedirect(
			$APPLICATION->GetCurPageParam(
				"",
				array("sessid", "ACTION")
			)
		);
	}
	catch (Exception $e)
	{
		$arError[] = array(
			"code"  => 'failure during comment removing',
			"title" => GetMessage('F_ERR_REMOVE_COMMENT')
		);
	}
}
elseif (empty($_REQUEST["preview_comment"]) || $_REQUEST["preview_comment"] == "N")
{
	$FORUM_TOPIC_ID = $arResult["FORUM_TOPIC_ID"] ? $arResult["FORUM_TOPIC_ID"] : 0;
	$strErrorMessage = "";

	if (strLen($_POST["REVIEW_TEXT"]) < 1)
	{
		if (empty($_REQUEST["FILES"]) && empty($_FILES))
		{
			$arError[] = array(
				"code" => "post is empty",
				"title" => GetMessage("F_ERR_NO_REVIEW_TEXT")
			);
		}
		else
		{
			$_POST['REVIEW_TEXT'] = '[COLOR=#FFFFFF] [/COLOR]';
		}
	}

	if (!empty($arError))
	{
		return false;
	}

	$commentText = $_POST["REVIEW_TEXT"];
	$forumTopicId = $FORUM_TOPIC_ID;
	$forumId = $arParams["FORUM_ID"];
	$nameTemplate = $arParams["NAME_TEMPLATE"];
	$arTask = $arResult["TASK"];
	$permissions = $arParams["PERMISSION"];
	$commentId = $_REQUEST["COMMENT_ID"];		// only if edit
	$givenUserId = $USER->GetID();
	$imageWidth = $arParams['IMAGE_SIZE'];
	$imageHeight = $arParams['IMAGE_SIZE'];
	$arSmiles = $arResult["SMILES"];
	$arForum = $arResult["FORUM"];
	$messagesPerPage = $arParams["MESSAGES_PER_PAGE"];
	$arUserGroupArray = $GLOBALS["USER"]->GetUserGroupArray();
	$backPage = $_REQUEST["back_page"];


	$rsUser = CUser::GetList(
		$by = 'id',
		$order = 'asc',
		array('ID_EQUAL_EXACT' => (int) $givenUserId),
		array('FIELDS' => array('PERSONAL_GENDER'))
	);

	$strMsgAddComment = GetMessage("TASKS_COMMENT_MESSAGE_ADD");
	$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT");

	if ($arUser = $rsUser->fetch())
	{
		switch ($arUser['PERSONAL_GENDER'])
		{
			case "F":
			case "M":
				$strMsgAddComment = GetMessage("TASKS_COMMENT_MESSAGE_ADD" . '_' . $arUser['PERSONAL_GENDER']);
				$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT" . '_' . $arUser['PERSONAL_GENDER']);
			break;

			default:
			break;
		}
	}

	$strMsgNewTask = GetMessage("TASKS_COMMENT_SONET_NEW_TASK_MESSAGE");

	$arErrorCodes = array();
	$outForumTopicId = null;
	$outStrUrl = '';

	$rc = CTaskComments::__deprecated_Add(
		$commentText,
		$forumTopicId,
		$forumId,
		$nameTemplate,
		$arTask,
		$permissions,
		$commentId,
		$givenUserId,
		$imageWidth,
		$imageHeight,
		$arSmiles,
		$arForum,
		$messagesPerPage,
		$arUserGroupArray,
		$backPage,
		$strMsgAddComment,
		$strMsgEditComment,
		$strMsgNewTask,
		$componentName,
		$outForumTopicId,
		$arErrorCodes,
		$outStrUrl
	);
	$strURL = $outStrUrl;

	$arResult["FORUM_TOPIC_ID"] = $outForumTopicId;

	$strOKMessage = GetMessage("COMM_COMMENT_OK");

	foreach ($arErrorCodes as $v)
	{
		if (is_string($v['title']))
			$errTitle = $v['title'];
		else
		{
			switch ($v['code'])
			{
				case 'topic is not created':
					$errTitle = GetMessage('F_ERR_ADD_TOPIC');
				break;

				case 'message is not added 2':
					$errTitle = GetMessage('F_ERR_ADD_MESSAGE');
				break;

				default:
					$errTitle = '';
				break;
			}
		}

		$arError[] = array(
			'code'  => $v['code'],
			'title' => $errTitle
		);
	}

	if (empty($arError))
		LocalRedirect($strURL);
}
else
{
	$parser->allow["SMILES"] = ($_POST["REVIEW_USE_SMILES"] != "Y" ? "N" : $arResult["FORUM"]["ALLOW_SMILES"]);
	$arResult["MESSAGE_VIEW"] = array(
		"POST_MESSAGE_TEXT" => $parser->convertText($_POST["REVIEW_TEXT"]),
		"AUTHOR_NAME" => htmlspecialcharsEx($arResult["USER"]["SHOWED_NAME"]),
		"AUTHOR_ID" => intVal($USER->GetID()),
		"AUTHOR_URL" => CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_PROFILE_VIEW"], array("UID" => $USER->GetID())),
		"POST_DATE" => CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], time()+CTimeZone::GetOffset()),
		"FILES" => array()
	);

	$arFields = array(
		"FORUM_ID" => intVal($arParams["FORUM_ID"]),
		"TOPIC_ID" => 0,
		"MESSAGE_ID" => 0,
		"USER_ID" => intVal($GLOBALS["USER"]->GetID())
	);

	$arFiles = array();
	$arFilesExists = array();
	$res = array();

	foreach ($_FILES as $key => $val)
	{
		if ((substr($key, 0, strLen("FILE_NEW")) == "FILE_NEW") && !empty($val["name"]))
		{
			$arFiles[] = $_FILES[$key];
		}
	}
	foreach ($_REQUEST["FILES"] as $key => $val)
	{
		if (!in_array($val, $_REQUEST["FILES_TO_UPLOAD"]))
		{
			$arFiles[$val] = array("FILE_ID" => $val, "del" => "Y");
			unset($_REQUEST["FILES"][$key]);
			unset($_REQUEST["FILES_TO_UPLOAD"][$key]);
		}
		else
		{
			$arFilesExists[$val] = array("FILE_ID" => $val);
		}
	}

	if (!empty($arFiles))
	{
		$res = CForumFiles::Save($arFiles, $arFields);
		$res1 = $GLOBALS['APPLICATION']->GetException();
		if ($res1)
		{
			$arError[] = array(
				"code" => "file upload error",
				"title" => $res1->GetString()
			);
		}
	}

	$res = is_array($res) ? $res : array();
	foreach ($res as $key => $val)
	{
		$arFilesExists[$key] = $val;
	}
	$arFilesExists = array_keys($arFilesExists);
	sort($arFilesExists);
	$arResult["MESSAGE_VIEW"]["FILES"] = $_REQUEST["FILES"] = $arFilesExists;
}
