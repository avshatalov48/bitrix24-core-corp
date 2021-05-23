<?php

namespace Bitrix\Crm\Agent\Search;

class CompanySearchShortIndex extends BaseSearchShortIndex
{
	protected static
		$typeId = \CCrmOwnerType::Company;

	protected function getList(int $limit, int $steps)
	{
		return \CCrmCompany::getListEx(
			['ID' => 'ASC'],
			['CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID'],
			['QUERY_OPTIONS' => ['LIMIT' => $limit, 'OFFSET' => $steps]]
		);
	}
}