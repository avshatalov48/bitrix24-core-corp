<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Disk\Rest\Attachment;
use Bitrix\Tasks\Integration\Forum\Task\Comment;

/**
 * Class CTaskCommentItem
 */
final class CTaskCommentItem extends CTaskSubItemAbstract
{
	const ACTION_COMMENT_ADD    = 0x01;
	const ACTION_COMMENT_MODIFY = 0x02;
	const ACTION_COMMENT_REMOVE = 0x03;

	/**
	 * @param CTaskItemInterface $task
	 * @param array $fields
	 * @return int
	 * @throws ObjectException
	 * @throws TasksException
	 */
	public static function add(CTaskItemInterface $task, $fields): int
	{
		if (!is_array($fields))
		{
			$fields = [];
		}

		if (!array_key_exists('AUTHOR_ID', $fields))
		{
			$fields['AUTHOR_ID'] = $task->getExecutiveUserId();
		}
		$fields = self::formatPostDateField($fields);

        // rights are checked inside forum`s taskEntity class, NO NEED to check rights here
		$result = Comment::add($task->getId(), $fields);
		if (!$result->isSuccess())
		{
			$errorMessages = ($result->getErrors() ? $result->getErrors()->getMessages() : ['']);
			$errorCode = TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED | TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE;

			throw new TasksException(serialize($errorMessages), $errorCode);
		}

		$resultData = $result->getData();

		return (int)$resultData['ID'];
	}

	/**
	 * @param $fields
	 * @return bool
	 * @throws TasksException
	 * @throws ObjectException
	 */
	public function update($fields): bool
	{
		if (!is_array($fields))
		{
			$fields = [];
		}

		// Nothing to do?
		if (empty($fields))
		{
			return false;
		}

		// rights are checked inside forum`s taskEntity class, NO NEED to check rights
		// but for compatibility reasons, we have to leave exception throw here
		if (!$this->isActionAllowed(self::ACTION_COMMENT_MODIFY))
		{
			throw new TasksException('Action is not allowed', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$fields = self::formatPostDateField($fields);
		$result = Comment::update($this->itemId, $fields, $this->taskId);

		if (!$result->isSuccess())
		{
			$errorMessages = ($result->getErrors() ? $result->getErrors()->getMessages() : ['']);
			$errorCode = TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED | TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE;

			throw new TasksException(serialize($errorMessages), $errorCode);
		}

		return true;
	}

	/**
	 * @return bool
	 * @throws TasksException
	 */
	public function delete(): bool
	{
		// rights are checked inside forum`s taskEntity class, NO NEED to check rights
		// but for compatibility reasons, we have to leave exception throw here
		if (!$this->isActionAllowed(self::ACTION_COMMENT_REMOVE))
		{
			throw new TasksException('Action is not allowed', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		$result = Comment::delete($this->itemId, $this->taskId);
		if (!$result->isSuccess())
		{
			$errorMessages = ($result->getErrors() ? $result->getErrors()->getMessages() : ['']);
			$errorCode = TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED | TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE;

			throw new TasksException(serialize($errorMessages), $errorCode);
		}

		return true;
	}

	/**
	 * @param $actionId
	 * @return bool
	 */
	public function isActionAllowed($actionId): bool
	{
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int)$actionId;

		if (!in_array($actionId, [self::ACTION_COMMENT_ADD, self::ACTION_COMMENT_MODIFY, self::ACTION_COMMENT_REMOVE], true))
		{
			return false;
		}

		if ($actionId === self::ACTION_COMMENT_ADD)
		{
			return true; // you can view the task (if you reached this point, you obviously can)
		}

		$taskData = $this->oTaskItem->getData();
		$forumTopicId = $taskData['FORUM_TOPIC_ID'];

		if (!(int)$forumTopicId) // task even doesnt have a forum topic
		{
			return false;
		}

		if ($actionId === self::ACTION_COMMENT_MODIFY)
		{
			return CTaskComments::CanUpdateComment(
				$this->oTaskItem->getId(),
				$this->itemId,
				$this->executiveUserId,
				['FORUM_TOPIC_ID' => $forumTopicId]
			);
		}

		if ($actionId === self::ACTION_COMMENT_REMOVE)
		{
			return CTaskComments::CanRemoveComment(
				$this->oTaskItem->getId(),
				$this->itemId,
				$this->executiveUserId,
				['FORUM_TOPIC_ID' => $forumTopicId]
			);
		}

		return false;
	}

	/**
	 * @param $fields
	 * @return array
	 * @throws ObjectException
	 */
	private static function formatPostDateField($fields): array
	{
		if (array_key_exists('POST_DATE', $fields) && $fields['POST_DATE'])
		{
			$localTimestamp = (new DateTime($fields['POST_DATE']))->getTimestamp();
			$fields['POST_DATE'] = DateTime::createFromTimestamp($localTimestamp - \CTimeZone::GetOffset());
		}

		return $fields;
	}

	final protected function fetchListFromDb($taskData, $arOrder = array(), $arFilter = array())
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		$arItemsData = array();
		$rsData = null;

		if ($topicId = intval($taskData['FORUM_TOPIC_ID']))
		{
			CTaskAssert::assert(\Bitrix\Main\Loader::IncludeModule('forum'));

			if (!is_array($arFilter))
			{
				$arFilter = array();
			}

			$arFilter['TOPIC_ID'] = $topicId;

			$rsData = CForumMessage::GetList($arOrder, $arFilter/*, false, 0, array("SELECT" => array("UF_FORUM_MESSAGE_DOC"))*/);

			if (!is_object($rsData))
			{
				throw new Exception();
			}

			CTaskAssert::assert(\Bitrix\Main\Loader::includeModule('disk'));

			$driver = \Bitrix\Disk\Driver::getInstance();
			$userFieldManager = $driver->getUserFieldManager();

			while ($arData = $rsData->fetch())
			{
				if ($arData['NEW_TOPIC'] == 'Y') // typically the first one is a non-interesting system message, so skip it
				{
					continue;
				}

				$attachedObjects = $userFieldManager->getAttachedObjectByEntity(
					'FORUM_MESSAGE',
					$arData['ID'],
					'UF_FORUM_MESSAGE_DOC'
				);
				foreach ($attachedObjects as $object)
				{
					$arData['ATTACHED_OBJECTS_IDS'][] = $object->getId();
				}

				$arItemsData[] = $arData;
			}
		}

		return (array($arItemsData, $rsData));
	}

	final protected function fetchDataFromDb($taskId, $itemId)
	{
		CTaskAssert::assertLaxIntegers($taskId, $itemId);
		CTaskAssert::assert(CModule::IncludeModule('forum'));

		/** @noinspection PhpDeprecationInspection */
		$rsData = CForumMessage::GetList(
				array(),
			array('ID' => (int) $itemId)
		);

		if (is_object($rsData) && ($arData = $rsData->fetch()))
			return ($arData);
		else
			throw new Exception();
	}

	/**
	 * Do some post-processing of result of calling particular methods.
	 * This method is only for rest purposes
	 *
	 * @access private
	 */
	public static function postProcessRestRequest($methodName, $result, $parameters = array())
	{
		if (!is_array($parameters))
		{
			$parameters = array();
		}

		if ($methodName == 'getlist')
		{
			foreach ($result as $index => $comment)
			{
				$attachedObjectsIds = $comment['ATTACHED_OBJECTS_IDS'];
				if (is_array($attachedObjectsIds))
				{
					foreach ($attachedObjectsIds as $attachmentId)
					{
						$result[$index]['ATTACHED_OBJECTS'][$attachmentId] = Attachment::getById($attachmentId, ['SERVER' => $parameters['SERVER']]);
					}
				}

				unset($result[$index]['ATTACHED_OBJECTS_IDS']);
			}
		}
		elseif ($methodName == 'get')
		{
			foreach ($result['ATTACHED_OBJECTS_IDS'] as $attachmentId)
			{
				$result['ATTACHED_OBJECTS'][$attachmentId] = Attachment::getById($attachmentId, array('SERVER' => $parameters['SERVER']));
			}
		}

		return $result;
	}

	/**
	 * Todo: proxy forum API calls with this function
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
		$argsParsed = CTaskRestService::_parseRestParams('ctaskcommentitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				[$taskId, $arFields] = $argsParsed;
				$task = CTaskItem::getInstance($taskId, $executiveUserId);
				$itemId = self::add($task, $arFields);

				$returnValue = $itemId;
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = is_array($argsParsed[1]) ? $argsParsed[1] : array();
				$filter = is_array($argsParsed[2]) ? $argsParsed[2] : array();
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				[$oCommentItems, $rsData] = self::fetchList($oTaskItem, $order, $filter);

				$returnValue = array();

				foreach ($oCommentItems as $oCommentItem)
				{
					$returnValue[] = $oCommentItem->getData(false);
				}
			}
			else
			{
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
			}
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$obComment  = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
			{
				CTaskAssert::assert(\Bitrix\Main\Loader::includeModule('disk'));

				$driver = \Bitrix\Disk\Driver::getInstance();
				$userFieldManager = $driver->getUserFieldManager();

				$returnValue = $obComment->getData();
				foreach ($userFieldManager->getAttachedObjectByEntity('FORUM_MESSAGE', $itemId, 'UF_FORUM_MESSAGE_DOC') as $attachedObject)
				{
					$returnValue['ATTACHED_OBJECTS_IDS'][] = $attachedObject->getId();
				}
			}
			else
			{
				$returnValue = call_user_func_array(array($obComment, $methodName), $argsParsed);
			}
		}

		return (array($returnValue, null));
	}

	public static function onEventFilter($arParams, $arHandler)
	{
		if ( ! isset($arHandler['EVENT_NAME']) )
		{
			$arHandler['EVENT_NAME'] = '$arHandler[\'EVENT_NAME\'] is not set';
		}

		$commentId = 	(int) array_shift($arParams);
		$parameters = 	array_shift($arParams);
		$taskId =       intval($parameters['TASK_ID']);

		$arEventFields = array(
			'FIELDS_BEFORE'        => 'undefined',
			'FIELDS_AFTER'         => 'undefined',
			'IS_ACCESSIBLE_BEFORE' => 'undefined',
			'IS_ACCESSIBLE_AFTER'  => 'undefined'
		);

		CTaskAssert::assert($taskId >= 1);

		if(!$commentId)
		{
			return;
		}

		switch (strtolower($arHandler['EVENT_NAME']))
		{
			case 'ontaskcommentadd':
				$arEventFields['FIELDS_AFTER']         =  array('ID' => $commentId, 'TASK_ID' => $taskId);
				$arEventFields['IS_ACCESSIBLE_BEFORE'] = 'N';
			break;

			case 'ontaskcommentupdate':
				$arEventFields['FIELDS_BEFORE']        =  array('ID' => $commentId, 'TASK_ID' => $taskId);
				$arEventFields['FIELDS_AFTER']         =  array('ID' => $commentId, 'TASK_ID' => $taskId, 'ACTION' => 'EDIT');
			break;

			case 'ontaskcommentdelete':
				$arEventFields['FIELDS_BEFORE']        =  array('ID' => $commentId, 'TASK_ID' => $taskId);
				$arEventFields['FIELDS_AFTER']         =  array('ID' => $commentId, 'TASK_ID' => $taskId, 'ACTION' => 'DEL');
				break;

			default:
				throw new Exception(
					'tasks\' RPC event handler: onEventFilter: '
					. 'not allowed $arHandler[\'EVENT_NAME\']: '
					. $arHandler['EVENT_NAME']
				);
			break;
		}

		return ($arEventFields);
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @access private
	 */
	public static function getManifest()
	{
		$arWritableKeys = array('POST_MESSAGE', 'AUTHOR_ID', 'POST_DATE');
		$arDateKeys = array('POST_DATE');
		$arSortableKeys = array('ID', 'AUTHOR_ID', 'AUTHOR_NAME', 'AUTHOR_EMAIL', /*'EDITOR_ID',*/ 'POST_DATE');
		$arReadableKeys = array_merge(
			array('POST_MESSAGE_HTML'),
			$arSortableKeys,
			$arDateKeys,
			$arWritableKeys,
			array('ATTACHED_OBJECTS')
		);
		$arFiltrableKeys = array('ID', 'AUTHOR_ID', 'AUTHOR_NAME', 'POST_DATE');

		return(array(
			'Manifest version' => '1.1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'commentitem',
			'REST: writable commentitem data fields'   =>  $arWritableKeys,
			'REST: readable commentitem data fields'   =>  $arReadableKeys,
			'REST: sortable commentitem data fields'   =>  $arSortableKeys,
			'REST: filterable commentitem data fields' =>  $arFiltrableKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'getlist' => array(
					'staticMethod'         =>  true,
					'mandatoryParamsCount' =>  1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' => $arFiltrableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							)
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'collectionInReturnValue'  => true
				),
				'get' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					),
					'allowedKeysInReturnValue' => $arReadableKeys
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
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'update' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'delete' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						)
					)
				)
			)
		));
	}
}
