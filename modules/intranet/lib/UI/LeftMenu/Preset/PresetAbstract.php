<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use Bitrix\Intranet\UI\LeftMenu;
use Bitrix\Main;
use Bitrix\Intranet\Settings\Tools;

Main\Localization\Loc::loadMessages(__FILE__);

abstract class PresetAbstract implements PresetInterface
{
	const CODE = 'undefined_or_abstract_does_not_matter';
	protected $siteId;

/*	const STRUCTURE = [
		'shown' => [
			'menu_teamwork' => [
				'menu_files',
				'menu_documents',
				'menu_calendar',
				'menu_timeman_sect',
				'menu_all_groups',
			],
			'menu_marketplace_group' => [
				'menu_marketplace_sect'
			],
			'menu_tasks',
			'menu_im_messenger',
			'menu_rpa',
			'menu_crm_favorite',
			'menu_sites',
		],
		'hidden' => [

		]
	];
*/
	public function __construct(string $siteId)
	{
		$this->siteId = $siteId;
	}

	protected function getSiteId()
	{
		return $this->siteId;
	}

	public function getCode(): string
	{
		return static::CODE;
	}

	private function getPlainSort()
	{
		static $plainArray;

		if ($plainArray)
		{
			return $plainArray;
		}

		$flatten = function($value, $key) use (&$plainArray, &$flatten) {
			if (is_string($key))
			{
				$plainArray[] = $key;
			}
			if (is_array($value))
			{
				array_walk($value, $flatten);
			}
			else
			{
				$plainArray[] = strval($value);
			}
		};

		$structure = $this->getFinalStructure();
		array_walk($structure, $flatten);

		return $plainArray;
	}

	public function getSortForItem($itemId, $parentId): ?int
	{
		$res = array_search($itemId, $this->getPlainSort());
		return $res === false ? null : $res;
	}

	public final function getFinalStructure(): array
	{
		static $finalStructure;
		if (!$finalStructure)
		{
			$finalStructure = $this->getSavedStructure() ?: $this->getToolsStructure() ?: $this->getStructure();
		}
		return $finalStructure;
	}

	private function getSavedStructure(): ?array
	{
		static $savedStructure;
		if ($savedStructure)
		{
			return $savedStructure;
		}
		$data = \CUserOptions::GetOption("intranet", "left_menu_sorted_items_" . $this->getSiteId());

		if (!$data)
		{
			return null;
		}
		if (!isset($data['version']))
		{
			$savedStructure = self::oldToNewStructure($data, $this->getStructure());
		}
		else
		{
			$flatOwnStructure = self::flatten(array_merge($data['show'], $data['hide']));
			$expectedStructure = $this->getStructure();
			$notNaturalSelection = function(&$group, $key) use (&$notNaturalSelection, $flatOwnStructure)
			{
				if (is_array($group))
				{
					$needToSelect = false;
					foreach ($group as $menuKey => $menuItem)
					{
						if (is_array($menuItem))
						{
							$needToSelect = true;
						}
						else if (array_key_exists($menuItem, $flatOwnStructure))
						{
							unset($group[$menuKey]);
						}
					}
					if ($needToSelect)
					{
						array_walk($group, $notNaturalSelection);
						foreach ($group as $menuKey => $menuItem)
						{
							if (empty($menuItem))
							{
								unset($group[$menuKey]);
							}
						}
					}
				}
			};
			array_walk($expectedStructure, $notNaturalSelection);
			$savedStructure = array_merge_recursive($data, ['show' => $expectedStructure['shown'], 'hide' => $expectedStructure['hidden']]);
			$savedStructure = ['shown' => $savedStructure['show'], 'hidden' => $savedStructure['hide']];
		}

		return $savedStructure;
	}

	public function getToolsStructure(): ?array
	{
		if ($this instanceof Custom)
		{
			return null;
		}

		if (Manager::hasOwnPreset())
		{
			return null;
		}

		$savedToolsSort = Tools\ToolsManager::getInstance()->getSorter()->getSavedSort();

		if ($savedToolsSort)
		{
			$savedToolsSort = array_values($savedToolsSort);
			$structure = $this->getStructure();
			$shownStructure = $structure['shown'];
			$structureSortedByTools['hidden'] = $structure['hidden'];
			$structureSortedByTools['shown'] = [];
			$flattenShownStructure = [];

			foreach ($shownStructure as $key => $value)
			{
				if (is_array($value))
				{
					$flattenShownStructure[] = $key;
				}
				else
				{
					$flattenShownStructure[] = $value;
				}
			}

			$indexes = [];
			$ids = [];

			foreach ($savedToolsSort as $id)
			{
				$index = array_search($id, $flattenShownStructure, true);

				if ($index !== false)
				{
					$indexes[] = $index;
					$ids[] = $id;
				}
			}

			sort($indexes);
			$structureSortToolsItem = array_combine($indexes, $ids);

			foreach ($structureSortToolsItem as $key => $value)
			{
				$flattenShownStructure[$key] = $value;
			}

			foreach ($flattenShownStructure as $key => $value)
			{
				$index = array_search($value, $shownStructure, true);

				if ($index !== false)
				{
					$structureSortedByTools['shown'][$key] = $shownStructure[$index];
				}
				elseif (isset($shownStructure[$value]))
				{
					$structureSortedByTools['shown'][$value] = $shownStructure[$value];
				}
			}

			return $structureSortedByTools;
		}

		return null;
	}

	public static function oldToNewStructure(array $oldData, array $newData): array
	{
		$data = [
			'shown' => isset($oldData['show']) && is_array($oldData['show']) ? $oldData['show'] : [],
			'hidden' => isset($oldData['hide']) && is_array($oldData['hide']) ? $oldData['hide'] : []
		];

		$result = ['shown' => [], 'hidden' => []];
		$systemGroups = [];
		$replaceItems = [
			'menu_live_feed' => 'menu_teamwork',
			'menu_marketplace_sect' => 'menu_marketplace_group'
		];
		foreach ($result as $visibility => $items)
		{
			foreach ($data[$visibility] as $itemId)
			{
				if (array_key_exists($itemId, $replaceItems))
				{
					$groupId = $replaceItems[$itemId];
					$expectedStructure = null;
					foreach ($newData as $structureContent)
					{
						if (is_array($structureContent) && array_key_exists($groupId , $structureContent))
						{
							$expectedStructure = $structureContent[$groupId];
							break;
						}
					}
					if ($expectedStructure !== null)
					{
						$result[$visibility][$groupId] = [$itemId];
						$systemGroups[$groupId] = [$visibility, $expectedStructure];
					}
					continue;
				}

				$result[$visibility][] = $itemId;
			}
		}

		foreach ($systemGroups as $groupId => [$visibility, $expectedStructure])
		{
			$menu = &$result[$visibility];
			$foundItems = array_intersect($menu, $expectedStructure);
			$menu[$groupId] = array_merge(
				$menu[$groupId],
				array_values($foundItems)
			);
			$notFoundItems = array_diff($expectedStructure, $menu[$groupId]);
			if ($visibility === 'shown')
			{
				$notFoundItems = array_diff($notFoundItems,
					array_intersect($result['hidden'], $expectedStructure)
				);
				$menu[$groupId] = array_merge(
					$menu[$groupId],
					array_values($notFoundItems)
				);
			}
			$result[$visibility] = array_diff($menu, $menu[$groupId]);
			unset($menu);
		}
		return ['shown' => $result['shown'], 'hidden' => $result['hidden']];
	}

	private static function flatten(array $someArray): array
	{
		$plainStructure = [];
		$flatten = function($value, $key, $parent) use (&$plainStructure, &$flatten) {
			if (is_string($key))
			{
				$plainStructure[$key] = $parent;
				$parent = $key;
			}
			if (is_array($value))
			{
				array_walk($value, $flatten, $parent);
			}
			else
			{
				$plainStructure[strval($value)] = $parent;
			}
		};

		array_walk($someArray, $flatten, null);
		return $plainStructure;
	}

	private function getPlainStructure()
	{
		static $plainStructure;

		if (!$plainStructure)
		{
			$plainStructure = self::flatten($this->getFinalStructure());
		}
		return $plainStructure;
	}

	public function getParentForItem($itemId, LeftMenu\MenuItem\Basic $item): ?string
	{
		$plainStructure = $this->getPlainStructure();
		if (array_key_exists($itemId, $plainStructure))
		{
			return $plainStructure[$itemId];
		}
		return null;
	}

	public function getItems(): array
	{
		static $result;
		if ($result)
		{
			return $result;
		}

		$res = $this->getPlainStructure();
		$result = [];

		if (in_array('menu_teamwork', $res, true))
		{
			$menuTeamWork = new LeftMenu\MenuItem\GroupSystem([
				'ID' => 'menu_teamwork',
				'TEXT' => Main\Localization\Loc::getMessage('MENU_TEAMWORK')
			]);
			if (static::CODE === Social::CODE)
			{
				$menuTeamWork->setDefaultOptions(['collapsed_mode' => 'expanded']);
			}

			$result[] = $menuTeamWork;
		}

		if (in_array('menu_marketplace_group', $res))
		{
			$result[] = new LeftMenu\MenuItem\GroupSystem([
				'ID' => 'menu_marketplace_group',
				'TEXT' => Main\Localization\Loc::getMessage('MENU_APPLICATIONS'),
			], $this->getSiteId());
		}
		return $result;
	}
}