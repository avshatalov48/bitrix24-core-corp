<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class ConversionManager
{
	/** @var array */
	private static $entityTypeConversionMap = array(
		\CCrmOwnerType::Lead => array(
			\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company
		),
		\CCrmOwnerType::Deal => array(
			\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice
		),
		\CCrmOwnerType::Quote => array(
			\CCrmOwnerType::Deal
		)
	);

	public static function getConcernedFields($srcEntityTypeID, $srcFieldName)
	{
		$bindings = array();
		self::prepareBoundFields($srcEntityTypeID, $srcFieldName, $bindings);
		return array_values($bindings);
	}

	protected static function prepareBoundFields($srcEntityTypeID, $srcFieldName, array &$bindings)
	{
		foreach(self::getDestinationEntityTypeIDs($srcEntityTypeID) as $dstEntityTypeID)
		{
			//Protection against infinite loop
			if(isset($bindings[$dstEntityTypeID]))
			{
				continue;
			}

			$map = EntityConversionMap::load($srcEntityTypeID, $dstEntityTypeID);
			if(!$map)
			{
				continue;
			}

			foreach($map->getItems() as $item)
			{
				if($srcFieldName !== $item->getSourceField())
				{
					continue;
				}

				$dstFieldName = $item->getDestinationField();
				$bindings[$dstEntityTypeID] = array(
					'ENTITY_TYPE_ID' => $dstEntityTypeID,
					'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($dstEntityTypeID),
					'FIELD_NAME' => $dstFieldName
				);

				self::prepareBoundFields($dstEntityTypeID, $dstFieldName, $bindings);
				break;
			}
		}
	}

	public static function getParentalField($entityTypeID, $fieldName)
	{
		$resultField = array(
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($entityTypeID),
			'FIELD_NAME' => $fieldName
		);
		$traverseMap = array($fieldName => true);

		for(;;)
		{
			$field = null;
			$srcEntityTypeIDs = self::getSourceEntityTypeIDs($resultField['ENTITY_TYPE_ID']);
			foreach($srcEntityTypeIDs as $srcEntityTypeID)
			{
				$map = EntityConversionMap::load($srcEntityTypeID, $resultField['ENTITY_TYPE_ID']);
				if(!$map)
				{
					continue;
				}

				foreach($map->getItems() as $item)
				{
					if($resultField['FIELD_NAME'] === $item->getDestinationField())
					{
						$field = array(
							'ENTITY_TYPE_ID' => $srcEntityTypeID,
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($srcEntityTypeID),
							'FIELD_NAME' => $item->getSourceField()
						);
						break;
					}
				}

				if($field !== null)
				{
					break;
				}
			}

			if($field === null)
			{
				break;
			}

			//Protection against infinite loop
			if(isset($traverseMap[$field['FIELD_NAME']]))
			{
				break;
			}

			$resultField = $field;
			$traverseMap[$field['FIELD_NAME']] = true;
		}

		return $resultField;
	}

	public static function getSourceEntityTypeIDs($dstEntityTypeID)
	{
		if(!is_int($dstEntityTypeID))
		{
			$dstEntityTypeID = (int)$dstEntityTypeID;
		}

		$results = array();
		foreach(self::$entityTypeConversionMap as $srcEntityTypeID => $dstEntityTypeIDs)
		{
			if(in_array($dstEntityTypeID, $dstEntityTypeIDs, true))
			{
				$results[] = $srcEntityTypeID;
			}
		}
		return $results;
	}

	public static function getDestinationEntityTypeIDs($srcEntityTypeID)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}

		return isset(self::$entityTypeConversionMap[$srcEntityTypeID])
			? self::$entityTypeConversionMap[$srcEntityTypeID] : array();
	}
}