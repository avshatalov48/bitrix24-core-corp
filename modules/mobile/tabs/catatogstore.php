<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

class CatalogStore implements Tabable {

	const MINIMAL_API_VERSION = 49;

	/**
	 * @var Context $context
	 */
	private $context;

	public function isAvailable()
	{
		return (
			!$this->context->extranet
			&& Mobile::getApiVersion() >= self::MINIMAL_API_VERSION
			&& IsModuleInstalled('catalog')
			&& \CModule::IncludeModule('catalog')
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
		);
	}

	public function getData()
	{
		return [
			'id' => 'catalog_store',
			'sort' => $this->defaultSortValue(),
			'imageName' => 'catalog_store',
			'badgeCode' => 'catalog_store',
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData() {
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'sectionCode' => 'catalog',
			'color' => '#05b5ab',
			'imageUrl' => 'catalog/icon-catalog-store.png',
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
			],
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => "catalog.store.document.list",
			'scriptPath' => Manager::getComponentPath('catalog.store.document.list'),
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
		return true;
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
}