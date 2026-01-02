<?php
namespace Bitrix\Tasks\Internals\UserOption;

/**
 * Class Name
 *
 * @package Bitrix\Tasks\Internals\UserOption
 */
class Option
{
	public const ALLOWED_OPTIONS = [
		self::MUTED,
		self::PINNED,
		self::PINNED_IN_GROUP,
	];

	public const MUTED = 1;
	public const PINNED = 2;
	public const PINNED_IN_GROUP = 3;
}