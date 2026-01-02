<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\CheckList;

final class Option
{
	public const COLLAPSED = 1;
	public const EXPANDED = 2;

	public const ALLOWED_OPTIONS = [
		self::COLLAPSED,
		self::EXPANDED,
	];

	private function __construct() {}
}
