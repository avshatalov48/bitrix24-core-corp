<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk\Volume;
use Bitrix\Main\Type\DateTime;

/**
 * Task cleanlier class.
 * @package Bitrix\Disk\Volume
 */
class Task
{
	/** @var Volume\IVolumeIndicator */
	private $indicator;

	/** @var array */
	private $param;

	/** @var int */
	private $id = -1;

	/** @var int */
	private $lastFileId = -1;

	/** @var int */
	private $ownerId = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID;

	/** @var string */
	private $indicatorType = '';

	/** @var int */
	private $droppedFolderCount = 0;

	/** @var int */
	private $droppedFileCount = 0;

	/** @var int */
	private $droppedVersionCount = 0;

	/** @var int */
	private $iterationFileCount = -1;

	/** @var int */
	private $failCount = 0;

	/** @var string */
	private $lastError;

	/** @var boolean */
	private $fatalError = false;

	/** @var int */
	private $status = -1;

	const TASK_STATUS_NONE = 0;
	const TASK_STATUS_WAIT = 1;
	const TASK_STATUS_RUNNING = 2;
	const TASK_STATUS_DONE = 3;
	const TASK_STATUS_CANCEL = 4;

	const DROP_UNNECESSARY_VERSION = 'DROP_UNNECESSARY_VERSION';
	const DROP_TRASHCAN = 'DROP_TRASHCAN';
	const EMPTY_FOLDER = 'EMPTY_FOLDER';
	const DROP_FOLDER = 'DROP_FOLDER';


	/**
	 * Saves task state.
	 * @return boolean
	 */
	public function fixState()
	{
		if ($this->getId() > 0)
		{
			$taskParams = array();

			// status changed
			if ($this->getStatus() != (int)$this->getParam('AGENT_LOCK'))
			{
				$taskParams['AGENT_LOCK'] = $this->getStatus();
			}
			$taskParams[self::DROP_UNNECESSARY_VERSION] = $this->getStatusSubTask(self::DROP_UNNECESSARY_VERSION);
			$taskParams[self::DROP_TRASHCAN] = $this->getStatusSubTask(self::DROP_TRASHCAN);
			$taskParams[self::EMPTY_FOLDER] = $this->getStatusSubTask(self::EMPTY_FOLDER);
			$taskParams[self::DROP_FOLDER] = $this->getStatusSubTask(self::DROP_FOLDER);

			$taskParams['DROPPED_FILE_COUNT'] = $this->getDroppedFileCount();
			$taskParams['DROPPED_VERSION_COUNT'] = $this->getDroppedVersionCount();
			$taskParams['DROPPED_FOLDER_COUNT'] = $this->getDroppedFolderCount();
			$taskParams['LAST_FILE_ID'] = $this->getLastFileId();
			$taskParams['FAIL_COUNT'] = $this->getFailCount();
			$taskParams['LAST_ERROR'] = $this->getLastError();

			$result = \Bitrix\Disk\Internals\VolumeTable::update($this->getId(), $taskParams);

			return $result->isSuccess();
		}

		$result = \Bitrix\Disk\Internals\VolumeTable::add(array(
			'INDICATOR_TYPE' => $this->getIndicatorType(),
			'OWNER_ID' => $this->getOwnerId(),
			'STORAGE_ID' => $this->getParam('STORAGE_ID'),
			'AGENT_LOCK' => $this->getStatus(),
			self::DROP_UNNECESSARY_VERSION => $this->getStatusSubTask(self::DROP_UNNECESSARY_VERSION),
			self::DROP_TRASHCAN => $this->getStatusSubTask(self::DROP_TRASHCAN),
			self::EMPTY_FOLDER => $this->getStatusSubTask(self::EMPTY_FOLDER),
			self::DROP_FOLDER => $this->getStatusSubTask(self::DROP_FOLDER),
			'LAST_FILE_ID' => ($this->getLastFileId() > 0 ? $this->getLastFileId() : null),
			'FAIL_COUNT' => $this->getFailCount(),
			'LAST_ERROR' => $this->getLastError(),
		));
		if ($result->isSuccess())
		{
			$this->id = $result->getId();
		}

		return $result->isSuccess();
	}

	/**
	 * Loads task params from db.
	 * @param int $filterId Id of saved indicator result from b_disk_volume.
	 * @param int $ownerId Task owner id.
	 * @return boolean
	 */
	public function loadTaskById($filterId, $ownerId = \Bitrix\Disk\SystemUser::SYSTEM_USER_ID)
	{
		$filter = array(
			'=ID' => $filterId,
		);
		if ($ownerId != \Bitrix\Disk\SystemUser::SYSTEM_USER_ID)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerResult = \Bitrix\Disk\Internals\VolumeTable::getList(array(
			'filter' => $filter,
			'limit' => 1,
		));
		if ($row = $workerResult->fetch())
		{
			$this->param = $row;
			$this->id = (int)$this->param['ID'];
			$this->setLastFileId((int)$this->param['LAST_FILE_ID']);
			$this->setOwnerId((int)$this->param['OWNER_ID']);
			$this->setStatus((int)$this->param['AGENT_LOCK']);
			$this->setIndicatorType($this->param['INDICATOR_TYPE']);
			if ($this->param['LAST_ERROR'] != '')
			{
				$this->setLastError($this->param['LAST_ERROR']);
			}

			$this->droppedFolderCount = (int)$this->param['DROPPED_FOLDER_COUNT'];
			$this->droppedFileCount = (int)$this->param['DROPPED_FILE_COUNT'];
			$this->droppedVersionCount = (int)$this->param['DROPPED_VERSION_COUNT'];
			$this->failCount = (int)$this->param['FAIL_COUNT'];

			return true;
		}

		return false;
	}

	/**
	 * Checks if all files have deleted.
	 * @param string $subTask Sub task to check.
	 * @return boolean
	 */
	public function hasTaskFinished($subTask)
	{
		$subTaskDone = true;
		if(
			$this->hasUserCanceled() === false &&
			$this->hasFatalError() === false
		)
		{
			switch ($subTask)
			{
				case self::DROP_TRASHCAN:
				case self::EMPTY_FOLDER:
				case self::DROP_FOLDER:
				{
					if ($this->getCountFilesToDrop() > 0)
					{
						if ($this->getIterationFileCount() === 0)
						{
							// there are no files in iteration
							break;
						}

						if ($this->getDroppedFileCount() + $this->getFailCount() >= $this->getCountFilesToDrop())
						{
							break;
						}

						$subTaskDone = false;
					}
					break;
				}

				case self::DROP_UNNECESSARY_VERSION:
				{
					if ($this->getCountVersionToDrop() > 0)
					{
						if ($this->getIterationFileCount() === 0)
						{
							// there are no files in iteration
							break;
						}

						if ($this->getDroppedVersionCount() + $this->getFailCount() >= $this->getCountVersionToDrop())
						{
							break;
						}

						$subTaskDone = false;
					}
					break;
				}
			}
		}

		return $subTaskDone;
	}

	/**
	 * Checks if status is in running mode.
	 * @param int $status Status to check.
	 * @param int[] $runningStatus This statuses are in running mode.
	 * @return boolean
	 */
	public static function isRunningMode($status, $runningStatus = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING))
	{
		return in_array((int)$status, $runningStatus);
	}

	/**
	 * Check user cancel task.
	 * @return boolean
	 */
	public function hasUserCanceled()
	{
		if ($this->getId() > 0 && self::isRunningMode($this->getStatus()))
		{
			$param = \Bitrix\Disk\Internals\VolumeTable::getByPrimary($this->getId(), array('select' => array('AGENT_LOCK')))->fetch();
			if ($param)
			{
				if ((int)$param['AGENT_LOCK'] === self::TASK_STATUS_CANCEL)
				{
					$this->setParam('AGENT_LOCK', self::TASK_STATUS_CANCEL);
					$this->setStatus(self::TASK_STATUS_CANCEL);

					return true;
				}
			}
		}

		return (bool)($this->getStatus() === self::TASK_STATUS_CANCEL);
	}

	/**
	 * Returns object indicator corresponding to task.
	 * @return Volume\IVolumeIndicator|boolean
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function getIndicator()
	{
		if (!$this->indicator instanceof Volume\IVolumeIndicator)
		{
			try
			{
				/** @var Volume\IVolumeIndicator $indicatorType */
				$indicatorType = $this->getIndicatorType();
				$this->indicator = Volume\Base::getIndicator($indicatorType::getIndicatorId());
				$this->indicator->setOwner($this->getOwnerId());
				if ($this->getId() > 0)
				{
					if (
						count($this->param) > 0 &&
						(int)$this->param['ID'] === $this->id
					)
					{
						$this->indicator->restoreFilter($this->param);
					}
					else
					{
						$this->indicator->restoreFilter($this->id);
					}
				}
			}
			catch(\Bitrix\Main\ObjectException $ex)
			{
				return false;
			}
		}
		return $this->indicator;
	}

	/**
	 * Returns indicator class name.
	 * @return string
	 */
	public function getIndicatorType()
	{
		return $this->indicatorType;
	}

	/**
	 * Sets indicator type.
	 * @param string $indicatorType Indicator class name.
	 * @return void
	 */
	public function setIndicatorType($indicatorType)
	{
		$this->indicatorType = $indicatorType;
	}

	/**
	 * Gets task id.
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Gets task owner id.
	 * @return int
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * Sets task owner id.
	 * @param int $ownerId Owner id.
	 * @return void
	 */
	public function setOwnerId($ownerId)
	{
		$this->ownerId = $ownerId;
	}

	/**
	 * Gets last file id.
	 * @return int
	 */
	public function getLastFileId()
	{
		return $this->lastFileId;
	}

	/**
	 * Sets last file id.
	 * @param int $lastFileId File id.
	 * @return void
	 */
	public function setLastFileId($lastFileId)
	{
		$this->lastFileId = $lastFileId;
	}

	/**
	 * Gets task status.
	 * @return int
	 */
	public function getStatus()
	{
		if ((int)$this->status > 0)
		{
			return (int)$this->status;
		}

		return self::TASK_STATUS_NONE;
	}

	/**
	 * Sets task status.
	 * @param int $status Task status.
	 * @return void
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Gets task status.
	 * @param string $subTask Subtask code.
	 * @return int
	 */
	public function getStatusSubTask($subTask)
	{
		if (isset($this->param[$subTask]))
		{
			return (int)$this->param[$subTask];
		}

		return self::TASK_STATUS_NONE;
	}


	/**
	 * Sets task status.
	 * @param string $subTask Subtask code.
	 * @param int $status Subtask status.
	 * @return void
	 */
	public function setStatusSubTask($subTask, $status)
	{
		if ($subTask != '')
		{
			$this->param[$subTask] = $status;
		}
	}

	/**
	 * Gets task parameter.
	 * @param string $code Parameter code.
	 * @return string|int|null
	 */
	public function getParam($code)
	{
		if (isset($this->param[$code]))
		{
			return $this->param[$code];
		}

		return null;
	}

	/**
	 * Sets task parameter.
	 * @param string $code Parameter code.
	 * @param string $value Parameter value.
	 * @return void
	 */
	public function setParam($code, $value)
	{
		$this->param[$code] = $value;
	}

	/**
	 * Gets dropped file count.
	 * @return int
	 */
	public function getDroppedFileCount()
	{
		return $this->droppedFileCount;
	}

	/**
	 * Gets count files loaded by filter for iteration.
	 * @return int
	 */
	public function getIterationFileCount()
	{
		return $this->iterationFileCount;
	}

	/**
	 * Sets count files loaded by filter for iteration.
	 * @param int $iterationFileCount Count rows in result set.
	 * @return void
	 */
	public function setIterationFileCount($iterationFileCount)
	{
		$this->iterationFileCount = $iterationFileCount;
	}

	/**
	 * Gets last error text occurred in iteration.
	 * @return int
	 */
	public function getLastError()
	{
		return $this->lastError;
	}

	/**
	 * Sets last error text ocoried  in iteration.
	 * @param string $errorText Error text to save.
	 * @return void
	 */
	public function setLastError($errorText)
	{
		$this->lastError = $errorText;
	}

	/**
	 * Increases dropped file count.
	 * @param int $delta Amount to add.
	 * @return void
	 */
	public function increaseDroppedFileCount($delta = 1)
	{
		if ($this->droppedFileCount < 0)
		{
			$this->droppedFileCount = 0;
		}
		$this->droppedFileCount += $delta;
	}

	/**
	 * Gets dropped version count.
	 * @return int
	 */
	public function getDroppedVersionCount()
	{
		return $this->droppedVersionCount;
	}

	/**
	 * Increases dropped version count.
	 * @param int $delta  Amount to add.
	 * @return void
	 */
	public function increaseDroppedVersionCount($delta = 1)
	{
		if ($this->droppedVersionCount < 0)
		{
			$this->droppedVersionCount = 0;
		}
		$this->droppedVersionCount += $delta;
	}

	/**
	 * Gets dropped folder count.
	 * @return int
	 */
	public function getDroppedFolderCount()
	{
		return $this->droppedFolderCount;
	}

	/**
	 * Increases dropped folder count.
	 * @param int $delta Amount to add.
	 * @return void
	 */
	public function increaseDroppedFolderCount($delta = 1)
	{
		if ($this->droppedFolderCount < 0)
		{
			$this->droppedFolderCount = 0;
		}
		$this->droppedFolderCount += $delta;
	}

	/**
	 * Set fatal error.
	 * @return void
	 */
	public function raiseFatalError()
	{
		$this->fatalError = true;
	}

	/**
	 * Has fatal error.
	 * @return boolean
	 */
	public function hasFatalError()
	{
		return $this->fatalError;
	}

	/**
	 * Reset fail and error.
	 * @return void
	 */
	public function resetFail()
	{
		$this->failCount = 0;
		$this->lastError = null;
	}

	/**
	 * Gets fail count.
	 * @return int
	 */
	public function getFailCount()
	{
		return $this->failCount;
	}

	/**
	 * Increases fail count.
	 * @param int $delta Amount to add.
	 * @return void
	 */
	public function increaseFailCount($delta = 1)
	{
		if ($this->failCount < 0)
		{
			$this->failCount = 0;
		}
		$this->failCount += $delta;
	}

	/**
	 * Gets count files to drop.
	 * @return int
	 */
	public function getCountFilesToDrop()
	{
		return (int)$this->getParam('FILE_COUNT');
	}

	/**
	 * Gets dropped folder count.
	 * @return int
	 */
	public function getCountVersionToDrop()
	{
		return (int)$this->getParam('UNNECESSARY_VERSION_COUNT');
	}

	/**
	 * Collects data for logging.
	 * @param \Bitrix\Disk\BaseObject|\Bitrix\Disk\Version $object Object to delete.
	 * @return array
	 */
	public function collectLogData($object)
	{
		$logData = array();
		/** \Bitrix\Disk\File $object */
		if ($object instanceof \Bitrix\Disk\File)
		{
			$crumbs = \Bitrix\Disk\CrumbStorage::getInstance()->getByObject($object, false);
			$logData = array(
				'OBJECT_TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE,
				'STORAGE_ID' => $object->getStorageId(),
				'OBJECT_ID' => $object->getId(),
				'OBJECT_PARENT_ID' => $object->getParentId(),
				'OBJECT_NAME' => $object->getName(),
				'OBJECT_PATH' => implode('/', $crumbs),
				'OBJECT_SIZE' => $object->getSize(),
				'OBJECT_CREATED_BY' => $object->getCreatedBy(),
				'OBJECT_UPDATED_BY' => $object->getUpdatedBy(),
				'FILE_ID' => $object->getFileId(),
			);
		}
		/** \Bitrix\Disk\Folder $object */
		elseif ($object instanceof \Bitrix\Disk\Folder)
		{
			$crumbs = \Bitrix\Disk\CrumbStorage::getInstance()->getByObject($object, false);
			$logData = array(
				'OBJECT_TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
				'STORAGE_ID' => $object->getStorageId(),
				'OBJECT_ID' => $object->getId(),
				'OBJECT_PARENT_ID' => $object->getParentId(),
				'OBJECT_NAME' => $object->getName(),
				'OBJECT_PATH' => implode('/', $crumbs),
				'OBJECT_CREATED_BY' => $object->getCreatedBy(),
				'OBJECT_UPDATED_BY' => $object->getUpdatedBy(),
			);
		}
		/** \Bitrix\Disk\Version $object */
		elseif ($object instanceof \Bitrix\Disk\Version)
		{
			$file = $object->getObject();
			$logData = array(
				'VERSION_ID' => $object->getId(),
				'VERSION_NAME' => $object->getName(),
			);
			if ($file instanceof \Bitrix\Disk\File)
			{
				$crumbs = \Bitrix\Disk\CrumbStorage::getInstance()->getByObject($file, false);
				$logData = array_merge($logData, array(
					'OBJECT_TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE,
					'STORAGE_ID' => $file->getStorageId(),
					'OBJECT_ID' => $file->getId(),
					'OBJECT_PARENT_ID' => $file->getParentId(),
					'OBJECT_NAME' => $file->getName(),
					'OBJECT_PATH' => implode('/', $crumbs),
					'OBJECT_SIZE' => $file->getSize(),
					'OBJECT_CREATED_BY' => $file->getCreatedBy(),
					'OBJECT_UPDATED_BY' => $file->getUpdatedBy(),
					'FILE_ID' => $file->getFileId(),
				));
			}
		}

		return $logData;
	}


	/**
	 * Fix data of object to table log.
	 * @param array $data File or folder info.
	 * @param string $operation Operation name.
	 * @return boolean
	 */
	public function log($data, $operation)
	{
		$data['OPERATION'] = $operation;
		$data['DELETED_TIME'] = new DateTime();
		$data['DELETED_BY'] = $this->getOwnerId();
		$result = \Bitrix\Disk\Internals\VolumeDeletedLogTable::add($data);
		return $result->isSuccess();
	}
}
