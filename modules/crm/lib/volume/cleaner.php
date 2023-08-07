<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Entity;
use Bitrix\Crm;
use Bitrix\Crm\Volume;

/**
 * Task cleanlier class.
 * @package Bitrix\Crm\Volume
 */
class Cleaner
{
	/** @var Volume\IVolumeIndicator | Volume\IVolumeClearFile | Volume\IVolumeClearEvent | Volume\IVolumeClearActivity */
	private $indicator;

	/** @var array */
	private $param;

	/** @var int */
	private $id = -1;

	/** @var int */
	private $lastId = -1;

	/** @var int */
	private $ownerId = 0;

	/** @var string */
	private $indicatorType = '';

	/** @var int */
	private $droppedEntityCount = 0;

	/** @var int */
	private $droppedFileCount = 0;

	/** @var int */
	private $droppedEventCount = 0;

	/** @var int */
	private $droppedActivityCount = 0;

	/** @var int */
	private $iterationCount = -1;

	/** @var int */
	private $failCount = 0;

	/** @var string */
	private $lastError;

	/** @var boolean */
	private $fatalError = false;

	/** @var int */
	private $status = -1;

	public const TASK_STATUS_NONE = 0;
	public const TASK_STATUS_WAIT = 1;
	public const TASK_STATUS_RUNNING = 2;
	public const TASK_STATUS_DONE = 3;
	public const TASK_STATUS_CANCEL = 4;

	public const DROP_ENTITY = 'DROP_ENTITY';
	public const DROP_FILE = 'DROP_FILE';
	public const DROP_EVENT = 'DROP_EVENT';
	public const DROP_ACTIVITY = 'DROP_ACTIVITY';

	// interval agent start
	public const AGENT_INTERVAL = 60;


	/**
	 * @deprecated
	 * @todo Remove it
	 * @param int $taskId Id of saved indicator result from b_disk_volume.
	 * @return string
	 */
	public static function runWorker($taskId)
	{
		return '';
	}

	/**
	 * Main cleaning agent.
	 * @return string
	 */
	public static function runProcess(): string
	{
		$workerResult = Crm\VolumeTable::getList([
			'select' => ['ID', 'OWNER_ID'],
			'filter' => [
				'=AGENT_LOCK' => [
					self::TASK_STATUS_WAIT,
					self::TASK_STATUS_RUNNING,
				],
			],
			'limit' => 100
		]);
		if ($workerResult->getSelectedRowsCount() > 0)
		{
			if (!self::isCronRun() && defined('START_EXEC_TIME'))
			{
				// running on hint
				$timeLeft = 60;
				$startTime = \START_EXEC_TIME;
			}
			else
			{
				// cron running
				$timeLeft = 600;
				$startTime = time();
			}

			while ($taskRow = $workerResult->fetch())
			{
				$filterId = (int)$taskRow['ID'];

				$cleaner = new static();

				if ($cleaner->loadTaskById($filterId))
				{
					$cleaner->runTask($timeLeft);
				}

				$timeLeft -= (time() - $startTime);
				if ($timeLeft <= 0)
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

		return self::agentName();
	}

	/**
	 * Runs clean process.
	 *
	 * @param int $timeLimit
	 * @return bool
	 */
	protected function runTask(int $timeLimit = 25): bool
	{
		$indicator = $this->getIndicator();
		if (
			!$indicator instanceof Volume\IVolumeIndicator
			|| !$indicator instanceof Volume\IVolumeClear
		)
		{
			$this->setStatus(self::TASK_STATUS_DONE)->fixState();
			return true;
		}

		$indicator->startTimer($timeLimit);

		if ($this->getStatus() != self::TASK_STATUS_RUNNING)
		{
			$this->setStatus(self::TASK_STATUS_RUNNING);
		}

		$indicator->setOwner($this->getOwnerId());

		// subTask to run
		$subTask = '';
		if (self::isRunningMode($this->getStatusSubTask(self::DROP_ENTITY)))
		{
			$subTask = self::DROP_ENTITY;
		}
		elseif (self::isRunningMode($this->getStatusSubTask(self::DROP_FILE)))
		{
			$subTask = self::DROP_FILE;
		}
		elseif (self::isRunningMode($this->getStatusSubTask(self::DROP_EVENT)))
		{
			$subTask = self::DROP_EVENT;
		}
		elseif (self::isRunningMode($this->getStatusSubTask(self::DROP_ACTIVITY)))
		{
			$subTask = self::DROP_ACTIVITY;
		}

		if ($this->getStatusSubTask($subTask) != self::TASK_STATUS_RUNNING)
		{
			$this->setStatusSubTask($subTask, self::TASK_STATUS_RUNNING);
			$this->setLastId(0);
			$indicator->setProcessOffset(0);
		}
		elseif ($this->getStatusSubTask($subTask) == self::TASK_STATUS_RUNNING && $this->getLastId() > 0)
		{
			$indicator->setProcessOffset($this->getLastId());
		}

		// run subTask
		$taskDone = false;
		switch ($subTask)
		{
			case self::DROP_ENTITY:
			{
				if (!$indicator->canClearEntity())
				{
					$this->setLastError('Indicator can not drop entity')->raiseFatalError();
					$taskDone = true;
					break;
				}

				$countLeft = $this->getCountToDrop($subTask) - $this->getDroppedCount($subTask) - $this->getFailCount();
				if ($countLeft <= 0)
				{
					$taskDone = true;
					break;
				}
				if ($countLeft <= $indicator::MAX_ENTITY_PER_INTERACTION)
				{
					$countLeft = $indicator->countEntity();
					if ($countLeft <= 0)
					{
						$taskDone = true;
						break;
					}
					$this->setIterationCount($countLeft);
				}
				else
				{
					$this->setIterationCount($indicator::MAX_ENTITY_PER_INTERACTION);
				}

				$dropped = $indicator->clearEntity();
				if ($dropped >= 0)
				{
					$taskDone = $this->hasTaskFinished($subTask);
				}
				elseif ($indicator->hasErrors())
				{
					$this->raiseFatalError();
					$taskDone = true;
				}

				$this
					->setLastId($indicator->getProcessOffset())
					->setDroppedCount($subTask, $indicator->getDroppedEntityCount())
					->setFailCount($indicator->getFailCount());

				if ($indicator->hasErrors())
				{
					$error = $indicator->getLastError();
					if ($error instanceof Main\Error)
					{
						$this->setLastError($error->getMessage());
					}
				}

				break;
			}

			case self::DROP_FILE:
			{
				if (!($indicator instanceof Volume\IVolumeClearFile))
				{
					$this->setLastError('Wrong parameter indicatorId')->raiseFatalError();
					$taskDone = true;
					break;
				}
				if (!$indicator->canClearFile())
				{
					$this->setLastError('Indicator can not drop entity files.')->raiseFatalError();
					$taskDone = true;
					break;
				}

				$countLeft = $this->getCountToDrop($subTask) - $this->getDroppedCount($subTask) - $this->getFailCount();
				if ($countLeft <= 0)
				{
					$taskDone = true;
					break;
				}
				if ($countLeft <= $indicator::MAX_FILE_PER_INTERACTION)
				{
					$countLeft = $indicator->countEntityWithFile();
					if ($countLeft <= 0)
					{
						$taskDone = true;
						break;
					}
					$this->setIterationCount($countLeft);
				}
				else
				{
					$this->setIterationCount($indicator::MAX_FILE_PER_INTERACTION);
				}

				$dropped = $indicator->clearFiles();
				if ($dropped >= 0)
				{
					$taskDone = $this->hasTaskFinished($subTask);
				}
				elseif ($indicator->hasErrors())
				{
					$this->raiseFatalError();
					$taskDone = true;
				}

				$this
					->setLastId($indicator->getProcessOffset())
					->setDroppedCount($subTask, $indicator->getDroppedFileCount())
					->setFailCount($indicator->getFailCount());

				if ($indicator->hasErrors())
				{
					$error = $indicator->getLastError();
					if ($error instanceof Main\Error)
					{
						$this->setLastError($error->getMessage());
					}
				}

				break;
			}

			case self::DROP_EVENT:
			{
				if (!($indicator instanceof Volume\IVolumeClearEvent))
				{
					$this->setLastError('Wrong parameter indicatorId')->raiseFatalError();
					$taskDone = true;
					break;
				}
				if (!$indicator->canClearEvent())
				{
					$this->setLastError('Indicator can not drop entity events.')->raiseFatalError();
					$taskDone = true;
					break;
				}

				$countLeft = $this->getCountToDrop($subTask) - $this->getDroppedCount($subTask) - $this->getFailCount();
				if ($countLeft <= 0)
				{
					$taskDone = true;
					break;
				}
				if ($countLeft <= $indicator::MAX_ENTITY_PER_INTERACTION)
				{
					$countLeft = $indicator->countEvent();
					if ($countLeft <= 0)
					{
						$taskDone = true;
						break;
					}
					$this->setIterationCount($countLeft);
				}
				else
				{
					$this->setIterationCount($indicator::MAX_ENTITY_PER_INTERACTION);
				}

				$dropped = $indicator->clearEvent();
				if ($dropped >= 0)
				{
					$taskDone = $this->hasTaskFinished($subTask);
				}
				elseif ($indicator->hasErrors())
				{
					$this->raiseFatalError();
					$taskDone = true;
				}

				$this
					->setLastId($indicator->getProcessOffset())
					->setDroppedCount($subTask, $indicator->getDroppedEventCount())
					->setFailCount($indicator->getFailCount());

				if ($indicator->hasErrors())
				{
					$error = $indicator->getLastError();
					if ($error instanceof Main\Error)
					{
						$this->setLastError($error->getMessage());
					}
				}

				break;
			}

			case self::DROP_ACTIVITY:
			{
				if (!($indicator instanceof Volume\IVolumeClearActivity))
				{
					$this->setLastError('Wrong parameter indicatorId')->raiseFatalError();
					$taskDone = true;
					break;
				}
				if (!$indicator->canClearActivity())
				{
					$this->setLastError('Indicator can not drop entity activity.')->raiseFatalError();
					$taskDone = true;
					break;
				}

				$countLeft = $this->getCountToDrop($subTask) - $this->getDroppedCount($subTask) - $this->getFailCount();
				if ($countLeft <= 0)
				{
					$taskDone = true;
					break;
				}
				if ($countLeft <= $indicator::MAX_ENTITY_PER_INTERACTION)
				{
					$countLeft = $indicator->countActivity();
					if ($countLeft <= 0)
					{
						$taskDone = true;
						break;
					}
					$this->setIterationCount($countLeft);
				}
				else
				{
					$this->setIterationCount($indicator::MAX_ENTITY_PER_INTERACTION);
				}

				$dropped = $indicator->clearActivity();
				if ($dropped >= 0)
				{
					$taskDone = $this->hasTaskFinished($subTask);
				}
				elseif ($indicator->hasErrors())
				{
					$this->raiseFatalError();
					$taskDone = true;
				}

				$this
					->setLastId($indicator->getProcessOffset())
					->setDroppedCount($subTask, $indicator->getDroppedActivityCount())
					->setFailCount($indicator->getFailCount());

				if ($indicator->hasErrors())
				{
					$error = $indicator->getLastError();
					if ($error instanceof Main\Error)
					{
						$this->setLastError($error->getMessage());
					}
				}

				break;
			}

			default:
			{
				$taskDone = true;
			}
		}

		if ($taskDone)
		{
			// finish subtask
			$this->setStatusSubTask($subTask, self::TASK_STATUS_DONE);

			if ($this->hasTaskFinished(''))
			{
				$this->setStatus(self::TASK_STATUS_DONE);
			}
		}

		// Fix task state
		$this->fixState($subTask);

		return $taskDone;
	}


	/**
	 * Adds delayed delete worker agent.
	 * @param array $params Named parameters:
	 * 		int ownerId - who is owner,
	 * 		int taskId - as row private id from b_crm_volume as task id,
	 * 		int delay - number seconds to delay first execution
	 * 		string filter - filter
	 * 		bool DROP_ENTITY  - set job to drop entity
	 * 		bool DROP_FILE  - set job to drop file
	 * 		bool DROP_EVENT  - set job to drop event
	 * 		bool DROP_ACTIVITY  - set job to drop activity.
	 * @param  Volume\IVolumeIndicator $indicator Indicator to setup agent.
	 * @return bool
	 */
	public static function addWorker(array $params, Volume\IVolumeIndicator $indicator): bool
	{
		$agentAdded = false;

		$ownerId = (int)$params['ownerId'];

		$taskParams = array(
			'OWNER_ID' => $ownerId,
			'AGENT_LOCK' => self::TASK_STATUS_WAIT,
			'INDICATOR_TYPE' => $indicator::getIndicatorId(),
		);
		$taskCheck = array(
			'=OWNER_ID' => $ownerId,
			'=AGENT_LOCK' => array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING),
			'=INDICATOR_TYPE' => $indicator::getIndicatorId(),
		);

		if (count($indicator->getFilter()) > 0)
		{
			$taskParams['FILTER'] = serialize($indicator->getFilter());
		}

		if ($indicator->loadTotals())
		{
			$taskParams['ENTITY_SIZE'] = $indicator->getEntitySize();
			$taskParams['ENTITY_COUNT'] = $indicator->getEntityCount();

			$taskParams['FILE_SIZE'] = $indicator->getFileSize();
			$taskParams['FILE_COUNT'] = $indicator->getFileCount();

			$taskParams['DISK_SIZE'] = $indicator->getDiskSize();
			$taskParams['DISK_COUNT'] = $indicator->getDiskCount();

			$taskParams['ACTIVITY_SIZE'] = $indicator->getActivitySize();
			$taskParams['ACTIVITY_COUNT'] = $indicator->getActivityCount();

			$taskParams['EVENT_SIZE'] = $indicator->getEventSize();
			$taskParams['EVENT_COUNT'] = $indicator->getEventCount();
		}

		if ($params[self::DROP_ENTITY] === true)
		{
			if ($indicator instanceof Volume\IVolumeClear)
			{
				if ($indicator->canClearEntity())
				{
					$taskParams[self::DROP_ENTITY] = self::TASK_STATUS_WAIT;
					$taskCheck['='.self::DROP_ENTITY] = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING);
				}
			}
		}
		if ($params[self::DROP_FILE] === true)
		{
			if ($indicator instanceof Volume\IVolumeClearFile)
			{
				if ($indicator->canClearFile())
				{
					$taskParams[self::DROP_FILE] = self::TASK_STATUS_WAIT;
					$taskCheck['='.self::DROP_FILE] = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING);
				}
			}
		}
		if ($params[self::DROP_EVENT] === true)
		{
			if ($indicator instanceof Volume\IVolumeClearEvent)
			{
				if ($indicator->canClearEvent())
				{
					$taskParams[self::DROP_EVENT] = self::TASK_STATUS_WAIT;
					$taskCheck['='.self::DROP_EVENT] = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING);
				}
			}
		}
		if ($params[self::DROP_ACTIVITY] === true)
		{
			if ($indicator instanceof Volume\IVolumeClearActivity)
			{
				if ($indicator->canClearActivity())
				{
					$taskParams[self::DROP_ACTIVITY] = self::TASK_STATUS_WAIT;
					$taskCheck['='.self::DROP_ACTIVITY] = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING);
				}
			}
		}

		Crm\VolumeTable::deleteBatch(array(
			'=OWNER_ID' => $ownerId,
			'=INDICATOR_TYPE' => $indicator::getIndicatorId(),
			'=AGENT_LOCK' => array(self::TASK_STATUS_CANCEL, self::TASK_STATUS_DONE),
		));

		if (!empty($taskParams['FILTER']))
		{
			$taskCheck['=FILTER'] = $taskParams['FILTER'];
		}

		$taskId = -1;

		$res = Crm\VolumeTable::getList(array('filter' => $taskCheck, 'select' => array('ID')));
		if ($row = $res->fetch())
		{
			$taskId = $row['ID'];
		}
		else
		{
			$addRes = Crm\VolumeTable::add($taskParams);
			if($addRes instanceof ORM\Data\AddResult)
			{
				$taskId = $addRes->getId();
			}
		}

		if ($taskId > 0)
		{
			$nextExecutionTime = '';
			if (!empty($params['delay']) && (int)$params['delay'] > 0)
			{
				$now = new Main\Type\DateTime();
				$now->add($params['delay'].' SECOND');
				$nextExecutionTime = $now->format(Main\Type\DateTime::getFormat());
			}

			$agents = \CAgent::getList(
				['ID' => 'DESC'],
				['=NAME' => static::agentName()]
			);
			if ($agents->fetch())
			{
				$agentAdded = true;
			}
			else
			{
				$agentAdded = (bool)(\CAgent::addAgent(
						static::agentName(),
						'crm',
						(self::canAgentUseCrontab() ? 'N' : 'Y'),
						self::AGENT_INTERVAL,
						'',
						'Y',
						$nextExecutionTime
					) !== false);
			}
		}

		// count statistic for progress bar
		self::countWorker($ownerId);

		return $agentAdded;
	}

	/**
	 * Returns agent's name.
	 *
	 * @return string
	 */
	public static function agentName(): string
	{
		return static::className(). '::runProcess();';
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
	 * Checks ability agent to use Crontab.
	 * @return bool
	 */
	public static function canAgentUseCrontab()
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
	 * Determines if a script is loaded via cron/command line.
	 * @return bool
	 */
	public static function isCronRun(): bool
	{
		$isCronRun = false;
		if (
			!Main\ModuleManager::isModuleInstalled('bitrix24') &&
			(php_sapi_name() === 'cli')
		)
		{
			$isCronRun = true;
		}

		return $isCronRun;
	}


	/**
	 * Saves task state.
	 * @param string $subTask Subtask code.
	 * @return boolean
	 */
	public function fixState($subTask = ''): bool
	{
		if ($this->getId() > 0)
		{
			$taskParams = [];

			// status changed
			if ($this->getStatus() != (int)$this->getParam('AGENT_LOCK'))
			{
				$taskParams['AGENT_LOCK'] = $this->getStatus();
			}

			if (($subTask == '' || $subTask == self::DROP_ENTITY) && $this->getIndicator())
			{
				$taskParams[self::DROP_ENTITY] = $this->getStatusSubTask(self::DROP_ENTITY);
				$taskParams['DROPPED_ENTITY_COUNT'] = $this->getIndicator()->getDroppedEntityCount();
			}
			if (($subTask == '' || $subTask == self::DROP_FILE) && $this->getIndicator() instanceof Volume\IVolumeClearFile)
			{
				$taskParams[self::DROP_FILE] = $this->getStatusSubTask(self::DROP_FILE);
				$taskParams['DROPPED_FILE_COUNT'] = $this->getIndicator()->getDroppedFileCount();
			}
			if (($subTask == '' || $subTask == self::DROP_EVENT) && $this->getIndicator() instanceof Volume\IVolumeClearEvent)
			{
				$taskParams[self::DROP_EVENT] = $this->getStatusSubTask(self::DROP_EVENT);
				$taskParams['DROPPED_EVENT_COUNT'] = $this->getIndicator()->getDroppedEventCount();
			}
			if (($subTask == '' || $subTask == self::DROP_ACTIVITY) && $this->getIndicator() instanceof Volume\IVolumeClearActivity)
			{
				$taskParams[self::DROP_ACTIVITY] = $this->getStatusSubTask(self::DROP_ACTIVITY);
				$taskParams['DROPPED_ACTIVITY_COUNT'] = $this->getIndicator()->getDroppedActivityCount();
			}
			if ($this->getIndicator() && ($this->getIndicator()->getProcessOffset() > 0))
			{
				$taskParams['LAST_ID'] = $this->getIndicator()->getProcessOffset();
			}
			if ($this->getFailCount() > 0)
			{
				$taskParams['FAIL_COUNT'] = $this->getFailCount();
			}
			if ($this->getLastError() != '')
			{
				$taskParams['LAST_ERROR'] = $this->getLastError();
			}

			$result = Crm\VolumeTable::update($this->getId(), $taskParams);

			return $result->isSuccess();
		}

		$result = Crm\VolumeTable::add(array(
			'INDICATOR_TYPE' => $this->getIndicatorType(),
			'OWNER_ID' => $this->getOwnerId(),
			'AGENT_LOCK' => $this->getStatus(),
			self::DROP_ENTITY => $this->getStatusSubTask(self::DROP_ENTITY),
			self::DROP_FILE => $this->getStatusSubTask(self::DROP_FILE),
			self::DROP_EVENT => $this->getStatusSubTask(self::DROP_EVENT),
			self::DROP_ACTIVITY => $this->getStatusSubTask(self::DROP_ACTIVITY),
			'LAST_ID' => ($this->getLastId() > 0 ? $this->getLastId() : null),
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
	 *
	 * @param int $taskId Id of saved indicator result from b_crm_volume.
	 * @param int $ownerId Task owner id.
	 *
	 * @return boolean
	 */
	public function loadTaskById($taskId, $ownerId = 0): bool
	{
		$filter = array(
			'=ID' => $taskId,
		);
		if ($ownerId != 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerResult = Crm\VolumeTable::getList(array(
			'filter' => $filter,
			'limit' => 1,
		));
		if ($row = $workerResult->fetch())
		{
			$this->param = $row;
			$this->id = (int)$this->param['ID'];
			$this
				->setLastId((int)$this->param['LAST_ID'])
				->setOwnerId((int)$this->param['OWNER_ID'])
				->setStatus((int)$this->param['AGENT_LOCK'])
				->setIndicatorType($this->param['INDICATOR_TYPE'])
				->setFailCount((int)$this->param['FAIL_COUNT']);

			$this->droppedEntityCount = (int)$this->param['DROPPED_ENTITY_COUNT'];
			$this->droppedFileCount = (int)$this->param['DROPPED_FILE_COUNT'];
			$this->droppedEventCount = (int)$this->param['DROPPED_EVENT_COUNT'];
			$this->droppedActivityCount = (int)$this->param['DROPPED_ACTIVITY_COUNT'];

			return true;
		}

		return false;
	}


	/**
	 * Checks if status is in running mode.
	 * @param int $status Status to check.
	 * @param int[] $runningStatus This statuses are in running mode.
	 * @return boolean
	 */
	public static function isRunningMode($status, $runningStatus = array(self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING)): bool
	{
		return in_array((int)$status, $runningStatus);
	}

	/**
	 * Check user cancel task.
	 * @return boolean
	 */
	public function hasUserCanceled(): bool
	{
		if ($this->getId() > 0 && self::isRunningMode($this->getStatus()))
		{
			$param = Crm\VolumeTable::getByPrimary($this->getId(), array('select' => array('AGENT_LOCK')))->fetch();
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
	 * @return Volume\IVolumeIndicator|null
	 * @throws Main\ArgumentNullException
	 */
	public function getIndicator()
	{
		if (!$this->indicator instanceof Volume\IVolumeIndicator)
		{
			try
			{
				/** @var Volume\IVolumeIndicator $indicatorType */
				$indicatorType = $this->getIndicatorType();

				/** @var Volume\IVolumeClear $this->indicator */
				$this->indicator = Volume\Base::getIndicator($indicatorType);

				if ($this->indicator instanceof Volume\IVolumeClear)
				{
					$this->indicator->setDroppedEntityCount($this->getParam('DROPPED_ENTITY_COUNT'));
					$this->indicator->setFailCount($this->getParam('FAIL_COUNT'));
				}
				if ($this->indicator instanceof Volume\IVolumeClearFile)
				{
					$this->indicator->setDroppedFileCount($this->getParam('DROPPED_FILE_COUNT'));
				}
				if ($this->indicator instanceof Volume\IVolumeClearEvent)
				{
					$this->indicator->setDroppedEventCount($this->getParam('DROPPED_EVENT_COUNT'));
				}
				if ($this->indicator instanceof Volume\IVolumeClearActivity)
				{
					$this->indicator->setDroppedActivityCount($this->getParam('DROPPED_ACTIVITY_COUNT'));
				}

				if (!empty($this->param['FILTER']))
				{
					$filter = $this->unserializeFilter($this->param['FILTER']);
					if (!is_array($filter))
					{
						$this->setLastError('Filter error')->raiseFatalError();
						return null;
					}

					$this->indicator->setFilter($filter);
				}
			}
			catch (Main\ObjectException $ex)
			{
				$this->setLastError($ex->getMessage())->raiseFatalError();
				return null;
			}
		}

		return $this->indicator;
	}

	/**
	 * Returns indicator class name.
	 * @return string
	 */
	public function getIndicatorType(): string
	{
		return $this->indicatorType;
	}

	/**
	 * Sets indicator type.
	 * @param string $indicatorType Indicator class name.
	 * @return self
	 */
	public function setIndicatorType($indicatorType): self
	{
		$this->indicatorType = $indicatorType;
		return $this;
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
	 * @return self
	 */
	public function setOwnerId($ownerId): self
	{
		$this->ownerId = $ownerId;
		return $this;
	}

	/**
	 * Gets last file id.
	 * @return int
	 */
	public function getLastId()
	{
		return $this->lastId;
	}

	/**
	 * Sets last proceeded id.
	 * @param int $lastId Last proceeded id.
	 * @return self
	 */
	public function setLastId($lastId): self
	{
		$this->lastId = $lastId;
		return $this;
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
	 * @return self
	 */
	public function setStatus($status): self
	{
		$this->status = $status;
		return $this;
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
	 * @return self
	 */
	public function setStatusSubTask($subTask, $status): self
	{
		if ($subTask != '')
		{
			$this->param[$subTask] = $status;
		}
		return $this;
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
	 * @return self
	 */
	public function setParam($code, $value): self
	{
		$this->param[$code] = $value;
		return $this;
	}


	/**
	 * Gets count loaded by filter for iteration.
	 * @return int
	 */
	public function getIterationCount()
	{
		return $this->iterationCount;
	}

	/**
	 * Sets count loaded by filter for iteration.
	 * @param int $iterationCount Count rows in result set.
	 * @return self
	 */
	public function setIterationCount($iterationCount): self
	{
		$this->iterationCount = $iterationCount;
		return $this;
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
	 * Sets last error text occurred in iteration.
	 * @param string $errorText Error text to save.
	 * @return self
	 */
	public function setLastError($errorText): self
	{
		$this->lastError = $errorText;
		return $this;
	}

	/**
	 * Sets dropped smt count.
	 * @param string $subTask Sub task to check.
	 * @param int $count Amount to set.
	 * @return self
	 */
	public function setDroppedCount($subTask, $count): self
	{
		if ($subTask == self::DROP_ENTITY)
		{
			$this->droppedEntityCount = $count;
		}
		if ($subTask == self::DROP_FILE)
		{
			$this->droppedFileCount = $count;
		}
		if ($subTask == self::DROP_EVENT)
		{
			$this->droppedEventCount = $count;
		}
		if ($subTask == self::DROP_ACTIVITY)
		{
			$this->droppedActivityCount = $count;
		}
		return $this;
	}

	/**
	 * Gets dropped smt count.
	 * @param string $subTask Sub task to check.
	 * @return int
	 */
	public function getDroppedCount($subTask): int
	{
		$cnt = 0;
		if ($subTask == '' || $subTask == self::DROP_ENTITY)
		{
			$cnt += $this->droppedEntityCount;
		}
		if ($subTask == '' || $subTask == self::DROP_FILE)
		{
			$cnt += $this->droppedFileCount;
		}
		if ($subTask == '' || $subTask == self::DROP_EVENT)
		{
			$cnt += $this->droppedEventCount;
		}
		if ($subTask == '' || $subTask == self::DROP_ACTIVITY)
		{
			$cnt += $this->droppedActivityCount;
		}

		return $cnt;
	}


	/**
	 * Set fatal error.
	 * @return self
	 */
	public function raiseFatalError(): self
	{
		$this->fatalError = true;
		return $this;
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
	 * Gets fail count.
	 * @return int
	 */
	public function getFailCount(): int
	{
		return $this->failCount;
	}

	/**
	 * Sets fail count.
	 * @param int $count Amount to set.
	 * @return self
	 */
	public function setFailCount($count): self
	{
		$this->failCount = $count;
		return $this;
	}

	/**
	 * Gets count to drop.
	 * @param string $subTask Sub task to check.
	 * @return int
	 */
	public function getCountToDrop($subTask): int
	{
		$cnt = 0;

		if ($subTask == '' || $subTask == self::DROP_ENTITY)
		{
			$cnt += (int)$this->getParam('ENTITY_COUNT');
		}
		if ($subTask == '' || $subTask == self::DROP_FILE)
		{
			$cnt += (int)$this->getParam('FILE_COUNT');
		}
		if ($subTask == '' || $subTask == self::DROP_EVENT)
		{
			$cnt += (int)$this->getParam('EVENT_COUNT');
		}
		if ($subTask == '' || $subTask == self::DROP_ACTIVITY)
		{
			$cnt += (int)$this->getParam('ACTIVITY_COUNT');
		}

		return $cnt;
	}


	/**
	 * Checks if all have deleted.
	 * @param string $subTask Sub task to check.
	 * @return boolean
	 */
	public function hasTaskFinished($subTask): bool
	{
		$subTaskDone = true;
		if(
			$this->hasUserCanceled() === false &&
			$this->hasFatalError() === false &&
			$this->getCountToDrop($subTask) > 0 &&
			$this->getIterationCount() > 0
		)
		{
			if ($subTask == '')
			{
				return (
					self::isRunningMode($this->getStatusSubTask(self::DROP_ENTITY)) !== true &&
					self::isRunningMode($this->getStatusSubTask(self::DROP_FILE)) !== true &&
					self::isRunningMode($this->getStatusSubTask(self::DROP_EVENT)) !== true &&
					self::isRunningMode($this->getStatusSubTask(self::DROP_ACTIVITY)) !== true
				);
			}
			if (self::isRunningMode($this->getStatusSubTask($subTask)) !== true)
			{
				$subTaskDone = false;
			}
			elseif ($this->getDroppedCount($subTask) + $this->getFailCount() < $this->getCountToDrop($subTask))
			{
				$subTaskDone = false;
			}
		}

		return $subTaskDone;
	}


	/**
	 * Cancels all agent process.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return void
	 */
	public static function cancelWorker(int $ownerId = -1): void
	{
		$filter = [
			'=AGENT_LOCK' => [self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING],
		];
		if ($ownerId > 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}
		$workerResult = Crm\VolumeTable::getList([
			'select' => ['ID'],
			'filter' => $filter
		]);
		foreach ($workerResult as $row)
		{
			Crm\VolumeTable::update($row['ID'], ['AGENT_LOCK' => self::TASK_STATUS_CANCEL]);
		}

		self::clearProgressInfo($ownerId);
	}

	/**
	 * Count worker agent for user.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return int
	 */
	public static function countWorker(int $ownerId = -1): int
	{
		$filter = [
			'=AGENT_LOCK' => [self::TASK_STATUS_WAIT, self::TASK_STATUS_RUNNING]
		];
		if ($ownerId > 0)
		{
			$filter['=OWNER_ID'] = $ownerId;
		}

		$workerResult = Crm\VolumeTable::getList([
			'select' => [
				'OWNER_ID',
				new Entity\ExpressionField('CNT', 'COUNT(*)'),
				new Entity\ExpressionField('FAIL_COUNT', 'SUM(FAIL_COUNT)'),
				new Entity\ExpressionField('ENTITY_COUNT', 'SUM(ENTITY_COUNT)'),
				new Entity\ExpressionField('FILE_COUNT', 'SUM(FILE_COUNT)'),
				new Entity\ExpressionField('EVENT_COUNT', 'SUM(EVENT_COUNT)'),
				new Entity\ExpressionField('ACTIVITY_COUNT', 'SUM(ACTIVITY_COUNT)'),
				new Entity\ExpressionField('DROPPED_ENTITY_COUNT', 'SUM(DROPPED_ENTITY_COUNT)'),
				new Entity\ExpressionField('DROPPED_FILE_COUNT', 'SUM(DROPPED_FILE_COUNT)'),
				new Entity\ExpressionField('DROPPED_EVENT_COUNT', 'SUM(DROPPED_EVENT_COUNT)'),
				new Entity\ExpressionField('DROPPED_ACTIVITY_COUNT', 'SUM(DROPPED_ACTIVITY_COUNT)'),
				self::DROP_ENTITY,
				self::DROP_FILE,
				self::DROP_EVENT,
				self::DROP_ACTIVITY,
			],
			'group' => [
				'OWNER_ID',
				self::DROP_ENTITY,
				self::DROP_FILE,
				self::DROP_EVENT,
				self::DROP_ACTIVITY,
			],
			'filter' => $filter,
		]);

		$totalToDrop = 0;
		$droppedCount = 0;
		$workerCount = 0;
		$failCount = 0;

		if ($workerResult->getSelectedRowsCount() > 0)
		{
			foreach ($workerResult as $row)
			{
				$workerCount += $row['CNT'];
				$failCount += $row['FAIL_COUNT'];
				$droppedCount += $row['DROPPED_ENTITY_COUNT'];
				$droppedCount += $row['DROPPED_FILE_COUNT'];
				$droppedCount += $row['DROPPED_EVENT_COUNT'];
				$droppedCount += $row['DROPPED_ACTIVITY_COUNT'];

				if (self::isRunningMode($row[self::DROP_ENTITY]))
				{
					$totalToDrop += $row['ENTITY_COUNT'];
				}
				if (self::isRunningMode($row[self::DROP_FILE]))
				{
					$totalToDrop += $row['FILE_COUNT'];
				}
				if (self::isRunningMode($row[self::DROP_EVENT]))
				{
					$totalToDrop += $row['EVENT_COUNT'];
				}
				if (self::isRunningMode($row[self::DROP_ACTIVITY]))
				{
					$totalToDrop += $row['ACTIVITY_COUNT'];
				}
			}
			self::setProgressInfo((int)$row['OWNER_ID'], (int)$totalToDrop, (int)$droppedCount, (int)$failCount);
		}
		else
		{
			self::clearProgressInfo($ownerId);
		}

		return $workerCount;
	}


	/**
	 * Set up information showing at stepper progress bar.
	 * @param int $ownerId Whom will mark as deleted by.
	 * @return array|null
	 */
	public static function getProgressInfo($ownerId)
	{
		$optionSerialized = Main\Config\Option::get(
			'main.stepper.crm',
			self::class. $ownerId,
			''
		);
		if (!empty($optionSerialized))
		{
			return \unserialize($optionSerialized, ['allowed_classes' => false]);
		}

		return null;
	}

	/**
	 * Set up information showing at stepper progress bar.
	 *
	 * @param int $ownerId Whom will mark as deleted by.
	 * @param int $totalToDrop Total smt to drop.
	 * @param int $droppedCount Dropped smt count.
	 * @param int $failCount Failed deletion count.
	 *
	 * @return void
	 */
	public static function setProgressInfo($ownerId, $totalToDrop, $droppedCount = 0, $failCount = 0): void
	{
		if ($totalToDrop  > 0)
		{
			$option = self::getProgressInfo($ownerId);
			if (!empty($option) && $option['count'] > 0)
			{
				$prevTotalToDrop = $option['count'];

				// If total count decreases mean some agents finished its work.
				if ($prevTotalToDrop > $totalToDrop)
				{
					$droppedCount = ($prevTotalToDrop - $totalToDrop) + $droppedCount;
					$totalToDrop = $prevTotalToDrop;
				}
			}

			Main\Config\Option::set(
				'main.stepper.crm',
				self::class. $ownerId,
				serialize(array('steps' => ($droppedCount + $failCount), 'count' => $totalToDrop))
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
			Main\Config\Option::delete('main.stepper.crm', ['name' => self::class . $ownerId]);
		}
		else
		{
			$optionList = Main\Config\Option::getForModule('main.stepper.crm');
			foreach ($optionList as $name => $value)
			{
				Main\Config\Option::delete('main.stepper.crm', ['name' => $name]);
			}
		}
	}

	/**
	 * Workaround about bug of protected field's null bite \0
	 * @return array|null
	 */
	private function unserializeFilter(string $source): ?array
	{
		$options = [
			'allowed_classes' => [
				Main\Type\DateTime::class,
				\DateTime::class
			]
		];
		$filter = \unserialize($source, $options);
		if ($filter !== false && is_array($filter))
		{
			return $filter;
		}

		// do some workaround
		$reflect = new \ReflectionClass(Main\Type\DateTime::class);
		$props = $reflect->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);
		foreach ($props as $prop)
		{
			$name = $prop->getName();
			$len = strlen($prop->getName()) + 3;
			$source = str_replace("s:".$len.":\"*".$name."\"", "s:".$len.":\"\0*\0".$name."\"", $source);
		}

		$filter = \unserialize($source, $options);
		if ($filter === false || !is_array($filter))
		{
			return null;
		}

		return $filter;
	}
}
