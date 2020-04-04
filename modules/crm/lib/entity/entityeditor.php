<?php
namespace Bitrix\Crm\Entity;

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
		'crm_quote' => true
	);

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
			if(strpos($k, 'UF_CRM') === 0)
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
		return 'CRM_ENTITY_EDITOR';
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
			$entityTypeID = \CCrmOwnerType::ResolveID(substr($typeName, 4));
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
		elseif($typeName === 'integer' || $typeName === 'user')
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

		if(strpos($name, 'UF_') !== 0)
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
			$k = strtoupper($k);
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
}