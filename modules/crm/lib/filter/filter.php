<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm;

class Filter extends \Bitrix\Main\Filter\Filter
{
	protected function getDateFieldNames(): array
	{
		$result = [];
		$fields = $this->getFields();
		foreach ($fields as $field)
		{
			if ($field->getType() === 'date')
			{
				$result[] = $field->getName();
			}
		}

		return $result;
	}

	/**
	 * Prepare list filter params.
	 * @param array $filter Source Filter.
	 * @return void
	 */
	public function prepareListFilterParams(array &$filter): void
	{
		foreach ($filter as $k => $v)
		{
			$match = array();
			if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				Crm\UI\Filter\Range::prepareFrom($filter, $match[1], $v);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				if ($v != '' && in_array($match[1], $this->getDateFieldNames()) && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				{
					$v = \CCrmDateTimeHelper::SetMaxDayTime($v);
				}
				Crm\UI\Filter\Range::prepareTo($filter, $match[1], $v);
			}

			$this->entityDataProvider->prepareListFilterParam($filter, $k);
		}
		Crm\UI\Filter\EntityHandler::internalize($this->getFieldArrays(), $filter);
	}
}