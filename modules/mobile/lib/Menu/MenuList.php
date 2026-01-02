<?php

namespace Bitrix\Mobile\Menu;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;

use Bitrix\Mobile\Menu\Entity\MenuItem;
use Bitrix\Mobile\Menu\Entity\MenuSection;
use Bitrix\Mobile\Menu\Service\MenuListCacheInterface;

class MenuList
{
	public const SECTION_BITRIX24 = 'bitrix24';
	public const SECTION_TEAMWORK = 'teamwork';
	public const SECTION_TASKS = 'tasks';
	public const SECTION_CRM = 'crm';
	public const SECTION_BIZPROC = 'bizproc';
	public const SECTION_CRM_DYNAMIC = 'crm_dynamic';
	public const SECTION_MARKETPLACE = 'marketplace';
	public const SECTION_TOOLS = 'tools';
	public const SECTION_DEVELOPMENT = 'development';

	private Context $context;
	private MenuListCacheInterface $cache;

	public function __construct(Context $context, MenuListCacheInterface $cache)
	{
		$this->context = $context;
		$this->cache = $cache;
	}

	public function build(bool $forceRefresh = false): array
	{
		if (!$forceRefresh)
		{
			$cachedMenu = $this->cache->get();

			if ($cachedMenu)
			{
				return $cachedMenu;
			}
		}

		$this->cache->clear();

		$menuStructure = $this->getBaseStructure();

		$processedMenu = $this->applyMenuEvents($menuStructure);
 		$compositeStructure = $this->buildCompositeMenuStructure($processedMenu);

		$this->cache->set($compositeStructure);

		return $compositeStructure;
	}

	private function getBaseStructure(): array
	{
		return [
			$this->createSection(self::SECTION_TOOLS, Loc::getMessage('MENU_TOOLS_SECTION_TITLE'), 1900),
			$this->createSection(self::SECTION_BITRIX24, Loc::getMessage('MENU_BITRIX24_SECTION_TITLE'), 100),
			$this->createSection(self::SECTION_TEAMWORK, Loc::getMessage('MENU_TEAMWORK_SECTION_TITLE'), 200),
			$this->createSection(self::SECTION_TASKS, Loc::getMessage('MENU_TASKS_SECTION_TITLE'), 300),
			$this->createSection(self::SECTION_CRM, Loc::getMessage('MENU_CRM_SECTION_TITLE'), 400),
			$this->createSection(self::SECTION_BIZPROC, Loc::getMessage('MENU_BIZPROC_SECTION_TITLE'), 500),
			$this->createSection(self::SECTION_CRM_DYNAMIC, Loc::getMessage('MENU_CRM_DYNAMIC_SECTION_TITLE'), 600),
			$this->createSection(self::SECTION_MARKETPLACE, Loc::getMessage('MENU_MARKETPLACE_SECTION_TITLE'), 700),
			$this->createSection(self::SECTION_DEVELOPMENT, 'Development', 2000),
		];
	}

	private function createSection(string $id, string $title, int $sort): array
	{
		return [
			'id' => $id,
			'code' => $id,
			'title' => $title,
			'sort' => $sort,
			'items' => [],
		];
	}

	private function applyMenuEvents(array $menuStructure): array
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventHandlers = $eventManager->findEventHandlers('mobile', 'onMobileMenuStructureBuilt');

		if (!empty($eventHandlers))
		{
			foreach ($eventHandlers as $handler)
			{
				$updatedMenu = \ExecuteModuleEventEx($handler, [$menuStructure, $this->context]);

				if (is_array($updatedMenu))
				{
					$menuStructure = $updatedMenu;
				}
			}
		}

		return $menuStructure;
	}

	private function buildCompositeMenuStructure(array $menuData): array
	{
		$sections = [];

		foreach ($menuData as $sectionData)
		{
			if (empty($sectionData['id']))
			{
				continue;
			}

			$section = new MenuSection(
				$sectionData['id'],
					$sectionData['title'] ?? '',
				$sectionData['sort'] ?? 100,
			);

			if (!empty($sectionData['items']) && is_array($sectionData['items']))
			{
				foreach ($sectionData['items'] as $itemData)
				{
					if (empty($itemData['id']))
					{
						continue;
					}

					// attrs deprecated
					$params = !empty($itemData['attrs']) ? $itemData['attrs'] : ($itemData['params'] ?? null);

					$item = new MenuItem(
						$itemData['id'],
						$itemData['title'] ?? '',
						$itemData['imageName'] ?? '',
						$itemData['sort'] ?? 100,
						$itemData['path'] ?? null,
						$itemData['tag'] ?? null,
						$params,
					);

					$section->addChild($item);
				}
			}

			$sections[] = $section;
		}

		usort($sections, static function(MenuSection $a, MenuSection $b) {
			return $a->getSort() <=> $b->getSort();
		});

		return array_values(
			array_filter(
				array_map(
					static fn(MenuSection $section) => $section->toArray(), $sections),
		));
	}
}
