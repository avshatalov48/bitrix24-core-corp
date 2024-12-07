<?php

namespace Bitrix\Tasks\Flow\Option;

class OptionService
{
	private static array $cache = [];
	private static OptionService $instance;
	private OptionRepository $optionRepository;

	public static function getInstance(): OptionService
	{
		if (!isset(self::$instance))
		{
			self::$instance = new OptionService(
				new OptionRepository(),
			);
		}

		return self::$instance;
	}

	public function __construct(OptionRepository $optionRepository)
	{
		$this->optionRepository = $optionRepository;
	}

	public function getOption(int $flowId, string $name): ?Option
	{
		$options = $this->getOptions($flowId);

		return $options[$name] ?? null;
	}

	/**
	 * @return array<Option>
	 */
	public function getOptions(int $flowId): array
	{
		$cachedOptions = $this->getFromCache($flowId);

		if ($cachedOptions)
		{
			return $cachedOptions;
		}

		$optionsData = $this->optionRepository->getOptions($flowId);

		$options = [];
		foreach ($optionsData as $option)
		{
			$options[$option['NAME']] = new Option($flowId, $option['NAME'], $option['VALUE']);
		}

		$this->saveInCache($options);

		return $options;
	}

	public function save(int $flowId, string $name, string $value): void
	{
		$this->optionRepository->save($flowId, $name, $value);
	}

	public function deleteAll(int $flowId): void
	{
		$this->optionRepository->deleteAll($flowId);
		$this->invalidateCache($flowId);
	}

	public function delete(int $flowId, string $name): void
	{
		$this->optionRepository->delete($flowId, $name);
		$this->invalidateCache($flowId);
	}

	private function getFromCache(int $flowId)
	{
		return self::$cache[$flowId] ?? null;
	}

	/**
	 * @param array<Option> $options
	 * @return void
	 */
	private function saveInCache(array $options): void
	{
		foreach ($options as $option)
		{
			self::$cache[$option->getFlowId()] ??= [];
			self::$cache[$option->getFlowId()][$option->getName()] = $option;
		}
	}

	public function invalidateCache(int $flowId): void
	{
		unset(self::$cache[$flowId]);
	}
}