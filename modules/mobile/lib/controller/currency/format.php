<?php

namespace Bitrix\Mobile\Controller\Currency;

use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use CCurrencyLang;

Loader::includeModule('currency');

class Format extends Controller
{
	public function getAction($currencyId)
	{
		return CCurrencyLang::GetFormatDescription($currencyId);
	}

	public function listAction(): array
	{
		$result = [];
		$rows = CurrencyTable::getList([
			'select' => ['CURRENCY'],
			'order' => ['SORT' => 'ASC', 'CURRENCY' => 'ASC'],
			'limit' => 100,
		]);
		while ($row = $rows->fetch())
		{
			$currencyId = $row['CURRENCY'];
			$result[$currencyId] = CCurrencyLang::GetFormatDescription($currencyId);
		}
		return $result;
	}
}
