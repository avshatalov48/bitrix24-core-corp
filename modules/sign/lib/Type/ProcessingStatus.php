<?php

namespace Bitrix\Sign\Type;

final class ProcessingStatus
{
	public const WAIT = 'wait';
	public const PREPARED = 'prepared';
	public const ASSEMBLED = 'assembled';
	public const SIGNED = 'signed';
	public const DONE = 'done';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::WAIT,
			self::PREPARED,
			self::ASSEMBLED,
			self::SIGNED,
			self::DONE,
		];
	}
}