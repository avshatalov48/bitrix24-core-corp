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
			->setIcon('edit')
			->setHideIfReadonly()
			->setSort(9900)
		;
	}

	public static function createViewMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_VIEW')))
			->setIcon('view')
			->setSort(9900)
		;
	}

	public static function createDeleteMenuItem(): MenuItem
	{
		return (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_DELETE')))
			->setIcon('delete')
			->setHideIfReadonly()
			->setSort(9999)
		;
	}

	public static function createFromArray(array $menuItem): MenuItem
	{
		if ($menuItem['delimiter'])
		{
			return new MenuItemDelimiter($menuItem['text'] ?? '');
		}

		if (is_array($menuItem['items']))
		{
			$menuItems = [];
			foreach ($menuItem['items'] as $item)
			{
				$id = $item['id'] ?? \Bitrix\Main\Security\Random::getString(4, true);
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
