<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProvider;

/**
 * Fake DataProvider to mark suspended (moved to recycle bin) documents
 */
class Suspended extends DataProvider
{
	/**
	 * @return array
	 */
	public function getFields()
	{
		return [];
	}
}