<?php

namespace Bitrix\Crm\Agent\Search;

class ContactSearchShortIndex extends BaseSearchShortIndex
{
	protected static $typeId = \CCrmOwnerType::Contact;

	protected function getList(int $limit, int $steps)
	{
		return \CCrmContact::getListEx(
			['ID' => 'ASC'],
			['CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID'],
			['QUERY_OPTIONS' => ['LIMIT' => $limit, 'OFFSET' => $steps]]
		);
	}
}