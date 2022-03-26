<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateList;
use Bitrix\Crm\Integrity\DuplicateManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

abstract class IndexRebuild extends Base
{
	public const ERR_ON_START_MERGE_AGENT_ACTIVE = 1010;  // Start is not possible, because merge agent is active.

	protected function getMessage($messageId): ?string
	{
		static $isMessagesLoaded = false;

		if (!$isMessagesLoaded)
		{
			Loc::loadMessages(__FILE__);
			$isMessagesLoaded = true;
		}

		$message = Loc::getMessage($messageId);

		if ($message === null)
		{
			return parent::getMessage($messageId);
		}

		return $message;
	}

	protected function getErrorByCode(int $errorCode): Error
	{
		$errorMessage = '';

		switch ($errorCode)
		{
			case static::ERR_ALREADY_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_IDX_ERR_ALREADY_RUNNING');
				break;
			case static::ERR_NOT_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_IDX_ERR_NOT_RUNNING');
				break;
			case static::ERR_NOT_STOPPED:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_IDX_ERR_NOT_STOPPED');
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
		return 'CRM_AGNT_DUP_BGRND_IDX_NOTIFY';
	}

	protected function getMergeAgentClassName(): string
	{
		return Helper::getInstance()->getAgentClassName(
			CCrmOwnerType::ResolveName($this->getEntityTypeId()),
			'Merge'
		);
	}

	protected function onPendingStart(array $progressData): bool
	{
		/** @var Merge $mergeAgentClassName */
		$mergeAgentClassName = $this->getMergeAgentClassName();
		$mergeAgent = $mergeAgentClassName::getInstance($this->getUserId());

		if ($mergeAgent->isActive())
		{
			return $this->setError($progressData, static::ERR_ON_START_MERGE_AGENT_ACTIVE);
		}

		$mergeAgent->deleteProgressData();

		$progressData['TYPE_INDEX'] = 0;
		$progressData['PROCESSED_ITEMS'] = 0;
		$progressData['FOUND_ITEMS'] = 0;

		$totalItemQty = 0;

		$typeIds = $this->getTypeIds($progressData['TYPES']);
		foreach($typeIds as $typeId)
		{
			$builder = DuplicateManager::createIndexBuilder(
				$typeId,
				$this->getEntityTypeId(),
				$this->getUserId(),
				!Container::getInstance()->getUserPermissions($this->getUserId())->isAdmin(),
				array('SCOPE' => $progressData['SCOPE'])
			);
			$totalItemQty += $builder->getTotalCount();
		}
		$progressData['TOTAL_ITEMS'] = $totalItemQty;

		$timestamp = time();

		$progressData['STATUS'] = static::STATUS_RUNNING;
		$progressData['TIMESTAMP_START'] = $timestamp;
		$progressData['TIMESTAMP_HALF'] = 0;
		$progressData['TIMESTAMP_FINISH'] = 0;
		$progressData['TIMESTAMP'] = $timestamp;
		$this->setProgressData($progressData);

		return true;
	}
	
	protected function onRunning(array $progressData): bool
	{
		if (!$this->checkStepInterval($progressData))
		{
			return false;
		}

		$typeIds = $this->getTypeIds($progressData['TYPES']);
		if(empty($typeIds))
		{
			return $this->setError($progressData, static::ERR_INDEX_TYPES);
		}

		if (!DuplicateIndexType::checkScopeValue($progressData['SCOPE']))
		{
			return $this->setError($progressData, static::ERR_SCOPE);
		}

		$timeToBuild = (int)floor(static::STEP_TTL * static::STEP_INDEX_RATIO);
		$startTime = time();
		$endTime = $startTime;
		$isFinal = false;
		$needNotify = false;
		$notifyPercentage = 0;
		while (!$isFinal && $endTime - $startTime <= $timeToBuild)
		{
			$typeIndex = $progressData['TYPE_INDEX'];
			$countOfTypes = count($typeIds);
			if($typeIndex >= $countOfTypes)
			{
				return $this->setError($progressData, static::ERR_TYPE_INDEX);
			}

			$builder = DuplicateManager::createIndexBuilder(
				$typeIds[$typeIndex],
				$this->getEntityTypeId(),
				$this->getUserId(),
				!Container::getInstance()->getUserPermissions($this->getUserId())->isAdmin(),
				array('SCOPE' => $progressData['SCOPE'])
			);

			$buildData = $progressData['BUILD_DATA'];

			$offset = isset($buildData['OFFSET']) ? (int)$buildData['OFFSET'] : 0;
			if($offset === 0)
			{
				$builder->remove();
			}

			$limit = isset($buildData['LIMIT']) ? (int)$buildData['LIMIT'] : 0;
			if($limit === 0)
			{
				$buildData['LIMIT'] = static::ITEM_LIMIT;
			}

			$isInProgress = $builder->build($buildData);
			if(isset($buildData['PROCESSED_ITEM_COUNT']))
			{
				$progressData['PROCESSED_ITEMS'] += $buildData['PROCESSED_ITEM_COUNT'];
			}

			// Recognize the need for progress notifications by 50% and 90%.
			if ($progressData['TIMESTAMP_START'] > 0 && $progressData['TOTAL_ITEMS'] > 0)
			{
				$percentage = (int)floor(
					static::PERCENT_FULL * $progressData['PROCESSED_ITEMS'] / $progressData['TOTAL_ITEMS']
				);
				if (
					$percentage >= static::PERCENT_HALF
					&& $percentage < static::PERCENT_ALMOST
					&& $progressData['TIMESTAMP_HALF'] === 0
				)
				{
					$progressData['TIMESTAMP_HALF'] = time();
					if ($this->isNeedHalfNotification($progressData))
					{
						$notifyPercentage = static::PERCENT_HALF;
						$needNotify = true;
					}
				}
				else if ($percentage >= static::PERCENT_ALMOST && $progressData['TIMESTAMP_ALMOST'] === 0)
				{
					$progressData['TIMESTAMP_ALMOST'] = time();
					if ($this->isNeedAlmostNotification($progressData))
					{
						$notifyPercentage = static::PERCENT_ALMOST;
						$needNotify = true;
					}
				}
			}

			if(isset($buildData['EFFECTIVE_ITEM_COUNT']))
			{
				$progressData['FOUND_ITEMS'] += $buildData['EFFECTIVE_ITEM_COUNT'];
			}

			$progressData['BUILD_DATA'] = $buildData;

			if(!$isInProgress)
			{
				$isFinal = $typeIndex === ($countOfTypes - 1);
				if(!$isFinal)
				{
					$progressData['TYPE_INDEX'] = ++$typeIndex;
					unset($progressData['BUILD_DATA']);
				}
			}

			if($isFinal)
			{
				$progressData['TOTAL_ENTITIES'] = DuplicateList::getTotalEntityCount(
					$this->getUserId(),
					$this->getEntityTypeId(),
					$typeIds,
					$progressData['SCOPE']
				);
				$progressData['STATUS'] = static::STATUS_FINISHED;
				$progressData['TIMESTAMP_FINISH'] = time();
				$progressData['TIMESTAMP'] = time();

				if ($this->isNeedFullNotification($progressData))
				{
					$notifyPercentage = static::PERCENT_FULL;
					$needNotify = true;
				}

				$this->setAgentResult(false);
			}

			$currentProgressData = $this->getProgressData();
			if (
				isset($currentProgressData['TIMESTAMP'])
				&& isset($currentProgressData['NEXT_STATUS'])
				&& $currentProgressData['TIMESTAMP'] >= $progressData['TIMESTAMP']
				&& $currentProgressData['NEXT_STATUS'] !== static::STATUS_UNDEFINED
				&& $currentProgressData['NEXT_STATUS'] !== $progressData['NEXT_STATUS']
			)
			{
				$progressData['NEXT_STATUS'] = $currentProgressData['NEXT_STATUS'];
			}

			$this->setProgressData($progressData);

			$endTime = time();
		}

		// Progress notification, if needed.
		if ($needNotify)
		{
			$this->notifyPercentage($notifyPercentage);
		}

		return false;
	}

	protected function onPendingStop(array $progressData): bool
	{
		$progressData['STATUS'] = static::STATUS_STOPPED;
		$progressData['TIMESTAMP'] = time();
		$this->setProgressData($progressData);

		$this->setAgentResult(false);

		return false;
	}
}
