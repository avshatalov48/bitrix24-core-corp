<?php

namespace Bitrix\Location\Entity\Location;

use Bitrix\Location\Entity\Location;
use Bitrix\Location\Service;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Result;

/**
 * Class LocationCollection
 * @package Bitrix\Location\Entity\Location
 * todo: groups
 */
class Collection extends \Bitrix\Location\Entity\Generic\Collection
{
	/** @var Location[]  */
	protected $items = [];


	/**
	 * LocationCollection constructor.
	 * @param Location[] $locations
	 */
	public function  __construct(array  $locations = [])
	{

		foreach($locations as $location)
		{
			if(!($location instanceof Location))
			{
				throw new ArgumentOutOfRangeException('location');
			}
		}

		parent::__construct($locations);
	}

	/**
	 * Saves all locations from the collection.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * todo: batch saving
	 */
	public function save()
	{
		$result = new Result();

		foreach($this->items as $location)
		{
			$res = $location->save();

			if(!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
			}
		}

		return $result;
	}
}
