<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main;
use \Bitrix\Main\Type\Date;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\UserField\Types\EnumType;

abstract class EntityConversionMapper
{
	/** @var Array */
	private static $userFields = null;
	/** @var int */
	protected $srcEntityTypeID = 0;
	/** @var int */
	protected $srcEntityID = 0;
	public function __construct($srcEntityTypeID, $srcEntityID)
	{
		if(!is_int($srcEntityTypeID))
		{
			$srcEntityTypeID = (int)$srcEntityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($srcEntityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('srcEntityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}
		$this->srcEntityTypeID = $srcEntityTypeID;

		if(!is_int($srcEntityID))
		{
			$srcEntityID = (int)$srcEntityID;
		}

		if($srcEntityID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'srcEntityID');
		}
		$this->srcEntityID = $srcEntityID;
	}
	public function getSourceEntityTypeID()
	{
		return $this->srcEntityTypeID;
	}
	public function getSourceEntityID()
	{
		return $this->srcEntityID;
	}
	/**
	 * Get source fields
	 * @return array
	 */
	abstract public function getSourceFields();
	/**
	 * Get source field value
	 * @param string $fieldName Field name
	 * @return mixed
	 */
	public function getSourceFieldValue($fieldName, $default = null)
	{
		$fields = $this->getSourceFields();
		return isset($fields[$fieldName]) ? $fields[$fieldName] : $default;
	}
	abstract public function map(EntityConversionMap $map, array $options = null);
	public static function updateMap(EntityConversionMap $map)
	{
		//Synchonize dynamic bindings only
		$srcEntitTypeID = $map->getSourceEntityTypeID();
		$dstEntitTypeID = $map->getDestinationEntityTypeID();

		$outdatedItems = array();
		foreach($map->getItems() as $item)
		{
			$srcFieldID = $item->getSourceField();
			$dstFieldID = $item->getDestinationField();
			if($dstFieldID === '')
			{
				$dstFieldID = $srcFieldID;
			}

			$isDynamicSrc = EntityConversionMapItem::isDynamicField($srcFieldID);
			$isDynamicDst = EntityConversionMapItem::isDynamicField($dstFieldID);
			$srcField = $isDynamicSrc ? self::getUserField($srcEntitTypeID, $srcFieldID) : null;
			$dstField = $isDynamicDst ? self::getUserField($dstEntitTypeID, $dstFieldID) : null;

			if(($isDynamicSrc && $srcField === null) || ($isDynamicDst && $dstField === null))
			{
				$outdatedItems[] = $item;
				continue;
			}
			elseif($isDynamicSrc && $srcField !== null && $isDynamicDst && $dstField !== null && !$item->isLocked())
			{
				$srcCode = UserFieldSynchronizer::getFieldComplianceCode($srcField);
				$dstCode = UserFieldSynchronizer::getFieldComplianceCode($dstField);
				if($srcCode !== $dstCode)
				{
					$outdatedItems[] = $item;
					continue;
				}
			}
		}

		if(!empty($outdatedItems))
		{
			foreach($outdatedItems as $item)
			{
				$map->removeItem($item);
			}
		}

		$intersections = UserFieldSynchronizer::getIntersection($srcEntitTypeID, $dstEntitTypeID);
		foreach($intersections as $intersection)
		{
			$srcFieldID = $intersection['SRC_FIELD_NAME'];
			$dstFieldID = $intersection['DST_FIELD_NAME'];

			if($map->findItemBySourceID($srcFieldID) === null)
			{
				$map->createItem($srcFieldID, $dstFieldID);
			}
		}
	}
	public static function getUserFields($entityTypeID)
	{
		if(self::$userFields !== null && isset(self::$userFields[$entityTypeID]))
		{
			return self::$userFields[$entityTypeID];
		}

		if(self::$userFields !== null)
		{
			self::$userFields = array();
		}

		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		self::$userFields[$entityTypeID] = $USER_FIELD_MANAGER->GetUserFields(
			\CCrmOwnerType::ResolveUserFieldEntityID($entityTypeID)
		);

		return self::$userFields[$entityTypeID];
	}
	public static function getUserField($entityTypeID, $fieldName)
	{
		$fields = self::getUserFields($entityTypeID);
		return isset($fields[$fieldName]) ? $fields[$fieldName] : null;
	}
	public static function isUserFieldExists($entityTypeID, $fieldName)
	{
		$fields = self::getUserFields($entityTypeID);
		return isset($fields[$fieldName]);
	}
	protected static function initializeUserFields($entityTypeID, array &$fields)
	{
		$userFields = self::getUserFields($entityTypeID);
		foreach($userFields as $userField)
		{
			$typeID = isset($userField['USER_TYPE_ID']) ? $userField['USER_TYPE_ID'] : '';
			$name = isset($userField['FIELD_NAME']) ? $userField['FIELD_NAME'] : '';
			if($name === '' || $typeID === '')
			{
				continue;
			}

			//Skip already assigned fields
			if(isset($fields[$name]))
			{
				continue;
			}

			if (
				$typeID === EnumType::USER_TYPE_ID
				&& is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'GetList'])
			)
			{
				$dbResult = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'GetList'], $userField);

				$enumIds = [];
				while($enum = $dbResult->Fetch())
				{
					$isDefault = (($enum['DEF'] ?? 'N') === 'Y');
					if (!$isDefault)
					{
						continue;
					}

					$isMultiple = (($userField['MULTIPLE'] ?? 'N') === 'Y');
					if ($isMultiple)
					{
						$enumIds[] = $enum['ID'];
					}
					else
					{
						$enumIds = $enum['ID'];

						break;
					}
				}

				if(!empty($enumIds))
				{
					$fields[$name] = $enumIds;
				}

				continue;
			}

			$settings = isset($userField['SETTINGS']) && is_array($userField['SETTINGS'])
				? $userField['SETTINGS'] : array();

			if(!isset($settings['DEFAULT_VALUE']))
			{
				continue;
			}

			if(!is_array($settings['DEFAULT_VALUE']))
			{
				$fields[$name] =
					$userField['MULTIPLE'] === 'Y'
						? [ $settings['DEFAULT_VALUE'] ]
						: $settings['DEFAULT_VALUE']
				;
			}
			elseif($typeID === 'datetime')
			{
				$valueType = isset($settings['DEFAULT_VALUE']['TYPE']) ? $settings['DEFAULT_VALUE']['TYPE'] : '';
				if($valueType === 'NOW')
				{
					$d = new Date();
					$fields[$name] = $d->format(Date::getFormat());
				}
				elseif($valueType === 'FIXED'
					&& isset($settings['DEFAULT_VALUE']['VALUE'])
					&& $settings['DEFAULT_VALUE']['VALUE'] !== '')
				{
					try
					{
						$d = new Date($settings['DEFAULT_VALUE']['VALUE'], 'Y-m-d');
						$fields[$name] = $d->format(Date::getFormat());
					}
					catch(Main\ObjectException $e)
					{
					}
				}
			}
		}
	}

	public static function mapUserField($srcEntityTypeID, $srcFieldID, array &$srcFields, $dstEntityTypeID, $dstFieldID, array &$dstFields, array $options = null)
	{
		if(!isset($srcFields[$srcFieldID]))
		{
			return;
		}

		$srcField = self::getUserField($srcEntityTypeID, $srcFieldID);
		$dstField = self::getUserField($dstEntityTypeID, $dstFieldID);

		if($srcField && !UserFieldSynchronizer::isUserFieldTypeSupported($srcField['USER_TYPE_ID']))
		{
			return;
		}

		if($srcField && $srcField['USER_TYPE_ID'] === 'enumeration'
			&& $dstField && $dstField['USER_TYPE_ID'] === 'enumeration')
		{
			$srcValues = self::getEnumerationMap($srcField, false);
			$dstValues = self::getEnumerationMap($dstField, true);

			if(isset($srcField['MULTIPLE']) && $srcField['MULTIPLE'] === 'Y')
			{
				$enumIDs = $srcFields[$srcFieldID];
				if(!is_array($enumIDs))
				{
					$enumIDs = array($enumIDs);
				}

				if(isset($dstField['MULTIPLE']) && $dstField['MULTIPLE'] === 'Y')
				{
					foreach($enumIDs as $enumID)
					{
						if(isset($srcValues[$enumID]))
						{
							$hash = $srcValues[$enumID];
							if(isset($dstValues[$hash]))
							{
								if(!isset($dstFields[$dstFieldID]))
								{
									$dstFields[$dstFieldID] = array();
								}
								elseif(!is_array($dstFields[$dstFieldID]))
								{
									$dstFields[$dstFieldID] = array($dstFields[$dstFieldID]);
								}

								$dstFields[$dstFieldID][] = $dstValues[$hash];
							}
						}
					}
				}
				elseif(!empty($enumIDs))
				{
					$enumID = $enumIDs[0];
					if(isset($srcValues[$enumID]))
					{
						$hash = $srcValues[$enumID];
						if(isset($dstValues[$hash]))
						{
							$dstFields[$dstFieldID] = $dstValues[$hash];
						}
					}
				}
			}
			else
			{
				$enumID = $srcFields[$srcFieldID];
				if(isset($srcValues[$enumID]))
				{
					$hash = $srcValues[$enumID];
					if(isset($dstValues[$hash]))
					{
						$dstFields[$dstFieldID] = $dstValues[$hash];
					}
				}
			}

			return;
		}

		$enableFiles = true;
		if(is_array($options) && isset($options['ENABLE_FILES']))
		{
			$enableFiles = $options['ENABLE_FILES'];
		}

		if($enableFiles && $srcField && $srcField['USER_TYPE_ID'] === 'file'
			&& $dstField && $dstField['USER_TYPE_ID'] === 'file')
		{

			if(isset($srcField['MULTIPLE']) && $srcField['MULTIPLE'] === 'Y')
			{
				$fileIDs = $srcFields[$srcFieldID];
				if(!is_array($fileIDs))
				{
					$fileIDs = array($fileIDs);
				}

				if(isset($dstField['MULTIPLE']) && $dstField['MULTIPLE'] === 'Y')
				{
					foreach($fileIDs as $fileID)
					{
						$file = null;
						if(\CCrmFileProxy::TryResolveFile($fileID, $file, array('ENABLE_ID' => true)))
						{
							if(!isset($dstFields[$dstFieldID]))
							{
								$dstFields[$dstFieldID] = array();
							}
							$dstFields[$dstFieldID][] = $file;
						}
					}
				}
				elseif(!empty($fileIDs))
				{
					$fileID = $fileIDs[0];
					$file = null;
					if(\CCrmFileProxy::TryResolveFile($fileID, $file, array('ENABLE_ID' => true)))
					{
						$dstFields[$dstFieldID] = $file;
					}
				}
			}
			else
			{
				$file = null;
				if(\CCrmFileProxy::TryResolveFile($srcFields[$srcFieldID], $file, array('ENABLE_ID' => true)))
				{
					$dstFields[$dstFieldID] = $file;
				}
			}

			return;
		}

		if ($srcField && $srcField['USER_TYPE_ID'] === 'address'
			&& $dstField && $dstField['USER_TYPE_ID'] === 'address')
		{

			if (isset($srcField['MULTIPLE']) && $srcField['MULTIPLE'] === 'Y')
			{
				$addresses = $srcFields[$srcFieldID];
				if (!is_array($addresses))
				{
					$addresses = [$addresses];
				}

				if (isset($dstField['MULTIPLE']) && $dstField['MULTIPLE'] === 'Y')
				{
					foreach ($addresses as $address)
					{
						if (!isset($dstFields[$dstFieldID]))
						{
							$dstFields[$dstFieldID] = [];
						}
						$dstFields[$dstFieldID][] = static::getAddressFields($address);
					}
				}
				elseif (!empty($addresses))
				{
					$address = $addresses[0];
					$dstFields[$dstFieldID] = static::getAddressFields($address);
				}
			}
			else
			{
				$dstFields[$dstFieldID] = static::getAddressFields($srcFields[$srcFieldID]);
			}

			return;
		}

		$dstFields[$dstFieldID] = $srcFields[$srcFieldID];
	}

	protected static function getAddressFields($value)
	{
		if (!Main\Loader::includeModule('fileman') || !$value)
		{
			return null;
		}

		// the value has been cleared;
		// we have to return an empty value so that the original entity's value stays intact
		$isDelete = (is_string($value) && mb_strlen($value) > 4 && mb_substr($value, -4) === '_del');
		if ($isDelete)
		{
			return null;
		}

		$addressFields = AddressType::getAddressFieldsByValue($value);
		if (!$addressFields)
		{
			return null;
		}

		unset($addressFields['id']);

		try
		{
			$addressFields = Main\Web\Json::encode($addressFields);
		}
		catch (Main\ArgumentException $exception)
		{
			return null;
		}

		return $addressFields;
	}

	protected static function getEnumerationMap(array $field, $flip = false)
	{
		$result = array();
		if($field['USER_TYPE_ID'] === 'enumeration' && is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
		{
			$dbResult = call_user_func_array(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), array($field));
			while($enum = $dbResult->GetNext())
			{
				$result[$enum['ID']] = md5($enum['VALUE']);
			}
		}

		if($flip)
		{
			//Function 'strval' is required for returning values type to String.
			$result = array_map('strval', array_flip($result));
		}
		return $result;
	}
}
