<?php

namespace Bitrix\Crm\Agent\Duplicate\Background;

use Bitrix\Crm\Integrity\DuplicateIndexBuilder;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateList;
use Bitrix\Crm\Integrity\DuplicateStatus;
use Bitrix\Crm\Integrity\Entity\DuplicateIndexTable;
use Bitrix\Crm\Merger\ConflictResolutionMode;
use Bitrix\Crm\Merger\EntityMerger;
use Bitrix\Crm\Merger\EntityMergerException;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;
use Exception;

abstract class Merge extends Base
{
	public const ERR_ON_START_INDEX_AGENT_ACTIVE = 2010;
	public const ERR_ON_START_INDEX_AGENT_IS_NOT_FINISHED = 2020;
	public const ERR_ON_START_INDEX_AGENT_NOT_FOUND_ITEMS = 2030;
	public const ERR_MERGE_UNHANDLED_EXCEPTION = 2040;

	protected function getNotifyMessagePrefix(): string
	{
		return 'CRM_AGNT_DUP_BGRND_MRG_NOTIFY';
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

		return $message ?? parent::getMessage($messageId, $languageId);
	}

	protected function getErrorByCode(int $errorCode): Error
	{
		$errorMessage = '';

		switch ($errorCode)
		{
			case static::ERR_ALREADY_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_MERGE_ERR_ALREADY_RUNNING');
				break;
			case static::ERR_NOT_RUNNING:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_MERGE_ERR_NOT_RUNNING');
				break;
			case static::ERR_NOT_STOPPED:
				$errorMessage = $this->getMessage('CRM_AGNT_DUP_BGRND_MERGE_ERR_NOT_STOPPED');
				break;
		}

		if ($errorMessage === '')
		{
			return parent::getErrorByCode($errorCode);
		}

		return new Error($errorMessage, $errorCode);
	}

	protected function getIndexAgentClassName(): string
	{
		return Helper::getInstance()->getAgentClassName(
			CCrmOwnerType::ResolveName($this->getEntityTypeId()),
			'IndexRebuild'
		);
	}

	protected function onPendingStart(array $progressData): bool
	{
		/** @var IndexRebuild $indexAgentClassName */
		$indexAgentClassName = $this->getIndexAgentClassName();
		$indexAgent = $indexAgentClassName::getInstance($this->getUserId());

		if ($indexAgent->isActive())
		{
			return $this->setError($progressData, static::ERR_ON_START_INDEX_AGENT_ACTIVE);
		}

		$indexAgentState = $indexAgent->state()->getData();

		if ($indexAgentState['STATUS'] !== static::STATUS_FINISHED)
		{
			return $this->setError($progressData, static::ERR_ON_START_INDEX_AGENT_IS_NOT_FINISHED);
		}

		if (!isset($indexAgentState['FOUND_ITEMS']) || $indexAgentState['FOUND_ITEMS'] <= 0)
		{
			return $this->setError($progressData, static::ERR_ON_START_INDEX_AGENT_NOT_FOUND_ITEMS);
		}

		$progressData['FOUND_ITEMS'] = $indexAgentState['FOUND_ITEMS'];
		$progressData['MERGED_ITEMS'] = 0;
		$progressData['CONFLICTED_ITEMS'] = 0;
		$progressData['PROCESSED_ITEMS'] = 0;
		$progressData['TOTAL_ITEMS'] = 0;

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

		$entityTypeId = $this->getEntityTypeId();
		$scope = $progressData['SCOPE'];
		$userId = $this->getUserId();
		$enablePermissionCheck = !Container::getInstance()->getUserPermissions($userId)->isAdmin();

		$timeToMerge = (int)floor(static::STEP_TTL * static::STEP_INDEX_RATIO);
		$startTime = time();
		$endTime = $startTime;
		$isFinal = false;
		$needNotify = false;
		$notifyPercentage = 0;
		while (!$isFinal && $endTime - $startTime <= $timeToMerge)
		{

			$list = new DuplicateList(
				DuplicateIndexType::joinType($typeIds),
				$this->getEntityTypeId(),
				$userId,
				$enablePermissionCheck
			);
			$list->setScope($scope);
			$list->setStatusIDs([DuplicateStatus::PENDING]);

			$list->setSortTypeID(
				$entityTypeId === CCrmOwnerType::Company
					? DuplicateIndexType::ORGANIZATION
					: DuplicateIndexType::PERSON
			);
			$list->setSortOrder(SORT_ASC);

			$items = $list->getRootItems(0, 1);
			if(empty($items))
			{
				$isFinal = true;

				$list->setStatusIDs([]);
				$progressData['TOTAL_ITEMS'] = $list->getRootItemCount();
				$progressData['TOTAL_ENTITIES'] = DuplicateList::getTotalEntityCount(
					$userId,
					$entityTypeId,
					$typeIds,
					$scope
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
			else
			{
				$item = $items[0];

				$rootEntityId = $item->getRootEntityID();
				$criterion = $item->getCriterion();
				$entityIds = $criterion->getEntityIDs(
					$entityTypeId,
					$rootEntityId,
					$userId,
					$enablePermissionCheck,
					['limit' => 50]
				);
				$entityIds = array_unique($entityIds);

				if(empty($entityIds))
				{
					//Skip Junk item
					DuplicateIndexTable::deleteByFilter(
						array(
							'USER_ID' => $userId,
							'ENTITY_TYPE_ID' => $entityTypeId,
							'TYPE_ID' => $criterion->getIndexTypeID(),
							'MATCH_HASH' => $criterion->getMatchHash()
						)
					);
				}
				else
				{
					$isEntityMergerException = false;
					$merger = EntityMerger::create($entityTypeId, $userId, $enablePermissionCheck);
					$merger->setConflictResolutionMode(ConflictResolutionMode::ASK_USER);
					try
					{
						$merger->mergeBatch($entityIds, $rootEntityId, $criterion);
					}
					catch(EntityMergerException $e)
					{
						$isEntityMergerException = true;
						if($e->getCode() === EntityMergerException::CONFLICT_OCCURRED)
						{
							DuplicateIndexBuilder::setStatusID(
								$userId,
								$entityTypeId,
								$criterion->getIndexTypeID(),
								$criterion->getMatchHash(),
								$scope,
								DuplicateStatus::CONFLICT
							);
							$progressData['CONFLICTED_ITEMS']++;
						}
						else
						{
							DuplicateIndexBuilder::setStatusID(
								$userId,
								$entityTypeId,
								$criterion->getIndexTypeID(),
								$criterion->getMatchHash(),
								$scope,
								DuplicateStatus::ERROR
							);
						}
					}
					catch(Exception $e)
					{
						return $this->setError(
							$progressData,
							static::ERR_MERGE_UNHANDLED_EXCEPTION,
							['MESSAGE' => $e->getMessage()]
						);
					}

					$progressData['PROCESSED_ITEMS']++;
					// Recognize the need for progress notifications by 50% and 90%.
					if ($progressData['TIMESTAMP_START'] > 0 && $progressData['TOTAL_ITEMS'] > 0)
					{
						$percentage = (int)floor(
							static::PERCENT_FULL * $progressData['PROCESSED_ITEMS'] / $progressData['FOUND_ITEMS']
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

					if (!$isEntityMergerException)
					{
						$progressData['MERGED_ITEMS']++;
					}
				}
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
		$typeIds = $this->getTypeIds($progressData['TYPES']);
		if(empty($typeIds))
		{
			return $this->setError($progressData, static::ERR_INDEX_TYPES);
		}

		if (!DuplicateIndexType::checkScopeValue($progressData['SCOPE']))
		{
			return $this->setError($progressData, static::ERR_SCOPE);
		}

		$entityTypeId = $this->getEntityTypeId();
		$scope = $progressData['SCOPE'];
		$userId = $this->getUserId();
		$enablePermissionCheck = !Container::getInstance()->getUserPermissions($userId)->isAdmin();

		$list = new DuplicateList(
			DuplicateIndexType::joinType($typeIds),
			$this->getEntityTypeId(),
			$userId,
			$enablePermissionCheck
		);
		$list->setScope($scope);
		$list->setStatusIDs([DuplicateStatus::PENDING]);

		$list->setSortTypeID(
			$entityTypeId === CCrmOwnerType::Company
				? DuplicateIndexType::ORGANIZATION
				: DuplicateIndexType::PERSON
		);
		$list->setSortOrder(SORT_ASC);

		$list->setStatusIDs([]);
		$progressData['TOTAL_ITEMS'] = $list->getRootItemCount();
		$progressData['TOTAL_ENTITIES'] = DuplicateList::getTotalEntityCount(
			$userId,
			$entityTypeId,
			$typeIds,
			$scope
		);

		return parent::onPendingStop($progressData);
	}
}
