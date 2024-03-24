<?php

namespace Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;

class DateTime extends UrlFilter
{
	public const CONFIG_PERIOD_OPTION_NAME = 'bi_superset_dashboard_period';
	public const CONFIG_DATE_START_OPTION_NAME = 'bi_superset_dashboard_default_date_start';
	public const CONFIG_DATE_END_OPTION_NAME = 'bi_superset_dashboard_default_date_end';

	public const PERIOD_WEEK = 'week';
	public const PERIOD_MONTH = 'month';
	public const PERIOD_QUARTER = 'quarter';
	public const PERIOD_HALF_YEAR = 'halfyear';
	public const PERIOD_YEAR = 'year';
	public const PERIOD_RANGE = 'range';

	public const PERIOD_DEFAULT = 'default';

	private ?string $period;
	private bool $hasDefaultPeriod = false;
	private ?Date $from;
	private ?Date $to;

	public function __construct(private Dashboard $dashboard)
	{
		$this->period = $dashboard->getOrmObject()->getFilterPeriod();
		if (empty($this->period))
		{
			$this->hasDefaultPeriod = true;
			$this->period = self::getDefaultPeriod();
		}

		if ($this->isRange())
		{
			$this->from = $dashboard->getOrmObject()->getDateFilterStart();
			if ($this->from === null)
			{
				$this->from = self::getDefaultDateStart();
			}

			$this->to = $dashboard->getOrmObject()->getDateFilterEnd();
			if ($this->to === null)
			{
				$this->to = self::getDefaultDateEnd();
			}
		}
		else
		{
			$this->to = new Date();

			$interval = '-1 year';

			if ($this->period === self::PERIOD_WEEK)
			{
				$interval = '-1 week';
			}
			elseif ($this->period === self::PERIOD_MONTH)
			{
				$interval = '-1 month';
			}
			elseif ($this->period === self::PERIOD_QUARTER)
			{
				$interval = '-3 month';
			}
			elseif ($this->period === self::PERIOD_HALF_YEAR)
			{
				$interval = '-6 month';
			}

			$this->from = clone($this->to);
			$this->from->add($interval);
		}
	}

	public function hasDefaultFilter(): bool
	{
		return $this->hasDefaultPeriod;
	}

	private function isRange(): bool
	{
		return $this->period === self::PERIOD_RANGE;
	}

	public function getCode(): string
	{
		$config = $this->dashboard->getNativeFiltersConfig();
		$timeFilter = array_filter($config, static fn($item) => $item['filterType'] === 'filter_time');
		$timeFilter = array_pop($timeFilter);

		return (string)$timeFilter['id'];
	}

	public function getFormatted(): string
	{
		$from = clone($this->from);
		$from = $from->format('Y-m-d');

		$to = clone($this->to);
		$to = $to->format('Y-m-d');

		$urlTemplateFilter = '(
			#FILTER_ID#:(
				extraFormData:(
					time_range:\'#DATE_FROM#+:+#DATE_TO#\'
				),
				filterState:(
					value:\'#DATE_FROM#+:+#DATE_TO#\'
				),
				id:#FILTER_ID#,
				ownState:()
			)
		)';

		return strtr(
			$urlTemplateFilter,
			[
				'#FILTER_ID#' => $this->getCode(),
				'#DATE_FROM#' => $from,
				'#DATE_TO#' => $to,
			]
		);
	}

	public function getPeriod(): string
	{
		return $this->period;
	}

	public static function getPeriodName(string $name): string
	{
		$name = strtoupper($name);

		return Loc::getMessage("BICONNECTOR_SUPERSET_EMBEDDED_FILTER_RANGE_{$name}") ?? '';
	}

	public function getDateEnd(): string
	{
		return $this->to;
	}

	public function getDateStart(): string
	{
		return $this->from;
	}

	public static function getDefaultPeriod(): string
	{
		$defaultValue = self::PERIOD_YEAR;
		$value = Option::get('biconnector', self::CONFIG_PERIOD_OPTION_NAME, $defaultValue);
		if (self::isAvailablePeriod($value))
		{
			return $value;
		}

		return $defaultValue;
	}

	public static function getDefaultDateEnd(): Date
	{
		$defaultValue = (new Date())->add('-1 day');
		$option = Option::get('biconnector', self::CONFIG_DATE_END_OPTION_NAME, $defaultValue->toString());
		$value = new Date($option);

		$today = new Date();
		if ($value->getTimestamp() > $today->getTimestamp())
		{
			return $defaultValue;
		}

		return $value;
	}

	public static function getDefaultDateStart(): Date
	{
		$dateEnd = self::getDefaultDateEnd();
		$defaultValue = clone($dateEnd);
		$defaultValue->add('-1 year');
		$option = Option::get('biconnector', self::CONFIG_DATE_START_OPTION_NAME, $defaultValue->toString());
		$value = new Date($option);
		if ($value->getTimestamp() > $dateEnd->getTimestamp())
		{
			return $defaultValue;
		}

		return $value;
	}

	public static function isAvailablePeriod(string $period): bool
	{
		return in_array(
			$period,
			[
				self::PERIOD_WEEK,
				self::PERIOD_MONTH,
				self::PERIOD_QUARTER,
				self::PERIOD_HALF_YEAR,
				self::PERIOD_YEAR,
				self::PERIOD_RANGE,
			],
		true
		);
	}
}