<?php

namespace Bitrix\Intranet\Integration\Rest;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Rest\Lang;
use Bitrix\Rest\PlacementTable;
use Bitrix\Main\Config\Option;
use Bitrix\Rest\Marketplace\Url;

class EventHandler
{
	/**
	 * Event adds new item to menu.
	 * @param Event $event
	 * @return EventResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onRegisterPlacementLeftMenu(Event $event): EventResult
	{
		$id = (int)$event->getParameter('ID');

		if ($id > 0 && Loader::includeModule('rest'))
		{
			return static::reloadPlacementLeftMenu();
		}

		return new EventResult(EventResult::ERROR);
	}

	/**
	 * Event deletes new item to menu.
	 * @param Event $event
	 * @return EventResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function onUnRegisterPlacementLeftMenu(Event $event): EventResult
	{
		$id = (int)$event->getParameter('ID');

		if ($id > 0 && Loader::includeModule('rest'))
		{
			return static::reloadPlacementLeftMenu();
		}

		return new EventResult(EventResult::ERROR);
	}

	private static function reloadPlacementLeftMenu()
	{
		$menuList = [];
		$placementList = [];
		$option = Option::get('intranet', 'left_menu_items_marketplace_' . SITE_ID);
		if (!empty($option))
		{
			$menuList = unserialize($option, ['allowed_classes' => false]);
			foreach ($menuList as $key => $item)
			{
				if ((int)$item['PLACEMENT_ID'] > 0)
				{
					$placementList[$item['PLACEMENT_ID']] = $key;
				}
			}
		}

		$needSave = false;
		$res = PlacementTable::getList(
			[
				'filter' => [
					'=PLACEMENT' => \CIntranetRestService::LEFT_MENU,
				],
				'select' => [
					'ID',
					'ICON_ID',
					'TITLE',
					'GROUP_NAME',
					'COMMENT',
					'APP_ID',
					'ADDITIONAL',
				],
			]
		);

		foreach ($res->fetchCollection() as $handler)
		{
			if ($placementList[$handler->getId()])
			{
				unset($placementList[$handler->getId()]);
				continue;
			}
			$title = $handler->getTitle();
			$data = [
				'TITLE' => '',
			];

			$handler->fillLangAll();
			if (!is_null($handler->getLangAll()))
			{
				foreach ($handler->getLangAll() as $lang)
				{
					$data['LANG_ALL'][$lang->getLanguageId()] = [
						'TITLE' => $lang->getTitle(),
						'DESCRIPTION' => $lang->getDescription(),
						'GROUP_NAME' => $lang->getGroupName(),
					];
				}
			}

			$data = Lang::mergeFromLangAll($data);
			if ($data['TITLE'] !== '')
			{
				$title = $data['TITLE'];
			}

			if ($title === '')
			{
				continue;
			}

			$url = Url::getApplicationPlacementUrl($handler->getId());
			$menuList[] = [
				'ID' => crc32($url),
				'LINK' => $url,
				'TEXT' => $title,
				'ADDITIONAL_LINKS' => [
					$url,
				],
				'PLACEMENT_ID' => $handler->getId(),
			];
			$needSave = true;
		}

		foreach ($placementList as $placementId => $key)
		{
			unset($menuList[$key]);
			$needSave = true;
		}

		if ($needSave)
		{
			Option::set(
				'intranet',
				'left_menu_items_marketplace_' . SITE_ID,
				serialize($menuList),
				SITE_ID
			);

			if (defined('BX_COMP_MANAGED_CACHE'))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('bitrix24_left_menu');
			}

			return new EventResult(EventResult::SUCCESS);
		}

		return new EventResult(EventResult::ERROR);
	}
}
