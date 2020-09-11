<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Timeman\Component\SchedulePlan\TimemanWorktimeGridComponent;

CBitrixComponent::includeComponentClass('bitrix:timeman.worktime.grid');

class TimemanWorktimeExportExcel extends TimemanWorktimeGridComponent
{
	protected $gridId = 'TM_WORKTIME_STATS_GRID';

	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function onPrepareComponentParams($params)
	{
		$params = parent::onPrepareComponentParams($params);

		return $params;
	}

	public function executeComponent()
	{
		if (!$this->prepareData())
		{
			return null;
		}

		$this->prepareDataToExcel();

		$this->includeComponentTemplate();

		return [
			'PROCESSED_ITEMS' => $this->getLimit($this->getCurrentPage()),
			'TOTAL_ITEMS' => $this->arParams['STEXPORT_TOTAL_ITEMS']
		];
	}

	protected function setTotalCount($departmentsToUsersMap)
	{
		parent::setTotalCount($departmentsToUsersMap);

		if ($this->arParams['PAGE_NUMBER'] == 1)
		{
			$this->arParams['STEXPORT_TOTAL_ITEMS'] = $this->arResult['TOTAL_USERS_COUNT'];
		}
	}

	protected function getNavigationData(): array
	{
		$currentPage = $this->getCurrentPage();
		return [
			$this->getLimit($currentPage),
			$currentPage
		];
	}

	private function getCurrentPage(): int
	{
		if (isset($this->arParams['PAGE_NUMBER']))
		{
			return (int) $this->arParams['PAGE_NUMBER'];
		}
		else
		{
			return $this->getGrid()->getNavigation()->getCurrentPage();
		}
	}

	private function getLimit(int $currentPage): int
	{
		$pageSize = $this->getPageSize();
		$total = (int) $this->arParams['STEXPORT_TOTAL_ITEMS'];
		$processed = ($currentPage - 1) * $pageSize;
		if ($total - $processed <= $pageSize)
		{
			return $total - $processed;
		}
		return $pageSize;
	}

	private function getPageSize(): int
	{
		return !empty($this->arParams['STEXPORT_PAGE_SIZE']) ?
			$this->arParams['STEXPORT_PAGE_SIZE'] : $this->getGrid()->getNavigation()->getPageSize();
	}

	private function prepareDataToExcel()
	{
		foreach ($this->arResult['ROWS'] as &$row)
		{
			foreach ($row['columns'] as &$value)
			{
				$value = strip_tags($value);
			}
		}
	}
}