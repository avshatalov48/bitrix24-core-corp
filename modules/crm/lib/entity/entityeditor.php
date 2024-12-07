<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Crm;

class EntityEditor
{
	/** @var \CCrmPerms|null */
	protected static $userPermissions = null;
	protected static $allowedTypesMap = array(
		'integer' => true,
		'double' => true,
		'char' => true,
		'string' => true,
		'date' => true,
		'datetime' => true,
		'user' => true,
		'location' => true,
		'crm_status' => true,
		'crm_currency' => true,
		'crm_lead' => true,
		'crm_company' => true,
		'crm_contact' => true,
		'crm_quote' => true,
		'enumeration' => true,
		'iblock_element' => true,
		'iblock_section' => true,
	);
	protected static $multiFields = null;

	protected static function getUserPermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}
		return self::$userPermissions;
	}
	/**
	 * Prepare Multifield Data for save in Destination Entity.
	 * @param array $data Source Multifield Data.
	 * @param array $entityFields Destination Entity Fields.
	 */
	public static function internalizeMultifieldData(array $data, array &$entityFields)
	{
		foreach($data as $typeName => $items)
		{
			if(!isset($entityFields[$typeName]))
			{
				$entityFields[$typeName] = array();
			}

			foreach($items as $itemID => $item)
			{
				$entityFields[$typeName][] = array_merge(array('ID' => $itemID), $item);
			}
		}
	}
	/**
	 * Prepare Entity Fields received from editor for copy.
	 * @param array $entityFields Entity Fields.
	 * @param \CCrmUserType $userType User Type Entity.
	 * @return void
	 */
	public static function prepareForCopy(array &$entityFields, \CCrmUserType $userType)
	{
		$request = Main\Context::getCurrent()->getRequest();

		$userFields = $userType->GetFields();
		foreach($userFields as $fieldName => $userField)
		{
			if(!isset($entityFields[$fieldName]))
			{
				continue;
			}

			if($userField['USER_TYPE_ID'] !== 'file')
			{
				continue;
			}

			$deletionKey = "{$fieldName}_del";
			if(isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y')
			{
				$results = array();
				if(is_array($entityFields[$fieldName]))
				{
					$deletedFileIDs = isset($request[$deletionKey]) && is_array($request[$deletionKey])
						? $request[$deletionKey] : array();
					foreach($entityFields[$fieldName] as $fileInfo)
					{
						if(is_numeric($fileInfo) && in_array($fileInfo, $deletedFileIDs))
						{
							continue;
						}

						$results[] = $fileInfo;
					}
				}
				$entityFields[$fieldName] = $results;
			}
			else
			{
				$fileInfo = $entityFields[$fieldName];
				if(is_numeric($fileInfo)
					&& isset($request[$deletionKey])
					&& ($request[$deletionKey] === 'Y' || $request[$deletionKey] == $fileInfo)
				)
				{
					$entityFields[$fieldName] = false;
				}
			}
		}
	}
	/**
	 * @param \Bitrix\Crm\Conversion\EntityConversionWizard $wizard
	 * @param int $entityTypeID
	 * @param array $entityFields
	 * @param array $userFields
	 */
	public static function prepareConvesionMap($wizard, $entityTypeID, array &$entityFields, array &$userFields)
	{
		$mappedFields = $wizard->mapEntityFields($entityTypeID, array('ENABLE_FILES' => false));
		foreach($mappedFields as $k => $v)
		{
			if(mb_strpos($k, 'UF_CRM') === 0)
			{
				$userFields[$k] = $v;
			}
			elseif($k === 'FM')
			{
				self::internalizeMultifieldData($v, $entityFields);
			}
			else
			{
				$entityFields[$k] = $v;
			}
		}
	}
	/**
	 * Get User Selector Context
	 * Can be used in CSocNetLogDestination::GetDestinationSort
	 * @return string
	 */
	public static function getUserSelectorContext()
	{
		return EntitySelector::CONTEXT;
	}
	/**
	 * Save selected User in Finder API
	 * @param int $userID User ID.
	 * @return void
	 */
	public static function registerSelectedUser($userID)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID > 0)
		{
			Main\FinderDestTable::merge(
				array(
					'CONTEXT' => self::getUserSelectorContext(),
					'CODE' => Main\FinderDestTable::convertRights(array("U{$userID}"))
				)
			);
		}
	}
	protected static function setDataField($typeName, $name, $value, array &$entityData,  array &$userFields)
	{
		if(!isset(self::$allowedTypesMap[$typeName]))
		{
			return false;
		}

		if($typeName === 'crm_lead' ||
			$typeName === 'crm_company' ||
			$typeName === 'crm_contact' ||
			$typeName === 'crm_quote'
		)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID(mb_substr($typeName, 4));
			if($entityTypeID === \CCrmOwnerType::Undefined)
			{
				return false;
			}

			$value = (int)$value;
			if(!Crm\Security\EntityAuthorization::checkReadPermission($entityTypeID, $value, self::getUserPermissions()))
			{
				return false;
			}
		}
		elseif($typeName === 'integer' || $typeName === 'user' || $typeName === 'enumeration' || $typeName === 'iblock_element' || $typeName === 'iblock_section')
		{
			$value = (int)$value;
		}
		elseif($typeName === 'double')
		{
			$value = (double)$value;
		}
		elseif($typeName === 'date' || $typeName === 'datetime')
		{
			$format = $typeName === 'date' ? Main\Type\Date::getFormat() : Main\Type\DateTime::getFormat();
			$time = Main\Type\DateTime::tryParse($value, $format);
			if(!$time)
			{
				return false;
			}
			$value = $time->format($format);
		}

		if(mb_strpos($name, 'UF_') !== 0)
		{
			//System Field
			$entityData[$name] = $value;
			return true;
		}
		if(isset($userFields[$name]))
		{
			//User Field (it uses custom initialization scheme).
			$userFields[$name]['VALUE'] = $value;
			return true;
		}
		return false;
	}
	public static function mapData(array $fieldInfos, array &$entityData,  array &$userFields, array $data)
	{
		foreach($data as $k => $v)
		{
			if(!isset($fieldInfos[$k]))
			{
				continue;
			}

			$info = $fieldInfos[$k];
			$attributes = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES']: array();
			if(!empty($attributes) && in_array(\CCrmFieldInfoAttr::ReadOnly, $attributes, true))
			{
				continue;
			}

			self::setDataField(isset($info['TYPE']) ? $info['TYPE']: 'string', $k, $v, $entityData, $userFields);
		}
	}
	public static function mapRequestData(array $fieldInfos, array &$entityData,  array &$userFields)
	{
		$results = array();
		/** @var \CCrmPerms $userPermissions */
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		/** @var Main\HttpRequest $request */
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$queryParams = $request->getQueryList()->toArray();
		foreach($queryParams as $k => $v)
		{
			$k = mb_strtoupper($k);
			if(!isset($fieldInfos[$k]))
			{
				continue;
			}

			$info = $fieldInfos[$k];
			$attributes = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES']: array();
			if(!empty($attributes) && in_array(\CCrmFieldInfoAttr::ReadOnly, $attributes, true))
			{
				continue;
			}

			//Strip all tags for security reasons
			if(is_array($v))
			{
				$v = array_map('strip_tags', $v);
			}
			else
			{
				$v = strip_tags($v);
			}

			$typeName = isset($info['TYPE']) ? $info['TYPE']: 'string';
			if(self::setDataField($typeName, $k, $v, $entityData, $userFields))
			{
				$results[$k] = $v;
			}
		}

		return $results;
	}
	public static function prepareMultiFieldDataModel($entityTypeID, $entityID, $typeID, array &$entityMultiFields, array $params = null)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
		$multiFieldViewClassNames = array(
			'PHONE' => 'crm-entity-phone-number',
			'EMAIL' => 'crm-entity-email',
			'IM' => 'crm-entity-phone-number'
		);

		for($i = 0, $length = count($entityMultiFields); $i < $length; $i++)
		{
			$value = $entityMultiFields[$i]['VALUE'];
			$valueTypeID = $entityMultiFields[$i]['VALUE_TYPE'];
			$multiFieldEntityType = $multiFieldEntityTypes[$typeID];

			$entityMultiFields[$i] =
				array_merge(
					$entityMultiFields[$i],
					array(
						'VIEW_DATA' => \CCrmViewHelper::PrepareMultiFieldValueItemData(
							$typeID,
							array(
								'VALUE' => $value,
								'VALUE_TYPE_ID' => $valueTypeID,
								'VALUE_TYPE' => isset($multiFieldEntityType[$valueTypeID]) ? $multiFieldEntityType[$valueTypeID] : null,
								'CLASS_NAME' => isset($multiFieldViewClassNames[$typeID]) ? $multiFieldViewClassNames[$typeID] : ''
							),
							array(
								'ENABLE_SIP' => false,
								'SIP_PARAMS' => array(
									'ENTITY_TYPE_NAME' => $entityTypeName,
									'ENTITY_ID' => $entityID,
									'AUTO_FOLD' => true
								)
							)
						)
					)
				);
		}
	}
	public static function prepareEntityInfo($entityTypeID, $entityID, array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		$userPermissions = isset($params['USER_PERMISSIONS'])
			? $params['USER_PERMISSIONS'] : \CCrmPerms::GetCurrentUserPermissions();

		return \CCrmEntitySelectorHelper::PrepareEntityInfo(
			\CCrmOwnerType::ResolveName($entityTypeID),
			$entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !Crm\Security\EntityAuthorization::checkReadPermission($entityTypeID, $entityID, $userPermissions),
				'USER_PERMISSIONS' => $userPermissions,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NORMALIZE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			)
		);
	}

}
