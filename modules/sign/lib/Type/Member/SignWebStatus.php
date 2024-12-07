<?php

namespace Bitrix\Sign\Type\Member;

final class SignWebStatus
{
	public const WAIT = 'wait';
	public const READY = 'ready';
	public const DONE = 'done';
	public const REFUSED = 'refused';
	public const STOPPED = 'stopped';
	public const STOPPABLE_READY = 'stoppable_ready';
	public const COMPLETING = 'completing';

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
			self::COMPLETING,
		];
	}
}
