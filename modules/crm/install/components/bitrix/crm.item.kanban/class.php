<?php

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;

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

		$this->arResult['entityTypeName'] = CCrmOwnerType::ResolveName($this->entityTypeId);
		$this->arResult['categoryId'] = $this->category->getId();

		if (!$this->factory->isStagesEnabled())
		{
			LocalRedirect(Service\Container::getInstance()->getRouter()->getItemListUrl($this->entityTypeId, $this->category->getId()));
		}

		$this->includeComponentTemplate();
	}

	protected function getToolbarSettingsItems(): array
	{
		return array_merge([
			[
				'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_TITLE'),
				'items' => [
					[
						'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_FIELDS_VIEW'),
						'onclick' => new Buttons\JsEvent('crm-kanban-settings-fields-view'),
					],
					[
						'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_FIELDS_EDIT'),
						'onclick' => new Buttons\JsEvent('crm-kanban-settings-fields-edit'),
					],
				]
			]
		], parent::getToolbarSettingsItems());
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
		return Service\Container::getInstance()->getRouter()->getKanbanUrl($this->entityTypeId, $categoryId);
	}

	protected function getListViewType(): string
	{
		return Router::LIST_VIEW_KANBAN;
	}
}
