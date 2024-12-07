<?php
namespace Bitrix\Crm\Agent\Duplicate\Volatile;

use Bitrix\Crm\Agent\Duplicate\Background\Helper;
use Bitrix\Crm\Agent\Notice\Notification;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateIndexTypeSettingsTable;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmCompany;
use CCrmContact;
use CCrmLead;
use CCrmOwnerType;

class IndexRebuild extends Base
{
	protected const NOTIFY_INTERVAL_HALF = 600;		// The minimum time for half-process (sec.) when
	protected const NOTIFY_INTERVAL_FULL = 60;		// Minimum process time (sec.) when 100% notification is needed.
	protected const PERCENT_ZERO = 0;
	protected const PERCENT_HALF = 50;
	protected const PERCENT_ALMOST = 90;
	protected const PERCENT_FULL = 100;

	/** @var int */
	protected $entityTypeId = null;

	/** @var int */
	protected $volatileTypeId = 0;

	protected function __construct(int $volatileTypeId)
	{
		$this->volatileTypeId = $volatileTypeId;
	}

	protected function getVolatileTypeId(): int
	{
		return $this->volatileTypeId;
	}

	protected function getAgentNameFilterPrefix(): string
	{
		return static::class . '::run(';
	}

	protected function getAgentName(): string
	{
		return $this->getAgentNameFilterPrefix() . $this->getVolatileTypeId() . ');';
	}

	public function getFinalActionsInfo(): array
	{
		$result = [];

		$progressData = $this->getProgressData();
		if (is_array($progressData['FINAL_ACTIONS']))
		{
			$result = $progressData['FINAL_ACTIONS'];
		}

		return $result;
	}

	public function setFinalActionsInfo(array $info)
	{
		$progressData = $this->getProgressData();
		$progressData['FINAL_ACTIONS'] = $info;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData, false);
	}

	protected function setError(array $progressData, int $errorId, array $errorInfo = []): bool
	{
		$state = Volatile\Type\State::getInstance();
		$state->set($this->getVolatileTypeId(), Volatile\Type\State::STATE_ERROR);

		return parent::setError($progressData, $errorId, $errorInfo);
	}

	protected function getEntityTypeId(): int
	{
		if ($this->entityTypeId === null)
		{
			$entityTypeId = CCrmOwnerType::Undefined;
			$res = DuplicateIndexTypeSettingsTable::getList(
				[
					'filter' => ['=ID' => $this->getVolatileTypeId()],
					'select' => ['ENTITY_TYPE_ID'],
				]
			);
			if (is_object($res) && $row = $res->fetch())
			{
				$entityTypeId = (int)$row['ENTITY_TYPE_ID'];
			}

			$this->entityTypeId =
				CCrmOwnerType::IsDefined($entityTypeId)
					? $entityTypeId
					: CCrmOwnerType::Undefined;
		}

		return $this->entityTypeId;
	}

	public static function getInstance(int $volatileTypeId)
	{
		static $instanceMap = [];

		$instance = null;

		if (
			DuplicateVolatileCriterion::isSupportedType($volatileTypeId)
			&& !isset($instanceMap[$volatileTypeId])
		)
		{
			$instance = new static($volatileTypeId);
			$instanceMap[$volatileTypeId] = $instance;
		}

		if ($instance === null && isset($instanceMap[$volatileTypeId]))
		{
			$instance = $instanceMap[$volatileTypeId];
		}

		return $instance;
	}

	public static function run(int $volatileTypeId): string
	{
		$instance = static::getInstance($volatileTypeId);

		return ($instance->doRun() ? $instance->getAgentName() : '');
	}

	protected function getProgressData(): array
	{
		$data = DuplicateIndexTypeSettingsTable::getProgressData($this->getVolatileTypeId());
		if (empty($data))
		{
			$data = $this->getDefaultProgressData();
		}

		return $data;
	}

	protected function setProgressData(array $data, bool $checkSavedData = true)
	{
		if ($checkSavedData)
		{
			$progressData = $this->getProgressData();

			if (
				isset($progressData['TIMESTAMP'])
				&& $progressData['TIMESTAMP'] >= $data['TIMESTAMP']
			)
			{
				// Next status
				if (
					isset($progressData['NEXT_STATUS'])
					&& $progressData['NEXT_STATUS'] !== static::STATUS_UNDEFINED
					&& $progressData['NEXT_STATUS'] !== $data['NEXT_STATUS']
				)
				{
					$data['NEXT_STATUS'] = $progressData['NEXT_STATUS'];
				}

				// Final actions
				if (isset($progressData['FINAL_ACTIONS']))
				{
					$data['FINAL_ACTIONS'] = $progressData['FINAL_ACTIONS'];
				}
			}
		}

		DuplicateIndexTypeSettingsTable::setProgressData($this->getVolatileTypeId(), $data);
	}

	protected function deleteProgressData()
	{
		DuplicateIndexTypeSettingsTable::setProgressData($this->getVolatileTypeId(), []);
	}

	protected function getMessage(string $messageId, ?string $languageId = null): ?string
	{
		static $isMessagesLoaded = false;

		if (!$isMessagesLoaded)
		{
			Loc::loadMessages(__FILE__);
			$isMessagesLoaded = true;
		}

		$message = Loc::getMessage($messageId, null, $languageId);

		if ($message === null)
		{
			return parent::getMessage($messageId, $languageId);
		}

		return $message;
	}

	protected function getErrorByCode(int $errorCode): Error
	{
		$errorMessage = '';

		switch ($errorCode)
		{
			case static::ERR_ALREADY_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_VOLATILE_IDX_ERR_ALREADY_RUNNING');
				break;
			case static::ERR_NOT_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_VOLATILE_IDX_ERR_NOT_RUNNING');
				break;
			case static::ERR_NOT_STOPPED:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_VOLATILE_IDX_ERR_NOT_STOPPED');
				break;
		}

		if ($errorMessage === '')
		{
			return parent::getErrorByCode($errorCode);
		}

		return new Error($errorMessage, $errorCode);
	}

	protected function getNotifyMessagePrefix(): string
	{
		return 'CRM_AGNT_DUP_VOLATILE_IDX_NOTIFY';
	}

	protected function getNotifyMessage(int $percentage): string|callable
	{
		$message = '';

		if ($percentage >= static::PERCENT_ZERO && $percentage <= static::PERCENT_FULL)
		{
			$percentageString = sprintf('%03d', $percentage);
			$messagePrefix = $this->getNotifyMessagePrefix();
			$message = $this->getMessageCallback("{$messagePrefix}_$percentageString");
		}

		return $message;
	}

	protected function getMessageCallback(string $code): callable
	{
		return fn (?string $languageId = null) =>
			$this->getMessage($code, $languageId)
		;
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

	protected function notifyPercentage(int $userId, int $percentage): void
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
				->toList([$userId])
				->send();
		}
	}

	protected function getDefaultProgressData(): array
	{
		$result = parent::getDefaultProgressData();

		$result['TIMESTAMP_HALF'] = 0;
		$result['TIMESTAMP_ALMOST'] = 0;

		$result['PROGRESS_VARS'] = [
			'LAST_ITEM_ID' => 0,
			'PROCESSED_ITEMS' => 0,
			'TOTAL_ITEMS' => 0,
			'LIMIT' => 0,
		];

		$result['FINAL_ACTIONS'] = [];

		return $result;
	}

	protected function checkEntityType(): bool
	{
		static $result = null;

		if ($result === null)
		{
			$result = in_array(
				$this->getEntityTypeId(),
				[CCrmOwnerType::Lead, CCrmOwnerType::Company, CCrmOwnerType::Contact]
			);
		}

		return $result;
	}

	protected function getTotalCount(): int
	{
		if (!$this->checkEntityType())
		{
			return 0;
		}

		/** @var  $entityClass CCrmLead|CCrmCompany|CCrmContact */
		$entityClass = '\\CCrm'.ucfirst(strtolower(CCrmOwnerType::ResolveName($this->getEntityTypeId())));

		return (int)$entityClass::GetListEx([], ['CHECK_PERMISSIONS' => 'N'], []);
	}

	protected function prepareItemIds(int $offsetId, int $limit): array
	{
		$filter = ['CHECK_PERMISSIONS' => 'N'];
		if($offsetId > 0)
		{
			$filter['>ID'] = $offsetId;
		}

		/** @var  $entityClass CCrmLead|CCrmCompany|CCrmContact */
		$entityClass = '\\CCrm'.ucfirst(strtolower(CCrmOwnerType::ResolveName($this->getEntityTypeId())));

		$res = $entityClass::GetListEx(['ID' => 'ASC'], $filter, false, ['nTopCount' => $limit], ['ID']);

		$result = [];

		if(is_object($res))
		{
			while($fields = $res->Fetch())
			{
				$result[] = (int)$fields['ID'];
			}
		}

		return $result;
	}

	protected function onPendingStart(array $progressData): bool
	{
		$progressData['PROGRESS_VARS']['LAST_ITEM_ID'] = 0;
		$progressData['PROGRESS_VARS']['PROCESSED_ITEMS'] = 0;
		$progressData['PROGRESS_VARS']['TOTAL_ITEMS'] = $this->getTotalCount();

		$progressData['TIMESTAMP_HALF'] = 0;
		$progressData['TIMESTAMP_ALMOST'] = 0;

		$result = parent::onPendingStart($progressData);

		$state = Volatile\Type\State::getInstance();
		$state->set($this->getVolatileTypeId(), Volatile\Type\State::STATE_INDEX);

		return $result;
	}

	protected function processItems(array $itemIds)
	{
		foreach ($itemIds as $id)
		{
			//region Register volatile duplicate criterion fields
			DuplicateVolatileCriterion::register($this->getEntityTypeId(), $id, [], [$this->getVolatileTypeId()]);
			//endregion Register volatile duplicate criterion fields
		}
	}

	protected function runFinalActions()
	{
		$actionsInfo = $this->getFinalActionsInfo();
		$typeInfo = Volatile\TypeInfo::getInstance();
		$userTypeMap  = is_array($actionsInfo['USER_TYPE_MAP']) ? $actionsInfo['USER_TYPE_MAP'] : [];
		foreach ($userTypeMap as $userId => $info)
		{
			$isVolatileTypesReady = true;
			if (is_array($info['notReadyVolatileTypes']))
			{
				foreach ($info['notReadyVolatileTypes'] as $volatileTypeName)
				{
					$volatileTypeId = DuplicateIndexType::resolveID($volatileTypeName);
					if ($volatileTypeId !== $this->getVolatileTypeId())
					{
						$typeSettings = $typeInfo->getById($volatileTypeId);
						if (
							isset($typeSettings['STATE_ID'])
							&& $typeSettings['STATE_ID'] !== Volatile\Type\State::STATE_READY
						)
						{
							$isVolatileTypesReady = false;
							break;
						}
					}
				}
			}
			if ($isVolatileTypesReady && $userId > 0 && is_array($info['types']))
			{
				$entityTypeName = CCrmOwnerType::ResolveName($this->getEntityTypeId());

				/** @var \Bitrix\Crm\Agent\Duplicate\Background\IndexRebuild $agentClassName */
				$agentClassName = Helper::getInstance()->getAgentClassName($entityTypeName, 'IndexRebuild');
				$agent = $agentClassName::getInstance($userId);
				if ($agent)
				{
					$agent->start($info['types'], $info['scope'] ?? '', ['IS_HALF_PERCENTAGE_MODE' => 'Y']);
				}
			}
		}
	}

	protected function onRunning(array $progressData): bool
	{
		if (!$this->checkStepInterval($progressData))
		{
			return false;
		}

		$timeToBuild = (int)floor(static::STEP_TTL * static::STEP_RATIO);
		$startTime = $this->getTimeStamp();
		$endTime = $startTime;
		$isFinal = false;
		$needNotify = false;
		$notifyPercentage = 0;
		$userId = (int)($progressData['INITIAL_PARAMS']['USER_ID'] ?? 0);

		while (!$isFinal && $endTime - $startTime <= $timeToBuild)
		{
			$progressVars = $progressData['PROGRESS_VARS'];

			$limit = isset($progressVars['LIMIT']) ? (int)$progressVars['LIMIT'] : 0;
			if($limit === 0)
			{
				$limit = static::ITEM_LIMIT;
				$progressVars['LIMIT'] = $limit;
			}

			$progressVars['LAST_ITEM_ID'] = (int)($progressVars['LAST_ITEM_ID'] ?? 0);

			$itemIds = $this->prepareItemIds($progressVars['LAST_ITEM_ID'], $limit);

			$itemCount = count($itemIds);

			if ($itemCount > 0)
			{
				$this->processItems($itemIds);

				$progressVars['LAST_ITEM_ID'] = $itemIds[$itemCount -1];

				$progressVars['PROCESSED_ITEMS'] += $itemCount;
				if($progressVars['TOTAL_ITEMS'] < $progressVars['PROCESSED_ITEMS'])
				{
					$isFinal = true;
				}
			}
			else
			{
				$isFinal = true;
			}

			// Recognize the need for progress notifications by 50% and 90%.
			if ($progressData['TIMESTAMP_START'] > 0 && $progressVars['TOTAL_ITEMS'] > 0)
			{
				$percentage = (int)floor(
					static::PERCENT_FULL * $progressVars['PROCESSED_ITEMS'] / $progressVars['TOTAL_ITEMS']
				);
				if (
					$percentage >= static::PERCENT_HALF
					&& $percentage < static::PERCENT_ALMOST
					&& $progressData['TIMESTAMP_HALF'] === 0
				)
				{
					$progressData['TIMESTAMP_HALF'] = $this->getTimeStamp();
					if ($this->isNeedHalfNotification($progressData))
					{
						$notifyPercentage = static::PERCENT_HALF;
						$needNotify = true;
					}
				}
				else if ($percentage >= static::PERCENT_ALMOST && $progressData['TIMESTAMP_ALMOST'] === 0)
				{
					$progressData['TIMESTAMP_ALMOST'] = $this->getTimeStamp();
					if ($this->isNeedAlmostNotification($progressData))
					{
						$notifyPercentage = static::PERCENT_ALMOST;
						$needNotify = true;
					}
				}
			}

			$progressData['PROGRESS_VARS'] = $progressVars;

			if($isFinal)
			{
				$this->runFinalActions();

				$timestamp = $this->getTimeStamp();
				$progressData['STATUS'] = static::STATUS_FINISHED;
				$progressData['TIMESTAMP_FINISH'] = $timestamp;
				$progressData['TIMESTAMP'] = $timestamp;
				unset($timestamp);

				if ($this->isNeedFullNotification($progressData))
				{
					$notifyPercentage = static::PERCENT_FULL;
					$needNotify = true;
				}

				$this->setAgentResult(false);
			}

			$this->setProgressData($progressData);

			if ($isFinal)
			{
				$state = Volatile\Type\State::getInstance();
				$state->set($this->getVolatileTypeId(), Volatile\Type\State::STATE_READY);
			}

			$endTime = $this->getTimeStamp();
		}

		// Progress notification, if needed.
		$needNotify = false;
		if ($needNotify)
		{
			if ($userId > 0)
			{
				$this->notifyPercentage($userId, $notifyPercentage);
			}
		}

		return false;
	}

	protected function onPendingStop(array $progressData): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['TIMESTAMP'] = $this->getTimeStamp();
		$this->setProgressData($progressData);

		$this->setAgentResult(false);

		return false;
	}
}
