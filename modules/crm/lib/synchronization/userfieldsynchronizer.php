<?php
namespace Bitrix\Crm\Synchronization;
use Bitrix\Crm\Entity\Traits\VisibilityConfig;
use Bitrix\Crm\Model;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\UserField\UserFieldHistory;
use Bitrix\Main\UserField\Access\Permission\PermissionDictionary;

class UserFieldSynchronizer
{
	use VisibilityConfig;

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
	* @param array $options Operation options
	* @return bool
	*/
	public static function needForSynchronization($srcEntityTypeID, $dstEntityTypeID, $languageID = '', array $options = array())
	{
		$isRecycling = (isset($options['IS_RECYCLING']) && $options['IS_RECYCLING'] === true);
		
		$fieldsToCreate = self::getSynchronizationFields(
			$srcEntityTypeID,
			$dstEntityTypeID,
			$languageID,
			false,
			$isRecycling
		);
		if(!empty($fieldsToCreate))
		{
			return true;
		}

		if(isset($options['ENABLE_TRIM']) && $options['ENABLE_TRIM'] === true)
		{
			$fieldsToDelete = self::getSynchronizationFields(
				$dstEntityTypeID,
				$srcEntityTypeID,
				$languageID,
				false,
				$isRecycling
			);
			if(!empty($fieldsToDelete))
			{
				return true;
			}
		}

		return false;
	}
	/**
	* Prepare synchronization field list.
	* @static
	* @param int $srcEntityTypeID Source Entity Type ID
	* @param int $dstEntityTypeID Destination Entity Type ID
	* @param string $languageID Language
	* @return array
	*/
	public static function getSynchronizationFields(
		$srcEntityTypeID,
		$dstEntityTypeID,
		$languageID = '',
		$forced = false,
		$isRecycling = false
	)
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

		/** @var \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

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

			$isMultiple = $field['MULTIPLE'] === 'Y' ? 'Y' : 'N';
			if(!isset($map[$typeID][$isMultiple]))
			{
				$map[$typeID][$isMultiple] = array();
			}

			if(!isset($map[$typeID][$isMultiple][$label]))
			{
				$map[$typeID][$isMultiple][$label] = $field['FIELD_NAME'];
			}
		}

		self::$existedFieldNameMap = array();
		$results = array();

		$srcFields = $USER_FIELD_MANAGER->GetUserFields($srcUfEntityID, 0, $languageID);
		if (!$isRecycling)
		{
			$srcFields = VisibilityManager::getVisibleUserFields($srcFields);
		}

		foreach($srcFields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(!self::isUserFieldTypeSupported($typeID))
			{
				continue;
			}

			$isMultiple = $field['MULTIPLE'] === 'Y' ? 'Y' : 'N';
			if( !(isset($map[$typeID]) && isset($map[$typeID][$isMultiple]) && isset($map[$typeID][$isMultiple][$label])) )
			{
				$results[] = $field;
			}
			else
			{
				self::$existedFieldNameMap[$field['FIELD_NAME']] = $map[$typeID][$isMultiple][$label];
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

	public static function isUserFieldTypeSupported($userFieldTypeID)
	{
		return $userFieldTypeID !== 'resourcebooking';
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
	 * @param array $options Operation options
	 * @return array $synchronizedFieldNameMap
	 * @throws Main\ObjectException
	 * @throws UserFieldSynchronizationException
	 */
	public static function synchronize($srcEntityTypeID, $dstEntityTypeID, $languageID = '', $filter = array(), array $options = array())
	{
		/** @var \CMain $APPLICATION */
		global $APPLICATION;

		$synchronizedFieldNameMap = array();
		$entity = new \CUserTypeEntity();

		$isRecycling = (isset($options['IS_RECYCLING']) && $options['IS_RECYCLING'] === true);

		if(isset($options['ENABLE_TRIM']) && $options['ENABLE_TRIM'] === true)
		{
			$fieldsToDelete = self::getSynchronizationFields(
				$dstEntityTypeID,
				$srcEntityTypeID,
				$languageID,
				true,
				$isRecycling
			);
			foreach($fieldsToDelete as $field)
			{
				if(self::isUserFieldTypeSupported($field['USER_TYPE_ID']))
				{
					$entity->Delete($field['ID']);
				}
			}
		}

		$entityID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);
		$fieldsToCreateDraft = self::getSynchronizationFields(
			$srcEntityTypeID,
			$dstEntityTypeID,
			$languageID,
			true,
			$isRecycling
		);
		$labelMap = [];
		foreach ($fieldsToCreateDraft as $index => $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label !== '' && !isset($labelMap[$label]))
			{
				$labelMap[$label] = $index;
			}
		}
		$fieldsToCreate = [];
		foreach ($labelMap as $index)
		{
			$fieldsToCreate[] = $fieldsToCreateDraft[$index];
		}
		unset($fieldsToCreateDraft);

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

		foreach($fieldsToCreate as $field)
		{
			$srcField = $entity->GetByID($field['ID']);
			if(!is_array($srcField))
			{
				continue;
			}

			$typeID = $srcField['USER_TYPE_ID'];
			if(!self::isUserFieldTypeSupported($typeID))
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

			do
			{
				$fieldName = 'UF_CRM_'.mb_strtoupper(uniqid());
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
				'SORT' => $srcField['SORT'] ?? 100,
				'MULTIPLE' => $srcField['MULTIPLE'] ?? 'N',
				'MANDATORY' => $srcField['MANDATORY'] ?? 'N',
				'SHOW_FILTER' => $srcField['SHOW_FILTER'] ?? 'N',
				'SHOW_IN_LIST' => $srcField['SHOW_IN_LIST'] ?? 'N'
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
				$ex = $APPLICATION->GetException();
				if(!($ex instanceof \CApplicationException))
				{
					$ex = null;
				}

				throw new UserFieldSynchronizationException(
					$dstField,
					$ex,
					UserFieldSynchronizationException::CREATE_FAILED,
					__FILE__,
					__LINE__
				);
			}

			//region UserField visible access settings
			$visibilityConfig = self::getInstance()
				->prepareEntityFieldvisibilityConfigs($srcEntityTypeID);
			if (isset($visibilityConfig[$srcField['FIELD_NAME']]))
			{
				$accessCodesKeys = array_keys($visibilityConfig[$srcField['FIELD_NAME']]['accessCodes'] ?? []);
				$accessCodes = array_map(
					static function($accessCode){
						return ['ID' => $accessCode];
					}, $accessCodesKeys
				);
				VisibilityManager::saveEntityConfiguration(
					$accessCodes,
					$dstField['FIELD_NAME'],
					$dstEntityTypeID,
					PermissionDictionary::USER_FIELD_VIEW
					);
			}
			// endregion

			if($typeID === 'enumeration')
			{
				if (is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$enumList = array();
					$enumQty = 0;
					$enumResult = call_user_func_array(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), array($field));
					while($enum = $enumResult->Fetch())
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

		//remove this branch if proper bugfix exists and this workaround is no longer needed
		if (!empty($fieldsToCreate) || !empty($fieldsToDelete))
		{
			$dstFactory = Container::getInstance()->getFactory((int)$dstEntityTypeID);
			if ($dstFactory)
			{
				$dstDataClass = $dstFactory->getDataClass();

				//rebuild entity so that new user fields are added to map and old fields are removed
				Main\ORM\Entity::destroy($dstDataClass::getEntity());

				if ($dstFactory instanceof Factory\Dynamic)
				{
					//if we do not recompile entity for dynamic type, some fields will be absent
					Model\Dynamic\TypeTable::compileEntity($dstFactory->getType());
				}
			}
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
	* @return array
	*/
	public static function getIntersection($srcEntityTypeID, $dstEntityTypeID, $languageID = '')
	{
		if(!is_string($languageID) || $languageID === '')
		{
			$languageID = LANGUAGE_ID;
		}

		$srcUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeID);
		$dstUfEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);

		/** @var \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		/** @var \CMain $APPLICATION */
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

			$isMultiple = $field['MULTIPLE'] === 'Y' ? 'Y' : 'N';
			if(!isset($map[$typeID][$isMultiple]))
			{
				$map[$typeID][$isMultiple] = array();
			}

			if(!isset($map[$typeID][$isMultiple][$label]))
			{
				$map[$typeID][$isMultiple][$label] = array('NAME' => $field['FIELD_NAME'], 'IS_BUSY' => false);
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
			$isMultiple = $field['MULTIPLE'] === 'Y' ? 'Y' : 'N';
			if(isset($map[$typeID]) && isset($map[$typeID][$isMultiple]) && isset($map[$typeID][$isMultiple][$label])
				&& !($map[$typeID][$label]['IS_BUSY'] ?? null))
			{
				$results[$label] = array(
					'LABEL' => $label,
					'SRC_FIELD_NAME' => $field['FIELD_NAME'],
					'DST_FIELD_NAME' => $map[$typeID][$isMultiple][$label]['NAME']
				);
				$map[$typeID][$isMultiple][$label]['IS_BUSY'] = true;
			}
		}

		return $results;
	}
	public static function getDifference($srcEntityTypeID, $dstEntityTypeID, $languageID = '')
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

		$srcMap = self::prepareFieldMap($srcFields);
		$dstMap = self::prepareFieldMap($dstFields);

		$result = array();
		foreach($srcMap as $typeID => $fieldMapByMultiple)
		{
			foreach ($fieldMapByMultiple as $isMultiple => $fieldMap)
			{
				if (!isset($dstMap[$typeID][$isMultiple]))
				{
					foreach ($fieldMap as $label => $fieldName)
					{
						$result[$label] = array('LABEL' => $label, 'SRC_FIELD_NAME' => $fieldName);
					}
					continue;
				}

				$diffMap = array_diff_key($fieldMap, $dstMap[$typeID][$isMultiple]);
				if (!empty($diffMap))
				{
					foreach ($diffMap as $label => $fieldName)
					{
						$result[$label] = array('LABEL' => $label, 'SRC_FIELD_NAME' => $fieldName);
					}
				}
			}
		}
		return $result;
	}
	protected static function prepareFieldMap(array $fields)
	{
		$result = array();
		foreach($fields as $field)
		{
			$label = self::getFieldComplianceCode($field);
			if($label === '')
			{
				continue;
			}

			$typeID = $field['USER_TYPE_ID'];
			if(!isset($result[$typeID]))
			{
				$result[$typeID] = array();
			}

			$isMultiple = $field['MULTIPLE'] === 'Y' ? 'Y' : 'N';
			if(!isset($result[$typeID][$isMultiple]))
			{
				$result[$typeID][$isMultiple] = array();
			}

			if(!isset($result[$typeID][$isMultiple][$label]))
			{
				$result[$typeID][$isMultiple][$label] = $field['FIELD_NAME'];
			}
		}
		return $result;
	}
	public static function getFieldComplianceCode(array $field)
	{
		$label = isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : '';
		if($label === '' && isset($field['LIST_COLUMN_LABEL']))
		{
			$label = $field['LIST_COLUMN_LABEL'];
		}

		return $label !== ''? mb_strtolower(str_replace(' ', '', $label)) : '';
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
		$ary = $s !== '' ? unserialize($s, ['allowed_classes' => false]) : null;
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
