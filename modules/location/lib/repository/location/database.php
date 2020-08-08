<?php

namespace Bitrix\Location\Repository\Location;

use Bitrix\Location\Entity\Address\Normalizer\Builder;
use Bitrix\Location\Entity\Location;
use Bitrix\Location\Model;
use Bitrix\Location\Entity\Location\Parents;
use Bitrix\Location\Repository\Location\Capability\ISave;
use Bitrix\Location\Repository\Location\Capability\IDelete;
use Bitrix\Location\Repository\Location\Capability\IFindByExternalId;
use Bitrix\Location\Repository\Location\Capability\IFindById;
use Bitrix\Location\Repository\Location\Capability\IFindByText;
use Bitrix\Location\Repository\Location\Capability\IFindParents;
use Bitrix\Location\Repository\Location\Capability\ISaveParents;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

/**
 * Class Database
 * @package Bitrix\Location\Repository
 * todo: language variations
 */
final class Database
	implements
		IRepository,
		IDatabase,
		IFindById, IFindByExternalId, IFindByText, IFindParents,
		ISave, ISaveParents,
		IDelete,
		IScope
{
	/** @var Model\LocationTable  */
	protected $locationTable = Model\LocationTable::class;
	/** @var Model\HierarchyTable  */
	protected $hierarchyTable = Model\HierarchyTable::class;
	/** @var Model\LocationNameTable  */
	protected $locationNameTable = Model\LocationNameTable::class;
	/** @var Model\AddressTable  */
	protected $addressTable = Model\AddressTable::class;
	/** @var Model\LocationFieldTable  */
	protected $fieldTable = Model\LocationFieldTable::class;

	/**
	 * @inheritDoc
	 */
	public function isScopeSatisfy(int $scope): bool
	{
		return $scope === LOCATION_SEARCH_SCOPE_ALL || $scope === LOCATION_SEARCH_SCOPE_INTERNAL;
	}

	/** @inheritDoc */
	public function findByExternalId(string $externalId, string $sourceCode, string $languageId)
	{
		if($externalId == '')
		{
			return null;
		}

		$result = $this->createQuery($languageId)
			->addFilter('EXTERNAL_ID', $externalId)
			->addFilter('SOURCE_CODE', $sourceCode)
			->fetchObject();

		if($result)
		{
			$result = Location\Factory\OrmFactory::createLocation($result, $languageId);
		}

		return $result;

	}

	/** @inheritDoc */
	public function findById(int $id, string $languageId)
	{

		if((int)$id <= 0)
		{
			return null;
		}

		$result = $this->createQuery($languageId)
			->addFilter('=ID', $id)
			->fetchObject();

		if($result)
		{
			$result =Location\Factory\OrmFactory::createLocation($result, $languageId);
		}

		return $result;
	}

	/** @inheritDoc
	 * todo: address fields
	 */
	public function findByText(string $text, string $languageId)
	{
		if($text == '')
		{
			return null;
		}

		$text = Builder::build($languageId)->normalize($text);

		$result = $this->createQuery($languageId)
			->addFilter('%NAME.NAME_NORMALIZE', $text)
			->fetchCollection();

		return Location\Factory\OrmFactory::createCollection($result, $languageId);
	}

	/** @inheritDoc */
	public function findParents(Location $location, string $languageId)
	{
		if($location->getId() <= 0)
		{
			return null;
		}

		$ormCollection = $this->hierarchyTable::getList([
			'filter' => [
				'=DESCENDANT_ID' => $location->getId(),
				'=ANCESTOR.NAME.LANGUAGE_ID' => $languageId, //todo: if not found required language
			],
			'order' => ['LEVEL' => 'ASC'],
			'select' => [
				'*',
				'ANCESTOR',
				'ANCESTOR.NAME'
			]
		])->fetchCollection();

		$result = Location\Factory\OrmFactory::createParentCollection($ormCollection, $languageId);
		$result->setDescendant($location);
		return $result;
	}

	protected function obtainLocationKeys(Location $location): array
	{
		$result = [0, ''];

		$query = $this->locationTable::query()
			->where('EXTERNAL_ID', $location->getExternalId())
			->where('SOURCE_CODE', $location->getSourceCode())
			->addSelect('ID')
			->addSelect('CODE');

		if($location->getId() > 0)
		{
			$query->where('ID', $location->getId());
		}

		if($res = $query->fetch())
		{
			$result = [(int)$res['ID'], (string)$res['CODE']];
		}

		return $result;
	}

	protected function generateLocationCode()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * @inheritDoc
	 * todo: Transaction. If something wrong with address, or name.
	*/
	public function save(Location $location): Result
	{
		$fields = Location\Converter\DbFieldConverter::convertToDbFields($location);
		$isNewLocation = false;
		list($locationId, $locationCode) = $this->obtainLocationKeys($location);

		if($locationId > 0)
		{
			if($location->getId() <= 0)
			{
				$location->setId($locationId);
				$location->setCode($locationCode);
				$fields['CODE'] = $locationCode;
			}

			$result = $this->locationTable::update($locationId, $fields);
		}
		else
		{
			if((string)$fields['CODE'] === '')
			{
				$fields['CODE'] = $this->generateLocationCode();
			}

			$result  = $this->locationTable::add($fields);
			$isNewLocation = true;

			if($result->isSuccess())
			{
				$location->setId($result->getId());
				$location->setCode($fields['CODE']);
			}
		}

		if($result->isSuccess())
		{
			$res = $this->saveName($location, $isNewLocation);

			if(!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
			}

			$res = $this->saveFields($location);

			if(!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
			}
		}

		return $result;
	}

	private function saveFields(Location $location)
	{
		if($location->getId() <= 0)
		{
			throw new ArgumentNullException('Location Id');
		}

		$fieldsCollection = Location\Converter\OrmConverter::convertFieldsToOrm($location);

		$this->fieldTable::deleteByLocationId($location->getId());
		return $fieldsCollection->save();
	}

	private function saveName(Location $location, bool $newLocation)
	{
		$fields = Location\Converter\DbFieldConverter::convertNameToDbFields($location);

		if($newLocation)
		{
			$result = $this->locationNameTable::add($fields);
		}
		else
		{
			$result = $this->locationNameTable::update([
				'LOCATION_ID' => $fields['LOCATION_ID'],
				'LANGUAGE_ID' => $fields['LANGUAGE_ID']
			], $fields);
		}

		return $result;
	}

	/** @inheritDoc */
	public function delete(Location $location): Result
	{
		$id = $location->getId();

		if($id <= 0)
		{
			return new Result();
		}

		$res = $this->addressTable::getList([
			'filter' => ['LOCATION_ID' => (int)$id]
		]);

		if($row = $res->fetch())
		{
			return (new Result())
				->addError(
					new Error(
						Loc::getMessage('LOCATION_REPO_DB_EXIST_LINKED_ADDRESS')
					)
				);
		}

		$result = $this->locationTable::delete($id);

		if($result->isSuccess())
		{
			$this->locationNameTable::deleteByLocationId($id);
			$this->hierarchyTable::deleteByLocationId($id);
			$this->fieldTable::deleteByLocationId($id);
		}

		return $result;
	}

	/**
	 * @param Parents $parents
	 * @return Result
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function saveParents(Parents $parents): Result
	{
		$result = new Result();

		if($parents->count() <= 0)
		{
			return new Result();
		}

		if($parents->getDescendant()->getId() <= 0)
		{
			throw new ArgumentNullException('descendant has not saved yet');
		}

		$data = [];
		$items = $parents->getItems();
		krsort($items);

		/**
		 * @var  int $level
		 * @var  Location $parentLocation
		 */
		foreach($items as $level => $parentLocation)
		{
			if($parentLocation->getId() <= 0)
			{
				$res = $parentLocation->save();

				if(!$res->isSuccess())
				{
					$result->addErrors($res->getErrors());
					continue;
				}
			}

			$data[] = [
				'DESCENDANT_ID' => (int)$parents->getDescendant()->getId(),
				'ANCESTOR_ID' => (int)$parentLocation->getId(),
				'LEVEL' => (int)$level,
			];
		}

		if(!empty($data))
		{
			$this->hierarchyTable::insertBatch($data);
		}

		return $result;
	}

	private function createQuery(string $languageId)
	{
		return $this->locationTable::query()
			->addFilter('NAME.LANGUAGE_ID', $languageId)
			->addSelect('*')
			->addSelect('NAME')
			->addSelect('FIELDS');
	}
}
