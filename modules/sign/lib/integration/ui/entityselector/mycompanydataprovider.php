<?php

namespace Bitrix\Sign\Integration\Ui\EntitySelector;

use Bitrix\Crm\Integration\UI\EntitySelector\CompanyProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector;
use Bitrix\UI\EntitySelector;

final class MyCompanyDataProvider extends EntitySelector\BaseProvider
{
	private const ENTITY_SELECTOR_MYCOMPANY_ENTITY_ID = 'sign-mycompany';

	private CompanyProvider $crmEntitySelectorCompanyDataProvider;

	public function __construct()
	{
		parent::__construct();

		if (Loader::includeModule('crm'))
		{
			$this->crmEntitySelectorCompanyDataProvider = new CompanyProvider(
				['enableMyCompanyOnly' => true]
			);
		}

	}

	public function isAvailable(): bool
	{
		global $USER;
		$storage = Storage::instance();

		$crmEntitySelectorExist = Loader::includeModule('crm')
			&& isset($this->crmEntitySelectorCompanyDataProvider)
		;

		return $storage->isAvailable()
			&& $storage->isB2eAvailable()
			&& $USER->IsAuthorized()
			&& $crmEntitySelectorExist
		;
	}

	public function getItems(array $ids): array
	{
		$items = $this->crmEntitySelectorCompanyDataProvider->getItems($ids);
		$this
			->changeTitleToItems($items)
			->changeVisibilityToItems($items)
		;

		return $items;
	}

	public function getPreselectedItems(array $ids): array
	{
		return $this->crmEntitySelectorCompanyDataProvider->getPreselectedItems($ids);
	}

	public function fillDialog(EntitySelector\Dialog $dialog): void
	{
		$recentItems = $dialog->getRecentItems();
		$myCompanies = Connector\Crm\MyCompany::listItems(20);
		foreach ($myCompanies as $myCompany)
		{
			$recentItems->add(
				new EntitySelector\RecentItem([
					'id' => $myCompany->id,
					'entityId' => self::ENTITY_SELECTOR_MYCOMPANY_ENTITY_ID,
				])
			);
		}
	}

	public function doSearch(EntitySelector\SearchQuery $searchQuery, EntitySelector\Dialog $dialog): void
	{
		$this->crmEntitySelectorCompanyDataProvider->doSearch($searchQuery, $dialog);

		$items = $dialog->getItemCollection()->getAll();
		$this
			->changeTitleToItems($items)
			->changeVisibilityToItems($items)
		;
	}

	/**
	 * @param array<EntitySelector\Item> $items
	 *
	 * @return static
	 */
	private function changeTitleToItems(array $items): static
	{
		foreach ($items as $item)
		{
			$item->setTitle(
				(new Connector\Crm\MyCompany($item->getId()))->getName()
			);
		}

		return $this;
	}

	/**
	 * All my companies is visible
	 *
	 * @param array<EntitySelector\Item> $items
	 *
	 * @return static
	 */
	private function changeVisibilityToItems(array $items): static
	{
		foreach ($items as $item)
		{
			$item->setTitle(
				(new Connector\Crm\MyCompany($item->getId()))->getName()
			);
			$item->setHidden(false);
		}

		return $this;
	}
}