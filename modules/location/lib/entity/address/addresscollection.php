<?php

namespace Bitrix\Location\Entity\Address;

use Bitrix\Location\Entity\Address;
use Bitrix\Main\ArgumentTypeException;

/**
 * Class AddressCollection
 * @package Bitrix\Location\Entity\Address
 * @internal
 */
final class AddressCollection extends \Bitrix\Location\Entity\Generic\Collection
{
	/** @var Address[]  */
	protected $items = [];

	/**
	 * Add Address to Collection
	 *
	 * @param Address $address
	 * @return int
	 * @throws ArgumentTypeException
	 */
	public function addItem($address): int
	{
		if(!($address instanceof Address))
		{
			throw new ArgumentTypeException('address must be the instance of Address');
		}

		return parent::addItem($address);
	}
}
