<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm;
use Bitrix\Crm\Statistics\Entity\ActivityStatisticsTable;
class ActivityStatisticEntry
{
	/**
	 * @param $ownerID
	 * @param Date $date
	 * @param string $providerId
	 * @param string $providerTypeId
	 * @return array|null
	 * @throws Main\ArgumentException
	 */
	public static function get($ownerID, Date $date, $providerId, $providerTypeId)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ActivityStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addFilter('=DEADLINE_DATE', $date);
		$query->addFilter('=PROVIDER_ID', $providerId);
		$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}

	/**
	 * @param $ownerID
	 * @param array $entityFields
	 * @param array $options
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public static function register($ownerID, array $entityFields = null, array $options = null)
	{
		if(!is_int($ownerID))
			$ownerID = (int)$ownerID;

		if($ownerID <= 0)
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');

		if(!is_array($options))
			$options = array();

		if(!is_array($entityFields))
		{
			$entityFields = \CCrmActivity::GetByID($ownerID, false);
			if(!is_array($entityFields) || !$entityFields['DEADLINE'])
			{
				return false;
			}
		}

		$provider = \CCrmActivity::GetActivityProvider($entityFields);
		if (!$provider)
			return false;

		$supportedStatistics = $provider::getSupportedCommunicationStatistics();

		if (empty($supportedStatistics))
			return true;

		$date = isset($entityFields['DEADLINE']) ? $entityFields['DEADLINE'] : null;
		if(!$date)
			throw new Main\ArgumentException('DEADLINE must be specified.', 'DEADLINE');

		if (!is_object($date))
		{
			$date = new Main\Type\DateTime($date);
			$date->setTime(0, 0, 0);
		}

		$providerId = $provider::getId();
		$providerTypeId = $provider::getTypeId($entityFields);
		if(!$providerTypeId)
			throw new Main\ArgumentException('PROVIDER_TYPE_ID must be specified.', 'PROVIDER_TYPE_ID');

		$ownerTypeID = isset($entityFields['OWNER_TYPE_ID']) ? (int)$entityFields['OWNER_TYPE_ID'] : 0;
		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;
		$completed = isset($entityFields['COMPLETED']) && $entityFields['COMPLETED'] === 'Y' ? 'Y' : 'N';

		$statusId = isset($entityFields['RESULT_STATUS']) ? (int)$entityFields['RESULT_STATUS'] : 0;
		$markId = isset($entityFields['RESULT_MARK']) ? (int)$entityFields['RESULT_MARK'] : 0;
		$streamId = isset($entityFields['RESULT_STREAM']) ? (int)$entityFields['RESULT_STREAM'] : 0;
		$sourceId = isset($entityFields['RESULT_SOURCE_ID']) ? (string)$entityFields['RESULT_SOURCE_ID'] : '';
		if (strlen($sourceId) === 0)
			$sourceId = Crm\Activity\CommunicationStatistics::DEFAULT_SOURCE;

		$currencyId = isset($entityFields['RESULT_CURRENCY_ID']) ? (string)$entityFields['RESULT_CURRENCY_ID'] : '';
		$sumTotal = isset($entityFields['RESULT_SUM']) ? (float)$entityFields['RESULT_SUM'] : 0.0;

		$accountingCurrencyID = \CCrmCurrency::GetAccountCurrencyID();

		if($currencyId !== $accountingCurrencyID)
		{
			$accData = \CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $currencyId,
					'SUM' => $sumTotal
				)
			);
			if(is_array($accData))
			{
				$sumTotal = (float)$accData['ACCOUNT_SUM'];
				$currencyId = $accountingCurrencyID;
			}
		}

		if ($completed === 'N'
			&& !in_array(Crm\Activity\CommunicationStatistics::STATISTICS_STATUSES, $supportedStatistics)
			&& !in_array(Crm\Activity\CommunicationStatistics::STATISTICS_STREAMS, $supportedStatistics)
			|| !in_array(Crm\Activity\CommunicationStatistics::STATISTICS_MARKS, $supportedStatistics)
			&& $statusId === 0
			&& $streamId === 0
			&& $sourceId === ''
			&& $sumTotal === 0.0
		)
		{
			ActivityStatisticsTable::delete(array(
				'OWNER_ID' => $ownerID,
				'DEADLINE_DATE' => $date,
				'PROVIDER_ID' => $providerId,
				'PROVIDER_TYPE_ID' => $providerTypeId
			));
			return true;
		}

		$present = self::get($ownerID, $date, $providerId, $providerTypeId);
		if(is_array($present))
		{
			if (
				$ownerTypeID === (int)$present['OWNER_TYPE_ID']
				&& $responsibleID === (int)$present['RESPONSIBLE_ID']
				&& $completed === $present['COMPLETED']
				&& $statusId === (int)$present['STATUS_ID']
				&& $markId === (int)$present['MARK_ID']
				&& $streamId === (int)$present['STREAM_ID']
				&& $sourceId === $present['SOURCE_ID']
				&& $sumTotal === (float)$present['SUM_TOTAL']
			)
				return true;

			if($responsibleID !== (int)$present['RESPONSIBLE_ID'])
			{
				ActivityStatisticsTable::synchronize(
					$ownerID,
					array('RESPONSIBLE_ID' => $responsibleID)
				);
			}
		}

		$data = array(
			'OWNER_ID' => $ownerID,
			'DEADLINE_DATE' => $date,
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'RESPONSIBLE_ID' => $responsibleID,
			'COMPLETED' => $completed,
			'STATUS_ID' => $statusId,
			'MARK_ID' => $markId,
			'SOURCE_ID' => $sourceId,
			'STREAM_ID' => $streamId,
			'CURRENCY_ID' => $currencyId,
			'SUM_TOTAL' => $sumTotal,
		);

		ActivityStatisticsTable::upsert($data);

		return true;
	}

	/**
	 * @param $ownerID
	 * @throws Main\ArgumentException
	 */
	public static function unregister($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		ActivityStatisticsTable::deleteByOwner($ownerID);
	}

	/**
	 * @param $ownerID
	 * @param array $entityFields
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function synchronize($ownerID, array $entityFields = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ActivityStatisticsTable::getEntity());
		$query->addSelect('RESPONSIBLE_ID');

		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$first = $dbResult->fetch();
		if(!is_array($first))
		{
			return false;
		}

		if(!is_array($entityFields))
		{
			$entityFields = \CCrmActivity::GetByID($ownerID, false);
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;
		if($responsibleID === (int)$first['RESPONSIBLE_ID'])
		{
			return false;
		}

		ActivityStatisticsTable::synchronize(
			$ownerID,
			array('RESPONSIBLE_ID' => $responsibleID)
		);
		return true;
	}

	/**
	 * @param array $ownerIDs
	 * @return bool
	 */
	public static function rebuild(array $ownerIDs)
	{
		foreach ($ownerIDs as $ownerID)
		{
			static::register($ownerID);
		}

		return true;
	}
}