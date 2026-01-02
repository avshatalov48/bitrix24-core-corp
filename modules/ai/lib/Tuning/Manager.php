<?php

namespace Bitrix\AI\Tuning;

use Bitrix\AI\Config;
use Bitrix\AI\Engine;
use Bitrix\Main\Event;
use Bitrix\Main\ORM;
use Bitrix\Main\Web\Json;

/**
 * Class for manage tuning options
 */
class Manager
{
	private const CONFIG_EXTERNAL_CODE = 'tuning';

	private GroupCollection $groups;

	public function __construct()
	{
		$this->groups = new GroupCollection();
		$this->createDefaultGroups();
		$this->createInternalGroups();

		$this->loadExternal();
		$this->loadInternal();

		$this->onAfterLoad();
	}

	/**
	 * Default config for AI module
	 * @return void
	 */
	private function loadInternal(): void
	{
		foreach (Defaults::getInternalItems() as $groupCode => $items)
		{
			foreach ($items as $code => $item)
			{
				/** @var Item $item */
				$value = Config::getValue($code);
				if ($value)
				{
					$item->setValue($value);
				}

				$group = $this->groups->get($groupCode);
				$group?->addItem($item);
			}
		}
	}

	/**
	 * Loads config from external handlers.
	 *
	 * @return void
	 */
	private function loadExternal(): void
	{
		$event = new Event('ai', 'onTuningLoad');
		$event->send();

		$storage = self::getTuningStorage();
		foreach ($event->getResults() as $result)
		{
			if (!$result instanceof Orm\EventResult)
			{
				continue;
			}

			foreach ($result->getModified()['groups'] ?? [] as $groupCode => $raw)
			{
				if (!$this->groups->get($groupCode))
				{
					$group = Group::create($groupCode, $raw);
					if ($group)
					{
						Defaults::normalizeGroupSort($group);
						$this->groups->set($groupCode, $group);
					}
				}
			}

			foreach ($result->getModified()['items'] ?? [] as $code => $raw)
			{
				$groupCode = $raw['group'] ?? Defaults::GROUP_DEFAULT;
				$group = $this->groups->get($groupCode);
				$item = Item::create($code, $raw);

				if ($item && !Defaults::isGroupInternal($group))
				{
					$item->setValue(
						array_key_exists($code, $storage)
							? $storage[$code]
							: ($raw['default'] ?? null)
					);

					$group->addItem($item);
				}
			}

			foreach ($result->getModified()['itemRelations'] ?? [] as $groupCode => $relations)
			{
				foreach ($relations as $parent => $children)
				{
					$this->groups->get($groupCode)?->addItemRelations($parent, $children);
				}
			}
		}
	}

	/**
	 * Special preparatory operations on setting items. F.e. hiding some elements from lists
	 * @return void
	 */
	private function onAfterLoad(): void
	{
		$engineConfigurations = [
			[
				'configKey' => 'bitrixgpt_options',
				'engineCode' => Engine\Cloud\Bitrix24::ENGINE_CODE,
			],
			[
				'configKey' => 'bitrixaudio_availableIn',
				'engineCode' => Engine\Cloud\BitrixAudio::ENGINE_CODE,
			],
			[
				'configKey' => 'bitrixaudio_availableIn',
				'engineCode' => 'BitrixAudioCall',
			]
		];

		foreach ($engineConfigurations as $config)
		{
			$optionValue = Config::getValue($config['configKey']);
			if ($optionValue === null)
			{
				continue;
			}

			$availableInItems = [];
			$decodedValue = json_decode($optionValue, true);
			if (isset($decodedValue['availableIn']) && $config['configKey'] === 'bitrixgpt_options')
			{
				$availableInItems = $decodedValue['availableIn'];
			}
			elseif (is_array($decodedValue) && $config['configKey'] === 'bitrixaudio_availableIn')
			{
				$availableInItems = $decodedValue;
			}

			if (empty($availableInItems))
			{
				return;
			}

			$this->removeEngineFromGroups($availableInItems, $config['engineCode']);
		}
	}

	private function removeEngineFromGroups(array $availableInItems, string $engineCode): void
	{
		foreach ($this->groups as $group)
		{
			/** @var Group $group */
			foreach ($group->getItems() as $item)
			{
				if (!$this->shouldProcessItem($item, $availableInItems))
				{
					continue;
				}

				$this->removeEngineFromItem($item, $engineCode);
			}
		}
	}

	private function shouldProcessItem($item, array $availableInItems): bool
	{
		return $item->isList() && !in_array($item->getCode(), $availableInItems, true);
	}

	private function removeEngineFromItem($item, string $engineCode): void
	{
		$item->setOptions(array_filter(
			$item->getOptions(),
			static fn($code) => $code !== $engineCode,
			ARRAY_FILTER_USE_KEY
		));
	}

	/**
	 * Get default internal groups and add to store
	 * @return void
	 */
	private function createInternalGroups(): void
	{
		foreach (Defaults::getInternalGroups() as $code => $group)
		{
			$this->groups->set($code, $group);
		}
	}

	/**
	 * Get default groups and add to store
	 * @return void
	 */
	private function createDefaultGroups(): void
	{
		foreach (Defaults::getDefaultGroups() as $code => $group)
		{
			$this->groups->set($code, $group);
		}
	}

	/**
	 * Returns Collection of all tuning items.
	 * @param bool $toArray - if true - format objects list to array
	 *
	 * @return GroupCollection|array
	 */
	public function getList(bool $toArray = false): GroupCollection|array
	{
		$this->groups->sort();
		foreach ($this->groups as $group)
		{
			/**
			 * @var Group $group
			 */
			$group->sortItems();
		}

		return $toArray
			? $this->groups->toArray()
			: $this->groups
		;
	}

	/**
	 * Returns tuning item by code.
	 *
	 * @param string $code
	 * @return Item|null
	 */
	public function getItem(string $code): ?Item
	{
		foreach ($this->groups as $group)
		{
			/**
			 * @var Group $group
			 */
			if ($group->getItems()->get($code))
			{
				return $group->getItems()->get($code);
			}
		}

		return null;
	}

	/**
	 * Saves current config to db.
	 *
	 * @return void
	 */
	public function save(): void
	{
		$externalConfig = new Collection();

		foreach ($this->groups as $group)
		{
			/**
			 * @var Group $group
			 */
			foreach ($group->getItems() as $code => $item)
			{
				$item->onSave();

				if (Defaults::isItemInternal($item))
				{
					Config::setOptionsValue($code, $item->getValue());
				}
				else
				{
					$externalConfig->set($item->getCode(), $item);
				}
			}
		}

		if (!$externalConfig->isEmpty())
		{
			Config::setOptionsValue(self::CONFIG_EXTERNAL_CODE, json_encode($externalConfig->toArray()));
		}
	}

	public static function getTuningStorage(): array
	{
		$config = json_decode(Config::getValue(self::CONFIG_EXTERNAL_CODE) ?? "", true) ?: [];

		return is_array($config) ? $config : [];
	}
}
