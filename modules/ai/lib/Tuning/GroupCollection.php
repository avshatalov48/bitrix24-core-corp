<?php

namespace Bitrix\AI\Tuning;

use Bitrix\Main\Type\Dictionary;

/**
 * Dictionary for work with Group objects
 */
class GroupCollection extends Dictionary
{
	/**
	 * @var Group[]
	 */
	protected $values = [];

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return Group | null
	 */
	public function get($name): ?Group
	{
		if (array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}
		return null;
	}

	/**
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$data = [];

		foreach ($this->values as $group)
		{
			if (!$group->getItems()->isEmpty())
			{
				$data[$group->getCode()] = $group->toArray();
			}
		}

		return $data;
	}

	public function sort(): void
	{
		/**
		 * @return int - <0, 0 or >0 if 1st element less than, equal to, or greater than 2nd
		 */
		$compare = function(Group $groupA, Group $groupB) {

			if (
				$groupA->getSort() === $groupB->getSort()
			)
			{
				return strcasecmp($groupA->getCode(), $groupB->getCode());
			}

			if ($groupA->getSort() === null)
			{
				return 1;
			}

			if ($groupB->getSort() === null)
			{
				return -1;
			}

			return $groupA->getSort() > $groupB->getSort() ? 1 : -1;
		};

		uasort($this->values, $compare);
	}
}
