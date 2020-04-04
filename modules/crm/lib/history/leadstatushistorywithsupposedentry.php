<?php

namespace Bitrix\Crm\History;

use Bitrix\Crm\History\Entity\LeadStatusHistoryWithSupposedTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use CCrmStatus;

class LeadStatusHistoryWithSupposedEntry
{

	public static function register($leadId)
	{
		$historiesToAdd = self::getHistoriesToAdd($leadId);
		self::saveHistories($historiesToAdd);
	}

	private static function getHistoriesToAdd($leadId)
	{
		$lastSavedStatusChanging = self::getLeadLastChangingFromSupposed($leadId);

		if ($lastSavedStatusChanging)
		{
			$lastChangeDate = $lastSavedStatusChanging['CREATED_TIME'];
			$existingHistoryToWrite = self::getLeadNewHistoryChangingsById($leadId, $lastChangeDate);
			if ($existingHistoryToWrite)
			{
				$firstChangingToWrite = $existingHistoryToWrite[0];
				/** @var DateTime $firstChangingDate */
				$firstChangingDate = $firstChangingToWrite['HISTORY_CREATED_TIME'];
				/** @var DateTime $lastSavedChangingDate */
				$lastSavedChangingDate = $lastSavedStatusChanging['CREATED_TIME'];
				$spentTime = $firstChangingDate->getTimestamp() - $lastSavedChangingDate->getTimestamp();
				LeadStatusHistoryWithSupposedTable::update(
					$lastSavedStatusChanging['ID'],
					[
						'SPENT_TIME' => $spentTime
					]
				);
			}
		}
		else
		{
			$existingHistoryToWrite = self::getLeadNewHistoryChangingsById($leadId);
		}

		$prepared = self::prepareHistoriesFromExistHistory($existingHistoryToWrite);
		$historiesToAdd = [];
		if (!$lastSavedStatusChanging)
		{
			foreach ($prepared as $statusId => $history)
			{
				if (!empty($history))
				{
					$historiesToAdd[$statusId] = $history;
				}
			}
		}
		else
		{
			foreach ($prepared as $statusId => $history)
			{
				if ($statusId === $lastSavedStatusChanging['STATUS_ID'])
				{
					break;
				}

				if (!empty($history))
				{
					$historiesToAdd[$statusId] = $history;
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
				if (in_array($history['STATUS_SEMANTIC_ID'], ['S', 'F']))
				{
					LeadStatusHistoryWithSupposedTable::updateCloseDateByOwnerId(
						$history['OWNER_ID'],
						$history['CREATED_DATE']
					);
				}
				LeadStatusHistoryWithSupposedTable::add($history);
			}
		}
	}

	private static function getLeadLastChangingFromSupposed($leadId)
	{
		$query = LeadStatusHistoryWithSupposedTable::query();
		$query->addSelect('ID');
		$query->addSelect('STATUS_ID');
		$query->addSelect('CREATED_TIME');
		$query->addSelect('SPENT_TIME');
		$query->setOrder(['CREATED_TIME' => 'DESC']);
		$query->setLimit(1);
		$query->where('OWNER_ID', $leadId);
		$query->where('IS_SUPPOSED', 'N');
		$result = $query->exec()->fetchAll();

		return !empty($result) ? $result[0] : null;
	}

	private static function getLeadNewHistoryChangingsById($leadId, $fromDate = null)
	{
		$leadHistoryQuery = LeadTable::query();
		$leadHistoryQuery->addSelect('HISTORY.OWNER_ID', 'LEAD_ID');
		$leadHistoryQuery->addSelect('HISTORY.STATUS_ID', 'STATUS_ID_FROM_HISTORY');
		$leadHistoryQuery->addSelect('HISTORY.CREATED_TIME', 'HISTORY_CREATED_TIME');
		$leadHistoryQuery->addSelect('HISTORY.CREATED_DATE', 'HISTORY_CREATED_DATE');
		$leadHistoryQuery->addSelect('HISTORY.STATUS_SEMANTIC_ID', 'STATUS_SEMANTIC_ID_FROM_HISTORY');
		$leadHistoryQuery->addSelect('STATUS_SEMANTIC_ID', 'LEAD_STATUS_SEMANTIC_ID');
		$leadHistoryQuery->where('ID', $leadId);

		if (!is_null($fromDate))
		{
			$leadHistoryQuery->where('HISTORY.CREATED_TIME', '>', $fromDate);
		}

		$leadHistory = $leadHistoryQuery->exec()->fetchAll();

		return $leadHistory;
	}

	private static function prepareHistoriesFromExistHistory($existHistory)
	{


		$sortedAllStatuses = self::getExistStatusIdList();
		$unSuccessStatusList = self::getLeadUnSuccessStatusList();

		$sortedAllStatusDESC = array_reverse($sortedAllStatuses);

		$history = [];
		foreach ($existHistory as $historyRow)
		{
			$history[$historyRow['STATUS_ID_FROM_HISTORY']] = $historyRow;
		}
		$statusIdsFromHistory = array_keys($history);

		$lastStatusFromHistory = end($statusIdsFromHistory);

		$histories = [];
		$lastHistoryRow = [];
		if (in_array($lastStatusFromHistory, $unSuccessStatusList))
		{
			$firstStatusId = $sortedAllStatuses[0];
			$histories[$lastStatusFromHistory] = self::prepareHistoryRow($history[$lastStatusFromHistory]);

			if (!empty($histories[$lastStatusFromHistory]))
			{
				$histories[$firstStatusId] = $histories[$lastStatusFromHistory];
				$histories[$firstStatusId]['IS_SUPPOSED'] = 'Y';
				$histories[$firstStatusId]['STATUS_ID'] = $firstStatusId;
				$histories[$firstStatusId]['STATUS_SEMANTIC_ID'] = 'P';
				$histories[$firstStatusId]['IS_LOST'] = 'N';
			}
		}
		else
		{
			foreach ($sortedAllStatusDESC as $statusId)
			{
				if (isset($history[$statusId]))
				{
					$histories[$statusId] = self::prepareHistoryRow($history[$statusId]);
					$lastHistoryRow = $histories[$statusId];
				}
				else
				{
					if (!empty($lastHistoryRow))
					{
						$lastHistoryRow['IS_SUPPOSED'] = 'Y';
						$lastHistoryRow['STATUS_ID'] = $statusId;
						$lastHistoryRow['STATUS_SEMANTIC_ID'] = 'P';
						$lastHistoryRow['IS_LOST'] = 'N';
					}
					$histories[$statusId] = $lastHistoryRow;
				}
			}
		}

		return $histories;
	}

	private static function prepareHistoryRow($history)
	{
		$historyRow = [
			'OWNER_ID' => $history['LEAD_ID'],
			'STATUS_SEMANTIC_ID' => $history['STATUS_SEMANTIC_ID_FROM_HISTORY'],
			'STATUS_ID' => $history['STATUS_ID_FROM_HISTORY'],
			'IS_LOST' => $history['STATUS_SEMANTIC_ID_FROM_HISTORY'] == 'F' ? 'Y' : 'N',
			'IS_SUPPOSED' => 'N',
			'LAST_UPDATE_DATE' => $history['HISTORY_CREATED_DATE'],
			'CREATED_TIME' => $history['HISTORY_CREATED_TIME'],
			'CREATED_DATE' => $history['HISTORY_CREATED_DATE'],
			'CLOSE_DATE' => new Date('3000-12-12', 'Y-m-d'),
			'SPENT_TIME' => null
		];

		if (in_array($history['LEAD_STATUS_SEMANTIC_ID'], ['F', 'S']))
		{
			$historyRow['CLOSE_DATE'] = $historyRow['LAST_UPDATE_DATE'];
		}

		return $historyRow;
	}

	public static function getHistoryLastStatusId($leadId)
	{
		$historyQuery = LeadStatusHistoryWithSupposedTable::query();
		$historyQuery->addSelect('STATUS_ID');
		$historyQuery->where('OWNER_ID', $leadId);
		$historyQuery->where('IS_SUPPOSED', 'N');
		$historyQuery->setOrder(['ID' => 'DESC']);
		$historyQuery->setLimit(1);
		$lastStatusIdFromHistory = $historyQuery->exec()->fetchAll();

		return $lastStatusIdFromHistory;
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getExistStatusIdList()
	{
		$statusListQuery = new Query(StatusTable::getEntity());
		$statusListQuery->addSelect('STATUS_ID');
		$statusListQuery->where('ENTITY_ID', 'STATUS');
		$statusListQuery->addOrder('SORT');

		$statusList = $statusListQuery->exec()->fetchAll();
		$result = [];
		if (!empty($statusList))
		{
			foreach ($statusList as $status)
			{
				$result[] = $status['STATUS_ID'];
			}
		}

		return $result;
	}


	private static function getLeadUnSuccessStatusList()
	{
		$statusSemanticInfo = CCrmStatus::GetLeadStatusSemanticInfo();

		$statusIds = self::getExistStatusIdList();

		$unSuccessStatusList = [];
		$firstUnSuccessStatusId = $statusSemanticInfo['FINAL_UNSUCCESS_FIELD'];

		$firstUnSuccessStatus = false;
		foreach ($statusIds as $statusId)
		{
			if ($statusId === $firstUnSuccessStatusId)
			{
				$unSuccessStatusList[] = $statusId;
				$firstUnSuccessStatus = true;
			}

			if ($firstUnSuccessStatus)
			{
				$unSuccessStatusList[] = $statusId;
			}
		}

		return $unSuccessStatusList;
	}

	public static function unregister($leadId)
	{
		LeadStatusHistoryWithSupposedTable::deleteByOwnerId($leadId);
	}
}