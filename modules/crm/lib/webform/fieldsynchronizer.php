<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

class FieldSynchronizer
{
	private const TYPE_ALIASES = [
		'NAME' => ['ORDER_TOPIC'],
		'TITLE' => ['NAME', 'ORDER_TOPIC',],
		'ORDER_TOPIC' => ['NAME', 'TITLE'],
	];

	private const HARDCODED_TYPE_ALIASES = [
		\CCrmOwnerType::LeadName => [
			\CCrmOwnerType::CompanyName => [
				'COMPANY_TITLE' => 'TITLE',
			],
		],
	];

	private const CLIENT_CONTACT_FIELDS_FIELDS = [
		'NAME',
		'COMPANY_TITLE',
		'HONORIFIC',
		'SECOND_NAME',
		'LAST_NAME',
		'BIRTHDATE',
		'POST',
		'ADDRESS',
		'PHOTO',
		'PHONE',
		'EMAIL',
		'IM',
		'WEB',
	];

	protected $isCreateMode = false;

	public function getSynchronizeFields($schemeId, $fieldNames)
	{
		$this->isCreateMode = false;

		$syncFieldCodes = array();
		$srcFieldMap = $this->getFieldMap($schemeId, $fieldNames);
		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			foreach($entityFields as $fieldName => $entityField)
			{
				$oldFieldCode = $entityField['OLD_FIELD_CODE'];
				$newFieldCode = $entityField['NEW_FIELD_CODE'];

				if($oldFieldCode == $newFieldCode)
				{
					continue;
				}

				if($newFieldCode)
				{
					continue;
				}

				$syncFieldCodes[] = $oldFieldCode;
			}
		}

		return $syncFieldCodes;
	}

	public function replaceOptionFields(Options $options)
	{
		$this->isCreateMode = true;

		$form = $options->getForm();
		$fields = $form->getFields();
		['DEPENDENCIES' => $dependencies, 'ENTITY_SCHEME' => $schemeId, 'INTEGRATION' => $integration] = $form->get();
		$integration = $integration ?? [];
		$srcFieldCodes = array_column($fields, 'CODE');
		$fields = array_combine(
			$srcFieldCodes,
			$fields
		);
		foreach ($integration as $key => $integrationOption)
		{
			$integration[$key]['FIELDS_MAPPING'] =  array_combine(
				array_column($integrationOption['FIELDS_MAPPING'],'CRM_FIELD_KEY'),
				$integrationOption['FIELDS_MAPPING']
			);
		}
		$srcFieldMap = $this->getFieldMap($schemeId, $srcFieldCodes);
		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			foreach($entityFields as $fieldName => $entityField)
			{
				$oldFieldCode = $entityField['OLD_FIELD_CODE'];
				$newFieldCode = $entityField['NEW_FIELD_CODE'];

				if($oldFieldCode == $newFieldCode)
				{
					continue;
				}

				// replace field
				$this->replaceField($fields, $entityField);

				// replace dependencies
				$this->replaceFieldDependencies($dependencies, $entityField);

				// replace field mapping
				$this->replaceIntegrationFields($integration,$entityField);

				unset($fields[$entityField['OLD_FIELD_CODE']]);
			}
		}

		$form->merge([
			'FIELDS' => array_values($fields),
			'DEPENDENCIES' => $dependencies,
			'INTEGRATION' => array_map(
				static function($integrationOption)
				{
					$integrationOption['FIELDS_MAPPING'] = array_values($integrationOption['FIELDS_MAPPING']);
					return $integrationOption;
				},$integration
			)
		]);

		Options\Fields::clearCache();
	}

	public function replacePostFields($schemeId, &$fields, &$dependencies, $invoicePayerEntityName = null)
	{
		$this->isCreateMode = true;

		$srcFieldCodes = array_keys($fields);
		$srcFieldMap = $this->getFieldMap($schemeId, $srcFieldCodes);

		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			foreach($entityFields as $fieldName => $entityField)
			{
				$oldFieldCode = $entityField['OLD_FIELD_CODE'];
				$newFieldCode = $entityField['NEW_FIELD_CODE'];

				if($oldFieldCode == $newFieldCode)
				{
					continue;
				}

				// replace field
				$this->replaceField($fields, $entityField);

				// replace dependencies
				$this->replaceFieldDependencies($dependencies, $entityField);

				unset($fields[$entityField['OLD_FIELD_CODE']]);
			}
		}
	}

	protected function getFieldMap($schemeId, $srcFieldCodes)
	{
		$entityTypeNames = Entity::getNames();
		$srcFieldMap = array();
		foreach($srcFieldCodes as $srcFieldCodeTmp)
		{
			foreach($entityTypeNames as $entityTypeName)
			{
				$prefix = "{$entityTypeName}_";
				if(mb_strpos($srcFieldCodeTmp, $prefix) !== 0)
				{
					continue;
				}

				$srcEntity = Entity::getMap($entityTypeName);
				$srcEntityFields = EntityFieldProvider::getFieldsInternal($entityTypeName, $srcEntity);

				$fieldName = mb_substr($srcFieldCodeTmp, mb_strlen($prefix));
				$srcFieldMap[$entityTypeName][$fieldName] = array(
					'FIELD_NAME' => $fieldName,
					'OLD_FIELD_CODE' => $srcFieldCodeTmp,
					'OLD_FIELD' => $this->findField($fieldName, $srcEntityFields),
					'NEW_FIELD_CODE' => '',
					'NEW_FIELD' => null
				);
				break;
			}
		}

		foreach($srcFieldMap as $entityTypeName => $entityFields)
		{
			$fieldNames = array();
			foreach($entityFields as $keyId => $entityField)
			{
				$fieldNames[] = $entityField['FIELD_NAME'];
			}
			if(count($fieldNames) == 0)
			{
				continue;
			}

			$specificLeadInvoiceSchema =
				Entity::ENUM_ENTITY_SCHEME_LEAD_INVOICE === (int)$schemeId
				&& $entityTypeName === \CCrmOwnerType::LeadName
			;

			$specificLeadWithContactSchema =
				Entity::ENUM_ENTITY_SCHEME_LEAD === (int)$schemeId
				&& $entityTypeName === \CCrmOwnerType::LeadName
				&& ($srcFieldMap[\CCrmOwnerType::ContactName] || $srcFieldMap[\CCrmOwnerType::CompanyName])
			;

			$synchronizedFields = $this->getReplacedSchemeFields(
				$entityTypeName,
				$schemeId,
				$fieldNames,
				$specificLeadInvoiceSchema || $specificLeadWithContactSchema
			);

			foreach($synchronizedFields as $dstEntityTypeName => $syncFields)
			{
				$dstEntity = Entity::getMap($dstEntityTypeName);
				$dstEntityFields = EntityFieldProvider::getFieldsInternal($dstEntityTypeName, $dstEntity);

				$prefix = $dstEntityTypeName . '_';
				foreach($syncFields as $syncFieldOld => $syncFieldNew)
				{
					if(!$syncFieldNew)
					{
						continue;
					}

					$srcFieldMap[$entityTypeName][$syncFieldOld]['NEW_FIELD_CODE'] = $prefix . $syncFieldNew;
					$srcFieldMap[$entityTypeName][$syncFieldOld]['NEW_FIELD'] = $this->findField($syncFieldNew, $dstEntityFields);
				}

				if ($this->isCreateMode && is_callable($dstEntity['CLEAR_FIELDS_CACHE_CALL'] ?? null))
				{
					$dstEntity['CLEAR_FIELDS_CACHE_CALL']();
				}
			}
		}

		return $srcFieldMap;
	}

	protected function getFieldItemMap($oldItems, $newItems)
	{
		$itemIdMap = array();
		foreach($oldItems as $oldItem)
		{
			foreach($newItems as $newItem)
			{
				if($oldItem['VALUE'] == $newItem['VALUE'])
				{
					$itemIdMap[$oldItem['ID']] = $newItem['ID'];
				}
			}
		}

		return $itemIdMap;
	}

	protected function replaceField(&$fields, $entityField)
	{
		$oldFieldCode = $entityField['OLD_FIELD_CODE'];
		$newFieldCode = $entityField['NEW_FIELD_CODE'];
		if(!$newFieldCode || isset($fields[$newFieldCode]))
		{
			return;
		}

		// replace field codes and items
		$field = $fields[$oldFieldCode];
		if (!empty($field['CODE']))
		{
			$field['CODE'] = $newFieldCode;
		}
		$fields[$newFieldCode] = $field;
		if(!$fields[$newFieldCode]['ITEMS'])
		{
			return;
		}
		if(!$entityField['OLD_FIELD']['items'])
		{
			return;
		}
		if(!$entityField['NEW_FIELD']['items'])
		{
			return;
		}

		$this->replaceFieldItems(
			$fields[$newFieldCode],
			$entityField['OLD_FIELD']['items'],
			$entityField['NEW_FIELD']['items']
		);
	}

	protected function replaceFieldItems(&$field, $oldItems, $newItems)
	{
		$itemIdMap = $this->getFieldItemMap($oldItems, $newItems);
		foreach($field['ITEMS'] as $keyId => $oldItem)
		{
			foreach($itemIdMap as $oldItemId => $newItemId)
			{
				if($oldItem['ID'] != $oldItemId)
				{
					continue;
				}

				$field['ITEMS'][$keyId]['ID'] = $newItemId;
			}
		}
	}

	protected function replaceIntegrationFields(&$integration, $entityField): void
	{
		['OLD_FIELD_CODE' => $oldFieldCode, 'NEW_FIELD_CODE' => $newFieldCode] = $entityField;

		if(!$newFieldCode)
		{
			return;
		}

		foreach ($integration as &$integrationOption)
		{
			if (!$integrationOption['FIELDS_MAPPING'])
			{
				continue;
			}

			$integrationMap = array_combine(
				array_column($integrationOption['FIELDS_MAPPING'],'CRM_FIELD_KEY'),
				$integrationOption['FIELDS_MAPPING']
			);

			if(!$map = $integrationMap[$oldFieldCode])
			{
				continue;
			}
			$map['CRM_FIELD_KEY'] = $newFieldCode;
			$integrationMap[$oldFieldCode] = $map;
			$integrationOption['FIELDS_MAPPING'] = array_values($integrationMap);
		}
	}

	protected function replaceFieldDependencies(&$dependencies, $entityField)
	{
		$oldFieldCode = $entityField['OLD_FIELD_CODE'];
		$newFieldCode = $entityField['NEW_FIELD_CODE'];

		if(!$newFieldCode)
		{
			return;
		}

		foreach($dependencies as $dependencyId => $dependency)
		{
			if($dependency['IF_FIELD_CODE'] == $oldFieldCode)
			{
				$dependency['IF_FIELD_CODE'] = $newFieldCode;
				if($dependency['IF_VALUE'] && $entityField['OLD_FIELD']['items'] && $entityField['NEW_FIELD']['items'])
				{
					$itemIdMap = $this->getFieldItemMap(
						$entityField['OLD_FIELD']['items'],
						$entityField['NEW_FIELD']['items']
					);
					if(isset($itemIdMap[$dependency['IF_VALUE']]))
					{
						$dependency['IF_VALUE'] = $itemIdMap[$dependency['IF_VALUE']];
					}
				}
			}

			if($dependency['DO_FIELD_CODE'] == $oldFieldCode)
			{
				$dependency['DO_FIELD_CODE'] = $newFieldCode;
			}

			$dependencies[$dependencyId] = $dependency;
		}
	}

	protected function findField($fieldName, $entityFields)
	{
		foreach($entityFields as $entityField)
		{
			if($entityField['entity_field_name'] == $fieldName)
			{
				return $entityField;
			}
		}

		return null;
	}

	protected function getXmlIdUserFieldBySystemField($entityTypeName, $fieldName): string
	{
		return "CRM_WEBFORM_{$entityTypeName}_{$fieldName}";
	}

	protected function getSystemFieldByUserField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$srcEntityTypeId = \CCrmOwnerType::ResolveID($srcEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $srcFieldName)
		);

		if($dstField = $resultDb->Fetch())
		{
			$prefix = 'CRM_WEBFORM_' . $dstEntityTypeName . '_';

			if(mb_substr($dstField['XML_ID'], 0, mb_strlen($prefix)) == $prefix)
			{
				return mb_substr($dstField['XML_ID'], mb_strlen($prefix));
			}
		}

		return null;
	}

	protected function getUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$xmlId = $this->getXmlIdUserFieldBySystemField($srcEntityTypeName, $srcFieldName);

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'XML_ID' => $xmlId)
		);
		if($dstField = $resultDb->Fetch())
		{
			return $dstField['FIELD_NAME'];
		}

		return null;
	}

	protected function createUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName)
	{
		$userFieldId = null;

		$srcEntity = Entity::getMap($srcEntityTypeName);
		$srcEntityFields = EntityFieldProvider::getFieldsInternal($srcEntityTypeName, $srcEntity);
		$srcField = $this->findField($srcFieldName, $srcEntityFields);

		if(!$srcField)
		{
			return null;
		}

		$dstFieldName = $this->getUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName);
		if($dstFieldName || !$this->isCreateMode)
		{
			return $dstFieldName;
		}


		$typeId = null;
		$userFieldSettings = null;
		switch($srcField['type'])
		{
			case 'checkbox':
			case 'radio':
			case 'list':
				$typeId = 'enumeration';
				if($srcField['type'] == 'checkbox' || $srcField['type'] == 'radio')
				{
					$userFieldSettings['DISPLAY'] = 'CHECKBOX';
				}
				else
				{
					$userFieldSettings['DISPLAY'] = 'LIST';
				}
				break;

			case 'file':
			case 'string':
			case 'date':
				$typeId = $srcField['type'];
				break;

			default:
				$typeId = 'string';
		}

		$xmlId = $this->getXmlIdUserFieldBySystemField($srcEntityTypeName, $srcFieldName);

		$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName);
		$entityId = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeId);
		$userTypeEntity = new \CUserTypeEntity();

		$resultDb = $userTypeEntity->GetList(
			array(),
			array('ENTITY_ID' => $entityId, 'XML_ID' => $xmlId)
		);
		if($dstField = $resultDb->Fetch())
		{
			return $dstField['FIELD_NAME'];
		}
		if(!$this->isCreateMode)
		{
			return null;
		}


		do
		{
			$dstFieldName = 'UF_CRM_'.mb_strtoupper(uniqid());
			$resultDb = $userTypeEntity->GetList(
				array(),
				array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $dstFieldName)
			);
		}
		while(is_array($resultDb->Fetch()));

		$dstField = array(
			'XML_ID' => $xmlId,
			'FIELD_NAME' => $dstFieldName,
			'ENTITY_ID' => $entityId,
			'USER_TYPE_ID' => $typeId,
			'SORT' =>100,
			'MULTIPLE' => $srcField['multiple'] ? 'Y' : 'N',
			'MANDATORY' => $srcField['required'] ? 'Y' : 'N',
			'SHOW_FILTER' => 'N',
			'SHOW_IN_LIST' => 'N',
			'EDIT_FORM_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
			'LIST_COLUMN_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
			'LIST_FILTER_LABEL' => array(LANGUAGE_ID => $srcField['caption']),
		);

		if($userFieldSettings)
		{
			$dstField['SETTINGS'] = $userFieldSettings;
		}

		$userFieldId = $userTypeEntity->Add($dstField);
		if($userFieldId && $typeId === 'enumeration' && $srcField['items'])
		{
			$enumList = array();
			$enumQty = 0;
			foreach($srcField['items'] as $item){
				$enum = array(
					'XML_ID' => $item['ID'],
					'VALUE' => $item['VALUE'],
					'SORT' => ($enumQty + 1) * 10,
				);
				$enumList["n{$enumQty}"] = $enum;
				$enumQty++;
			}

			$enumEntity = new \CUserFieldEnum();
			$enumEntity->SetEnumValues($userFieldId, $enumList);

			$GLOBALS['CACHE_MANAGER']->ClearByTag('crm_fields_list_' . $entityId);
		}

		return $dstFieldName;
	}

	/**
	 * Synchronize source type fields with destination type fields.
	 * Matches are searched by comparing field labels.
	 * If a source field is not found in the destination type, it will be created there.
	 *
	 * @param string $srcEntityTypeName Source Entity Type Name
	 * @param string $dstSchemeId Destination Scheme ID
	 * @param string[] $fieldNames Field names for synchronizing
	 * @return array<string,string[]> $synchronizedFieldMap
	 */
	protected function getReplacedSchemeFields(
		string $srcEntityTypeName,
		string $dstSchemeId,
		array $fieldNames,
		bool $specificSchema
	): array
	{
		$synchronizedFieldMap = [];
		$scheme = Entity::getSchemes($dstSchemeId);

		if (!$specificSchema && in_array($srcEntityTypeName, $scheme['ENTITIES'], true))
		{
			foreach($fieldNames as $fieldName)
			{
				$synchronizedFieldMap[$srcEntityTypeName][$fieldName] = $fieldName;
			}

			return $synchronizedFieldMap;
		}

		$mainEntity = $this->getSchemaMainEntity($scheme);
		$unresolvedFields = [];

		if ($specificSchema)
		{
			if (!empty($contactFields = array_intersect($this::CLIENT_CONTACT_FIELDS_FIELDS, $fieldNames)))
			{
				$unresolvedFields = $contactFields;
				$fieldNames = array_diff($fieldNames,$contactFields);
			}
		}

		$synchronizedFieldMap[$mainEntity] = $this->findSyncFields(
			$srcEntityTypeName,
			$mainEntity,
			$fieldNames
		);


		foreach ($synchronizedFieldMap[$mainEntity] as $name => $item)
		{
			if (!$item)
			{
				$unresolvedFields[] = $name;
			}
		}

		if (empty($unresolvedFields))
		{
			return $synchronizedFieldMap;
		}

		foreach($this->orderSchemaEntities($scheme) as $dstTypeName)
		{
			if (in_array($dstTypeName,[\CCrmOwnerType::InvoiceName, $mainEntity],true))
			{
				continue;
			}

			$synchronizedFieldMap[$dstTypeName] = $this->findSyncFields(
				$srcEntityTypeName,
				$dstTypeName,
				$unresolvedFields
			);

			foreach ($unresolvedFields as $key => $name)
			{
				if ($synchronizedFieldMap[$dstTypeName][$name])
				{
					unset($unresolvedFields[$key], $synchronizedFieldMap[$mainEntity][$name]);
					continue;
				}

				unset($synchronizedFieldMap[$dstTypeName][$name]);
			}

			if (empty($synchronizedFieldMap[$dstTypeName]))
			{
				unset($synchronizedFieldMap[$dstTypeName]);
			}
		}

		if ($this->isCreateMode)
		{
			$this->createSyncEntityField($synchronizedFieldMap,$srcEntityTypeName);
		}

		return $synchronizedFieldMap;
	}

	private function getSchemaMainEntity(array $schema) : string
	{
		if (!$mainEntity = \CCrmOwnerType::ResolveName($schema['MAIN_ENTITY']))
		{
			foreach ($this->orderSchemaEntities($schema) as $entityType)
			{
				if ($entityType !== \CCrmOwnerType::InvoiceName)
				{
					$mainEntity = $entityType;
					break;
				}
			}
		}

		return $mainEntity;
	}

	private function getUserFieldSyncMap(string $srcEntityTypeName, string $dstEntityTypeName) : array
	{
		static $prevSyncType;
		if ($prevSyncType !== $origin = "{$srcEntityTypeName}/{$dstEntityTypeName}")
		{
			if (!$srcEntityTypeId = \CCrmOwnerType::ResolveID($srcEntityTypeName))
			{
				return [];
			}

			if (!$dstEntityTypeId = \CCrmOwnerType::ResolveID($dstEntityTypeName))
			{
				return [];
			}
			UserFieldSynchronizer::getSynchronizationFields($srcEntityTypeId, $dstEntityTypeId, null, true);

			$prevSyncType = $origin;
		}

		return UserFieldSynchronizer::$existedFieldNameMap;

	}

	private function findSyncField(
		string $srcEntityTypeName,
		string $srcFieldName,
		string $dstEntityTypeName,
		array $dstEntityFieldNames
	) : ?string
	{
		if ($dstEntityTypeName === $srcEntityTypeName)
		{
			return $srcFieldName;
		}

		if ($this->isUserField($srcFieldName))
		{
			if ($dstFieldName = $this->getSystemFieldByUserField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName))
			{
				return $dstFieldName;
			}

			if ($dstFieldName = $this->getUserFieldSyncMap($srcEntityTypeName,$dstEntityTypeName)[$srcFieldName])
			{
				return $dstFieldName;
			}

			return null;
		}

		if (in_array($srcFieldName, $dstEntityFieldNames, true))
		{
			return $srcFieldName;
		}

		if ($aliases = $this::TYPE_ALIASES[$srcFieldName])
		{
			foreach ($aliases as $alias)
			{
				if (in_array($alias,$dstEntityFieldNames,true))
				{
					return $alias;
				}
			}
		}

		$hardcodeType = @$this::HARDCODED_TYPE_ALIASES[$srcEntityTypeName][$dstEntityTypeName][$srcFieldName];
		if ($hardcodeType && in_array($hardcodeType,$dstEntityFieldNames,true))
		{
			return $hardcodeType;
		}

		if ($dstFieldName = $this->getUserFieldBySystemField($dstEntityTypeName, $srcEntityTypeName, $srcFieldName))
		{
			return $dstFieldName;
		}

		return null;
	}

	private function findSyncFields(
		string $srcEntityTypeName,
		string $dstEntityTypeName,
		array $srcFieldNames
	) : array
	{
		$synchronizedFieldMap = [];
		$dstEntity = Entity::getMap($dstEntityTypeName);
		$dstEntityFields = EntityFieldProvider::getFieldsInternal($dstEntityTypeName, $dstEntity);

		$dstEntityFieldNames = array_column($dstEntityFields, 'entity_field_name');

		foreach($srcFieldNames as $fieldName)
		{
			$synchronizedFieldMap[$fieldName] = $this->findSyncField(
				$srcEntityTypeName,
				$fieldName,
				$dstEntityTypeName,
				$dstEntityFieldNames
			);
		}

		return $synchronizedFieldMap;
	}

	private function isUserField(string $field) : bool
	{
		return mb_strpos($field, 'UF_') === 0;
	}

	private function createSyncEntityField(
		array& $synchronizedFieldMap,
		string $srcEntityTypeName
	) : void
	{
		if (!$srcEntityTypeId = \CCrmOwnerType::ResolveID($srcEntityTypeName))
		{
			return;
		}

		foreach ($synchronizedFieldMap as $entity => &$fields)
		{
			$unresolvedUserFields = [];

			if (!$dstEntityTypeId = \CCrmOwnerType::ResolveID($entity))
			{
				continue;
			}

			foreach ($fields as $from => $to)
			{
				if ($to)
				{
					continue;
				}

				if (!$this->isUserField($from))
				{
					$synchronizedFieldMap[$entity][$from] = $this->createUserFieldBySystemField(
						$entity,
						$srcEntityTypeName,
						$from
					);
				}
				else
				{
					$unresolvedUserFields[] = $from;
				}
			}

			$newUserFields = $this->syncUserFields($srcEntityTypeId,$dstEntityTypeId,$unresolvedUserFields);
			foreach ($newUserFields as $from => $to)
			{
				$synchronizedFieldMap[$entity][$from] = $to;
			}
		}

	}

	private function orderSchemaEntities($schema) : \Generator
	{
		$priorityQueue = new \SplPriorityQueue();

		foreach ($schema['ENTITIES'] as $entity)
		{
			if (in_array($entity, [\CCrmOwnerType::ContactName, \CCrmOwnerType::CompanyName], true))
			{
				$priority = $entity === \CCrmOwnerType::ContactName? 200 : 100;
			}
			$priority = $priority ?? 300;
			$priorityQueue->insert($entity, $priority);
		}

		for (;$priorityQueue->valid();$priorityQueue->next())
		{
			yield $priorityQueue->current();
		}

	}

	private function syncUserFields(
		int $srcEntityTypeId,
		int $dstEntityTypeId,
		array $userFieldNames
	): array
	{
		return UserFieldSynchronizer::synchronize(
			$srcEntityTypeId,
			$dstEntityTypeId,
			Context::getCurrent()->getLanguage(),
			['FIELD_NAME' => $userFieldNames]
		);
	}
}
