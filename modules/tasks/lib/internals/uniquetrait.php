<?php

namespace Bitrix\Tasks\Internals;

trait UniqueTrait
{
	/**
	 * Override __toString() method in object class!
	 */
	public function makeUnique(): static
	{
		$unique = clone $this;

		foreach ($this as $object)
		{
			$this->remove($object);
		}

		$unique = array_unique(iterator_to_array($unique));
		foreach ($unique as $object)
		{
			$this->add($object);
		}

		return $this;
	}
}