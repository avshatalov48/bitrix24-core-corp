<?
/**
 * Class implements all further interactions with "forum" module considering "task comment" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration\Forum\Task;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

final class Comment extends \Bitrix\Tasks\Integration\Forum\Comment
{
	public static function getForumId()
	{
		// todo: refactor
		return \CTasksTools::getForumIdForIntranet();
	}

	/**
	 * Add new task comment
	 *
	 * @param $taskId
	 * @param mixed[] $data
	 * @return Result
	 *
	 * @access private
	 */
	public static function add($taskId, array $data)
	{
		$result = new Result();

		if(!static::includeModule())
		{
			$result->addError('NO_MODULE', 'No forum module installed');
			return $result;
		}

		if(!array_key_exists('AUTHOR_ID', $data))
		{
			$data['AUTHOR_ID'] = User::getId();
		}
		if(!array_key_exists('USE_SMILES', $data))
		{
			$data['USE_SMILES'] = 'Y';
		}

		$feed = new \Bitrix\Forum\Comments\Feed(
			static::getForumId(),
			array(
				"type" => "TK",
				"id" => $taskId,
				"xml_id" => "TASK_".$taskId
			),
			$data['AUTHOR_ID']
		);

		// $feed->add() works with global-defined user fields
		foreach($data as $k => $v)
		{
			if(\Bitrix\Tasks\Util\UserField::isUFKey($k))
			{
				$GLOBALS[$k] = $data[$k];
				unset($data[$k]);
			}
		}

		$addResult = $feed->add($data);
		if($addResult)
		{
			$result->setData($addResult);
		}
		else
		{
			$errors = $feed->getErrors();
			if(is_array($errors))
			{
				foreach($errors as $error)
				{
					$result->getErrors()->add('ACTION_FAILED_REASON', $error->getMessage(), Error::TYPE_FATAL, array('CODE' => $error->getCode()));
				}
			}
		}

		return $result;
	}

	/**
	 * Update an existing task comment
	 *
	 * @param $id
	 * @param array $data
	 * @param bool $taskId
	 * @return Result|bool
	 *
	 * @access private
	 */
	public static function update($id, array $data, $taskId = false)
	{
		$result = new Result();

		if(!static::includeModule())
		{
			$result->addError('NO_MODULE', 'No forum module installed');
			return false;
		}

		// get task by comment id
		if($taskId === false)
		{
			// todo
		}
		$taskId = intval($taskId);

		$feed = new \Bitrix\Forum\Comments\Feed(
			static::getForumId(),
			array(
				"type" => 'TK',
				"id" => $taskId,
				"xml_id" => "TASK_".$taskId
			)
		);

		$updateResult = $feed->edit($id, $data);
		if($updateResult)
		{
			$result->setData($updateResult);
		}
		else
		{
			$errors = $feed->getErrors();
			if(is_array($errors))
			{
				foreach($errors as $error)
				{
					$result->getErrors()->add('ACTION_FAILED_REASON', $error->getMessage(), Error::TYPE_FATAL, array('CODE' => $error->getCode()));
				}
			}
		}

		return $result;
	}

	/**
	 * Remove task comment
	 *
	 * @param $id
	 * @param bool $taskId
	 * @return Result|bool
	 *
	 * @access private
	 */
	public static function delete($id, $taskId = false)
	{
		$result = new Result();

		if(!static::includeModule())
		{
			$result->addError('NO_MODULE', 'No forum module installed');
			return false;
		}

		// get task by comment id
		if($taskId === false)
		{
			// todo
		}
		$taskId = intval($taskId);

		$feed = new \Bitrix\Forum\Comments\Feed(
			static::getForumId(),
			array(
				"type" => 'TK',
				"id" => $taskId,
				"xml_id" => "TASK_".$taskId
			)
		);

		$deleteResult = $feed->delete($id);
		if($deleteResult)
		{
			$result->setData($deleteResult);
		}
		else
		{
			$errors = $feed->getErrors();
			if(is_array($errors))
			{
				foreach($errors as $error)
				{
					$result->getErrors()->add('ACTION_FAILED_REASON', $error->getMessage(), Error::TYPE_FATAL, array('CODE' => $error->getCode()));
				}
			}
		}

		return $result;
	}

	// event handling below...

	/**
	 * Event callback for comment add. Fires on forum::OnAfterCommentAdd
	 *
	 * This is not a part of public API.
	 * This function is for internal use only.
	 *
	 * This function WILL send notifications in case of comment add through bitrix:forum.comments component
	 * Also, socialnetwork`s Live Feed uses modern forum API now to store comments for task-related block.
	 * This API subsequently fires forum::OnAfterCommentAdd, and, as a result, it comes to this function.
	 * So we got an isomorphic handler relative to the comment feed and Live Feed.
	 *
	 * @access private
	 */
	public static function onAfterAdd($entityType, $taskId, $arData)
	{
		static $parser = null;

		$arFilesIds = array();

		// 'TK' is our entity type
		if ($entityType !== 'TK')
		{
			return;
		}

		if (!(\CTaskAssert::isLaxIntegers($taskId) && ((int) $taskId >= 1)))
		{
			\CTaskAssert::logWarning('[0xc4b31fa6] Expected integer $taskId >= 1');
			return;
		}

		$messageId  = $arData['MESSAGE_ID'];
		$strMessage = $arData['PARAMS']['POST_MESSAGE'];

		if (
			isset($arData['PARAMS']['UF_FORUM_MESSAGE_DOC'])
			&& !empty($arData['PARAMS']['UF_FORUM_MESSAGE_DOC'])
		)
		{
			$arFilesIds = $arData['PARAMS']['UF_FORUM_MESSAGE_DOC'];
		}

		$urlPreviewId = '';
		if (
			isset($arData['PARAMS']['UF_FORUM_MES_URL_PRV'])
			&& !empty($arData['PARAMS']['UF_FORUM_MES_URL_PRV'])
		)
		{
			$urlPreviewId = $arData['PARAMS']['UF_FORUM_MES_URL_PRV'];
		}

		if ($parser === null)
		{
			$parser = new \CTextParser();
		}

		$messageAuthorId = null;
		$messageEditDate = null;
		$messagePostDate = null;

		if (
			array_key_exists('AUTHOR_ID', $arData['PARAMS'])
			&& array_key_exists('EDIT_DATE', $arData['PARAMS'])
			&& array_key_exists('POST_DATE', $arData['PARAMS'])
		)
		{
			$messageAuthorId = $arData['PARAMS']['AUTHOR_ID'];
			$messageEditDate = $arData['PARAMS']['POST_DATE'];
		}
		else
		{
			$arMessage = \CForumMessage::GetByID($messageId);

			$messageAuthorId = $arMessage['AUTHOR_ID'];
			$messageEditDate = $arMessage['POST_DATE'];
		}

		$occurAsUserId = static::getOccurAsId($messageAuthorId);

		$oTask = \CTaskItem::getInstance($taskId, User::getAdminId());
		$arTask = $oTask->getData();

		$arRecipientsIDs = static::getTaskMembersByTaskId($taskId, $excludeUser = $occurAsUserId);

		SearchIndex::setCommentSearchIndex($taskId, $messageId, $strMessage);

		// sonet log
		if (\Bitrix\Tasks\Integration\Socialnetwork::includeModule() && \Bitrix\Tasks\Integration\SocialNetwork::isEnabled())
		{
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

			if (!$bCrmTask)
			{
				$dbRes = \CSocNetLog::getList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => "tasks",
						"SOURCE_ID" => $taskId
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
				);
				if ($arRes = $dbRes->fetch())
				{
					$log_id = $arRes["ID"];
					$entity_type = $arRes["ENTITY_TYPE"];
					$entity_id = $arRes["ENTITY_ID"];
				}
				else
				{
					$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
					$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

					// todo: refactor when user cache implemented
					$rsUser = \CUser::getByID($arTask["CREATED_BY"]);
					if ($arUser = $rsUser->fetch())
					{
						$arSoFields = array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "tasks",
							"LOG_DATE" => $arTask["CREATED_DATE"],
							"TITLE_TEMPLATE" => "#TITLE#",
							"TITLE" => $arTask["TITLE"],
							"MESSAGE" => "",
							"TEXT_MESSAGE" => '',// $strMsgNewTask,
							"MODULE_ID" => "tasks",
							"CALLBACK_FUNC" => false,
							"SOURCE_ID" => $taskId,
							"ENABLE_COMMENTS" => "Y",
							"USER_ID" => $arTask["CREATED_BY"],
							"URL" => \CTaskNotifications::getNotificationPath($arUser, $taskId),
							"PARAMS" => serialize(array("TYPE" => "create"))
						);
						$log_id = \CSocNetLog::Add($arSoFields, false);
						if (intval($log_id) > 0)
						{
							\CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
							$arRights = \CTaskNotifications::__UserIDs2Rights(static::getTaskMembersByTaskId($taskId));
							if($arTask["GROUP_ID"])
							{
								$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
							}
							\CSocNetLogRights::Add($log_id, $arRights);
						}
					}
				}
			}

			if (intval($log_id) > 0)
			{
				$filtered = (\COption::GetOptionString("forum", "FILTER", "Y") == "Y");
				$sText = ($filtered ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
				$arTagInline = $parser->detectTags($sText);

				\CSocNetLog::Update(
					$log_id,
					array(
						'PARAMS' => serialize(array('TYPE' => 'comment'))
					)
				);

				// todo: some garbage?
				$strURL = $GLOBALS['APPLICATION']->getCurPageParam("", array("IFRAME", "IFRAME_TYPE", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
				$strURL = \ForumAddPageParams(
					$strURL,
					array(
						"MID" => $messageId,
						"result" => "reply"
					),
					false,
					false
				);

				$arFieldsForSocnet = array(
					"ENTITY_TYPE" => $entity_type,
					"ENTITY_ID" => $entity_id,
					"EVENT_ID" => "tasks_comment",
					"MESSAGE" => $sText,
					"TEXT_MESSAGE" => $parser->convert4mail($sText),
//					"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
					"MODULE_ID" => "tasks",
					"SOURCE_ID" => $messageId,
					"LOG_ID" => $log_id,
					"RATING_TYPE_ID" => "FORUM_POST",
					"RATING_ENTITY_ID" => $messageId
				);

				if (!empty($arTagInline))
				{
					$arFieldsForSocnet["TAG"] = $arTagInline;
				}

				$arFieldsForSocnet["USER_ID"] = $occurAsUserId;
				$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS['DB']->CurrentTimeFunction();

				$ufFileID = array();
				$dbAddedMessageFiles = \CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageId));
				while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
				{
					$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
				}

				if (count($ufFileID) > 0)
				{
					$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;
				}

				$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageId, LANGUAGE_ID);
				if ($ufDocID)
				{
					$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;
				}

				$ufUrlPreview = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MES_URL_PRV", $messageId, LANGUAGE_ID);
				if ($ufUrlPreview)
				{
					$arFieldsForSocnet["UF_SONET_COM_URL_PRV"] = $ufUrlPreview;
				}

				$ufVersionId = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_VER", $messageId, LANGUAGE_ID);
				if ($ufVersionId)
				{
					$arFieldsForSocnet["UF_SONET_COM_VER"] = $ufVersionId;
				}

				$comment_id = \CSocNetLogComments::Add($arFieldsForSocnet, false, false);

				$bHasAccessAll = \CSocNetLogRights::CheckForUserAll($log_id);
				$arUserIdToPush = array();

				if (!$bHasAccessAll)
				{
					$dbRight = \CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
					while ($arRight = $dbRight->Fetch())
					{
						if (preg_match('/^U(\d+)$/', $arRight["GROUP_CODE"], $matches))
						{
							$arUserIdToPush[] = $matches[1];
						}
						elseif (!in_array($arRight["GROUP_CODE"], array("SA")))
						{
							$arUserIdToPush = array();
							break;
						}
					}
				}

				\CSocNetLog::CounterIncrement(
					$comment_id,
					false,
					false,
					"LC",
					$bHasAccessAll,
					(
					$bHasAccessAll
					|| empty($arUserIdToPush)
					|| count($arUserIdToPush) > 20
						? array()
						: $arUserIdToPush
					)
				);
			}
		}

		$userIdToShareList = static::processMentions($arData);
		if (is_array($userIdToShareList))
		{
			$arRecipientsIDs = array_merge($arRecipientsIDs, $userIdToShareList);
		}

		if (Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::add(\Bitrix\Pull\Event::SHARED_CHANNEL, [
				'module_id' => 'tasks',
				'command' => 'comment_add',
				'params' => [
					'ENTITY_XML_ID'=>$arData['PARAMS']['XML_ID'],
					'OWNER_ID'=>$occurAsUserId
				]
			], \CPullChannel::TYPE_SHARED);
		}

		static::sendNotification(
			array(
				'ID' => $messageId,
				'POST_MESSAGE' => $strMessage
			),
			$arTask,
			$occurAsUserId,
			$arRecipientsIDs,
			$arData
		);

		if (!isset($arData['replica']))
		{
			static::addLogItem(array(
				"TASK_ID" => $taskId,
				"USER_ID" => $occurAsUserId,
				"CREATED_DATE" => ($messageEditDate ? ConvertTimeStamp(MakeTimeStamp($messageEditDate, \CSite::GetDateFormat()), "FULL") : $messagePostDate),
				"FIELD" => "COMMENT",
				"TO_VALUE" => $messageId
			));

			static::fireEvent('Add', $taskId, $arData, $arFilesIds, $urlPreviewId);
		}
	}

	/**
	 * Event callback for comment update\delete. Fires on forum::OnAfterCommentUpdate. Also this is used for socialnetwork`s Live Feed.
	 *
	 * This is not a part of public API.
	 * This function is for internal use only.
	 *
	 * @access private
	 */
	public static function onAfterUpdate($entityType, $taskID, $arData)
	{
		// 'TK' is our entity type
		if ($entityType !== 'TK')
		{
			return;
		}

		if (empty($arData["MESSAGE_ID"]))
		{
			return;
		}

		$arMessage = false;

		if (\Bitrix\Tasks\Integration\Socialnetwork::includeModule() && \Bitrix\Tasks\Integration\SocialNetwork::isEnabled())
		{
			$parser = new \CTextParser();
			$parser->allow = array("HTML" => 'Y',"ANCHOR" => 'Y',"BIU" => 'Y',"IMG" => "Y","VIDEO" => "Y","LIST" => 'N',"QUOTE" => 'Y',"CODE" => 'Y',"FONT" => 'Y',"SMILES" => "N","UPLOAD" => 'N',"NL2BR" => 'N',"TABLE" => "Y");

			$oTask = \CTaskItem::getInstance($taskID, User::getAdminId());
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

			switch ($arData["ACTION"])
			{
				case "DEL":
				case "HIDE":
					$dbLogComment = \CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
							"SOURCE_ID" => intval($arData["MESSAGE_ID"])
						),
						false,
						false,
						array("ID")
					);
					while ($arLogComment = $dbLogComment->Fetch())
					{
						\CSocNetLogComments::Delete($arLogComment["ID"]);
					}
					break;
				case "SHOW":
					$dbLogComment = \CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
							"SOURCE_ID" => intval($arData["MESSAGE_ID"])
						),
						false,
						false,
						array("ID")
					);
					$arLogComment = $dbLogComment->Fetch();
					if (!$arLogComment)
					{
						$arMessage = \CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
						if ($arMessage)
						{
							$arFilter = false;
							if (!$bCrmTask)
							{
								$arFilter = array(
									"EVENT_ID" => "tasks",
									"SOURCE_ID" => $taskID
								);
							}
							elseif (\Bitrix\Tasks\Integration\CRM::includeModule())
							{
								$dbCrmActivity = \CCrmActivity::GetList(
									array(),
									array(
										'TYPE_ID' => \CCrmActivityType::Task,
										'ASSOCIATED_ENTITY_ID' => $taskID,
										'CHECK_PERMISSIONS' => 'N'
									),
									false,
									false,
									array('ID')
								);

								if ($arCrmActivity = $dbCrmActivity->Fetch())
								{
									$arFilter = array(
										"EVENT_ID" => "crm_activity_add",
										"ENTITY_ID" => $arCrmActivity["ID"]
									);
								}
							}

							if ($arFilter)
							{
								$dbLog = \CSocNetLog::GetList(
									array("ID" => "DESC"),
									$arFilter,
									false,
									false,
									array("ID", "ENTITY_TYPE", "ENTITY_ID")
								);
								if ($arLog = $dbLog->Fetch())
								{
									$log_id = $arLog["ID"];
									$entity_type = $arLog["ENTITY_TYPE"];
									$entity_id = $arLog["ENTITY_ID"];
								}
								else
								{
									$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
									$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

									$rsUser = \CUser::GetByID($arTask["CREATED_BY"]);
									if ($arUser = $rsUser->Fetch())
									{
										$arSoFields = array(
											"ENTITY_TYPE" => $entity_type,
											"ENTITY_ID" => $entity_id,
											"EVENT_ID" => "tasks",
											"LOG_DATE" => $arTask["CREATED_DATE"],
											"TITLE_TEMPLATE" => "#TITLE#",
											"TITLE" => $arTask["TITLE"],
											"MESSAGE" => "",
											"TEXT_MESSAGE" => '', //$strMsgNewTask,
											"MODULE_ID" => "tasks",
											"CALLBACK_FUNC" => false,
											"SOURCE_ID" => $taskID,
											"ENABLE_COMMENTS" => "Y",
											"USER_ID" => $arTask["CREATED_BY"],
											"URL" => \CTaskNotifications::getNotificationPath($arUser, $taskID),
											"PARAMS" => serialize(array("TYPE" => "create"))
										);
										$log_id = \CSocNetLog::Add($arSoFields, false);
										if (intval($log_id) > 0)
										{
											$arRights = \CTaskNotifications::__UserIDs2Rights(self::getTaskMembersByTaskId($taskID));
											if($arTask["GROUP_ID"])
												$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
											\CSocNetLogRights::Add($log_id, $arRights);
										}
									}
								}
							}

							if ($log_id > 0)
							{
								$sText = (\COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
								$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "IFRAME_TYPE", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
								$strURL = \ForumAddPageParams(
									$strURL,
									array(
										"MID" => intval($arData["MESSAGE_ID"]),
										"result" => "reply"
									),
									false,
									false
								);

								$arFieldsForSocnet = array(
									"ENTITY_TYPE" => $entity_type,
									"ENTITY_ID" => $entity_id,
									"EVENT_ID" => ($bCrmTask ? 'crm_activity_add_comment' : 'tasks_comment'),
									"MESSAGE" => $sText,
									"TEXT_MESSAGE" => $parser->convert4mail($sText),
//									"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
									"MODULE_ID" => "tasks",
									"SOURCE_ID" => intval($arData["MESSAGE_ID"]),
									"LOG_ID" => $log_id,
									"RATING_TYPE_ID" => "FORUM_POST",
									"RATING_ENTITY_ID" => intval($arData["MESSAGE_ID"])
								);

								$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];
								$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS["DB"]->currentTimeFunction();

								$ufFileID = array();
								$dbAddedMessageFiles = \CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
								while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
									$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

								if (count($ufFileID) > 0)
									$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

								$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
								if ($ufDocID)
									$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;

								$comment_id = \CSocNetLogComments::Add($arFieldsForSocnet, false, false);
								\CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
							}
						}
					}
					break;
				case "EDIT":
					$arMessage = \CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
					if ($arMessage)
					{
						$dbLogComment = \CSocNetLogComments::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID" => ($bCrmTask ? 'crm_activity_add_comment' : 'tasks_comment'),
								"SOURCE_ID" => intval($arData["MESSAGE_ID"])
							),
							false,
							false,
							array("ID", "LOG_ID")
						);
						$arLogComment = $dbLogComment->fetch();
						if ($arLogComment)
						{
							$sText = (\COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
							$arFieldsForSocnet = array(
								"LOG_ID" => intval($arLogComment["LOG_ID"]),
								"MESSAGE" => $sText,
								"TEXT_MESSAGE" => $parser->convert4mail($sText),
							);

							$ufFileID = array();
							$arFilesIds = array();

							$taskId = null;
							if (
								isset($arData['PARAMS']['PARAM2'])
								&& !empty($arData['PARAMS']['PARAM2'])
							)
							{
								$taskId = (int) $arData['PARAMS']['PARAM2'];
							}

							$dbAddedMessageFiles = \CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
							while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							{
								$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
								$arFilesIds[] = $arAddedMessageFiles["FILE_ID"];
							}

							if (count($ufFileID) > 0)
							{
								$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;
							}

							$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
							if ($ufDocID)
							{
								$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;

								if (is_array($ufDocID))
								{
									$arFilesIds = array_merge($arFilesIds, $ufDocID);
								}
							}

							$ufUrlPreview = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MES_URL_PRV", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
							if ($ufUrlPreview)
							{
								$arFieldsForSocnet["UF_SONET_COM_URL_PRV"] = $ufUrlPreview;
							}

							if ($taskId && ! empty($arFilesIds))
							{
								static::addWebDavFileRights($taskId, $arFilesIds);
							}
							\CSocNetLogComments::Update($arLogComment["ID"], $arFieldsForSocnet);
						}
					}
					break;
				default:
			}
		}

		// add records to log. only EDIT and DEL actions handled
		// here may be edit or delete

		$messageId = intval($arData['MESSAGE_ID']);
		$messageText = $arData['PARAMS']['POST_MESSAGE'];

		if($arData['ACTION'] == 'EDIT' || $arData['ACTION'] == 'DEL')
		{
			$messageAuthorId = null;
			$messageEditDate = null;

			if($arData['ACTION'] == 'EDIT')
			{
				$message = $arData['MESSAGE'];

				if(!is_array($arData['MESSAGE'])
					|| !array_key_exists('AUTHOR_ID', $arData['MESSAGE'])
				)
				{
					if($arMessage === false) // it was not obtained previously
					{
						$message = \CForumMessage::getByID($messageId);
					}
					else
					{
						$message = $arMessage;
					}
				}

				$messageAuthorId = $message['AUTHOR_ID'];

				SearchIndex::setCommentSearchIndex($taskID, $messageId, $messageText);
			}
			else
			{
				// in case of DEL action, there is no PARAMS passed in $arData, so there is a data surrogate
				$messageAuthorId = static::getUserId();

				SearchIndexTable::deleteByTaskAndMessageIds($taskID, $messageId);
			}

			// no instant notification on comment update

			if (!isset($arData['replica']))
			{
				static::addLogItem(array(
					"TASK_ID" => (int)$taskID,
					"USER_ID" => static::getOccurAsId($messageAuthorId),
					"CREATED_DATE" => null,
					"FIELD" => "COMMENT_".$arData['ACTION'],
					"TO_VALUE" => $arData['MESSAGE_ID']
				));

				static::fireEvent($arData['ACTION'] == 'EDIT'? 'Update': 'Delete', $taskID, $arData);
			}
		}
	}

	private static function getTaskMembersByTaskId($taskId, $excludeUser = 0)
	{
		$oTask = \CTaskItem::getInstance((int)$taskId, User::getAdminId());
		$arTask = $oTask->getData(false);

		$arUsersIds = \CTaskNotifications::getRecipientsIDs($arTask, $bExcludeLoggedUser = false);

		$excludeUser = (int) $excludeUser;

		if ($excludeUser >= 1)
		{
			$currentUserPos = array_search($excludeUser, $arUsersIds);
			if ($currentUserPos !== false)
			{
				unset($arUsersIds[$currentUserPos]);
			}
		}
		else if ($excludeUser < 0)
		{
			\CTaskAssert::logWarning('[0x3c2a31fe] invalid user id (' . $excludeUser . ')');
		}

		return $arUsersIds;
	}

	private static function fireEvent($action, $taskId, $fields, $arFilesIds = array(), $urlPreviewId = '')
	{
		if($action !== 'Add' && $action !== 'Update' && $action != 'Delete')
		{
			return false;
		}

		$commentId = intval($fields['MESSAGE_ID']);

		$arFields = array(
			'TASK_ID'      => $taskId,
			'MESSAGE_ID'   => $commentId,
			'COMMENT_TEXT' => $fields['MESSAGE']['POST_MESSAGE'],
			'FILES'        => $arFilesIds,
			'URL_PREVIEW'  => $urlPreviewId,
		);

		if($action == 'Add')
		{
			static::addWebDavFileRights($taskId, $arFilesIds);
		}

		foreach(\GetModuleEvents('tasks', 'OnAfterComment'.$action, true) as $arEvent)
		{
			\ExecuteModuleEventEx($arEvent, array($commentId, &$arFields));
		}

		return true;
	}

	private static function addLogItem(array $fields)
	{
		$log = new \CTaskLog();
		$log->add($fields);
	}

	private static function processMentions(array $fields)
	{
		global $DB;

		$currentUserId = User::getId();
		$userIdToShareList = array();

		if (
			is_array($fields)
			&& isset($fields['MESSAGE'])
			&& isset($fields['MESSAGE']['POST_MESSAGE'])
		)
		{
			preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $fields['MESSAGE']['POST_MESSAGE'], $matches);
			if (
				is_array($matches)
				&& !empty($matches)
				&& !empty($matches[1])
				&& is_array($matches[1])
			)
			{
				$mentionUserIdList = $matches[1];
				$taskParticipantList = array();
				$taskObject = $task = false;

				if (
					isset($fields['PARAMS'])
					&& isset($fields['PARAMS']['XML_ID'])
				)
				{
					preg_match("/TASK_(\d+)/is".BX_UTF_PCRE_MODIFIER, $fields['PARAMS']['XML_ID'], $matches);
					if (
						is_array($matches)
						&& !empty($matches[1])
						&& intval($matches[1]) > 0
					)
					{
						$taskObject = new \CTaskItem(intval($matches[1]), $currentUserId);
						$task = $taskObject->getData();
						$taskParticipantList = \CTaskNotifications::getRecipientsIDs(
							$task,
							false		// don't exclude current user
						);
					}
				}

				if (!empty($taskParticipantList))
				{
					foreach($mentionUserIdList as $mentionUserId)
					{
						if (!in_array($mentionUserId, $taskParticipantList))
						{
							$userIdToShareList[] = intval($mentionUserId);
						}
					}
				}

				if (
					!empty($userIdToShareList)
					&& $taskObject
					&& $task
				)
				{
					foreach($userIdToShareList as $userIdToShare)
					{
						$taskObject->startWatch($userIdToShare, true);
					}

					if ($taskNew = \CTasks::getByID($task['ID'], false, array('returnAsArray' => true)))
					{
						\CTaskNotifications::sendUpdateMessage(
							array (
								'AUDITORS' => $taskNew['AUDITORS'],
								'CHANGED_BY' => $currentUserId,
								'CHANGED_DATE' => $DB->currentTimeFunction(),
								'OUTLOOK_VERSION' => $taskNew['OUTLOOK_VERSION'],
								'DEADLINE_COUNTED' => $taskNew['DEADLINE_COUNTED'],
								'IGNORE_RECIPIENTS' => $userIdToShareList
							),
							$task,
							false,
							array(
								'THROTTLE_MESSAGES' => false,
								'USER_ID' => $currentUserId,
								'CHECK_RIGHTS_ON_FILES' => true,
								'CORRECT_DATE_PLAN' => true,
								'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => true
							)
						);

						if (Loader::includeModule('socialnetwork'))
						{
							$crm = \CTaskNotifications::isCrmTask($taskNew);
							$logFilter = \CTaskNotifications::getSonetLogFilter($taskNew["ID"], $crm);

							$res = \CSocNetLog::getList(
								array(),
								$logFilter,
								false,
								false,
								array("ID")
							);
							if ($logEntry = $res->fetch())
							{
								\CTaskNotifications::setSonetLogRights(array(
									'LOG_ID' => $logEntry['ID'],
									'EFFECTIVE_USER_ID' => $currentUserId
								), $taskNew, $taskNew);
							}
						}
					}
				}
			}
		}

		return $userIdToShareList;
	}

	private static function sendNotification($messageData, $taskData, $fromUser, $toUsers, array $eventData = array())
	{
		if(empty($toUsers) || !\Bitrix\Tasks\Integration\IM::includeModule())
		{
			return false;
		}

		// some sources do not even pass $eventData, so ensure we got at least MESSAGE_ID
		$eventData['MESSAGE_ID'] = $messageData['ID'];

		$user = \CTaskNotifications::getUser($fromUser);

		// in comment messages we can get BBCODEs that are not supported by IM. rip them out. also limit text length to 100
		$message = \Bitrix\Tasks\Util::trim(\CTextParser::clearAllTags($messageData['POST_MESSAGE']));
		$messageCropped = self::cropMessage($message);

		$messageTemplate = \CTaskNotifications::getGenderMessage($fromUser, "TASKS_COMMENT_MESSAGE_ADD");
		$messageTemplatePush = \CTaskNotifications::getGenderMessage($fromUser, "TASKS_COMMENT_MESSAGE_ADD_PUSH");

		if($messageCropped != '')
		{
			$messageTemplate .= Loc::getMessage('TASKS_COMMENT_MESSAGE_ADD_WITH_TEXT');
			$messageTemplatePush .= ': #TASK_COMMENT_TEXT#';
		}

		\CTaskNotifications::SendMessageEx($taskData["ID"], $fromUser, $toUsers, array(
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
			'PUSH' => \CTaskNotifications::cropMessage($messageTemplatePush, array(
				'USER_NAME' => 			User::formatName($user),
				'TASK_TITLE' => 		$taskData["TITLE"],
				'TASK_COMMENT_TEXT' => 	$message
			), \CTaskNotifications::PUSH_MESSAGE_MAX_LENGTH)
		), array(
			'ENTITY_CODE' => 'COMMENT',
			'ENTITY_OPERATION' => 'ADD',
			'EVENT_DATA' => $eventData,
			'NOTIFY_EVENT' => 'comment',
			'NOTIFY_ANSWER' => true,
			'TASK_DATA' => $taskData,
			'TASK_URL' => array(
				'PARAMETERS' => static::getUrlParameters($messageData['ID']),
				'HASH' => static::makeUrlHash($messageData['ID'])
			)
		));

		return true;
	}

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

	private static function getUserId()
	{
		$userId = User::getId();
		if(!$userId)
		{
			$userId = User::getAdminId();
		}

		return $userId;
	}

	// legacy webdav support. it will be removed in future, so no external integration helper for webdav used here
	private static function addWebDavFileRights($taskId, $arFilesIds)
	{
		$arFilesIds = array_unique(array_filter($arFilesIds));

		// Nothing to do?
		if (empty($arFilesIds))
		{
			return;
		}

		if(!\CModule::IncludeModule('webdav') || !\CModule::IncludeModule('iblock'))
		{
			return;
		}

		$arRightsTasks = \CWebDavIblock::GetTasks();	// tasks-operations

		$oTask  = new \CTaskItem((int)$taskId, User::getAdminId());
		$arTask = $oTask->getData(false);

		$arTaskMembers = array_unique(array_merge(
			array($arTask['CREATED_BY'], $arTask['RESPONSIBLE_ID']),
			$arTask['AUDITORS'],
			$arTask['ACCOMPLICES']
		));

		$ibe = new \CIBlockElement();
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

				if (\CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
				{
					$ibRights = new \CIBlockElementRights($arWDFile['IBLOCK_ID'], $fileId);
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
	 * @param $topicId
	 * @param int $forumId
	 * @return int
	 * @deprecated
	 */
	public static function getFileCount($topicId, $forumId = 0)
	{
		return \Bitrix\Tasks\Integration\Forum\Task\Topic::getFileCount($topicId, $forumId);
	}
}