<?php

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Model\PlanTable;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;

final class Plan
{
	private function __construct(
		private string $code,
		private int $maxUsage,
	) {}

	/**
	 * Returns Plan code.
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * Returns Plan max usage.
	 * @return int
	 */
	public function getMaxUsage(): int
	{
		return $this->maxUsage;
	}

	/**
	 * Returns Plan instance by Bitrix24 features.
	 * @return self|null
	 */
	public static function createByB24(): ?self
	{
		foreach (QueryPackage::cases() as $enum)
		{
			if (Bitrix24::isFeatureEnabled($enum->value))
			{
				return new self(
					$enum->value,
					$enum->getMaxUsageValue(),
				);
			}
		}

		return null;
	}

	/**
	 * Returns Plan instance by portal's license type.
	 * @deprecated Use Plan::createByB24() instead.
	 * @return self|null
	 */
	public static function getByLicense(): ?self
	{
		return self::get(Bitrix24::getLicenseType());
	}

	/**
	 * Returns Plan instance by code.
	 * @deprecated Use Plan::createByB24() instead.
	 * @param string|null $code Plan code.
	 * @return self|null
	 */
	public static function get(?string $code): ?self
	{
		static $plans = [];

		if (!$code)
		{
			return null;
		}

		if (array_key_exists($code, $plans))
		{
			return $plans[$code];
		}

		$plans[$code] = PlanTable::query()
			->setSelect(['CODE', 'MAX_USAGE'])
			->where('CODE', $code)
			->setLimit(1)
			->fetch() ?: null
		;

		if ($plans[$code])
		{
			$plans[$code] = new self(
				$plans[$code]['CODE'],
				(int)$plans[$code]['MAX_USAGE'],
			);
		}

		return $plans[$code];
	}
}