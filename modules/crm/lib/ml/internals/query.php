<?php

namespace Bitrix\Crm\Ml\Internals;

/**
 * Class Query
 * @internal
 */
class Query extends \Bitrix\Main\ORM\Query\Query
{
	/**
	 * Generates where condition by filter.
	 * @return string
	 */
	public function getWhere()
	{
		return $this->buildWhere();
	}
}
