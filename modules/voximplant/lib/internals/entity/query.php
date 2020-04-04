<?php

namespace Bitrix\Voximplant\Internals\Entity;

/**
 * Class Query
 * @package Bitrix\Voximplant\Internals\Entity
 * @internal
 */
class Query extends \Bitrix\Main\Entity\Query
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