<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetTag;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

class SupersetDashboardTagProvider extends BaseProvider
{
	public const ENTITY_ID = 'biconnector-superset-dashboard-tag';
	protected const ELEMENTS_LIMIT = 20;

	private const TAGS_TAB_ID = 'all';
	private const TAGS_ORDER = 'desc nulls last';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options = $options;
	}

	public function isAvailable(): bool
	{
		global $USER;

		return is_object($USER) && $USER->isAuthorized();
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->addTagsTab($dialog);
		$recentItems = $dialog->getRecentItems()->getEntityItems(self::ENTITY_ID);
		$recentItemsCount = count($recentItems);

		if ($recentItemsCount < self::ELEMENTS_LIMIT)
		{
			$elements = $this->getElements([], self::ELEMENTS_LIMIT);
			$dialog->addRecentItems($elements);
		}

		$dialog->setFooter('BX.BIConnector.EntitySelector.TagFooter', $this->getFooterOptions());
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchQuery->setCacheable(false);
		$query = $searchQuery->getQuery();

		$filter = [
			'%TITLE' => $query,
		];
		$items = $this->getElements($filter);

		$dialog->addItems($items);
	}

	public function getItems(array $ids): array
	{
		$filter = !empty($ids) ? ['ID' => $ids] : [];

		return $this->getElements($filter);
	}

	public function getElements(array $filter = [], ?int $limit = null): array
	{
		$result = [];
		$ormParams = [
			'filter' => $filter,
			'limit' => $limit ?? self::ELEMENTS_LIMIT,
		];
		$elements = SupersetTagTable::getList($ormParams);
		foreach ($elements->fetchCollection() as $element)
		{
			$result[] = $this->makeItem($element);
		}

		return $result;
	}

	private function addTagsTab(Dialog $dialog): void
	{
		$dialog->addTab(
			new Tab([
				'id' => self::TAGS_TAB_ID,
				'title' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_TAG_PROVIDER_TAB_LABEL'),
				'stub' => true,
				'itemOrder' => [
					'sort' => self::TAGS_ORDER,
				],
			])
		);
	}

	private function makeItem(EO_SupersetTag $element): Item
	{
		$itemParams = [
			'id' => $element->getId(),
			'entityId' => self::ENTITY_ID,
			'title' => $element->getTitle(),
			'description' => null,
			'tabs' => [self::TAGS_TAB_ID],
		];

		return new Item($itemParams);
	}

	private function getFooterOptions(): array
	{
		return [
			'canCreateTag' => AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_TAG_MODIFY),
		];
	}
}
