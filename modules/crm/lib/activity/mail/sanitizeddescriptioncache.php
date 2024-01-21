<?php

namespace Bitrix\Crm\Activity\Mail;

use Bitrix\Main\Data\Cache;

/**
 * Cache reader/writer for sanitized email activity description
 */
final class SanitizedDescriptionCache
{
	/**
	 * Cache TTL
	 *
	 * @var int
	 */
	private const TTL = 10 * 60;

	/**
	 * Cache directory
	 */
	private const DIR = 'crm_mail';

	/**
	 * Get cache ID
	 *
	 * @param int $activityId Activity ID
	 *
	 * @return string
	 */
	private function getId(int $activityId): string
	{
		return "crm_mail_ajax_description_$activityId";
	}

	/**
	 * Get description by ID
	 *
	 * @param int $activityId Activity ID
	 *
	 * @return string|null
	 */
	public function get(int $activityId): ?string
	{
		$cache = Cache::createInstance();
		if ($cache->initCache(self::TTL, $this->getId($activityId), self::DIR))
		{
			return $cache->getVars();
		}

		return null;
	}

	/**
	 * Set ajax description
	 *
	 * @param int $activityId Activity ID
	 * @param string $value
	 *
	 * @return void
	 */
	public function set(int $activityId, string $value): void
	{
		$cache = Cache::createInstance();
		$cache->initCache(self::TTL, $this->getId($activityId), self::DIR);
		$cache->startDataCache();
		$cache->endDataCache($value);
	}

}
