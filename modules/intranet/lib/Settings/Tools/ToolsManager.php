<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Main;

class ToolsManager
{
	private static ToolsManager $instance;
	/**
	 * @var Tool[]|null
	 */
	private ?array $baseTools = null;
	/**
	 * @var string[]|null
	 */
	private ?array $disabledMenuItemListId = null;
	private Sorter $sorter;
	private FirstPageChanger $firstPageChanger;
	private const CACHE_ID_DISABLED_TOOLS = 'disabled_tools';
	private const CACHE_PATH = '/intranet/settings/';

	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	protected function init(): void
	{
		$sitesTool = new Sites();

		$toolsByMenuId = [
			$sitesTool->getMenuItemId() => $sitesTool,
			'menu_tasks' => new Tasks(),
			'menu_crm_favorite' => new Crm(),
			'menu_teamwork' => new TeamWork(),
			'menu_automation' => new Automation(),
			'menu_sign' => new Sign(),
			'menu_crm_store' => new Inventory(),
			'menu_bi_constructor' => new BIConstructor(),
			'menu_company' => new Company(),
		];

		$this->sorter = new Sorter($toolsByMenuId);
		$sortedToolList = $this->sorter->getSortedToolList();
		$this->baseTools = array_filter($sortedToolList, static fn ($tool) => $tool->isAvailable());
	}

	/**
	 * @return Tool[]
	 */
	public function getToolList(): array
	{
		if (!is_array($this->baseTools))
		{
			$this->init();
		}

		return $this->baseTools;
	}

	public function checkAvailabilityByMenuId(string $menuItemId): bool
	{
		if ($menuItemId === 'menu_company')
		{
			return true;
		}

		$listDisabledIdMenuItem = $this->getDisabledMenuItemListId();
		$menuItemId = $this->checkCustomMenuId($menuItemId);

		if (in_array($menuItemId, $listDisabledIdMenuItem, true))
		{
			return false;
		}

		return true;
	}

	public function checkCustomMenuId(string $menuItemId): string
	{
		return match ($menuItemId)
		{
			'ANALYTICS_SALES_FUNNEL', 'ANALYTICS_MANAGERS', 'ANALYTICS_DIALOGS', 'ANALYTICS_CALLS' => 'analytics',
			'SMART_INVOICE' => 'INVOICE',
			default => $menuItemId,
		};
	}

	public function checkAvailabilityByToolId(string $toolId): bool
	{
		$listDisabledIdMenuItem = $this->getDisabledMenuItemListId();

		if (array_key_exists($toolId, $listDisabledIdMenuItem))
		{
			return false;
		}

		return true;
	}

	public function getDisabledMenuItemListId(): array
	{
		if (is_array($this->disabledMenuItemListId))
		{
			return $this->disabledMenuItemListId;
		}

		$cache = Main\Application::getInstance()->getCache();
		$cacheTtl = 60 * 60 * 24 * 7; // 7 days
		$this->disabledMenuItemListId = [];

		if ($cache->initCache($cacheTtl, self::CACHE_ID_DISABLED_TOOLS, self::CACHE_PATH))
		{
			$this->disabledMenuItemListId = $cache->getVars();
		}
		else
		{
			$toolList = $this->getToolList();

			foreach ($toolList as $tool)
			{
				if (!$tool->isEnabled() && $tool->getMenuItemId())
				{
					$this->disabledMenuItemListId[$tool->getId()] = $tool->getMenuItemId();
					$additionalMenuItemIds = $tool->getAdditionalMenuItemIds();

					foreach ($additionalMenuItemIds as $menuItemId)
					{
						$this->disabledMenuItemListId[] = $menuItemId;
					}
				}

				$subgroups = $tool->getSubgroups();

				foreach ($subgroups as $id => $subgroup)
				{
					if (isset($subgroup['menu_item_id'], $subgroup['enabled']) && !$subgroup['enabled'])
					{
						$this->disabledMenuItemListId[$id] = $subgroup['menu_item_id'];
					}
				}
			}

			$cache->startDataCache();
			$cache->endDataCache($this->disabledMenuItemListId);
		}

		return $this->disabledMenuItemListId;
	}

	public function getSorter(): Sorter
	{
		if (!isset($this->sorter))
		{
			$this->init();
		}

		return $this->sorter;
	}

	public function getFirstPageChanger(): FirstPageChanger
	{
		if (!isset($this->firstPageChanger))
		{
			$this->firstPageChanger = new FirstPageChanger($this);
		}

		return $this->firstPageChanger;
	}

	public function clearDisabledMenuItemIdList(): void
	{
		$this->disabledMenuItemListId = null;
	}

	public function clearCache(): void
	{
		$this->clearDisabledMenuItemIdList();
		Main\Application::getInstance()->getCache()->clean(self::CACHE_ID_DISABLED_TOOLS, self::CACHE_PATH);
	}
}