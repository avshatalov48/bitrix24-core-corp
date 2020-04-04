<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Crm\Tracking;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Orm;

/**
 * Class Action
 *
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
class Action extends Base
{
	const CODE = 'actions';

	public function getCode()
	{
		return static::CODE;
	}

	public function isCostable()
	{
		return true;
	}

	public function getPath()
	{
		return null;
	}

	public function query()
	{
		/*
		$defaultItem = [
			'CNT' => 0,
			'SUM' => 0,
			'VIEWS' => 0,
			'ASSIGNED_BY_ID' => null,
			'TRACKING_SOURCE_ID' => null,
		];
		*/

		$dateFrom = $this->dateFrom ? Date::createFromTimestamp($this->dateFrom->getTimestamp()) : null;
		$dateTo = $this->dateTo ? Date::createFromTimestamp($this->dateTo->getTimestamp()) : null;

		$list = static::getAdExpenses($dateFrom, $dateTo);
		foreach (static::getUserExpenses($dateFrom, $dateTo) as $row)
		{
			$isFound = false;
			foreach ($list as $index => $item)
			{
				if ($item['TRACKING_SOURCE_ID'] != $row['TRACKING_SOURCE_ID'])
				{
					continue;
				}

				$item['CNT'] += $row['CNT'];
				$item['SUM'] += $row['SUM'];
				$list[$index] = $item;
				$isFound = true;
			}

			if (!$isFound)
			{
				$list[] = $row;
			}
		}

		if (!$this->isGroupedByTrackingSource())
		{
			$summary = [
				'CNT' => 0,
				'SUM' => 0,
				'ASSIGNED_BY_ID' => null,
				'TRACKING_SOURCE_ID' => 'summary',
			];
			foreach ($list as $row)
			{
				$summary['CNT'] += (int) $row['CNT'];
				$summary['SUM'] += (int) $row['SUM'];
			}
			$list = [$summary];
		}

		return $list;
	}

	public static function getUserExpenses(Date $dateFrom, Date $dateTo)
	{
		$list = [];
		$listBySource = [];
		$sources = Tracking\Provider::getActualSources();
		$sourceIds = array_column($sources, 'ID');
		$sources = array_combine($sourceIds, $sources);
		$sourceIds = array_filter(
			$sourceIds,
			function ($item)
			{
				return !empty($item);
			}
		);

		$sourcesWithMoney = [];
		$rows = Tracking\Internals\SourceExpensesTable::getList([
			'select' => ['CNT', 'SUM', 'CURRENCY_ID', 'SOURCE_ID'],
			'filter' => [
				'=SOURCE_ID' => $sourceIds,
				'>=DATE_STAT' => $dateFrom,
				'<=DATE_STAT' => $dateTo,
			],
			'runtime' => [
				new Orm\Fields\ExpressionField('SUM', 'SUM(%s)', ['EXPENSES']),
				new Orm\Fields\ExpressionField('CNT', 'SUM(%s)', ['ACTIONS'])
			],
			'group' => ['CURRENCY_ID', 'SOURCE_ID']
		]);
		foreach ($rows as $row)
		{
			$sourceId = $row['SOURCE_ID'];
			if (!is_array($listBySource[$sourceId]))
			{
				$listBySource[$sourceId] = [];
			}

			$listBySource[$sourceId][] = [
				'CNT' => $row['CNT'],
				'SUM' => \CCrmCurrency::convertMoney(
					$row['SUM'],
					$row['CURRENCY_ID'],
					\CCrmCurrency::getAccountCurrencyID()
				)
			];

			if (!Tracking\Analytics\Ad::isSupported($sources[$sourceId]['CODE']))
			{
				$sourcesWithMoney[] = $sourceId;
			}
		}

		$sourcesWithMoney = array_unique($sourcesWithMoney);
		$traces = Tracking\Internals\TraceTable::getList([
			'select' => ['SOURCE_ID', 'TRACE_CNT'],
			'filter' => [
				'>=DATE_CREATE' => $dateFrom,
				'<=DATE_CREATE' => $dateTo,
				'=SOURCE_ID' => $sourcesWithMoney,
			],
			'runtime' => [
				new Orm\Fields\ExpressionField('TRACE_CNT', 'COUNT(%s)', ['ID'])
			],
			'group' => ['SOURCE_ID']
		])->fetchAll();
		$traces = array_combine(
			array_column($traces, 'SOURCE_ID'),
			array_column($traces, 'TRACE_CNT')
		);

		foreach ($listBySource as $sourceId => $rows)
		{
			$cnt = isset($traces[$sourceId]) ? (int) $traces[$sourceId] : 0;
			$sum = 0;

			foreach ($rows as $row)
			{
				$cnt += $row['CNT'];
				$sum += $row['SUM'];
			}

			$list[] = [
				'CNT' => $cnt,
				'SUM' => $sum,
				'VIEWS' => 0,
				'ASSIGNED_BY_ID' => null,
				'TRACKING_SOURCE_ID' => $sourceId,
			];
		}

		return $list;
	}

	private static function getAdExpenses($dateFrom, $dateTo)
	{
		$list = [];
		foreach (Tracking\Provider::getActualAdSources() as $source)
		{
			$ad = new Tracking\Analytics\Ad($source['CODE']);
			if (!$ad->isConnected())
			{
				continue;
			}

			$expenses = $ad->getExpenses($dateFrom, $dateTo);

			/*
			$expenses = [
				'actions' => 875,
				'spend' => 7883.62,
			];
			*/

			$list[] = [
				'CNT' => $expenses['actions'] ?: 0,
				'SUM' => $expenses['spend'] ?: 0,
				'VIEWS' => $expenses['impressions'] ?: 0,
				'ASSIGNED_BY_ID' => null,
				'TRACKING_SOURCE_ID' => $source['ID'],
			];
		}

		return $list;
	}
}