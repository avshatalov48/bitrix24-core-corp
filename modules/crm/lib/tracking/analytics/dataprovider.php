<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Tracking;

/**
 * Class DataProvider
 *
 * @package Bitrix\Crm\Tracking\Analytics
 */
class DataProvider
{
	protected $filter = [];
	protected $group = [];
	protected $dateFrom;
	protected $dateTo;
	/** @var int[]|null $sourceId */
	protected $sourceId;

	public function __construct()
	{
		$this->groupByAssigned(false);
		$this->groupByTrackingSource(true);
	}

	public function addFilter($key, $value, $operation = '=')
	{
		$this->filter[$operation . $key] = $value;
		return $this;
	}

	public function setPeriod($from, $to)
	{
		$key = '>=' . Provider\Base::DateCreate;
		unset($this->filter[$key]);
		if ($from)
		{
			$this->filter[$key] = $from;
		}

		$key = '<=' . Provider\Base::DateCreate;
		unset($this->filter[$key]);
		if ($to)
		{
			$this->filter[$key] = $to;
		}

		$this->dateFrom = $from;
		$this->dateTo = $to;

		return $this;
	}

	public function setSourceId($sourceId)
	{
		if (is_array($sourceId))
		{
			$sourceId = array_map('intval', $sourceId);
			$sourceId = array_filter($sourceId);
		}
		else
		{
			$sourceId = [];
		}
		$this->sourceId = $sourceId;
		unset($this->filter['=' . Provider\Base::TrackingSourceId]);
		if (!empty($sourceId))
		{
			$this->filter['=' . Provider\Base::TrackingSourceId] = $this->sourceId;
		}
		return $this;
	}

	public function groupByAssigned($state = true)
	{
		return $this->groupBy(Provider\Base::Assigned, $state);
	}

	public function groupByTrackingSource($state = true)
	{
		return $this->groupBy(Provider\Base::TrackingSourceId, $state);
	}

	private function groupBy($key, $value = true)
	{
		if (!$value)
		{
			$pos = array_search($key, $this->group);
			if ($pos !== false)
			{
				unset($this->group[$pos]);
			}
		}
		elseif (!in_array($key, $this->group))
		{
			$this->group[] = $key;
		}

		return $this;
	}

	/**
	 * @return Provider\Base[]
	 */
	public function getProviders()
	{
		$list = [];

		if (Tracking\Provider::hasReadyAdSources())
		{
			$list[] = (new Provider\Action($this->filter, $this->group));
		}

		if (LeadSettings::getCurrent()->isEnabled())
		{
			$list[] = new Provider\Lead($this->filter, $this->group);
		}

		$list[] = new Provider\Deal($this->filter, $this->group);
		$list[] = new Provider\Order($this->filter, $this->group);

		$list[] = new Provider\CompleteDeal($this->filter, $this->group);
		$list[] = new Provider\CompleteOrder($this->filter, $this->group);

		foreach ($list as $item)
		{
			/** @var Provider\Base $item */
			$item->setPeriod($this->dateFrom, $this->dateTo)
				->setSourceId($this->sourceId);
		}

		return $list;
	}

	/**
	 * @return Provider\Base[]
	 */
	public function isCostable()
	{
		return current($this->getProviders())->isCostable();
	}
}