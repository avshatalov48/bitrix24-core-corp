<?php

namespace Bitrix\Crm\Search\Result\Provider;

use Bitrix\Crm\Item;
use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Search\Result\Provider;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Factory;

class FactoryBased extends Provider
{
	/** @var Factory */
	private $factory;

	public function __construct(Factory $factory)
	{
		$this->factory = $factory;

		parent::__construct();
	}

	public function getSearchResult(string $searchQuery): Result
	{
		$result = new Result();

		$searchQuery = trim($searchQuery);
		if ($searchQuery === '')
		{
			return $result;
		}

		$filter = [];
		if ($this->useDenominationSearch)
		{
			$filter['%' . Item::FIELD_NAME_TITLE] = $searchQuery;
		}
		else
		{
			$filter['SEARCH_CONTENT'] = $searchQuery;
			SearchEnvironment::prepareSearchFilter(
				$this->factory->getEntityTypeId(),
				$filter,
				[
					'ENABLE_PHONE_DETECTION' => false,
				],
			);
		}

		$items = $this->factory->getItemsFilteredByPermissions(
			[
				'select' => [Item::FIELD_NAME_ID],
				'filter' => $filter,
				'limit' => $this->limit,
			],
			$this->userId,
		);

		foreach ($items as $item)
		{
			$result->addId($item->getId());
		}

		return $result;
	}
}
