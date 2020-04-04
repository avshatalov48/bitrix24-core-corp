<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Main\NotSupportedException;

class Relation
{
	protected $sourceEntityTypeID = 0;
	protected $sourceEntityID = 0;
	protected $sourceRecycleBinID = null;

	protected $destinationEntityTypeID = 0;
	protected $destinationEntityID = 0;
	protected $destinationRecycleBinID = null;

	protected $previousSourceEntityID = 0;
	protected $previousDestinationEntityID = 0;

	public function __construct($sourceEntityTypeID, $sourceEntityID, $destinationEntityTypeID, $destinationEntityID)
	{
		if(!is_int($sourceEntityTypeID))
		{
			$sourceEntityTypeID = (int)$sourceEntityTypeID;
		}

		if(!is_int($sourceEntityID))
		{
			$sourceEntityID = (int)$sourceEntityID;
		}

		if(!is_int($destinationEntityTypeID))
		{
			$destinationEntityTypeID = (int)$destinationEntityTypeID;
		}

		if(!is_int($destinationEntityID))
		{
			$destinationEntityID = (int)$destinationEntityID;
		}

		$this->sourceEntityTypeID = $sourceEntityTypeID;
		$this->sourceEntityID = $sourceEntityID;

		$this->destinationEntityTypeID = $destinationEntityTypeID;
		$this->destinationEntityID = $destinationEntityID;
	}

	public static function createFromArray(array $data)
	{
		$item = new Relation(
			$data['SRC_ENTITY_TYPE_ID'],
			$data['SRC_ENTITY_ID'],
			$data['DST_ENTITY_TYPE_ID'],
			$data['DST_ENTITY_ID']
		);

		if(isset($data['SRC_RECYCLE_BIN_ID']))
		{
			$item->setSourceRecycleBinID($data['SRC_RECYCLE_BIN_ID']);
		}

		if(isset($data['DST_RECYCLE_BIN_ID']))
		{
			$item->setDestinationRecycleBinID($data['DST_RECYCLE_BIN_ID']);
		}

		if(isset($data['PREVIOUS_SRC_ENTITY_ID']))
		{
			$item->setPreviousSourceEntityID($data['PREVIOUS_SRC_ENTITY_ID']);
		}

		if(isset($data['PREVIOUS_DST_ENTITY_ID']))
		{
			$item->setPreviousDestinationEntityID($data['PREVIOUS_DST_ENTITY_ID']);
		}

		return $item;
	}

	public function getSourceEntityTypeID()
	{
		return $this->sourceEntityTypeID;
	}

	public function setSourceEntityTypeID($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		$this->sourceEntityTypeID = $entityTypeID;
	}

	public function getSourceEntityID()
	{
		return $this->sourceEntityID;
	}

	public function setSourceEntityID($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$this->sourceEntityID = $entityID;
	}

	public function getSourceRecycleBinID()
	{
		return $this->sourceRecycleBinID;
	}

	public function setSourceRecycleBinID($recycleBinID)
	{
		if(!is_int($recycleBinID))
		{
			$recycleBinID = (int)$recycleBinID;
		}

		$this->sourceRecycleBinID = $recycleBinID;
	}

	public function getDestinationEntityTypeID()
	{
		return $this->destinationEntityTypeID;
	}

	public function setDestinationEntityTypeID($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		$this->destinationEntityTypeID = $entityTypeID;
	}

	public function getDestinationEntityID()
	{
		return $this->destinationEntityID;
	}

	public function setDestinationEntityID($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$this->destinationEntityID = $entityID;
	}

	public function getDestinationRecycleBinID()
	{
		return $this->destinationRecycleBinID;
	}

	public function setDestinationRecycleBinID($recycleBinID)
	{
		if(!is_int($recycleBinID))
		{
			$recycleBinID = (int)$recycleBinID;
		}

		$this->destinationRecycleBinID = $recycleBinID;
	}

	public function setRecycleBinID($entityTypeID, $entityID, $recycleBinID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if(!is_int($recycleBinID))
		{
			$recycleBinID = (int)$recycleBinID;
		}

		if($this->sourceEntityTypeID === $entityTypeID && $this->sourceEntityID === $entityID)
		{
			$this->sourceRecycleBinID = $recycleBinID;
		}
		elseif($this->destinationEntityTypeID === $entityTypeID && $this->destinationEntityID === $entityID)
		{
			$this->destinationRecycleBinID = $recycleBinID;
		}
	}

	public function getPreviousSourceEntityID()
	{
		return $this->previousSourceEntityID;
	}

	public function setPreviousSourceEntityID($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$this->previousSourceEntityID = $entityID;
	}

	public function getPreviousDestinationEntityID()
	{
		return $this->previousDestinationEntityID;
	}

	public function setPreviousDestinationEntityID($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$this->previousDestinationEntityID = $entityID;
	}

	public function save()
	{
		Entity\RelationTable::upsert(
			[
				'SRC_ENTITY_TYPE_ID' => $this->sourceEntityTypeID,
				'SRC_ENTITY_ID' => $this->sourceEntityID,
				'SRC_RECYCLE_BIN_ID' => $this->sourceRecycleBinID,
				'DST_ENTITY_TYPE_ID' => $this->destinationEntityTypeID,
				'DST_ENTITY_ID' => $this->destinationEntityID,
				'DST_RECYCLE_BIN_ID' => $this->destinationRecycleBinID
			]
		);
	}

	public static function getBySourceEntity($entityTypeID, $entityID)
	{
		$dbResult = Entity\RelationTable::getList([
			'filter' => [
				'=SRC_ENTITY_TYPE_ID' => $entityTypeID,
				'=SRC_ENTITY_ID' => $entityID
			]
		]);

		$results = [];
		while($fields = $dbResult->fetch())
		{
			$results[] = self::createFromArray($fields);
		}
		return $results;
	}

	public static function getByDestinationEntity($entityTypeID, $entityID)
	{
		$dbResult = Entity\RelationTable::getList([
			'filter' => [
				'=DST_ENTITY_TYPE_ID' => $entityTypeID,
				'=DST_ENTITY_ID' => $entityID
			]
		]);

		$results = [];
		while($fields = $dbResult->fetch())
		{
			$results[] = self::createFromArray($fields);
		}
		return $results;
	}

	public static function getByEntity($entityTypeID, $entityID, $recyclingEntityID = 0)
	{
		$srcFilterFields = [ '=SRC_ENTITY_TYPE_ID' => $entityTypeID, '=SRC_ENTITY_ID' => $entityID ];
		$dstFilterFields = [ '=DST_ENTITY_TYPE_ID' => $entityTypeID, '=DST_ENTITY_ID' => $entityID ];

		if($recyclingEntityID > 0)
		{
			$srcFilterFields['SRC_RECYCLE_BIN_ID'] = $recyclingEntityID;
			$dstFilterFields['DST_RECYCLE_BIN_ID'] = $recyclingEntityID;
		}

		$dbResult = Entity\RelationTable::getList(
			['filter' => [ 'LOGIC' => 'OR', $srcFilterFields, $dstFilterFields ]]
		);

		$results = [];
		while($fields = $dbResult->fetch())
		{
			$results[] = self::createFromArray($fields);
		}
		return $results;
	}

	public static function getSourceEntityIDs(array $items, $sourceEntityTypeID)
	{
		$entityIDs = [];
		foreach($items as $item)
		{
			/** @var Relation $item */
			if($sourceEntityTypeID === $item->getSourceEntityTypeID())
			{
				$entityIDs[] = $item->getSourceEntityID();
			}
		}
		return $entityIDs;
	}

	public static function getDestinationEntityIDs(array $items, $destinationEntityTypeID)
	{
		$entityIDs = [];
		foreach($items as $item)
		{
			/** @var Relation $item */
			if($destinationEntityTypeID === $item->getDestinationEntityTypeID())
			{
				$entityIDs[] = $item->getDestinationEntityID();
			}
		}
		return $entityIDs;
	}

	public static function updateEntityID($entityTypeID, $oldEntityID, $newEntityID, $recyclingEntityID)
	{
		Entity\RelationTable::updateEntityID($entityTypeID, $oldEntityID, $newEntityID, $recyclingEntityID);
	}

	public static function registerRecycleBin($entityTypeID, $entityID, $recycleBinID)
	{
		Entity\RelationTable::registerRecycleBin($entityTypeID, $entityID, $recycleBinID);
	}

	public static function unregisterRecycleBin($recycleBinID)
	{
		Entity\RelationTable::unregisterRecycleBin($recycleBinID);
	}

	public static function deleteByRecycleBin($recycleBinID)
	{
		Entity\RelationTable::deleteByRecycleBin($recycleBinID);
	}

	public static function deleteJunks()
	{
		Entity\RelationTable::deleteJunks();
	}
}