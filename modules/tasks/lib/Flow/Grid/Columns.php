<?php

namespace Bitrix\Tasks\Flow\Grid;

use Bitrix\Tasks\Flow\Grid\Column\Activity;
use Bitrix\Tasks\Flow\Grid\Column\AtWork;
use Bitrix\Tasks\Flow\Grid\Column\BIAnalytics;
use Bitrix\Tasks\Flow\Grid\Column\Column;
use Bitrix\Tasks\Flow\Grid\Column\Completed;
use Bitrix\Tasks\Flow\Grid\Column\CreateTask;
use Bitrix\Tasks\Flow\Grid\Column\Efficiency;
use Bitrix\Tasks\Flow\Grid\Column\Id;
use Bitrix\Tasks\Flow\Grid\Column\MyTasks;
use Bitrix\Tasks\Flow\Grid\Column\Name;
use Bitrix\Tasks\Flow\Grid\Column\Owner;
use Bitrix\Tasks\Flow\Grid\Column\Pending;
use InvalidArgumentException;

abstract class Columns
{
	/** @var Column[] */
	private static array $columns = [];

	final public static function build(): void
	{
		self::set(new Id());
		self::set(new Name());
		self::set(new Activity());
		self::set(new MyTasks());
		self::set(new CreateTask());
		self::set(new Pending());
		self::set(new AtWork());
		self::set(new Efficiency());
		self::set(new Completed());
		self::set(new BIAnalytics());
		self::set(new Owner());
	}

	final public static function set(Column $column): void
	{
		self::$columns[$column->getId()] = $column;
	}

	final public static function get(string $id): Column
	{
		if (!isset(self::$columns[$id]))
		{
			throw new InvalidArgumentException("Unknown id {$id} given");
		}

		return self::$columns[$id];
	}

	final public static function has(string $id): bool
	{
		return isset(self::$columns[$id]);
	}

	/**
	 * @return Column[]
	 */
	final public static function getAll(): array
	{
		return self::$columns;
	}
}
