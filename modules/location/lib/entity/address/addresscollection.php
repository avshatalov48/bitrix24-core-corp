<?php

namespace Bitrix\Location\Entity\Address;

use Bitrix\Location\Entity\Address;
use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * Class Collection
 * @package Bitrix\Location\Entity\Address
 */
class AddressCollection extends \Bitrix\Location\Entity\Generic\Collection
{
	/** @var Address[]  */
	protected $items = [];

	/**
	 * Collection constructor.
	 * @param Address[] $addresses
	 */
	public function  __construct(array  $addresses = [])
	{

		foreach($addresses as $address)
		{
			if(!($addresses instanceof Address))
			{
				throw new ArgumentOutOfRangeException('address');
			}
		}

		parent::__construct($addresses);
	}
}
