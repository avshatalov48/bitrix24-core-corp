<?php

use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\Category\EntityTypeRelationsRepository;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integration\BankDetailResolver;
use Bitrix\Crm\Integration\ClientResolver;
use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Integrity\DuplicateControl;
use Bitrix\Crm\Item;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmComponentHelper
{
	public static function TrimZeroTime($str)
	{
		$str = trim($str);
		if(mb_substr($str, -9) == ' 00:00:00')
		{
			return mb_substr($str, 0, -9);
		}
		elseif(mb_substr($str, -3) == ':00')
		{
			return mb_substr($str, 0, -3);
		}
		return $str;
	}

	public static function RemoveSeconds($str, $options = null)
	{
		$ary = array();
		if(preg_match('/(\d{1,2}):(\d{1,2}):(\d{1,2})/', $str, $ary, PREG_OFFSET_CAPTURE) !== 1)
		{
			return $str;
		}

		$time = "{$ary[1][0]}:{$ary[2][0]}";
		//Treat tail as part of time (AM/PM)
		$tailPos = $ary[3][1] + 2;
		if($tailPos < mb_strlen($str))
		{
			$time .= mb_substr($str, $tailPos);
		}
		$timeFormat = is_array($options) && isset($options['TIME_FORMAT']) ? strval($options['TIME_FORMAT']) : '';
		return mb_substr($str, 0, $ary[0][1]).($timeFormat === ''? $time : str_replace('#TIME#', $time, $timeFormat));
	}

	public static function TrimDateTimeString($str, $options = null)
	{
		return self::RemoveSeconds($str, $options);
	}

	public static function SynchronizeFormSettings($formID, $userFieldEntityID, $options = array())
	{
		$formID = strval($formID);
		$userFieldEntityID = strval($userFieldEntityID);
		$options = is_array($options) ? $options : array();

		if($formID === '')
		{
			return;
		}

		$arOptions = CUserOptions::GetOption('main.interface.form', $formID, array());
		if(isset($arOptions['settings_disabled'])
			&& $arOptions['settings_disabled'] === 'Y'
			|| !(isset($arOptions['tabs']) && is_array($arOptions['tabs'])))
		{
			return;
		}

		$changed = false;

		$normalizeTabs = isset($options['NORMALIZE_TABS']) ? $options['NORMALIZE_TABS'] : array();
		if(!empty($normalizeTabs))
		{
			if(COption::GetOptionString('crm', mb_strtolower($formID).'_normalized', 'N') !== 'Y')
			{
				foreach($arOptions['tabs'] as &$tab)
				{
					if(!in_array($tab['id'], $normalizeTabs, true))
					{
						continue;
					}

					$tabName = $tab['name'];
					// remove counter from tab name
					$tabName = preg_replace('/\s\(\d+\)$/', '', $tabName);
					if($tabName !== $tab['name'])
					{
						$tab['name'] = $tabName;
						if(!$changed)
						{
							$changed = true;
						}
					}
				}
				unset($tab);
				reset($arOptions['tabs']);
				if($changed)
				{
					CUserOptions::SetOption('main.interface.form', $formID, $arOptions);
					$changed = false;
				}
				COption::SetOptionString('crm', mb_strtolower($formID).'_normalized', 'Y');
			}
		}

		if($userFieldEntityID === '')
		{
			return;
		}

		$bRemoveFields = (isset($options['REMOVE_FIELDS']) && is_array($options['REMOVE_FIELDS']));
		$bAddFields = (isset($options['ADD_FIELDS']) && is_array($options['ADD_FIELDS']));
		if ($bRemoveFields)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if (is_array($tab) && isset($tab['id']) && isset($tab['fields']) && is_array($tab['fields'])
					&& isset($options['REMOVE_FIELDS'][$tab['id']]) && is_array($options['REMOVE_FIELDS'][$tab['id']]))
				{
					foreach($tab['fields'] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
						{
							if (in_array($item['id'], $options['REMOVE_FIELDS'][$tab['id']]))
							{
								unset($tab['fields'][$key]);
								$changed = true;
							}
						}
					}
				}
			}
		}
		if ($bAddFields)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if (is_array($tab) && isset($tab['id']) && isset($tab['fields']) && is_array($tab['fields'])
					&& isset($options['ADD_FIELDS'][$tab['id']]) && is_array($options['ADD_FIELDS'][$tab['id']]))
				{
					$addFieldsNames = array();
					foreach ($options['ADD_FIELDS'][$tab['id']] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
							$addFieldsNames[$item['id']] = $key;
					}
					unset($key, $item);
					$addIndex = array();
					$removeIndex = array();
					foreach($tab['fields'] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
						{
							if (isset($options['ADD_FIELDS'][$tab['id']][$item['id']]))
								$addIndex[$item['id']] = $key;
							if ($addFieldsNames[$item['id']])
								$removeIndex[] = $addFieldsNames[$item['id']];
						}
					}
					unset($key, $item);
					foreach ($removeIndex as $key)
						unset($addIndex[$key]);
					unset($key);
					if (count($addIndex) > 0)
					{
						foreach ($addIndex as $key => $index)
						{
							array_splice(
								$tab['fields'], $index + 1, 0,
								array($index + 1 => $options['ADD_FIELDS'][$tab['id']][$key])
							);
							$changed = true;
						}
						unset($key, $index);
					}
				}
			}
		}

		global $USER_FIELD_MANAGER;
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($userFieldEntityID);
		if(is_array($arUserFields) && count($arUserFields) > 0)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if(!(isset($tab['fields']) && is_array($tab['fields'])))
				{
					continue;
				}

				$arJunkKeys = array();

				foreach($tab['fields'] as $itemKey => $item)
				{
					$itemID = isset($item['id'])? mb_strtoupper($item['id']) : '';
					if(mb_strpos($itemID, 'UF_CRM_') === 0 && !isset($arUserFields[$itemID]))
					{
						$arJunkKeys[] = $itemKey;
					}
				}

				if(count($arJunkKeys) > 0)
				{
					if(!$changed)
					{
						$changed = true;
					}

					foreach($arJunkKeys as $key)
					{
						unset($tab['fields'][$key]);
					}
				}
			}
			unset($tab);
		}

		if($changed)
		{
			CUserOptions::SetOption('main.interface.form', $formID, $arOptions);
		}
	}

	public static function PrepareEntityFields($arValues, $arFields)
	{
		$result = array();
		foreach($arValues as $k => &$v)
		{
			if(!isset($arFields[$k]))
			{
				$result[$k] = $v;
			}
			else
			{
				$type = isset($arFields[$k]['TYPE'])? mb_strtolower($arFields[$k]['TYPE']) : '';
				if($type !== 'string' )
				{
					$result["~{$k}"] = $result[$k] = $v;
				}
				else
				{
					if(is_string($v))
					{
						$result["~{$k}"] = $v;
						$result[$k] = htmlspecialcharsbx($v);
					}
					else
					{
						$result["~{$k}"] = $result[$k] = $v;
					}
				}
			}
		}
		unset($v);
		return $result;
	}

	public static function PrepareExportFieldsList(&$arSelect, $arFieldMap, $processMultiFields = true)
	{
		if($processMultiFields)
		{
			$arMultiFieldTypes = CCrmFieldMulti::GetEntityTypes();
			foreach($arMultiFieldTypes as $typeID => &$arType)
			{
				if(isset($arFieldMap[$typeID]))
				{
					continue;
				}

				$arFieldMap[$typeID] = array();
				$arValueTypes = array_keys($arType);
				foreach($arValueTypes as $valueType)
				{
					$arFieldMap[$typeID][] = "{$typeID}_{$valueType}";
				}
			}
			unset($arType);
		}

		foreach($arFieldMap as $fieldID => &$arFieldReplace)
		{
			$offset = array_search($fieldID, $arSelect, true);
			if($offset === false)
			{
				continue;
			}

			array_splice(
				$arSelect,
				$offset,
				1,
				array_diff($arFieldReplace, $arSelect)
			);
		}
		unset($arFieldReplace);
	}

	public static function FindFieldKey($fieldID, &$arFields)
	{
		$fieldID = strval($fieldID);
		if(!is_array($arFields) || empty($arFields))
		{
			return false;
		}

		$result = false;
		foreach($arFields as $k => &$v)
		{
			$id = isset($v['id']) ? $v['id'] : '';
			if($id === $fieldID)
			{
				$result = $k;
				break;
			}
		}
		unset($v);
		return $result;
	}

	public static function FindField($fieldID, &$arFields)
	{
		$key = self::FindFieldKey($fieldID, $arFields);
		return $key !== false ? $arFields[$key] : null;
	}

	public static function RegisterScriptLink($url)
	{
		$url = trim(mb_strtolower(strval($url)));
		if($url === '')
		{
			return false;
		}
		$GLOBALS['APPLICATION']->AddHeadScript($url);
		return true;
	}

	public static function EnsureFormTabPresent(&$tabs, $tab, $index = -1)
	{
		if(!is_array($tabs) || empty($tabs) || !is_array($tab))
		{
			return false;
		}

		$tabID = isset($tab['id']) ? $tab['id'] : '';
		if($tabID === '')
		{
			return false;
		}

		$isFound = false;
		foreach($tabs as &$curTab)
		{
			$curTabID = isset($curTab['id']) ? $curTab['id'] : '';
			if($curTabID === $tabID)
			{
				$isFound = true;
				break;
			}
		}
		unset($curTab);

		if($isFound)
		{
			return false;
		}

		foreach($tab['fields'] as &$field)
		{
			if(isset($field['value']))
			{
				unset($field['value']);
			}
		}
		unset($field);

		$index = intval($index);
		if($index < 0 || $index >= count($tabs))
		{
			$tabs[] = $tab;
		}
		else
		{
			array_splice($tabs, $index, 0, array($tab));
		}
		return true;
	}

	public static function getFieldInfoData($entityTypeId, $fieldType, array $options = [])
	{
		$result = [];
		switch ($fieldType)
		{
			case "requisite":
				$result = [
					'presets'=> \CCrmInstantEditorHelper::prepareRequisitesPresetList(
						EntityRequisite::getDefaultPresetId($entityTypeId)
					),
					'feedback_form' => EntityRequisite::getRequisiteFeedbackFormParams(),
					'isEditMode' => $options['IS_EDIT_MODE'] ?? false,
				];
				break;
			case "requisite_address":
				$result = static::getRequisiteAddressFieldData((int)$entityTypeId);
				break;
			case "address":
				$featureRestriction = RestrictionManager::getAddressSearchRestriction();
				$result = [
					'multiple' => false,
					'autocompleteEnabled' => $featureRestriction->hasPermission(),
					'featureRestrictionCallback' => (
						$featureRestriction ? $featureRestriction->prepareInfoHelperScript() : ''
					),
				];
				break;
		}

		return $result;
	}

	public static function getRequisiteAddressFieldData(int $entityTypeId, int $categoryId = 0): array
	{
		$featureRestriction = RestrictionManager::getAddressSearchRestriction();
		$addressTypeInfos = [];
		foreach (EntityAddressType::getAllDescriptions() as $id => $desc)
		{
			$addressTypeInfos[$id] = [
				'ID' => $id,
				'DESCRIPTION' => $desc
			];
		}
		$countryAddressTypeMap = [];
		foreach (EntityRequisite::getCountryAddressZoneMap() as $countryId => $addressZoneId)
		{
			$countryAddressTypeMap[$countryId] = EntityAddressType::getIdsByZonesOrValues([$addressZoneId]);
		}
		$addressZoneId = EntityAddress::getZoneId();

		$result = [
			'multiple' => true,
			'types' => $addressTypeInfos,
			'autocompleteEnabled' => $featureRestriction->hasPermission(),
			'featureRestrictionCallback' => $featureRestriction->prepareInfoHelperScript(),
			'addressZoneConfig' => [
				'defaultAddressType' => EntityAddressType::getDefaultIdByEditorConfigOrByZone($entityTypeId),
				'currentZoneAddressTypes' => EntityAddressType::getIdsByZonesOrValues([$addressZoneId]),
				'countryAddressTypeMap' => $countryAddressTypeMap,
			],
		];

		if (CCrmOwnerType::IsDefined($entityTypeId) && $categoryId > 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoryAvailable($categoryId))
			{
				$category = $factory->getCategory($categoryId);
				if ($category instanceof ItemCategory)
				{
					$result['defaultAddressTypeByCategory'] = $category->getDefaultAddressType();
				}
			}
		}

		return $result;
	}

	public static function getRequisiteAutocompleteFieldInfoData(int $countryId): array
	{
		$clientResolverPropertyType = ClientResolver::getClientResolverPropertyWithPlacements($countryId);
		$featureRestriction = ClientResolver::getRestriction($countryId);

		return [
			'enabled' => !!$clientResolverPropertyType,
			'featureRestrictionCallback' =>
				$featureRestriction ? $featureRestriction->prepareInfoHelperScript() : ''
			,
			'placeholder' => ClientResolver::getClientResolverPlaceholderText($countryId),
			'feedback_form' => EntityRequisite::getRequisiteFeedbackFormParams(),
			'clientResolverPlacementParams' => ClientResolver::getClientResolverPlacementParams($countryId),
		];
	}

	public static function getBankDetailsAutocompleteFieldInfoData(int $countryId): array
	{
		$clientResolverPropertyType = BankDetailResolver::getClientResolverPropertyWithPlacements($countryId);

		return [
			'enabled' => !!$clientResolverPropertyType,
			'featureRestrictionCallback' => '',
			'placeholder' => BankDetailResolver::getClientResolverPlaceholderText($countryId),
			'clientResolverPlacementParams' => BankDetailResolver::getClientResolverPlacementParams($countryId),
		];
	}

	public static function getEventTabParams(
		int $entityId,
		string $tabName,
		string $entityTypeName,
		array $result
	): array
	{
		$tabParams = [
			'id' => 'tab_event',
			'name' => $tabName,
		];

		if ($entityId > 0)
		{
			if (!RestrictionManager::isHistoryViewPermitted())
			{
				$tabParams['tariffLock'] = RestrictionManager::getHistoryViewRestriction()->prepareInfoHelperScript();
			}
			else
			{
				$tabParams['loader'] = [
					'serviceUrl' =>
						'/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site='
						. SITE_ID . '&' . bitrix_sessid_get()
					,
					'componentData' => [
						'template' => '',
						'contextId' => "{$entityTypeName}_{$entityId}_EVENT",
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'AJAX_OPTION_ADDITIONAL' => "{$entityTypeName}_{$entityId}_EVENT",
							'ENTITY_TYPE' => $entityTypeName,
							'ENTITY_ID' => $entityId,
							'PATH_TO_USER_PROFILE' => $result['PATH_TO_USER_PROFILE'],
							'TAB_ID' => 'tab_event',
							'INTERNAL' => 'Y',
							'SHOW_INTERNAL_FILTER' => 'Y',
							'PRESERVE_HISTORY' => true,
							'NAME_TEMPLATE' => $result['NAME_TEMPLATE']
						], 'crm.event.view')
					]
				];
			}
		}
		else
		{
			$tabParams['enabled'] = false;
		}

		return $tabParams;
	}

	/**
	 * Method allows detecting active item for "bitrix:crm.control_panel"
	 *
	 * @param string          $entityName Entity name (CONTACT|COMPANY or custom value)
	 * @param int|string|null $categoryId Category ID
	 *
	 * @return string
	 */
	public static function getMenuActiveItemId(string $entityName, $categoryId): string
	{
		$categoryId = isset($categoryId) ? (int)$categoryId : 0;

		return $categoryId > 0
			? "{$entityName}_C{$categoryId}"
			: $entityName;
	}

	/**
	 * @param int $entityTypeId Entity type ID
	 * @param int $categoryId   Entity category ID
	 *
	 * @return int[][]
	 *
	 * @todo: temporary stub to get entity client field additional parameters
	 */
	public static function getEntityClientFieldCategoryParams(int $entityTypeId, int $categoryId = 0, ?int $parentEntityTypeId = null): array
	{
		if ($entityTypeId === CCrmOwnerType::SmartDocument || $parentEntityTypeId === CCrmOwnerType::SmartDocument)
		{
			$contactCategory = Container::getInstance()
				->getFactory(\CCrmOwnerType::Contact)
				->getCategoryByCode(\Bitrix\Crm\Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE)
			;
			if ($contactCategory)
			{
				return [
					\CCrmOwnerType::Contact => [
						'categoryId' => $contactCategory->getId(),
						'extraCategoryIds' => [
							$categoryId
						]
					],
					\CCrmOwnerType::Company => [
						'categoryId' => $categoryId,
					]
				];
			}
		}

		return array_map(
			function ($categoryId)
			{
				return [
					'categoryId' => $categoryId,
				];
			},
			EntityTypeRelationsRepository::getInstance()->getMapByEntityTypeId(
				$entityTypeId,
				$categoryId
			)
		);
	}

	public static function prepareMultifieldData(
		int $entityTypeId,
		array $entityIds,
		array $typeIds,
		array &$entityData,
		array $options = []
	)
	{
		$addToDataLevel = isset($options['ADD_TO_DATA_LEVEL']) && $options['ADD_TO_DATA_LEVEL'] === true;
		$copyMode = isset($options['COPY_MODE']) && $options['COPY_MODE'] === true;

		if (empty($entityIds))
		{
			return;
		}

		$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
		$multiFieldViewClassNames = [
			'PHONE' => 'crm-entity-phone-number',
			'EMAIL' => 'crm-entity-email',
			'IM' => 'crm-entity-phone-number',
		];

		if (!isset($entityData['MULTIFIELD_DATA']))
		{
			$entityData['MULTIFIELD_DATA'] = [];
		}

		$filter = [
			'=ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeId),
			'@ELEMENT_ID' => $entityIds,
		];
		if (!empty($typeIds))
		{
			$filter['@TYPE_ID'] = $typeIds;
		}

		// fetch field IDs to create phone country list
		$multiFieldIds = [];
		$dbResultIds = CCrmFieldMulti::GetListEx(['ID' => 'asc'], $filter, false, false, ['ID']);
		while ($row = $dbResultIds->fetch())
		{
			$multiFieldIds[] = (int)$row['ID'];
		}
		$phoneCountryList = CCrmFieldMulti::GetPhoneCountryList($multiFieldIds);

		$ownerTitles = self::getOwnerTitles($entityTypeId, $entityIds);

		$dbResult = CCrmFieldMulti::GetListEx(['ID' => 'asc'], $filter);
		while ($fields = $dbResult->fetch())
		{
			$elementID = (int)$fields['ELEMENT_ID'];
			$entityKey = "{$entityTypeId}_{$elementID}";
			$typeID = $fields['TYPE_ID'];
			$value = $fields['VALUE'] ?? '';
			if ($value === '')
			{
				continue;
			}

			$ID = $fields['ID'];
			$complexID = isset($fields['COMPLEX_ID']) ? $fields['COMPLEX_ID'] : '';
			$valueTypeID = isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '';

			if (!isset($entityData['MULTIFIELD_DATA'][$typeID]))
			{
				$entityData['MULTIFIELD_DATA'][$typeID] = array();
			}

			if (!isset($entityData['MULTIFIELD_DATA'][$typeID][$entityKey]))
			{
				$entityData['MULTIFIELD_DATA'][$typeID][$entityKey] = array();
			}

			//Is required for phone & email & messenger menu
			if (
				$typeID === 'PHONE'
				|| $typeID === 'EMAIL'
				|| ($typeID === 'IM' && OpenLineManager::isImOpenLinesValue($value))
			)
			{
				$formattedValue = $typeID === 'PHONE'
					? Main\PhoneNumber\Parser::getInstance()->parse($value)->format()
					: $value;

				$entityData['MULTIFIELD_DATA'][$typeID][$entityKey][] = [
					'ID' => $ID,
					'VALUE' => $value,
					'VALUE_TYPE' => $valueTypeID,
					'VALUE_EXTRA' => [
						'COUNTRY_CODE' => $phoneCountryList[$ID] ?? ''
					],
					'VALUE_FORMATTED' => $formattedValue,
					'COMPLEX_ID' => $complexID,
					'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($complexID, false),
					'TITLE' => OpenLineManager::isImOpenLinesValue($value) ? OpenLineManager::getOpenLineTitle($value) : '',
					'OWNER' => [
						'ID' => $elementID,
						'TYPE_ID' => $entityTypeId,
						'TITLE' => $ownerTitles[$elementID] ?? '',
					],
				];
			}

			if ($addToDataLevel)
			{
				$multiFieldID = $ID;
				$countryCode = $phoneCountryList[$multiFieldID] ?? '';
				if ($copyMode)
				{
					$multiFieldID = "n0{$multiFieldID}";
				}

				$entityData[$typeID][] = [
					'ID' => $multiFieldID,
					'VALUE' => $value,
					'VALUE_TYPE' => $valueTypeID,
					'VALUE_EXTRA' => [
						'COUNTRY_CODE' => $countryCode,
					],
					'VIEW_DATA' => \CCrmViewHelper::PrepareMultiFieldValueItemData(
						$typeID,
						[
							'VALUE' => $value,
							'VALUE_TYPE_ID' => $valueTypeID,
							'VALUE_TYPE' => $multiFieldEntityTypes[$typeID][$valueTypeID] ?? null,
							'CLASS_NAME' => $multiFieldViewClassNames[$typeID] ?? '',
						],
						[
							'ENABLE_SIP' => false,
							'SIP_PARAMS' => [
								'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName($entityTypeId),
								'ENTITY_ID' => $elementID,
								'AUTO_FOLD' => true,
							],
						]
					)
				];
			}
		}
	}

	/**
	 * @param int $entityTypeId
	 * @param int[] $entityIds
	 * @return array
	 */
	private static function getOwnerTitles(int $entityTypeId, array $entityIds): array
	{
		if (empty($entityIds))
		{
			return [];
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !\CcrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			return [];
		}

		$select = [
			Item::FIELD_NAME_ID
		];

		if ($factory->isFieldExists(Item::FIELD_NAME_CATEGORY_ID))
		{
			$select[] = Item::FIELD_NAME_CATEGORY_ID;
		}

		if ($factory->isFieldExists(Item::FIELD_NAME_TITLE))
		{
			$select[] = Item::FIELD_NAME_TITLE;
		}

		if ($entityTypeId == CCrmOwnerType::Contact)
		{
			$select[] = Item::FIELD_NAME_LAST_NAME;
			$select[] = Item::FIELD_NAME_SECOND_NAME;
			$select[] = Item::FIELD_NAME_NAME;
			$select[] = Item::FIELD_NAME_HONORIFIC;
		}

		$items = $factory->getItemsFilteredByPermissions([
			'select' => $select,
			'filter' => [
				'@ID' => $entityIds,
			],
		]);

		$result = [];
		foreach ($items as $item)
		{
			$result[$item->getId()] = $item->getHeading();
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function prepareClientEditorFieldsParams(array $params = []): array
	{
		$result = [];

		$entityTypeCategoryMap = [
			CCrmOwnerType::Contact => 0,
			CCrmOwnerType::Company => 0,
		];

		$entityTypeMap = [];
		if (isset($params['entityTypes']) && is_array($params['entityTypes']))
		{
			foreach($params['entityTypes'] as $entityTypeId)
			{
				if (is_int($entityTypeId) && isset($entityTypeCategoryMap[$entityTypeId]))
				{
					$entityTypeMap[$entityTypeId] = true;
				}
			}
		}

		if (isset($params['categoryParams']) && is_array($params['categoryParams']))
		{
			foreach (array_keys($entityTypeCategoryMap) as $entityTypeId)
			{
				if (
					isset($params['categoryParams'][$entityTypeId])
					&& is_array($params['categoryParams'][$entityTypeId])
					&& isset($params['categoryParams'][$entityTypeId]['categoryId'])
					&& is_int($params['categoryParams'][$entityTypeId]['categoryId'])
					&& $params['categoryParams'][$entityTypeId]['categoryId'] > 0
				)
				{
					$entityTypeCategoryMap[$entityTypeId] = $params['categoryParams'][$entityTypeId]['categoryId'];
				}
			}
		}

		$isLocationModuleIncluded = Main\Loader::includeModule('location');

		foreach (array_keys($entityTypeCategoryMap) as $entityTypeId)
		{
			if (empty($entityTypeMap) || isset($entityTypeMap[$entityTypeId]))
			{
				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
				$result[$entityTypeName] = [
					'REQUISITES' => static::getFieldInfoData($entityTypeId, 'requisite')
				];
				if ($isLocationModuleIncluded)
				{
					$result[$entityTypeName]['ADDRESS'] =
						static::getRequisiteAddressFieldData($entityTypeId, $entityTypeCategoryMap[$entityTypeId])
					;
				}
			}
		}

		return $result;
	}

	public static function prepareClientEditorDuplicateControlParams(array $params = []): array
	{
		$result = [];

		$entityTypes =
			(isset($params['entityTypes']) && is_array($params['entityTypes']))
				? $params['entityTypes']
				: []
		;

		foreach ($entityTypes as $entityTypeId)
		{
			$entityTypeId = (int)$entityTypeId;
			if (
				CCrmOwnerType::IsDefined($entityTypeId)
				&& DuplicateControl::isControlEnabledFor($entityTypeId)
			)
			{
				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
				$entityTypeNameLower = mb_strtolower($entityTypeName);
				$result[$entityTypeId] = [
					'enabled' => true,
					'serviceUrl' =>
						'/bitrix/components/bitrix/crm.'
						. $entityTypeNameLower
						. '.edit/ajax.php?'
						. bitrix_sessid_get(),
					'entityTypeName' => $entityTypeName,
					'groups' => [
						'title' => [
							'parameterName' => 'TITLE',
							'groupType' => 'single',
							'groupSummaryTitle' => Loc::getMessage('CRM_COMPONENT_HELPER_DUP_CTRL_TTL_SUMMARY_TITLE')
						],
						'email' => [
							'groupType' => 'communication',
							'communicationType' => 'EMAIL',
							'groupSummaryTitle' => Loc::getMessage('CRM_COMPONENT_HELPER_DUP_CTRL_EMAIL_SUMMARY_TITLE')
						],
						'phone' => [
							'groupType' => 'communication',
							'communicationType' => 'PHONE',
							'groupSummaryTitle' => Loc::getMessage('CRM_COMPONENT_HELPER_DUP_CTRL_PHONE_SUMMARY_TITLE')
						],
					],
				];
			}
		}

		return $result;
	}

	public static function encodeErrorMessage(string $text): string
	{
		if ($text === '')
		{
			return '';
		}
		$text = str_ireplace(['<br/>', '<br />', '<br>'], '<br>', $text);
		$textLines = explode('<br>', $text);
		$textLines = array_map(static function($item) { return htmlspecialcharsbx($item); }, $textLines);

		return implode('<br>', $textLines);
	}

	public static function prepareInitReceiverRepositoryJS(int $entityTypeId, int $entityId): string
	{
		\Bitrix\Main\UI\Extension::load('crm.messagesender');

		$receivers = [];
		if (\CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
		{
			$repo = \Bitrix\Crm\MessageSender\Channel\ChannelRepository::create(
				new \Bitrix\Crm\ItemIdentifier($entityTypeId, $entityId),
			);

			$receivers = $repo->getToList();
		}

		$receiversJson = Main\Web\Json::encode($receivers);

		return <<<JS
<script>
	BX.ready(() => {
		BX.Crm.MessageSender.ReceiverRepository.onDetailsLoad({$entityTypeId}, {$entityId}, '{$receiversJson}');
	});
</script>
JS;
	}
}

class CCrmInstantEditorHelper
{
	private static $TEMPLATES = array(
		'_LINK_' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-#FIELD_TYPE#">
		<a class="crm-fld-text" href="#VIEW_VALUE#" target="_blank" >#VALUE#</a>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-#FIELD_TYPE#"></span>
</span>',
		'INPUT' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-input">
		<span class="crm-fld-text">#VALUE#</span>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-input"></span>
</span>',
		'SELECT' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-select">
		<span class="crm-fld-text">#TEXT#</span>
		<span class="crm-fld-value">
			<select class="crm-fld-element-select#CLASS#">#OPTIONS_HTML#</select>
			<input class="crm-fld-element-value" type="hidden" value="#VALUE#"/>
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-select"></span>
</span>',
		'TEXT_AREA' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-textarea">
		<span class="crm-fld-text">#VALUE#</span>
		<span class="crm-fld-value">
			<textarea class="crm-fld-element-textarea" rows="25" cols="50" style="display: none;">#VALUE#</textarea>
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-textarea"></span>
</span>',
		'FULL_NAME' => '<span class="crm-fld-block crm-fld-block-multi-input">
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_1#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_1#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_1#"/>
	</span>
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_2#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_2#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_2#"/>
	</span>
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_3#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_3#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_3#"/>
	</span>
	<span class="crm-fld-icon crm-fld-icon-multiple-input"></span>
</span>',
		'LHE' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-lhe">
		<span class="crm-fld-text crm-fld-text-lhe">#TEXT#</span>
		<span class="crm-fld-value">
			<input class="crm-fld-element-value" type="hidden" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
			<input class="crm-fld-element-lhe-data" type="hidden" value="#SETTINGS#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-lhe"></span>
</span>
<div id="#WRAPPER_ID#" style="display:none;" class="crm-fld-lhe-wrap" >#HTML#</div>'
	);

	private static $IS_FILEMAN_INCLUDED = false;
	public static function CreateMultiFields($fieldTypeID, &$fieldValues, &$formFields, $fieldParams = array(), $readOnlyMode = true)
	{
		$fieldTypeID = mb_strtoupper(strval($fieldTypeID));
		if($fieldTypeID === '' || !is_array($fieldValues) || count($fieldValues) === 0 || !is_array($formFields))
		{
			return false;
		}

		if(!is_array($fieldParams))
		{
			$fieldParams = array();
		}

		foreach($fieldValues as $ID => &$data)
		{
			$valueType = isset($data['VALUE_TYPE'])? mb_strtoupper($data['VALUE_TYPE']) : '';
			$value = isset($data['VALUE']) ? $data['VALUE'] : '';

			$fieldID = "FM.{$fieldTypeID}.{$valueType}";
			$field = array(
				'id' => $fieldID,
				'name' => CCrmFieldMulti::GetEntityName($fieldTypeID, $valueType, true)
			);

			if($readOnlyMode)
			{
				$field['type'] = 'label';
				$field['value'] = CCrmFieldMulti::GetTemplate($fieldTypeID, $valueType, $value);
			}
			else
			{
				$templateType = 'INPUT';
				$editorFieldType = mb_strtolower($fieldTypeID);

				if($fieldTypeID === 'PHONE' || $fieldTypeID === 'EMAIL' || $fieldTypeID === 'WEB')
				{
					$templateType = '_LINK_';

					if($fieldTypeID === 'WEB')
					{
						if($valueType !== 'WORK' && $valueType !== 'HOME' && $valueType !== 'OTHER')
						{
							$editorFieldType .= '-'.mb_strtolower($valueType);
						}
					}
				}
				elseif($fieldTypeID === 'IM')
				{
					$templateType = $valueType === 'SKYPE' || $valueType === 'ICQ' || $valueType === 'MSN' ? '_LINK_' : 'INPUT';
					$editorFieldType .= '-'.mb_strtolower($valueType);
				}

				$template = isset(self::$TEMPLATES[$templateType]) ? self::$TEMPLATES[$templateType] : '';

				if($template === '')
				{
					$field['type'] = 'label';
					$field['value'] = CCrmFieldMulti::GetTemplate($fieldTypeID, $valueType, $value);
				}
				else
				{
					$viewValue = $value;
					if($fieldTypeID === 'PHONE')
					{
						$viewValue = CCrmCallToUrl::Format($value);
					}
					elseif($fieldTypeID === 'EMAIL')
					{
						$viewValue = "mailto:{$value}";
					}
					elseif($fieldTypeID === 'WEB')
					{
						if($valueType === 'OTHER' || $valueType === 'WORK' || $valueType === 'HOME')
						{
							$hasProto = preg_match('/^http(?:s)?:\/\/(.+)/', $value, $urlMatches) > 0;
							if($hasProto)
							{
								$value = $urlMatches[1];
							}
							else
							{
								$viewValue = "http://{$value}";
							}
						}
						elseif($valueType === 'FACEBOOK')
						{
							$viewValue = "http://www.facebook.com/{$value}/";
						}
						elseif($valueType === 'TWITTER')
						{
							$viewValue = "http://twitter.com/{$value}/";
						}
						elseif($valueType === 'LIVEJOURNAL')
						{
							$viewValue = "http://{$value}.livejournal.com/";
						}
					}
					elseif($fieldTypeID === 'IM')
					{
						if($valueType === 'SKYPE')
						{
							$viewValue = "skype:{$value}?chat";
						}
						elseif($valueType === 'ICQ')
						{
							$viewValue = "http://www.icq.com/people/{$value}/";
						}
						elseif($valueType === 'MSN')
						{
							$viewValue = "msn:{$value}";
						}
					}

					$field['type'] = 'custom';
					$field['value'] = str_replace(
						array('#NAME#', '#FIELD_TYPE#', '#VALUE#', '#VIEW_VALUE#'),
						array($fieldID, htmlspecialcharsbx($editorFieldType), htmlspecialcharsbx($value), htmlspecialcharsbx($viewValue)),
						$template
					);
				}
			}

			$formFields[] = !empty($fieldParams) ? array_merge($field, $fieldParams) : $field;
		}
		unset($data);

		return true;
	}

	public static function CreateField($fieldID, $fieldName, $fieldTemplateName, $fieldValues, &$formFields, $fieldParams = array(), $ignoreIfEmpty = true)
	{
		$fieldID = strval($fieldID);
		$fieldName = strval($fieldName);
		$fieldTemplateName = strval($fieldTemplateName);

		if(!isset(self::$TEMPLATES[$fieldTemplateName]))
		{
			return false;
		}

		$field = array(
			'id' => $fieldID,
			'name' => $fieldName
		);

		if($fieldTemplateName === 'FULL_NAME')
		{
			$field['type'] = 'custom';
			$field['value'] = str_replace(
				array(
					'#VALUE_1#', '#NAME_1#',
					'#VALUE_2#', '#NAME_2#',
					'#VALUE_3#', '#NAME_3#',
				),
				array(
					isset($fieldValues['LAST_NAME']) ? htmlspecialcharsbx($fieldValues['LAST_NAME']) : '', 'LAST_NAME',
					isset($fieldValues['NAME']) ? htmlspecialcharsbx($fieldValues['NAME']) : '', 'NAME',
					isset($fieldValues['SECOND_NAME']) ? htmlspecialcharsbx($fieldValues['SECOND_NAME']) : '', 'SECOND_NAME'
				),
				self::$TEMPLATES[$fieldTemplateName]
			);
		}
		elseif($fieldTemplateName === 'INPUT' || $fieldTemplateName === 'TEXT_AREA')
		{
			$value = isset($fieldValues['VALUE']) ? htmlspecialcharsbx($fieldValues['VALUE']) : '';
			if($value === '' && $ignoreIfEmpty)
			{
				// IGNORE EMPTY VALUES (INSERT STUB ONLY)
				$field['type'] = 'label';
				$field['value'] = '';
			}
			else
			{
				if($fieldTemplateName === 'TEXT_AREA')
				{
					//Convert NL, CR chars to BR tags
					$value = str_replace(array("\r", "\n"), '', nl2br($value));
				}

				$field['type'] = 'custom';
				$field['value'] = str_replace(
					array(
						'#VALUE#',
						'#NAME#'
					),
					array(
						$value,
						$fieldID
					),
					self::$TEMPLATES[$fieldTemplateName]
				);
			}
		}
		elseif($fieldTemplateName === 'SELECT')
		{
			$value = isset($fieldValues['VALUE']) ? $fieldValues['VALUE'] : '';
			$text = isset($fieldValues['TEXT']) ? $fieldValues['TEXT'] : '';
			$class = isset($fieldValues['CLASS']) ? $fieldValues['CLASS'] : '';

			$options = isset($fieldValues['OPTIONS']) && is_array($fieldValues['OPTIONS']) ? $fieldValues['OPTIONS'] : array();
			$optionHtml = '';
			if(!empty($options))
			{
				foreach($options as $k => &$v)
				{
					$optionHtml .= '<option value="'.htmlspecialcharsbx($k).'"'.($value === $v ? 'selected="selected"' : '').'>'.htmlspecialcharsbx($v).'</option>';
				}
				unset($v);
			}
			if($class !== '')
			{
				$class = ' '.$class;
			}

			$field['type'] = 'custom';
			$field['value'] = str_replace(
				array(
					'#NAME#',
					'#VALUE#',
					'#TEXT#',
					'#CLASS#',
					'#OPTIONS_HTML#'
				),
				array(
					$fieldID,
					htmlspecialcharsbx($value),
					htmlspecialcharsbx($text),
					htmlspecialcharsbx($class),
					$optionHtml
				),
				self::$TEMPLATES[$fieldTemplateName]
			);
		}
		elseif($fieldTemplateName === 'LHE')
		{
			$value = isset($fieldValues['VALUE']) ? $fieldValues['VALUE'] : '';
			if($value === '' && $ignoreIfEmpty)
			{
				// IGNORE EMPTY VALUES (INSERT STUB ONLY)
				$field['type'] = 'label';
				$field['value'] = '';
			}
			else
			{
				$editorID = isset($fieldValues['EDITOR_ID']) ? $fieldValues['EDITOR_ID'] : '';
				if($editorID ==='')
				{
					$editorID = uniqid('LHE_');
				}

				$editorJsName = isset($fieldValues['EDITOR_JS_NAME']) ? $fieldValues['EDITOR_JS_NAME'] : '';
				if($editorJsName ==='')
				{
					$editorJsName = $editorID;
				}

				if(!self::$IS_FILEMAN_INCLUDED)
				{
					CModule::IncludeModule('fileman');
					self::$IS_FILEMAN_INCLUDED = true;
				}

				ob_start();
				$editor = new CLightHTMLEditor;
				$editor->Show(
					array(
						'id' => $editorID,
						'height' => '150',
						'bUseFileDialogs' => false,
						'bFloatingToolbar' => false,
						'bArisingToolbar' => false,
						'bResizable' => false,
						'jsObjName' => $editorJsName,
						'bInitByJS' => false, // TODO: Lazy initialization
						'bSaveOnBlur' => true,
						'bHandleOnPaste'=> false,
						'toolbarConfig' => array(
							'Bold', 'Italic', 'Underline', 'Strike',
							'BackColor', 'ForeColor',
							'CreateLink', 'DeleteLink',
							'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
						)
					)
				);
				$lheHtml = ob_get_contents();
				ob_end_clean();

				$wrapperID = isset($fieldValues['WRAPPER_ID']) ? $fieldValues['WRAPPER_ID'] : '';
				if($wrapperID ==='')
				{
					$wrapperID = $editorID.'_WRAPPER';
				}

				$field['type'] = 'custom';
				$field['value'] = str_replace(
					array(
						'#TEXT#',
						'#VALUE#',
						'#NAME#',
						'#SETTINGS#',
						'#WRAPPER_ID#',
						'#HTML#'
					),
					array(
						$value,
						htmlspecialcharsbx($value),
						$fieldID,
						htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
						$wrapperID,
						$lheHtml
					),
					self::$TEMPLATES[$fieldTemplateName]
				);
			}
		}
		$formFields[] = !empty($fieldParams) ? array_merge($field, $fieldParams) : $field;
		return true;
	}

	public static function PrepareUserInfo($userID, &$userInfo, $options = array())
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			return false;
		}

		// Check if extranet user request intranet user info
		if(IsModuleInstalled('extranet')
			&& CModule::IncludeModule('extranet')
			&& $userID != CCrmSecurityHelper::GetCurrentUserID()
			&& !CExtranet::IsProfileViewableByID($userID))
		{
			return false;
		}

		$dbUser = CUser::GetList(
			'ID',
			'ASC',
			array('ID' => $userID)
		);

		$arUser = $dbUser->Fetch();
		if(!is_array($arUser))
		{
			return false;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$photoW = isset($options['PHOTO_WIDTH']) ? intval($options['PHOTO_WIDTH']) : 0;
		$photoH = isset($options['PHOTO_HEIGHT']) ? intval($options['PHOTO_HEIGHT']) : 0;

		$photoInfo = CFile::ResizeImageGet(
			$arUser['PERSONAL_PHOTO'],
			array(
				'width' => $photoW > 0 ? $photoW : 100,
				'height'=> $photoH > 0 ? $photoH : 100
			),
			BX_RESIZE_IMAGE_EXACT
		);

		$nameTemplate = isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : '';

		$userInfo['ID'] = $userID;
		$userInfo['FULL_NAME'] = CUser::FormatName(
			$nameTemplate !== '' ? $nameTemplate : CSite::GetNameFormat(false),
			$arUser,
			true,
			false
		);

		$urlTemplate = isset($options['USER_PROFILE_URL_TEMPLATE']) ? $options['USER_PROFILE_URL_TEMPLATE'] : '';
		$userInfo['USER_PROFILE'] = $urlTemplate !== ''
			? CComponentEngine::MakePathFromTemplate(
				$urlTemplate,
				array('user_id' => $userID)
			)
			: '';

		$userInfo['WORK_POSITION'] = isset($arUser['WORK_POSITION']) ? $arUser['WORK_POSITION'] : '';
		$userInfo['PERSONAL_PHOTO'] = isset($photoInfo['src']) ? $photoInfo['src'] : '';

		return true;
	}

	public static function PrepareUpdate($ownerTypeID, &$arFields, &$arFieldNames, &$arFieldValues)
	{
		$count = count($arFieldNames);
		$fieldMap = array();
		for($i = 0; $i < $count; $i++)
		{
			$fieldName = $arFieldNames[$i];
			$fieldValue = isset($arFieldValues[$i]) ? $arFieldValues[$i] : '';

			if($fieldName === 'COMMENTS' || $fieldName === 'USER_DESCRIPTION')
			{
				$arFields[$fieldName] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($fieldValue);
			}
			elseif(mb_strpos($fieldName, 'FM.') === 0)
			{
				// Processing of multifield name (FM.[TYPE].[VALUE_TYPE].[ID])
				$fmParts = explode('.', mb_substr($fieldName, 3));
				if(count($fmParts) === 3)
				{
					[$fmType, $fmValueType, $fmID] = $fmParts;

					$fmType = strval($fmType);
					$fmValueType = strval($fmValueType);
					$fmID = intval($fmID);

					if($fmType !== '' && $fmValueType !== '' && $fmID > 0)
					{
						if(!isset($arFields['FM']))
						{
							$arFields['FM'] = array();
						}

						if(!isset($arFields['FM'][$fmType]))
						{
							$arFields['FM'][$fmType] = array();
						}

						$arFields['FM'][$fmType][$fmID] = array('VALUE_TYPE' => $fmValueType, 'VALUE' => $fieldValue);
					}
				}
			}
			elseif(array_key_exists($fieldName, $arFields))
			{
				$arFields[$fieldName] = $fieldValue;
			}

			$fieldMap[$fieldName] = isset($arFields[$fieldName]) ? $arFields[$fieldName] : null;
		}

		//Cleanup not changed user fields
		foreach($arFields as $fieldName => $fieldValue)
		{
			if(mb_strpos($fieldName, 'UF_') === 0 && !isset($fieldMap[$fieldName]))
			{
				unset($arFields[$fieldName]);
			}
		}

		if($ownerTypeID === CCrmOwnerType::Lead
			|| $ownerTypeID === CCrmOwnerType::Deal
			|| $ownerTypeID === CCrmOwnerType::Contact
			|| $ownerTypeID === CCrmOwnerType::Company)
		{
			if(isset($arFields['CREATED_BY_ID']))
			{
				unset($arFields['CREATED_BY_ID']);
			}

			if(isset($arFields['DATE_CREATE']))
			{
				unset($arFields['DATE_CREATE']);
			}

			if(isset($arFields['MODIFY_BY_ID']))
			{
				unset($arFields['MODIFY_BY_ID']);
			}

			if(isset($arFields['DATE_MODIFY']))
			{
				unset($arFields['DATE_MODIFY']);
			}
		}
	}

	public static function RenderHtmlEditor(&$arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';

		$editorID = isset($arParams['EDITOR_ID']) ? $arParams['EDITOR_ID'] : '';
		if($editorID ==='')
		{
			$editorID = uniqid('LHE_');
		}

		$editorJsName = isset($arParams['EDITOR_JS_NAME']) ? $arParams['EDITOR_JS_NAME'] : '';
		if($editorJsName ==='')
		{
			$editorJsName = $editorID;
		}

		$toolbarConfig = isset($arParams['TOOLBAR_CONFIG']) ? $arParams['TOOLBAR_CONFIG'] : null;
		if(!is_array($toolbarConfig))
		{
			$toolbarConfig = array(
				'Bold', 'Italic', 'Underline', 'Strike',
				'BackColor', 'ForeColor',
				'CreateLink', 'DeleteLink',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
			);
		}

		if(!self::$IS_FILEMAN_INCLUDED)
		{
			CModule::IncludeModule('fileman');
			self::$IS_FILEMAN_INCLUDED = true;
		}

		ob_start();
		$editor = new CLightHTMLEditor;
		$editor->Show(
			array(
				'id' => $editorID,
				'height' => '150',
				'bUseFileDialogs' => false,
				'bFloatingToolbar' => false,
				'bArisingToolbar' => false,
				'bResizable' => false,
				'jsObjName' => $editorJsName,
				'bInitByJS' => false, // TODO: Lazy initialization
				'bSaveOnBlur' => true,
				'toolbarConfig' => $toolbarConfig
			)
		);
		$lheHtml = ob_get_contents();
		ob_end_clean();

		$wrapperID = isset($arParams['WRAPPER_ID']) ? $arParams['WRAPPER_ID'] : '';
		if($wrapperID ==='')
		{
			$wrapperID = $editorID.'_WRAPPER';
		}

		echo str_replace(
			array(
				'#TEXT#',
				'#VALUE#',
				'#NAME#',
				'#SETTINGS#',
				'#WRAPPER_ID#',
				'#HTML#'
			),
			array(
				$value,
				htmlspecialcharsbx($value),
				$fieldID,
				htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
				$wrapperID,
				$lheHtml
			),
			self::$TEMPLATES['LHE']
		);
	}

	public static function RenderTextArea(&$arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$value = isset($arParams['VALUE']) ? htmlspecialcharsbx($arParams['VALUE']) : '';
		//Convert NL, CR chars to BR tags
		$value = str_replace(array("\r", "\n"), '', nl2br($value));

		echo str_replace(
			array(
				'#VALUE#',
				'#NAME#'
			),
			array(
				$value,
				$fieldID
			),
			self::$TEMPLATES['TEXT_AREA']
		);
	}

	public static function PrepareListOptions(array $list, array $options = null)
	{
		if($options === null)
		{
			$options = array();
		}

		$excludeFromEdit = isset($options['EXCLUDE_FROM_EDIT']) && is_array($options['EXCLUDE_FROM_EDIT'])
			? $options['EXCLUDE_FROM_EDIT'] : null;

		$results = array();
		if(isset($options['NOT_SELECTED']) && is_string($options['NOT_SELECTED']))
		{
			$results[] = array(
				'NAME' => $options['NOT_SELECTED'],
				'VALUE' => isset($options['NOT_SELECTED_VALUE']) ? $options['NOT_SELECTED_VALUE'] : '0'
			);
		}

		foreach($list as $k => $v)
		{
			$item = array('NAME' => $v, 'VALUE' => $k);
			if($excludeFromEdit && in_array($k, $excludeFromEdit, true))
			{
				$item['IS_EDITABLE'] = false;
			}
			$results[] = $item;
		}
		return $results;
	}

	protected static function prepareStatusItemsConfig(string $statusType, array $fakeValues): array
	{
		$result = [
			'fakeValues' => $fakeValues,
			'systemValues' => [],
			'systemInitText' => [],
		];

		foreach (StatusTable::loadStatusesByEntityId($statusType) as $statusInfo)
		{
			if (isset($statusInfo['SYSTEM']) && $statusInfo['SYSTEM'] === 'Y')
			{
				$result['systemValues'][] = $statusInfo['STATUS_ID'];
				$result['systemInitText'][$statusInfo['STATUS_ID']] =
					is_string($statusInfo['NAME_INIT']) ? $statusInfo['NAME_INIT'] : ''
				;
			}
		}

		return $result;
	}

	public static function prepareInnerConfig(
		string $type,
		string $controller,
		string $statusType,
		array $fakeValues
	): array
	{
		static $allowMap = null;

		if ($allowMap === null)
		{
			$allowMap = array_fill_keys(
				CCrmStatus::getAllowedInnerConfigTypes(),
				CCrmStatus::CheckCreatePermission()
			);
		}

		$result = [];

		if (isset($allowMap[$statusType]) && $allowMap[$statusType])
		{
			$result = [
				'type' => $type,
				'controller' => $controller,
				'statusType' => $statusType,
				'itemsConfig' => self::prepareStatusItemsConfig($statusType, $fakeValues),
			];
		}

		return $result;
	}

	public static function prepareRequisitesPresetList($defaultPresetId): array
	{
		$result = [];
		$propertyTypeByCountry = [];
		$list = EntityPreset::getListForRequisiteEntityEditor();
		foreach ($list as $item)
		{
			$countryId = (int)$item['COUNTRY_ID'];
			$preset = [
				'NAME' => $item['NAME'],
				'VALUE' => $item['ID'],
				'IS_DEFAULT' => ($defaultPresetId == $item['ID'])
			];
			if (!isset($propertyTypeByCountry[$countryId]))
			{
				$propertyValue = ClientResolver::getClientResolverPropertyWithPlacements($countryId);
				$propertyTypeByCountry[$countryId] = $propertyValue;
			}
			$preset['CLIENT_RESOLVER_PROP'] = $propertyTypeByCountry[$countryId];
			$result[] = $preset;
		}

		return $result;
	}

	public static function signComponentParams(array $params, string $componentName): string
	{
		$signer = new \Bitrix\Main\Security\Sign\Signer;

		return $signer->sign(base64_encode(serialize($params)), 'signed_' . $componentName);
	}

	public static function unsignComponentParams(string $params, string $componentName): ?array
	{
		$signer = new \Bitrix\Main\Security\Sign\Signer;
		try
		{
			return (array)unserialize(
				base64_decode(
					$signer->unsign($params, 'signed_' . $componentName)
				),
				['allowed_classes' => false]
			);
		}
		catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
		{
			return null;
		}
	}
}
