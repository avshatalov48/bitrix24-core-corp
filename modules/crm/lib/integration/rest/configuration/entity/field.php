<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Integration\Rest\Configuration\Helper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\UserFieldHistory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\Configuration\Manifest;
use CUserFieldEnum;
use CCrmOwnerType;
use CCrmFields;
use CLanguage;
use CUserTypeEntity;

Loc::loadMessages(__FILE__);

class Field
{
	const ENTITY_CODE = 'CRM_FIELDS';
	const OWNER_ENTITY_TYPE_FIELD_PREFIX = 'FIELD_';

	private static $regExpDealCategory = '/(^C)(\\d+)(:)/';
	private static $regExpDynamicCategory = '/^(DT\\d+_\\d+)(:)/u';
	private static $clearSort = 99999;
	private static $context = '';
	private static $accessManifest = [
		'total',
		'crm',
		'automated_solution',
	];

	private static bool $isDynamicTypeChecked = false;
	private static bool $isDynamicType = false;
	private static int $dynamicEntityTypeId = 0;
	private static int $oldDynamicTypeId = 0;

	protected static function addDynamicUserField(string $ufEntityId, array $fields): bool
	{
		global $USER_FIELD_MANAGER;
		$entity = new CCrmFields($USER_FIELD_MANAGER, $ufEntityId);

		return $entity->AddField($fields);
	}

	protected static function updateDynamicUserField(string $ufEntityId, int $id, array $fields): bool
	{
		$obUserField  = new CUserTypeEntity();
		$res = $obUserField->Update($id, $fields);
		if($res)
		{
			UserFieldHistory::processModification(CCrmOwnerType::ResolveIDByUFEntityID($ufEntityId), $id);
		}

		if ($res && $fields['USER_TYPE_ID'] == 'enumeration' && is_array($fields['LIST']))
		{
			$obEnum = new CUserFieldEnum();
			$res = $obEnum->SetEnumValues($id, $fields['LIST']);
		}

		return $res;
	}

	protected static function deleteDynamicUserField(string $ufEntityId, int $id): bool
	{
		$obUserField = new CUserTypeEntity();
		@set_time_limit(0);
		if ($obUserField->Delete($id))
		{
			return false;
		}

		UserFieldHistory::processRemoval(CCrmOwnerType::ResolveIDByUFEntityID($ufEntityId), $id);

		return true;
	}

	protected static function getDynamicUserFields(string $ufEntityId): array
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER->GetUserFields($ufEntityId, 0, LANGUAGE_ID);
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public static function export($option)
	{
		global $USER_FIELD_MANAGER;

		if(!Manifest::isEntityAvailable('', $option, static::$accessManifest))
		{
			return null;
		}

		$step = false;
		if(array_key_exists('STEP', $option))
		{
			$step = $option['STEP'];
		}

		$result = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => $step
		];

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeExportParams($option))
		{
			return null;
		}

		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeParams($option);
		$entityList =
			$automatedSolutionModeParams['isAutomatedSolutionMode']
				? []
				: array_column(CCrmFields::GetEntityTypes(), 'ID')
		;


		// Dynamic types
		$isDynamicType = false;
		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load(
			[
				'isLoadCategories' => true,
				'isLoadStages' => false,
			]
		);
		$dynamicTypeFields = [];
		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			if (
				!$helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId($type->getEntityTypeId() ?? 0)
					)
				)
			)
			{
				continue;
			}

			$ufEntityId = ServiceLocator::getInstance()
				->get('crm.type.factory')
				->getUserFieldEntityId($type->getId())
			;
			if (count($entityList) === $step)
			{
				$isDynamicType = true;
				$dynamicTypeFields[$ufEntityId] = static::getDynamicUserFields($ufEntityId);
			}
			$entityList[] = $ufEntityId;
		}

		if($entityList[$step])
		{
			$ufEntityId = $entityList[$step];

			// Entity type name and identifier
			$entityTypeId = 0;
			$separatorPosition = mb_strpos($entityList[$step], '_');
			if(!$isDynamicType && $separatorPosition !== false)
			{
				$entityTypeName = mb_substr($ufEntityId, $separatorPosition + 1);
			}
			else
			{
				$entityTypeName = $ufEntityId;
			}
			if ($entityTypeName !== '')
			{
				if ($isDynamicType)
				{
					$entityTypeId = CCrmOwnerType::ResolveIDByUFEntityID($ufEntityId);
					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
				}
				else
				{
					$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
				}
			}

			$result['FILE_NAME'] = $ufEntityId;
			$result['CONTENT'] = [
				'TYPE' => $ufEntityId,
				'ENTITY_TYPE_NAME' => $entityTypeName,
				'ITEMS' => $dynamicTypeFields[$ufEntityId]
					?? (new CCrmFields($USER_FIELD_MANAGER, $ufEntityId))->GetFields()
				,
				'ATTRIBUTE' => []
			];

			if($entityTypeId > 0)
			{
				$attributeData = [];
				if (!$isDynamicType)
				{
					$attributeItem = [
						'CONFIG' => FieldAttributeManager::getEntityConfigurations(
							$entityTypeId,
							FieldAttributeManager::resolveEntityScope(
								$entityTypeId,
								0
							)
						),
						'ENTITY_TYPE_NAME' => $entityTypeName,
						'OPTION' => []
					];
					if (!empty($attributeItem['CONFIG']))
					{
						$attributeData[] = $attributeItem;
					}
				}
				if($isDynamicType || $entityTypeId === CCrmOwnerType::Deal)
				{
					$categories =
						$isDynamicType
							? $dynamicTypesMap->getCategories($entityTypeId)
							: DealCategory::getAll()
					;
					if (is_array($categories))
					{
						foreach ($categories as $category)
						{
							$option = [
								'CATEGORY_ID' => $category['ID']
							];
							$attributeItem = [
								'CONFIG' => FieldAttributeManager::getEntityConfigurations(
									$entityTypeId,
									FieldAttributeManager::resolveEntityScope(
										$entityTypeId,
										0,
										$option
									)
								),
								'ENTITY_TYPE_NAME' => $entityTypeName,
								'OPTION' => $option
							];
							if (!empty($attributeItem['CONFIG']))
							{
								$attributeData[] = $attributeItem;
							}
						}
					}
				}
				$result['CONTENT']['ATTRIBUTE'] = $attributeData;
			}

			foreach ($result['CONTENT']['ITEMS'] as $key => $field)
			{
				if($field['USER_TYPE_ID'] == 'enumeration')
				{
					$result['CONTENT']['ITEMS'][$key]['LIST'] = [];
					$res = CUserFieldEnum::GetList([], ['USER_FIELD_ID' =>$field['ID']]);
					$i = 0;
					while($value = $res->fetch())
					{
						$i++;
						$result['CONTENT']['ITEMS'][$key]['LIST']['n'.$i] = $value;
					}
				}
			}
		}
		else
		{
			$result['NEXT'] = false;
		}

		return $result;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function clear($option)
	{
		if(!Manifest::isEntityAvailable('', $option, static::$accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeClearParams($option))
		{
			return null;
		}

		$result = [
			'NEXT' => false,
			'OWNER_DELETE' => []
		];
		$step = $option['STEP'];
		$clearFull = $option['CLEAR_FULL'];
		$prefix = $option['PREFIX_NAME'];
		$pattern = '/^\('.$prefix.'\)/';

		$entityTypeList = array_column(CCrmFields::GetEntityTypes(), 'ID');

		// Dynamic types
		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($option);
		$isDynamicType = false;
		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load(
			[
				'isLoadCategories' => true,
				'isLoadStages' => false,
			]
		);
		$dynamicTypeFields = [];
		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			if (
				!$helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId($type->getEntityTypeId() ?? 0)
					)
				)
			)
			{
				continue;
			}
			$ufEntityId = ServiceLocator::getInstance()
				->get('crm.type.factory')
				->getUserFieldEntityId($type->getId())
			;
			if (count($entityTypeList) === $step)
			{
				$isDynamicType = true;
				$dynamicTypeFields = static::getDynamicUserFields($ufEntityId);
			}
			$entityTypeList[] = $ufEntityId;
		}

		if(isset($entityTypeList[$step]))
		{
			$ufEntityId = $entityTypeList[$step];
			$result['NEXT'] = $step;
			global $USER_FIELD_MANAGER;

			if ($isDynamicType)
			{
				$fieldList = $dynamicTypeFields;
			}
			else
			{
				$entity = new CCrmFields($USER_FIELD_MANAGER, $ufEntityId);
				$fieldList = $entity->GetFields();
			}

			foreach ($fieldList as $field)
			{
				if($clearFull)
				{
					$isDynamicType
						? static::deleteDynamicUserField($ufEntityId, $field['ID'])
						: $entity->DeleteField($field['ID'])
					;
					$result['OWNER_DELETE'][] = [
						'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_FIELD_PREFIX.$field['ENTITY_ID'],
						'ENTITY' => $field['FIELD_NAME']
					];
				}
				else
				{
					$saveData = [
						'MANDATORY' => 'N',
						'SORT' => static::$clearSort + $field['SORT']
					];
					if ($prefix != '')
					{
						if($field['EDIT_FORM_LABEL'] != '' && preg_match($pattern, $field['EDIT_FORM_LABEL']) === 0)
						{
							$saveData['EDIT_FORM_LABEL'] = "($prefix) ".$field['EDIT_FORM_LABEL'];
						}
						if($field['LIST_COLUMN_LABEL'] != '' && preg_match($pattern, $field['LIST_COLUMN_LABEL']) === 0)
						{
							$saveData['LIST_COLUMN_LABEL'] = "($prefix) ".$field['LIST_COLUMN_LABEL'];
						}
						if($field['LIST_FILTER_LABEL'] != '' && preg_match($pattern, $field['LIST_FILTER_LABEL']) === 0)
						{
							$saveData['LIST_FILTER_LABEL'] = "($prefix) ".$field['LIST_FILTER_LABEL'];
						}
					}
					$isDynamicType
						? static::updateDynamicUserField($ufEntityId, (int)$field['ID'], $saveData)
						: $entity->UpdateField($field['ID'], $saveData)
					;
				}
			}

			if($clearFull)
			{
				if(mb_strpos($ufEntityId, '_') !== false)
				{
					[$tmp, $entityCode] = explode('_', $ufEntityId);
				}
				else
				{
					$entityCode = $ufEntityId;
				}
				$entityTypeId = CCrmOwnerType::ResolveID($entityCode);
				if($entityTypeId > 0)
				{
					FieldAttributeManager::deleteByOwnerType($entityTypeId);
				}
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$ufEntityId);
			}
		}

		return $result;
	}

	protected static function checkDynamicType(array $import, bool $refresh = false): void
	{
		if (!static::$isDynamicTypeChecked || $refresh)
		{
			if ($refresh)
			{
				static::$oldDynamicTypeId = 0;
				static::$dynamicEntityTypeId = 0;
				static::$isDynamicType = false;
			}

			$data = $import['CONTENT']['DATA'];

			$matches = [[], []];
			if (
				isset($data['TYPE'])
				&& is_string($data['TYPE'])
				&& preg_match('/^CRM_(\d+)$/u', $data['TYPE'], $matches[0])
				&& isset($import['RATIO']['CRM_DYNAMIC_TYPES']["DT{$matches[0][1]}"])
				&& isset($data['ENTITY_TYPE_NAME'])
				&& is_string($data['ENTITY_TYPE_NAME'])
				&& preg_match('/^DYNAMIC_(\d+)$/u', $data['ENTITY_TYPE_NAME'], $matches[1])
				&& isset($import['RATIO']['CRM_DYNAMIC_TYPES']["DYNAMIC_{$matches[1][1]}"])
			)
			{
				$entityTypeId = (int)$import['RATIO']['CRM_DYNAMIC_TYPES']["DYNAMIC_{$matches[1][1]}"];
				if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
				{
					static::$oldDynamicTypeId = (int)$matches[0][1];
					static::$dynamicEntityTypeId = $entityTypeId;
					static::$isDynamicType = true;
				}
			}

			static::$isDynamicTypeChecked = true;
		}
	}

	protected static function isDynamicType(array $import): bool
	{
		if (!static::$isDynamicTypeChecked)
		{
			static::checkDynamicType($import);
		}

		return static::$isDynamicType;
	}

	protected static function getDynamicEntityTypeId(array $import): int
	{
		if (!static::$isDynamicTypeChecked)
		{
			static::checkDynamicType($import);
		}

		return static::$dynamicEntityTypeId;
	}

	protected static function getOldDynamicTypeId(array $import): int
	{
		if (!static::$isDynamicTypeChecked)
		{
			static::checkDynamicType($import);
		}

		return static::$oldDynamicTypeId;
	}

	protected static function replaceFieldNamePrefix(
		string $fieldName,
		string $oldUfEntityId,
		string $newUfEntityId
	): string
	{
		$fieldPrefix = "UF_{$oldUfEntityId}_";
		if ($fieldPrefix === mb_substr($fieldName, 0, mb_strlen($fieldPrefix)))
		{
			$fieldName = "UF_{$newUfEntityId}_" . mb_substr($fieldName, mb_strlen($fieldPrefix));
		}

		return $fieldName;
	}

	/**
	 * @param $import
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public static function import($import)
	{
		if(!Manifest::isEntityAvailable('', $import, static::$accessManifest))
		{
			return null;
		}

		$helper = new Helper();
		if (!$helper->checkAutomatedSolutionModeImportParams($import))
		{
			return null;
		}

		$result = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}

		$data = $import['CONTENT']['DATA'];
		$ufEntityId = $oldUfEntityId = $data['TYPE'];
		$isDynamicType = static::isDynamicType($import);
		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($import);
		if ($isDynamicType)
		{
			$dynamicEntityTypeId = static::getDynamicEntityTypeId($import);
			$dynamicType = Container::getInstance()->getTypeByEntityTypeId($dynamicEntityTypeId);
			if ($dynamicType && $helper->checkDynamicTypeImportConditions($dynamicEntityTypeId, $import))
			{
				$ufEntityId = 'CRM_' . $dynamicType->getId();
			}
			else
			{
				$isDynamicType = false;
			}
		}

		if (!$isDynamicType && $automatedSolutionModeParams['isAutomatedSolutionMode'])
		{
			return $result;
		}

		if(!empty($data['ITEMS']))
		{
			$entityList = array_column(CCrmFields::GetEntityTypes(), 'ID');
			if($isDynamicType || in_array($ufEntityId, $entityList))
			{
				global $USER_FIELD_MANAGER;
				$entity = new CCrmFields($USER_FIELD_MANAGER, $ufEntityId);
				$langList = array();
				$resLang = CLanguage::GetList();
				while($lang = $resLang->Fetch())
				{
					$langList[] = $lang['LID'];
				}
				$result['OWNER'] = [];
				$oldFields = $entity->GetFields();
				foreach ($data['ITEMS'] as $field)
				{
					$fieldName = $field['FIELD_NAME'];
					if ($isDynamicType)
					{
						// Replace field name prefix with new dynamic type identifier
						$fieldName = static::replaceFieldNamePrefix($fieldName, $oldUfEntityId, $ufEntityId);
					}

					$saveData = [
						'ENTITY_ID' => $ufEntityId,
						'XML_ID' => static::$context . '_' . $fieldName,
						'FIELD_NAME' => $fieldName,
						'SORT' => intVal($field['SORT']),
						'MULTIPLE' => $field['MULTIPLE'],
						'MANDATORY' => $field['MANDATORY'],
						'SHOW_FILTER' => $field['SHOW_FILTER'],
						'SHOW_IN_LIST' => $field['SHOW_IN_LIST'],
						'EDIT_IN_LIST' => $field['EDIT_IN_LIST'],
						'IS_SEARCHABLE' => $field['IS_SEARCHABLE'],
						'SETTINGS' => $field['SETTINGS'],
						'USER_TYPE_ID' => $field['USER_TYPE']["USER_TYPE_ID"]
					];

					if(is_array($field['LIST']))
					{
						foreach ($field['LIST'] as $key => $value)
						{
							if(isset($value['ID']))
							{
								unset($value['ID']);
							}
							$saveData['LIST'][$key] = $value;
						}
					}
					$arLabels = ["EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE"];
					foreach($arLabels as $label)
					{
						foreach ($langList as $lang)
						{
							$saveData[$label][$lang] = $field[$label];
						}
					}

					if(!empty($oldFields[$saveData['FIELD_NAME']]))
					{
						if(
							$oldFields[$saveData['FIELD_NAME']]['XML_ID'] == $saveData['XML_ID']
							&&
							$oldFields[$saveData['FIELD_NAME']]['USER_TYPE']['USER_TYPE_ID'] == $saveData['USER_TYPE_ID']
						)
						{
							$fieldId = (int)$oldFields[$saveData['FIELD_NAME']]['ID'];
							$isDynamicType
								? static::updateDynamicUserField($ufEntityId, $fieldId, $saveData)
								: $entity->UpdateField($fieldId, $saveData)
							;
						}
						else
						{
							$result['ERROR_MESSAGES'] = Loc::getMessage(
								"CRM_ERROR_CONFIGURATION_IMPORT_CONFLICT_FIELDS",
								[
									'#CODE#' => $saveData['FIELD_NAME']
								]
							);
						}
					}
					else
					{
						$isDynamicType
							? static::addDynamicUserField($ufEntityId, $saveData)
							: $entity->AddField($saveData)
						;
						$result['OWNER'][] = [
							'ENTITY_TYPE' => self::OWNER_ENTITY_TYPE_FIELD_PREFIX.$saveData['ENTITY_ID'],
							'ENTITY' => $saveData['FIELD_NAME']
						];
					}
				}
			}
		}

		if(is_array($data['ATTRIBUTE']))
		{
			foreach ($data['ATTRIBUTE'] as $attribute)
			{
				if(is_array($attribute['CONFIG']))
				{
					$oldEntityTypeId = CCrmOwnerType::ResolveID($attribute['ENTITY_TYPE_NAME']);
					$entityTypeId = $isDynamicType ? static::getDynamicEntityTypeId($import) : $oldEntityTypeId;
					if($oldEntityTypeId > 0)
					{
						$oldCategoryId = (int)($attribute['OPTION']['CATEGORY_ID'] ?? 0);
						$categoryId = 0;
						if ($isDynamicType)
						{
							if ($oldCategoryId > 0)
							{
								$categoryKey = "DT{$oldEntityTypeId}_$oldCategoryId";
								if (
									isset($import['RATIO'][Status::ENTITY_CODE][$categoryKey])
									&& !empty($import['RATIO'][Status::ENTITY_CODE][$categoryKey])
								)
								{
									$categoryId = $import['RATIO'][Status::ENTITY_CODE][$categoryKey];
									$attribute['OPTION']['CATEGORY_ID'] = $categoryId;
								}
							}
						}
						elseif (
							$oldCategoryId > 0
							&& isset($import['RATIO'][Status::ENTITY_CODE][$oldCategoryId])
							&& !empty($import['RATIO'][Status::ENTITY_CODE][$oldCategoryId])
						)
						{
							$categoryId = $import['RATIO'][Status::ENTITY_CODE][$oldCategoryId];
							$attribute['OPTION']['CATEGORY_ID'] = $categoryId;
						}

						foreach ($attribute['CONFIG'] as $code => $configList)
						{
							foreach ($configList as $config)
							{
								if($oldEntityTypeId === CCrmOwnerType::Deal && $categoryId > 0)
								{
									$config = static::changeDealCategory($config, $categoryId);
								}

								if ($isDynamicType)
								{
									if($categoryId > 0)
									{
										$categoryCode = "DT{$entityTypeId}_$categoryId";
										$config = static::changeDynamicCategory($config, $categoryCode);
									}

									// Replace field name prefix with new dynamic type identifier
									$code = static::replaceFieldNamePrefix($code, $oldUfEntityId, $ufEntityId);
								}

								if (!$isDynamicType || $categoryId > 0)
								{
									FieldAttributeManager::saveEntityConfiguration(
										$config,
										$code,
										$entityTypeId,
										FieldAttributeManager::resolveEntityScope(
											$entityTypeId,
											0,
											is_array($attribute['OPTION']) ? $attribute['OPTION'] : null
										)
									);
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}

	private static function changeDynamicCategoryCode(
		string $pattern,
		string|array $data,
		string $newCategoryCode
	): string|array
	{
		if (is_string($data))
		{
			$data = preg_replace($pattern, $newCategoryCode . '${2}', $data);
		}
		elseif (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				$newKey = static::changeDynamicCategoryCode($pattern, $key, $newCategoryCode);
				if($newKey != $key)
				{
					unset($data[$key]);
				}

				$data[$newKey] = static::changeDynamicCategoryCode($pattern, $value, $newCategoryCode);
			}
		}

		return $data;
	}

	/**
	 * @param array|string $data
	 * @param integer $newId new id deal category
	 *
	 * @return mixed
	 */
	private static function changeDealCategory($data, $newId)
	{
		if (is_string($data))
		{
			$data =	preg_replace(static::$regExpDealCategory, '${1}'.$newId.'${3}', $data);
		}
		elseif (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				$newKey = static::changeDealCategory($key, $newId);
				if($newKey != $key)
				{
					unset($data[$key]);
				}

				$data[$newKey] = static::changeDealCategory($value, $newId);
			}
		}

		return $data;
	}

	private static function changeDynamicCategory(string|array $data, string $newCategoryCode): string|array
	{
		return static::changeDynamicCategoryCode(static::$regExpDynamicCategory, $data, $newCategoryCode);
	}
}