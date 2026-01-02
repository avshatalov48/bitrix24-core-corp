<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Restriction\ToolAvailabilityManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

class CatalogStore implements Tabable {

	/**
	 * @var Context $context
	 */
	private $context;

	public function isAvailable()
	{
		return (
			!$this->context->extranet
			&& IsModuleInstalled('catalog')
			&& \CModule::IncludeModule('catalog')
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
			&& ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
			&& !\Bitrix\Catalog\Store\EnableWizard\Manager::isOnecMode()
		);
	}

	public function getData()
	{
		return [
			'id' => 'catalog_store',
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => 'catalog_store',
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData() {
		return [
			'id' => 'catalog_store',
			'sort' => 400,
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'sectionCode' => 'catalog_store',
			'section_code' => 'crm',
			'color' => '#05b5ab',
			'imageUrl' => 'catalog/icon-catalog-store.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'analytics' => [
					'tool' => 'inventory',
					'category' => 'inventory',
					'event' => 'open_section',
					'c_section' => 'ava_menu',
				],
			],
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => "catalog:catalog.store.document.list",
			'scriptPath' => Manager::getComponentPath('catalog:catalog.store.document.list'),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'useLargeTitleMode' => true,
				],
			],
			'params' => [],
		];
	}

	public function shouldShowInMenu()
	{
		return $this->isAvailable();
	}

	public function canBeRemoved()
	{
		return true;
	}

	public function defaultSortValue()
	{
		return 100;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_CATALOG_STORE");
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_CATALOG_STORE_SHORT");
	}

	public function getId()
	{
		return 'catalog_store';
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getIconId(): string
	{
		return 'stock';
	}
}
