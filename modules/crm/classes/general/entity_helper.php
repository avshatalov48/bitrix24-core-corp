<?php

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Timeline;

class CCrmEntityHelper
{
	private static $ENTITY_KEY = '/^\s*(L|D|C|CO)_([0-9]+)\s*$/i';
	private static $ITEMS = array();
	private static $DEFAULT_FIELD_TYPES = array('EMAIL', 'WEB', 'PHONE', 'IM');

	public static function IsEntityKey($key)
	{
		return preg_match(self::$ENTITY_KEY, strval($key)) === 1;
	}
	public static function ParseEntityKey($key, &$entityInfo)
	{
		if(preg_match(self::$ENTITY_KEY, strval($key), $match) !== 1)
		{
			$entityInfo = array();
			return false;
		}

		$entityTypeAbbr = mb_strtoupper($match[1]);
		$entityID = intval($match[2]);
		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeAbbr);
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		$entityInfo = array(
			'ENTITY_TYPE_ABBR' => $entityTypeAbbr,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_TYPE_NAME' => $entityTypeName,
			'ENTITY_ID' => $entityID
		);
		return true;
	}
	public static function GetCached($sCacheName, $sKey)
	{
		return isset(self::$ITEMS[$sCacheName]) && isset(self::$ITEMS[$sCacheName][$sKey])
			? self::$ITEMS[$sCacheName][$sKey] : false;
	}
	public static function SetCached($sCacheName, $sKey, $value)
	{
		if(!isset(self::$ITEMS[$sCacheName]))
		{
			self::$ITEMS[$sCacheName] = array();
		}
		self::$ITEMS[$sCacheName][$sKey] = $value;
	}
	public static function RemoveCached($sCacheName, $sKey)
	{
		if(isset(self::$ITEMS[$sCacheName]))
		{
			unset(self::$ITEMS[$sCacheName]);
		}
	}
	public static function NormalizeUserFields(&$arFields, $entityID, $manager = null, $arOptions = null)
	{
		$entityID = strval($entityID);

		if(!$manager)
		{
			$manager = $GLOBALS['USER_FIELD_MANAGER'];
		}

		$userType = new CCrmUserType($manager, $entityID);
		$userType->PrepareUpdate($arFields, $arOptions);
	}
	public static function PrepareMultiFieldFilter(&$arFilter, $arFieldTypes = array(), $comparisonType = '%', $lockComparisonType = false)
	{
		if(!is_array($arFieldTypes) && is_string($arFieldTypes))
		{
			$arFieldTypes = array($arFieldTypes);
		}

		if(!is_array($arFieldTypes) || count($arFieldTypes) === 0)
		{
			// Default field types
			$arFieldTypes = self::$DEFAULT_FIELD_TYPES;
		}

		if(!is_string($comparisonType))
		{
			$comparisonType = '%';
		}

		if($comparisonType === '')
		{
			$comparisonType = '%';
		}

		if(isset($arFilter['FM']))
		{
			unset($arFilter['FM']);
		}

		foreach($arFieldTypes as $fieldType)
		{
			if(!isset($arFilter[$fieldType]))
			{
				continue;
			}

			$fieldValue = $arFilter[$fieldType];
			if(is_array($fieldValue))
			{
				$fieldValue = count($fieldValue) ? $fieldValue[0] : '';
			}

			if(!is_string($fieldValue))
			{
				$fieldValue = strval($fieldValue);
			}

			$fieldValue = trim($fieldValue);
			if($fieldValue === '')
			{
				unset($arFilter[$fieldType]);
				continue;
			}

			if(!isset($arFilter['FM']))
			{
				$arFilter['FM'] = array();
			}

			$curentComparisonType = $comparisonType;
			if(!$lockComparisonType)
			{
				if(preg_match('/^%([^%]+)%$/', $fieldValue, $m) === 1)
				{
					$fieldValue = $m[1];
					$curentComparisonType = '%';
				}
				elseif(preg_match('/^%([^%]+)$/', $fieldValue, $m) === 1)
				{
					$fieldValue = $m[1];
					$curentComparisonType = '%=';
				}
				elseif(preg_match('/^([^%]+)%$/', $fieldValue, $m) === 1)
				{
					$fieldValue = $m[1];
					$curentComparisonType = '=%';
				}
			}

			if($curentComparisonType === '=%')
			{
				$fieldValue = preg_replace('/%/', '', $fieldValue);
				$arFilter['FM'][] = array('TYPE_ID' => $fieldType, '=%VALUE' => "{$fieldValue}%");
			}
			elseif($curentComparisonType === '%=')
			{
				$fieldValue = preg_replace('/%/', '', $fieldValue);
				$arFilter['FM'][] = array('TYPE_ID' => $fieldType, '%=VALUE' => "%{$fieldValue}");
			}
			else
			{
				$arFilter['FM'][] = array('TYPE_ID' => $fieldType, "{$curentComparisonType}VALUE" => $fieldValue);
			}

			unset($arFilter[$fieldType]);
		}
	}

	/**
	 * Simple method-adapter that is used to avoid copy-paste in compatible Crm entities
	 * Used, for example, in @see \CAllCrmDeal and other entities
	 *
	 * @param array $params = [
	 *     'entityTypeId' => 0,
	 *     'entityId' => 0,
	 *     'fieldsInfo' => [], // fields description from static::getFieldsInfo
	 *     'previousFields' => [],
	 *     'currentFields' => [],
	 *     'previousStageSemantics' => '', // constant of PhaseSemantics. UNDEFINED by default
	 *     'currentStageSemantics' => '', // constant of PhaseSemantics.  UNDEFINED by default
	 *     'options' => [], // options that are passed to a CRUD method
	 *     'bindings' => [
	 *         'entityTypeId' => 0, // entityTypeId that is passed in EntityBinding methods
	 *         'previous' => [],
	 *         'current' => null, // array|null. Null if bindings are not changed
	 *     ],
	 *     'isMarkEventRegistrationEnabled' => true, // pass false here if you want to disable mark event registration
	 * ]
	 *
	 */
	public static function registerAdditionalTimelineEvents(array $params): void
	{
		$entityTypeId = (int)($params['entityTypeId'] ?? 0);
		$entityId = (int)($params['entityId'] ?? 0);
		if (($entityId <= 0) || !\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return;
		}

		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		static::registerRelationEvents($itemIdentifier, $params);

		$isMarkEventRegistrationEnabled = (bool)($params['isMarkEventRegistrationEnabled'] ?? true);
		if ($isMarkEventRegistrationEnabled)
		{
			static::registerMarkEvent($itemIdentifier, $params);
		}
	}

	protected static function registerRelationEvents(ItemIdentifier $itemIdentifier, array $params): void
	{
		$options = static::extractArrayFromParams($params, 'options');
		$excludeFromRelationRegistration = static::extractArrayFromParams($options, 'EXCLUDE_FROM_RELATION_REGISTRATION');

		Timeline\RelationController::getInstance()->registerEventsByFieldsChange(
			$itemIdentifier,
			static::extractArrayFromParams($params, 'fieldsInfo'),
			static::extractArrayFromParams($params, 'previousFields'),
			static::extractArrayFromParams($params, 'currentFields'),
			$excludeFromRelationRegistration
		);

		$bindings = static::extractArrayFromParams($params, 'bindings');
		// current bindings are null if they were not changed in this call
		$currentBindings =
			isset($bindings['current']) && is_array($bindings['current'])
				? $bindings['current']
				: null
		;
		if (!empty($bindings) && !is_null($currentBindings))
		{
			Timeline\RelationController::getInstance()->registerEventsByBindingsChange(
				$itemIdentifier,
				(int)($bindings['entityTypeId'] ?? 0),
				static::extractArrayFromParams($bindings, 'previous'),
				$currentBindings,
				$excludeFromRelationRegistration
			);
		}
	}

	protected static function registerMarkEvent(ItemIdentifier $itemIdentifier, array $params): void
	{
		$fieldsInfo = static::extractArrayFromParams($params, 'fieldsInfo');

		if (!static::isStagesSupportedForEntity($fieldsInfo))
		{
			return;
		}

		$previousSemantics = (string)($params['previousStageSemantics'] ?? PhaseSemantics::UNDEFINED);
		$currentSemantics = (string)($params['currentStageSemantics'] ?? PhaseSemantics::UNDEFINED);

		if (
			$previousSemantics !== $currentSemantics
			&& PhaseSemantics::isFinal($currentSemantics)
		)
		{
			Timeline\MarkController::getInstance()->onItemMoveToFinalStage(
				$itemIdentifier,
				$currentSemantics
			);
		}
	}

	protected static function isStagesSupportedForEntity(array $fieldsInfo): bool
	{
		return !empty(static::getStageIdFieldName($fieldsInfo));
	}

	protected static function getStageIdFieldName(array $fieldsInfo): ?string
	{
		foreach ($fieldsInfo as $fieldName => $singleFieldInfo)
		{
			$type = $singleFieldInfo['TYPE'] ?? null;

			if (
				$type === \Bitrix\Crm\Field::TYPE_CRM_STATUS
				&& \CCrmFieldInfoAttr::isFieldHasAttribute($singleFieldInfo, \CCrmFieldInfoAttr::Progress)
			)
			{
				return $fieldName;
			}
		}

		return null;
	}

	protected static function extractArrayFromParams(array $params, string $key): array
	{
		if (isset($params[$key]) && is_array($params[$key]))
		{
			return $params[$key];
		}

		return [];
	}
}
