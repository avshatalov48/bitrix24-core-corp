<?php

namespace Bitrix\Transformer\Integration\Baas;

use Bitrix\Transformer\Command;

/**
 * @internal
 */
interface Feature
{
	/**
	 * Returns true if the feature is available for purchase
	 *
	 * @return bool
	 */
	public function isAvailable(): bool;

	/**
	 * Returns true if the feature has been purchased
	 *
	 * @return bool
	 */
	public function isEnabled(): bool;

	/**
	 * Returns true if the feature can be used - it has been purchased and has available resources for usage.
	 *
	 * @return bool
	 */
	public function isActive(): bool;

	public function isApplicableToCommand(Command $command): bool;

	public function isApplicable(array $params): bool;
}
