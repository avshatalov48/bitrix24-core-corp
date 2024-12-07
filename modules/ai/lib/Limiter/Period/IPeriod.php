<?php

namespace Bitrix\AI\Limiter\Period;

use Bitrix\AI\Context;

interface IPeriod
{
	/**
	 * @param string|null $time String representation of datetime.
	 */
	public function __construct(
		Context $context,
		?string $time = null,
	);

	/**
	 * Returns code of period.
	 *
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * Returns current usages during current period.
	 *
	 * @return int
	 */
	public function getCurrentUsage(): int;

	/**
	 * Returns maximum possible usages during current period.
	 *
	 * @return int
	 */
	public function getMaximumUsage(): int;
}
