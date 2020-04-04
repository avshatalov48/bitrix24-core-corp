<?php
namespace Bitrix\Crm\Synchronization;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\UserField\UserFieldHistory;

class UserFieldSynchronizer
{
	/** @var DateTime[]|null $items*/
	private static $timestamps = null;
	/** @var array|null $history*/
	private static $history = null;

	/** @var array|null $existedFieldNameMap*/
	public static $existedFieldNameMap = array();

	/**
	* Check if destination type fields need for synchronization with source fields.
	* Matches are searched by comparing field labels.
	* @static
	* @param int $srcEntityTypeID Source Entity Type ID
	* @param int $dstEntityTypeID Destination Entity Type ID
	* @param string $languageID Language
	* @return bool
	*/
	public static function needForSynchronization($srcEntityTypeID, $dstEntityTypeID, $languageID = '')
	{
		$fields = self::getSynchronizationFields($srcEntityTypeID, $dstEntityTypeID, $languageID, false);
		return !empty($fields);
	}
	/**
	* Prepare synchronization field list.
	* @static
	* @param int $srcEntityTypeID Source Entity Type ID
	* @param int $dstEntityTypeID Destination Entity Type ID
	* @param string $languageID Language
	* @return array
	*/
	public static function getSynchronizationFields($srcEntityTypeID, $dstEntityTypeID, $languageID = '', $forced = false)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}


		if(!is_int($dstEntityTypeID))
		{
			$dstEntityTypeID = (int)$dstEntityTypeID;
		}

		if($srcEntityTypeID === $dstEntityTypeID
			|| !\CCrmOwnerType::IsDefined($srcEntityTypeID)
			|| !\CCrmOwnerType::IsDefined($dstEntityTypeID)
		)
		{
			return array();
		}

		$historyItem = self::getHistoryItem($srcEntityTypeID, $dstEntityTypeID);
		if($historyItem !== null && !$forced)
		{
			$srcLastChanged = UserFieldHistory::getLastChangeTime($srcEntityTypeID);
			$dstLastChanged = UserFieldHistory::getLastChangeTime($dstEntityTypeID);

			$lastChanged = null;
			if($srcLastChanged !== null && $dstLastChanged !== null)
			{
				$lastChanged = $srcLastChanged->getTimestamp() > $dstLastChanged->getTimestamp()
					? $srcLastChanged : $dstLastChanged;
			}
			elseif($srcLastChanged !== null || $dstLastChanged !== null)
			{
				$lastChanged = $srcLastChanged !== null
					? $srcLastChanged : $dstLastChanged;
			}

			$lastChangeTimestamp = $lastChanged !== null ? $lastChanged->getTimestamp() : 0;
			/** @var DateTime $sync */
			$sync = isset($historyItem['sync']) ? $historyItem['sync'] : null;
			if($sync !== null && $sync->getTimestamp() > $lastChangeTimestamp)
			{
				return array();
			}

			/** @var DateTime $check */
			$check = isset($historyItem['check']) ? $historyItem['check'] : null;
			$required = isset($historyItem['required']) ? $historyItem['required'] : null;
			if($check !== null && $check->getTimestamp() > $lastChangeTimestamp && $required === false)
			{
				return array();
			}
		}

		if(!is_string($languageID) || $languageID === '')
		{
			$languageID = LANGUAGE_ID;
		}

		$srcUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeID);
		$dstUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);

		if($srcUfEntityID === '' || $dstUfEntityID === '')
		{
			return array();
		}

		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		$srcFields = $USER_FIELD_MANAGER->GetUserFields($srcUfEntityID, 0, $languageID);
		$dstFields = $USER_FIELD_MANAGER->GetUserFields($dstUfEntityID, 0, $languageID);

		$map = array();
		foreach($dstFields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(!isset($map[$typeID]))
			{
				$map[$typeID] = array();
			}

			if(!isset($map[$typeID][$label]))
			{
				$map[$typeID][$label] = $field['FIELD_NAME'];
			}
		}

		self::$existedFieldNameMap = array();
		$results = array();
		foreach($srcFields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(!(isset($map[$typeID]) && isset($map[$typeID][$label])))
			{
				$results[] = $field;
			}
			else
			{
				self::$existedFieldNameMap[$field['FIELD_NAME']] = $map[$typeID][$label];
			}
		}

		if($historyItem === null)
		{
			$historyItem = array('sync' => null);
		}

		$historyItem['check'] = new DateTime();
		$historyItem['required'] = !empty($results);
		self::setHistoryItem($srcEntityTypeID, $dstEntityTypeID, $historyItem);

		return $results;
	}

	/**
	 * Synchronize source type fields with destination type fields.
	 * Matches are searched by comparing field labels.
	 * If a source field is not found in the destination type, it will be created there.
	 * @static
	 * @param int $srcEntityTypeID Source Entity Type ID
	 * @param int $dstEntityTypeID Destination Entity Type ID
	 * @param string $languageID Language
	 * @param array $filter Filter
	 * @return array $synchronizedFieldNameMap
	 * @throws Main\ObjectException
	 * @throws UserFieldSynchronizationException
	 */
	public static function synchronize($srcEntityTypeID, $dstEntityTypeID, $languageID = '', $filter = array())
	{
		/** @var \CAllMain $APPLICATION */
		global $APPLICATION;

		$synchronizedFieldNameMap = array();
		$entity = new \CUserTypeEntity();
		$entityID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);
		$fields = self::getSynchronizationFields($srcEntityTypeID, $dstEntityTypeID, $languageID, true);

		if(isset($filter['FIELD_NAME']))
		{
			$filter['FIELD_NAME'] = is_array($filter['FIELD_NAME']) ? $filter['FIELD_NAME'] : array($filter['FIELD_NAME']);
			foreach(self::$existedFieldNameMap as $existedSrcFieldName => $existedDstFieldName)
			{
				if(!in_array($existedSrcFieldName, $filter['FIELD_NAME']))
				{
					unset(self::$existedFieldNameMap[$existedSrcFieldName]);
				}
			}
		}

		foreach($fields as $field)
		{
			$srcField = $entity->GetByID($field['ID']);
			if(!is_array($srcField))
			{
				continue;
			}

			if(isset($filter['FIELD_NAME']))
			{
				if(!in_array($srcField['FIELD_NAME'], $filter['FIELD_NAME']))
				{
					continue;
				}
			}

			$typeID = $srcField['USER_TYPE_ID'];
			do
			{
				$fieldName = 'UF_CRM_'.strtoupper(uniqid());
				$dbResult = $entity->GetList(
					array(),
					array('ENTITY_ID' => $entityID, 'FIELD_NAME' => $fieldName)
				);
			}
			while(is_array($dbResult->Fetch()));

			$dstField = array(
				'FIELD_NAME' => $fieldName,
				'ENTITY_ID' => $entityID,
				'USER_TYPE_ID' => $typeID,
				'SORT' => isset($srcField['SORT']) ? $srcField['SORT'] : 100,
				'MULTIPLE' => isset($srcField['MULTIPLE']) ? $srcField['MULTIPLE'] : 'N',
				'MANDATORY' => isset($srcField['MANDATORY']) ? $srcField['MANDATORY'] : 'N',
				'SHOW_FILTER' => isset($srcField['SHOW_FILTER']) ? $srcField['SHOW_FILTER'] : 'N',
				'SHOW_IN_LIST' => isset($srcField['SHOW_IN_LIST']) ? $srcField['SHOW_IN_LIST'] : 'N'
			);

			if(isset($srcField['SETTINGS']))
			{
				$dstField['SETTINGS'] = $srcField['SETTINGS'];
			}

			if(isset($srcField['EDIT_FORM_LABEL']))
			{
				$dstField['EDIT_FORM_LABEL'] = $srcField['EDIT_FORM_LABEL'];
			}

			if(isset($srcField['LIST_COLUMN_LABEL']))
			{
				$dstField['LIST_COLUMN_LABEL'] = $srcField['LIST_COLUMN_LABEL'];
			}

			if(isset($srcField['LIST_FILTER_LABEL']))
			{
				$dstField['LIST_FILTER_LABEL'] = $srcField['LIST_FILTER_LABEL'];
			}

			$ID = $entity->Add($dstField);
			if($ID === false)
			{
				throw new UserFieldSynchronizationException(
					$dstField,
					$APPLICATION->GetException(),
					UserFieldSynchronizationException::CREATE_FAILED,
					__FILE__,
					__LINE__
				);
			}

			if($typeID === 'enumeration')
			{
				if (is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$enumList = array();
					$enumQty = 0;
					$enumResult = call_user_func_array(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), array($field));
					while($enum = $enumResult->GetNext())
					{
						unset($enum['ID']);
						$enumList["n{$enumQty}"] = $enum;
						$enumQty++;
					}

					$enumEntity = new \CUserFieldEnum();
					$enumEntity->SetEnumValues($ID, $enumList);
				}
			}

			$synchronizedFieldNameMap[$srcField['FIELD_NAME']] = $fieldName;
		}

		$historyItem = self::getHistoryItem($srcEntityTypeID, $dstEntityTypeID);
		if($historyItem === null)
		{
			$historyItem = array();
		}

		$historyItem['sync'] = new DateTime();
		$historyItem['check'] = new DateTime();
		$historyItem['required'] = false;
		self::setHistoryItem($srcEntityTypeID, $dstEntityTypeID, $historyItem);

		return array_merge(self::$existedFieldNameMap, $synchronizedFieldNameMap);
	}
	public static function markAsSynchronized($srcEntityTypeID, $dstEntityTypeID)
	{
		$historyItem = self::getHistoryItem($srcEntityTypeID, $dstEntityTypeID);
		if($historyItem === null)
		{
			$historyItem = array();
		}

		$historyItem['check'] = new DateTime();
		$historyItem['required'] = false;
		self::setHistoryItem($srcEntityTypeID, $dstEntityTypeID, $historyItem);
	}
	/**
	* Compares source type fields with destination type fields.
	* Matches are searched by comparing field labels.
	* @static
	* @param int $srcEntityTypeID Source Entity Type ID
	* @param int $dstEntityTypeID Destination Entity Type ID
	* @param string $languageID Language
	* @return Array
	*/
	public static function getIntersection($srcEntityTypeID, $dstEntityTypeID, $languageID = '')
	{
		if(!is_string($languageID) || $languageID === '')
		{
			$languageID = LANGUAGE_ID;
		}

		$srcUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeID);
		$dstUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);

		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		/** @var \CAllMain $APPLICATION */
		global $APPLICATION;

		$srcFields = $USER_FIELD_MANAGER->GetUserFields($srcUfEntityID, 0, $languageID);
		$dstFields = $USER_FIELD_MANAGER->GetUserFields($dstUfEntityID, 0, $languageID);

		$map = array();
		foreach($dstFields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(!isset($map[$typeID]))
			{
				$map[$typeID] = array();
			}

			if(!isset($map[$typeID][$label]))
			{
				$map[$typeID][$label] = array('NAME' => $field['FIELD_NAME'], 'IS_BUSY' => false);
			}
		}

		$results = array();
		foreach($srcFields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			if(isset($results[$label]))
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(isset($map[$typeID]) && isset($map[$typeID][$label]) && !$map[$typeID][$label]['IS_BUSY'])
			{
				$results[$label] = array('LABEL' => $label, 'SRC_FIELD_NAME' => $field['FIELD_NAME'], 'DST_FIELD_NAME' => $map[$typeID][$label]['NAME']);
				$map[$typeID][$label]['IS_BUSY'] = true;
			}
		}

		return $results;
	}
	public static function getFieldComplianceCode(array $field)
	{
		$label = isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : '';
		if($label === '' && isset($field['LIST_COLUMN_LABEL']))
		{
			$label = $field['LIST_COLUMN_LABEL'];
		}

		return $label !== '' ? strtolower(str_replace(' ', '', $label)) : '';
	}
	public static function getFieldLabel(array $field)
	{
		$label = isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : '';
		if($label === '' && isset($field['LIST_COLUMN_LABEL']))
		{
			$label = $field['LIST_COLUMN_LABEL'];
		}

		return $label;
	}
	public static function getHistoryItem($srcEntityTypeID, $dstEntityTypeID)
	{
		$key = "{$srcEntityTypeID}_{$dstEntityTypeID}";
		$history = self::getHistory();
		return isset($history[$key]) ? $history[$key] : null;
	}
	public static function setHistoryItem($srcEntityTypeID, $dstEntityTypeID, array $historyItem)
	{
		$history = self::getHistory();
		$key = "{$srcEntityTypeID}_{$dstEntityTypeID}";

		$historyItem['src'] = $srcEntityTypeID;
		$historyItem['dst'] = $dstEntityTypeID;
		$history[$key] = $historyItem;
		self::setHistory($history);
	}
	public static function removeHistoryItem($srcEntityTypeID, $dstEntityTypeID)
	{
		$history = self::getHistory();
		unset($history["{$srcEntityTypeID}_{$dstEntityTypeID}"]);
		self::setHistory($history);
	}
	/**
	* Get synchronization history.
	* @return array
	*/
	protected static function getHistory()
	{
		if(self::$history !== null)
		{
			return self::$history;
		}

		self::$history = array();
		$s = Main\Config\Option::get('crm', 'crm_uf_sync_history', '', '');
		$ary = $s !== '' ? unserialize($s) : null;
		if(is_array($ary))
		{
			foreach($ary as $item)
			{
				if(!is_array($item))
				{
					continue;
				}

				$srcEntityTypeID = \CCrmOwnerType::ResolveID(isset($item['src']) ? $item['src'] : '');
				$dstEntityTypeID = \CCrmOwnerType::ResolveID(isset($item['dst']) ? $item['dst'] : '');

				if($srcEntityTypeID === \CCrmOwnerType::Undefined || $dstEntityTypeID === \CCrmOwnerType::Undefined)
				{
					continue;
				}

				$sync = isset($item['sync']) ? $item['sync'] : '';
				$check = isset($item['check']) ? $item['check'] : '';
				try
				{
					self::$history["{$srcEntityTypeID}_{$dstEntityTypeID}"] = array(
						'src' => $srcEntityTypeID,
						'dst' => $dstEntityTypeID,
						'sync' => $sync !== '' ? new DateTime($sync, \DateTime::ISO8601) : null,
						'check' => $check !== '' ? new DateTime($check, \DateTime::ISO8601) : null,
						'required' => isset($item['required']) ? $item['required'] : null
					);
				}
				catch(Main\ObjectException $e)
				{
				}
			}
		}

		return self::$history;
	}
	/**
	* Set synchronization history.
	* @param array $history History
	* @return void
	*/
	protected static function setHistory(array $history)
	{
		self::$history = $history;
		$ary = array();
		foreach(self::$history as $item)
		{
			/** @var DateTime $sync */
			$sync = isset($item['sync']) ? $item['sync'] : null;
			/** @var DateTime $check */
			$check = isset($item['check']) ? $item['check'] : null;

			$ary[] = array(
				'src' => \CCrmOwnerType::ResolveName($item['src']),
				'dst' => \CCrmOwnerType::ResolveName($item['dst']),
				'sync' => $sync !== null ? $sync->format(\DateTime::ISO8601) : '',
				'check' => $check !== null ? $check->format(\DateTime::ISO8601) : '',
				'required' => isset($item['required']) ? $item['required'] : null
			);
		}

		Main\Config\Option::set('crm', 'crm_uf_sync_history', serialize($ary), '');
	}
}