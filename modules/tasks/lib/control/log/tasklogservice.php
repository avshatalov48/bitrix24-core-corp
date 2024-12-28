<?php

namespace Bitrix\Tasks\Control\Log;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Control\Log\Command\DeleteByTaskIdCommand;
use Bitrix\Tasks\Control\Log\Exception\TaskLogAddException;
use Bitrix\Tasks\Control\Log\Command\AddCommand;
use Bitrix\Tasks\Control\Log\Command\DeleteCommand;
use Bitrix\Tasks\Integration\Disk\UserField;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\EO_Log;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use CFile;
use CTimeZone;
use CUserTypeManager;
use Exception;

class TaskLogService
{
	protected CUserTypeManager $ufManager;

	public function __construct()
	{
		global $USER_FIELD_MANAGER;
		$this->ufManager = $USER_FIELD_MANAGER;
	}

	protected function initTrackedFields(): array
	{
		$fields = [];

		$comparedFields = [
			'TITLE' => 'string',
			'DESCRIPTION' => 'text',
			'REAL_STATUS' => 'integer',
			'STATUS' => 'integer',
			'PRIORITY' => 'integer',
			'MARK' => 'string',
			'COMMENT' => 'integer',
			'DELETE' => 'integer',
			'NEW' => 'integer',
			'RENEW' => 'integer',
			'MOVE_TO_BACKLOG' => 'integer',
			'MOVE_TO_SPRINT' => 'integer',
			'PARENT_ID' => 'integer',
			'GROUP_ID' => 'integer',
			'STAGE_ID' => 'integer',
			'CREATED_BY' => 'integer',
			'RESPONSIBLE_ID' => 'integer',
			'ACCOMPLICES' => 'array',
			'AUDITORS' => 'array',
			'DEADLINE' => 'date',
			'START_DATE_PLAN' => 'date',
			'END_DATE_PLAN' => 'date',
			'DURATION_PLAN' => 'integer',
			'DURATION_PLAN_SECONDS' => 'integer',
			'DURATION_FACT' => 'integer',
			'TIME_ESTIMATE' => 'integer',
			'TIME_SPENT_IN_LOGS' => 'integer',
			'TAGS' => 'array',
			'DEPENDS_ON' => 'array',
			'FILES' => 'array',
			'UF_TASK_WEBDAV_FILES' => 'array',
			'CHECKLIST_ITEM_CREATE' => 'string',
			'CHECKLIST_ITEM_RENAME' => 'string',
			'CHECKLIST_ITEM_REMOVE' => 'string',
			'CHECKLIST_ITEM_CHECK' => 'string',
			'CHECKLIST_ITEM_UNCHECK' => 'string',
			'ADD_IN_REPORT' => 'bool',
			'TASK_CONTROL' => 'bool',
			'ALLOW_TIME_TRACKING' => 'bool',
			'ALLOW_CHANGE_DEADLINE' => 'bool',
			'FLOW_ID' => 'integer',
		];

		foreach ($comparedFields as $code => $type)
		{
			$fields[$code] = ['TYPE' => $type];
		}

		$ufs = $this->ufManager->getUserFields('TASKS_TASK');
		foreach ($ufs as $code => $desc)
		{
			// exception for system disk files
			$title = '';
			if ($code !== UserField::getMainSysUFCode() && array_key_exists('EDIT_FORM_LABEL', $desc))
			{
				$title = $desc['EDIT_FORM_LABEL'];
			}

			$fields[$code] = [
				'TITLE' => $title,
				'TYPE' => $desc['MULTIPLE'] === 'Y' ? 'array' : 'string'
			];
		}

		return $fields;
	}

	/**
	 * Getting the union of self::$comparedFields and user fields
	 *
	 * @return array
	 */
	public function getTrackedFields(): array
	{
		static $fields;

		if (!$fields)
		{
			$fields = $this->initTrackedFields();
		}

		return $fields;
	}

	/**
	 * Add entry to task history
	 *
	 * @param AddCommand $command
	 * @return TaskLog
	 *
	 * @throws TaskLogAddException
	 * @throws InvalidCommandException
	 * @throws Exception
	 */
	public function add(AddCommand $command): TaskLog
	{
		$command->validateAdd();

		if ($this->isAutoUserField($command->field))
		{
			throw new TaskLogAddException('Attempt to add user field to task history');
		}

		if ($this->isBoolean($command->field))
		{
			throw new TaskLogAddException('Attempt to add boolean field to task history');
		}

		$addResult = LogTable::add([
			'CREATED_DATE' => $command->createdDate,
			'USER_ID' => $command->userId,
			'TASK_ID' => $command->taskId,
			'FIELD' => $command->field,
			'FROM_VALUE' => $command->change?->getFromValue(),
			'TO_VALUE' => $command->change?->getToValue(),
		]);

		if (!$addResult->isSuccess())
		{
			Logger::logErrors($addResult->getErrorCollection());
			throw new SystemException(implode(' ', $addResult->getErrorMessages()));
		}

		/** @var EO_Log $entityObject */
		$entityObject = $addResult->getObject();

		return new TaskLog($entityObject->collectValues());
	}

	/**
	 * Delete all entries from task history
	 *
	 * @param DeleteByTaskIdCommand $command
	 * @return bool
	 *
	 * @throws InvalidCommandException
	 * @throws Exception
	 */
	public function deleteByTaskId(DeleteByTaskIdCommand $command): bool
	{
		$command->validateDelete();

		LogTable::deleteByTaskId($command->taskId);

		return true;
	}

	/**
	 * Delete task log
	 *
	 * @param DeleteCommand $command
	 * @return bool
	 *
	 * @throws InvalidCommandException
	 * @throws Exception
	 */
	public function delete(DeleteCommand $command): bool
	{
		$command->validateDelete();

		LogTable::delete($command->id);

		return true;
	}

	/**
	 * Get changes between current task fields and new ones
	 *
	 * @param array $currentFields
	 * @param array $newFields
	 * @return Change[]
	 *
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getChanges(array $currentFields, array $newFields): array
	{
		$changes = [];

		foreach ($currentFields as $key => $field)
		{
			$this->castValueTypeByKeyInTrackedFields($currentFields[$key], $key);
		}

		foreach ($newFields as $key => $field)
		{
			$this->castValueTypeByKeyInTrackedFields($newFields[$key], $key);
		}

		if (array_key_exists('REAL_STATUS', $currentFields))
		{
			$currentFields['STATUS'] = $currentFields['REAL_STATUS'];
		}

		if (array_key_exists('TITLE', $currentFields))
		{
			$currentFields['TITLE'] = Emoji::encode($currentFields['TITLE']);
		}

		if (array_key_exists('DESCRIPTION', $currentFields))
		{
			$currentFields['DESCRIPTION'] = Emoji::encode($currentFields['DESCRIPTION']);
		}

		if (array_key_exists('TITLE', $newFields))
		{
			$newFields['TITLE'] = Emoji::encode($newFields['TITLE']);
		}

		if (array_key_exists('DESCRIPTION', $newFields))
		{
			$newFields['DESCRIPTION'] = Emoji::encode($newFields['DESCRIPTION']);
		}

		$trackedFields = $this->getTrackedFields();

		foreach ($newFields as $key => $newField)
		{
			if (
				!array_key_exists($key, $trackedFields)
				|| (($currentFields[$key] ?? null) == $newField) // don't change it to strict!
			)
			{
				continue;
			}

			switch ($key)
			{
				case 'FILES':
					if (!array_key_exists($key, $currentFields))
					{
						break;
					}

					$filesChanges = $this->getFilesChanges($currentFields[$key], $newField);

					if (array_key_exists('DELETED_FILES', $filesChanges))
					{
						$changes['DELETED_FILES'] = $filesChanges['DELETED_FILES'];
					}

					if (array_key_exists('NEW_FILES', $filesChanges))
					{
						$changes['NEW_FILES'] = $filesChanges['NEW_FILES'];
					}

					break;
				case 'UF_TASK_WEBDAV_FILES':
					$currentFields[$key] = implode(',', $currentFields[$key] ?? []);
					$newFields[$key] = implode(',', $newField);

					if ($currentFields[$key] !== $newFields[$key])
					{
						$changes[$key] = new Change(($currentFields[$key]) ?: false, ($newFields[$key]) ?: false);
					}

					break;
				case 'STAGE_ID':
					if (!array_key_exists($key, $currentFields))
					{
						break;
					}

					$oldGroupId = $currentFields['GROUP_ID'];
					$newGroupId = $newFields['GROUP_ID'] ?? $oldGroupId;

					$stageChange = $this->getStageChange($currentFields[$key], $newField, $oldGroupId, $newGroupId);
					if ($stageChange)
					{
						$changes['STAGE'] = $stageChange;
					}

					break;
				case 'UF_CRM_TASK':
					if (!array_key_exists($key, $currentFields))
					{
						break;
					}

					$crmTaskChanges = $this->getCrmTaskChanges($currentFields[$key], $newField);

					if (array_key_exists('UF_CRM_TASK_DELETED', $crmTaskChanges))
					{
						$changes['UF_CRM_TASK_DELETED'] = $crmTaskChanges['UF_CRM_TASK_DELETED'];
					}

					if (array_key_exists('UF_CRM_TASK_ADDED', $crmTaskChanges))
					{
						$changes['UF_CRM_TASK_ADDED'] = $crmTaskChanges['UF_CRM_TASK_ADDED'];
					}

					break;
				default:
					if (!array_key_exists($key, $currentFields))
					{
						break;
					}

					if ($trackedFields[$key]['TYPE'] === 'text')
					{
						$currentFields[$key] = false;
						$newFields[$key] = false;
					}
					elseif ($trackedFields[$key]['TYPE'] === 'array')
					{
						$currentFields[$key] = implode(',', $currentFields[$key]);
						$newFields[$key] = implode(',', $newField);
					}

					$changes[$key] = new Change(($currentFields[$key]) ?: false, ($newFields[$key]) ?: false);

					break;
			}
		}

		return $changes;
	}

	/**
	 * @param array $currentFiles
	 * @param array $newFiles
	 * @return Change[]
	 */
	protected function getFilesChanges(array $currentFiles, array $newFiles): array
	{
		$filesChanges = [];

		$deleted = array_diff($currentFiles, $newFiles);
		if (!empty($deleted))
		{
			$fileNames = [];
			$res = CFile::GetList(
				[],
				['@ID' => implode(',', $deleted)]
			);

			while ($file = $res->Fetch())
			{
				$fileNames[] = $file['ORIGINAL_NAME'];
			}

			if (!empty($fileNames))
			{
				$filesChanges['DELETED_FILES'] = new Change(implode(', ', $fileNames), false);
			}
		}

		$added = array_diff($newFiles, $currentFiles);
		if (!empty($added))
		{
			$fileNames = [];
			$res = CFile::GetList(
				[],
				['@ID' => implode(',', $added)]
			);

			while ($file = $res->Fetch())
			{
				$fileNames[] = $file['ORIGINAL_NAME'];
			}

			if (count($fileNames))
			{
				$filesChanges['NEW_FILES'] = new Change(false, implode(', ', $fileNames));
			}
		}

		return $filesChanges;
	}

	/**
	 * @param int $oldStageId
	 * @param int $newStageId
	 * @param int $oldGroupId
	 * @param int $newGroupId
	 * @return Change|null
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function getStageChange(int $oldStageId, int $newStageId, int $oldGroupId, int $newGroupId): ?Change
	{
		if ($newGroupId !== $oldGroupId)
		{
			return null;
		}

		$isScrum = false;
		if (Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($newGroupId);
			$isScrum = ($group && $group->isScrumProject());
		}

		if ($isScrum)
		{
			$kanbanService = new KanbanService();

			if (!$oldStageId && $oldGroupId)
			{
				$sprintService = new SprintService();

				$sprint = $sprintService->getActiveSprintByGroupId($newGroupId);

				$oldStageId = $kanbanService->getDefaultStageId($sprint->getId());
			}

			$stageTitles = $kanbanService->getStageTitles([$newStageId, $oldStageId]);

			return new Change($stageTitles[$oldStageId], $stageTitles[$newStageId]);
		}

		if (!$oldStageId && $oldGroupId)
		{
			$oldStageId = StagesTable::getDefaultStageId($oldGroupId);
		}

		$stageFrom = false;
		$stageTo = false;

		$res = StagesTable::getList([
			'select' => [
				'ID',
				'TITLE',
			],
			'filter' => [
				'@ID' => [
					$oldStageId,
					$newStageId,
				],
			],
		]);

		while ($stage = $res->fetch())
		{
			if ((int)$stage['ID'] === $oldStageId)
			{
				$stageFrom = $stage['TITLE'];
			}
			elseif ((int)$stage['ID'] === $newStageId)
			{
				$stageTo = $stage['TITLE'];
			}
		}

		return new Change($stageFrom, $stageTo);
	}

	/**
	 * @param array $currentTasks
	 * @param array $newTasks
	 * @return Change[]
	 */
	protected function getCrmTaskChanges(array $currentTasks, array $newTasks): array
	{
		$crmTaskChanges = [];

		$added = implode(',', array_diff($newTasks, $currentTasks));
		if (!empty($added))
		{
			$crmTaskChanges['UF_CRM_TASK_ADDED'] = new Change(false, $added);
		}

		$deleted = implode(',', array_diff($currentTasks, $newTasks));
		if (!empty($deleted))
		{
			$crmTaskChanges['UF_CRM_TASK_DELETED'] = new Change($deleted, false);
		}

		return $crmTaskChanges;
	}

	/**
	 * Casting the type of the received value
	 * by its field in $this->getTrackedFields() to the desired one
	 *
	 * @param mixed $value
	 * @param int|string $key
	 * @return void
	 */
	public function castValueTypeByKeyInTrackedFields(mixed &$value, int|string $key): void
	{
		$trackedFields = $this->getTrackedFields();

		if (!array_key_exists($key, $trackedFields))
		{
			return;
		}

		switch ($trackedFields[$key]['TYPE'])
		{
			case 'integer':
				$value = (int)((string)$value);

				break;
			case 'string':
				$value = trim((string)$value);

				break;
			case 'array':
				if (!is_array($value))
				{
					$value = explode(',', $value);
				}
				$value = array_map(static fn ($item): mixed => is_string($item) ? trim($item) : $item, $value);
				$value = array_filter($value);
				$value = array_unique($value);
				sort($value);

				break;
			case 'date':
				$shiftedTimestamp = MakeTimeStamp($value);

				if (!$shiftedTimestamp)
				{
					$value = strtotime($value ?? '');
				}
				else
				{
					// It can be other date on server (relative to client), ...
					$timezoneWasDisabled = !CTimeZone::enabled();

					if ($timezoneWasDisabled)
					{
						CTimeZone::enable();
					}

					$value = $shiftedTimestamp - CTimeZone::getOffset();

					if ($timezoneWasDisabled)
					{
						CTimeZone::disable();
					}
				}
				/* We mustn't store result of MakeTimestamp() in DB,
					 because it is shifted for time zone offset already,
					 which can't be restored. */

				break;
			case 'bool':
				if ($value !== 'Y' && $value !== true)
				{
					$value = false;
				}

				if($value === 'Y')
				{
					$value = true;
				}

				break;
		}
	}

	private function isAutoUserField(mixed $field): bool
	{
		return str_starts_with($field, 'UF_AUTO_');
	}

	private function isBoolean(mixed $field): bool
	{
		$booleanFields = $this->getBooleanFields();

		return in_array($field, $booleanFields);
	}

	private function getBooleanFields(): array
	{
		$trackedFields = $this->getTrackedFields();

		$booleanFields = [];
		foreach ($trackedFields as $field => $value)
		{
			if($value['TYPE'] === 'bool')
			{
				$booleanFields[] = $field;
			}
		}

		return $booleanFields;
	}
}
