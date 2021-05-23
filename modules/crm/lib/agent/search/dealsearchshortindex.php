<?php

namespace Bitrix\Crm\Agent\Search;

class DealSearchShortIndex extends BaseSearchShortIndex
{
	protected static $typeId = \CCrmOwnerType::Deal;

	protected function getList(int $limit, int $steps)
	{
		return \CCrmDeal::getListEx(
			['ID' => 'ASC'],
			['CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID'],
			['QUERY_OPTIONS' => ['LIMIT' => $limit, 'OFFSET' => $steps]]
		);
	}
}