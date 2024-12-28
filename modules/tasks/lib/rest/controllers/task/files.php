<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Rest\Controllers\Base;
use CAdminException;
use CTaskItem;

class Files extends Base
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			CTaskItem::class,
			'task',
			static function($className, $id) {
				return new $className($id, CurrentUser::get()->getId());
			}
		);
	}

	/**
	 * @restMethod tasks.task.files.attach
	 */
	public function attachAction(CTaskItem $task, int $fileId, array $params = []): ?array
	{
		if (!$task->checkAccess(ActionDictionary::ACTION_TASK_EDIT))
		{
			$this->errorCollection->add([new Error('Access denied')]);
			return null;
		}

		$attachmentId = Integration\Disk\Rest\Attachment::attach(
			$task->getId(),
			$fileId,
			[
				'USER_ID' => CurrentUser::get()->getId(),
				'ENTITY_ID' => Integration\Rest\Task\UserField::getTargetEntityId(),
				'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
			]
		);
		if (!$attachmentId)
		{
			global $APPLICATION;

			$exception = $APPLICATION->GetException();
			if ($exception instanceof CAdminException)
			{
				foreach ($exception->GetMessages() as $message)
				{
					$this->errorCollection->add([new Error($message['text'])]);
				}
			}
			else
			{
				$this->errorCollection->add([new Error('Unexpected error')]);
			}

			return null;
		}

		(new Integration\CRM\TimeLineManager($task->getId(), $this->getUserId()))->onTaskFilesUpdated()->save();

		return ['attachmentId' => $attachmentId];
	}

	/**
	 * Return all DB and UF_ fields of file
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return  [];
	}

	/**
	 * Add new file
	 *
	 * @param int $taskId
	 * @param array $fields
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public function addAction($taskId, array $fields, array $params = array())
	{
		return 1;
	}

	/**
	 * Remove existing file
	 *
	 * @param int $taskId
	 * @param int $fileId
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction($taskId, $fileId, array $params = array())
	{
		return false;
	}

	/**
	 * Get list all files at task
	 *
	 * @param int $taskId
	 *
	 * @param array $params
	 *
	 *
	 * @return array
	 */
	public function listAction($taskId, array $params = array())
	{
		return [];
	}

	/**
	 * Get file data
	 *
	 * @param int $fileId
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function getAction($fileId, array $params = array())
	{
		return [];
	}
}