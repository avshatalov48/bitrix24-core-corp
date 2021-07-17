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

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\Task\ProjectLastActivityTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;
use Bitrix\Forum;
use CSite;

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

		if (!self::includeModule())
		{
			$result->addError('NO_MODULE', 'No forum module installed');
			return $result;
		}

		Counter\CounterService::getInstance()->collectData((int)$taskId);

		if (!array_key_exists('AUTHOR_ID', $data))
		{
			$data['AUTHOR_ID'] = User::getId();
		}
		if (!array_key_exists('USE_SMILES', $data))
		{
			$data['USE_SMILES'] = 'Y';
		}
		if(
			$data['POST_MESSAGE'] !== ''
			&& array_key_exists('UF_TASK_COMMENT_TYPE', $data)
		)
		{
			if (empty($data['AUX_DATA']))
			{
				$data['AUX_DATA'] = [
					'auxData' => $data['UF_TASK_COMMENT_TYPE'],
					'text' => $data['POST_MESSAGE'],
				];
			}
			$data['SERVICE_TYPE'] = Forum\Comments\Service\Manager::TYPE_TASK_INFO;

			if (defined(Forum\Comments\Service\Manager::class . '::TYPE_FORUM_DEFAULT'))
			{
				$data['SERVICE_DATA'] = Json::encode($data['AUX_DATA']);
				$data['POST_MESSAGE'] = Forum\Comments\Service\Manager::find([
					'SERVICE_TYPE' => Forum\Comments\Service\Manager::TYPE_TASK_INFO,
				])->getText($data['SERVICE_DATA']);
			}
			else
			{
				$data['POST_MESSAGE'] = Json::encode($data['AUX_DATA']);
			}
		}

		$feed = new Forum\Comments\Feed(
			self::getForumId(),
			[
				'type' => 'TK',
				'id' => $taskId,
				'xml_id' => "TASK_{$taskId}",
			],
			$data['AUTHOR_ID']
		);

		// $feed->add() works with global-defined user fields
		foreach ($data as $key => $value)
		{
			if (Util\UserField::isUFKey($key))
			{
				$GLOBALS[$key] = $value;
			}
		}

		// remove attachments from system comments
		$sourceValues = [];
		if (array_key_exists('AUX', $data))
		{
			foreach ($GLOBALS as $key => $value)
			{
				if (strpos($key, 'UF_FORUM_MESSAGE_') === 0)
				{
					$sourceValues[$key] = $GLOBALS[$key];
					unset($GLOBALS[$key]);
				}
			}
		}

		$addResult = $feed->add($data);
		if ($addResult)
		{
			$result->setData($addResult);
			if ($data['AUX'] === 'Y' && method_exists($feed, 'send'))
			{
				$skipUserRead = 'N';

				if ($data['UF_TASK_COMMENT_TYPE'] === Comments\Internals\Comment::TYPE_EXPIRED_SOON)
				{
					$taskData = \CTaskItem::getInstance($taskId, $data['AUTHOR_ID']);
					$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
					$accomplices = $taskData['ACCOMPLICES'];
					$accomplices = (is_array($accomplices) ? $accomplices : $accomplices->export());
					$accomplices = array_map('intval', $accomplices);

					if (in_array($data['AUTHOR_ID'], array_merge([$responsibleId], $accomplices), true))
					{
						$skipUserRead = 'Y';
					}
				}

				$feed->send(
					$addResult["ID"],
					[
						'URL_TEMPLATES_PROFILE_VIEW' => Option::get('socialnetwork', 'user_page', '/company/personal/') . 'user/#user_id#/',
						'SKIP_USER_READ' => $skipUserRead,
					]
				);
			}
		}
		elseif (is_array($errors = $feed->getErrors()))
		{
			$resultErrors = $result->getErrors();
			foreach ($errors as $error)
			{
				$resultErrors && $resultErrors->add(
					'ACTION_FAILED_REASON',
					$error->getMessage(),
					Error::TYPE_FATAL,
					['CODE' => $error->getCode()]
				);
			}
		}

		// restore attachments
		foreach ($sourceValues as $k => $v)
		{
			$GLOBALS[$k] = $v;
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

		Counter\CounterService::getInstance()->collectData($taskId);

		$feed = new Forum\Comments\Feed(
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

		Counter\CounterService::getInstance()->collectData($taskId);

		$feed = new Forum\Comments\Feed(
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

	public static function onBeforeAdd($entityType, $taskId, $data): void
	{
		if ($entityType !== 'TK' || !$taskId)
		{
			return;
		}

		Counter\CounterService::getInstance()->collectData($taskId);
	}

	/**
	 * @param $entityType
	 * @param $taskId
	 * @param $data
	 */
	public static function onBeforeDelete($entityType, $taskId, $data): void
	{
		if ($entityType !== 'TK' || empty($data['MESSAGE_ID']))
		{
			return;
		}

		$taskId = (int)$taskId;
		Counter\CounterService::getInstance()->collectData($taskId);
	}

	public static function onAfterDelete($entityType, $taskId, $data): void
	{
		if ($entityType !== 'TK' || !$taskId)
		{
			return;
		}

		$taskId = (int)$taskId;
		$messageId = (int)$data['MESSAGE_ID'];

		$message = $data['MESSAGE'];
		if (!is_array($message) || !array_key_exists('AUTHOR_ID', $message))
		{
			$message = \CForumMessage::getByID($messageId);
		}

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_COMMENT_DELETE,
			[
				'TASK_ID' => (int) $taskId,
				'USER_ID' => (int) $message['AUTHOR_ID'],
				'MESSAGE_ID' => (int) $messageId
			]
		);

		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$task = TaskRegistry::getInstance()->getObject((int) $taskId, true);
		if (!$task)
		{
			return;
		}

		$groupId = (int) $task->getGroupId();

		$members = $task->getMemberList();
		$taskParticipants = [];
		foreach ($members as $member)
		{
			$taskParticipants[] = $member->getUserId();
		}
		$taskParticipants = array_unique($taskParticipants);

		$pushRecipients = $taskParticipants;
		if (
			SocialNetwork::includeModule()
			&& $groupId > 0
		)
		{
			$pushRecipients = array_unique(
				array_merge(
					$taskParticipants,
					SocialNetwork\User::getUsersCanPerformOperation($groupId, 'view_all')
				)
			);

			\CSocNetGroup::SetLastActivity($groupId);
		}

		PushService::addEvent($pushRecipients, [
			'module_id' => 'tasks',
			'command' => 'comment_delete',
			'params' => [
				'entityXmlId' => $data['MESSAGE']['XML_ID'],
				'ownerId' => static::getOccurAsId($data['MESSAGE']['AUTHOR_ID']),
				'messageId' => $data['MESSAGE']['ID'],
				'groupId' => $groupId,
				'participants' => $taskParticipants,
				'pullComment' => true,
			],
		]);
	}

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

		$arData['PARAMS'] = (isset($arData['PARAMS']) && is_array($arData['PARAMS']) ? $arData['PARAMS'] : []);

		$aux = (isset($arData['PARAMS']['AUX']) && $arData['PARAMS']['AUX'] == "Y");
		$messageId  = $arData['MESSAGE_ID'];
		$strMessage = $arData['PARAMS']['POST_MESSAGE'];

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
		$messageEditDateTimeStamp = MakeTimeStamp($messageEditDate, CSite::GetDateFormat()) - \CTimeZone::getOffset();

		TaskTable::update($taskId, ['ACTIVITY_DATE' => DateTime::createFromTimestamp($messageEditDateTimeStamp)]);

		try
		{
			$oTask = new \CTaskItem($taskId, User::getAdminId());
			$arTask = $oTask->getData();
		}
		catch (\TasksException | \CTaskAssertException $e)
		{
			return;
		}

		if ($arTask['GROUP_ID'] > 0)
		{
			ProjectLastActivityTable::update(
				$arTask['GROUP_ID'],
				['ACTIVITY_DATE' => DateTime::createFromTimestamp($messageEditDateTimeStamp)]
			);
		}

		if (!$aux)
		{
			SearchIndex::setCommentSearchIndex($taskId, $messageId, $strMessage);
		}

		// sonet log
		if (
			Socialnetwork::includeModule()
			&& (
				SocialNetwork::isEnabled()
				|| $aux
			)
		)
		{
			$bCrmTask = (
				isset($arTask["UF_CRM_TASK"])
				&& (
					(
						is_array($arTask["UF_CRM_TASK"])
						&& (
							isset($arTask["UF_CRM_TASK"][0])
							&& $arTask["UF_CRM_TASK"][0] <> ''
						)
					)
					||
					(
						!is_array($arTask["UF_CRM_TASK"])
						&& $arTask["UF_CRM_TASK"] <> ''
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
					$signer = new \Bitrix\Main\Security\Sign\Signer();
					$arFieldsForSocnet["UF_SONET_COM_URL_PRV"] = $signer->sign((string)$ufUrlPreview, \Bitrix\Main\UrlPreview\UrlPreview::SIGN_SALT);
				}

				$ufVersionId = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_VER", $messageId, LANGUAGE_ID);
				if ($ufVersionId)
				{
					$arFieldsForSocnet["UF_SONET_COM_VER"] = $ufVersionId;
				}

				if (!empty($arData['AUX_DATA']))
				{
					$arFieldsForSocnet['MESSAGE'] = $arFieldsForSocnet['TEXT_MESSAGE'] = \Bitrix\Socialnetwork\CommentAux\TaskInfo::getPostText();
				}

				$comment_id = \CSocNetLogComments::Add($arFieldsForSocnet, [
					'SET_SOURCE' => false,
					'SEND_EVENT' => false,
					'SUBSCRIBE' => false,
				]);

				if (\Bitrix\Socialnetwork\ComponentHelper::checkLivefeedTasksAllowed())
				{
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

					$isNew = false;
					if (
						isset($_POST['ACTION'][0]['OPERATION'])
						&& $_POST['ACTION'][0]['OPERATION'] === 'task.add'
					)
					{
						$isNew = true;
					}

					if (!$isNew)
					{
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
			}

			if (
				array_key_exists('GROUP_ID', $arTask)
				&& $arTask['GROUP_ID']
			)
			{
				\CSocNetGroup::SetLastActivity((int)$arTask['GROUP_ID']);
			}
		}

		$commentType = Comments\Internals\Comment::TYPE_DEFAULT;
		if (isset($arData['PARAMS']['UF_TASK_COMMENT_TYPE']) && !empty($arData['PARAMS']['UF_TASK_COMMENT_TYPE']))
		{
			$commentType = (int)$arData['PARAMS']['UF_TASK_COMMENT_TYPE'];
		}

		$isPingComment = 'N';
		if ($aux)
		{
			$commentReader = Comments\Task\CommentReader::getInstance($taskId, $messageId);
			$commentReader->setCommentData([
				'MESSAGE' => '',
				'AUTHOR_ID' => (int)$messageAuthorId,
				'TYPE' => $commentType,
				'AUX_DATA' => (is_array($arData['AUX_DATA']) ? serialize($arData['AUX_DATA']) : $arData['AUX_DATA']),
			]);

			$isPingComment = ($commentReader->isContainCodes(['COMMENT_POSTER_COMMENT_TASK_PINGED_STATUS']) ? 'Y' : 'N');
			$arData['PARAMS']['IS_PING_COMMENT'] = $isPingComment;
		}

		if (!$aux || $isPingComment === 'Y')
		{
			UserOption::delete($taskId, (int)$messageAuthorId, UserOption\Option::MUTED);
		}

		$recipientsIds = static::getTaskMembersByTaskId($taskId, $occurAsUserId);
		$userIdToShareList = static::processMentions($arData);

		if (is_array($userIdToShareList))
		{
			foreach ($userIdToShareList as $userId)
			{
				$viewedDate = DateTime::createFromTimestamp($messageEditDateTimeStamp);
				$viewedDate->addSecond(-1);

				ViewedTable::set($taskId, $userId, $viewedDate);
			}

			$recipientsIds = array_merge($recipientsIds, $userIdToShareList);
		}

		$newCommentsCount = Comments\Task::getNewCommentsCountForTasks([$taskId], (int)$messageAuthorId);
		if (!$newCommentsCount[$taskId])
		{
			ViewedTable::set($taskId, (int)$messageAuthorId, DateTime::createFromTimestamp($messageEditDateTimeStamp));
		}

		Counter\CounterService::addEvent(
			Counter\Event\EventDictionary::EVENT_AFTER_COMMENT_ADD,
			[
				'TASK_ID' => (int) $taskId,
				'USER_ID' => (int) $occurAsUserId,
				'SERVICE_TYPE' => $commentType
			]
		);

		$isCompleteComment = false;
		if ($aux)
		{
			$commentReader->read();
			$isCompleteComment = $commentReader->isContainCodes([
				'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_5_V2',
				'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_5_APPROVE_V2',
			]);
		}

		if (Loader::includeModule('pull'))
		{
			$taskParticipants = array_unique(array_merge($recipientsIds, [$occurAsUserId]));

			$groupId = (int)$arTask['GROUP_ID'];
			$pushRecipients = $taskParticipants;
			if ($groupId > 0)
			{
				$pushRecipients = array_unique(
					array_merge(
						$taskParticipants,
						SocialNetwork\User::getUsersCanPerformOperation($groupId, 'view_all')
					)
				);
			}

			PushService::addEvent($pushRecipients, [
				'module_id' => 'tasks',
				'command' => 'comment_add',
				'params' => [
					'entityXmlId' => $arData['PARAMS']['XML_ID'],
					'ownerId' => $occurAsUserId,
					'messageId' => $messageId,
					'groupId' => $groupId,
					'participants' => $taskParticipants,
					'pullComment' => ($commentType !== Comments\Internals\Comment::TYPE_EXPIRED),
					'isCompleteComment' => $isCompleteComment,
				],
			]);
		}

		if (!$aux)
		{
			$messageData = ['ID' => $messageId, 'POST_MESSAGE' => $strMessage];
			static::sendNotification($messageData, $arTask, $occurAsUserId, $recipientsIds, $arData);
		}

		if (!$aux || $isPingComment === 'Y')
		{
			self::addToAuditor((int)$messageAuthorId, (int)$taskId);
		}

		if (!isset($arData['replica']))
		{
			static::addLogItem(array(
				"TASK_ID" => $taskId,
				"USER_ID" => $occurAsUserId,
				"CREATED_DATE" => (
					$messageEditDate
						? ConvertTimeStamp(MakeTimeStamp($messageEditDate, CSite::GetDateFormat()), "FULL")
						: $messagePostDate
				),
				"FIELD" => "COMMENT",
				"TO_VALUE" => $messageId
			));

			$fileIds = [];
			$urlPreviewId = '';

			if (isset($arData['PARAMS']['UF_FORUM_MESSAGE_DOC']) && !empty($arData['PARAMS']['UF_FORUM_MESSAGE_DOC']))
			{
				$fileIds = $arData['PARAMS']['UF_FORUM_MESSAGE_DOC'];
			}
			if (isset($arData['PARAMS']['UF_FORUM_MES_URL_PRV']) && !empty($arData['PARAMS']['UF_FORUM_MES_URL_PRV']))
			{
				$urlPreviewId = $arData['PARAMS']['UF_FORUM_MES_URL_PRV'];
			}

			static::fireEvent('Add', $taskId, $arData, $fileIds, $urlPreviewId);
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

			try
			{
				$arTask = $oTask->getData();
			}
			catch (\TasksException $e)
			{
				return;
			}

			$bCrmTask = (
				isset($arTask["UF_CRM_TASK"])
				&& (
					(
						is_array($arTask["UF_CRM_TASK"])
						&& (
							isset($arTask["UF_CRM_TASK"][0])
							&& $arTask["UF_CRM_TASK"][0] <> ''
						)
					)
					||
					(
						!is_array($arTask["UF_CRM_TASK"])
						&& $arTask["UF_CRM_TASK"] <> ''
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

		if ($arData['ACTION'] === 'EDIT' || $arData['ACTION'] === 'DEL')
		{
			$messageId = (int)$arData['MESSAGE_ID'];
			if (!is_array($arData['MESSAGE']) || !array_key_exists('AUTHOR_ID', $arData['MESSAGE']))
			{
				$arData['MESSAGE'] = ($arMessage === false ? \CForumMessage::getByID($messageId) : $arMessage);
			}
			$messageAuthorId = $arData['MESSAGE']['AUTHOR_ID'];

			if ($arData['ACTION'] === 'EDIT')
			{
				$messageText = $arData['PARAMS']['POST_MESSAGE'];
				SearchIndex::setCommentSearchIndex($taskID, $messageId, $messageText);
			}
			else
			{
				SearchIndexTable::deleteByTaskAndMessageIds($taskID, $messageId);

				Counter\CounterService::addEvent(
					Counter\Event\EventDictionary::EVENT_AFTER_COMMENT_DELETE,
					[
						'TASK_ID' => (int) $taskID,
						'USER_ID' => (int) $messageAuthorId,
						'MESSAGE_ID' => (int) $messageId
					]
				);
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

		try
		{
			$arTask = $oTask->getData(false);
		}
		catch (\TasksException $e)
		{
			return [];
		}

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
			&& isset($fields['MESSAGE']['POST_MESSAGE'], $fields['PARAMS']['AUX'])
			&& ($fields['PARAMS']['AUX'] !== 'Y' || $fields['PARAMS']['IS_PING_COMMENT'] === 'Y')
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
						try
						{
							$taskObject = new \CTaskItem(intval($matches[1]), $currentUserId);
							$task = $taskObject->getData();
							$taskParticipantList = \CTaskNotifications::getRecipientsIDs($task, false);
						}
						catch (\TasksException | \CTaskAssertException $e)
						{

						}
					}
				}

				if (!empty($taskParticipantList))
				{
					foreach ($mentionUserIdList as $mentionUserId)
					{
						if (!in_array($mentionUserId, $taskParticipantList))
						{
							$userIdToShareList[] = (int)$mentionUserId;
						}
					}
				}

				if (!empty($userIdToShareList) && $taskObject && $task)
				{
					$commentPoster = Comments\Task\CommentPoster::getInstance($taskObject->getId(), $currentUserId);
					$commentPoster->enableDeferredPostMode();
					$commentPoster->clearComments();

					foreach($userIdToShareList as $userIdToShare)
					{
						$taskObject->startWatch($userIdToShare, true);
					}

					$commentPoster->disableDeferredPostMode();
					$commentPoster->postComments();
					$commentPoster->clearComments();

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

				if ($taskObject)
				{
					$taskId = $taskObject->getId();

					foreach ($mentionUserIdList as $userId)
					{
						if (UserOption::isOptionSet($taskId, $userId, UserOption\Option::MUTED))
						{
							UserOption::delete($taskId, $userId, UserOption\Option::MUTED);
						}
					}
				}
			}
		}

		return $userIdToShareList;
	}

	/**
	 * @param $messageData
	 * @param $taskData
	 * @param $fromUser
	 * @param $toUsers
	 * @param array $eventData
	 * @return bool
	 */
	private static function sendNotification($messageData, $taskData, $fromUser, $toUsers, array $eventData = []): bool
	{
		if (empty($toUsers) || !IM::includeModule())
		{
			return false;
		}

		// some sources do not even pass $eventData, so ensure we got at least MESSAGE_ID
		$eventData['MESSAGE_ID'] = $messageData['ID'];

//		$notifyType = IM_NOTIFY_SYSTEM;
//		$notifyAnswer = false;

		$user = \CTaskNotifications::getUser($fromUser);

//		$messageTemplate = '[color=#000]#TASK_TITLE#[/color][br][i]#USER_NAME#:[/i] #TASK_COMMENT_TEXT#';
//		$messageTemplatePush = '#USER_NAME#: #TASK_COMMENT_TEXT#';
		$message = (string)Util::trim(\CTextParser::clearAllTags($messageData['POST_MESSAGE']));

//		if (
//			Loader::includeModule('socialnetwork')
//			&& $messageData['POST_MESSAGE'] === \Bitrix\Socialnetwork\CommentAux\TaskInfo::POST_TEXT
//			&& array_key_exists('AUX_DATA', $eventData)
//		)
//		{
//			$commentInfo = unserialize($eventData['AUX_DATA'], ['allowed_classes' => false]);
//			if (!$commentInfo)
//			{
//				return false;
//			}
//			$message = Comments\Task\CommentPoster::getCommentText($commentInfo);
//		}

		$messageTemplate = \CTaskNotifications::getGenderMessage($fromUser, 'TASKS_COMMENT_MESSAGE_ADD');
		$messageTemplatePush = \CTaskNotifications::getGenderMessage($fromUser, 'TASKS_COMMENT_MESSAGE_ADD_PUSH');

		$messageCropped = self::cropMessage($message);
		if ($messageCropped !== '')
		{
			$messageTemplate .= Loc::getMessage('TASKS_COMMENT_MESSAGE_ADD_WITH_TEXT');
			$messageTemplatePush .= ': #TASK_COMMENT_TEXT#';
		}

		\CTaskNotifications::SendMessageEx(
			$taskData['ID'],
			$fromUser,
			$toUsers,
			[
				'INSTANT' => str_replace('#TASK_COMMENT_TEXT#', $messageCropped, $messageTemplate),
				'EMAIL' => str_replace('#TASK_COMMENT_TEXT#', $message, $messageTemplate),
				'PUSH' => \CTaskNotifications::cropMessage(
					$messageTemplatePush,
					[
						'USER_NAME' => User::formatName($user),
						'TASK_TITLE' => $taskData['TITLE'],
						'TASK_COMMENT_TEXT' => $message,
					],
					\CTaskNotifications::PUSH_MESSAGE_MAX_LENGTH
				)
			],
			[
				'ENTITY_CODE' => 'COMMENT',
				'ENTITY_OPERATION' => 'ADD',
				'EVENT_DATA' => $eventData,
				'NOTIFY_EVENT' => 'comment',
				'NOTIFY_ANSWER' => true,
				'TASK_DATA' => $taskData,
				'TASK_URL' => [
					'PARAMETERS' => static::getUrlParameters($messageData['ID']),
					'HASH' => static::makeUrlHash($messageData['ID']),
				],
			]
		);

//		\CTaskNotifications::SendMessageEx(
//			$taskData["ID"],
//			$fromUser,
//			$toUsers,
//			[
//				'INSTANT' => str_replace(
//					["#TASK_COMMENT_TEXT#", "#USER_NAME#", "#TASK_TITLE#"],
//					[$messageCropped, User::formatName($user), $taskData["TITLE"]],
//					$messageTemplate
//				),
//				'EMAIL' => str_replace(
//					["#TASK_COMMENT_TEXT#", "#USER_NAME#"],
//					[$message, User::formatName($user)],
//					$messageTemplate
//				),
//				'PUSH' => \CTaskNotifications::cropMessage(
//					$messageTemplatePush,
//					[
//						'USER_NAME' => 			User::formatName($user),
//						'TASK_TITLE' => 		$taskData["TITLE"],
//						'TASK_COMMENT_TEXT' => 	$message
//					],
//					\CTaskNotifications::PUSH_MESSAGE_MAX_LENGTH
//				)
//			],
//			[
//				'ENTITY_CODE' => 'COMMENT',
//				'ENTITY_OPERATION' => 'ADD',
//				'EVENT_DATA' => $eventData,
//				'NOTIFY_EVENT' => 'comment',
//				'NOTIFY_ANSWER' => $notifyAnswer,
//				'NOTIFY_TYPE' => $notifyType,
//				'TASK_DATA' => $taskData,
//				'TASK_URL' => [
//					'PARAMETERS' => static::getUrlParameters($messageData['ID']),
//					'HASH' => static::makeUrlHash($messageData['ID'])
//				],
//				'PUSH_PARAMS' => [
//					'SENDER_NAME' => $taskData["TITLE"]
//				]
//			]);

		return true;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	private static function cropMessage(string $message): string
	{
		// cropped message to instant messenger
		if (mb_strlen($message) >= 100)
		{
			$dot = '...';
			$message = mb_substr($message, 0, 99);

			if (mb_substr($message, -1) === '[')
			{
				$message = mb_substr($message, 0, 98);
			}

			if (
				(($lastLinkPosition = mb_strrpos($message, '[u')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'http://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'https://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'ftp://')) !== false)
				|| (($lastLinkPosition = mb_strrpos($message, 'ftps://')) !== false)
			)
			{
				if (mb_strpos($message, ' ', $lastLinkPosition) === false)
				{
					$message = mb_substr($message, 0, $lastLinkPosition);
				}
			}

			$message .= $dot;
		}

		return $message;
	}

	/**
	 * @param int $authorId
	 * @param int $taskId
	 */
	private static function addToAuditor(int $authorId, int $taskId)
	{
		$task = \CTaskItem::getInstance($taskId, User::getAdminId());
		try
		{
			$taskData = $task->getData(false);
			if (
				$authorId === (int)$taskData['CREATED_BY']
				|| $authorId === (int)$taskData['RESPONSIBLE_ID']
				|| in_array($authorId, $taskData['ACCOMPLICES'])
				|| in_array($authorId, $taskData['AUDITORS'])
			)
			{
				return;
			}
			$auditors = array_merge($taskData['AUDITORS'], [$authorId]);
			$task->update(['AUDITORS' => $auditors], ['SKIP_ACCESS_CONTROL' => true, 'FIELDS_FOR_COMMENTS' => []]);
		}
		catch (\TasksException $e)
		{
			return;
		}
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

		try
		{
			$oTask  = new \CTaskItem((int)$taskId, User::getAdminId());
			$arTask = $oTask->getData(false);
		}
		catch (\TasksException | \CTaskAssertException $e)
		{
			return;
		}

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
