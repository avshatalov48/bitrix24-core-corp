<?php

namespace Bitrix\Crm\Agent\Search;

class LeadSearchShortIndex extends BaseSearchShortIndex
{
	protected static $typeId = \CCrmOwnerType::Lead;

	protected function getList(int $limit, int $steps)
	{
		return \CCrmLead::getListEx(
			['ID' => 'ASC'],
			['CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID'],
			['QUERY_OPTIONS' => ['LIMIT' => $limit, 'OFFSET' => $steps]]
		);
	}
}