<?php

namespace Bitrix\Intranet\CustomSection;

use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

abstract class Provider
{
	/**
	 * Check if a custom section page with such $pageSettings should be displayed for the user with $userId
	 *
	 * @param string $pageSettings
	 * @param int $userId
	 *
	 * @return bool
	 */
	abstract public function isAvailable(string $pageSettings, int $userId): bool;

	/**
	 * Returns counter id for a custom section page with $pageSettings
	 * Returns null if there is no counter for the page
	 *
	 * @param string $pageSettings
	 *
	 * @return string|null
	 */
	public function getCounterId(string $pageSettings): ?string
	{
		return null;
	}

	/**
	 * Returns counter value for a custom section page with $pageSettings
	 * Returns null if there is no counter for the page
	 *
	 * @param string $pageSettings
	 *
	 * @return int|null
	 */
	public function getCounterValue(string $pageSettings): ?int
	{
		return null;
	}

	/**
	 * Returns params of a component that should be included in a custom section page with such $pageSettings
	 * If some error occurs, .e.g., $pageSettings are invalid, returns null instead
	 *
	 * @param string $pageSettings
	 * @param Uri $url
	 *
	 * @return Component|null
	 */
	abstract public function resolveComponent(string $pageSettings, Uri $url): ?Component;
}
