<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Internals\Trait;

use Bitrix\Main\NotImplementedException;

trait SingletonTrait
{
	private static array $instances = [];

	public static function getInstance(): static
	{
		if (!isset(self::$instances[static::class]))
		{
			self::$instances[static::class] = new static();
		}

		return self::$instances[static::class];
	}

	/**
	 * @throws NotImplementedException
	 */
	public function __serialize(): array
	{
		throw new NotImplementedException('Can not serialize singleton');
	}

	protected function init(): void
	{

	}

	private function __construct()
	{
		$this->init();
	}

	/**
	 * @throws NotImplementedException
	 */
	private function __clone()
	{
		throw new NotImplementedException('Can not clone singleton');
	}
}