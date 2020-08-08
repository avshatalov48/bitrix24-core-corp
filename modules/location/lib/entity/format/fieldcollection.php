<?php

namespace Bitrix\Location\Entity\Format;

use Bitrix\Main\SystemException;

/**
 * Class FieldCollection
 * @package Bitrix\Location\Entity\Format
 */
final class FieldCollection extends \Bitrix\Location\Entity\Generic\FieldCollection
{
	/** @var Field[] */
	protected $items = [];

	/**
	 * @param mixed $item
	 * @return int
	 * @throws SystemException
	 */
	public function addItem($item)
	{
		$result = parent::addItem($item);

		usort(
			$this->items,
			function (Field $a, Field $b)
			{
				if ($a->getSort() == $b->getSort())
				{
					return 0;
				}

				return ($a->getSort() < $b->getSort()) ? -1 : 1;
			}
		);

		return $result;
	}
}
