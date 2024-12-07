<?php

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\UI\Tools\ToolBar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('crm');

class CrmItemKanbanComponent extends Bitrix\Crm\Component\ItemList
{
	protected function initCategory(): ?Category
	{
		// there is always a category in the kanban view
		return (parent::initCategory() ?? $this->factory->createDefaultCategoryIfNotExist());
	}

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
		$this->arResult['performance'] = $this->arParams['performance'] ?? [];
		$this->arResult['pathToMerge'] = $this->router->getEntityMergeUrl($this->entityTypeId);

		$addOperationRestriction = RestrictionManager::getAddOperationRestriction($this->factory->getEntityTypeId());
		$this->arResult['addItemPermittedByTariff'] = $addOperationRestriction->hasPermission();

		if (!$this->factory->isStagesEnabled())
		{
			LocalRedirect($this->router->getItemListUrl($this->entityTypeId, $this->category->getId()));
		}

		$isEntityInCustomSection = IntranetManager::isEntityTypeInCustomSection($this->entityTypeId);
		if ($isEntityInCustomSection)
		{
			$section = Dictionary::SECTION_CUSTOM;
		}
		else
		{
			$section = Dictionary::getAnalyticsEntityType($this->entityTypeId) . '_section';
		}
		$this->arResult['analytics'] = [
			'c_section' => $section,
			'c_sub_section' => Dictionary::SUB_SECTION_KANBAN,
		];

		$this->arResult['isCustomSection'] = $isEntityInCustomSection;

		$this->includeComponentTemplate();
	}

	protected function getToolbarSettingsItems(): array
	{
		return array_merge([ToolBar::getKanbanSettings()], parent::getToolbarSettingsItems());
	}

	protected function getToolbarViews(): array
	{
		$views = parent::getToolbarViews();

		$views[Service\Router::LIST_VIEW_KANBAN]['isActive'] = true;
		$views[Service\Router::LIST_VIEW_LIST]['isActive'] = false;

		return $views;
	}

	protected function getListUrl(int $categoryId = null): \Bitrix\Main\Web\Uri
	{
		return $this->router->getKanbanUrl($this->entityTypeId, $categoryId);
	}

	protected function getListViewType(): string
	{
		return Router::LIST_VIEW_KANBAN;
	}

	protected function configureAnalyticsEventBuilder(\Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder $builder): void
	{
		parent::configureAnalyticsEventBuilder($builder);

		if (!$this->isEmbedded())
		{
			$builder->setSubSection(Dictionary::SUB_SECTION_KANBAN);
		}
	}
}
