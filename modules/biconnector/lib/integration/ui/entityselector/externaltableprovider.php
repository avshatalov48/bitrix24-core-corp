<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\EO_ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\EntityError;
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
		$items = $this->getElements($filter, $dialog);

		$dialog->addItems($items);
	}

	private function getElements(array $filter, Dialog $dialog): array
	{
		$result = [];
		$searchString = $filter['searchString'];

		if (!$this->externalSource)
		{
			$dialog->addError(
				new EntityError(
					self::ENTITY_ID,
					Loc::getMessage('EXTERNAL_TABLE_PROVIDER_EMPTY_EXTERNAL_SOURCE')
				)
			);

			return [];
		}

		$type = ExternalSource\Type::tryFrom($this->externalSource->getType());
		if (!$type)
		{
			$dialog->addError(
				new EntityError(
					self::ENTITY_ID,
					Loc::getMessage('EXTERNAL_TABLE_PROVIDER_UNKNOWN_TYPE_EXTERNAL_SOURCE')
				)
			);

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
			$queryResult = $source->getEntityList($searchString);
			$tables = [];
			if ($queryResult->isSuccess())
			{
				$tables = $queryResult->getData();

				$cacheManager->set($cacheKey, $tables);
			}
			else
			{
				foreach ($queryResult->getErrors() as $error)
				{
					$dialog->addError(new EntityError(self::ENTITY_ID, $error->getMessage(), $error->getCode()));
				}
			}
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