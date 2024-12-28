<?php

namespace Bitrix\Mobile;

use Bitrix\Main\Config\Option;

final class Tourist
{
	private const MAX_TIMES_TO_REMEMBER = 1000;

	/**
	 * @return array<string, array>
	 */
	public static function getEvents(): array
	{
		if (self::isFakeMode())
		{
			return [];
		}

		/** @var array<string, array> $events */
		$events = \CUserOptions::GetOption('mobile', 'tourist_events', []);

		return $events;
	}

	public static function remember(string $eventId): array
	{
		$event = [
			'ts' => time(),
		];

		if (!self::isFakeMode())
		{
			$events = self::getEvents();
			$cnt = intval($events[$eventId]['cnt'] ?? 0);
			$event['cnt'] = min($cnt + 1, self::MAX_TIMES_TO_REMEMBER);
			$events[$eventId] = $event;
			\CUserOptions::SetOption('mobile', 'tourist_events', $events);
		}

		return $event;
	}

	public static function forget(string $eventId): void
	{
		$events = self::getEvents();

		if ($events[$eventId])
		{
			unset($events[$eventId]);
			\CUserOptions::SetOption('mobile', 'tourist_events', $events);
		}
	}

	/**
	 * Useful for QA
	 * @param int|null $userId
	 * @return void
	 */
	public static function forgetAll(?int $userId = null): void
	{
		$resolvedUserId = $userId === null ? false : $userId;

		\CUserOptions::SetOption('mobile', 'tourist_events', [], false, $resolvedUserId);
	}

	/**
	 * Useful for QA
	 * @param bool $enable
	 * @return void
	 */
	public static function fake(bool $enable = true): void
	{
		if ($enable)
		{
			Option::set('mobile', 'tourist_fake_mode', true);
		}
		else
		{
			Option::delete('mobile', ['name' => 'tourist_fake_mode']);
		}
	}

	private static function isFakeMode(): bool
	{
		return Option::get('mobile', 'tourist_fake_mode', false);
	}
}
