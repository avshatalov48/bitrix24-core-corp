<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\ExternalSource\Internal\EO_ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class ExternalConnectionProvider extends BaseProvider
{
	public const ENTITY_ID = 'biconnector-external-connection';
	protected const ELEMENTS_LIMIT = 20;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options = $options;
	}

	public function isAvailable(): bool
	{
		global $USER;

		return
			is_object($USER)
			&& $USER->isAuthorized()
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS)
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG)
		;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addItems($this->getItems([]));
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

	private function getElements(array $filter): array
	{
		$result = [];
		$ormParams = [
			'filter' => $filter,
			'limit' => self::ELEMENTS_LIMIT,
		];

		$sources = ExternalSourceTable::getList($ormParams)->fetchCollection();
		foreach ($sources as $source)
		{
			$result[] = $this->makeItem($source);
		}

		return $result;
	}

	public function getItems(array $ids): array
	{
		$filter = [];
		if (!empty($ids))
		{
			$filter['=ID'] = $ids;
		}

		return $this->getElements($filter);
	}

	private function makeItem(EO_ExternalSource $source): Item
	{
		$itemParams = [
			'id' => $source->getId(),
			'entityId' => self::ENTITY_ID,
			'title' => $source->getTitle(),
			'tabs' => 'connections',
			'avatar' => "/bitrix/images/biconnector/database-connections/{$source->getType()}.svg",
			'link' => "/bitrix/components/bitrix/biconnector.externalconnection/slider.php?sourceId={$source->getId()}",
			'linkTitle' => Loc::getMessage('EXTERNAL_CONNECTION_PROVIDER_LINK_TEXT'),
			'customData' => [
				'connectionType' => $source->getType(),
			],
		];

		return new Item($itemParams);
	}
}
