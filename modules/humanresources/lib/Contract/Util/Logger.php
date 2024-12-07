<?php

namespace Bitrix\HumanResources\Contract\Util;

interface Logger
{
	/**
	 * @param array{message: ?string, entityType: ?string, entityId: int, userId: ?int} $context
	 * @return void
	 */
	public function write(array $context): void;

	/**
	 * @param string $level
	 * @param string $message
	 *
	 * @return void
	 */
	public function logMessage(string $level, string $message): void;
}