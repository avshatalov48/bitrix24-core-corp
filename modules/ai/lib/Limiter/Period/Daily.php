<?php

namespace Bitrix\AI\Limiter\Period;

use Bitrix\AI\Context;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Limiter\Plan;
use Bitrix\AI\Model\UsageTable;
use Bitrix\Main\Type\DateTime;

class Daily implements IPeriod
{
	private const MAX_IN_PERIOD = 5;
	private const PERIOD_FORMAT = 'Y-m-d';

	/**
	 * @inheritDoc
	 */
	public function __construct(
		private Context $context,
		private ?string $time = null,
	) {}

	/**
	 * @inheritDoc
	 */
	public function getCode(): string
	{
		return (new DateTime($this->time))->format(self::PERIOD_FORMAT);
	}

	/**
	 * @inheritDoc
	 */
	public function getCurrentUsage(): int
	{
		$result = UsageTable::query()
			->setSelect(['USAGE_COUNT'])
			->where('USER_ID', $this->context->getUserId())
			->where('USAGE_PERIOD', $this->getCode())
			->exec()
			->fetch()
		;

		if (empty($result))
		{
			return 0;
		}

		return (int)($result['USAGE_COUNT'] ?? 0);
	}

	/**
	 * @inheritDoc
	 */
	public function getMaximumUsage(): int
	{
		if (!Bitrix24::isFreeLicense())
		{
			// +1 - tricky hack, because otherwise we will be always fall in daily limit
			return Plan::createByB24()?->getMaxUsage() + 1;
		}

		return self::MAX_IN_PERIOD;
	}
}
