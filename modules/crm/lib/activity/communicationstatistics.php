<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Statistics;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Binding;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

class CommunicationStatistics
{
	const STATISTICS_QUANTITY = 'QUANTITY';
	const STATISTICS_STATUSES = 'STATUSES';
	const STATISTICS_STREAMS = 'STREAMS';
	const STATISTICS_MARKS = 'MARKS';
	const STATISTICS_MONEY = 'MONEY';

	const DEFAULT_SOURCE = 'none';

	public static function registerActivity(array $activity)
	{
		$bindings = static::getCommunicationBindings($activity);
		if (!$bindings || !static::isRegistrationNeedle($activity))
			return true;

		static::updateStatistics($activity, $bindings);
	}

	public static function updateActivity(array $activity, array $prevFields)
	{
		$bindings = static::getCommunicationBindings($activity);
		$prevBindings = static::getCommunicationBindings($prevFields);

		$curNeedle = static::isRegistrationNeedle($activity);
		$prevNeedle = static::isRegistrationNeedle($prevFields);

		if (!$curNeedle && !$prevNeedle && !$bindings && !$prevBindings)
			return true;

		if ($prevNeedle)
		{
			if ($curNeedle)
				$prevBindings = static::getLostBindings($prevBindings, $bindings);
			if ($prevBindings)
			{
				static::updateStatistics($prevFields, $prevBindings);
			}
		}

		if ($curNeedle && $bindings)
			static::updateStatistics($activity, $bindings);
	}
	
	public static function unregisterActivity(array $activity)
	{
		$bindings = static::getCommunicationBindings($activity);
		if (!$bindings || !static::isRegistrationNeedle($activity))
			return true;

		static::updateStatistics($activity, $bindings);
	}

	public static function unregisterByOwner($ownerTypeId, $ownerId)
	{
		$ownerTypeId = (int)$ownerTypeId;
		$ownerId = (int)$ownerId;

		if ($ownerTypeId === \CCrmOwnerType::Company)
		{
			Statistics\CompanyActivityMarkStatisticEntry::unregister($ownerId);
			Statistics\CompanyActivityStatisticEntry::unregister($ownerId);
			Statistics\CompanyActivityStatusStatisticEntry::unregister($ownerId);
			Statistics\CompanyActivityStreamStatisticEntry::unregister($ownerId);
			Statistics\CompanyActivitySumStatisticEntry::unregister($ownerId);

		}
		elseif ($ownerTypeId === \CCrmOwnerType::Contact)
		{
			Statistics\ContactActivityStatisticEntry::unregister($ownerId);
			Statistics\ContactActivityMarkStatisticEntry::unregister($ownerId);
			Statistics\ContactActivityStatusStatisticEntry::unregister($ownerId);
			Statistics\ContactActivityStreamStatisticEntry::unregister($ownerId);
			Statistics\ContactActivitySumStatisticEntry::unregister($ownerId);
		}

		return true;
	}

	public static function synchronizeByOwner($ownerTypeId, $ownerId, $ownerFields = null)
	{
		$ownerTypeId = (int)$ownerTypeId;
		$ownerId = (int)$ownerId;

		if ($ownerTypeId === \CCrmOwnerType::Company)
		{
			Statistics\CompanyActivityMarkStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\CompanyActivityStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\CompanyActivityStatusStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\CompanyActivityStreamStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\CompanyActivitySumStatisticEntry::synchronize($ownerId, $ownerFields);

		}
		elseif ($ownerTypeId === \CCrmOwnerType::Contact)
		{
			Statistics\ContactActivityStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\ContactActivityMarkStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\ContactActivityStatusStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\ContactActivityStreamStatisticEntry::synchronize($ownerId, $ownerFields);
			Statistics\ContactActivitySumStatisticEntry::synchronize($ownerId, $ownerFields);
		}

		return true;
	}
	
	public static function rebuild($ownerTypeId, array $ownerIds)
	{
		$ownerTypeId = (int)$ownerTypeId;
		$data = static::getDataForRebuild($ownerTypeId, $ownerIds);

		foreach ($data as $providerId => $typeData)
		{
			foreach ($typeData as $typeId => $ownerData)
			{
				foreach ($ownerData as $ownerId => $ownerData)
				{
					foreach ($ownerData as $date => $activities)
					{
						if (count($activities) <= 0)
							continue;
						$provider = \CCrmActivity::GetProviderById($providerId);
						$supportedStatistics = $provider? $provider::getSupportedCommunicationStatistics() : array();

						static::register(
							$ownerTypeId,
							$ownerId,
							$supportedStatistics,
							array(
								'DATE'             => new Main\Type\Date($date, 'Y-m-d'),
								'PROVIDER_ID'      => $providerId,
								'PROVIDER_TYPE_ID' => $typeId
							),
							static::mergeActivityResults($activities)
						);
					}
				}
			}
		}

		return true;
	}

	public static function getLoadCurrents($ownerTypeId, $ownerId)
	{
		return static::getLoadAverages($ownerTypeId, $ownerId);
	}

	public static function getLoadMaxValues($ownerTypeId)
	{
		$maxValues = static::getMaxRelatedDealActivities($ownerTypeId);
		$maxValues['*'] = $maxValues ? max($maxValues) : 0;
		return $maxValues;
	}

	/**
	 * Get communication load averages.
	 * @param int $ownerTypeId Owner type id. @see \CCrmOwnerType.
	 * @param null|int $ownerId Owner id.
	 * @return array
	 */
	public static function getLoadAverages($ownerTypeId, $ownerId = null)
	{
		$activities = static::countRelatedDealActivities($ownerTypeId, $ownerId);
		$allActivities = array_sum($activities);

		$deals = static::countRelatedDeals($ownerTypeId, $ownerId);
		$allDeals = array_sum($deals);

		$result = array('*' => $allDeals > 0 ? $allActivities / $allDeals : 0);

		$dealCategories = array_merge(array_keys($activities), array_keys($deals));
		$dealCategories = array_unique($dealCategories);

		foreach ($dealCategories as $categoryId)
		{
			$numerator = isset($activities[$categoryId]) ? $activities[$categoryId] : 0;
			$denominator = isset($deals[$categoryId]) ? $deals[$categoryId] : 0;

			$result[$categoryId] = $denominator > 0 ? $numerator / $denominator : 0;
		}

		return $result;
	}

	private static function updateStatistics($activity, array $bindings)
	{
		$provider = \CCrmActivity::GetActivityProvider($activity);
		$supportedStatistics = $provider? $provider::getSupportedCommunicationStatistics() : array();
		$completed = static::isCompleted($activity);
		$deadline = static::getDeadline($activity);

		$statistics = array();

		if (in_array(static::STATISTICS_STATUSES, $supportedStatistics))
			$statistics[] = static::STATISTICS_STATUSES;
		if (in_array(static::STATISTICS_STREAMS, $supportedStatistics))
			$statistics[] = static::STATISTICS_STREAMS;
		if ($completed && in_array(static::STATISTICS_QUANTITY, $supportedStatistics))
			$statistics[] = static::STATISTICS_QUANTITY;
		if ($completed && in_array(static::STATISTICS_MARKS, $supportedStatistics))
			$statistics[] = static::STATISTICS_MARKS;
		if ($completed && in_array(static::STATISTICS_MONEY, $supportedStatistics))
			$statistics[] = static::STATISTICS_MONEY;

		$countOnly = (count($statistics) === 1 && in_array(static::STATISTICS_QUANTITY, $statistics));

		foreach ($bindings as $ownerTypeId => $ids)
		{
			if ($countOnly)
			{
				$activities = static::getActivitiesCount(
					$ownerTypeId,
					$ids,
					$activity
				);
			}
			else
			{
				$activities = static::getActivities(
					$ownerTypeId,
					$ids,
					$activity
				);
			}

			foreach ($ids as $ownerId)
			{
				if ($ownerId <= 0 || !isset($activities[$ownerId]))
					continue;

				if ($countOnly)
				{
					$results = array(static::STATISTICS_QUANTITY => $activities[$ownerId]);
				}
				else
				{
					$results = static::mergeActivityResults($activities[$ownerId]);
				}

				static::register(
					$ownerTypeId,
					$ownerId,
					$statistics,
					array(
						'DATE'             => $deadline,
						'PROVIDER_ID'      => $provider::getId(),
						'PROVIDER_TYPE_ID' => $provider::getTypeId($activity)
					),
					$results
				);
			}
		}
	}

	private static function register($ownerTypeId, $ownerId, $statistics, $options, $summary)
	{
		if ($ownerTypeId === \CCrmOwnerType::Company)
		{
			if (in_array(static::STATISTICS_QUANTITY, $statistics))
				Statistics\CompanyActivityStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_QUANTITY]
				));
			if (in_array(static::STATISTICS_STATUSES, $statistics))
				Statistics\CompanyActivityStatusStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_STATUSES]
				));
			if (in_array(static::STATISTICS_MARKS, $statistics))
			{
				$sources = $summary[static::STATISTICS_MARKS];
				foreach ($sources as $sourceId => $marks)
				{
					Statistics\CompanyActivityMarkStatisticEntry::register($ownerId, null, array(
						'DATE'             => $options['DATE'],
						'PROVIDER_ID'      => $options['PROVIDER_ID'],
						'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
						'SOURCE_ID'        => $sourceId,
						'VALUE'            => $marks
					));
				}
			}
			if (in_array(static::STATISTICS_STREAMS, $statistics))
				Statistics\CompanyActivityStreamStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_STREAMS]
				));
			if (in_array(static::STATISTICS_MONEY, $statistics))
				Statistics\CompanyActivitySumStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_MONEY]
				));
		}
		elseif ($ownerTypeId === \CCrmOwnerType::Contact)
		{
			if (in_array(static::STATISTICS_QUANTITY, $statistics))
				Statistics\ContactActivityStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_QUANTITY]
				));
			if (in_array(static::STATISTICS_STATUSES, $statistics))
				Statistics\ContactActivityStatusStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_STATUSES]
				));
			if (in_array(static::STATISTICS_MARKS, $statistics))
			{
				$sources = $summary[static::STATISTICS_MARKS];
				foreach ($sources as $sourceId => $marks)
				{
					Statistics\ContactActivityMarkStatisticEntry::register($ownerId, null, array(
						'DATE'             => $options['DATE'],
						'PROVIDER_ID'      => $options['PROVIDER_ID'],
						'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
						'SOURCE_ID'        => $sourceId,
						'VALUE'            => $marks
					));
				}
			}
			if (in_array(static::STATISTICS_STREAMS, $statistics))
				Statistics\ContactActivityStreamStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_STREAMS]
				));
			if (in_array(static::STATISTICS_MONEY, $statistics))
				Statistics\ContactActivitySumStatisticEntry::register($ownerId, null, array(
					'DATE'             => $options['DATE'],
					'PROVIDER_ID'      => $options['PROVIDER_ID'],
					'PROVIDER_TYPE_ID' => $options['PROVIDER_TYPE_ID'],
					'VALUE'            => $summary[static::STATISTICS_MONEY]
				));
		}
	}

	private static function getActivities($ownerTypeId, array $ownerIds, $activity)
	{
		if (count($ownerIds) <= 0)
			return array();

		$date = static::getDeadline($activity);
		
		$startTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime->setTime(23, 59, 59);
		$provider = \CCrmActivity::GetActivityProvider($activity);

		$filter = array(
			'>=DEADLINE' => $startTime,
			'<=DEADLINE' => $endTime,
			'=BINDINGS.OWNER_TYPE_ID' => $ownerTypeId,
			'@BINDINGS.OWNER_ID' => $ownerIds,
		);

		$typeId = \CCrmActivity::GetActivityType($activity);
		if ($typeId !== \CCrmActivityType::Provider)
			$filter['=TYPE_ID'] = $typeId;
		else
		{
			$filter['=PROVIDER_ID'] = $provider::getId();
			$filter['=PROVIDER_TYPE_ID'] = $provider::getTypeId($activity);
		}

		\CTimeZone::Disable();
		$activitiesList = Crm\ActivityTable::getList(array(
			'select' => array(
				'ID', 'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'COMPLETED', 'B_OWNER_ID' => 'BINDINGS.OWNER_ID',
				'RESULT_STATUS', 'RESULT_SOURCE_ID', 'RESULT_MARK', 'RESULT_STREAM', 'RESULT_VALUE', 'RESULT_SUM', 'RESULT_CURRENCY_ID'
			),
			'filter' => $filter
		));
		\CTimeZone::Enable();

		$result = array();
		if ($activitiesList)
		{
			while($activity = $activitiesList->fetch())
			{
				if (!isset($result[$activity['B_OWNER_ID']]))
					$result[$activity['B_OWNER_ID']] = array();

				$result[$activity['B_OWNER_ID']][] = $activity;
			}
		}

		return $result;
	}

	private static function getActivitiesCount($ownerTypeId, array $ownerIds, $activity)
	{
		if (count($ownerIds) <= 0)
			return array();

		$date = static::getDeadline($activity);

		$startTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime->setTime(23, 59, 59);
		$provider = \CCrmActivity::GetActivityProvider($activity);

		$filter = array(
			'=COMPLETED' => 'Y',
			'>=DEADLINE' => $startTime,
			'<=DEADLINE' => $endTime,
			'=BINDINGS.OWNER_TYPE_ID' => $ownerTypeId,
			'@BINDINGS.OWNER_ID' => $ownerIds,
		);

		$typeId = \CCrmActivity::GetActivityType($activity);
		if ($typeId !== \CCrmActivityType::Provider)
			$filter['=TYPE_ID'] = $typeId;
		else
		{
			$filter['=PROVIDER_ID'] = $provider::getId();
			$filter['=PROVIDER_TYPE_ID'] = $provider::getTypeId($activity);
		}

		\CTimeZone::Disable();

		$query = new Query(Crm\ActivityTable::getEntity());
		$query->setFilter($filter);

		$query->registerRuntimeField('', new ExpressionField('CNT', "COUNT(*)"));
		$query->registerRuntimeField('',
			new ReferenceField('BINDINGS',
				Crm\ActivityBindingTable::getEntity(),
				array('=this.ID' => 'ref.ACTIVITY_ID'),
				array('join_type' => 'INNER')
			)
		);

		$query->addSelect('CNT');
		$query->addSelect('BINDINGS.OWNER_ID', 'B_OWNER_ID');
		$query->addGroup('BINDINGS.OWNER_ID');

		$activitiesList = $query->exec();
		\CTimeZone::Enable();

		$result = array();
		if ($activitiesList)
		{
			while($activity = $activitiesList->fetch())
			{
				if (!isset($result[$activity['B_OWNER_ID']]))
					$result[$activity['B_OWNER_ID']] = 0;

				$result[$activity['B_OWNER_ID']] += $activity['CNT'];
			}
		}

		return $result;
	}

	private static function getDataForRebuild($ownerTypeId, array $ownerIds)
	{
		$filter = array(
			'=BINDINGS.OWNER_TYPE_ID' => $ownerTypeId,
			'@BINDINGS.OWNER_ID' => $ownerIds,
		);

		\CTimeZone::Disable();
		$activitiesList = Crm\ActivityTable::getList(array(
			'select' => array(
				'ID', 'TYPE_ID', 'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'B_OWNER_ID' => 'BINDINGS.OWNER_ID', 'COMPLETED', 'DEADLINE',
				'RESULT_STATUS', 'RESULT_SOURCE_ID', 'RESULT_MARK', 'RESULT_STREAM', 'RESULT_VALUE', 'RESULT_SUM', 'RESULT_CURRENCY_ID'
			),
			'filter' => $filter
		));
		\CTimeZone::Enable();

		$data = array();
		while ($activity = $activitiesList->fetch())
		{
			$provider = \CCrmActivity::GetActivityProvider($activity);
			if (!$provider || !$activity['DEADLINE'])
				continue;

			$date = $activity['DEADLINE']->format('Y-m-d');

			$providerId = $provider::getId();
			$typeId = $provider::getTypeId($activity);
			$ownerId = (int)$activity['B_OWNER_ID'];

			if ($ownerId <= 0)
				continue;

			if (!isset($data[$providerId]))
				$data[$providerId] = array();
			if (!isset($data[$providerId][$typeId]))
				$data[$providerId][$typeId] = array();
			if (!isset($data[$providerId][$typeId][$ownerId]))
				$data[$providerId][$typeId][$ownerId] = array();
			if (!isset($data[$providerId][$typeId][$ownerId][$date]))
				$data[$providerId][$typeId][$ownerId][$date] = array();

			$data[$providerId][$typeId][$ownerId][$date][] = $activity;
		}

		return $data;
	}
	
	private static function mergeActivityResults($activities)
	{
		$quantity = 0;
		$statuses = array(
			StatisticsStatus::Answered => 0,
			StatisticsStatus::Unanswered => 0
		);
		$streams = array(
			StatisticsStream::Incoming => 0,
			StatisticsStream::Outgoing => 0,
			StatisticsStream::Reversing => 0,
			StatisticsStream::Missing => 0,
		);
		$marks = array(
			StatisticsMark::None => 0,
			StatisticsMark::Negative => 0,
			StatisticsMark::Positive => 0
		);
		$sources = array();
		$money = array();
		
		foreach ($activities as $activity)
		{
			$status = (int)$activity['RESULT_STATUS'];
			if (isset($statuses[$status]))
				++$statuses[$status];
			$stream = (int)$activity['RESULT_STREAM'];
			if (isset($streams[$stream]))
				++$streams[$stream];
			
			$isCompleted = static::isCompleted($activity);
			if (!$isCompleted)
				continue;
			
			++$quantity;
			$mark = (int)$activity['RESULT_MARK'];
			if (!isset($marks[$mark]))
				$mark = StatisticsMark::None;

			$source = !empty($activity['RESULT_SOURCE_ID']) ? (string)$activity['RESULT_SOURCE_ID'] : static::DEFAULT_SOURCE;

			if (!isset($sources[$source]))
			{
				$sources[$source] = array(
					StatisticsMark::None     => 0,
					StatisticsMark::Negative => 0,
					StatisticsMark::Positive => 0
				);
			}

			++$sources[$source][$mark];
				
			if (!empty($activity['RESULT_SUM']) && !empty($activity['RESULT_CURRENCY_ID']))
			{
				if (!isset($money[$activity['RESULT_CURRENCY_ID']]))
					$money[$activity['RESULT_CURRENCY_ID']] = 0;
				
				$money[$activity['RESULT_CURRENCY_ID']] += $activity['RESULT_SUM'];
			}
		}
		
		return array(
			static::STATISTICS_QUANTITY => $quantity,
			static::STATISTICS_STATUSES => $statuses,
			static::STATISTICS_STREAMS => $streams,
			static::STATISTICS_MARKS => $sources,
			static::STATISTICS_MONEY => $money
		);
	}

	private static function isRegistrationNeedle($activity)
	{
		$provider = \CCrmActivity::GetActivityProvider($activity);
		$supportedStatistics = $provider? $provider::getSupportedCommunicationStatistics() : array();
		$completed = static::isCompleted($activity);
		$deadline = static::getDeadline($activity);

		return (
			$deadline
			&& count($supportedStatistics) > 0
			&& (
				$completed
				|| in_array(static::STATISTICS_STATUSES, $supportedStatistics)
				|| in_array(static::STATISTICS_STREAMS, $supportedStatistics)
			)
		);
	}

	private static function isCompleted($activity)
	{
		return isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y';
	}

	private static function getDeadline($activity)
	{
		$deadline = isset($activity['DEADLINE']) ? $activity['DEADLINE'] : '';
		if ($deadline)
		{
			$deadline = new Main\Type\DateTime($deadline);
			$deadline->setTime(0, 0, 0);
		}
		return $deadline;
	}

	private static function getCommunicationBindings($activity)
	{
		$companies = $contacts = array();
		$bindings = isset($activity['BINDINGS']) && is_array($activity['BINDINGS']) ? $activity['BINDINGS'] : array();

		foreach ($bindings as $binding)
		{
			$ownerTypeId = (int) $binding['OWNER_TYPE_ID'];
			if ($ownerTypeId === \CCrmOwnerType::Company)
				$companies[] = (int)$binding['OWNER_ID'];
			elseif ($ownerTypeId === \CCrmOwnerType::Contact)
				$contacts[] = (int)$binding['OWNER_ID'];
		}

		$companies = array_unique($companies);
		$contacts = array_unique($contacts);

		return $companies || $contacts ? array(
			\CCrmOwnerType::Company => $companies,
			\CCrmOwnerType::Contact => $contacts
		) : false;
	}

	private static function getLostBindings($prevBindings, $curBindings)
	{
		if (!is_array($prevBindings))
			return false;

		if (!is_array($curBindings))
			return $prevBindings;

		$companies = $contacts = array();

		foreach ($prevBindings[\CCrmOwnerType::Company] as $bingingId)
		{
			if (!in_array($bingingId, $curBindings[\CCrmOwnerType::Company]))
				$companies[] = $bingingId;
		}
		foreach ($prevBindings[\CCrmOwnerType::Contact] as $bingingId)
		{
			if (!in_array($bingingId, $curBindings[\CCrmOwnerType::Contact]))
				$contacts[] = $bingingId;
		}

		return $companies || $contacts ? array(
			\CCrmOwnerType::Company => $companies,
			\CCrmOwnerType::Contact => $contacts
		) : false;
	}

	private static function countRelatedDeals($relatedOwnerTypeId, $relatedOwnerId = null)
	{
		if ($relatedOwnerTypeId !== \CCrmOwnerType::Contact && $relatedOwnerTypeId !== \CCrmOwnerType::Company)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($relatedOwnerTypeId);
			throw new Main\NotSupportedException("The '{$entityTypeName}' is not supported in current context");
		}

		$result = array();

		$query = new Query(Crm\DealTable::getEntity());
		$query->registerRuntimeField('', new ExpressionField('CNT', 'COUNT(*)'));

		$query->addSelect('CNT');
		$query->addSelect('CATEGORY_ID');
		$query->addGroup('CATEGORY_ID');

		if ($relatedOwnerTypeId === \CCrmOwnerType::Company)
		{
			if ($relatedOwnerId !== null)
			{
				$query->addFilter('=COMPANY_ID', (int)$relatedOwnerId);
			}
			else
			{
				$query->addFilter('>COMPANY_ID', 0);
			}
		}
		else
		{
			$query->registerRuntimeField('',
				new ReferenceField('DCT',
					Crm\Binding\DealContactTable::getEntity(),
					array('=this.ID' => 'ref.DEAL_ID'),
					array('join_type' => 'INNER')
				)
			);

			if ($relatedOwnerId !== null)
			{
				$query->addFilter('=DCT.CONTACT_ID', (int)$relatedOwnerId);
			}
		}

		$iterator = $query->exec();
		while ($row = $iterator->fetch())
			$result[$row['CATEGORY_ID']] = (int)$row['CNT'];

		return $result;
	}

	private static function countRelatedDealActivities($relatedOwnerTypeId, $relatedOwnerId = null)
	{
		if ($relatedOwnerTypeId !== \CCrmOwnerType::Contact && $relatedOwnerTypeId !== \CCrmOwnerType::Company)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($relatedOwnerTypeId);
			throw new Main\NotSupportedException("The '{$entityTypeName}' is not supported in current context");
		}

		$query = new Query(Crm\ActivityBindingTable::getEntity());

		$query->registerRuntimeField('', new ExpressionField('CNT', 'COUNT(*)'));
		$query->registerRuntimeField('',
			new ReferenceField('DT',
				Crm\DealTable::getEntity(),
				array('=this.OWNER_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			)
		);

		$query->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Deal);

		$query->addSelect('CNT');
		$query->addSelect('DT.CATEGORY_ID', 'CATEGORY_ID');
		$query->addGroup('DT.CATEGORY_ID');

		$query->registerRuntimeField('',
			new ReferenceField('A',
				Crm\ActivityTable::getEntity(),
				array('=this.ACTIVITY_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			)
		);

		$query->addFilter('=A.COMPLETED', 'Y');
		$query->addFilter('!=A.TYPE_ID', \CCrmActivityType::Task);

		$subQuery = new Query(Crm\ActivityBindingTable::getEntity());
		$subQuery->addFilter('=OWNER_TYPE_ID', $relatedOwnerTypeId);
		if ($relatedOwnerId)
			$subQuery->addFilter('=OWNER_ID', $relatedOwnerId);
		$subQuery->addSelect('ACTIVITY_ID');

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.ACTIVITY_ID' => 'ref.ACTIVITY_ID'),
				array('join_type' => 'INNER')
			)
		);

		$result = array();
		$iterator = $query->exec();

		while ($row = $iterator->fetch())
			$result[$row['CATEGORY_ID']] = (int)$row['CNT'];

		return $result;
	}

	private static function getMaxRelatedDealActivities($relatedOwnerTypeId)
	{
		if ($relatedOwnerTypeId !== \CCrmOwnerType::Contact && $relatedOwnerTypeId !== \CCrmOwnerType::Company)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($relatedOwnerTypeId);
			throw new Main\NotSupportedException("The '{$entityTypeName}' is not supported in current context");
		}

		$query = new Query(Crm\DealTable::getEntity());

		$query->addSelect('CATEGORY_ID');
		//$query->addSelect('ID');
		$query->addGroup('CATEGORY_ID');

		$subQuery = new Query(Crm\ActivityBindingTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('CNT', 'COUNT(*)'));

		$subQuery->addSelect('OWNER_ID');
		$subQuery->addSelect('CNT');

		$subQuery->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Deal);

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.OWNER_ID'),
				array('join_type' => 'INNER')
			)
		);

		$query->registerRuntimeField('', new ExpressionField('MAX', 'MAX(%s)', 'M.CNT'));
		$query->addSelect('MAX');

		$result = array();
		$iterator = $query->exec();

		while ($row = $iterator->fetch())
			$result[$row['CATEGORY_ID']] = (int)$row['MAX'];

		return $result;
	}

}