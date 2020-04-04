<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 * 
 * @deprecated
 */

class CTaskComments
{
	// eventing below:

	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 *
	 * @deprecated
	 * @access private
	 */
	public static function onCommentTopicAdd($entityType, $entityId, $arPost, &$arTopic)
	{
		\Bitrix\Tasks\Integration\Forum\Task\Topic::onBeforeAdd($entityType, $entityId, $arPost, $arTopic);
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @param $topicId
	 *
	 * @deprecated
	 */
	public static function onAfterCommentTopicAdd($entityType, $entityId, $topicId)
	{
		return \Bitrix\Tasks\Integration\Forum\Task\Topic::onAfterAdd($entityType, $entityId, $topicId);
	}

	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 * 
	 * This function WILL send notifications in case of comment add through bitrix:forum.comments component
	 * 
	 * @access private
	 * @deprecated
	 */
	public static function onAfterCommentAdd($entityType, $entityId, $arData)
	{
		return \Bitrix\Tasks\Integration\Forum\Task\Comment::onAfterAdd($entityType, $entityId, $arData);
	}

	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 *
	 * @access private
	 */
	public static function onAfterCommentUpdate($entityType, $entityId, $arData)
	{
		return \Bitrix\Tasks\Integration\Forum\Task\Comment::onAfterUpdate($entityType, $entityId, $arData);
	}

	public static function fireOnAfterCommentAddEvent($commentId, $taskId, $commentText, $arFilesIds, $urlPreviewId)
	{
		$arFields = array(
			'TASK_ID'      => $taskId,
			'COMMENT_TEXT' => $commentText,
			'FILES'        => $arFilesIds,
			'URL_PREVIEW'  => $urlPreviewId
		);

		self::addFilesRights($taskId, $arFilesIds);

		foreach(GetModuleEvents('tasks', 'OnAfterCommentAdd', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($commentId, &$arFields));
		}
	}

	/**
	 * @param $messageData
	 * @param $taskData
	 * @param $fromUser
	 * @param $toUsers
	 * @param array $eventData
	 *
	 * @deprecated
	 */
	public static function sendAddMessage($messageData, $taskData, $fromUser, $toUsers, array $eventData = array())
	{
		IncludeModuleLangFile(__FILE__);

		// some sources do not even pass $eventData, so ensure we got at least MESSAGE_ID
		$eventData['MESSAGE_ID'] = $messageData['ID'];

		$user = CTaskNotifications::getUser($fromUser);

		// in comment messages we can get BBCODEs that are not supported by IM. rip them out. also limit text length to 100
		$message = CTaskNotifications::clearNotificationText($messageData['POST_MESSAGE']);
		$messageCropped = \Bitrix\Tasks\Util::trim(self::cropMessage(CTextParser::clearAllTags($message)));

		$messageTemplate  = CTaskNotifications::getGenderMessage($fromUser, "TASKS_COMMENT_MESSAGE_ADD");
		$messageTemplatePush = CTaskNotifications::getGenderMessage($fromUser, "TASKS_COMMENT_MESSAGE_ADD_PUSH");

		if($messageCropped != '')
		{
			$messageTemplate .= GetMessage('TASKS_COMMENT_MESSAGE_ADD_WITH_TEXT');
			$messageTemplatePush .= ': #TASK_COMMENT_TEXT#';
		}

		CTaskNotifications::SendMessageEx($taskData["ID"], $fromUser, $toUsers, array(
			'INSTANT' => str_replace(
				array("#TASK_COMMENT_TEXT#"),
				array('[COLOR=#000000]'.$messageCropped.'[/COLOR]'),
				$messageTemplate
			),
			'EMAIL' => str_replace(
				array("#TASK_COMMENT_TEXT#"),
				array($message),
				$messageTemplate
			),
			'PUSH' => CTaskNotifications::cropMessage($messageTemplatePush, array(
				'USER_NAME' => 			CUser::FormatName(CSite::GetNameFormat(false), $user),
				'TASK_TITLE' => 		$taskData["TITLE"],
				'TASK_COMMENT_TEXT' => 	html_entity_decode(CTextParser::clearAllTags($message)) // convert entities back and drop bbcode tags
			), CTaskNotifications::PUSH_MESSAGE_MAX_LENGTH)
		), array(
			'ENTITY_CODE' => 'COMMENT',
			'ENTITY_OPERATION' => 'ADD',
			'EVENT_DATA' => $eventData,
			'NOTIFY_EVENT' => 'comment',
			'NOTIFY_ANSWER' => true,
			'TASK_DATA' => $taskData,
			'TASK_URL' => array(
				'PARAMETERS' => \Bitrix\Tasks\Integration\Forum\Comment::getUrlParameters($messageData['ID']),
				'HASH' => \Bitrix\Tasks\Integration\Forum\Comment::makeUrlHash($messageData['ID'])
			)
		));
	}

	// replaced
	private static function cropMessage($message)
	{
		// cropped message to instant messenger
		if (strlen($message) >= 100)
		{
			$dot = '...';
			$message = substr($message, 0, 99);

			if (substr($message, -1) === '[')
				$message = substr($message, 0, 98);

			if (
				(($lastLinkPosition = strrpos($message, '[u')) !== false)
				|| (($lastLinkPosition = strrpos($message, 'http://')) !== false)
				|| (($lastLinkPosition = strrpos($message, 'https://')) !== false)
				|| (($lastLinkPosition = strrpos($message, 'ftp://')) !== false)
				|| (($lastLinkPosition = strrpos($message, 'ftps://')) !== false)
			)
			{
				if (strpos($message, ' ', $lastLinkPosition) === false)
					$message = substr($message, 0, $lastLinkPosition);
			}

			$message .= $dot;
		}

		return $message;
	}

	protected static function getOccurAsUserId($messageAuthorId)
	{
		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = ($messageAuthorId ? $messageAuthorId : 1);

		return $messageAuthorId;
	}

	/**
	 * Create new comment for task
	 * 
	 * @param integer $taskId
	 * @param integer $commentAuthorId - ID of user who is comment's author
	 * @param string $commentText - text in BB code
	 * @param additional fields to be passed to CForumMessage::Add() through ForumAddMessage()
	 * 
	 * @throws TasksException, CTaskAssertException
	 * 
	 * @return integer $messageId
	 */
	public static function add($taskId, $commentAuthorId, $commentText, $arFields = array())
	{
		CTaskAssert::assertLaxIntegers($taskId, $commentAuthorId);
		CTaskAssert::assert(is_string($commentText));

		if ( ! CModule::includeModule('forum') )
		{
			throw new TasksException(
				'forum module can not be loaded',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		IncludeModuleLangFile(__FILE__);

		$forumId = CTasksTools::GetForumIdForIntranet();
		$oTask = CTaskItem::getInstance($taskId, $commentAuthorId);
		$arTask = $oTask->getData();

		$outForumTopicId = $outStrUrl = null;
		$arErrorCodes = array();

		$messageId = self::__deprecated_Add(
			$commentText,
			$forumTopicId      = $arTask['FORUM_TOPIC_ID'],
			$forumId,
			$nameTemplate      = CSite::GetNameFormat(false),
			$arTask            = $arTask,
			$permissions       = 'Y',
			$commentId         = 0,
			$givenUserId       = $commentAuthorId,
			$imageWidth        = 300,
			$imageHeight       = 300,
			$arSmiles          = array(),
			$arForum           = CForumNew::GetByID($forumId),
			$messagesPerPage   = 10,
			$arUserGroupArray  = CUser::GetUserGroup($commentAuthorId),
			$backPage          = null,
			$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD"),
			$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT"),
			$strMsgNewTask     = GetMessage("TASKS_COMMENT_SONET_NEW_TASK_MESSAGE"),
			$componentName     = null,
			$outForumTopicId,
			$arErrorCodes,
			$outStrUrl,
			$arFields
		);

		if ( ! ($messageId >= 1) )
		{
			throw new TasksException(
				serialize($arErrorCodes),
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			);
		}

		return ( (int) $messageId );
	}

	/**
	 * Update a comment
	 * 
	 * @deprecated
	 * 
	 * @param integer $taskId
	 * @param integet $commentId
	 * @param integer $commentEditorId - ID of user who is comment's editor
	 * @param string[] $arFields - fields to be updated, including text in BB code
	 * 
	 * @throws TasksException, CTaskAssertException
	 * 
	 * @return boolean
	 */
	public static function update($taskId, $commentId, $commentEditorId, $arFields)
	{
		CTaskAssert::assertLaxIntegers($taskId, $commentId, $commentEditorId);
		CTaskAssert::assert(is_array($arFields) && !empty($arFields));

		if ( ! CModule::includeModule('forum') )
		{
			throw new TasksException(
				'forum module can not be loaded',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		IncludeModuleLangFile(__FILE__);

		$forumId = CTasksTools::GetForumIdForIntranet();
		$oTask = CTaskItem::getInstance($taskId, $commentEditorId);
		$arTask = $oTask->getData();

		$outForumTopicId = $outStrUrl = null;
		$arErrorCodes = array();

		$arFields = array_merge(array(
			'EDITOR_ID' => $commentEditorId
		), $arFields);

		$messageId = self::__deprecated_Add(
			$arFields['POST_MESSAGE'],
			$forumTopicId      = $arTask['FORUM_TOPIC_ID'],
			$forumId,
			$nameTemplate      = CSite::GetNameFormat(false),
			$arTask            = $arTask,
			$permissions       = 'Y',
			$commentId         = $commentId,
			$givenUserId       = $commentEditorId,
			$imageWidth        = 300,
			$imageHeight       = 300,
			$arSmiles          = array(),
			$arForum           = CForumNew::GetByID($forumId),
			$messagesPerPage   = 10,
			$arUserGroupArray  = CUser::GetUserGroup($commentEditorId),
			$backPage          = null,
			$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD"),
			$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT"),
			$strMsgNewTask     = GetMessage("TASKS_COMMENT_SONET_NEW_TASK_MESSAGE"),
			$componentName     = null,
			$outForumTopicId,
			$arErrorCodes,
			$outStrUrl,
			$arFields
		);

		if ( ! ($messageId >= 1) )
		{
			throw new TasksException(
				serialize($arErrorCodes),
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			);
		}

		return ( true );
	}

	/**
	 * Never used
	 *
	 * @deprecated
	 */
	public static function Remove($taskId, $commentId, $userId, $arParams)
	{
		global $DB;

		if (self::CanRemoveComment($taskId, $commentId, $userId, $arParams) !== true)
			throw new TasksException('', TasksException::TE_ACCESS_DENIED);

		$strErrorMessage = $strOKMessage = '';
		$result = ForumDeleteMessage($commentId, $strErrorMessage, $strOKMessage, array('PERMISSION' => 'Y'));

		if($result)
		{
			if (CModule::IncludeModule("socialnetwork"))
			{
				$oTask = CTaskItem::getInstance($taskId, CTasksTools::GetCommanderInChief());
				$arTask = $oTask->getData();

				$bCrmTask = (
					isset($arTask["UF_CRM_TASK"])
					&& (
						(
							is_array($arTask["UF_CRM_TASK"])
							&& (
								isset($arTask["UF_CRM_TASK"][0])
								&& strlen($arTask["UF_CRM_TASK"][0]) > 0
							)
						)
						||
						(
							!is_array($arTask["UF_CRM_TASK"])
							&& strlen($arTask["UF_CRM_TASK"]) > 0
						)
					)
				);

				$dbRes = CSocNetLogComments::GetList(
					array(),
					array(
						'EVENT_ID'	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
						'SOURCE_ID' => $commentId
					),
					false,
					false,
					array('ID')
				);

				if ($arRes = $dbRes->Fetch())
				{
					CSocNetLogComments::Delete($arRes['ID']);
				}
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = ($userId ? $userId : 1);

			// Tasks log
			$arLogFields = array(
				'TASK_ID'       =>  $taskId,
				'USER_ID'       =>  $occurAsUserId,
				'~CREATED_DATE' =>  $DB->CurrentTimeFunction(),
				'FIELD'         => 'COMMENT_REMOVE'
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);

		}

		return $result;
	}


	/**
	 * @deprecated
	 */
	public static function CanUpdateComment($taskId, $commentId, $userId, $arParams)
	{
		$bCommentsCanBeUpdated = COption::GetOptionString('tasks', 'task_comment_allow_edit'); // there could be trouble

		if ( ! $bCommentsCanBeUpdated || !CModule::IncludeModule('forum'))
			return (false);

		return self::CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams);
	}


	/**
	 * @deprecated
	 */
	public static function CanRemoveComment($taskId, $commentId, $userId, $arParams)
	{
		$bCommentsCanBeRemoved = COption::GetOptionString('tasks', 'task_comment_allow_remove'); // there could be trouble

		if ( ! $bCommentsCanBeRemoved || !CModule::IncludeModule('forum'))
			return (false);

		return self::CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams);
	}

	/**
	 * @deprecated
	 */
	private static function CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams)
	{
		$filter = array('TOPIC_ID' => $arParams['FORUM_TOPIC_ID']);

		// have no idea in which case the following parameters will be used:
		if(isset($arParams['FORUM_ID']))
			$filter['FORUM_ID'] = $arParams['FORUM_ID'];
		if(isset($arParams['APPROVED']))
			$filter['APPROVED'] = $arParams['APPROVED'];

		$res = CForumMessage::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			0,
			array('bShowAll' => true)
		);

		// Take last message
		$comment = false;
		$lastComment = false;
		$cnt = 0;
		while ($ar = $res->fetch())
		{
			if($ar['ID'] == $commentId)
				$comment = $ar;

			$lastComment = $ar;
			$cnt++;
		}

		if ( $cnt == 0 ) // no comments in the topic
			return (false);

		if ( empty($comment) ) // comment not found
			return (false);

		if (
			CTasksTools::isAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
		)
		{
			return (true);
		}
		elseif ($userId == $lastComment['AUTHOR_ID'])
		{
			if ($commentId != $lastComment['ID'])	// it's not the last comment
				return (false);
			else
				return (true);
		}
		else
			return (false);
	}

	/**
	 * @deprecated
	 */
	public static function onAfterTaskAdd($taskId, $arFields)
	{
		if ( ! isset($arFields['UF_TASK_WEBDAV_FILES']) || !is_array($arFields['UF_TASK_WEBDAV_FILES']))
			return;

		$arFilesIds = array_filter($arFields['UF_TASK_WEBDAV_FILES']);

		if (empty($arFilesIds))
			return;

		self::addFilesRights($taskId, $arFilesIds);
	}

	/**
	 * @deprecated
	 */
	public static function onAfterTaskUpdate($taskId, $arTask, $arFields)
	{
		// List of files to be updated
		if (isset($arFields['UF_TASK_WEBDAV_FILES']) && is_array($arFields['UF_TASK_WEBDAV_FILES']))
			$arFilesIds = array_filter($arFields['UF_TASK_WEBDAV_FILES']);
		else
			$arFilesIds = array();

		$arAddedMembers = array_diff(
			self::getTaskMembersByFields($arFields),
			self::getTaskMembersByFields($arTask)
		);

		// If added new members to task - rights for ALL files must be updated
		if ( ! empty($arAddedMembers) )
		{
			// Get all files of task
			if (is_array($arTask['UF_TASK_WEBDAV_FILES']))
				$arFilesIds = array_merge($arFilesIds, $arTask['UF_TASK_WEBDAV_FILES']);

			// Get all files from all comments
			$arFilesIds = array_merge($arFilesIds, self::getCommentsFiles($arTask['FORUM_TOPIC_ID']));
		}

		// Nothing to do?
		if (empty($arFilesIds))
			return;

		self::addFilesRights($taskId, $arFilesIds);
	}

	/**
	 * @deprecated
	 */
	private static function getCommentsFiles($forumTopicId)
	{
		$arFilesIds = array();

		if (
			CModule::IncludeModule('forum')
			&& ($forumId = CTasksTools::GetForumIdForIntranet())
			&& ($forumId >= 1)
		)
		{
			$rc = CForumMessage::GetListEx(
				array(),
				array('FORUM_ID' => $forumId, 'TOPIC_ID' => $forumTopicId)
			);

			$arMessagesIds = array();
			while ($arMsg = $rc->fetch())
				$arMessagesIds[] = (int) $arMsg['ID'];

			foreach ($arMessagesIds as $msgId)
			{
				$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("FORUM_MESSAGE", $msgId, LANGUAGE_ID, 1);

				if (isset($arUF['UF_FORUM_MESSAGE_DOC'], $arUF['UF_FORUM_MESSAGE_DOC']['VALUE']))
				{
					if (is_array($arUF['UF_FORUM_MESSAGE_DOC']['VALUE']))
						$arFilesIds = array_merge($arFilesIds, $arUF['UF_FORUM_MESSAGE_DOC']['VALUE']);
				}				
			}
		}

		$arFilesIds = array_unique(array_map('intval', $arFilesIds));

		return ($arFilesIds);
		/*
		if (CModule::IncludeModuel("forum"))
		{
			$arFilter = (is_array($arFilter) ? $arFilter : array($arFilter));
			$arFilter[">UF_FORUM_MESSAGE_DOC"] = 0;
			$db_res = CForumMessage::GetList(array("ID" => "ASC"), $arFilter, false, 0, array("SELECT" => array("UF_FORUM_MESSAGE_DOC")));
			$arDocs = array();
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do {
					if (!empty($res["UF_FORUM_MESSAGE_DOC"]) && is_array($res["UF_FORUM_MESSAGE_DOC"]))
						$arDocs = array_merge($arDocs, $res["UF_FORUM_MESSAGE_DOC"]);
				} while ($res = $db_res->Fetch());
			}
		}
		*/
	}


	/**
	 * Add rights for reading files by given users.
	 * @deprecated
	 */
	private static function addFilesRights($taskId, $arFilesIds)
	{
		$arFilesIds = array_unique(array_filter($arFilesIds));

		// Nothing to do?
		if (empty($arFilesIds))
			return;

		if(!CModule::IncludeModule('webdav') || !CModule::IncludeModule('iblock'))
			return;

		$arRightsTasks = CWebDavIblock::GetTasks();	// tasks-operations

		$oTask  = new CTaskItem((int)$taskId, CTasksTools::getCommanderInChief());
		$arTask = $oTask->getData(false);

		$arTaskMembers = array_unique(array_merge(
			array($arTask['CREATED_BY'], $arTask['RESPONSIBLE_ID']),
			$arTask['AUDITORS'],
			$arTask['ACCOMPLICES']
		));

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(
			array(),
			array('ID' => $arFilesIds, 'SHOW_NEW' => 'Y'),
			false,
			false,
			array('ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW')
		);

		if ($dbWDFile)
		{
			$i = 0;
			$arRightsForTaskMembers = array();
			foreach ($arTaskMembers as $userId)
			{
				// For intranet users and their managers
				$arRightsForTaskMembers['n' . $i++] = array(
					'GROUP_CODE' => 'IU' . $userId,
					'TASK_ID'    => $arRightsTasks['R']		// rights for reading
				);

				// For extranet users
				$arRightsForTaskMembers['n' . $i++] = array(
					'GROUP_CODE' => 'U' . $userId,
					'TASK_ID'    => $arRightsTasks['R']		// rights for reading
				);
			}
			$iNext = $i;

			while ($arWDFile = $dbWDFile->Fetch())
			{
				if ( ! $arWDFile['IBLOCK_ID'] )
					continue;

				$fileId = $arWDFile['ID'];

				if (CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
				{
					$ibRights = new CIBlockElementRights($arWDFile['IBLOCK_ID'], $fileId);
					$arCurRightsRaw = $ibRights->getRights();

					// Preserve existing rights
					$i = $iNext;
					$arRights = $arRightsForTaskMembers;
					foreach ($arCurRightsRaw as $arRightsData)
					{
						$arRights['n' . $i++] = array(
							'GROUP_CODE' => $arRightsData['GROUP_CODE'],
							'TASK_ID'    => $arRightsData['TASK_ID']
						);
					}

					$ibRights->setRights($arRights);
				}
			}
		}
	}

	/**
	 * @deprecated
	 */
	private static function getTaskMembersByTaskId($taskId, $excludeUser = 0)
	{
		$oTask = CTaskItem::getInstance((int)$taskId, CTasksTools::GetCommanderInChief());
		$arTask = $oTask->getData(false);

		$arUsersIds = CTaskNotifications::getRecipientsIDs($arTask, $bExcludeLoggedUser = false);

		$excludeUser = (int) $excludeUser;

		if ($excludeUser >= 1)
		{
			$currentUserPos = array_search($excludeUser, $arUsersIds);
			if ($currentUserPos !== false)
				unset($arUsersIds[$currentUserPos]);
		}
		else if ($excludeUser < 0)
			CTaskAssert::logWarning('[0x3c2a31fe] invalid user id (' . $excludeUser . ')');

		return ($arUsersIds);
	}

	/**
	 * @deprecated
	 */
	private static function getTaskMembersByFields($arFields)
	{
		$arMembers = array();

		if (isset($arFields['CREATED_BY']))
			$arMembers[] = $arFields['CREATED_BY'];

		if (isset($arFields['RESPONSIBLE_ID']))
			$arMembers[] = $arFields['RESPONSIBLE_ID'];

		if (isset($arFields['AUDITORS']))
		{
			if ( ! is_array($arFields['AUDITORS']) )
				$arFields['AUDITORS'] = array($arFields['AUDITORS']);

			$arMembers = array_merge($arMembers, $arFields['AUDITORS']);
		}

		if (isset($arFields['ACCOMPLICES']))
		{
			if ( ! is_array($arFields['ACCOMPLICES']) )
				$arFields['ACCOMPLICES'] = array($arFields['ACCOMPLICES']);

			$arMembers = array_merge($arMembers, $arFields['ACCOMPLICES']);
		}

		$arMembers = array_unique(array_map('intval', $arMembers));

		return ($arMembers);
	}


	/**
	 * WARNING! This method is transitional and can be changed without 
	 * any notifications! Don't use it.
	 * 
	 * @deprecated
	 */
	public static function __deprecated_Add(
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
		&$outForumTopicId,
		&$arErrorCodes,
		&$outStrUrl,
		$arFieldsAdditional = array()
	)
	{
		global $DB;

		if (is_array($arTask))
		{
			if ( ! array_key_exists('~TITLE', $arTask) )
			{
				$arTmpTask = $arTask;

				foreach ($arTmpTask as $key => $value)
				{
					if (substr($key, 0, 1) !== '~')
						$arTask['~' . $key] = $arTmpTask[$key];
				}
			}
		}

		$MID = 0;
		$TID = 0;

		if (($forumTopicId > 0) && (CForumTopic::GetByID($forumTopicId) === false))
			$forumTopicId = false;

		if ($forumTopicId <= 0)
		{
			$arUserStart = array(
				"ID" => intVal($arTask["CREATED_BY"]),
				"NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"]
			);

			if ($arUserStart["ID"] > 0)
			{
				$res = array();
				$db_res = CForumUser::GetListEx(
					array(),
					array("USER_ID" => $arTask["CREATED_BY"])
				);

				if ($db_res && $res = $db_res->Fetch())
				{
					$res["FORUM_USER_ID"] = intVal($res["ID"]);
					$res["ID"] = $res["USER_ID"];
				}
				else
				{
					$db_res = CUser::GetByID($arTask["CREATED_BY"]);
					if ($db_res && $res = $db_res->Fetch())
					{
						$res["SHOW_NAME"] = COption::GetOptionString("forum", "USER_SHOW_NAME", "Y");
						$res["USER_PROFILE"] = "N";
					}
				}

				if (!empty($res))
				{
					$arUserStart = $res;
					$sName = ($res["SHOW_NAME"] == "Y" ? trim(CUser::FormatName($nameTemplate, $res)) : "");
					$arUserStart["NAME"] = (empty($sName) ? trim($res["LOGIN"]) : $sName);
				}
			}

			$arUserStart["NAME"] = (empty($arUserStart["NAME"]) ? $GLOBALS["FORUM_STATUS_NAME"]["guest"] : $arUserStart["NAME"]);
			$DB->StartTransaction();

			$arFields = Array(
				"TITLE" => $arTask["~TITLE"],
				"FORUM_ID" => $forumId,
				"USER_START_ID" => $arUserStart["ID"],
				"USER_START_NAME" => $arUserStart["NAME"],
				"LAST_POSTER_NAME" => $arUserStart["NAME"],
				"APPROVED" => "Y",
				"PERMISSION_EXTERNAL" => $permissions,
				"PERMISSION" => $permissions,
				"NAME_TEMPLATE" => $nameTemplate,
				'XML_ID' => 'TASK_' . $arTask['ID']
			);

			$TID = CForumTopic::Add($arFields);

			if (intVal($TID) <= 0)
				$arErrorCodes[] = array('code' => 'topic is not created');
			else
			{
				$arFields = array(
					"FORUM_TOPIC_ID" => $TID
				);

				$task = new CTasks();
				$task->Update($arTask["ID"], $arFields);
			}

			if (!empty($arErrorCodes))
			{
				$DB->Rollback();
				return false;
			}
			else
			{
				$DB->Commit();
			}
		}

		$arFieldsG = array(
			"POST_MESSAGE" => $commentText,
			"AUTHOR_NAME"  => '',
			"AUTHOR_EMAIL" => $GLOBALS['USER']->GetEmail(),
			"USE_SMILES" => NULL,
			"PARAM2" => $arTask['ID'],
			"TITLE"               => $arTask["~TITLE"],
			"PERMISSION_EXTERNAL" => $permissions,
			"PERMISSION"          => $permissions,
		);

		// UF_* forwarding
		if(is_array($arFieldsAdditional))
		{
			foreach($arFieldsAdditional as $field => $value)
			{
				if(strlen($field) && substr($field, 0, 3) == 'UF_')
				{
					$arFieldsG[$field] = $value;
					$GLOBALS[$field] = $value; // strange behaviour required for ForumMessageAdd() to handle UF_* properly
				}
			}
		}

		if (!empty($_FILES["REVIEW_ATTACH_IMG"]))
		{
			$arFieldsG["ATTACH_IMG"] = $_FILES["REVIEW_ATTACH_IMG"];
		}
		else
		{
			$arFiles = array();
			if (!empty($_REQUEST["FILES"]))
			{
				foreach ($_REQUEST["FILES"] as $key)
				{
					$arFiles[$key] = array("FILE_ID" => $key);
					if (!in_array($key, $_REQUEST["FILES_TO_UPLOAD"]))
					{
						$arFiles[$key]["del"] = "Y";
					}
				}
			}
			if (!empty($_FILES))
			{
				$res = array();
				foreach ($_FILES as $key => $val)
				{
					if (substr($key, 0, strLen("FILE_NEW")) == "FILE_NEW" && !empty($val["name"]))
					{
						$arFiles[] = $_FILES[$key];
					}
				}
			}
			if (!empty($arFiles))
			{
				$arFieldsG["FILES"] = $arFiles;
			}
		}
		$TOPIC_ID = ($forumTopicId > 0 ? $forumTopicId : $TID);

		$MESSAGE_ID = 0;
		$MESSAGE_TYPE = $TOPIC_ID > 0 ? "REPLY" : "NEW";
		if (COption::GetOptionString("tasks", "task_comment_allow_edit") && $MESSAGE_ID = intval($commentId))
		{
			$MESSAGE_TYPE = "EDIT";
		}

		$strErrorMessage = '';
		$strOKMessage = '';
		$MID = ForumAddMessage($MESSAGE_TYPE, $forumId, $TOPIC_ID, $MESSAGE_ID, 
			$arFieldsG, $strErrorMessage, $strOKMessage, false, 
			$_POST["captcha_word"], 0, $_POST["captcha_code"], $nameTemplate);

		if ($MID <= 0 || !empty($strErrorMessage))
		{
			$arErrorCodes[] = array(
				'code'  => 'message is not added 2',
				'title' => (empty($strErrorMessage) ? NULL : $strErrorMessage)
			);
		}
		else
		{
			$arMessage = CForumMessage::GetByID($MID);

			if ($forumTopicId <= 0)
			{
				$forumTopicId = $TID = intVal($arMessage["TOPIC_ID"]);
			}

			$outForumTopicId = intVal($forumTopicId);

			if ($componentName !== null)
				ForumClearComponentCache($componentName);

			$strURL = (!empty($backPage) ? $backPage : $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result")));
			$strURL = ForumAddPageParams(
				$strURL,
				array(
					"MID" => $MID, 
					"result" => ($arForum["MODERATION"] != "Y" 
						|| CForumNew::CanUserModerateForum($forumId, $arUserGroupArray) ? "reply" : "not_approved"
					)
				), 
				false, 
				false
			);
			$outStrUrl = $strURL;

			/*
			// sonet log
			if (CModule::IncludeModule("socialnetwork"))
			{
				$dbRes = CSocNetLog::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => "tasks",
						"SOURCE_ID" => $arTask["ID"]
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
				);
				if ($arRes = $dbRes->Fetch())
				{
					$log_id = $arRes["TMP_ID"];
					$entity_type = $arRes["ENTITY_TYPE"];
					$entity_id = $arRes["ENTITY_ID"];
				}
				else
				{
					$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
					$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

					$rsUser = CUser::GetByID($arTask["CREATED_BY"]);
					if ($arUser = $rsUser->Fetch())
					{
						$arSoFields = Array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "tasks",
							"LOG_DATE" => $arTask["CREATED_DATE"],
							"TITLE_TEMPLATE" => "#TITLE#",
							"TITLE" => htmlspecialcharsBack($arTask["~TITLE"]),
							"MESSAGE" => "",
							"TEXT_MESSAGE" => $strMsgNewTask,
							"MODULE_ID" => "tasks",
							"CALLBACK_FUNC" => false,
							"SOURCE_ID" => $arTask["ID"],
							"ENABLE_COMMENTS" => "Y",
							"USER_ID" => $arTask["CREATED_BY"],
							"URL" => CTaskNotifications::GetNotificationPath($arUser, $arTask["ID"]),
							"PARAMS" => serialize(array("TYPE" => "create"))
						);
						$log_id = CSocNetLog::Add($arSoFields, false);
						if (intval($log_id) > 0)
						{
							CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
							$arRights = CTaskNotifications::__UserIDs2Rights(CTaskNotifications::GetRecipientsIDs($arTask, false));
							if($arTask["GROUP_ID"])
								$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
							CSocNetLogRights::Add($log_id, $arRights);
						}
					}
				}

				if (intval($log_id) > 0)
				{
					$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

					CSocNetLog::Update(
						$log_id,
						array(
							'PARAMS' => serialize(array('TYPE' => 'comment'))
						)
					);

					$arFieldsForSocnet = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "tasks_comment",
						"MESSAGE" => $sText,
						"TEXT_MESSAGE" => $parser->convert4mail($sText),
						"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
						"MODULE_ID" => "tasks",
						"SOURCE_ID" => $MID,
						"LOG_ID" => $log_id,
						"RATING_TYPE_ID" => "FORUM_POST",
						"RATING_ENTITY_ID" => $MID
					);

					if ($MESSAGE_TYPE == "EDIT")
					{
						$dbRes = CSocNetLogComments::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID"	=> array("tasks_comment"),
								"SOURCE_ID" => $MID
							),
							false,
							false,
							array("ID")
						);
						while ($arRes = $dbRes->Fetch())
						{
							CSocNetLogComments::Update($arRes["ID"], $arFieldsForSocnet);
						}
					}
					else
					{
						$arFieldsForSocnet['USER_ID']   = $givenUserId;
						$arFieldsForSocnet['=LOG_DATE'] = $GLOBALS['DB']->CurrentTimeFunction();

						$ufFileID = array();
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $MID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

						if (count($ufFileID) > 0)
							$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $MID, LANGUAGE_ID);
						if ($ufDocID)
							$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;
							
						$ufDocVer = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_VER", $MID, LANGUAGE_ID);
						if ($ufDocVer)
							$arFieldsForSocnet["UF_SONET_COM_VER"] = $ufDocVer;

						if (
							isset($arFieldsAdditional["ANCILLARY"])
							&& $arFieldsAdditional["ANCILLARY"]
						)
						{
							CSocNetLogComments::Add($arFieldsForSocnet, false, false, false);
						}
						else
						{
							$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
							CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
						}
					}
				}
			}
			*/

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = ($arMessage["AUTHOR_ID"] ? $arMessage["AUTHOR_ID"] : 1);

			// Tasks log
			$arLogFields = array(
				"TASK_ID" => $arTask["ID"],
				"USER_ID" => $occurAsUserId,
				"CREATED_DATE" => ($arMessage["EDIT_DATE"] ? ConvertTimeStamp(MakeTimeStamp($arMessage["EDIT_DATE"], CSite::GetDateFormat()), "FULL") : $arMessage["POST_DATE"]),
				"FIELD" => "COMMENT",
				"TO_VALUE" => $MID
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}

		return ($MID);	// Message id
	}

	/**
	 * @deprecated
	 */
	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */ $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskcomments', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$occurAsUserId = CTasksTools::getOccurAsUserId();
				if ( ! $occurAsUserId )
					$occurAsUserId = $executiveUserId;

				$taskId          = $argsParsed[0];
				$commentText     = $argsParsed[1];
				$commentAuthorId = $occurAsUserId;
				$returnValue     = self::add($taskId, $commentAuthorId, $commentText);
			}
		}
		else
		{
			$taskId = array_shift($argsParsed);
			$oTask  = self::getInstanceFromPool($taskId, $executiveUserId);
			$returnValue = call_user_func_array(array($oTask, $methodName), $argsParsed);
		}

		return (array($returnValue, null));
	}


	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @deprecated
	 * @access private
	 */
	public static function getManifest()
	{
		return(array(
			'Manifest version' => '1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'comment',
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'add' => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'commentText',
							'type'        => 'string'
						)
					)
				)
			)
		));
	}
}
