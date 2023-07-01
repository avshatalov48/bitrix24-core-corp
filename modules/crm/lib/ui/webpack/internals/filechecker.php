<?php

namespace Bitrix\Crm\UI\Webpack\Internals;

use Bitrix\Main;
use Bitrix\Crm\UI\Webpack;

final class FileChecker
{
	private const LIMIT_ITEMS = 3;
	private const CACHE_KEY = 'crm/webpack/checker';
	private const CACHE_TTL = 3600;

	public static function checkAsAgent(): string
	{
		$instance = new self();
		if (!$instance->isEnabled())
		{
			return '';
		}

		$cnt = 0;
		foreach ($instance->getItems() as [$type, $id])
		{
			// limiting the number of processed items per agent
			if ($cnt++ >= self::LIMIT_ITEMS)
			{
				break;
			}

			// remove from queue for preventing processing single item multiple times
			if (!$instance->removeItem($type, $id))
			{
				continue;
			}

			$pack = $instance->createPack($type, $id);

			// check if pack exists
			if (!$pack->hasRow())
			{
				continue;
			}

			// check if file exists
			if ($pack->checkFileExists())
			{
				continue;
			}

			// rebuild file
			$pack->build();
		}

		return $instance->getItems()
			? self::class . '::checkAsAgent();'
			: ''
		;
	}

	public function addItem(string $type, int $id): bool
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		if (!$this->createPack($type, $id))
		{
			return false;
		}

		$items = $this->getItems();
		$item = [$type, $id];
		if (in_array($item, $items, true))
		{
			return false;
		}

		$items[] = $item;
		if (!$this->saveItems($items))
		{
			return false;
		}

		$this->addAgent();
		return true;
	}

	private function removeItem(string $type, int $id): bool
	{
		$item = [$type, $id];
		$items = [];
		foreach ($this->getItems() as $savedItem)
		{
			if ($savedItem !== $item)
			{
				$items[] = $savedItem;
			}
		}

		return $this->saveItems($items);
	}

	public function removeItems(): void
	{
		$cache = Main\Data\Cache::createInstance();
		$cache->clean(self::CACHE_KEY, 'crm');
	}

	public function getItems(): array
	{
		$cache = Main\Data\Cache::createInstance();
		$cache->noOutput();
 		$cache->initCache(self::CACHE_TTL, self::CACHE_KEY, 'crm');
		return array_values($cache->getVars() ?: []);
	}

	private function saveItems(array $items): bool
	{
		$cache = Main\Data\Cache::createInstance();
		$cache->noOutput();
		$cache->forceRewriting(true);
		$result = $cache->startDataCache(self::CACHE_TTL, self::CACHE_KEY, 'crm');
		$vars = [];
		foreach ($items as $index => $item)
		{
			$vars["key{$index}"] = $item;
		}
		$cache->endDataCache($vars);
		return $result;
	}

	private function isEnabled(): bool
	{
		return Main\ModuleManager::isModuleInstalled('bitrix24');
	}

	private function addAgent(): void
	{
		if (!$this->isEnabled())
		{
			return;
		}

		$agentName = self::class . "::checkAsAgent();";
		if (\CAgent::GetList(null, ['NAME' => $agentName, 'MODULE_ID' => 'crm'])->Fetch())
		{
			return;
		}

		\CAgent::AddAgent(
			$agentName,
			'crm', "N", 300, "", "Y",
			\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+300, "FULL")
		);
	}

	private function createPack(string $type, int $id): ?Webpack\Base
	{
		switch ($type)
		{
			case 'form':
				return Webpack\Form::instance($id);
			case 'call.tracker':
				return Webpack\CallTracker::instance();
			case 'call.tracker.ed':
				return Webpack\CallTrackerEditor::instance();
		}

		return null;
	}
}