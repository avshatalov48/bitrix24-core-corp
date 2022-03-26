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

	public static function findAllFieldOperations($fieldName, array $filter)
	{
		$results = array();
		foreach($filter as $k => $v)
		{
			$operationInfo = \CSqlUtil::GetFilterOperation($k);
			if($operationInfo['FIELD'] === $fieldName)
			{
				$operationInfo['CONDITION'] = $v;
				$results[] = $operationInfo;
			}
		}
		return $results;
	}

	public static function internalize(array $filterFields, array &$filter)
	{
		foreach($filterFields as $field)
		{
			$id = isset($field['id']) ? $field['id'] : '';
			$type = isset($field['type']) ? $field['type'] : '';
			if(
				$type !== 'custom_entity'
				&& $type !== 'dest_selector'
			)
			{
				continue;
			}

			if ($type === 'custom_entity')
			{
				$selector = isset($field['selector']) && is_array($field['selector']) ? $field['selector'] : array();
				$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
				if($selectorType !== 'crm_entity')
				{
					continue;
				}

				$data = isset($selector['DATA']) ? $selector['DATA'] : array();
				$entityTypeNames = isset($data['ENTITY_TYPE_NAMES']) && is_array($data['ENTITY_TYPE_NAMES'])
					? $data['ENTITY_TYPE_NAMES'] : array();

				$fieldID = isset($data['FIELD_ID']) ? $data['FIELD_ID'] : $id;
				$fieldAlias = isset($data['FIELD_ALIAS']) ? $data['FIELD_ALIAS'] : $fieldID;
				$isMultiple = isset($data['IS_MULTIPLE']) ? $data['IS_MULTIPLE'] : false;
			}
			elseif ($type === 'dest_selector')
			{
				$params = isset($field['params']) && is_array($field['params']) ? $field['params'] : array();
				if(
					!isset($params['convertJson'])
					|| $params['convertJson'] !== 'Y'
				)
				{
					continue;
				}

				$entityTypeNames = [];
				if (
					isset($params['enableCrmCompanies'])
					&& $params['enableCrmCompanies'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::CompanyName;
				}

				if (
					isset($params['enableCrmContacts'])
					&& $params['enableCrmContacts'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::ContactName;
				}

				if (
					isset($params['enableCrmLeads'])
					&& $params['enableCrmLeads'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::LeadName;
				}

				if (
					isset($params['enableCrmDeals'])
					&& $params['enableCrmDeals'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::DealName;
				}

				if (
					isset($params['enableCrmOrders'])
					&& $params['enableCrmOrders'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::OrderName;
				}

				if (
					isset($params['enableCrmQuotes'])
					&& $params['enableCrmQuotes'] == 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::QuoteName;
				}

				if (
					isset($params['enableCrmProducts'])
					&& $params['enableCrmProducts'] == 'Y'
				)
				{
					$entityTypeNames[] = 'PRODUCT';
				}

				if (
					isset($params['enableCrmSmartInvoices'])
					&& $params['enableCrmSmartInvoices'] === 'Y'
				)
				{
					$entityTypeNames[] = \CCrmOwnerType::SmartInvoiceName;
				}

				if(isset($params['enableCrmDynamics']) && is_array($params['enableCrmDynamics']))
				{
					foreach($params['enableCrmDynamics'] as $entityTypeId => $active)
					{
						if ($active === 'Y' && \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
						{
							$entityTypeNames[] = \CCrmOwnerType::ResolveName($entityTypeId);
						}
					}
				}

				$fieldID = $id;
				$fieldAlias = isset($field['alias']) ? $field['alias'] : $id;
				$isMultiple = (isset($params['multiple']) && $params['multiple'] == 'Y');
			}

			$entityTypeQty = count($entityTypeNames);
			if($entityTypeQty === 0)
			{
				continue;
			}

			if(!isset($filter[$fieldID]))
			{
				continue;
			}

			if (is_array($filter[$fieldID]))
			{
				$entityData = [];
				foreach($filter[$fieldID] as $item)
				{
					$parsedData = \CUtil::JsObjectToPhp($item);
					if(!(is_array($parsedData) && !empty($parsedData)))
					{
						continue;
					}
					$entityData = array_merge_recursive($entityData, $parsedData);
				}
			}
			else
			{
				$entityData = \CUtil::JsObjectToPhp($filter[$fieldID]);
			}
			unset($filter[$fieldID]);

			if(!(is_array($entityData) && !empty($entityData)))
			{
				continue;
			}

			if ($isMultiple)
			{
				$filter["={$fieldAlias}"] = [];
			}

			foreach($entityTypeNames as $entityTypeName)
			{
				if($entityTypeQty > 1)
				{
					$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID(
						\CCrmOwnerType::ResolveID($entityTypeName)
					);
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
					for($i = 0, $qty = count($entityData[$entityTypeName]); $i < $qty; $i++)
					{
						$filter["={$fieldAlias}"][] = "{$prefix}{$entityData[$entityTypeName][$i]}";
					}
				}
			}
		}
	}
}
