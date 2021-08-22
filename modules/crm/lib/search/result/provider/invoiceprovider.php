<?php

namespace Bitrix\Crm\Search\Result\Provider;

use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Search\SearchEnvironment;

class InvoiceProvider extends \Bitrix\Crm\Search\Result\Provider
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
			$filter['%ORDER_TOPIC'] = $searchQuery;
		}
		else
		{
			$filter['FIND'] = $searchQuery;
			SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Invoice, $filter);
		}

		$invoices = \CCrmInvoice::GetList(
			[],
			$filter,
			false,
			['nTopCount' => $this->limit],
			['ID']
		);

		while ($invoice = $invoices->Fetch())
		{
			$result->addId($invoice['ID']);
		}

		return $result;
	}
}
