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
	public const CONFIG_INCLUDE_LAST_FILTER_DATE_OPTION_NAME = 'bi_superset_dashboard_default_include_last_filter_date';

	public const PERIOD_RANGE = 'range';

	public const PERIOD_LAST_7 = 'last_7';
	public const PERIOD_LAST_30 = 'last_30';
	public const PERIOD_LAST_90 = 'last_90';
	public const PERIOD_LAST_180 = 'last_180';
	public const PERIOD_LAST_365 = 'last_365';
	public const PERIOD_CURRENT_MONTH = 'current_month';
	public const PERIOD_CURRENT_WEEK = 'current_week';
	public const PERIOD_CURRENT_YEAR = 'current_year';

	public const PERIOD_DEFAULT = 'default';
	public const PERIOD_NONE = 'none';

	private ?string $period;
	private bool $hasDefaultPeriod = false;
	private ?Date $from;
	private ?Date $to;
	private bool $needIncludeLastFilterDate = false;

	public function __construct(private readonly Dashboard $dashboard)
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

			$includeLastFilterDate = $dashboard->getOrmObject()->getIncludeLastFilterDate();
			if ($includeLastFilterDate)
			{
				$this->needIncludeLastFilterDate = $includeLastFilterDate === 'Y';
			}
			else
			{
				$this->needIncludeLastFilterDate = self::needIncludeDefaultLastFilterDate();
			}
		}
		elseif ($this->isCurrentRange())
		{
			$this->from = new Date();
			$this->to = new Date();

			$currentYear = (int)(new Date())->format('Y');
			$currentMonth = (int)(new Date())->format('m');

			if ($this->period === self::PERIOD_CURRENT_MONTH)
			{
				$nextMonth = ($currentMonth + 1) % 12;
				$nextMonth = $nextMonth === 0 ? 12 : $nextMonth;
				$nextYear = $nextMonth === 1 ? $currentYear + 1 : $currentYear;

				$this->from->setDate($currentYear, $currentMonth, 1);
				$this->to->setDate($nextYear, $nextMonth, 1)->add('-1 day');
			}
			elseif ($this->period === self::PERIOD_CURRENT_YEAR)
			{
				$this->from->setDate($currentYear, 1, 1);
				$this->to->setDate($currentYear, 12, 31);
			}
			elseif ($this->period === self::PERIOD_CURRENT_WEEK)
			{
				$weekDay = (int)(new Date())->format('w');
				$weekDay = $weekDay === 0 ? 7 : $weekDay;

				$this->from->add('-' . ($weekDay - 1) . ' days');
				$this->to->add('+' . (7 - $weekDay) . ' days');
			}

			$this->to->add('+1 day');
		}
		elseif ($this->isNone())
		{
			$this->from = null;
			$this->to = null;
		}
		else
		{
			$this->to = new Date();

			$interval = '-1 year';

			if ($this->period === self::PERIOD_LAST_7)
			{
				$interval = '-7 days';
			}
			elseif ($this->period === self::PERIOD_LAST_30)
			{
				$interval = '-30 days';
			}
			elseif ($this->period === self::PERIOD_LAST_90)
			{
				$interval = '-90 days';
			}
			elseif ($this->period === self::PERIOD_LAST_180)
			{
				$interval = '-180 days';
			}
			elseif ($this->period === self::PERIOD_LAST_365)
			{
				$interval = '-365 days';
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

	private function isCurrentRange(): bool
	{
		return in_array($this->period, [
			self::PERIOD_CURRENT_YEAR,
			self::PERIOD_CURRENT_MONTH,
			self::PERIOD_CURRENT_WEEK,
		]);
	}

	private function isNone(): bool
	{
		return $this->period === self::PERIOD_NONE;
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
		if ($this->isNone())
		{
			return '';
		}

		$from = clone($this->from);
		$from = $from->format('Y-m-d');

		$to = clone($this->to);
		if ($this->isRange() && $this->needIncludeLastFilterDate())
		{
			$to->add('+1 day');
		}
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

	public function getDateEnd(): ?string
	{
		return $this->to;
	}

	public function getDateStart(): ?string
	{
		return $this->from;
	}

	public function needIncludeLastFilterDate(): bool
	{
		return $this->needIncludeLastFilterDate;
	}

	public static function getDefaultPeriod(): string
	{
		$defaultValue = self::PERIOD_LAST_365;
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

	public static function needIncludeDefaultLastFilterDate(): bool
	{
		return Option::get('biconnector', self::CONFIG_INCLUDE_LAST_FILTER_DATE_OPTION_NAME, 'N') === 'Y';
	}

	public static function isAvailablePeriod(string $period): bool
	{
		return in_array(
			$period,
			[
				self::PERIOD_LAST_7,
				self::PERIOD_LAST_30,
				self::PERIOD_LAST_90,
				self::PERIOD_LAST_180,
				self::PERIOD_LAST_365,
				self::PERIOD_RANGE,
				self::PERIOD_CURRENT_WEEK,
				self::PERIOD_CURRENT_MONTH,
				self::PERIOD_CURRENT_YEAR,
				self::PERIOD_NONE,
			],
		true
		);
	}
}