<?php

namespace Bitrix\AI;

class Cache
{
	/**
	 * Returns cached data by key. If data doesn't exist executes callback instead.
	 *
	 * @param string $key Cache key.
	 * @param callable $retrieveFunction Callback for retrieving data if cache doesn't exist.
	 * @param string|array $id Optional cache id.
	 * @return mixed
	 */
	public static function get(string $key, callable $retrieveFunction, string|array $id = ''): mixed
	{
		$cache = new Facade\Cache($key, $id);
		$existsData = $cache->getExists();

		if ($existsData !== null)
		{
			return $existsData;
		}
		else
		{
			$data = $retrieveFunction();
			$cache->store($data);

			return $data;
		}
	}

	/**
	 * Returns cached data by key depended on dynamic cache id. If data doesn't exist executes callback instead.
	 *
	 * @param string $key
	 * @param string|array $id
	 * @param callable $retrieveFunction
	 * @return mixed
	 */
	public static function getDynamic(string $key, string|array $id, callable $retrieveFunction): mixed
	{
		return self::get($key, $retrieveFunction, $id);
	}

	/**
	 * Deletes cache by exists key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public static function remove(string $key): void
	{
		Facade\Cache::remove($key);
	}
}
