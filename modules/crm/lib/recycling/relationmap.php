<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;

class RelationMap
{
	/** @var int */
	protected $entityTypeID = 0;
	/** @var int */
	protected $entityID = 0;
	/** @var Relation[]|null */
	protected $relations = null;
	/** @var array|null */
	protected $sourceMap = null;
	/** @var array|null */
	protected $destinationMap = null;
	/** @var bool */
	protected $isBuilt = false;

	public function __construct($entityTypeID, $entityID, array $relations)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
		$this->relations = $relations;

		$this->sourceMap = [];
		$this->destinationMap = [];
	}
	public function isBuilt()
	{
		return $this->isBuilt;
	}
	public function build()
	{
		if($this->isBuilt)
		{
			return;
		}

		$this->sourceMap = [];
		$this->destinationMap = [];

		if(empty($this->relations))
		{
			$this->isBuilt = true;
			return;
		}

		foreach($this->relations as $relation)
		{
			/** @var Relation $relation */
			$sourceEntityTypeID = $relation->getSourceEntityTypeID();
			$sourceEntityID = $relation->getSourceEntityID();

			$destinationEntityTypeID = $relation->getDestinationEntityTypeID();
			$destinationEntityID = $relation->getDestinationEntityID();

			if($this->entityTypeID === $sourceEntityTypeID && $this->entityID === $sourceEntityID)
			{
				if(!isset($this->destinationMap[$destinationEntityTypeID]))
				{
					$this->destinationMap[$destinationEntityTypeID] = [];
				}

				$this->destinationMap[$destinationEntityTypeID][] = $destinationEntityID;
			}
			elseif($this->entityTypeID === $destinationEntityTypeID && $this->entityID === $destinationEntityID)
			{
				if(!isset($this->sourceMap[$sourceEntityTypeID]))
				{
					$this->sourceMap[$sourceEntityTypeID] = [];
				}

				$this->sourceMap[$sourceEntityTypeID][] = $sourceEntityID;
			}
		}
		$this->isBuilt = true;
	}
	public function isEmpty()
	{
		return empty($this->relations);
	}
	public function getSourceEntityInfos()
	{
		$results = [];
		foreach($this->sourceMap as $entityTypeID => $entityIDs)
		{
			foreach($entityIDs as $entityID)
			{
				$results[] = [ 'ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID ];
			}
		}
		return $results;
	}
	public function getDestinationEntityInfos()
	{
		$results = [];
		foreach($this->destinationMap as $entityTypeID => $entityIDs)
		{
			foreach($entityIDs as $entityID)
			{
				$results[] = [ 'ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID ];
			}
		}
		return $results;
	}
	public function getSourceEntityIDs($entityTypeID)
	{
		return isset($this->sourceMap[$entityTypeID]) ? $this->sourceMap[$entityTypeID] : [];
	}
	public function getDestinationEntityIDs($entityTypeID)
	{
		return isset($this->destinationMap[$entityTypeID]) ? $this->destinationMap[$entityTypeID] : [];
	}
	public function getEntityIDs($entityTypeID)
	{
		return array_unique(
			array_merge(
				$this->getSourceEntityIDs($entityTypeID),
				$this->getDestinationEntityIDs($entityTypeID)
			)
		);
	}
	public function findRenewedEntityID($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		foreach($this->relations as $rel)
		{
			if($rel->getSourceEntityTypeID() === $entityTypeID
				&& $rel->getPreviousSourceEntityID() === $entityID
			)
			{
				return $rel->getSourceEntityID();
			}

			if($rel->getDestinationEntityTypeID() === $entityTypeID
				&& $rel->getPreviousDestinationEntityID() === $entityID
			)
			{
				return $rel->getDestinationEntityID();
			}
		}
		return 0;
	}
	public function resolveRecycleBinEntityID($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		foreach($this->relations as $relation)
		{
			/** @var Relation $relation */
			if($entityTypeID === $relation->getSourceEntityTypeID()
				&& $entityID === $relation->getSourceEntityID()
			)
			{
				return $relation->getSourceRecycleBinID();
			}
			elseif($entityTypeID === $relation->getDestinationEntityTypeID()
				&& $entityID === $relation->getDestinationEntityID()
			)
			{
				return $relation->getDestinationRecycleBinID();
			}
		}
		return 0;
	}
	public static function createByEntity($entityTypeID, $entityID, $recyclingEntityID)
	{
		return new RelationMap(
			$entityTypeID,
			$entityID,
			Relation::getByEntity($entityTypeID, $entityID, $recyclingEntityID)
		);
	}
}