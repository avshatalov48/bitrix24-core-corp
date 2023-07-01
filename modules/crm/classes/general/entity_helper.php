<?php

use Bitrix\Crm\Filter\EntityDataProvider;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service;
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
	 * Now it registers both timeline and event history records. But for compatibility reasons, no renaming of the method
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

		$options = static::extractArrayFromParams($params, 'options');
		$authorId = null;
		if (isset($options['CURRENT_USER']) && (int)$options['CURRENT_USER'] > 0)
		{
			$authorId = (int)$options['CURRENT_USER'];
		}

		$isWriteToHistoryEnabled = (bool)($options['IS_COMPARE_ENABLED'] ?? true);
		if ($isWriteToHistoryEnabled)
		{
			static::registerRelationEvents($itemIdentifier, $params, $authorId);
		}

		$isRestoration = isset($options['IS_RESTORATION']) && $options['IS_RESTORATION'] === true;
		$isWriteToTimelineDisabled = isset($options['DISABLE_TIMELINE_CREATION']) && $options['DISABLE_TIMELINE_CREATION'] === 'Y';
		$isMarkEventRegistrationEnabled = (bool)($params['isMarkEventRegistrationEnabled'] ?? true);
		if (!$isRestoration && !$isWriteToTimelineDisabled && $isMarkEventRegistrationEnabled)
		{
			static::registerMarkEvent($itemIdentifier, $params);
		}
	}

	protected static function registerRelationEvents(
		ItemIdentifier $itemIdentifier,
		array $params,
		?int $authorId = null
	): void
	{
		$options = static::extractArrayFromParams($params, 'options');
		$excludeFromRelationRegistration = static::extractArrayFromParams($options, 'EXCLUDE_FROM_RELATION_REGISTRATION');

		$context = null;
		if ($authorId !== null)
		{
			$context = clone Service\Container::getInstance()->getContext();
			$context->setUserId($authorId);
		}

		$relationRegistrar = Service\Container::getInstance()->getRelationRegistrar();

		$relationRegistrar->registerByFieldsChange(
			$itemIdentifier,
			static::extractArrayFromParams($params, 'fieldsInfo'),
			static::extractArrayFromParams($params, 'previousFields'),
			static::extractArrayFromParams($params, 'currentFields'),
			$excludeFromRelationRegistration,
			$context,
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
			$relationRegistrar->registerByBindingsChange(
				$itemIdentifier,
				(int)($bindings['entityTypeId'] ?? 0),
				static::extractArrayFromParams($bindings, 'previous'),
				$currentBindings,
				$excludeFromRelationRegistration,
				$context,
			);
		}
	}

	protected static function registerMarkEvent(ItemIdentifier $itemIdentifier, array $params, ?int $authorId = null): void
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
				$currentSemantics,
				$authorId,
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

	public static function prepareOperationByOptions(
		\Bitrix\Crm\Service\Operation $operation,
		array $options,
		bool $checkPermissions
	): void
	{
		$context = clone \Bitrix\Crm\Service\Container::getInstance()->getContext();

		if (isset($options['ITEM_OPTIONS']))
		{
			$context->setItemOptions($options['ITEM_OPTIONS']);
		}
		if (isset($options['CURRENT_USER']) && (int)$options['CURRENT_USER'] > 0)
		{
			$context->setUserId((int)$options['CURRENT_USER']);
		}
		if (isset($options['eventId']))
		{
			$context->setEventId($options['eventId']);
		}
		if (isset($options['PRESERVE_CONTENT_TYPE']) && is_bool($options['PRESERVE_CONTENT_TYPE']))
		{
			$context->setItemOption('PRESERVE_CONTENT_TYPE', $options['PRESERVE_CONTENT_TYPE']);
		}
		$operation->setContext($context);

		if (!$checkPermissions)
		{
			$operation->disableCheckAccess();
		}

		$disableUserFieldsCheck = $options['DISABLE_USER_FIELD_CHECK'] ?? false;
		if ($disableUserFieldsCheck === true)
		{
			$operation->disableCheckFields();
		}

		$disableRequiredUserFieldsCheck = $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] ?? false;
		if ($disableRequiredUserFieldsCheck === true)
		{
			$operation->disableCheckRequiredUserFields();
		}

		$excludeFromRelationRegistration =
			isset($options['EXCLUDE_FROM_RELATION_REGISTRATION']) && is_array($options['EXCLUDE_FROM_RELATION_REGISTRATION'])
				? $options['EXCLUDE_FROM_RELATION_REGISTRATION']
				: []
		;
		if (!empty($excludeFromRelationRegistration))
		{
			$operation->excludeItemsFromTimelineRelationEventsRegistration($excludeFromRelationRegistration);
		}

		$operation->disableCheckLimits();

		$isCompareEnabled = (bool)($options['IS_COMPARE_ENABLED'] ?? true);
		if ($isCompareEnabled === false)
		{
			$operation->disableSaveToHistory();
		}

		$disableTimeline = isset($options['DISABLE_TIMELINE_CREATION']) ? ($options['DISABLE_TIMELINE_CREATION'] === 'Y') : false;
		if ($disableTimeline === true)
		{
			$operation->disableSaveToTimeline();
		}

		$enableDeferredMode = (bool)($options['ENABLE_DEFERRED_MODE'] ?? true);
		if ($enableDeferredMode)
		{
			$operation->enableDeferredCleaning();
		}
		else
		{
			$operation->disableDeferredCleaning();
		}

		$enableDupIndexInvalidation = (bool)($options['ENABLE_DUP_INDEX_INVALIDATION'] ?? true);
		if ($enableDupIndexInvalidation)
		{
			$operation->enableDuplicatesIndexInvalidation();
		}
		else
		{
			$operation->disableDuplicatesIndexInvalidation();
		}

		$autocompleteActivities = (bool)($options['ENABLE_ACTIVITY_COMPLETION'] ?? true);
		if ($autocompleteActivities)
		{
			$operation->enableActivitiesAutocompletion();
		}
		else
		{
			$operation->disableActivitiesAutocompletion();
		}

		$processBizProc = (bool)($options['PROCESS_BIZPROC'] ?? true);
		if ($processBizProc)
		{
			$operation->enableBizProc();
		}
		else
		{
			$operation->disableBizProc();
		}
	}

	/**
	 * @param \Bitrix\Main\Error[] $errors
	 * @return \CAdminException[]
	 */
	public static function transformOperationErrorsToCheckExceptions(array $errors): array
	{
		$messages = [];
		foreach ($errors as $error)
		{
			if ($error->getCode() === Bitrix\Crm\Field::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE)
			{
				$messages[] = [
					'id' => $error->getCustomData()['fieldName'] ?? '',
					'text' => $error->getMessage(),
				];
			}
		}

		return [new \CAdminException($messages)];
	}

	/**
	 * @param \Bitrix\Crm\Settings\Traits\EnableFactory $settings
	 * @param \Bitrix\Main\Request $request
	 */
	public static function setEnabledFactoryFlagByRequest($settings, \Bitrix\Main\Request $request): void
	{
		if ($request->get('enableFactory') !== null)
		{
			$enableFactory = (string)$request->get('enableFactory');

			$settings->setFactoryEnabled(mb_strtoupper($enableFactory) === 'Y');
		}
	}

	public static function applyCounterFilterWrapper(
		int $entityTypeId,
		string $gridId,
		array $extras,
		array &$arFilter,
		$entityFilter
	): void
	{
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return;
		}

		if (isset($entityFilter))
		{
			$provider = $entityFilter->getEntityDataProvider();
		}
		else
		{
			$filterFactory = Service\Container::getInstance()->getFilterFactory();
			$provider = $filterFactory->getDataProvider($filterFactory::getSettingsByGridId($entityTypeId, $gridId));
		}

		if ($provider instanceof EntityDataProvider)
		{
			$provider->applyCounterFilter($entityTypeId, $arFilter, $extras);
		}

		unset($filterFactory, $provider);
	}
}
