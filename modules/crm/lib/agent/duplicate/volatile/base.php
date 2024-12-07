<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Bitrix\Crm\Agent\Duplicate\Volatile;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CAgent;
use ReflectionClass;

abstract class Base
{
	protected const STEP_TTL = 5;
	protected const INTERVAL_FACTOR = 1.2;
	protected const STEP_RATIO = 0.8;
	protected const ITEM_LIMIT = 50;

	/*
		Status transitions
		------------------------------------------
		INACTIVE         -> PENDING_START
		PENDING_START    -> RUNNING, PENDING_STOP, PENDING_START
		RUNNING          -> FINISHED, PENDING_STOP, STOPPED (on error)
		PENDING_STOP     -> STOPPED, PENDING_STOP, PENDING_START, PENDING_CONTINUE
		STOPPED          -> PENDING_CONTINUE, PENDING_START, PENDING_CONTINUE
		PENDING_CONTINUE -> RUNNING, PENDING_STOP, PENDING_CONTINUE
		FINISHED         -> PENDING_START
	*/

	public const STATUS_UNDEFINED = 0;
	public const STATUS_INACTIVE = 10;
	public const STATUS_PENDING_START = 20;
	public const STATUS_RUNNING = 30;
	public const STATUS_PENDING_STOP = 40;
	public const STATUS_STOPPED = 50;
	public const STATUS_PENDING_CONTINUE = 60;
	public const STATUS_FINISHED = 70;

	public const ERR_SUCCESS = 0;
	public const ERR_ALREADY_RUNNING = 10;  // Unable to start agent because it is already active.
	public const ERR_NOT_RUNNING = 20;      // Cannot stop agent because it has not been started.
	public const ERR_NOT_STOPPED = 30;      // Cannot resume agent because it has never been started or stopped.

	/** @var bool */
	protected $agentResult = true;

	public function isActive(): bool
	{
		return $this->getAgentId() > 0;
	}
	public function isReadyToStart(): bool
	{
		$progressData = $this->getProgressData();

		return (
			in_array(
				$progressData['STATUS'],
				[
					static::STATUS_INACTIVE,
					static::STATUS_PENDING_START,
					static::STATUS_PENDING_STOP,
					static::STATUS_STOPPED,
					static::STATUS_FINISHED,
				],
				true
			)
			|| !$this->isActive()
		);
	}
	public function start(array $params): Result
	{
		return $this->tryStart($params);
	}
	public function state(): Result
	{
		$result = new Result();
		$result->setData($this->getProgressData());

		return $result;
	}
	public function stop(): Result
	{
		return $this->tryStop();
	}
	public function continue(): Result
	{
		return $this->tryContinue();
	}
	public function delete(): Result
	{
		$result = new Result();

		$this->deactivate();
		$this->deleteProgressData();

		return $result;
	}

	public function getStatusCode(int $id): string
	{
		$result = '';

		$statusMap = $this->getStatusMap();
		if (isset($statusMap[$id]))
		{
			$result = $statusMap[$id];
		}

		return $result;
	}
	protected function getStatusMap(): array
	{
		static $statusMap = null;

		if ($statusMap === null)
		{
			$statusMap = [];
			$refClass = new ReflectionClass(__CLASS__);
			foreach ($refClass->getConstants() as $name => $value)
			{
				if (mb_substr($name, 0, 7) === 'STATUS_')
				{
					$statusMap[$value] = $name;
				}
			}
		}

		return $statusMap;
	}
	protected function getTypeIds(array $typeNames): array
	{
		$typeIds = [];
		foreach($typeNames as $typeName)
		{
			$typeId = DuplicateIndexType::resolveID($typeName);
			if($typeId !== DuplicateIndexType::UNDEFINED)
			{
				$typeIds[] = $typeId;
			}
		}

		return $typeIds;
	}
	protected function getMessage(string $messageId, ?string $languageId = null): ?string
	{
		static $isMessagesLoaded = false;

		if (!$isMessagesLoaded)
		{
			Loc::loadMessages(__FILE__);
			$isMessagesLoaded = true;
		}

		return Loc::getMessage($messageId, null, $languageId);
	}

	protected function getTimeStamp(): int
	{
		return time();
	}

	protected function doRun(): bool
	{
		$continuePlay = true;
		while ($continuePlay)
		{
			$continuePlay = $this->play();
		}

		return $this->getAgentResult();
	}
	protected function getAgentId()
	{
		$agentId = 0;

		$agentName = $this->getAgentName();

		$res = CAgent::GetList(
			['ID' => 'DESC'],
			[
				'MODULE_ID' => 'crm',
				'=NAME' => $agentName,
				'ACTIVE' => 'Y',
			]
		);
		if (is_object($res))
		{
			$row = $res->Fetch();
			if (is_array($row) && !empty($row))
			{
				$agentId = $row['ID'];
			}
		}
		if (!$agentId)
		{
			$res = CAgent::GetList(
				['ID' => 'DESC'],
				[
					'MODULE_ID' => 'crm',
					'=NAME' => '\\' . $agentName,
					'ACTIVE' => 'Y',
				]
			);
			if (is_object($res))
			{
				$row = $res->Fetch();
				if (is_array($row) && !empty($row))
				{
					$agentId = $row['ID'];
				}
			}
		}

		return $agentId;
	}
	abstract protected function getAgentName(): string;
	protected function getDefaultProgressData(): array
	{
		return [
			'TIMESTAMP' => 0,
			'TIMESTAMP_START' => 0,
			'TIMESTAMP_FINISH' => 0,
			'STATUS' => static::STATUS_INACTIVE,
			'NEXT_STATUS' => static::STATUS_UNDEFINED,
			'ERROR' => static::ERR_SUCCESS,
			'ERROR_INFO' => [
				//'MESSAGE' => 'Error message'
			]
		];
	}
	abstract protected function getProgressData();
	abstract protected function setProgressData(array $data, bool $checkSavedData = true);
	abstract protected function deleteProgressData();
	protected function getErrorByCode(int $errorCode): Error
	{
		$errorMessage = '';

		return new Error($errorMessage, $errorCode);
	}
	protected function setError(array $progressData, int $errorId, array $errorInfo = []): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['ERROR'] = $errorId;
		$progressData['ERROR_INFO'] = $errorInfo;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);

		$this->setAgentResult(false);

		return false;
	}
	protected function setAgentResult(bool $agentResult)
	{
		$this->agentResult = $agentResult;
	}
	protected function getAgentResult(): bool
	{
		return $this->agentResult;
	}

	protected function checkStepInterval(array &$progressData): bool
	{
		// Prevent multiple steps
		$timestamp = $this->getTimeStamp();
		if (isset($progressData['TIMESTAMP']))
		{
			$prevTimeStamp = (int)$progressData['TIMESTAMP'];
			if ($timestamp >= $prevTimeStamp && $timestamp - $prevTimeStamp < static::STEP_TTL)
			{
				// Not enough time has passed since the previous step
				return false;
			}
		}

		$progressData['TIMESTAMP'] = $timestamp;
		$this->setProgressData($progressData);

		// Enough time has passed since the previous step
		return true;
	}
	protected function activate()
	{
		CAgent::AddAgent(
			$this->getAgentName(),
			'crm',
			'Y',
			(int)round(static::STEP_TTL * static::INTERVAL_FACTOR)
		);
	}
	protected function deactivate()
	{
		$agentId = $this->getAgentId();

		if ($agentId > 0)
		{
			CAgent::Delete($agentId);
		}

		return $agentId;
	}
	protected function play(): bool
	{
		$progressData = $this->getProgressData();

		if (
			isset($progressData['STATUS'])
			&& isset($progressData['NEXT_STATUS'])
			&& $progressData['NEXT_STATUS'] !== static::STATUS_UNDEFINED
		)
		{
			$progressData['STATUS'] = $progressData['NEXT_STATUS'];
			$progressData['NEXT_STATUS'] = static::STATUS_UNDEFINED;
		}

		switch ($progressData['STATUS'])
		{
			case static::STATUS_PENDING_START:
				$continuePlay = $this->onPendingStart($progressData);
				break;
			case static::STATUS_RUNNING:
				$continuePlay = $this->onRunning($progressData);
				break;
			case static::STATUS_PENDING_STOP:
				$continuePlay = $this->onPendingStop($progressData);
				break;
			case static::STATUS_PENDING_CONTINUE:
				$continuePlay = $this->onPendingContinue($progressData);
				break;
			default:
				$continuePlay = false;
				$this->setAgentResult(false);
		}

		return $continuePlay;
	}
	protected function tryStart(array $params): Result
	{
		$result = new Result();

		if (!$this->isReadyToStart())
		{
			$result->addError($this->getErrorByCode(static::ERR_ALREADY_RUNNING));

			return $result;
		}

		$progressData = $this->getDefaultProgressData();
		$progressData['INITIAL_PARAMS'] = $params;
		$progressData['NEXT_STATUS'] = static::STATUS_PENDING_START;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);
		$this->activate();

		return $result;
	}
	protected function tryStop(): Result
	{
		$result = new Result();

		$progressData = $this->getProgressData();

		if (
			!(
				isset($progressData['STATUS'])
				&& in_array(
					$progressData['STATUS'],
					[
						static::STATUS_PENDING_START,
						static::STATUS_RUNNING,
						static::STATUS_PENDING_STOP,
						static::STATUS_PENDING_CONTINUE,
					],
					true
				)
			)
		)
		{
			$result->addError($this->getErrorByCode(static::ERR_NOT_RUNNING));

			return $result;
		}

		$progressData['NEXT_STATUS'] = static::STATUS_PENDING_STOP;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);

		return $result;
	}
	protected function tryContinue(): Result
	{
		$result = new Result();

		$progressData = $this->getProgressData();

		if (
			!(
				isset($progressData['STATUS'])
				&& in_array(
					$progressData['STATUS'],
					[
						static::STATUS_PENDING_STOP,
						static::STATUS_STOPPED,
						static::STATUS_PENDING_CONTINUE,
					],
					true
				)
			)
		)
		{
			$result->addError($this->getErrorByCode(static::ERR_NOT_STOPPED));

			return $result;
		}

		$progressData['NEXT_STATUS'] = static::STATUS_PENDING_CONTINUE;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);
		$this->activate();

		return $result;
	}

	protected function onPendingStart(array $progressData): bool
	{
		$timestamp = $this->getTimeStamp();

		$progressData['STATUS'] = static::STATUS_RUNNING;
		$progressData['TIMESTAMP_START'] = $timestamp;
		$progressData['TIMESTAMP_FINISH'] = 0;
		$progressData['TIMESTAMP'] = $timestamp;

		$this->setProgressData($progressData);

		return true;
	}
	abstract protected function onRunning(array $progressData): bool;
	protected function onPendingStop(array $progressData): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);

		$this->setAgentResult(false);

		return false;
	}
	protected function onPendingContinue(array $progressData): bool
	{
		$progressData['ERROR'] = static::ERR_SUCCESS;
		$progressData['ERROR_INFO'] = [];
		$progressData['STATUS'] = static::STATUS_RUNNING;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);

		return true;
	}
}
