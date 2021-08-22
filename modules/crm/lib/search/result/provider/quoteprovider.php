<?php

namespace Bitrix\Crm\Search\Result\Provider;

use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Search\SearchEnvironment;

class QuoteProvider extends \Bitrix\Crm\Search\Result\Provider
{
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
			$filter['%TITLE'] = $searchQuery;
		}
		else
		{
			$filter['FIND'] = $searchQuery;
			SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Quote, $filter);
		}

		$quotes = \CCrmQuote::GetList(
			[],
			$filter,
			false,
			['nTopCount' => $this->limit],
			['ID']
		);

		while ($quote = $quotes->Fetch())
		{
			$result->addId($quote['ID']);
		}

		return $result;
	}
}
