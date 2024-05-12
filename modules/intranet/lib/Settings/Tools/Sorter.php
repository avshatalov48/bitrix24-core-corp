<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Intranet\UI\LeftMenu\Preset\Custom;
use Bitrix\Intranet\UI\LeftMenu\Preset\Manager;
use Bitrix\Intranet\UI\LeftMenu\Preset\PresetInterface;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

class Sorter
{
	private PresetInterface $preset;

	/**
	 * @param array<string,Tool> $tools
	 */
	public function __construct(protected array $tools)
	{
		$this->preset = Manager::getPreset();
	}

	/**
	 * @return Tool[]
	 */
	public function getSortedToolList(): array
	{
		$sortedToolList = [];
		$savedSort = $this->getSavedSort();

		if ($savedSort && $this->preset->getCode() !== Custom::CODE)
		{
			$sort = $savedSort;
		}
		else
		{
			$sort = $this->getPresetStructure();
		}

		foreach ($sort as $key => $value)
		{
			if (is_array($value) && isset($this->tools[$key]))
			{
				$sortedToolList[$key] = $this->tools[$key];
			}
			elseif (is_string($value) && isset($this->tools[$value]))
			{
				$sortedToolList[$value] = $this->tools[$value];
			}
		}

		$diff = array_diff(array_keys($this->tools), array_keys($sortedToolList));

		if (!empty($diff))
		{
			foreach ($diff as $toolId)
			{
				if (isset($this->tools[$toolId]))
				{
					$sortedToolList[] = $this->tools[$toolId];
				}
			}
		}

		if (count($sortedToolList) !== count($this->tools))
		{
			$sortedToolList = $this->tools;
		}

		return array_values($sortedToolList);
	}

	/**
	 * @param String[] $sort
	 */
	public function saveSort(array $sort): void
	{
		foreach ($sort as $toolMenuId)
		{
			if (!isset($this->tools[$toolMenuId]))
			{
				return;
			}
		}

		try
		{
			Option::set('intranet', 'tools-sort', Json::encode($sort), SITE_ID);
		}
		catch (ArgumentException)
		{
			return;
		}
	}

	/**
	 * @return String[]|null
	 */
	public function getSavedSort(): ?array
	{
		$sort = Option::get('intranet', 'tools-sort');
		$prepareSort = [];

		if (!$sort)
		{
			return null;
		}

		try
		{
			$sort = Json::decode($sort);
		}
		catch (ArgumentException)
		{
			return null;
		}

		if (empty($sort) || !is_array($sort))
		{
			return null;
		}

		foreach ($sort as $toolId)
		{
			if (!isset($this->tools[$toolId]))
			{
				return null;
			}

			$prepareSort[$toolId] = $toolId;
		}

		return $prepareSort;
	}

	public function clearSort(): void
	{
		Option::delete('intranet', ['name' => 'tools-sort']);
	}

	/**
	 * @return String[]
	 */
	protected function getPresetStructure(): array
	{
		return Manager::getPreset()->getStructure()['shown'] ?? [];
	}
}