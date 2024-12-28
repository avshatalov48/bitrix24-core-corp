<?php

namespace Bitrix\Tasks\Flow\Grid;

use InvalidArgumentException;

abstract class Actions
{
	private static array $actions = [];

	final public static function build(): void
	{
		self::set(new Action\Edit());
		self::set(new Action\Activate());
		self::set(new Action\Remove());
		self::set(new Action\Pin());
	}

	final public static function set(Action\Action $action): void
	{
		self::$actions[$action->getId()] = $action;
	}

	final public static function get(string $id): Action\Action
	{
		if (!isset(self::$actions[$id]))
		{
			throw new InvalidArgumentException("Unknown id {$id} given");
		}

		return self::$actions[$id];
	}

	/**
	 * @return Action\Action[]
	 */
	final public static function getAll(): array
	{
		return self::$actions;
	}
}