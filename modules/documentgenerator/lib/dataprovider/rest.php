<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;

class Rest extends DataProvider
{
	/**
	 * @return array
	 */
	public function getFields()
	{
		return [];
	}

	public function isLoaded()
	{
		return true;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		return true;
	}
}