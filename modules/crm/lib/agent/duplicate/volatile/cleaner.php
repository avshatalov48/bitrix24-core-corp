<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Bitrix\Crm\Agent\Duplicate\Volatile;

use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\DuplicateVolatileMatchCodeTable;
use Bitrix\Crm\Integrity\Entity\DuplicateEntityMatchHashTable;
use Bitrix\Crm\Integrity\Volatile\TypeInfo;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

class Cleaner extends Base
{
	protected const STEP_TTL = 60;
	protected const STEP_RATIO = 0.05;

	protected function __construct()
	{
	}

	protected function getAgentNameFilterPrefix(): string
	{
		return static::class . '::run(';
	}

	protected function getAgentName(): string
	{
		return $this->getAgentNameFilterPrefix() . ');';
	}

	protected function setError(array $progressData, int $errorId, array $errorInfo = []): bool
	{
		return parent::setError($progressData, $errorId, $errorInfo);
	}

	public static function getInstance(): Cleaner
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	public static function run(): string
	{
		$instance = static::getInstance();

		return ($instance->doRun() ? $instance->getAgentName() : '');
	}

	protected function getOptionName(): string
	{
		return '~' . mb_strtoupper(str_replace('\\', '_', static::class)) . '_' . 'PROGRESS';
	}

	protected function getProgressData(): array
	{
		$value = Option::get('crm', $this->getOptionName());
		$data = $value !== '' ? unserialize($value, ['allowed_classes' => false]) : null;
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

	protected function setProgressData(array $data, bool $checkSavedData = true)
	{
		Option::set('crm', $this->getOptionName(), serialize($data));
	}

	protected function deleteProgressData()
	{
		Option::delete('crm', ['name' => $this->getOptionName()]);
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

	protected function getDefaultProgressData(): array
	{
		$result = parent::getDefaultProgressData();

		$result['PROGRESS_VARS'] = ['ACTIVE' => 'N'];

		return $result;
	}

	protected function onPendingStart(array $progressData): bool
	{
		$progressData['PROGRESS_VARS']['ACTIVE'] = 'Y';

		return parent::onPendingStart($progressData);
	}

	protected function processStep(): bool
	{
		$result = false;

		// Prefpare filter
		$entityTypeIds = DuplicateVolatileCriterion::getSupportedEntityTypes();
		$volatileTypeIds = DuplicateVolatileCriterion::getAllSupportedDedupeTypes();
		$activeTypeIds = [];
		$activeEntityTypeIds = [];
		foreach (TypeInfo::getInstance()->get() as $typeInfo)
		{
			if ($typeInfo['ACTIVE'] === 'Y')
			{
				$activeTypeIds[] = $typeInfo['ID'];
				$activeEntityTypeIds[] = $typeInfo['ENTITY_TYPE_ID'];
			}
		}
		$inactiveTypeIds = array_diff($volatileTypeIds, $activeTypeIds);
		$filter = [
			'LOGIC' => 'OR',
		];
		foreach ($inactiveTypeIds as $volatileTypeId)
		{
			$filter[] = [
				'=TYPE_ID' => $volatileTypeId,
				'@ENTITY_TYPE_ID' => $entityTypeIds,
			];
		}
		foreach ($activeTypeIds as $index => $volatileTypeId)
		{
			if (in_array($activeEntityTypeIds[$index], $entityTypeIds, true))
			{
				$entityTypeIdsToDelete = array_diff($entityTypeIds, [$activeEntityTypeIds[$index]]);
			}
			else
			{
				$entityTypeIdsToDelete = $entityTypeIds;
			}
			if (!empty($entityTypeIdsToDelete))
			{
				$filter[] = [
					'=TYPE_ID' => $volatileTypeId,
					'@ENTITY_TYPE_ID' => $entityTypeIdsToDelete,
				];
			}
		}

		$limit = static::ITEM_LIMIT;

		if (count($filter) > 1)
		{
			// Cleaning hashes
			$res = DuplicateEntityMatchHashTable::getList(
				[
					'select' => [
						'ENTITY_ID',
						'ENTITY_TYPE_ID',
						'TYPE_ID',
						'MATCH_HASH',
						'SCOPE',
					],
					'filter' => $filter,
					'limit' => $limit,
				]
			);
			if (is_object($res))
			{
				while ($row = $res->fetch())
				{
					$result = true;
					DuplicateEntityMatchHashTable::delete(
						[
							'ENTITY_ID' => (int)$row['ENTITY_ID'],
							'ENTITY_TYPE_ID' => (int)$row['ENTITY_TYPE_ID'],
							'TYPE_ID' => (int)$row['TYPE_ID'],
							'MATCH_HASH' => $row['MATCH_HASH'],
							'SCOPE' => $row['SCOPE'],
						]
					);
				}
			}

			// Cleaning match codes
			$res = DuplicateVolatileMatchCodeTable::getList(
				[
					'select' => ['ID'],
					'filter' => $filter,
					'limit' => $limit,
				]
			);
			if (is_object($res))
			{
				while ($row = $res->fetch())
				{
					$result = true;
					DuplicateVolatileMatchCodeTable::delete((int)$row['ID']);
				}
			}
		}

		return $result;
	}

	protected function onRunning(array $progressData): bool
	{
		if (!$this->checkStepInterval($progressData))
		{
			return false;
		}

		$timeToProcess = (int)floor(static::STEP_TTL * static::STEP_RATIO);
		$startTime = $this->getTimeStamp();
		$endTime = $startTime;
		$isFinal = false;

		while (!$isFinal && $endTime - $startTime <= $timeToProcess)
		{
			if(!$this->processStep())
			{
				$progressData['PROGRESS_VARS']['ACTIVE'] = 'N';

				$timestamp = $this->getTimeStamp();
				$progressData['STATUS'] = static::STATUS_FINISHED;
				$progressData['TIMESTAMP_FINISH'] = $timestamp;
				$progressData['TIMESTAMP'] = $timestamp;
				unset($timestamp);
				$this->setProgressData($progressData);

				$this->setAgentResult(false);

				$isFinal = true;
			}

			$endTime = $this->getTimeStamp();
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
