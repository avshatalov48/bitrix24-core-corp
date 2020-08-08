<?php

namespace Bitrix\Location\Repository\Location;

use Bitrix\Location\Entity\Location;
use Bitrix\Location\Entity\Generic\Collection;
use Bitrix\Location\Entity\Location\Parents;
use Bitrix\Location\Common\Point;
use Bitrix\Location\Repository\Location\Capability\IDelete;
use Bitrix\Location\Repository\Location\Capability\ISave;
use Bitrix\Location\Repository\Location\Capability\IFindByExternalId;
use Bitrix\Location\Repository\Location\Capability\IFindById;
use Bitrix\Location\Repository\Location\Capability\IFindByPoint;
use Bitrix\Location\Repository\Location\Capability\IFindByText;
use Bitrix\Location\Repository\Location\Capability\IFindParents;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;

/**
 * Class Repository
 * @package Bitrix\Location\Tests
 */
class Memory
	implements IRepository, IFindById, IFindByExternalId, IFindByPoint, IFindByText, IFindParents, ISave, IDelete
{
	const RETURN_TYPE_LOCATION = 0;
	const RETURN_TYPE_COLLECTION = 1;

	/** @var Location[]  */
	protected $locations = [];

	/**
	 * RepositoryArray constructor.
	 * @param array $locations
	 */
	public function __construct(array $locations = [])
	{
		$this->setLocations($locations);
	}

	/**
	 * @return Location[]
	 */
	public function getLocations(): array
	{
		return $this->locations;
	}

	/**
	 * @param Location[] $locations
	 * @return self
	 */
	public function setLocations(array $locations): self
	{
		$this->locations = $locations;
		return $this;
	}

	/** @inheritDoc */
	public function delete(Location $location): Result
	{
		throw new NotImplementedException();
	}

	/** @inheritDoc */
	public function findByExternalId(string $externalId, string $sourceCode, string $languageId)
	{
		return $this->find(
			self::RETURN_TYPE_LOCATION,
			function(Location $location) use($externalId, $sourceCode)
			{
				return $externalId === $location->getExternalId()
					&& $sourceCode === $location->getSourceCode();
			}
		);
	}

	/** @inheritDoc */
	public function findById(int $id, string $languageId)
	{
		return  $this->find(
			self::RETURN_TYPE_LOCATION,
			function(Location $location) use($id)
			{
				return $id === $location->getId();
			}
		);
	}

	/** @inheritDoc */
	public function findByPoint(Point $point, string $languageId)
	{
		return $this->find(
			self::RETURN_TYPE_COLLECTION,
			function(Location $location) use($point)
			{
				return $location->getLatitude() == $point->getLatitude()
					&& $location->getLongitude() == $point->getLongitude();
			}
		);
	}

	/** @inheritDoc */
	public function findByText(string $text, string $languageId)
	{
		return $this->find(
			self::RETURN_TYPE_COLLECTION,
			function(Location $location) use($text)
			{
				return strpos($location->getName(), $text) !== false
					|| strpos($location->getAddress(), $text) !== false;
			}
		);
	}

	public function findParents(Location $location, string $languageId = LANGUAGE_ID)
	{
		return new Parents();
	}

	/** @inheritDoc */
	public function save(Location $location): Result
	{
		throw new NotImplementedException();
	}

	/** @inheritDoc */
	protected function find(int $type, callable $comparator)
	{
		$collection =  new Collection;

		foreach($this->locations as $location)
		{
			if(call_user_func($comparator, $location))
			{
				if($type == self::RETURN_TYPE_LOCATION)
				{
					return $location;
				}

				$collection->addItem($location);
			}
		}

		return $type == self::RETURN_TYPE_COLLECTION ? $collection : null;
	}
}
