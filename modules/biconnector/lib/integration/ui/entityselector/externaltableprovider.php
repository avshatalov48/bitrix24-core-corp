<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\EO_ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Application;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class ExternalTableProvider extends BaseProvider
{
	public const ENTITY_ID = 'biconnector-external-table';

	private ?EO_ExternalSource $externalSource;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$connectionId = (int)($options['connectionId'] ?? 0);
		if ($connectionId)
		{
			$this->externalSource = ExternalSourceTable::getById($connectionId)->fetchObject();
		}
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
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
			'searchString' => $query,
		];
		$items = $this->getElements($filter);

		$dialog->addItems($items);
	}

	private function getElements(array $filter): array
	{
		$result = [];
		$searchString = $filter['searchString'];

		if (!$this->externalSource)
		{
			return [];
		}

		$type = ExternalSource\Type::tryFrom($this->externalSource->getType());
		if (!$type)
		{
			return [];
		}

		$cacheKey = "biconnector_external_tables_query_{$this->externalSource->getId()}_{$searchString}";
		$cacheManager = Application::getInstance()->getManagedCache();

		if ($cacheManager->read(3600, $cacheKey))
		{
			$tables = $cacheManager->get($cacheKey);
		}
		else
		{
			$source = ExternalSource\Source\Factory::getSource($type, $this->externalSource->getId());
			$tables = $source->getEntityList($searchString);
			$cacheManager->set($cacheKey, $tables);
		}

		foreach ($tables as $table)
		{
			$result[] = $this->makeItem($table);
		}

		return $result;
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	private function makeItem(array $data): Item
	{
		$itemParams = [
			'id' => $data['ID'],
			'entityId' => self::ENTITY_ID,
			'title' => $data['TITLE'],
			'tabs' => 'tables',
			'customData' => [
				'description' => $data['DESCRIPTION'],
				'datasetName' => $data['DATASET_NAME'],
			],
		];

		return new Item($itemParams);
	}
}