<?php
namespace Bitrix\Crm\UI\Filter;
class EntityHandler
{
	public static function findFieldOperation($fieldName, array $filter)
	{
		foreach($filter as $k => $v)
		{
			$operationInfo = \CSqlUtil::GetFilterOperation($k);
			if($operationInfo['FIELD'] === $fieldName)
			{
				$operationInfo['CONDITION'] = $v;
				return $operationInfo;
			}
		}
		return null;
	}

	public static function internalize(array $filterFields, array &$filter)
	{
		foreach($filterFields as $field)
		{
			$id = isset($field['id']) ? $field['id'] : '';
			$type = isset($field['type']) ? $field['type'] : '';
			if($type !== 'custom_entity')
			{
				continue;
			}

			$selector = isset($field['selector']) && is_array($field['selector']) ? $field['selector'] : array();
			$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
			if($selectorType !== 'crm_entity')
			{
				continue;
			}

			$data = isset($selector['DATA']) ? $selector['DATA'] : array();
			$entityTypeNames = isset($data['ENTITY_TYPE_NAMES']) && is_array($data['ENTITY_TYPE_NAMES'])
				? $data['ENTITY_TYPE_NAMES'] : array();

			$entityTypeQty = count($entityTypeNames);
			if($entityTypeQty === 0)
			{
				continue;
			}

			$fieldID = isset($data['FIELD_ID']) ? $data['FIELD_ID'] : $id;
			$fieldAlias = isset($data['FIELD_ALIAS']) ? $data['FIELD_ALIAS'] : $fieldID;
			$isMultiple = isset($data['IS_MULTIPLE']) ? $data['IS_MULTIPLE'] : false;

			if(!isset($filter[$fieldID]))
			{
				continue;
			}

			$entityData = \CUtil::JsObjectToPhp($filter[$fieldID]);
			unset($filter[$fieldID]);

			if(!(is_array($entityData) && !empty($entityData)))
			{
				continue;
			}

			foreach($entityTypeNames as $entityTypeName)
			{
				if($entityTypeQty > 1)
				{
					$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID(\CCrmOwnerType::ResolveID($entityTypeName));
					$prefix = "{$entityTypeAbbr}_";
				}
				else
				{
					$prefix = '';
				}

				if(!(isset($entityData[$entityTypeName])
					&& is_array($entityData[$entityTypeName])
					&& !empty($entityData[$entityTypeName]))
				)
				{
					continue;
				}

				if(!$isMultiple)
				{
					$filter["={$fieldAlias}"] = "{$prefix}{$entityData[$entityTypeName][0]}";
				}
				else
				{
					$effectiveValues = array();
					for($i = 0, $qty = count($entityData[$entityTypeName]); $i < $qty; $i++)
					{
						$effectiveValues[] = "{$prefix}{$entityData[$entityTypeName][$i]}";
					}
					$filter["={$fieldAlias}"] = $effectiveValues;
				}
			}
		}
	}
}