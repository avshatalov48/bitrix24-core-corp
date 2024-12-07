<?php

namespace Bitrix\Sign\Type;

final class MemberStatus
{
	public const DONE = 'Y';
	public const WAIT = 'N';
	public const READY = 'R';
	public const REFUSED = 'C';
	public const STOPPED = 'S';
	public const STOPPABLE_READY = 'F';
	public const PROCESSING = 'P';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::WAIT,
			self::READY,
			self::DONE,
			self::REFUSED,
			self::STOPPED,
			self::STOPPABLE_READY,
			self::PROCESSING,
		];
	}

	public static function getChangeableByFirstPartyMember(): array
	{
		return [
			self::WAIT,
		];
	}

	/**
	 * @return list<self::*>
	 */
	public static function getReadyForSigning(): array
	{
		return [
			self::READY,
			self::STOPPABLE_READY,
			self::PROCESSING
		];
	}

	/**
	 * @return list<self::*>
	 */
	public static function getStatusesNotDone(): array
	{
		return array_filter(
			self::getAll(),
			static fn ($status) => $status !== self::DONE
		);
	}

	public static function isReadyForSigning(string $status): bool
	{
		return in_array($status, self::getReadyForSigning(), true);
	}

	public static function isFinishForSigning(string $status): bool
	{
		return in_array($status, self::getStatusesIsFinished(), true);
	}

	public static function getStatusesIsFinished(): array
	{
		return [self::DONE, self::REFUSED, self::STOPPED];
	}

	public static function canBeChangedByFirstPartyMember(string $status): bool
	{
		return in_array($status, self::getChangeableByFirstPartyMember(), true);
	}

	public static function getStatusesNotFinished(): array
	{
		return array_filter(
			self::getAll(),
			static fn ($status) => !in_array($status, self::getStatusesIsFinished(), true)
		);
	}

	public static function toPresentedView(string $status): string
	{
		return match ($status)
		{
			self::DONE => 'signed',
			self::WAIT => 'waiting',
			self::READY => 'ready',
			self::REFUSED => 'refused',
			self::STOPPED => 'stopped',
			self::STOPPABLE_READY => 'stoppable_ready',
			self::PROCESSING => 'processing',
			default => '',
		};
	}
}
