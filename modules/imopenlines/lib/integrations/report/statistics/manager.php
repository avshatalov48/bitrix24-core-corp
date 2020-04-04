<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;


use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

/**
 * Class Manager
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
abstract class Manager
{

	const TREATMENT_STATISTIC_KEY = 'treatment';
	/**
	 * for optimisation
	 */
	const TREATMENT_BY_HOUR_STATISTIC_KEY = 'treatment_by_hour';
	const MARK_STATISTIC_KEY = 'mark';
	const DIALOG_CREATE_STATISTIC_KEY = 'dialog_create';
	const DIALOG_SKIP_STATISTIC_KEY = 'dialog_skip';
	const DIALOG_ANSWER_STATISTIC_KEY = 'dialog_answer';
	/**
	 * @return array
	 */
	private static function getAggregateManagersMap()
	{
		return array(
			self::TREATMENT_STATISTIC_KEY => Treatment::getClassName(),
			self::TREATMENT_BY_HOUR_STATISTIC_KEY => TreatmentByHour::getClassName(),
			self::MARK_STATISTIC_KEY => Mark::getClassName(),
			self::DIALOG_SKIP_STATISTIC_KEY => Dialog::getClassName(),
			self::DIALOG_ANSWER_STATISTIC_KEY => Dialog::getClassName(),
			self::DIALOG_CREATE_STATISTIC_KEY => Dialog::getClassName(),
		);
	}



	public static function writeToStatistics($statisticKey, array $params)
	{
		$map = self::getAggregateManagersMap();
		if (!empty($map[$statisticKey]))
		{
			/** @var AggregateStrategy $aggregator */
			$aggregator = new $map[$statisticKey]($params);
			if ($aggregator instanceof AggregateStrategy)
			{
				if (!$aggregator->getErrors())
				{
					if ($existingRecord = $aggregator->getExistingRecordByPrimary())
					{
						$aggregator->updateRecord($existingRecord);
					}
					else
					{
						$aggregator->createRecord();
					}
				}
			}
		}
	}


	public static function getFromQueue($primary)
	{
		return  StatisticQueueTable::getByPrimary($primary);
	}

	/**
	 * @param $sessionId
	 * @param $statisticKey
	 * @param array $additionalParams
	 */
	public static function addToQueue($sessionId, $statisticKey, $additionalParams = array())
	{
		$isPreCalculationFinish = Option::get("imopenlines", 'statisticPreCalculateFinish', false);
		if ($isPreCalculationFinish)
		{
			StatisticQueueTable::add(array(
				'fields' => array(
					'SESSION_ID' => $sessionId,
					'STATISTIC_KEY' => $statisticKey,
					'DATE_QUEUE' => new DateTime(),
					'PARAMS' => $additionalParams,
				)
			));
		}
	}

	public static function removeFromQueue($primary)
	{
		StatisticQueueTable::delete($primary);
	}

	public static function calculateStatisticsInQueue()
	{
		$query = new Query(StatisticQueueTable::getEntity());
		$query->addSelect('ID');
		$query->addSelect('SESSION_ID');
		$query->addSelect('STATISTIC_KEY');
		$query->addSelect('PARAMS');
		$query->addOrder('DATE_QUEUE');
		$query->where('DATE_QUEUE', '<=', new DateTime());
		$resultsFromQueue = $query->exec()->fetchAll();


		foreach ($resultsFromQueue as $resultFromQueue)
		{
			$id = $resultFromQueue['ID'];
			$statisticNameKey = $resultFromQueue['STATISTIC_KEY'];
			$params = $resultFromQueue['PARAMS'];
			self::writeToStatistics($statisticNameKey, $params);

			self::removeFromQueue($id);
		}

		$className = get_called_class();
		return $className  . '::calculateStatisticsInQueue();';
	}
}