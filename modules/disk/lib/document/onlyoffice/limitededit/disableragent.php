<?php
declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\LimitedEdit;

use Bitrix\Main\Config\Option;

/**
 * Agent for disabling limited edit mode after X days from the moment of first usage.
 */
final class DisablerAgent
{
	public const DEFAULT_ACTIVE_DAYS = 10;
	public const OPTION_LIMITED_EDIT_UNTIL = 'limited-edit-until';
	public const AGENT_NAME = self::class . '::disable();';
	private const STOP = '';

	public static function register(int $activeDays = self::DEFAULT_ACTIVE_DAYS): void
	{
		if (self::getLimitedEditUntil() > 0)
		{
			return;
		}

		$activeUntil = time() + $activeDays * 86400;
		$startTime = \ConvertTimeStamp($activeUntil - 120, 'FULL');

		\CAgent::AddAgent(
			self::AGENT_NAME,
			'disk',
			'N',
			60,
			next_exec: $startTime,
		);

		Option::set('disk', self::OPTION_LIMITED_EDIT_UNTIL, $activeUntil);
	}

	private static function getLimitedEditUntil(): int
	{
		return (int)Option::get('disk', self::OPTION_LIMITED_EDIT_UNTIL, 0);
	}

	/**
	 * Agent. Disables limited edit mode after X days from the moment of first usage.
	 * @return string
	 */
	public static function disable(): string
	{
		$activeUntilTime = self::getLimitedEditUntil();
		if (time() >= $activeUntilTime)
		{
			$configuration = new Configuration();
			$configuration->disableLimitEdit();

			return self::STOP;
		}

		return self::AGENT_NAME;
	}
}