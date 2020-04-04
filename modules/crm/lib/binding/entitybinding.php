<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main;

class EntityBinding
{
	const ROLE_UNDEFINED = 0;

	/**
	 * Verify binding structure.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $binding Source binding.
	 * @return bool
	 */
	public static function verifyEntityBinding($entityTypeID, array $binding)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			return false;
		}

		return is_array($binding) && isset($binding[$fieldName]) && $binding[$fieldName] > 0;
	}

	public static function normalizeEntityBindings($entityTypeID, array &$bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$effectiveBindings = array();
		$primaryBindingIndex = -1;
		for($i = 0, $l = count($bindings); $i < $l; $i++)
		{
			$binding = $bindings[$i];
			if(!is_array($binding))
			{
				continue;
			}

			$entityID = isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			if(!(isset($binding['SORT']) && $binding['SORT'] > 0))
			{
				$binding['SORT'] = ($i + 1) * 10;
			}

			if(isset($binding['IS_PRIMARY']))
			{
				if($binding['IS_PRIMARY'] === 'Y' && $primaryBindingIndex < 0)
				{
					$primaryBindingIndex = $i;
				}
				else
				{
					unset($binding['IS_PRIMARY']);
				}
			}
			$effectiveBindings[] = $binding;
		}

		if($primaryBindingIndex < 0 && count($effectiveBindings) > 0)
		{
			$effectiveBindings[0]['IS_PRIMARY'] = 'Y';
		}
		$bindings = $effectiveBindings;
	}

	public static function addEntityBinding($entityTypeID, $entityID, array &$bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$bindings[] = array($fieldName => (int)$entityID);

		$maxSort = 0;
		foreach($bindings as $binding)
		{
			$sort = isset($binding['SORT']) ? (int)$binding['SORT'] : 0;
			if($sort > $maxSort)
			{
				$maxSort = $sort;
			}
			elseif($sort <= 0)
			{
				$maxSort += 10;
				$binding['SORT'] = $maxSort;
			}
		}
	}

	public static function removeEntityBinding($entityTypeID, $entityID, array &$bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		$index = self::findBindingIndexByEntityID($entityTypeID, $entityID, $bindings);
		if($index >= 0)
		{
			unset($bindings[$index]);
			$bindings = array_values($bindings);
		}
	}

	/**
	 * Prepare entity bindings from array of entity IDs.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $entityIDs Entity IDs.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareEntityBindings($entityTypeID, array $entityIDs)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$bindings = array();
		$entityIDs = array_filter($entityIDs);
		$sort = 0;
		foreach($entityIDs as $entityID)
		{
			if($entityID > 0)
			{
				$sort += 10;
				$bindings[] = array($fieldName => (int)$entityID, 'SORT' => $sort);
			}
		}
		return $bindings;
	}
	/**
	 * Extract entity IDs from bindings.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $bindings Bindings.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareEntityIDs($entityTypeID, array $bindings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entityIDs = array();
		foreach($bindings as $binding)
		{
			if(!is_array($binding))
			{
				continue;
			}

			$entityID = is_array($binding) && isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
			if($entityID > 0)
			{
				$entityIDs[] = $entityID;
			}
		}
		return $entityIDs;
	}
	/**
	 * Extract entity ID from binding.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $binding Bindings.
	 * @return int
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareEntityID($entityTypeID, array $binding)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		return isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
	}
	/**
	 * Extract entity ID from first binding.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $bindings Bindings.
	 * @return array|int
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function getFirstEntityID($entityTypeID, array $bindings)
	{
		if(!(isset($bindings[0]) && is_array($bindings[0])))
		{
			return 0;
		}

		return self::prepareEntityID($entityTypeID, $bindings[0]);
	}
	/**
	 * Extract entity ID from last binding.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $bindings Bindings.
	 * @return array|int
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function getLastEntityID($entityTypeID, array $bindings)
	{
		if(empty($bindings))
		{
			return 0;
		}

		$index = count($bindings) - 1;
		return is_array($bindings[$index]) ? self::prepareEntityID($entityTypeID, $bindings[$index]) : 0;
	}
	/**
	 * Mark first binding as primary.
	 * @param array &$bindings Bindings.
	 */
	public static function markFirstAsPrimary(array &$bindings)
	{
		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		if(is_array($bindings[0]))
		{
			$bindings[0]['IS_PRIMARY'] = 'Y';
		}

		for($i = 1; $i < $qty; $i++)
		{
			if(is_array($bindings[$i]))
			{
				unset($bindings[$i]['IS_PRIMARY']);
			}
		}
	}
	/**
	 * Mark binding as primary.
	 * @param array &$bindings Bindings.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 */
	public static function markAsPrimary(array &$bindings, $entityTypeID, $entityID)
	{
		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$qty = count($bindings);
		for($i = 0; $i < $qty; $i++)
		{
			if(!is_array($bindings[$i]))
			{
				continue;
			}

			if(isset($bindings[$i][$fieldName]) && $bindings[$i][$fieldName] == $entityID)
			{
				$bindings[$i]['IS_PRIMARY'] = 'Y';
			}
			else
			{
				unset($bindings[$i]['IS_PRIMARY']);
			}
		}
	}
	public static function isPrimary(array $binding)
	{
		return isset($binding['IS_PRIMARY']) && $binding['IS_PRIMARY'] === 'Y';
	}
	/**
	 * Try find primary binding.
	 * @param array $bindings Bindings.
	 * @return array|null
	 */
	public static function findPrimaryBinding(array $bindings)
	{
		foreach($bindings as $binding)
		{
			if(!is_array($binding))
			{
				continue;
			}

			if(isset($binding['IS_PRIMARY']) && $binding['IS_PRIMARY'] === 'Y')
			{
				return $binding;
			}
		}
		return null;
	}
	public static function findBindingIndexByEntityID($entityTypeID, $entityID, array $bindings)
	{
		$fieldName = self::resolveEntityFieldName($entityTypeID);
		if($fieldName === '')
		{
			return -1;
		}

		for($i = 0, $l = count($bindings); $i < $l; $i++)
		{
			if(!is_array($bindings[$i]))
			{
				continue;
			}

			if(isset($bindings[$i][$fieldName]) && $bindings[$i][$fieldName] == $entityID)
			{
				return $i;
			}
		}
		return -1;
	}
	public static function findBindingByEntityID($entityTypeID, $entityID, array $bindings)
	{
		$index = self::findBindingIndexByEntityID($entityTypeID, $entityID, $bindings);
		return $index >= 0 ? $bindings[$index] : null;
	}
	/**
	 * Prepare binding changes.
	 * @param int $entityTypeID Entity Type ID.
	 * @param array $origin Origin bindings.
	 * @param array $current Current bindings.
	 * @param array &$added Added bindings (output parameter).
	 * @param array &$removed Removed bindings (output parameter).
	 * @return void
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareBindingChanges($entityTypeID, array $origin, array $current, array &$added, array &$removed)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fieldName = 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fieldName = 'CONTACT_ID';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$maxSort = 0;

		$originMap = array();
		$originPrimaryID = 0;
		foreach($origin as $binding)
		{
			$ID = isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
			if($ID > 0)
			{
				$originMap[$ID] = $binding;

				if(isset($binding['SORT']) && $binding['SORT'] > $maxSort)
				{
					$maxSort = (int)$binding['SORT'];
				}

				if(isset($binding['IS_PRIMARY']) && $binding['IS_PRIMARY'] === 'Y')
				{
					$originPrimaryID = $ID;
				}
			}
		}

		$currentMap = array();
		$currentPrimaryID = 0;
		foreach($current as $binding)
		{
			$ID = isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
			if($ID <= 0)
			{
				continue;
			}

			$currentMap[$ID] = $binding;
			if(isset($binding['IS_PRIMARY']) && $binding['IS_PRIMARY'] === 'Y')
			{
				$currentPrimaryID = $ID;
			}
		}

		$originIDs = array_keys($originMap);
		$currentIDs = array_keys($currentMap);

		if(!empty($removed))
		{
			$removed = array();
		}
		foreach(array_diff($originIDs, $currentIDs) as $ID)
		{
			$removed[$ID] = $originMap[$ID];
		}

		if(!empty($added))
		{
			$added = array();
		}
		foreach(array_diff($currentIDs, $originIDs) as $ID)
		{
			$binding = $currentMap[$ID];
			if($maxSort > 0 && !isset($binding['SORT']))
			{
				$maxSort += 10;
				$binding['SORT'] = $maxSort;
			}
			$added[$ID] = $binding;
		}

		foreach($current as $currentBinding)
		{
			$ID = isset($currentBinding[$fieldName]) ? (int)$currentBinding[$fieldName] : 0;
			if($ID <= 0)
			{
				continue;
			}

			if(isset($added[$ID]) || isset($removed[$ID]))
			{
				continue;
			}

			$originBinding = isset($originMap[$ID]) ? $originMap[$ID] : null;
			if(!is_array($originBinding))
			{
				continue;
			}

			$originSort = isset($originBinding["SORT"]) ? (int)$originBinding["SORT"] : 0;
			$currentSort = isset($currentBinding["SORT"]) ? (int)$currentBinding["SORT"] : 0;

			if($originSort !== $currentSort)
			{
				$added[$ID] = $currentBinding;
			}
		}

		if(($originPrimaryID > 0 || $currentPrimaryID > 0) && $originPrimaryID !== $currentPrimaryID)
		{
			if($currentPrimaryID > 0 && !isset($added[$currentPrimaryID]))
			{
				$added[$currentPrimaryID] = array_merge(
					$currentMap[$currentPrimaryID],
					array('IS_PRIMARY' => 'Y')
				);
			}

			if($originPrimaryID > 0 && !isset($removed[$originPrimaryID]))
			{
				$added[$originPrimaryID] = array_merge(
					$currentMap[$originPrimaryID],
					array('IS_PRIMARY' => 'N')
				);
			}
		}

		$removed = array_values($removed);
		$added = array_values($added);
	}

	/**
	 * Resolve field name for specified entity type.
	 * @param int $entityTypeID Entity type ID.
	 * @return string
	 */
	public static function resolveEntityFieldName($entityTypeID)
	{
		if($entityTypeID === \CCrmOwnerType::Company)
		{
			return 'COMPANY_ID';
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return 'CONTACT_ID';
		}
		return '';
	}

	public static function resolveEntityID($entityTypeID, array $binding)
	{
		$fieldName = self::resolveEntityFieldName($entityTypeID);
		if($fieldName === '')
		{
			return 0;
		}

		return isset($binding[$fieldName]) ? (int)$binding[$fieldName] : 0;
	}

	public static function getPrimaryOrDefault(array $bindings)
	{
		if(empty($bindings))
		{
			return null;
		}

		$binding = self::findPrimaryBinding($bindings);
		if(!is_array($binding))
		{
			$binding = $bindings[0];
		}

		return $binding;
	}
	public static function getPrimaryEntityID($entityTypeID, array $bindings)
	{
		$primaryBinding = self::getPrimaryOrDefault($bindings);
		return is_array($primaryBinding) ? self::prepareEntityID($entityTypeID, $primaryBinding) : 0;
	}
}