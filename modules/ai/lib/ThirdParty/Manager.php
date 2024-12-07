<?php

namespace Bitrix\AI\ThirdParty;

use Bitrix\AI\Cache;
use Bitrix\AI\Facade\Rest;
use Bitrix\AI\Model\EngineTable;
use Bitrix\AI\ThirdParty\Service\ThirdPartyRegisterService;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;

class Manager
{
	private const CACHE_DIR = 'ai.thirdparty01';

	/**
	 * Registers new custom Engine.
	 *
	 * @param array{name: string, code: string, category: string, completions_url: string, settings: array|null} $data
	 * @param mixed $service During REST executes.
	 * @param mixed $server During REST executes.
	 * @return int
	 * @throws RestException
	 * @throws \Exception
	 */
	public static function register(array $data, mixed $service = null, mixed $server = null): int
	{
		$registerService = self::getRegisterService();

		$dto = $registerService->getValidatedData(
			$data,
			Rest::getApplicationCode($server?->getClientId())
		);

		$result = $registerService->save($dto);

		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];
			throw new RestException($error->getMessage(), $error->getCode());
		}

		Cache::remove(self::CACHE_DIR);

		return $result->getId();
	}

	private static function getRegisterService(): ThirdPartyRegisterService
	{
		return new ThirdPartyRegisterService();
	}

	/**
	 * Remove existing custom Engine.
	 *
	 * @param array $data Array contains fields: ['code'].
	 * @param mixed $service During REST executes.
	 * @param mixed $server During REST executes.
	 * @return bool
	 */
	public static function unRegister(array $data, mixed $service = null, mixed $server = null): bool
	{
		$data = array_change_key_case($data);

		$code = $data['code'] ?? null;
		$appCode = Rest::getApplicationCode($server?->getClientId());

		$existing = EngineTable::query()
			->setSelect(['ID'])
			->where('code', $code)
			->where('app_code', $appCode)
			->setLimit(1)
			->fetch()
		;
		if ($existing)
		{
			if (EngineTable::delete($existing['ID'])->isSuccess())
			{
				Cache::remove(self::CACHE_DIR);
				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes all Engines by Application code.
	 *
	 * @param string $appCode Application code.
	 * @return void
	 */
	public static function deleteByAppCode(string $appCode): void
	{
		$engines = EngineTable::query()
			->setSelect(['ID'])
			->where('app_code', $appCode)
		;
		foreach ($engines->fetchAll() as $engine)
		{
			EngineTable::delete($engine['ID'])->isSuccess();
		}
	}

	/**
	 * Returns collection of Engines.
	 *
	 * @param array|null $data Maybe an array with `filter` and `limit` key.
	 * @param mixed $service During REST executes.
	 * @param mixed $server During REST executes.
	 * @return array
	 */
	public static function getList(?array $data = null, mixed $service = null, mixed $server = null): array
	{
		if ($data)
		{
			$data = array_change_key_case($data);
		}

		$list = [];

		$filter = $data['filter'] ?? [];
		if ($server?->getClientId())
		{
			$filter['app_code'] = Rest::getApplicationCode($server->getClientId());
		}

		$engines = EngineTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->setOrder(['ID' => 'asc'])
			->setLimit($data['limit'] ?? null)
		;
		foreach ($engines->fetchAll() as $engine)
		{
			$engine = array_change_key_case($engine);

			$dateCreate = time();
			if (!empty($engine['date_create']) && ($engine['date_create'] instanceof DateTime))
			{
				$dateCreate = $engine['date_create']->getTimestamp();
			}
			$engine['date_create'] = $dateCreate;

			$list[] = $engine;
		}

		return $list;
	}

	/**
	 * Returns collection of Engines.
	 *
	 * @param array|null $data Maybe an array with `filter` and `limit` key.
	 * @return Collection
	 */
	public static function getCollection(?array $data = null): Collection
	{
		if ($data)
		{
			$data = array_change_key_case($data);
		}

		$collection = [];
		$engines = empty($data)
			? Cache::get(self::CACHE_DIR, fn() => self::getList($data))
			: self::getList($data);

		foreach ($engines as $engine)
		{
			if (!self::isValidData($engine))
			{
				continue;
			}

			$collection[] = new Item(
				$engine['id'],
				$engine['name'],
				$engine['code'],
				$engine['app_code'],
				$engine['category'],
				$engine['completions_url'],
				$engine['settings'] ?? [],
				DateTime::createFromTimestamp($engine['date_create']),
			);
		}

		return new Collection($collection);
	}

	private static function isValidData(array $engine): bool
	{
		foreach (['name', 'code', 'category', 'completions_url'] as $field)
		{
			if (!isset($engine[$field]) || !is_string($engine[$field]))
			{
				return false;
			}
		}

		if (!empty($engine['settings']) && !is_array($engine['settings']))
		{
			return false;
		}

		if (!is_string($engine['app_code']) && $engine['app_code'] !== null)
		{
			return false;
		}

		if (!is_int($engine['date_create']))
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns Engine by code.
	 *
	 * @param string $code Engine's code.
	 * @return Item|null
	 */
	public static function getByCode(string $code): ?Item
	{
		$collection = Manager::getCollection([
			'filter' => ['=CODE' => $code],
			'limit' => 1,
		]);

		return !$collection->isEmpty() ? $collection->current() : null;
	}

	/**
	 * Checks Engine exists by code.
	 * @param string $code Engine's code.
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function hasEngine(string $code): bool
	{
		return (bool) EngineTable::query()
			->setSelect(['ID'])
			->where('code', $code)
			->setLimit(1)
			->fetch()
		;
	}

	/**
	 * Checks ThirdParty Engines exists.
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function hasEngines(): bool
	{
		return (bool) EngineTable::query()
			->setSelect(['ID'])
			->setLimit(1)
			->fetch()
		;
	}
}
