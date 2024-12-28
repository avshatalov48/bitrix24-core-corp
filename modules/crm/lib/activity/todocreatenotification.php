<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class TodoCreateNotification
{
	private const OPTION_NAME_PREFIX = 'skip_todo_notification';

	private const SKIP_PERIOD_DAY = 'day';
	private const SKIP_PERIOD_WEEK = 'week';
	private const SKIP_PERIOD_MONTH = 'month';
	private const SKIP_PERIOD_FOREVER = 'forever';

	private int $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function isSkipped(): bool
	{
		$value = \CUserOptions::GetOption('crm', $this->getOptionName(), '');

		if ($value === '' || !str_contains($value, '.'))
		{
			$skipFrom = 0;
			$period = '';
		}
		else
		{
			[$period, $skipFrom] = explode('.', $value, 2);
		}
		if (!$this->isPeriodExists($period))
		{
			\CUserOptions::DeleteOption('crm', $this->getOptionName());

			return false;
		}

		if ($period === self::SKIP_PERIOD_FOREVER)
		{
			return true;
		}

		$skipFrom = (int)$skipFrom;
		$skipFromDate = DateTime::createFromTimestamp($skipFrom);
		$skipFromDate->toUserTime();
		$now = (new DateTime())->toUserTime();

		$isSkipped = false;
		switch ($period)
		{
			case self::SKIP_PERIOD_DAY:
				$isSkipped =
					$skipFromDate->format('j') === $now->format('j')
					&& $now->getTimestamp() - $skipFromDate->getTimestamp() < 60*60*24
				;
				break;
			case self::SKIP_PERIOD_WEEK:
				$isSkipped =
					$skipFromDate->format('W') === $now->format('W')
					&& $now->getTimestamp() - $skipFromDate->getTimestamp() < 60*60*24*7
				;
				break;
			case self::SKIP_PERIOD_MONTH:
				$isSkipped =
					$skipFromDate->format('n') === $now->format('n')
					&& $now->getTimestamp() - $skipFromDate->getTimestamp() < 60*60*24*7*31
				;
				break;
		}

		if (!$isSkipped)
		{
			\CUserOptions::DeleteOption('crm', $this->getOptionName());
		}

		return $isSkipped;
	}

	public function getCurrentSkipPeriod(): ?string
	{
		if (!$this->isSkipped())
		{
			return null;
		}

		$value = \CUserOptions::GetOption('crm', $this->getOptionName(), '');
		if ($value === '')
		{
			return null;
		}
		[$period, $skipTo] = explode('.', $value, 2);

		if (!$this->isPeriodExists($period))
		{
			return null;
		}

		return $period;
	}
	
	public function skipForPeriod(string $period): Result
	{
		$result = new Result();

		if (!$this->isPeriodExists($period))
		{
			\CUserOptions::DeleteOption('crm', $this->getOptionName());

			return $result;
		}
		$value = $period . '.' . (new DateTime())->getTimestamp();
		\CUserOptions::SetOption('crm', $this->getOptionName(), $value);

		return $result;
	}

	private function isPeriodExists(string $period): bool
	{
		return (in_array($period, [
			self::SKIP_PERIOD_DAY,
			self::SKIP_PERIOD_WEEK,
			self::SKIP_PERIOD_MONTH,
			self::SKIP_PERIOD_FOREVER,
		]));
	}

	private function getOptionName(): string
	{
		return self::OPTION_NAME_PREFIX . '_' . \CCrmOwnerType::ResolveName($this->entityTypeId);
	}
}
