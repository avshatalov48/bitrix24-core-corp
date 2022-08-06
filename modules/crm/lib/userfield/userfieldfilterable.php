<?php

namespace Bitrix\Crm\UserField;

interface UserFieldFilterable
{
	/**
	 * Get filtered user fields
	 *
	 * @return array|null
	 */
	public function getFilteredUserFields(): ?array;
}
