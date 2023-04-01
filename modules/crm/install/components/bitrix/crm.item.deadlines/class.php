<?php

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\UI\Tools\ToolBar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');

class CrmItemDeadlinesComponent extends Bitrix\Crm\Component\ItemList
{
	public function executeComponent()
	{
		Service\Container::getInstance()->getLocalization()->loadKanbanMessages();

		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$restriction = RestrictionManager::getItemListRestriction($this->entityTypeId);
		if (!$restriction->hasPermission())
		{
			$this->arResult['restriction'] = $restriction;
			$this->arResult['entityName'] = \CCrmOwnerType::ResolveName($this->entityTypeId);
			$this->includeComponentTemplate('restrictions');
			return;
		}

		$this->arResult['entityTypeName'] = CCrmOwnerType::ResolveName($this->entityTypeId);
		$this->arResult['categoryId'] = $this->category->getId();
		$this->arResult['entityTypeDescription'] = $this->factory->getEntityDescription();
		$this->arResult['isCountersEnabled'] = $this->factory->getCountersSettings()->isCountersEnabled();

		$this->includeComponentTemplate();
	}

	protected function getToolbarSettingsItems(): array
	{
		return array_merge([ToolBar::getKanbanSettings()], parent::getToolbarSettingsItems());
	}

	protected function getToolbarViews(): array
	{
		$views = parent::getToolbarViews();

		$views[Service\Router::LIST_VIEW_KANBAN]['isActive'] = false;
		$views[Service\Router::LIST_VIEW_LIST]['isActive'] = false;
		$views[Service\Router::LIST_VIEW_DEADLINES]['isActive'] = true;

		return $views;
	}

	protected function getListUrl(int $categoryId = null): \Bitrix\Main\Web\Uri
	{
		return Service\Container::getInstance()->getRouter()->getDeadlinesUrl($this->entityTypeId, $categoryId);
	}

	protected function getListViewType(): string
	{
		return Router::LIST_VIEW_DEADLINES;
	}
}