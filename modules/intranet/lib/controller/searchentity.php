<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\ActionFilter;

require_once __DIR__."/../../install/templates/bitrix24/components/bitrix/search.title/.default/class.php";

class SearchEntity extends \Bitrix\Main\Engine\Controller
{
	const ENTITY_SONETGROUPS = 'sonetgroups';
	const ENTITY_MENUITEMS = 'menuitems';

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new ActionFilter\UserType([
			'employee',
			'extranet',
		]);

		return $preFilters;
	}

	private static function getAllEntities(): array
	{
		return array(
			self::ENTITY_SONETGROUPS,
			self::ENTITY_MENUITEMS
		);
	}

	public function getAllAction($entity)
	{
		$entity = trim($entity);

		if ($entity == '')
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_EMPTY'), 'INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_EMPTY'));
			return null;
		}
		if (!in_array($entity, self::getAllEntities()))
		{
			$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_INCORRECT'), 'INTRANET_CONTROLLER_SEARCHENTITY_GETALL_ENTITY_INCORRECT'));
			return null;
		}


		$items = array();

		if ($entity == self::ENTITY_SONETGROUPS)
		{
			$sonetGroupsList = \CB24SearchTitle::getSonetGroups();
			foreach($sonetGroupsList as $group)
			{
				$items['G'.$group['ID']] = \CB24SearchTitle::convertAjaxToClientDb($group, $entity);
			}
		}
		elseif ($entity == self::ENTITY_MENUITEMS)
		{
			$menuItemsList = \CB24SearchTitle::getMenuItems();
			foreach($menuItemsList as $menuItem)
			{
				$items['M'.$menuItem['URL']] = \CB24SearchTitle::convertAjaxToClientDb($menuItem, $entity);
			}
		}

		return array(
			'items' => $items
		);
	}
}
