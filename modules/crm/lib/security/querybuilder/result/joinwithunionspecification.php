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

		$allowedFields = array_intersect(array_keys($filter), ['CATEGORY_ID', '=IS_RECURRING']);

		return count($allowedFields) === count($filter);
	}
}
