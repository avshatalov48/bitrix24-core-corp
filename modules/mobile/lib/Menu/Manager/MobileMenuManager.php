<?php

namespace Bitrix\Mobile\Menu\Manager;

abstract class MobileMenuManager
{
	protected static function addMenuItem(array $menu, string $sectionCode, array $item): array
	{
		if (empty($item))
		{
			return $menu;
		}

		$sectionIndex = static::getSectionIndexByCode($menu, $sectionCode);

		if ($sectionIndex !== null)
		{
			return static::addItemToSection($menu, $sectionIndex, $item);
		}

		$toolsItemIndex = static::getSectionIndexByCode($menu, 'tools');

		if ($toolsItemIndex !== null)
		{
			return static::addItemToSection($menu, $toolsItemIndex, $item);
		}

		return $menu;
	}

	protected static function getSectionIndexByCode(array $menu, string $code): ?int
	{
		foreach ($menu as $index => $section)
		{
			if (!is_array($section))
			{
				continue;
			}

			if (isset($section['code']) && $section['code'] === $code)
			{
				return $index;
			}
		}

		return null;
	}

	protected static function addMenuItems(array $menu, string $sectionCode, array $items): array
	{
		if (empty($items))
		{
			return $menu;
		}

		$sectionIndex = static::getSectionIndexByCode($menu, $sectionCode);

		if ($sectionIndex !== null)
		{
			$menu[$sectionIndex]['items'] = array_merge(
				$menu[$sectionIndex]['items'] ?? [],
				$items
			);
		}

		return $menu;
	}

	private static function addItemToSection(array $menu, int $sectionIndex, array $item): array
	{
		if (!isset($menu[$sectionIndex]['items']))
		{
			$menu[$sectionIndex]['items'] = [];
		}

		$menu[$sectionIndex]['items'][] = $item;

		return $menu;
	}
}
