<?php

namespace Bitrix\Crm\Agent\Duplicate\Automatic;

use Bitrix\Crm\Integrity\AutoSearchUserSettings;
use Bitrix\Crm\Integrity\DedupeConfig;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\AutomaticDuplicateList;
use Bitrix\Crm\Integrity\DuplicateManager;
use Bitrix\Crm\Integrity\Entity\AutosearchUserSettingsTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;

class RebuildUserDuplicateIndexAgent
{
	public static function add(int $entityTypeId, int $userId, int $timeOffset = 0): void
	{
		$execTime = \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $timeOffset, 'FULL');
		\CAgent::AddAgent(
			static::getAgentName($entityTypeId, $userId),
			'crm', 'N', 1, "", "Y", $execTime
		);
	}

	public static function run($entityTypeId, $userId): string
	{
		$entityTypeId = (int)$entityTypeId;
		$userId = (int)$userId;

		return static::doRun($entityTypeId, $userId) ? static::getAgentName($entityTypeId, $userId) : "";
	}

	protected static function getAgentName(int $entityTypeId, int $userId): string
	{
		return get_called_class() . "::run($entityTypeId, $userId);";
	}

	public static function doRun(int $entityTypeId, int $userId)
	{
		$instance = new static();
		$userSettings = $instance->loadUserSettings($entityTypeId, $userId);
		if ($userSettings !== null)
		{
			return $instance->rebuildIndex($userSettings);
		}

		return false;
	}

	protected function loadUserSettings(int $entityTypeId, int $userId): ?AutoSearchUserSettings
	{
		if (!AutoSearchUserSettings::isEnabled())
		{
			return null;
		}

		$userSettings = AutosearchUserSettingsTable::query()
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('USER_ID', $userId)
			->setSelect(['*'])
			->setLimit(1)
			->fetchObject()
		;
		if (!$userSettings)
		{
			return null;
		}

		if (!in_array($userSettings->getStatusId(),
			[AutoSearchUserSettings::STATUS_NEW, AutoSearchUserSettings::STATUS_INDEX_REBUILDING], true))
		{
			return null;
		}

		if (!AutoSearchUserSettings::hasAccess($userSettings->getEntityTypeId(), $userSettings->getUserId()))
		{ // if user has not enough rights, check him tomorrow again:
			$userSettings->setNextExecTime((new DateTime())->add('1 day'));
			if ($userSettings->getStatusId() === AutoSearchUserSettings::STATUS_INDEX_REBUILDING)
			{
				$userSettings->setStatusId(AutoSearchUserSettings::STATUS_NEW);
			}
			$userSettings->save();

			return null;
		}

		return $userSettings;
	}

	protected function rebuildIndex(AutoSearchUserSettings $userSettings): bool
	{
		$progressData = $userSettings->getProgressData();
		$userId = $userSettings->getUserId();
		$entityTypeId = $userSettings->getEntityTypeId();

		$dedupeConfig = $this->getDedupeConfig($entityTypeId, $userId);
		$enablePermissionCheck = !\CCrmPerms::IsAdmin($userId);
		$isStart = ($userSettings->getStatusId() === AutoSearchUserSettings::STATUS_NEW);
		if ($isStart)
		{
			$userSettings
				->setStatusId(AutoSearchUserSettings::STATUS_INDEX_REBUILDING)
				->save()
			;

			// types and scope used in previous run:
			$prevTypeIds = $progressData['TYPE_IDS'] ?? [];
			$prevScope = $progressData['CURRENT_SCOPE'] ?? DuplicateIndexType::DEFAULT_SCOPE;

			$progressData = [
				'PREV_TYPE_IDS' => $prevTypeIds,
				'PREV_SCOPE' => $prevScope,
				'TYPE_IDS' => $dedupeConfig['typeIDs'],
				'CURRENT_SCOPE' => $dedupeConfig['scope'],
				'CURRENT_TYPE_INDEX' => 0,
				'PROCESSED_ITEMS' => 0,
				'FOUND_CHANGED_ITEMS' => 0,
				'FOUND_ITEMS' => 0,
				'TOTAL_ENTITIES' => 0,
				'CONTEXT_ID' => Random::getStringByCharsets(8, 'abcdefghijklmnopqrstuvwxyz'),
				'STARTED_TIMESTAMP' => time(),
			];

			$effectiveTypeIDs = $progressData['TYPE_IDS'];
			$effectiveScope = $progressData['CURRENT_SCOPE'];
			$currentTypeIndex = $progressData['CURRENT_TYPE_INDEX'];
		}
		else
		{
			$effectiveTypeIDs = $progressData['TYPE_IDS'] ?? null;
			if (!is_array($effectiveTypeIDs) || empty($effectiveTypeIDs))
			{
				$effectiveTypeIDs = $dedupeConfig['typeIDs'];
			}
			$effectiveScope = $progressData['CURRENT_SCOPE'] ?? DuplicateIndexType::DEFAULT_SCOPE;
			$currentTypeIndex = isset($progressData['CURRENT_TYPE_INDEX'])
				? (int)$progressData['CURRENT_TYPE_INDEX'] : 0;
		}

		$effectiveTypeQty = count($effectiveTypeIDs);
		if ($currentTypeIndex >= $effectiveTypeQty)
		{
			$userSettings->calcAndSetNextExecTime();
			$userSettings->setStatusId(AutoSearchUserSettings::STATUS_NEW);
			$userSettings->save();

			return false;
		}

		// fast algorithm can be used only
		// if data for this typeId was already processed at least once
		// and scope was not changed:
		$checkChangedOnly =
			$userSettings->getCheckChangedOnly() &&
			$progressData['PREV_SCOPE'] == $progressData['CURRENT_SCOPE'] &&
			in_array($effectiveTypeIDs[$currentTypeIndex], $progressData['PREV_TYPE_IDS']);

		$builder = DuplicateManager::createAutomaticIndexBuilder(
			$effectiveTypeIDs[$currentTypeIndex],
			$entityTypeId,
			$userId,
			$enablePermissionCheck,
			[
				'SCOPE' => $effectiveScope,
				'LAST_INDEX_DATE' => $userSettings->getLastExecTime(),
				'CHECK_CHANGED_ONLY' => $checkChangedOnly,
				'CONTEXT_ID' => $progressData['CONTEXT_ID'] ?? '',
			]
		);

		$buildData = $progressData['BUILD_DATA'] ?? [];

		$offset = (int)($buildData['OFFSET'] ?? 0);
		if ($offset === 0)
		{
			$builder->remove();
		}

		$limit = (int)($buildData['LIMIT'] ?? 0);
		if ($limit === 0)
		{
			$buildData['LIMIT'] = $this->getRebuildIndexLimit();
		}

		$isInProgress = $builder->build($buildData);

		if (isset($buildData['PROCESSED_ITEM_COUNT']))
		{
			$progressData['PROCESSED_ITEMS'] = (int)($progressData['PROCESSED_ITEMS'] ?? 0);
			$progressData['PROCESSED_ITEMS'] += $buildData['PROCESSED_ITEM_COUNT'];
		}

		if (isset($buildData['EFFECTIVE_ITEM_COUNT']))
		{
			$progressData['FOUND_CHANGED_ITEMS'] = (int)($progressData['FOUND_CHANGED_ITEMS'] ?? 0);
			$progressData['FOUND_CHANGED_ITEMS'] += $buildData['EFFECTIVE_ITEM_COUNT'];
		}

		$progressData['BUILD_DATA'] = $buildData;

		$isFinal = false;
		if (!$isInProgress)
		{
			$builder->dropDataSourceCache();
			$isFinal = $currentTypeIndex === ($effectiveTypeQty - 1);
			if (!$isFinal)
			{
				$progressData['CURRENT_TYPE_INDEX'] = ++$currentTypeIndex;
				unset($progressData['BUILD_DATA']);
			}
		}

		if ($isFinal)
		{
			if ($userSettings->getCheckChangedOnly())
			{
				// if search settings was changed,
				// possibly there are indexes for invalid types or scopes
				// and necessary to remove them:
				$unusedTypeIds = array_diff($progressData['PREV_TYPE_IDS'], $progressData['TYPE_IDS']);
				if (count($unusedTypeIds))
				{
					$builder->removeUnusedIndexByTypeIds($unusedTypeIds);
				}
				if ($progressData['PREV_SCOPE'] != $progressData['CURRENT_SCOPE'])
				{
					$builder->removeUnusedIndexByScope($progressData['PREV_SCOPE']);
				}
			}

			$foundItems = AutomaticDuplicateList::getTotalItems(
				$userId,
				$entityTypeId,
				$effectiveTypeIDs,
				$effectiveScope
			);

			$isMergeEnabled = $userSettings->getIsMergeEnabled();
			if ($foundItems > 0)
			{
				$totalEntries = AutomaticDuplicateList::getTotalEntityCount(
					$userId,
					$entityTypeId,
					$effectiveTypeIDs,
					$effectiveScope
				);

				$userSettings->setStatusId(
					$isMergeEnabled ?
						AutoSearchUserSettings::STATUS_MERGING :
						AutoSearchUserSettings::STATUS_READY_TO_MERGE
				);
			}
			else
			{
				$totalEntries = 0;
				$userSettings->setStatusId(AutoSearchUserSettings::STATUS_NEW);
			}
			$userSettings->calcAndSetNextExecTime();

			$userSettings->setLastExecTime(
				DateTime::createFromTimestamp($progressData['STARTED_TIMESTAMP'] ?? time())
			);
			$userSettings->setCheckChangedOnly(true);

			if ($totalEntries > 0 && Loader::includeModule('pull'))
			{
				\Bitrix\Pull\Event::add($userId, [
					'module_id' => 'crm',
					'command' => 'dedupe.autosearch.startMerge',
					'params' => $isMergeEnabled ?
						[
							'status' => 'MERGING',
							'entityTypeId' => $entityTypeId,
						] :
						[
							'status' => 'READY_TO_MERGE',
							'entityTypeId' => $entityTypeId,
							'progressData' => [
								'TOTAL_ENTITIES' => $totalEntries,
								'FOUND_ITEMS' => $foundItems,
							],
						],
				]);
			}

			$progressData['TOTAL_ENTITIES'] = $totalEntries;
			$progressData['FOUND_ITEMS'] = $foundItems;
		}

		$userSettings->setProgressData($progressData);
		$userSettings->save();

		return !$isFinal;
	}

	protected function getDedupeConfig(int $entityTypeId, int $userId): array
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$optionsGridName = 'lead_dedupe_wizard';
				break;
			case \CCrmOwnerType::Contact:
				$optionsGridName = 'contact_dedupe_wizard';
				break;
			case \CCrmOwnerType::Company:
				$optionsGridName = 'company_dedupe_wizard';
				break;
			default:
				throw new ArgumentException('This entity is not supported', 'ENTITY_TYPE_ID');
		}

		$config = new DedupeConfig($userId);

		return $config->get($optionsGridName, $entityTypeId);
	}

	protected function getRebuildIndexLimit()
	{
		$limit = (int)Option::get("crm", "~duplicate_autosearch_rebuild_limit", 50);

		return $limit > 0 ? $limit : 50;
	}
}