<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Ad\Index;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);



/**
 * Class TraceLinker.
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
final class TraceLinker extends Tracking\Ad\Builder
{
	private const fetchLimit = 100;

	/**
	 * Get complete label.
	 *
	 * @return string|null
	 */
	public function getCompleteLabel()
	{
		if ($this->isComplete())
		{
			return null;
		}

		return Loc::getMessage('CRM_TRACKING_AD_INDEX_TRACE_LINKER_LABEL');
	}


	protected function isBuilt()
	{
		return !$this->getTraceSources(1);
	}

	protected function getTraceSources($limit = null)
	{
		return Tracking\Internals\TraceSourceTable::getList([
			'select' => ['ID', 'LEVEL', 'CODE'],
			'filter' => [
				'=TRACE.SOURCE_ID' => $this->sourceId,
				'>=TRACE.DATE_CREATE' => $this->dateFrom,
				'<=TRACE.DATE_CREATE' => (clone $this->dateTo)->add('+1 day'),
				'=SOURCE_CHILD_ID' => 0,
				'=PROCESSED' => 0,
			],
			'limit' => $limit ?: static::fetchLimit,
		])->fetchAll();
	}

	protected function build()
	{
		$rows = $this->getTraceSources();
		if (!$rows)
		{
			return;
		}


		$levels = [];
		foreach ($rows as $row)
		{
			$level = (int) $row['LEVEL'];
			$levels[$level][$row['ID']] = $row['CODE'];
		}

		foreach ($levels as $level => $rows)
		{
			$children = Tracking\Internals\SourceChildTable::getList([
				'select' => ['ID', 'CODE'],
				'filter' => [
					'=SOURCE_ID' => $this->sourceId,
					'=LEVEL' => $level,
					'=CODE' => array_values($rows),
				],
			])->fetchAll();
			$children = array_combine(
				array_column($children, 'CODE'),
				array_column($children, 'ID')
			);

			foreach ($rows as $traceSourceId => $code)
			{
				$childId = $children[$code] ?? 0;
				Tracking\Internals\TraceSourceTable::update($traceSourceId, [
					'SOURCE_CHILD_ID' => $childId,
					'PROCESSED' => 1,
				]);
			}
		}

	}
}