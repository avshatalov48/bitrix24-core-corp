<?php
namespace Bitrix\Tasks\Replica;

class TaskAttachmentHandler extends \Bitrix\Replica\Client\AttachmentHandler
{
	protected $moduleId = "tasks";
	protected $relation = "b_tasks.ATTACH_ID";

	protected $executeEventEntity = "Task";
	protected $parentRelation = "b_tasks.ID";
	protected $diskConnectorString = "tasks_task";

	protected $dataFields = array("DESCRIPTION");

	/**
	 * Adds attachment to user field value for given entity.
	 *
	 * @param integer $taskId Task identifier.
	 * @param integer $diskAttachId Disk attachment identifier.
	 *
	 * @return void
	 */
	public static function updateUserField($taskId, $diskAttachId)
	{
		global $USER_FIELD_MANAGER;
		$ufValue = $USER_FIELD_MANAGER->GetUserFieldValue("TASKS_TASK", "UF_TASK_WEBDAV_FILES", $taskId);
		if (!$ufValue)
		{
			$ufValue = array($diskAttachId);
		}
		elseif (is_array($ufValue))
		{
			$ufValue[] = $diskAttachId;
		}
		else
		{
			$ufValue = $diskAttachId;
		}
		$USER_FIELD_MANAGER->Update("TASKS_TASK", $taskId, array("UF_TASK_WEBDAV_FILES" => $ufValue));
	}

	/**
	 * Returns array of attachments for given entity.
	 *
	 * @param integer $taskId Task identifier.
	 *
	 * @return array[]\Bitrix\Disk\AttachedObject
	 */
	public static function getUserField($taskId)
	{
		$result = array();

		$taskList = \CTasks::getList(
			array(),
			array(
				"=ID" => $taskId,
			),
			array("ID", "UF_TASK_WEBDAV_FILES"),
			array('bGetZombie' => true)
		);
		$taskInfo = $taskList->fetch();
		if ($taskInfo && $taskInfo["UF_TASK_WEBDAV_FILES"] && \Bitrix\Main\Loader::includeModule('disk'))
		{
			foreach ($taskInfo["UF_TASK_WEBDAV_FILES"] as $attachId)
			{
				$attachedObject = \Bitrix\Disk\AttachedObject::getById($attachId, array('OBJECT'));
				if ($attachedObject && $attachedObject->getFile())
				{
					$result[$attachId] = $attachedObject;
				}
			}
		}

		return $result;
	}

	/**
	 * Remote event handler.
	 *
	 * @param \Bitrix\Main\Event $event Contains two parameters: 0 - id, 1 - data.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAfterAdd
	 * @see \Bitrix\Replica\Client\AttachmentHandler::onAfterUpdate
	 */
	public function onExecuteDescriptionFix(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		$taskId = $parameters[0];
		$fields = $parameters[1];
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		if ($this->replaceGuidsWithFiles($fields))
		{
			$update = $sqlHelper->prepareUpdate("b_tasks", $fields);
			if (strlen($update[0]) > 0)
			{
				$sql = "UPDATE ".$sqlHelper->quote("b_tasks")." SET ".$update[0]." WHERE ID = ".$taskId;
				$connection->query($sql);
			}
		}
	}
}
