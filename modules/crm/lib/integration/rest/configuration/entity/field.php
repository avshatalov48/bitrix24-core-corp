<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\Configuration\Helper;
use CUserFieldEnum;
use CCrmOwnerType;
use CCrmFields;
use CLanguage;

Loc::loadMessages(__FILE__);

class Field
{
	const ENTITY_CODE = 'CRM_FIELDS';
	const OWNER_ENTITY_TYPE_FIELD_PREFIX = 'FIELD_';

	private static $regExpDealCategory = '/(^C)(\d+)(:)/';
	private static $clearSort = 99999;
	private static $context;
	private static $accessManifest = [
		'total',
		'crm'
	];

	/**
	 * @param $option
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public static function export($option)
	{
		if(!Helper::checkAccessManifest($option, static::$accessManifest))
		{
			return null;
		}

		$step = false;
		if(array_key_exists('STEP', $option))
		{
			$step = $option['STEP'];
		}

		$return = [
			'FILE_NAME' => '',
			'CONTENT' => [],
			'NEXT' => $step
		];
		global $USER_FIELD_MANAGER;
		$entityList = array_column(CCrmFields::GetEntityTypes(), 'ID');
		if($entityList[$step])
		{
			$return['FILE_NAME'] = $entityList[$step];
			$return['CONTENT'] = [
				'TYPE' => $entityList[$step],
				'ITEMS' => (new CCrmFields($USER_FIELD_MANAGER, $entityList[$step]))->GetFields(),
				'ATTRIBUTE' => []
			];

			if(mb_strpos($entityList[$step], '_') !== false)
			{
				list($tmp, $entity) = explode('_', $entityList[$step]);
			}
			else
			{
				$entity = $entityList[$step];
			}
			if($entity !== '')
			{
				$entityTypeId = CCrmOwnerType::ResolveID($entity);
				if($entityTypeId > 0)
				{
					$attributeData = [];
					$attributeData[] = [
						'CONFIG' => FieldAttributeManager::getEntityConfigurations(
							$entityTypeId,
							FieldAttributeManager::resolveEntityScope(
								$entityTypeId,
								0
							)
						),
						'ENTITY_TYPE_NAME' => $entity,
						'OPTION' => []
					];
					if($entityTypeId === CCrmOwnerType::Deal)
					{
						$dealCategory = DealCategory::getAll(false);
						foreach ($dealCategory as $category)
						{
							$option = [
								'CATEGORY_ID' => $category['ID']
							];
							$attributeData[] = [
								'CONFIG' => FieldAttributeManager::getEntityConfigurations(
									$entityTypeId,
									FieldAttributeManager::resolveEntityScope(
										$entityTypeId,
										0,
										$option
									)
								),
								'ENTITY_TYPE_NAME' => $entity,
								'OPTION' => $option
							];
						}
					}
					$return['CONTENT']['ATTRIBUTE'] = $attributeData;
				}
			}

			foreach ($return['CONTENT']['ITEMS'] as $key => $field)
			{
				if($field['USER_TYPE_ID'] == 'enumeration')
				{
					$return['CONTENT']['ITEMS'][$key]['LIST'] = [];
					$res = CUserFieldEnum::GetList([], ['USER_FIELD_ID' =>$field['ID']]);
					$i = 0;
					while($value = $res->fetch())
					{
						$i++;
						$return['CONTENT']['ITEMS'][$key]['LIST']['n'.$i] = $value;
					}
				}
			}
		}
		else
		{
			$return['NEXT'] = false;
		}

		return $return;
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
		if(!Helper::checkAccessManifest($option, static::$accessManifest))
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
		if(isset($entityTypeList[$step]))
		{
			$result['NEXT'] = $step;
			global $USER_FIELD_MANAGER;
			$entity = new CCrmFields($USER_FIELD_MANAGER, $entityTypeList[$step]);
			$fieldList = $entity->GetFields();

			foreach ($fieldList as $field)
			{
				if($clearFull)
				{
					$entity->DeleteField($field['ID']);
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
					$entity->UpdateField(
						$field['ID'],
						$saveData
					);
				}
			}

			if($clearFull)
			{
				if(mb_strpos($entityTypeList[$step], '_') !== false)
				{
					list($tmp, $entityCode) = explode('_', $entityTypeList[$step]);
				}
				else
				{
					$entityCode = $entityTypeList[$step];
				}
				$entityTypeId = CCrmOwnerType::ResolveID($entityCode);
				if($entityTypeId > 0)
				{
					FieldAttributeManager::deleteByOwnerType($entityTypeId);
				}
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$entityTypeList[$step]);
			}
		}

		return $result;
	}

	/**
	 * @param $import
	 *
	 * @return mixed
	 * @throws ArgumentException
	 */
	public static function import($import)
	{
		if(!Helper::checkAccessManifest($import, static::$accessManifest))
		{
			return null;
		}

		$result = [];
		if(!isset($import['CONTENT']['DATA']))
		{
			return $result;
		}
		$data = $import['CONTENT']['DATA'];
		if(!empty($data['ITEMS']))
		{
			$entityList = array_column(CCrmFields::GetEntityTypes(), 'ID');
			if(in_array($data['TYPE'], $entityList))
			{
				global $USER_FIELD_MANAGER;
				$entity = new CCrmFields($USER_FIELD_MANAGER, $data['TYPE']);
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
					$saveData = [
						'ENTITY_ID' => $data['TYPE'],
						'XML_ID' => static::$context.'_'.$field['FIELD_NAME'],
						'FIELD_NAME' => $field['FIELD_NAME'],
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
							$entity->UpdateField($oldFields[$saveData['FIELD_NAME']]['ID'], $saveData);
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
						$entity->AddField($saveData);
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
					$entityTypeId = CCrmOwnerType::ResolveID($attribute['ENTITY_TYPE_NAME']);
					if($entityTypeId > 0)
					{
						$dealCategoryId = 0;
						if(
							!empty($attribute['OPTION']['CATEGORY_ID'])
							&& !empty($import['RATIO'][Status::ENTITY_CODE][$attribute['OPTION']['CATEGORY_ID']])
						)
						{
							$dealCategoryId = $import['RATIO'][Status::ENTITY_CODE][$attribute['OPTION']['CATEGORY_ID']];
							$attribute['OPTION']['CATEGORY_ID'] = $dealCategoryId;
						}

						foreach ($attribute['CONFIG'] as $code => $configList)
						{
							foreach ($configList as $config)
							{
								if($entityTypeId === CCrmOwnerType::Deal && $dealCategoryId > 0)
								{
									$config = static::changeDealCategory($config, $dealCategoryId);
								}

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

		return $result;
	}

	/**
	 * @param array|string $data
	 * @param integer $newId new id deal category
	 *
	 * @return mixed
	 */
	private static function changeDealCategory($data, $newId)
	{
		// @todo rename to changeEntityCategory and support contacts, companies and smart processes categories

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
}