<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Menu;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Menu;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class MenuItemFactory
{
	public static function createEditMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_EDIT')))
			->setHideIfReadonly()
			->setSort(9900)
		;
	}

	public static function createViewMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_VIEW')))
			->setSort(9900)
		;
	}

	public static function createAddFileMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_ADD_FILE')))
			->setHideIfReadonly()
			->setSort(9990)
		;
	}

	public static function createChangeResponsibleMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_CHANGE_RESPONSIBLE')))
			->setHideIfReadonly()
			->setSort(9991)
		;
	}

	public static function createDownloadFileMenuItem(string $filename = null): MenuItem
	{
		$title = (string)Loc::getMessage('CRM_TIMELINE_MENU_DOWNLOAD_FILE');
		if (isset($filename))
		{
			$title = sprintf('%s "%s"', $title, $filename);
		}

		return (new MenuItem($title))
			->setHideIfReadonly()
			->setSort(9995)
		;
	}

	public static function createDeleteMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_DELETE')))
			->setHideIfReadonly()
			->setSort(9999)
		;
	}

	public static function createFromArray(array $menuItem): MenuItem
	{
		if (isset($menuItem['delimiter']) && $menuItem['delimiter'])
		{
			return new MenuItemDelimiter($menuItem['text'] ?? '');
		}

		$menuItemItems = $menuItem['items'] ?? null;
		if (is_array($menuItemItems))
		{
			$menuItems = [];
			$index = 0;

			foreach ($menuItem['items'] as $item)
			{
				$id = $item['id'] ?? "submenu_$index";
				$index++;

				$menuItems[$id] = self::createFromArray($item);
			}

			return new MenuItemSubmenu(
				$menuItem['text'],
				(new Menu())
					->setItems($menuItems)
			);
		}

		return (new MenuItem($menuItem['text']))
			->setAction(self::createMenuItemAction($menuItem))
		;
	}

	private static function createMenuItemAction(array $menuItem): ?Action
	{
		if (isset($menuItem['href']))
		{
			return new Action\Redirect(new Uri($menuItem['href']));
		}
		if (isset($menuItem['onclick']))
		{
			return new Action\JsCode($menuItem['onclick']);
		}

		return null;
	}
}
