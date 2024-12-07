<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Psr\Container\NotFoundExceptionInterface;

final class CommandLocator
{
	public static string $prefix = 'tasks.flow.command';
	private static self $instance;

	private function __construct()
	{}

	private function __clone()
	{}

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @throws CommandNotFoundException
	 */
	public function get(string $id): CommandHandler
	{
		try
		{
			return ServiceLocator::getInstance()->get(self::$prefix . '.' . $id);
		}
		catch (NotFoundExceptionInterface|ObjectNotFoundException $e)
		{
			throw new CommandNotFoundException($e->getMessage());
		}
	}
}