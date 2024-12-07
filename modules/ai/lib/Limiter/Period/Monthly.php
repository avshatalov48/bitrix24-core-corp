<?php

namespace Bitrix\AI\Limiter\Period;

use Bitrix\AI\Context;
use Bitrix\AI\Integration\Baas\BaasTokenService;
use Bitrix\AI\Limiter\Plan;
use Bitrix\AI\Model\UsageTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

class Monthly implements IPeriod
{
	private const PERIOD_FORMAT = 'Y-m';

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
			->addSelect(Query::expr()->sum('USAGE_COUNT'), 'SUM')
			->where('USAGE_PERIOD', $this->getCode())
			->exec()
			->fetch()
		;

		if (empty($result))
		{
			return 0;
		}

		return (int)($result['SUM'] ?? 0);
	}

	/**
	 * @inheritDoc
	 */
	public function getMaximumUsage(): int
	{
		return Plan::createByB24()?->getMaxUsage();
	}
}
