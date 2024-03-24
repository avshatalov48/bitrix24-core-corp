<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

final class DashboardPeriodFilterField extends PeriodFilterField
{
	public const FIELD_ENTITY_EDITOR_TYPE = 'dashboardTimePeriod';

	private Dashboard $dashboard;

	public function __construct(string $id, Dashboard $dashboard)
	{
		parent::__construct($id);

		$this->dashboard = $dashboard;
	}

	public function getFieldInitialData(): array
	{
		$filter = new EmbeddedFilter\DateTime($this->dashboard);

		$filterPeriod = $filter->getPeriod();
		if ($filter->hasDefaultFilter())
		{
			$filterPeriod = EmbeddedFilter\DateTime::PERIOD_DEFAULT;
		}

		return [
			'DATE_FILTER_START' => $filter->getDateStart(),
			'DATE_FILTER_END' => $filter->getDateEnd(),
			'FILTER_PERIOD' => $filterPeriod,
		];
	}

	protected static function getPeriodList(): array
	{
		$commonList = parent::getPeriodList();
		$defaultFilterData = self::getDefaultFilterData();
		$defaultFilterName = EmbeddedFilter\DateTime::getPeriodName($defaultFilterData['FILTER_PERIOD']);
		if ($defaultFilterData['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_RANGE)
		{
			$startDate = new Date($defaultFilterData['DATE_FILTER_START']);
			$endDate = new Date($defaultFilterData['DATE_FILTER_END']);

			$defaultFilterName = "{$startDate->toString()} - {$endDate->toString()}";
		}

		$defaultFilterName = "<span class='ui-color-light biconnector-default-filter-prefix'>({$defaultFilterName})</span>";


		$defaultFilter = [
			'NAME' => Loc::getMessage('DASHBOARD_PERIOD_FILTER_FIELD_DEFAULT_PERIOD', [
				'#PERIOD_NAME#' => $defaultFilterName,
				'#DEFAULT_PREFIX#' => Loc::getMessage('DASHBOARD_PERIOD_FILTER_FIELD_DEFAULT_PREFIX'),
			]),
			'VALUE' => EmbeddedFilter\DateTime::PERIOD_DEFAULT,
		];

		return [
			...$commonList,
			$defaultFilter,
		];
	}

	public function getFieldInfoData(): array
	{
		$infoData = parent::getFieldInfoData();
		$infoData['isHtml'] = true;

		return $infoData;
	}
}
