<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use Bitrix\Crm\Agent\Notice\Notification;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use CAgent;
use CCrmOwnerType;
use CUserOptions;
use ReflectionClass;

abstract class Base
{
	protected const STEP_TTL = 5;
	protected const STEP_INDEX_RATIO = 0.8;
	protected const ITEM_LIMIT = 10;
	protected const NOTIFY_INTERVAL_HALF = 600;		// The minimum time for half-process (sec.) when
													// 50% and 90% notifications are needed.
	protected const NOTIFY_INTERVAL_FULL = 60;		// Minimum process time (sec.) when 100% notification is needed.

	protected const PERCENT_ZERO = 0;
	protected const PERCENT_HALF = 50;
	protected const PERCENT_ALMOST = 90;
	protected const PERCENT_FULL = 100;

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
	public const ERR_INDEX_TYPES = 10;      // Index types are not defined or invalid.
	public const ERR_SCOPE = 20;            // Scope is invalid.
	public const ERR_TYPE_INDEX = 30;       // Invalid current type index.
	public const ERR_ALREADY_RUNNING = 40;  // Unable to start agent because it is already active.
	public const ERR_NOT_RUNNING = 50;      // Cannot stop agent because it has not been started.
	public const ERR_NOT_STOPPED = 60;      // Cannot resume agent because it has never been started or stopped.

	/** @var int */
	protected $userId = 0;

	/** @var bool */
	protected $agentResult = true;

	abstract public function getEntityTypeId(): int;

	public static function getInstance(int $userId = 0)
	{
		static $instanceByUserId = [];

		$instance = null;

		$index = static::class . '_' . $userId;

		if ($userId > 0 && !isset($instanceByUserId[$index]))
		{
			$instance = new static($userId);
			$instanceByUserId[$index] = $instance;
		}

		if ($instance === null && isset($instanceByUserId[$index]))
		{
			$instance = $instanceByUserId[$index];
		}

		return $instance;
	}
	public static function run(int $userId): string
	{
		$instance = static::getInstance($userId);

		return ($instance->doRun() ? $instance->getAgentName() : '');
	}

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

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
	public function start(array $types, string $scope, array $initialParams = []): Result
	{
		$filteredTypes = [];
		foreach($types as $typeName)
		{
			$typeId = DuplicateIndexType::resolveID($typeName);
			if($typeId !== DuplicateIndexType::UNDEFINED)
			{
				$filteredTypes[] = $typeName;
			}
		}

		$filteredScope = DuplicateIndexType::checkScopeValue($scope) ? $scope : '';

		return $this->tryStart($filteredTypes, $filteredScope, $initialParams);
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
	protected function getOptionNameFilterPrefix(): string
	{
		return mb_strtoupper(str_replace('\\', '_', static::class)) . '_';
	}
	protected function getAgentNameFilterPrefix(): string
	{
		return static::class . '::run(';
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

	protected function getMessageCallback(string $code): callable
	{
		return fn (?string $languageId = null) =>
			$this->getMessage($code, $languageId)
		;
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
	protected function getUserId(): int
	{
		return $this->userId;
	}
	protected function getOptionName(): string
	{
		return static::getOptionNameFilterPrefix() . $this->getUserId() . '_PROGRESS';
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
	protected function getAgentName(): string
	{
		return $this->getAgentNameFilterPrefix() . $this->getUserId() . ');';
	}
	protected function getDefaultProgressData(): array
	{
		return [
			'CONTEXT_ID' => '',
			'TIMESTAMP' => 0,
			'TIMESTAMP_START' => 0,
			'TIMESTAMP_HALF' => 0,
			'TIMESTAMP_ALMOST' => 0,
			'TIMESTAMP_FINISH' => 0,
			'STATUS' => static::STATUS_INACTIVE,
			'NEXT_STATUS' => static::STATUS_UNDEFINED,
			'ERROR' => static::ERR_SUCCESS,
			'ERROR_INFO' => [
				//'MESSAGE' => 'Error message'
			],
			'INITIAL_PARAMS' => [],
			'TYPES' => [],
			'SCOPE' => '',
			'TYPE_INDEX' => 0,
			'MERGED_ITEMS' => 0,
			'CONFLICTED_ITEMS' => 0,
			'PROCESSED_ITEMS' => 0,
			'FOUND_ITEMS' => 0,
			'TOTAL_ITEMS' => 0,
			'TOTAL_ENTITIES' => 0,
			'BUILD_DATA' => [],
		];
	}
	protected function getProgressData()
	{
		$data = CUserOptions::GetOption('crm', $this->getOptionName(), [], $this->getUserId());
		if(!is_array($data))
		{
			$data = [];
		}

		if (empty($data))
		{
			$data = $this->getDefaultProgressData();
		}

		return $data;
	}
	protected function setProgressData(array $data)
	{
		CUserOptions::SetOption('crm', $this->getOptionName(), $data, false, $this->getUserId());
	}
	protected function deleteProgressData()
	{
		CUserOptions::DeleteOption('crm', $this->getOptionName(), false, $this->getUserId());
	}
	protected function getErrorByCode(int $errorCode): Error
	{
		$errorMessage = '';

		switch ($errorCode)
		{
			case static::ERR_INDEX_TYPES:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_ERR_INDEX_TYPES');
				break;
			case static::ERR_SCOPE:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_ERR_SCOPE');
				break;
			case static::ERR_TYPE_INDEX:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_ERR_TYPE_INDEX');
				break;
		}

		return new Error($errorMessage, $errorCode);
	}
	protected function setError(array $progressData, int $errorId, array $errorInfo = []): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['ERROR'] = $errorId;
		$progressData['ERROR_INFO'] = $errorInfo;
		$progressData['TIMESTAMP'] = time();
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
	protected function isNeedHalfNotification(array $progressData): bool
	{
		return (
			$progressData['TIMESTAMP_HALF'] - $progressData['TIMESTAMP_START']
			>= static::NOTIFY_INTERVAL_HALF
		);
	}
	protected function isNeedAlmostNotification(array $progressData): bool
	{
		return $this->isNeedHalfNotification($progressData);
	}
	protected function isNeedFullNotification(array $progressData): bool
	{
		return (
			$progressData['TIMESTAMP_FINISH'] - $progressData['TIMESTAMP_START']
			>= static::NOTIFY_INTERVAL_FULL
		);
	}
	abstract protected function getNotifyMessagePrefix();
	protected function getNotifyMessage(int $percentage): string|callable
	{
		$message = '';

		if ($percentage >= static::PERCENT_ZERO && $percentage <= static::PERCENT_FULL)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($this->getEntityTypeId());
			$percentageString = sprintf('%03d', $percentage);
			$messagePrefix = $this->getNotifyMessagePrefix();

			$message = $this->getMessageCallback("{$messagePrefix}_{$entityTypeName}_{$percentageString}");
		}

		return $message;
	}
	protected function notifyPercentage(int $percentage): void
	{
		$message = '';

		if ($percentage >= static::PERCENT_HALF && $percentage < static::PERCENT_ALMOST)
		{
			$message = $this->getNotifyMessage(static::PERCENT_HALF);
		}
		else if ($percentage >= static::PERCENT_ALMOST && $percentage < static::PERCENT_FULL)
		{
			$message = $this->getNotifyMessage(static::PERCENT_ALMOST);
		}
		else if ($percentage === static::PERCENT_FULL)
		{
			$message = $this->getNotifyMessage(static::PERCENT_FULL);
		}

		if ($message !== '')
		{
			Notification::create()
				->withMessage($message)
				->toList([$this->getUserId()])
				->send();
		}
	}

	protected function checkStepInterval(array &$progressData): bool
	{
		// Prevent multiple steps
		$timestamp = time();
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
	protected function activate(/*int $delay = 0*/)
	{
		/*if($delay < 0)
		{
			$delay = 0;
		}*/

		CAgent::AddAgent(
			$this->getAgentName(),
			'crm',
			'Y',
			(int)round(static::STEP_TTL * 1.2),
			'',
			'Y'/*,
			ConvertTimeStamp(time() + CTimeZone::GetOffset($this->getUserId()) + $delay, 'FULL')*/
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
	protected function tryStart(array $types, string $scope, array $initialParams): Result
	{
		$result = new Result();

		if (!$this->isReadyToStart())
		{
			$result->addError($this->getErrorByCode(static::ERR_ALREADY_RUNNING));

			return $result;
		}

		$progressData = $this->getDefaultProgressData();
		$progressData['INITIAL_PARAMS'] = $initialParams;
		$progressData['TYPES'] = $types;
		$progressData['SCOPE'] = $scope;
		$progressData['NEXT_STATUS'] = static::STATUS_PENDING_START;
		$progressData['CONTEXT_ID'] = Random::getStringByCharsets(8, 'abcdefghijklmnopqrstuvwxyz');
		$progressData['TIMESTAMP'] = time();
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
		$progressData['TIMESTAMP'] = time();
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
		$progressData['TIMESTAMP'] = time();
		$this->setProgressData($progressData);
		$this->activate();

		return $result;
	}

	protected function getMergeAgentClassName(): string
	{
		$result = '';

		$entityTypeName = CCrmOwnerType::ResolveName($this->getEntityTypeId());
		if (in_array($entityTypeName, ['LEAD', 'COMPANY', 'CONTACT'], true))
		{
			$result =
				'Bitrix\\Crm\\Agent\\Duplicate\\Background\\'
				. ucfirst(strtolower($entityTypeName))
				. 'Merge'
			;
		}

		return $result;
	}

	abstract protected function onPendingStart(array $progressData): bool;
	abstract protected function onRunning(array $progressData): bool;
	protected function onPendingStop(array $progressData): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['TIMESTAMP'] = time();
		$this->setProgressData($progressData);

		$this->setAgentResult(false);

		return false;
	}
	protected function onPendingContinue(array $progressData): bool
	{
		$progressData['ERROR'] = static::ERR_SUCCESS;
		$progressData['ERROR_INFO'] = [];
		$progressData['STATUS'] = static::STATUS_RUNNING;
		$progressData['TIMESTAMP'] = time();
		$this->setProgressData($progressData);

		return true;
	}
}
