<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Orm;
use Bitrix\Main\Config;
use Bitrix\Main\Data\Cache;

use Bitrix\Crm\Tracking;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
abstract class Base
{
	const CacheTtl = 30;

	protected $filter = [];
	protected $group = [];
	protected $data = null;
	/** @var Date|null $dateFrom */
	protected $dateFrom;
	/** @var Date|null $dateFrom */
	protected $dateTo;

	const Assigned = 'ASSIGNED_BY_ID';
	const TrackingSourceId = 'TRACKING_SOURCE_ID';
	const DateCreate = 'DATE_CREATE';

	abstract public function getCode();

	abstract public function query();

	abstract public function getPath();

	public function __construct($filter, $group)
	{
		$this->filter = $filter;
		$this->group = $group;
	}

	public function getName()
	{
		return Loc::getMessage('CRM_TRACKING_ANALYTICS_PROVIDER_NAME_' . str_replace(
			'-',
			'_',
			strtoupper($this->getCode())
		));
	}

	public function getData()
	{
		if ($this->data === null)
		{
			$dateFrom = clone $this->dateFrom;
			if ($dateFrom instanceof DateTime)
			{
				$dateFrom->setTime(0, 0, 0);
			}
			$dateTo = clone $this->dateTo;
			if ($dateTo instanceof DateTime)
			{
				$dateTo->setTime(0, 0, 0);
			}

			$cacheDir = '/crm/tracking/data/provider';
			$cacheTtl = (int) Config\Option::get('crm', 'crm_tracking_actions_cache_ttl') ?: self::CacheTtl;
			$cacheId = $this->getCode()
				. '|' . serialize($this->filter)
				. '|' . serialize($this->group);
			$cache = Cache::createInstance();
			if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
			{
				$this->data = $cache->getVars()['data'];
			}
			else
			{
				$hasData = false;
				$this->data = [];
				foreach ($this->query() as $row)
				{
					if (is_numeric($row[self::TrackingSourceId]) || strlen($row[self::TrackingSourceId]) == 0)
					{
						$row[self::TrackingSourceId] = (int) $row[self::TrackingSourceId];
					}
					$this->data[] = $row;

					$hasData = $hasData || !empty($row['SUM']) || !empty($row['CNT']);
				}

				if ($hasData)
				{
					$cache->startDataCache();
					$cache->endDataCache(['data' => $this->data]);
				}
			}
		}

		return $this->data;
	}

	public function isCostable()
	{
		return false;
	}

	protected function isGroupedByAssigned()
	{
		return in_array(self::Assigned, $this->group);
	}

	protected function isGroupedByTrackingSource()
	{
		return in_array(self::TrackingSourceId, $this->group);
	}

	private function prepareQuery(Orm\Query\Query $query, $entityTypeId)
	{
		$query->setSelect(['CNT', 'SUM', 'ACCOUNT_CURRENCY_ID']);
		if ($this->isGroupedByAssigned())
		{
			$query->addSelect(self::Assigned);
		}
		if ($this->isGroupedByTrackingSource())
		{
			$query->addSelect(
				'TRACE_ENTITY.TRACE.SOURCE_ID',
				self::TrackingSourceId
			);
		}
		else
		{
			$query->addFilter('>TRACE_ENTITY.TRACE.SOURCE_ID', 0);
			$query->registerRuntimeField(new Orm\Fields\ExpressionField(
				self::TrackingSourceId, '\'summary\''
			));
			$query->addSelect(self::TrackingSourceId);
		}

		$query->registerRuntimeField(new Orm\Fields\ExpressionField(
			'CNT', 'COUNT(*)'
		));

		$query->registerRuntimeField(new Orm\Fields\ExpressionField(
			'SUM', 'SUM(%s)', ['OPPORTUNITY_ACCOUNT']
		));

		$query->registerRuntimeField(new Orm\Fields\Relations\Reference(
			'TRACE_ENTITY',
			Tracking\Internals\TraceEntityTable::class,
			[
				'=ref.ENTITY_TYPE_ID' => new SqlExpression('?', $entityTypeId),
				'=this.ID' => 'ref.ENTITY_ID'
			]
		));

		foreach ($this->filter as $key => $value)
		{
			$newKey = str_replace(
				[self::TrackingSourceId],
				['TRACE_ENTITY.TRACE.SOURCE_ID'],
				$key
			);
			unset($this->filter[$key]);
			$this->filter[$newKey] = $value;
		}

		$query->setFilter($query->getFilter() + $this->filter);

		return $query;
	}

	protected function performQuery(Orm\Query\Query $query, $entityTypeId)
	{
		$this->prepareQuery($query, $entityTypeId);

		$r = $query->exec();
		return $r->fetchAll();
	}

	public function setPeriod($from, $to)
	{
		$this->dateFrom = $from;
		$this->dateTo = $to;

		return $this;
	}
}