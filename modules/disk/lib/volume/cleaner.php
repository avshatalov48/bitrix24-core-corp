<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Security\SecurityContext;


/**
 * Disk cleanlier class.
 * @package Bitrix\Disk\Volume
 */
class Cleaner implements IErrorable, Volume\IVolumeTimeLimit
{
	/** @implements Volume\IVolumeTimeLimit */
	use Volume\TimeLimit;

	/** @var ErrorCollection */
	private $errorCollection;

	/** @var Volume\Task */
	private $task;

	/** @var int Owner id */
	private $ownerId;

	/** @var Disk\User */
	private $owner;

	// interval agent start
	public const AGENT_INTERVAL = 10;

	// fix every n interaction
	public const STATUS_FIX_INTERVAL = 20;

	// limit maximum number selected files
	public const MAX_FILE_PER_INTERACTION = 1000;

	// limit maximum number selected folders
	public const MAX_FOLDER_PER_INTERACTION = 1000;

	public const STEPPER_OPTION_ID = 'main.stepper.disk';


	/**
	 * @param int $ownerId Whom will mark as deleted by.
	 */
	public function __construct($ownerId = Disk\SystemUser::SYSTEM_USER_ID)
	{
		$this->ownerId = $ownerId;
	}


	/**
	 * Gets task.
	 * @return Volume\Task
	 */
	public function instanceTask(): Volume\Task
	{
		if (!($this->task instanceof Volume\Task))
		{
			$this->task = new Volume\Task();
		}

		return $this->task;
	}

	/**
	 * Loads task.
	 * @param int $filterId Id of saved indicator result from b_disk_volume.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return boolean
	 */
	public function loadTask($filterId, $ownerId = Disk\SystemUser::SYSTEM_USER_ID): bool
	{
		$task = $this->instanceTask();
		if ($filterId > 0)
		{
			if(!$task->loadTaskById($filterId, ($ownerId > 0 ? $ownerId : $this->ownerId)))
			{
				$this->collectError(new Error('Cleaner task not found', 'CLEANER_TASK_NOT_FOUND'));
				return false;
			}
		}

		if ($task->getOwnerId() > 0)
		{
			$this->ownerId = $task->getOwnerId();
		}

		return ($task->getId() > 0);
	}


	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className(): string
	{
		return get_called_class();
	}

	/**
	 * Returns agent's name.
	 * @return string
	 */
	public static function agentName(): string
	{
		return static::className(). '::runProcess();';
	}

	/**
	 * Determines if a script is loaded via cron/command line.
	 * @return bool
	 */
	public static function isCronRun(): bool
	{
		return
			!Main\ModuleManager::isModuleInstalled('bitrix24')
			&& (php_sapi_name() === 'cli')
		;
	}

	/**
	 * @deprecated
	 * @todo Remove it
	 * @param int $filterId
	 * @return string
	 */
	public static function runWorker($filterId)
	{
		return '';
	}

	/**
	 * @return string
	 */
	public static function runProcess(): string
	{
		$timer = new Volume\Timer();

		if (!self::isCronRun() && defined('START_EXEC_TIME') && START_EXEC_TIME > 0)
		{
			$timer
				->setTimeLimit(Volume\Timer::MAX_EXECUTION_TIME)
				->startTimer(\START_EXEC_TIME);
		}
		else
		{
			$timer
				->setTimeLimit(Volume\Timer::MAX_EXECUTION_TIME * 20)
				->startTimer();
		}

		$workerResult = VolumeTable::getList([
			'select' => ['ID', 'OWNER_ID'],
			'filter' => [
				'=AGENT_LOCK' => [
					Volume\Task::TASK_STATUS_WAIT,
					Volume\Task::TASK_STATUS_RUNNING,
				],
			],
			'limit' => 100
		]);
		if ($workerResult->getSelectedRowsCount() > 0)
		{
			while ($taskRow = $workerResult->fetch())
			{
				$filterId = (int)$taskRow['ID'];

				$cleaner = new static();
				$cleaner->setTimer($timer);

				if ($cleaner->loadTask($filterId))
				{
					$cleaner->runTask();
				}

				if ($timer->hasTimeLimitReached())
				{
					break;
				}
			}

			// count statistic for progress bar
			self::countWorker();
		}
		else
		{
			return '';
		}

		return static::agentName();
	}

	/**
	 * Runs cleaning process.
	 * @return bool
	 */
	protected function runTask(): bool
	{
		$task = $this->instanceTask();
		$indicator = $task->getIndicator();
		if (!$indicator instanceof Volume\IVolumeIndicator)
		{
			return true;
		}

		if ($task->getStatus() != Volume\Task::TASK_STATUS_RUNNING)
		{
			$task->setStatus(Volume\Task::TASK_STATUS_RUNNING);
		}

		// subTask to run
		$subTask = '';
		if (Volume\Task::isRunningMode($task->getStatusSubTask(Volume\Task::DROP_TRASHCAN)))
		{
			$subTask = Volume\Task::DROP_TRASHCAN;
		}
		elseif (Volume\Task::isRunningMode($task->getStatusSubTask(Volume\Task::EMPTY_FOLDER)))
		{
			$subTask = Volume\Task::EMPTY_FOLDER;
		}
		elseif (Volume\Task::isRunningMode($task->getStatusSubTask(Volume\Task::DROP_FOLDER)))
		{
			$subTask = Volume\Task::DROP_FOLDER;
		}
		elseif (Volume\Task::isRunningMode($task->getStatusSubTask(Volume\Task::DROP_UNNECESSARY_VERSION)))
		{
			$subTask = Volume\Task::DROP_UNNECESSARY_VERSION;
		}

		$repeatMeasure = function () use ($indicator, $task)
		{
			// reset offset
			$task->setLastFileId(0);
			$task->fixState();

			$retry = 1;
			while ($retry <= 3)
			{
				try
				{
					// check final result repeat measure
					self::repeatMeasure($indicator);
				}
				catch (Main\DB\SqlQueryException $exception)
				{
					if (mb_stripos($exception->getMessage(), 'deadlock found when trying to get lock; try restarting transaction') !== false)
					{
						// retrying in a few seconds
						sleep(5);
						$retry ++;
						continue;
					}

					throw $exception;
				}
				break;
			}

			// reload task
			$task->loadTaskById($indicator->getFilterId(), $task->getOwnerId());
		};

		// run subTask
		$taskDone = false;
		switch ($subTask)
		{
			case Volume\Task::DROP_TRASHCAN:
			{
				if ($task->getStatusSubTask($subTask) != Volume\Task::TASK_STATUS_RUNNING)
				{
					$task->setStatusSubTask($subTask, Volume\Task::TASK_STATUS_RUNNING);
				}

				if($this->deleteTrashcanByFilter($indicator))
				{
					$repeatMeasure();
					$taskDone = $task->hasTaskFinished($subTask);
				}
				elseif ($task->hasFatalError())
				{
					$taskDone = true;
				}

				break;
			}

			case Volume\Task::EMPTY_FOLDER:
			case Volume\Task::DROP_FOLDER:
			{
				if ($task->getStatusSubTask($subTask) != Volume\Task::TASK_STATUS_RUNNING)
				{
					$task->setStatusSubTask($subTask, Volume\Task::TASK_STATUS_RUNNING);
				}

				$folderId = $task->getParam('FOLDER_ID');
				$folder = Disk\Folder::getById($folderId);
				if ($folder instanceof Disk\Folder)
				{
					if ($this->deleteFolder($folder, ($subTask === Volume\Task::EMPTY_FOLDER)))
					{
						$repeatMeasure();
						$taskDone = $task->hasTaskFinished($subTask);
					}
					elseif ($task->hasFatalError())
					{
						$taskDone = true;
					}
				}
				else
				{
					$task->setLastError('Can not found folder #'.$folderId);
					$task->raiseFatalError();
					$taskDone = true;
				}

				break;
			}

			case Volume\Task::DROP_UNNECESSARY_VERSION:
			{
				if ($task->getStatusSubTask($subTask) != Volume\Task::TASK_STATUS_RUNNING)
				{
					$task->setStatusSubTask($subTask, Volume\Task::TASK_STATUS_RUNNING);
				}

				if($this->deleteUnnecessaryVersionByFilter($indicator))
				{
					$repeatMeasure();
					$taskDone = $task->hasTaskFinished($subTask);
				}
				elseif ($task->hasFatalError())
				{
					$taskDone = true;
				}

				break;
			}

			default:
			{
				$taskDone = true;
			}
		}

		if($taskDone)
		{
			// finish
			$task->setStatusSubTask($subTask, Volume\Task::TASK_STATUS_DONE);
			$task->setStatus(Volume\Task::TASK_STATUS_DONE);
		}

		// Fix task state
		$task->fixState();


		return $taskDone;
	}

	/**
	 * Adds delayed delete worker agent.
	 * @param array $params Named parameters:
	 * <pre>
	 * 		int ownerId - who is owner,
	 * 		int filterId - as row private id from b_disk_volume as filter id,
	 * 		int storageId - limit only one storage
	 * 		int delay - number seconds to delay first execution
	 * 		bool DROP_UNNECESSARY_VERSION - set job to delete unused version,
	 * 		bool DROP_TRASHCAN - set job to empty trashcan.
	 * 		bool DROP_FOLDER - set job to drop everything.
	 * 		bool EMPTY_FOLDER - set job to empty folder structure.
	 * </pre>
	 * @return boolean
	 */
	public static function addWorker(array $params = []): bool
	{
		$ownerId = (int)($params['ownerId'] ?? 0);

		if (!empty($params))
		{
			$filterId = (int)($params['filterId'] ?? 0);

			$task = new Volume\Task();
			if ($filterId > 0)
			{
				if (!$task->loadTaskById($filterId, $ownerId))
				{
					return false;
				}
				$ownerId = $task->getOwnerId();
			}

			$task->setStatus(Volume\Task::TASK_STATUS_WAIT);

			$subTaskCommands = [
				Volume\Task::DROP_UNNECESSARY_VERSION,
				Volume\Task::DROP_TRASHCAN,
				Volume\Task::DROP_FOLDER,
				Volume\Task::EMPTY_FOLDER,
			];
			foreach ($subTaskCommands as $command)
			{
				if (isset($params[$command]))
				{
					$task->setStatusSubTask(
						$command,
						(($params[$command] === true) ? Volume\Task::TASK_STATUS_WAIT : Volume\Task::TASK_STATUS_NONE)
					);
				}
			}

			if ($filterId > 0)
			{
				if (isset($params['manual']))
				{
					$task->resetFail();
				}
			}
			else
			{
				$task->setIndicatorType(Volume\Storage\Storage::className());
				$task->setParam('STORAGE_ID', (int)$params['storageId']);
				$task->setOwnerId($ownerId);
			}
			$task->fixState();
		}

		if ($ownerId > 0)
		{
			// count statistic for progress bar
			self::countWorker($ownerId);
		}

		$nextExecutionTime = '';
		if (!empty($params['delay']) && (int)$params['delay'] > 0)
		{
			$nextExecutionTime = \ConvertTimeStamp(\time() + \CTimeZone::getOffset() + (int)$params['delay'], "FULL");
		}

		$agentAdded = true;
		$agents = \CAgent::getList(
			['ID' => 'DESC'],
			['=NAME' => static::agentName()]
		);
		if (!$agents->fetch())
		{
			$agentAdded = (bool)(\CAgent::addAgent(
				static::agentName(),
				'disk',
				(self::canAgentUseCrontab() ? 'N' : 'Y'),
				self::AGENT_INTERVAL,
				'',
				'Y',
				$nextExecutionTime
			) !== false);
		}

		return $agentAdded;
	}


	/**
	 * Checks ability agent to use Crontab.
	 * @return bool
	 */
	public static function canAgentUseCrontab(): bool
	{
		$canAgentsUseCrontab = false;
		$agentsUseCrontab = Main\Config\Option::get('main', 'agents_use_crontab', 'N');
		if (
			!Main\ModuleManager::isModuleInstalled('bitrix24') &&
			($agentsUseCrontab === 'Y' || (defined('BX_CRONTAB_SUPPORT') && BX_CRONTAB_SUPPORT === true))
		)
		{
			$canAgentsUseCrontab = true;
		}

		return $canAgentsUseCrontab;
	}

	/**
	 * Cancels all agent process.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return void
	 */
	public static function cancelWorkers(int $ownerId = -1): void
	{
		$filter = [
			'=AGENT_LOCK' => [Volume\Task::TASK_STATUS_WAIT, Volume\Task::TASK_STATUS_RUNNING],
		];
		if ($ownerId > 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerResult = VolumeTable::getList([
			'select' => ['ID'],
			'filter' => $filter
		]);
		foreach ($workerResult as $row)
		{
			VolumeTable::update($row['ID'], ['AGENT_LOCK' => Volume\Task::TASK_STATUS_CANCEL]);
		}

		self::clearProgressInfo($ownerId);
	}

	/**
	 * Count worker agent for user.
	 * @param int $ownerId
	 * @return int
	 */
	public static function countWorker(int $ownerId = -1): int
	{
		$filter = [
			'=AGENT_LOCK' => [Volume\Task::TASK_STATUS_WAIT, Volume\Task::TASK_STATUS_RUNNING],
		];
		if ($ownerId > 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerResult = VolumeTable::getList([
			'runtime' => [
				new Entity\ExpressionField('CNT', 'COUNT(*)'),
				new Entity\ExpressionField('FILE_COUNT', 'SUM(FILE_COUNT)'),
				new Entity\ExpressionField('UNNECESSARY_VERSION_COUNT', 'SUM(UNNECESSARY_VERSION_COUNT)'),
				new Entity\ExpressionField('DROPPED_FILE_COUNT', 'SUM(DROPPED_FILE_COUNT)'),
				new Entity\ExpressionField('DROPPED_VERSION_COUNT', 'SUM(DROPPED_VERSION_COUNT)'),
				new Entity\ExpressionField('FAIL_COUNT', 'SUM(FAIL_COUNT)'),
			],
			'select' => [
				'OWNER_ID',
				'CNT',
				'FILE_COUNT',
				'UNNECESSARY_VERSION_COUNT',
				'DROPPED_FILE_COUNT',
				'DROPPED_VERSION_COUNT',
				'FAIL_COUNT',
				Volume\Task::DROP_UNNECESSARY_VERSION,
				Volume\Task::DROP_TRASHCAN,
				Volume\Task::EMPTY_FOLDER,
				Volume\Task::DROP_FOLDER,
			],
			'group' => [
				'OWNER_ID',
				Volume\Task::DROP_UNNECESSARY_VERSION,
				Volume\Task::DROP_TRASHCAN,
				Volume\Task::EMPTY_FOLDER,
				Volume\Task::DROP_FOLDER,
			],
			'filter' => $filter
		]);

		$totalFilesToDrop = 0;
		$droppedFilesCount = 0;
		$workerCount = 0;
		$failCount = 0;

		if ($workerResult->getSelectedRowsCount() > 0)
		{
			foreach ($workerResult as $row)
			{
				$workerCount += $row['CNT'];
				$failCount += $row['FAIL_COUNT'];
				if (Volume\Task::isRunningMode($row[Volume\Task::DROP_UNNECESSARY_VERSION]))
				{
					$totalFilesToDrop += $row['UNNECESSARY_VERSION_COUNT'];
					$droppedFilesCount += $row['DROPPED_VERSION_COUNT'];
				}
				if (Volume\Task::isRunningMode($row[Volume\Task::DROP_TRASHCAN]))
				{
					$totalFilesToDrop += $row['FILE_COUNT'];
					$droppedFilesCount += $row['DROPPED_FILE_COUNT'];
				}
				if (Volume\Task::isRunningMode($row[Volume\Task::DROP_FOLDER]))
				{
					$totalFilesToDrop += $row['FILE_COUNT'];
					$droppedFilesCount += $row['DROPPED_FILE_COUNT'];
				}
				if (Volume\Task::isRunningMode($row[Volume\Task::EMPTY_FOLDER]))
				{
					$totalFilesToDrop += $row['FILE_COUNT'];
					$droppedFilesCount += $row['DROPPED_FILE_COUNT'];
				}
			}
			self::setProgressInfo((int)$row['OWNER_ID'], (int)$totalFilesToDrop, (int)$droppedFilesCount, (int)$failCount);
		}
		else
		{
			self::clearProgressInfo($ownerId);
		}

		return $workerCount;
	}


	/**
	 * Check if workers exists. Sets up/removes missing task. Remove stepper info.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return int
	 */
	public static function checkRestoreWorkers(int $ownerId = -1): int
	{
		$filter = [
			'=AGENT_LOCK' => [Volume\Task::TASK_STATUS_WAIT, Volume\Task::TASK_STATUS_RUNNING]
		];
		if ($ownerId > 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerCount = VolumeTable::getCount($filter);
		if ($workerCount > 0)
		{
			$agents = \CAgent::getList(
				['ID' => 'DESC'],
				['=NAME' => self::agentName()]
			);
			if ((int)$agents->selectedRowsCount() == 0)
			{
				self::addWorker(['ownerId' => $ownerId]);
			}
		}
		else
		{
			self::clearProgressInfo($ownerId);
		}

		return $workerCount;
	}


	/**
	 * Deletes files corresponding to indicator filter.
	 * @param Volume\IVolumeIndicator $indicator Ignited indicator for file list filter.
	 * @return boolean
	 */
	public function deleteFileByFilter(Volume\IVolumeIndicator $indicator): bool
	{
		$subTaskDone = true;

		if ($indicator->getFilterValue('STORAGE_ID') > 0)
		{
			$storage = Disk\Storage::loadById($indicator->getFilterValue('STORAGE_ID'));
			if (!($storage instanceof Disk\Storage))
			{
				$this->collectError(
					new Error('Can not found storage #'.$indicator->getFilterValue('STORAGE_ID'), 'STORAGE_NOT_FOUND'),
					false,
					true
				);

				return false;
			}
			if (!$this->isAllowClearStorage($storage))
			{
				$this->collectError(
					new Error('Access denied to storage #'.$storage->getId(), 'ACCESS_DENIED'),
					false,
					true
				);

				return false;
			}
		}

		$task = $this->instanceTask();

		$filter = [];
		if ($task->getLastFileId() > 0)
		{
			$filter['>=ID'] = $task->getLastFileId();
		}

		$indicator->setLimit(self::MAX_FILE_PER_INTERACTION);

		$fileList = $indicator->getCorrespondingFileList($filter);

		$task->setIterationFileCount($fileList->getSelectedRowsCount());

		$countFileErasure = 0;

		foreach ($fileList as $row)
		{
			$fileId = $row['ID'];
			$file = Disk\File::getById($fileId);
			if ($file instanceof Disk\File)
			{
				if (!$this->isAllowClearFolder($file->getParent()))
				{
					$this->collectError(new Error("Access denied to file #$fileId", 'ACCESS_DENIED'));
				}
				else
				{
					$securityContext = $this->getSecurityContext($this->getOwner(), $file);
					if (!$file->canDelete($securityContext))
					{
						$this->collectError(new Error("Access denied to file #$fileId", 'ACCESS_DENIED'));
					}
					else
					{
						$this->deleteFile($file);
						$countFileErasure++;
					}
				}
			}

			$task->setLastFileId($fileId);

			// fix interval task state
			if ($countFileErasure >= self::STATUS_FIX_INTERVAL)
			{
				$countFileErasure = 0;

				if ($task->hasUserCanceled())
				{
					$subTaskDone = false;
					break;
				}

				$task->fixState();

				// count statistic for progress bar
				self::countWorker((int)$task->getOwnerId());
			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}
		}

		return $subTaskDone;
	}


	/**
	 * Deletes files in trashcan.
	 * @param Volume\IVolumeIndicator $indicator Ignited indicator for file list filter.
	 * @return boolean
	 */
	public function deleteTrashcanByFilter(Volume\IVolumeIndicator $indicator): bool
	{
		$subTaskDone = true;
		$task = $this->instanceTask();

		$filter = [
			'!=DELETED_TYPE' => Disk\Internals\ObjectTable::DELETED_TYPE_NONE
		];
		if ($task->getLastFileId() > 0)
		{
			$filter['>=ID'] = $task->getLastFileId();
		}

		$indicator->setLimit(self::MAX_FILE_PER_INTERACTION);

		$fileList = $indicator->getCorrespondingFileList($filter);

		$task->setIterationFileCount($fileList->getSelectedRowsCount());

		$countFileErasure = 0;

		foreach ($fileList as $row)
		{
			$fileId = $row['ID'];
			$file = Disk\File::getById($fileId);
			if ($file instanceof Disk\File)
			{
				$securityContext = $this->getSecurityContext($this->getOwner(), $file);
				if($file->canDelete($securityContext))
				{
					$this->deleteFile($file);
					$countFileErasure ++;
				}
				else
				{
					$this->collectError(new Error("Access denied to file #$fileId", 'ACCESS_DENIED'));
				}
			}

			$task->setLastFileId($fileId);

			// fix interval task state
			if ($countFileErasure >= self::STATUS_FIX_INTERVAL)
			{
				$countFileErasure = 0;

				if ($task->hasUserCanceled())
				{
					$subTaskDone = false;
					break;
				}

				$task->fixState();

				// count statistic for progress bar
				self::countWorker((int)$task->getOwnerId());
			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}
		}

		$indicator->setLimit(self::MAX_FOLDER_PER_INTERACTION);

		$folderList = $indicator->getCorrespondingFolderList(['!=DELETED_TYPE' => Disk\Internals\ObjectTable::DELETED_TYPE_NONE]);

		foreach ($folderList as $row)
		{
			$folder = Disk\Folder::getById($row['ID']);
			if ($folder instanceof Disk\Folder)
			{
				$this->deleteFolder($folder);
				$countFileErasure ++;
			}

			// fix interval task state
			if ($countFileErasure >= self::STATUS_FIX_INTERVAL)
			{
				$countFileErasure = 0;

				if ($task->hasUserCanceled())
				{
					$subTaskDone = false;
					break;
				}

				$task->fixState();

				// count statistic for progress bar
				self::countWorker((int)$task->getOwnerId());
			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}
		}

		return $subTaskDone;
	}


	/**
	 * Deletes unused file versions.
	 * @param Volume\IVolumeIndicator $indicator Ignited indicator for file list filter.
	 * @return boolean
	 */
	public function deleteUnnecessaryVersionByFilter(Volume\IVolumeIndicator $indicator): bool
	{
		$subTaskDone = true;

		if ($indicator->getFilterValue('STORAGE_ID') > 0)
		{
			$storage = Disk\Storage::loadById($indicator->getFilterValue('STORAGE_ID'));
			if (!($storage instanceof Disk\Storage))
			{
				$this->collectError(
					new Error('Can not found storage #'.$indicator->getFilterValue('STORAGE_ID'), 'STORAGE_NOT_FOUND'),
					false,
					true
				);

				return false;
			}
			if (!$this->isAllowClearStorage($storage))
			{
				$this->collectError(
					new Error('Access denied to storage #'.$storage->getId(), 'ACCESS_DENIED'),
					false,
					true
				);

				return false;
			}
		}

		$task = $this->instanceTask();

		$filter = [];
		if ($task->getLastFileId() > 0)
		{
			$filter['>=FILE_ID'] = $task->getLastFileId();
		}

		$indicator->setLimit(self::MAX_FILE_PER_INTERACTION);

		$versionList = $indicator->getCorrespondingUnnecessaryVersionList($filter);

		$task->setIterationFileCount($versionList->getSelectedRowsCount());

		$versionsPerFile = [];
		foreach ($versionList as $row)
		{
			$fileId = $row['FILE_ID'];
			$versionId = $row['VERSION_ID'];
			if (!isset($versionsPerFile[$fileId]))
			{
				$versionsPerFile[$fileId] = [];
			}
			$versionsPerFile[$fileId][] = $versionId;
		}
		unset($row, $fileId, $versionId, $versionList);


		$countFileErasure = 0;

		foreach ($versionsPerFile as $fileId => $versionIds)
		{
			$file = Disk\File::getById($fileId);

			if ($file instanceof Disk\File)
			{
				$securityContext = $this->getSecurityContext($this->getOwner(), $file);
				if($file->canDelete($securityContext))
				{
					$this->deleteFileUnnecessaryVersion($file, ['=ID' => $versionIds]);
					$countFileErasure++;
				}
				else
				{
					$this->collectError(new Error("Access denied to file #$fileId", 'ACCESS_DENIED'));
				}
			}

			$task->setLastFileId($fileId);

			// fix interval task state
			if ($countFileErasure >= self::STATUS_FIX_INTERVAL)
			{
				$countFileErasure = 0;

				if ($task->hasUserCanceled())
				{
					$subTaskDone = false;
					break;
				}

				$task->fixState();

				// count statistic for progress bar
				self::countWorker((int)$task->getOwnerId());
			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}
		}

		return $subTaskDone;
	}


	/**
	 * Returns disk security context.
	 * @param Disk\User $user Task owner.
	 * @param Disk\BaseObject $object File or folder.
	 * @return SecurityContext
	 */
	private function getSecurityContext(Disk\User $user, Disk\BaseObject $object): SecurityContext
	{
		static $securityContextCache = [];

		$userId = $user->getId();
		$storageId = $object->getStorageId();

		if (!isset($securityContextCache[$userId][$storageId]) || !($securityContextCache[$userId][$storageId] instanceof SecurityContext))
		{
			if (!isset($securityContextCache[$userId]))
			{
				$securityContextCache[$userId] = [];
			}

			if ($user->isAdmin())
			{
				$securityContextCache[$userId][$storageId] = new Disk\Security\FakeSecurityContext($userId);
			}
			else
			{
				$securityContextCache[$userId][$storageId] = $object->getStorage()->getSecurityContext($userId);
			}
		}

		return $securityContextCache[$userId][$storageId];
	}


	/**
	 * Deletes file.
	 * @param Disk\File $file File to drop.
	 * @return boolean
	 */
	public function deleteFile(Disk\File $file): bool
	{
		try
		{
			$task = $this->instanceTask();

			$logData = $task->collectLogData($file);

			if (!$file->delete($task->getOwnerId()))
			{
				$this->collectError($file->getErrors());

				return false;
			}

			$task->log($logData, __FUNCTION__);

			$task->increaseDroppedFileCount();

		}
		catch (Main\SystemException $exception)
		{
			$this->collectError(new Error($exception->getMessage(), $exception->getCode()), true, false);

			return false;
		}

		return true;
	}


	/**
	 * Deletes file unnecessary versions.
	 * @param Disk\File $file File to purify.
	 * @param array $additionalFilter Additional filter for vertion selection.
	 * @return boolean
	 */
	public function deleteFileUnnecessaryVersion(Disk\File $file, array $additionalFilter = []): bool
	{
		$subTaskDone = true;

		$filter = [
			'=OBJECT_ID' => $file->getId(),
		];
		if (count($additionalFilter) > 0)
		{
			$filter = array_merge($filter, $additionalFilter);
		}

		$versionList = Disk\Version::getList([
			'filter' => $filter,
			'select' => ['ID']
		]);
		foreach ($versionList as $row)
		{
			$versionId = $row['ID'];

			/** @var Disk\Version $version */
			$version = Disk\Version::getById($versionId);
			if(!$version instanceof Disk\Version)
			{
				//$this->collectError(new Error('Version '.$versionId.' was not found'));
				continue;
			}

			// is a head
			if ($version->getFileId() == $file->getFileId())
			{
				//$this->collectError(new Error('Version '.$versionId.' is a head'));
				continue;
			}

			// attached_object
			$attachedList = Disk\AttachedObject::getList([
				'filter' => [
					'=OBJECT_ID' => $file->getId(),
					'=VERSION_ID' => $version->getId(),
				],
				'select' => ['ID'],
				'limit' => 1,
			]);
			if($attachedList->getSelectedRowsCount() > 0)
			{
				$this->collectError(new Error('Version '.$versionId.' has attachments'));
				continue;
			}

			// external_link
			$externalLinkList = Disk\ExternalLink::getList([
				'filter' => [
					'=OBJECT_ID' => $file->getId(),
					'=VERSION_ID' => $version->getId(),
					'!TYPE' => Disk\ExternalLink::TYPE_AUTO,
				],
				'select' => ['ID'],
				'limit' => 1,
			]);
			if($externalLinkList->getSelectedRowsCount() > 0)
			{
				$this->collectError(new Error('Version '.$versionId.' has external links'));
				continue;
			}

			$task = $this->instanceTask();

			$logData = $task->collectLogData($version);

			try
			{
				// drop
				if (!$version->delete($task->getOwnerId()))
				{
					$this->collectError($version->getErrors());
				}
				else
				{
					$task->log($logData, __FUNCTION__);
					$task->increaseDroppedVersionCount();
				}
			}
			catch (Main\SystemException $exception)
			{
				$this->collectError(new Error($exception->getMessage(), $exception->getCode()));
			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}
		}

		return $subTaskDone;
	}


	/**
	 * Deletes folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @param boolean $emptyOnly Just delete folder's content.
	 * @return boolean
	 */
	public function deleteFolder(Disk\Folder $folder, bool $emptyOnly = false): bool
	{
		$subTaskDone = true;

		if (!$this->isAllowClearStorage($folder->getStorage()))
		{
			$this->collectError(
				new Error('Access denied to storage #'. $folder->getStorageId(), 'ACCESS_DENIED'),
				false,
				true
			);

			return false;
		}

		if (!$this->isAllowClearFolder($folder))
		{
			$this->collectError(
				new Error('Not allowed to drop #'. $folder->getId(), 'ACCESS_DENIED'),
				false,
				true
			);

			return false;
		}

		// restrict delete root folder
		$isRootFolder = false;
		if ($folder->getStorage()->getRootObjectId() == $folder->getId())
		{
			$isRootFolder = true;
			$emptyOnly = true;
		}

		if (!$emptyOnly && !$this->isAllowDeleteFolder($folder))
		{
			$this->collectError(
				new Error('Not allowed to drop #'. $folder->getId(), 'ACCESS_DENIED'),
				false,
				false
			);

			return false;
		}

		$countFileErasure = 0;

		$objectList = Disk\Internals\ObjectTable::getList([
			'filter' => [
				'=PATH_CHILD.PARENT_ID' => $folder->getId(),
			],
			'order' => [
				'PATH_CHILD.DEPTH_LEVEL' => 'DESC',
				'ID' => 'ASC'
			],
			'limit' => self::MAX_FOLDER_PER_INTERACTION,
		]);

		if ($objectList->getSelectedRowsCount() == 0 && $isRootFolder)
		{
			$objectList = Disk\Internals\ObjectTable::getList([
				'filter' => [
					'=PARENT_ID' => $folder->getId(),
				],
				'order' => [
					'ID' => 'ASC'
				],
				'limit' => self::MAX_FOLDER_PER_INTERACTION,
			]);
		}

		$task = $this->instanceTask();

		$task->setIterationFileCount($objectList->getSelectedRowsCount());

		foreach ($objectList as $row)
		{
			if ($row['ID'] == $folder->getId())
			{
				continue;
			}
			if ($isRootFolder)
			{
				// allow delete only files in root folder
				if ($row['PARENT_ID'] != $folder->getId() || $row['TYPE'] != Disk\Internals\ObjectTable::TYPE_FILE)
				{
					continue;
				}
			}

			$object = Disk\BaseObject::buildFromArray($row);

			/** @var Disk\Folder|Disk\File $object */
			if ($object instanceof Disk\Folder)
			{
				if ($isRootFolder)
				{
					// disallow recursive delete from root
					continue;
				}
				/** @var Disk\File $object */
				$securityContext = $this->getSecurityContext($this->getOwner(), $object);
				if ($object->canDelete($securityContext))
				{
					if ($this->isAllowDeleteFolder($object))
					{
						try
						{
							$logData = $task->collectLogData($object);

							/** @var Disk\Folder $object */
							if (!$object->deleteTree($task->getOwnerId()))
							{
								$this->collectError($object->getErrors(), false);

								$subTaskDone = false;
							}
							else
							{
								$task->log($logData, __FUNCTION__);
								$task->increaseDroppedFolderCount();
							}
						}
						catch (Main\SystemException $exception)
						{
							$this->collectError(
								new Error($exception->getMessage(), $exception->getCode()),
								true,
								false
							);
						}
					}
					else
					{
						$this->collectError(
							new Error('Not allowed to drop folder #'. $object->getId(), 'ACCESS_DENIED'),
							false,
							false
						);
					}
				}
				else
				{
					$this->collectError(
						new Error('Access denied to folder #'. $object->getId(), 'ACCESS_DENIED'),
						true,
						false
					);
				}
			}
			elseif($object instanceof Disk\File)
			{
				/** @var Disk\File $object */
				$securityContext = $this->getSecurityContext($this->getOwner(), $object);
				if($object->canDelete($securityContext))
				{
					$subTaskDone = $this->deleteFile($object);
				}
				else
				{
					$this->collectError(new Error('Access denied to file #'. $object->getId(), 'ACCESS_DENIED'));
				}
			}

			// fix interval task state
			$countFileErasure ++;
			if ($countFileErasure >= self::STATUS_FIX_INTERVAL)
			{
				$countFileErasure = 0;

				if ($task->hasUserCanceled())
				{
					$subTaskDone = false;
					break;
				}

				$task->fixState();

				// count statistic for progress bar
				self::countWorker((int)$task->getOwnerId());

			}

			if (!$this->checkTimeEnd())
			{
				$subTaskDone = false;
				break;
			}

		}

		if ($subTaskDone)
		{
			if ($emptyOnly === false)
			{
				try
				{
					$logData = $task->collectLogData($folder);

					if (!$folder->deleteTree($task->getOwnerId()))
					{
						$this->collectError($folder->getErrors());

						return false;
					}

					$task->log($logData, __FUNCTION__);
					$task->increaseDroppedFolderCount();
				}
				catch (Main\SystemException $exception)
				{
					$this->collectError(new Error($exception->getMessage(), $exception->getCode()));
				}
			}
		}

		return $subTaskDone;
	}


	/**
	 * Check ability to drop folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Disk\Folder $folder): bool
	{
		$allowDrop = true;

		if ($folder->isDeleted())
		{
			return true;
		}

		/** @var Volume\IDeleteConstraint[] $deleteConstraintList */
		static $deleteConstraintList;
		if (empty($deleteConstraintList))
		{
			$deleteConstraintList = [];

			// full list available indicators
			$constraintIdList = Volume\Base::listDeleteConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$deleteConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		/** @var Volume\IDeleteConstraint $indicator */
		foreach ($deleteConstraintList as $indicatorId => $indicator)
		{
			if (!$indicator->isAllowDeleteFolder($folder))
			{
				$allowDrop = false;
			}
		}

		return $allowDrop;
	}

	/**
	 * Check ability to empty folder.
	 * @param Disk\Folder $folder Folder to clear.
	 * @return boolean
	 */
	public function isAllowClearFolder(Disk\Folder $folder): bool
	{
		$allowClear = true;

		if ($folder->isDeleted())
		{
			return true;
		}

		/** @var Volume\IClearFolderConstraint[] $clearFolderConstraintList */
		static $clearFolderConstraintList;
		if (empty($clearFolderConstraintList))
		{
			$clearFolderConstraintList = [];

			// full list available indicators
			$constraintIdList = Volume\Base::listClearFolderConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$clearFolderConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		/** @var Volume\IClearFolderConstraint $indicator */
		foreach ($clearFolderConstraintList as $indicatorId => $indicator)
		{
			if (!$indicator->isAllowClearFolder($folder))
			{
				$allowClear = false;
			}
		}

		return $allowClear;
	}

	/**
	 * Check ability to clear storage.
	 * @param Disk\Storage $storage Storage to clear.
	 * @return boolean
	 */
	public function isAllowClearStorage(Disk\Storage $storage): bool
	{
		$allowClear = true;

		/** @var Volume\IClearConstraint[] $clearConstraintList */
		static $clearConstraintList;
		if (empty($clearConstraintList))
		{
			$clearConstraintList = [];

			// full list available indicators
			$constraintIdList = Volume\Base::listClearConstraint();
			foreach ($constraintIdList as $indicatorId => $indicatorIdClass)
			{
				$clearConstraintList[$indicatorId] = new $indicatorIdClass();
			}
		}

		if ($storage instanceof Disk\Storage)
		{
			/** @var Volume\IClearConstraint $indicator */
			foreach ($clearConstraintList as $indicatorId => $indicator)
			{
				if (!$indicator->isAllowClearStorage($storage))
				{
					$allowClear = false;
				}
			}
		}

		return $allowClear;
	}

	/**
	 * Repeats measurement for indicator.
	 * @param Volume\IVolumeIndicator $indicator Ignited indicator for measure.
	 * @return boolean
	 */
	public static function repeatMeasure(Volume\IVolumeIndicator $indicator): bool
	{
		$indicator->resetMeasurementResult();
		$indicator->measure();

		if ($indicator->getFilterValue('STORAGE_ID') > 0)
		{
			if ($indicator::className() != Volume\Storage\Storage::className())
			{
				/** @var Volume\IVolumeIndicator $storageIndicator */
				$storageIndicator = new Volume\Storage\Storage();
				$storageIndicator->setOwner($indicator->getOwner());

				$storageIndicator->addFilter('STORAGE_ID', $indicator->getFilterValue('STORAGE_ID'));
				$result = $storageIndicator->getMeasurementResult();
				if ($row = $result->fetch())
				{
					$storageIndicator->setFilterId($row['ID']);
				}
				$storageIndicator->measure();
			}

			if ($indicator::className() != Volume\Storage\TrashCan::className())
			{
				/** @var Volume\IVolumeIndicator $trashCanIndicator */
				$trashCanIndicator = new Volume\Storage\TrashCan();
				$trashCanIndicator->setOwner($indicator->getOwner());

				$trashCanIndicator->addFilter('STORAGE_ID', $indicator->getFilterValue('STORAGE_ID'));
				$result = $trashCanIndicator->getMeasurementResult();
				if ($row = $result->fetch())
				{
					$trashCanIndicator->setFilterId($row['ID']);
				}
				$trashCanIndicator->measure();
			}
		}

		return true;
	}




	/**
	 * Gets dropped file count.
	 * @return int
	 */
	public function getDroppedFileCount(): int
	{
		return $this->instanceTask()->getDroppedFileCount();
	}

	/**
	 * Gets dropped version count.
	 * @return int
	 */
	public function getDroppedVersionCount(): int
	{
		return $this->instanceTask()->getDroppedVersionCount();
	}

	/**
	 * Gets dropped folder count.
	 * @return int
	 */
	public function getDroppedFolderCount(): int
	{
		return $this->instanceTask()->getDroppedFolderCount();
	}

	/**
	 * Gets task owner id.
	 * @return int
	 */
	public function getOwnerId(): int
	{
		return $this->ownerId;
	}

	/**
	 * Gets task owner.
	 * @return Disk\User
	 */
	public function getOwner(): Disk\User
	{
		if (!($this->owner instanceof Disk\User))
		{
			$this->owner = Disk\User::loadById($this->getOwnerId());
			if (!($this->owner instanceof Disk\User))
			{
				$this->owner = Disk\User::loadById(Disk\SystemUser::SYSTEM_USER_ID);
			}
		}

		return $this->owner;
	}


	//region Errors

	/**
	 * Adds an array of errors to the collection.
	 * @param Main\Error[] | Main\Error $errors Raised error.
	 * @param boolean $increaseTaskFail Increase error count in task.
	 * @param boolean $raiseTaskFatalError Raise task fatal error.
	 * @return void
	 */
	public function collectError($errors, bool $increaseTaskFail = true, bool $raiseTaskFatalError = false): void
	{
		if (!($this->errorCollection instanceof ErrorCollection))
		{
			$this->errorCollection = new ErrorCollection();
		}

		if (is_array($errors))
		{
			$this->errorCollection->add($errors);
			$lastError = array_pop($errors);
		}
		else
		{
			$this->errorCollection->add([$errors]);
			$lastError = $errors;
		}

		if (($this->task instanceof Volume\Task) && ($lastError instanceof Error))
		{
			$task = $this->instanceTask();

			if ($increaseTaskFail)
			{
				$task->increaseFailCount();
			}
			if ($raiseTaskFatalError)
			{
				$task->raiseFatalError();
			}
			$task->setLastError($lastError->getMessage());
		}
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->toArray();
		}

		return [];
	}

	/**
	 * @return boolean
	 */
	public function hasErrors(): bool
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->hasErrors();
		}

		return false;
	}

	/**
	 * Returns array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code): array
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->getErrorsByCode($code);
		}

		return [];
	}

	/**
	 * Returns an error with the necessary code.
	 * @param string|int $code The code of the error.
	 * @return Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		if ($this->errorCollection instanceof ErrorCollection)
		{
			return $this->errorCollection->getErrorByCode($code);
		}

		return null;
	}

	//endregion

	//region ProgressInfo

	/**
	 * Set up information showing at stepper progress bar.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return array|null
	 */
	public static function getProgressInfo(int $ownerId): ?array
	{
		$optionSerialized = Main\Config\Option::get(
			self::STEPPER_OPTION_ID,
			self::className(). $ownerId,
			''
		);
		if (!empty($optionSerialized))
		{
			return unserialize($optionSerialized, ['allowed_classes' => false]);
		}

		return null;
	}

	/**
	 * Set up information showing at stepper progress bar.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @param int $totalFilesToDrop  Total files to drop.
	 * @param int $droppedFilesCount Dropped files count.
	 * @param int $failCount Failed deletion count.
	 * @return void
	 */
	public static function setProgressInfo(int $ownerId, int $totalFilesToDrop, int $droppedFilesCount = 0, int $failCount = 0): void
	{
		if ($totalFilesToDrop  > 0)
		{
			$option = self::getProgressInfo($ownerId);
			if (!empty($option) && $option['count'] > 0)
			{
				$prevTotalFilesToDrop = $option['count'];

				// If total count decreases mean some agents finished its work.
				if ($prevTotalFilesToDrop > $totalFilesToDrop)
				{
					$droppedFilesCount = ($prevTotalFilesToDrop - $totalFilesToDrop) + $droppedFilesCount;
					$totalFilesToDrop = $prevTotalFilesToDrop;
				}
			}

			Main\Config\Option::set(
				self::STEPPER_OPTION_ID,
				self::className().$ownerId,
				serialize(['steps' => ($droppedFilesCount + $failCount), 'count' => $totalFilesToDrop])
			);
		}
		else
		{
			self::clearProgressInfo($ownerId);
		}
	}

	/**
	 * Remove stepper progress bar.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return void
	 */
	public static function clearProgressInfo(int $ownerId = -1): void
	{
		if ($ownerId > 0)
		{
			Main\Config\Option::delete(self::STEPPER_OPTION_ID, ['name' => self::className() . $ownerId]);
		}
		else
		{
			$optionList = Main\Config\Option::getForModule(self::STEPPER_OPTION_ID);
			foreach ($optionList as $name => $value)
			{
				Main\Config\Option::delete(self::STEPPER_OPTION_ID, ['name' => $name]);
			}
		}
	}

	//endregion
}

