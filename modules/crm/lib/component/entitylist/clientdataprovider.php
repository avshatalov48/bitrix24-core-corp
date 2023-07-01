<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main\Localization\Loc;

abstract class ClientDataProvider
{
	protected $clientEntityTypeId;
	protected $fieldHelper;
	protected $ufManager;
	protected $multifieldsManager;
	protected $isExportMode = false;
	protected $gridId = '';

	protected $realSelectFields = [];
	protected $addRawIdToSelect = false;

	protected const ID_FIELD = 'ID';
	protected const RAW_ID_FIELD = 'RAW_ID';

	private const CACHE_TIME = 86400;
	private const CACHE_DIR = '/crm/entity_list/client_data_provider/';

	public function __construct(int $clientEntityTypeId)
	{
		$this->clientEntityTypeId = $clientEntityTypeId;
		$this->fieldHelper = new ClientFieldHelper($clientEntityTypeId);

		$entity = $this->fieldHelper->getEntityClass();
		$this->ufManager = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entity::GetUserFieldEntityID());
		$this->ufManager->setFieldNamePrefix($this->fieldHelper->getFieldPrefix());

		$this->multifieldsManager = new \CCrmFieldMulti();
	}

	public static function getPriorityEntityTypeId(): int
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if ($cache->initCache(self::CACHE_TIME, 'crm_client_entities_count', self::CACHE_DIR))
		{
			[$contactsCount, $companiesCount] = $cache->getVars();
		}
		elseif ($cache->startDataCache())
		{
			$contactsCount = \Bitrix\Crm\Entity\Contact::getInstance()->getCount(['enablePermissionCheck' => false]);
			$companiesCount = \Bitrix\Crm\Entity\Company::getInstance()->getCount(['enablePermissionCheck' => false]);
			$cache->endDataCache([$contactsCount, $companiesCount]);
		}

		return
			($companiesCount > $contactsCount)
				? \CCrmOwnerType::Company
				: \CCrmOwnerType::Contact
		;
	}

	/**
	 * Prepare ui.filter params for client user fields
	 *
	 * @param array $filterFields
	 * @param array $arFilter
	 */
	public function prepareFilter(array &$filterFields, array $arFilter): void
	{
		$this->ufManager->PrepareListFilterValues($filterFields, $arFilter, $this->gridId);
	}

	/**
	 * Remove deleted user fields from visible grid columns
	 *
	 * @param $fields
	 * @return bool
	 */
	public function removeUnavailableUserFields(&$fields): bool
	{
		return $this->ufManager->NormalizeFields($fields);
	}

	/**
	 * Remove client fields from $select, except CONTACT_ID / COMPANY_ID
	 *
	 * @param array $select
	 */
	public function prepareSelect(array &$select): void
	{
		if (!$this->hasClientFields($select))
		{
			return;
		}
		$clientFieldId = $this->fieldHelper->addPrefixToFieldId(self::ID_FIELD);

		if (!in_array($clientFieldId, $select, true))
		{
			$select[] = $clientFieldId;
		}

		$prefix = $this->fieldHelper->getFieldPrefix();

		// remove all client fields from $select except $clientFieldId
		// because they will be loaded separately in $this->appendResult():
		foreach ($select as $i => $fieldId)
		{
			if (
				mb_strpos($fieldId, $prefix) === 0
				&& $fieldId !== $clientFieldId
			)
			{
				$fieldIdWithoutPrefix = $this->fieldHelper->getFieldIdWithoutPrefix($fieldId);
				if ($fieldIdWithoutPrefix === self::RAW_ID_FIELD)
				{
					$this->addRawIdToSelect = true;
				}
				else
				{
					$this->realSelectFields[] = $fieldIdWithoutPrefix;
				}
				unset($select[$i]);
			}
		}
	}

	/**
	 * Add fields to be selected for contact/company
	 *
	 * @param array $fields
	 */
	public function addFieldsToSelect(array $fields): void
	{
		$this->realSelectFields = array_merge(
			$this->realSelectFields,
			$fields
		);
		$this->realSelectFields = array_unique($this->realSelectFields);
	}

	/**
	 * Add extra multifields columns for export
	 *
	 * @param array $headers
	 */
	public function prepareExportHeaders(array &$headers): void
	{
		$aliases = [];
		foreach (\CCrmFieldMulti::GetEntityTypeList() as $fieldId => $types)
		{
			$fieldIdWithPrefix = $this->fieldHelper->addPrefixToFieldId($fieldId);
			$aliases[$fieldIdWithPrefix] = [];

			foreach ($types as $typeId => $typeName)
			{
				$aliases[$fieldIdWithPrefix][] = $this->fieldHelper->addPrefixToFieldId($fieldId . '_' . $typeId);
			}
		}
		\CCrmComponentHelper::PrepareExportFieldsList(
			$headers,
			$aliases,
			false
		);
	}

	/**
	 * Get list of visible client fields
	 *
	 * @return Field[]
	 */
	public function getDisplayFields()
	{
		$rawIdFieldId = $this->fieldHelper->addPrefixToFieldId(self::RAW_ID_FIELD);
		$result = [
			$rawIdFieldId =>
				(Field::createByType('number', $rawIdFieldId))
					->setTitle($this->fieldHelper->getFieldName(self::ID_FIELD))
		];

		$baseFields = $this->getBaseFields();
		$entityBaseFieldsInfo = Container::getInstance()->getFactory($this->clientEntityTypeId)->getFieldsInfoByMap();
		foreach (array_keys($baseFields) as $fieldId)
		{
			if (!isset($entityBaseFieldsInfo[$fieldId]))
			{
				continue;
			}
			$fieldIdWithPrefix = $this->fieldHelper->addPrefixToFieldId($fieldId);
			$result[$fieldIdWithPrefix] = Field::createFromBaseField($fieldIdWithPrefix, $entityBaseFieldsInfo[$fieldId]);
			$result[$fieldIdWithPrefix]->setTitle($this->fieldHelper->getFieldName($fieldId));
		}

		$userFields = $this->ufManager->GetAbstractFields();
		foreach ($userFields as $fieldId => $userFieldInfo)
		{
			$result[$fieldId] = Field::createFromUserField($fieldId, $userFieldInfo);
		}

		$entityName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
		$iconUrl = '/bitrix/images/crm/grid_icons/' . strtolower($entityName) . '.svg';
		$iconTitle = $this->fieldHelper->getEntityTitle();

		foreach ($result as $field)
		{
			if ($this->isExportMode)
			{
				switch ($field->getType()) // force absolute date/time format for export
				{
					case \Bitrix\Crm\Field::TYPE_DATE:
						$field->addDisplayParams([
							'DATETIME_FORMAT' => \Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE)],
						);
						break;
					case \Bitrix\Crm\Field::TYPE_DATETIME:
						$field->addDisplayParams([
							'DATETIME_FORMAT' => \Bitrix\Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME)],
						);
						break;
				}
			}

			$field->addDisplayParam(
				'icon',
				[
					'url' => $iconUrl,
					'title' => $iconTitle,
				]
			);
		}

		return $result;
	}

	public function setExportMode(bool $isExportMode): self
	{
		$this->isExportMode = $isExportMode;

		return $this;
	}

	public function setGridId(string $gridId): self
	{
		$this->gridId = $gridId;


		return $this;
	}

	public static function getHeadersSections(): array
	{
		$sections = [
			[
				'id' => 'DEAL',
				'name' => Loc::getMessage("CRM_HEADER_SECTION_DEAL"),
				'default' => true,
				'selected' => true,
			],
		];
		$contactsSection = [
			'id' => \CCrmOwnerType::ContactName,
			'name' => Loc::getMessage("CRM_HEADER_SECTION_CONTACT"),
			'selected' => true,
		];
		$companiesSection = [
			'id' => \CCrmOwnerType::CompanyName,
			'name' =>  Loc::getMessage("CRM_HEADER_SECTION_COMPANY"),
			'selected' => true,
		];
		if (ClientDataProvider::getPriorityEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$sections[] = $contactsSection;
			$sections[] = $companiesSection;
		}
		else
		{
			$sections[] = $companiesSection;
			$sections[] = $contactsSection;
		}

		return $sections;
	}

	protected function hasPermissions(): bool
	{
		return \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($this->clientEntityTypeId, 0);
	}

	protected function hasClientFields(array $fields): bool
	{
		$prefix = $this->fieldHelper->getFieldPrefix();
		foreach ($fields as $field)
		{
			if (mb_strpos($field, $prefix) === 0)
			{
				return true;
			}
		}

		return false;
	}

	protected function extractClientIds(array $deals, string $clientFieldId): array
	{
		$result = [];

		foreach ($deals as $deal)
		{
			if (isset($deal[$clientFieldId]) && (int)$deal[$clientFieldId] > 0)
			{
				$result[] = (int)$deal[$clientFieldId];
			}
		}

		return array_unique($result);
	}

	protected function loadClientsInfo(array $clientIds): array
	{
		$result = [];

		$idFieldId = $this->fieldHelper->addPrefixToFieldId('ID');
		$isAccessibleFieldId = $this->fieldHelper->addPrefixToFieldId('IS_ACCESSIBLE');

		// by default there are no access to any $clientIds
		foreach ($clientIds as $clientId)
		{
			$result[$clientId] = [
				$idFieldId => $clientId,  // CONTACT_ID or COMPANY_ID
				$isAccessibleFieldId => false,  // CONTACT_IS_ACCESSIBLE or COMPANY_IS_ACCESSIBLE
			];
		}

		$selectFields = $this->getFieldsToSelect();
		$selectFields[] = self::ID_FIELD;

		$entity = $this->fieldHelper->getEntityClass();
		$collection = $entity::GetListEx(
			[],
			[
				'=ID' => $clientIds,
				'CHECK_PERMISSIONS' => 'Y',
			],
			false,
			false,
			$this->realSelectFields
		);

		while ($item = $collection->Fetch())
		{
			$client = [];
			foreach ($selectFields as $fieldId)
			{
				$fieldIdWithPrefix = $this->fieldHelper->addPrefixToFieldId($fieldId);
				if (isset($item[$fieldId]))
				{
					if (is_array($item[$fieldId]))
					{
						// multiple userfields will be encoded in $this->renderUserFieldsValues()
						$client[$fieldIdWithPrefix] = $item[$fieldId];
					}
					else
					{
						$client['~' . $fieldIdWithPrefix] = $item[$fieldId];
						$client[$fieldIdWithPrefix] = $this->prepareFieldValue($fieldId, (string)$item[$fieldId]);
					}
				}
			}
			$client[$isAccessibleFieldId] = true; // CONTACT_IS_ACCESSIBLE or COMPANY_IS_ACCESSIBLE
			$result[$item[self::ID_FIELD]] = $client;
		}

		$this->appendMultifieldsValue($result);

		return $result;
	}

	protected function getFieldsToSelect(): array
	{
		return $this->splitFieldsToSelect()['fields'];
	}

	protected function getMultifieldsToSelect(): array
	{
		return $this->splitFieldsToSelect()['multifields'];
	}


	protected function splitFieldsToSelect(): array
	{
		$selectFields = [];
		$selectMultifields = [];

		$multifieldTypes = array_merge(
			\CCrmFieldMulti::GetEntityTypeList(),
			\CCrmFieldMulti::GetEntityComplexList()
		);

		foreach ($this->realSelectFields as $fieldId)
		{
			if (isset($multifieldTypes[$fieldId]))
			{
				$selectMultifields[] = $fieldId;
			}
			else // not a multifield
			{
				$selectFields[] = $fieldId;
			}
		}

		return [
			'fields' => $selectFields,
			'multifields' => $selectMultifields,
		];
	}

	protected function getBaseHeaders(): array
	{
		$fields = $this->getBaseFields();

		$result = [];
		// special pseudo field CONTACT_RAW_ID / COMPANY_RAW_ID
		// because CONTACT_ID / COMPANY_ID fields are already in use:
		$result[] = $this->getIdHeader();

		foreach ($fields as $fieldId => $field)
		{
			$fieldIdWithPrefix = $this->fieldHelper->addPrefixToFieldId($fieldId);
			$field['name'] = $this->fieldHelper->getFieldName($fieldId, $this->isExportMode);
			$field['id'] = $fieldIdWithPrefix;
			$field['editable'] = false;
			$field['default'] = false;
			$field['sort'] = $field['sort'] ?? $field['id'];

			$result[] = $field;
		}

		return $result;
	}

	protected function getUfHeaders(): array
	{
		$result = [];
		$this->ufManager->ListAddHeaders($result);
		foreach ($result as &$field)
		{
			if ($this->isExportMode)
			{
				$field['name'] = $this->fieldHelper->addPrefixToFieldName($field['name']);
			}
			$field['editable'] = false;
			$field['default'] = false;
		}

		return array_values($result);
	}

	protected function getMultifieldsHeaders(): array
	{
		$result = [];
		if ($this->isExportMode)
		{
			$this->multifieldsManager->ListAddHeaders($result);
		}
		else
		{
			$this->multifieldsManager->PrepareListHeaders($result, ['LINK']);
		}
		foreach ($result as &$field)
		{
			$field['id'] = $this->fieldHelper->addPrefixToFieldId($field['id']);
			if ($this->isExportMode)
			{
				$field['name'] = $this->fieldHelper->addPrefixToFieldName($field['name']);
			}
			$field['editable'] = false;
			$field['default'] = false;
		}

		return $result;
	}

	protected function getIdHeader(): array
	{
		return [
			'id' => $this->fieldHelper->addPrefixToFieldId(self::RAW_ID_FIELD),
			'name' =>$this->fieldHelper->getFieldName(self::ID_FIELD),
			'sort' => $this->fieldHelper->addPrefixToFieldId(self::ID_FIELD),
			'first_order' => 'desc',
			'width' => 120,
			'type' => 'int',
		];
	}

	abstract protected function getBaseFields(): array;

	protected function appendMultifieldsValue(array &$result): void
	{
		$fieldsToSelect = $this->getMultifieldsToSelect();
		if (empty($fieldsToSelect))
		{
			return;
		}

		$clientIds = [];

		$isAccessibleFieldId = $this->fieldHelper->addPrefixToFieldId('IS_ACCESSIBLE');
		foreach ($result as $clientId => $client)
		{
			if ($client[$isAccessibleFieldId])
			{
				$clientIds[] = $clientId;
			}
		}

		if (!empty($clientIds))
		{
			$multifieldValues = $this->loadMultifieldInfo($clientIds);
			foreach ($multifieldValues as $clientId => $multifieldValue)
			{
				$result[$clientId] = array_merge(
					$result[$clientId],
					$multifieldValue
				);
			}
		}
	}

	protected function loadMultifieldInfo(array $clientIds): array
	{
		$entityName = \CCrmOwnerType::ResolveName($this->clientEntityTypeId);
		$filter = [
			'=ENTITY_ID' => $entityName,
			'@ELEMENT_ID' => $clientIds,
		];

		$items = \Bitrix\Crm\FieldMultiTable::getList([
			'order' => [
				'ID' => 'ASC',
			],
			'filter' => $filter,
		]);

		return $this->getPreparedMultifieldInfoValues($items, $entityName);
	}

	protected function getPreparedMultifieldInfoValues(\Bitrix\Main\ORM\Query\Result $items, string $entityName): array
	{
		$result = [];

		$values = [];
		$rawValues = [];

		while ($multifield = $items->fetch())
		{
			$value = $multifield['VALUE'];
			$fieldId = $multifield['COMPLEX_ID'];
			$clientId = $multifield['ELEMENT_ID'];

			$values[$clientId][$fieldId][] =
				$this->isExportMode
					? $value
					: \CCrmFieldMulti::GetTemplateByComplex($fieldId, $value);
			$rawValues[$clientId]['~' . $fieldId][] = $value;
			$result[$clientId]['~' . $this->fieldHelper->addPrefixToFieldId($fieldId)][] = $value;
		}

		foreach ($values as $clientId => $clientValues)
		{
			$preparedValues = [];
			foreach ($clientValues as $fieldId => $valuesByComplexId)
			{
				$preparedValues[$fieldId] = implode(', ', $valuesByComplexId);
			}

			if ($this->isExportMode)
			{
				$renderedValues = $preparedValues;
			}
			else
			{
				$allValues = array_merge(
					$rawValues[$clientId],
					$preparedValues
				);
				$renderedValues = \CCrmViewHelper::RenderListMultiFields(
					$allValues,
					$entityName . $clientId . '_',
					[
						'ENABLE_SIP' => true,
						'SIP_PARAMS' => [
							'ENTITY_TYPE' => 'CRM_' . $entityName,
							'ENTITY_ID' => $clientId,
						],
					]
				);
			}

			foreach ($renderedValues as $fieldId => $fieldValue)
			{
				$fieldId = $this->fieldHelper->addPrefixToFieldId($fieldId);
				$result[$clientId][$fieldId] = $fieldValue;
			}
		}

		return $result;
	}

	public function prepareFieldValue(string $fieldId, string $value): string
	{
		return htmlspecialcharsbx($value);
	}

	protected function isDictionaryField(string $fieldId): bool
	{
		$fields = $this->getBaseFields();
		$type = $fields[$fieldId]['type'] ?? '';

		return $type === 'list';
	}

	protected function getDictionaryFieldValue(string $fieldId, string $value): string
	{
		return $this->getDictionary($fieldId)[$value] ?? $value;
	}

	protected function getDictionary(string $fieldId): array
	{
		static $cache = [];
		if (!isset($cache[$fieldId]))
		{
			$values = [];
			switch ($fieldId)
			{
				case 'WEBFORM_ID':
					$values = \Bitrix\Crm\WebForm\Manager::getListNamesEncoded();
					break;
				case 'CURRENCY_ID':
					$values = \CCrmCurrency::GetCurrencyListEncoded();
					break;
				case 'TYPE_ID':
					$values = \CCrmStatus::GetStatusListEx('CONTACT_TYPE');
					break;
				case 'SOURCE_ID':
					$values = \CCrmStatus::GetStatusListEx('SOURCE');
					break;
				case 'HONORIFIC':
				case 'COMPANY_TYPE':
				case 'EMPLOYEES':
				case 'INDUSTRY':
					$values = \CCrmStatus::GetStatusListEx($fieldId);
					break;
			}
			$cache[$fieldId] = $values;
		}

		return $cache[$fieldId];
	}

	protected function isImageField(string $fieldId): bool
	{
		return in_array(
			$fieldId,
			[
				'PHOTO',
				'LOGO',
			]
		);
	}

	protected function getImageFieldValue(string $fieldId, string $value): string
	{
		if ($this->isExportMode)
		{
			if ($arFile = \CFile::GetFileArray($value))
			{
				return \CHTTP::URN2URI($arFile['SRC']);
			}
		}
		else
		{
			$arFileTmp = \CFile::ResizeImageGet(
				$value,
				['width' => 100, 'height' => 100],
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);

			return \CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}

		return '';
	}

	protected function addRawIdToResult(array &$deals): void
	{
		$clientFieldId = $this->fieldHelper->addPrefixToFieldId(self::ID_FIELD);
		$rawIdFieldId = $this->fieldHelper->addPrefixToFieldId(self::RAW_ID_FIELD);
		foreach ($deals as $dealId => $deal)
		{
			if (isset($deal[$clientFieldId]) && (int)$deal[$clientFieldId] > 0)
			{
				$deals[$dealId][$rawIdFieldId] = (int)$deal[$clientFieldId];
			}
		}
	}

	protected function formatTitle(array $clientInfo): string
	{
		if ($this->clientEntityTypeId === \CCrmOwnerType::Contact)
		{
			$titleParts = [
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'HONORIFIC',
			];
			$fields = [];
			foreach ($titleParts as $titlePart)
			{
				$fieldWithPrefix = '~' . $this->fieldHelper->addPrefixToFieldId($titlePart);
				$fields[$titlePart] = $clientInfo[$fieldWithPrefix] ?? '';
			}

			if (!$clientInfo['CONTACT_IS_ACCESSIBLE'])
			{
				return \CCrmEntitySelectorHelper::getHiddenTitle(\CCrmOwnerType::ContactName);
			}

			return \CCrmContact::prepareFormattedName($fields);
		}

		return $clientInfo['~' . $this->fieldHelper->addPrefixToFieldId('TITLE')] ?? '';
	}
}
