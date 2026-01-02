<?php

namespace Bitrix\HumanResources\Install\Agent;

abstract class BaseAgent
{
	abstract public static function run(int $offset = 0): string;

	protected static function finish(): string
	{
		return '';
	}

	protected static function next(int $offset): string
	{
		return static::class . "::run($offset);";
	}
}