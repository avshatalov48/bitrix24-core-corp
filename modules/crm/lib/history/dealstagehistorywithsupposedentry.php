<?php

namespace Bitrix\Crm\History;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\History\Entity\DealStageHistoryWithSupposedTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use CCrmStatus;

class DealStageHistoryWithSupposedEntry
{

	public static function register($dealId)
	{
		$historiesToAdd = self::getHistoriesToAdd($dealId);
		self::saveHistories($historiesToAdd);
	}

	private static function getHistoriesToAdd($dealId)
	{
		$lastSavedStageModification = self::getDealLastModificationFromSupposed($dealId);

		if ($lastSavedStageModification)
		{
			$existingHistoryToWrite = self::getDealNewHistoryModificationsById($dealId, $lastSavedStageModification['CREATED_TIME']);
			if ($existingHistoryToWrite)
			{
				$firstModificationToWrite = $existingHistoryToWrite[0];
				/** @var DateTime $firstModificationDate */
				$firstModificationDate = $firstModificationToWrite['HISTORY_CREATED_TIME'];
				/** @var DateTime $lastSavedModificationDate */
				$lastSavedModificationDate = $lastSavedStageModification['CREATED_TIME'];
				$spentTime = $firstModificationDate->getTimestamp() - $lastSavedModificationDate->getTimestamp();
				DealStageHistoryWithSupposedTable::update(
					$lastSavedStageModification['ID'],
					[
						'SPENT_TIME' => $spentTime
					]
				);
			}
		}
		else
		{
			$existingHistoryToWrite = self::getDealNewHistoryModificationsById($dealId);
		}

		$prepared = self::prepareHistoriesFromExistHistory($existingHistoryToWrite);
		$historiesToAdd = [];
		if (!$lastSavedStageModification)
		{
			foreach ($prepared as $stageId => $history)
			{
				if (!empty($history))
				{
					$historiesToAdd[$stageId] = $history;
				}
			}
		}
		else
		{
			foreach ($prepared as $stageId => $history)
			{
				if ((string)$stageId === (string)$lastSavedStageModification['STAGE_ID'])
				{
					break;
				}

				if (!empty($history))
				{
					$historiesToAdd[$stageId] = $history;
				}
			}
		}
		return $historiesToAdd;
	}

	private static function saveHistories($histories)
	{
		if (!empty($histories))
		{
			foreach ($histories as $history)
			{
				if (in_array($history['STAGE_SEMANTIC_ID'], ['S', 'F']))
				{
					DealStageHistoryWithSupposedTable::updateCloseDateByOwnerId(
						$history['OWNER_ID'],
						$history['CREATED_DATE']
					);
				}
				DealStageHistoryWithSupposedTable::add($history);
			}
		}
	}

	private static function getDealLastModificationFromSupposed($dealId)
	{
		$query = DealStageHistoryWithSupposedTable::query();
		$query->addSelect('ID');
		$query->addSelect('STAGE_ID');
		$query->addSelect('CREATED_TIME');
		$query->addSelect('SPENT_TIME');
		$query->setOrder(['CREATED_TIME' => 'DESC']);
		$query->setLimit(1);
		$query->where('OWNER_ID', $dealId);
		$query->where('IS_SUPPOSED', 'N');
		$result = $query->exec()->fetchAll();

		return !empty($result) ? $result[0] : null;
	}

	private static function getDealNewHistoryModificationsById($dealId, $fromDate = null)
	{
		$dealHistoryQuery = DealTable::query();
		$dealHistoryQuery->addSelect('HISTORY.OWNER_ID', 'DEAL_ID');
		$dealHistoryQuery->addSelect('HISTORY.STAGE_ID', 'STAGE_ID_FROM_HISTORY');
		$dealHistoryQuery->addSelect('HISTORY.CREATED_TIME', 'HISTORY_CREATED_TIME');
		$dealHistoryQuery->addSelect('HISTORY.CREATED_DATE', 'HISTORY_CREATED_DATE');
		$dealHistoryQuery->addSelect('HISTORY.CATEGORY_ID', 'HISTORY_CATEGORY_ID');
		$dealHistoryQuery->addSelect('HISTORY.STAGE_SEMANTIC_ID', 'STAGE_SEMANTIC_ID_FROM_HISTORY');
		$dealHistoryQuery->addSelect('STAGE_SEMANTIC_ID', 'DEAL_STAGE_SEMANTIC_ID');
		$dealHistoryQuery->where('ID', $dealId);

		if (!is_null($fromDate))
		{
			$dealHistoryQuery->where('HISTORY.CREATED_TIME', '>=', $fromDate);
			$dealHistoryQuery->where('HISTORY.HAS_SUPPOSED_HISTORY_RECORD', '=', 0);
		}

		return $dealHistoryQuery->exec()->fetchAll();
	}

	private static function prepareHistoriesFromExistHistory($existHistory)
	{
		$firstFromExistHistory = $existHistory[0];
		$categoryId = $firstFromExistHistory['HISTORY_CATEGORY_ID'];
		$sortedAllStages = self::getExistStageIdListByCategoryId($categoryId);
		$unSuccessStageList = self::getDealUnSuccessStageListByCategoryId($categoryId);

		$sortedAllStagesDESC = array_reverse($sortedAllStages);

		$history = [];
		foreach ($existHistory as $historyRow)
		{
			$history[$historyRow['STAGE_ID_FROM_HISTORY']] = $historyRow;
		}
		$stageIdsFromHistory = array_keys($history);

		$lastStageFromHistory = end($stageIdsFromHistory);

		$histories = [];
		$lastHistoryRow = [];
		if (in_array($lastStageFromHistory, $unSuccessStageList))
		{
			$firstStageId = $sortedAllStages[0];
			$histories[$lastStageFromHistory] = self::prepareHistoryRow($history[$lastStageFromHistory]);

			if (!empty($histories[$lastStageFromHistory]))
			{
				$histories[$firstStageId] = $histories[$lastStageFromHistory];
				$histories[$firstStageId]['IS_SUPPOSED'] = 'Y';
				$histories[$firstStageId]['STAGE_ID'] = $firstStageId;
				$histories[$firstStageId]['STAGE_SEMANTIC_ID'] = 'P';
				$histories[$firstStageId]['IS_LOST'] = 'N';
			}
		}
		else
		{
			foreach ($sortedAllStagesDESC as $stageId)
			{
				if (isset($history[$stageId]))
				{
					$histories[$stageId] = self::prepareHistoryRow($history[$stageId]);
					$lastHistoryRow = $histories[$stageId];
				}
				else
				{
					if (!empty($lastHistoryRow))
					{
						$lastHistoryRow['IS_SUPPOSED'] = 'Y';
						$lastHistoryRow['STAGE_ID'] = $stageId;
						$lastHistoryRow['STAGE_SEMANTIC_ID'] = 'P';
						$lastHistoryRow['IS_LOST'] = 'N';
					}
					$histories[$stageId] = $lastHistoryRow;
				}
			}
		}

		return $histories;
	}

	private static function prepareHistoryRow($history)
	{
		$historyRow = [
			'OWNER_ID' => $history['DEAL_ID'],
			'CATEGORY_ID' => $history['HISTORY_CATEGORY_ID'],
			'STAGE_SEMANTIC_ID' => $history['STAGE_SEMANTIC_ID_FROM_HISTORY'],
			'STAGE_ID' => $history['STAGE_ID_FROM_HISTORY'],
			'IS_LOST' => $history['STAGE_SEMANTIC_ID_FROM_HISTORY'] == 'F' ? 'Y' : 'N',
			'IS_SUPPOSED' => 'N',
			'LAST_UPDATE_DATE' => $history['HISTORY_CREATED_DATE'],
			'CREATED_TIME' => $history['HISTORY_CREATED_TIME'],
			'CREATED_DATE' => $history['HISTORY_CREATED_DATE'],
			'CLOSE_DATE' => new Date('3000-12-12', 'Y-m-d'),
			'SPENT_TIME' => null
		];

		if (in_array($history['DEAL_STAGE_SEMANTIC_ID'], ['F', 'S']))
		{
			$historyRow['CLOSE_DATE'] = $historyRow['LAST_UPDATE_DATE'];
		}

		return $historyRow;
	}

	public static function getHistoryLastStageId($dealId)
	{
		$historyQuery = DealStageHistoryWithSupposedTable::query();
		$historyQuery->addSelect('STAGE_ID');
		$historyQuery->where('OWNER_ID', $dealId);
		$historyQuery->where('IS_SUPPOSED', 'N');
		$historyQuery->setOrder(['ID' => 'DESC']);
		$historyQuery->setLimit(1);
		$lastStageIdFromHistory = $historyQuery->exec()->fetchAll();

		return $lastStageIdFromHistory;
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 */
	public static function getExistStageIdList()
	{
		static $stageIdsByCategories = [];

		if (!empty($stageIds))
		{
			return $stageIdsByCategories;
		}
		$categoriesIds = DealCategory::getAllIDs();

		foreach ($categoriesIds as $categoryId)
		{
			$stageListByCategory = DealCategory::getStageList($categoryId);
			foreach ($stageListByCategory as $stageId => $name)
			{
				$stageIdsByCategories[$categoryId][$stageId] = $stageId;
			}
		}

		return $stageIdsByCategories;
	}

	private static function getExistStageIdListByCategoryId($id)
	{
		static $allStagesByCategoryId = null;
		if (!$allStagesByCategoryId)
		{
			$allStagesByCategoryId = self::getExistStageIdList();
		}

		if (!empty($allStagesByCategoryId[$id]))
		{
			return array_values($allStagesByCategoryId[$id]);
		}
		else
		{
			return [];
		}
	}

	private static function getDealUnSuccessStageListByCategoryId($id)
	{
		$namespace = DealCategory::prepareStageNamespaceID($id);
		$stageSemanticInfo = CCrmStatus::GetDealStageSemanticInfo($namespace);

		$stageIds = self::getExistStageIdListByCategoryId($id);

		$unSuccessStageList = [];
		$firstUnSuccessStageId = $stageSemanticInfo['FINAL_UNSUCCESS_FIELD'];

		$firstUnSuccessStage = false;
		foreach ($stageIds as $stageId)
		{
			if ($stageId === $firstUnSuccessStageId)
			{
				$unSuccessStageList[] = $stageId;
				$firstUnSuccessStage = true;
			}

			if ($firstUnSuccessStage)
			{
				$unSuccessStageList[] = $stageId;
			}
		}

		return $unSuccessStageList;
	}

	public static function processCategoryChange($dealId)
	{
		self::unregister($dealId);
		self::register($dealId);
	}

	public static function unregister($dealId)
	{
		DealStageHistoryWithSupposedTable::deleteByOwnerId($dealId);
	}
}