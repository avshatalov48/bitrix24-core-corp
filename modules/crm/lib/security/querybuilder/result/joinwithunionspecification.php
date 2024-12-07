<?php

namespace Bitrix\Crm\Security\QueryBuilder\Result;

class JoinWithUnionSpecification
{
	public static function getInstance(): self
	{
		return new JoinWithUnionSpecification();
	}

	/**
	 * Checks whether optimization mode with UNION can be used.
	 * @param array $filter
	 * @return bool
	 */
	public function isSatisfiedBy(array $filter): bool
	{
		if (empty($filter))
		{
			return true;
		}

		if (isset($filter['CATEGORY_ID']) && isset($filter['@CATEGORY_ID']))
		{
			return false;
		}

		if ($this->isOrCategoryFilter($filter))
		{
			return true;
		}

		$diff = array_diff(array_keys($filter), ['CATEGORY_ID', '=IS_RECURRING', '@CATEGORY_ID']);

		return empty($diff);
	}

	private function isOrCategoryFilter(array $filter): bool
	{
		if (count($filter) !== 1) {
			return false;
		}

		if (!isset($filter[0]) || !is_array($filter[0]))
		{
			return false;
		}

		$subFilter = $filter[0];

		if (isset($subFilter['LOGIC']) && $subFilter['LOGIC'] === 'OR') {
			$result = true;
			foreach ($subFilter as $key => $value)
			{
				if ($key === 'LOGIC')
				{
					continue;
				}

				if (is_array($value) && array_key_exists('=CATEGORY_ID', $value) && count($value) === 1)
				{
					continue;
				}

				$result = false;
				break;
			}

			return $result;
		}

		return false;
	}
}
