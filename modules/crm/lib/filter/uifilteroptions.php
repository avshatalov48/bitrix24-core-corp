<?php

namespace Bitrix\Crm\Filter;

class UiFilterOptions extends \Bitrix\Main\UI\Filter\Options
{
	public function getFilter($sourceFields = [])
	{
		$value = parent::getFilter($sourceFields);
		unset(
			$value['__JOINS'],
			$value['__CONDITIONS'],
			$value['__INNER_FILTER'],
			$value['LOGIC']
		);

		return $value;
	}
}
