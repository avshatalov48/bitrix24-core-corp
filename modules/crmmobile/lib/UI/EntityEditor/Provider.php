<?php

namespace Bitrix\CrmMobile\UI\EntityEditor;

use Bitrix\Crm\Item;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item\Contact;
use Bitrix\Crm\Kanban\Exception;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Display\Field\IblockElementField;
use Bitrix\Crm\Service\Display\Field\IblockSectionField;
use Bitrix\Crm\Service\Display\Field\TextField;
use Bitrix\Crm\Service\Display\Field\StringField;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Im;
use Bitrix\Crm\Multifield\Type\Web;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Main\Web\Json;
use \Bitrix\Crm\Format\AddressFormatter;

final class Provider extends \Bitrix\Crm\Integration\UI\EntityEditor\Provider
{
	private const TEXTAREA = 'textarea';
	private const ENTITY_SELECTOR = 'entity-selector';
	private const MULTI_FIELD = 'multifield';
	private const COMBINED_FIELD = 'combined';
	private const STRING_FIELD = 'string';
	private const SELECT_FIELD = 'select';
	private const MENU_SELECT_FIELD = 'menu-select';
	private const OPPORTUNITY_FIELD = 'opportunity';
	private const CLIENT_FIELD = 'client_light';
	private const PHONE_FIELD = 'phone';
	private const WEB_FIELD = 'web';
	private const EMAIL_FIELD = 'email';
	private const IM_FIELD = 'im';
	private const ADDRESS_FIELD = 'address';
	private const RESOURCE_BOOKING_FIELD = 'resourcebooking';
	private const REQUISITE_FIELD = 'requisite';
	private const REQUISITE_ADDRESS_FIELD = 'requisite_address';

	private const COMBINED_V2_FIELDS = [
		self::WEB_FIELD,
		self::EMAIL_FIELD,
		self::IM_FIELD,
		self::PHONE_FIELD,
	];

	private const IMMUTABLE_COLLECTION_FIELDS = [
		self::ADDRESS_FIELD => true,
		self::RESOURCE_BOOKING_FIELD => true,
	];

	private ?array $displayFields = null;
	private ?array $displayItem = null;

	public function __construct(Factory $factory, Item $entity, array $params = [])
	{
		parent::__construct($factory, $entity, $params);

		$this->initializeEntityItem();
		$this->initializeDisplayItem();
	}

	public function getFields(): array
	{
		$fields = parent::getFields();
		$fields['MODULE_ID'] = 'crm';

		return $fields;
	}

	private function initializeEntityItem(): void
	{
		if ($this->isNewItem())
		{
			$fieldsWithoutUF = array_filter(
				parent::getEntityData(),
				static function ($key) {
					return mb_strpos($key, 'UF_') !== 0;
				},
				ARRAY_FILTER_USE_KEY
			);
			$this->entity->setFromCompatibleData($fieldsWithoutUF);

			$categoryId = $this->component->getCategoryId();
			if ($categoryId !== null && $this->entity->isCategoriesSupported())
			{
				$this->entity->setCategoryId($categoryId);
			}
		}
	}

	private function initializeDisplayItem(): void
	{
		$itemId = $this->getEntityId();

		$this->displayItem =
			(new Display($this->getEntityTypeId(), $this->getDisplayFields()))
				->skipEmptyFields(false)
				->setItems([$itemId => $this->entity])
				->getValues($itemId)
		;
	}

	private function getAliasFieldNames(): array
	{
		$aliases = $this->factory->getFieldsMap();

		if ($this->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			$aliases[Item::FIELD_NAME_OBSERVERS] = 'OBSERVER';
		}

		return $aliases;
	}

	private function getDisplayFields(): array
	{
		if ($this->displayFields === null)
		{
			$this->displayFields = [];

			$fieldsCollection = $this->factory->getFieldsCollection();
			$aliasMap = array_flip($this->getAliasFieldNames());

			foreach ($this->component->prepareFieldInfos() as $fieldInfo)
			{
				$fieldInfo['name'] = $aliasMap[$fieldInfo['name']] ?? $fieldInfo['name'];

				if ($field = $fieldsCollection->getField($fieldInfo['name']))
				{
					if ($fieldInfo['name'] === Item::FIELD_NAME_STAGE_ID)
					{
						$entityType = $this->factory->getStagesEntityId($this->entity->getCategoryId());
					}
					else
					{
						$entityType = $field->getCrmStatusType();
					}

					$displayParams = [
						'ENTITY_TYPE' => $entityType,
						'VALUE_TYPE' => $field->getValueType(),
					];

					try
					{
						$displayField = (
						Field::createByType($field->getType(), $field->getName())
							->setTitle($field->getTitle())
							->setIsMultiple($field->isMultiple())
							->setIsUserField($field->isUserField())
							->addDisplayParams($displayParams)
							->setUserFieldParams($field->getUserField())
							->setContext(Field::MOBILE_CONTEXT)
						);

						$this->displayFields[$fieldInfo['name']] = $displayField;
					}
					catch (Exception $exception)
					{
					}
				}
			}
		}

		return $this->displayFields;
	}

	public function getEntityFields(): array
	{
		$resultFields = [];

		$values = parent::getEntityData();
		$fieldsCollection = $this->factory->getFieldsCollection();
		$aliasMap = array_flip($this->getAliasFieldNames());

		foreach (parent::getEntityFields() as $field)
		{
			$fieldName = $field['name'];
			$fieldName = $aliasMap[$fieldName] ?? $fieldName;

			if ($this->isOpportunityField($fieldName))
			{
				if ($fieldName === 'OPPORTUNITY_WITH_CURRENCY')
				{
					$field['title'] = Loc::getMessage('MOBILE_UI_EDITOR_OPPORTUNITY_TITLE');
				}
				$field['type'] = self::OPPORTUNITY_FIELD;
			}

			if ($fieldName === 'OPPORTUNITY_WITH_CURRENCY')
			{
				$field['title'] = Loc::getMessage('MOBILE_UI_EDITOR_OPPORTUNITY_TITLE');
			}

			$fieldDataToMergeLast = [];

			if (!isset($field['data']) || !is_array($field['data']))
			{
				$field['data'] = [];
			}

			if ($fieldItem = $fieldsCollection->getField($fieldName))
			{
				if ($fieldItem->isHidden() || !$fieldItem->isDisplayed())
				{
					continue;
				}

				$field['type'] = $fieldItem->getType();

				if (isset(self::IMMUTABLE_COLLECTION_FIELDS[$field['type']]))
				{
					$field['editable'] = false;
				}
				else
				{
					$field['editable'] = $fieldItem->isValueCanBeChanged();
				}

				if ($field['type'] === self::RESOURCE_BOOKING_FIELD)
				{
					$field['multiple'] = false;
				}
				else
				{
					$field['multiple'] = $fieldItem->isMultiple();
				}

				if ($field['type'] === TextField::TYPE || $field['type'] ===  StringField::TYPE)
				{
					$field['type'] = self::TEXTAREA;
				}
				elseif ($field['type'] === 'double')
				{
					$field['type'] = 'number';
					$settings = $fieldItem->getSettings();
					$field['data']['precision'] = $settings['PRECISION'];
				}
				elseif (
					$field['type'] === IblockElementField::TYPE
					|| $field['type'] === IblockSectionField::TYPE
				)
				{
					$field['type'] = self::ENTITY_SELECTOR;
					$fieldDataToMergeLast['provider'] = $this->getIblockUserFieldProviderOptions(
						$field,
						$values[$fieldName]
					);
				}
				elseif ($field['type'] === self::RESOURCE_BOOKING_FIELD)
				{
					$field['type'] = self::STRING_FIELD;
				}

				if ($fieldName === Item::FIELD_NAME_STAGE_ID)
				{
					$field = $this->prepareStageFieldInfo($field);
				}
				elseif ($fieldName === Item::FIELD_NAME_ASSIGNED)
				{
					$field['required'] = true;
					$field['showRequired'] = false;
				}
				elseif ($fieldItem->getType() === \Bitrix\Crm\Field::TYPE_BOOLEAN)
				{
					$field['required'] = false;
				}
				elseif ($fieldItem->isUserField())
				{
					$field['required'] = $fieldItem->isRequired();
				}
				else
				{
					$field['required'] = !empty($field['data']['isRequiredByAttribute']);
				}

				if ($fieldItem->getType() === Field\EnumerationField::TYPE)
				{
					$items = [];

					$fieldInfo = $field['data']['fieldInfo'] ?? [];
					if (!empty($fieldInfo['ENUM']))
					{
						if (is_array($fieldInfo['ENUM']))
						{
							foreach ($fieldInfo['ENUM'] as $item)
							{
								$items[] = [
									'value' => $item['ID'],
									'name' => $item['VALUE'],
								];
							}
						}
						if (isset($fieldInfo['SETTINGS']['DISPLAY']))
						{
							$field['data']['mode'] = $fieldInfo['SETTINGS']['DISPLAY'] === 'DIALOG' ? 'selector' : 'picker';
						}
					}

					$fieldDataToMergeLast['items'] = $items;
				}
			}
			elseif ($field['type'] === self::MULTI_FIELD)
			{
				if (!\CCrmFieldMulti::IsSupportedType($fieldName))
				{
					continue;
				}

				$isCombinedV2 = $this->isCombinedV2($fieldName);

				$field['type'] = self::COMBINED_FIELD;
				$field['multiple'] = true;
				$field['required'] = !empty($field['data']['isRequiredByAttribute']);

				$field['data']['primaryField'] = [
					'id' => 'VALUE',
					'title' => $field['title'],
					'type' => $this->getFmPrimaryFieldType($fieldName),
					'config' => [
						'entityId' => $this->getEntityId(),
						'entityTypeName' => $this->getEntityTypeName(),
					],
				];

				$field['data']['secondaryField'] = [
					'id' => 'VALUE_TYPE',
					'title' => Loc::getMessage('MOBILE_UI_EDITOR_PROVIDER_TYPE'),
					'type' => $isCombinedV2 ? self::MENU_SELECT_FIELD : self::SELECT_FIELD,
					'required' => true,
					'showRequired' => false,
					'config' => [
						'menuTitle' => $field['title'],
						'partiallyHidden' => $fieldName === Im::ID,
						'items' => $isCombinedV2
							? $this->prepareMenuSelectItems($field)
							: $this->prepareFmItems($field),
					],
				];

				$field['data']['links'] = $isCombinedV2 ? $this->prepareMenuSelectLinks($fieldName) : [];
			}
			elseif ($field['type'] === self::REQUISITE_FIELD || $field['type'] === self::REQUISITE_ADDRESS_FIELD)
			{
				$field['multiple'] = true;
				$field['editable'] = false;
			}

			if (!empty($this->displayItem[$fieldName]['config']))
			{
				if (isset($this->displayItem[$fieldName]['config']['editable']))
				{
					$field['editable'] = $field['editable'] && $this->displayItem[$fieldName]['config']['editable'];
					unset($this->displayItem[$fieldName]['config']['editable']);
				}

				$field['data'] = array_merge($field['data'], $this->displayItem[$fieldName]['config']);
			}

			if ($field['type'] === self::CLIENT_FIELD)
			{
				$permissions = [];
				$categoryParams = $field['data']['categoryParams'] ?? [];
				if (!empty($categoryParams) && is_array($categoryParams))
				{
					foreach ($categoryParams as $entityTypeId => $entity)
					{
						$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
						$serviceUserPermissions = Container::getInstance()->getUserPermissions();
						$permissions[$entityTypeName] = [
							'read' => $serviceUserPermissions->checkReadPermissions($entityTypeId),
							'add' => $serviceUserPermissions->checkAddPermissions($entityTypeId),
						];
					}
				}

				$field['data']['permissions'] = $permissions;
			}

			if ($fieldName === Item::FIELD_NAME_TITLE)
			{
				$field['data']['autoCapitalize'] = 'sentences';
			}

			if (
				$fieldName === Item::FIELD_NAME_NAME
				|| $fieldName === Item::FIELD_NAME_LAST_NAME
				|| $fieldName === Item::FIELD_NAME_SECOND_NAME
				|| $fieldName === Item::FIELD_NAME_FULL_NAME
			)
			{
				$field['data']['autoCapitalize'] = 'words';
			}

			if ($fieldName === Company::FIELD_NAME_LOGO || $fieldName === Contact::FIELD_NAME_PHOTO)
			{
				$field['visibilityPolicy'] = 'edit';
				$field['data']['mediaType'] = 'image';
			}
			elseif ($fieldName === Item::FIELD_NAME_OBSERVERS)
			{
				$observersRestriction = RestrictionManager::getObserversRestriction();
				if (!$observersRestriction->hasPermission())
				{
					$field['data']['restriction']['mobileHelperId'] = $observersRestriction->getMobileInfoHelperId();
				}
			}

			if ($field['type'] === Field\UserField::TYPE || $field['type'] === self::CLIENT_FIELD)
			{
				$field['data']['hasSolidBorder'] = true;
			}

			if ($field['type'] === Field\FileField::TYPE)
			{
				$field['data']['controller']['entityId'] = 'crm-entity';
				$field['data']['controllerOptionNames'] = [
					'entityTypeId' => 'ENTITY_TYPE_ID',
					'entityId' => 'ID',
					'categoryId' => 'CATEGORY_ID',
				];
			}

			if ($this->isCombinedV2($field['name']))
			{
				$field['data']['addButtonText'] = Loc::getMessage("MOBILE_UI_EDITOR_{$field['name']}_BUTTON");
			}

			$field['data']['initialReadOnly'] = $this->isInitialReadOnlyField($field['type'], $field['name']);

			if (!empty($fieldDataToMergeLast))
			{
				$field['data'] = array_merge($field['data'], $fieldDataToMergeLast);
			}

			$resultFields[] = $field;
		}

		return $resultFields;
	}

	private function prepareStageFieldInfo(array $field): array
	{
		$field['type'] = 'crm-stage';
		$field['required'] = true;
		$field['showRequired'] = false;
		$field['data']['entityTypeId'] = $this->entity->getEntityTypeId();

		if ($this->entity->isCategoriesSupported())
		{
			$field['data']['categoryId'] = $this->entity->getCategoryId();
		}

		return $field;
	}

	private function isInitialReadOnlyField($fieldType, $fieldName): bool
	{
		return $fieldType === self::OPPORTUNITY_FIELD;
	}

	private function isOpportunityField($fieldName): bool
	{
		return $fieldName === 'OPPORTUNITY_WITH_CURRENCY' || $fieldName === 'REVENUE_WITH_CURRENCY';
	}

	private function isCombinedV2($fieldName): bool
	{
		return in_array($this->getFmPrimaryFieldType($fieldName), self::COMBINED_V2_FIELDS);
	}

	private function prepareFmItems(array $field): array
	{
		$items = (array)($field['data']['items'] ?? []);

		foreach ($items as &$item)
		{
			$item = array_change_key_case($item);
		}

		return $items;
	}

	private function prepareMenuSelectItems(array $field): array
	{
		$items = (array)($field['data']['items'] ?? []);

		foreach ($items as &$item)
		{
			$item = [
				'id' => $item['VALUE'],
				'title' => $item['NAME'],
			];
		}

		return $items;
	}

	private function prepareMenuSelectLinks(string $fieldName): array
	{
		if ($fieldName === Im::ID || $fieldName === Web::ID)
		{
			$result = [];
			$fmValueTypes = (array)(\CCrmFieldMulti::GetEntityTypes()[$fieldName] ?? []);

			foreach ($fmValueTypes as $name => $typeInfo)
			{
				$result[$name] = (string)($typeInfo['LINK'] ?? '');
			}

			return $result;
		}

		return [];
	}

	private function getFmPrimaryFieldType(string $fieldName): string
	{
		if ($fieldName === Web::ID)
		{
			return self::WEB_FIELD;
		}

		if ($fieldName === Email::ID)
		{
			return self::EMAIL_FIELD;
		}

		if ($fieldName === Im::ID)
		{
			return self::IM_FIELD;
		}

		if ($fieldName === Phone::ID)
		{
			return self::PHONE_FIELD;
		}

		return self::STRING_FIELD;
	}

	public function getEntityData(): array
	{
		$values = parent::getEntityData();

		$values['ENTITY_TYPE_ID'] = $this->factory->getEntityTypeId();
		$values['ID'] = $this->entity->getId();
		$values['CATEGORY_ID'] = $this->entity->getCategoryId();

		$aliasMap = $this->getAliasFieldNames();

		foreach ($this->getDisplayFields() as $displayName => $field)
		{
			$name = $aliasMap[$displayName] ?? $displayName;
			$values[$name] = $this->displayItem[$displayName]['value'] ?? null;
		}

		$values = $this->prepareStageFieldValue($values);
		$values = $this->prepareFmValues($values);
		$values = $this->prepareOpportunityField($values);
		$values = $this->prepareProductRowSummary($values);
		$values = $this->prepareRequisiteValues($values);

		if (isset($values['ID']) && $values['ID'] === '0')
		{
			$values['ID'] = null;
		}

		return $values;
	}

	private function prepareStageFieldValue(array $values): array
	{
		if ($this->entity->isStagesEnabled())
		{
			$stageStatus = $this->entity->getStageId();
			if ($stageStatus)
			{
				$stage = $this->factory->getStage($stageStatus);
				if ($stage)
				{
					$values[$this->entity::FIELD_NAME_STAGE_ID] = $stage->getId();
				}
			}
		}

		return $values;
	}

	private function prepareFmValues(array $values): array
	{
		foreach (\CCrmFieldMulti::GetEntityTypes() as $name => $info)
		{
			if (isset($values[$name]) && is_array($values[$name]))
			{
				$fmValues = [];

				foreach ($values[$name] as $item)
				{
					$valueType = isset($info[$item['VALUE_TYPE']]) ? $item['VALUE_TYPE'] : key($info);

					$fmValues[] = [
						'id' => $item['ID'],
						'value' => [
							'VALUE' => $item['VALUE'],
							'VALUE_TYPE' => $valueType,
							'VALUE_EXTRA' => $item['VALUE_EXTRA']
						],
					];
				}

				$values[$name] = $fmValues;
			}
		}

		return $values;
	}

	private function getPriceFieldName(): string
	{
		return $this->entity->hasField(Item::FIELD_NAME_OPPORTUNITY) ? Item::FIELD_NAME_OPPORTUNITY : 'OPPORTUNITY';
	}

	private function getCurrencyFieldName(): string
	{
		return $this->entity->hasField(Item::FIELD_NAME_CURRENCY_ID) ? Item::FIELD_NAME_CURRENCY_ID : 'CURRENCY_ID';
	}

	private function prepareOpportunityField(array $values): array
	{
		$priceFieldName = $this->getPriceFieldName();

		if (isset($values[$priceFieldName]))
		{
			$values[$priceFieldName] = (float)$values[$priceFieldName];
		}
		else
		{
			$values[$priceFieldName] = 0;
		}

		return $values;
	}

	private function prepareProductRowSummary(array $values): array
	{
		if ($this->factory->isLinkWithProductsEnabled())
		{
			$productRowFieldName = EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY;

			if (
				!isset($values[$productRowFieldName]['totalRaw'])
				|| !is_array($values[$productRowFieldName]['totalRaw'])
			)
			{
				$priceFieldName = $this->getPriceFieldName();
				$currencyFieldName = $this->getCurrencyFieldName();

				$values[$productRowFieldName]['totalRaw'] = [
					'amount' => $values[$priceFieldName] ?? 0,
					'currency' => $values[$currencyFieldName] ?? '',
				];
			}

			if (!isset($values[$productRowFieldName]['count']))
			{
				$values[$productRowFieldName]['count'] = 0;
			}
		}

		return $values;
	}

	public function getEntityControllers(): array
	{
		$controllers = parent::getEntityControllers();
		foreach ($controllers as $key => $controller)
		{
			if ($controller['name'] === EditorAdapter::CONTROLLER_PRODUCT_LIST)
			{
				$controllers[$key] = $this->prepareProductListController($controller);
				break;
			}
		}

		return $controllers;
	}

	private function prepareProductListController(array $controller): array
	{
		$config = $controller['config'] ?? [];

		$config['priceFieldName'] = $this->getPriceFieldName();
		$config['currencyFieldName'] = $this->getCurrencyFieldName();

		$config['priceWithCurrencyFieldName'] = isset($config['priceFieldName'], $config['currencyFieldName'])
			? EditorAdapter::FIELD_OPPORTUNITY : null;

		$config['productSummaryFieldName'] = $this->factory->isLinkWithProductsEnabled()
			? EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY : null;

		return array_merge($controller, ['config' => $config]);
	}

	private function getIblockUserFieldProviderOptions(array $field, array $value): array
	{
		$fieldInfo = $field['data']['fieldInfo'] ?? [];

		if (empty($value['IS_EMPTY']) && isset($value['VALUE']))
		{
			$fieldInfo['VALUE'] = $value['VALUE'];
		}

		$fieldInfo['SIGNATURE'] = $value['SIGNATURE'];

		return [
			'options' => [
				'fieldInfo' => $fieldInfo,
			],
		];
	}

	private function prepareRequisiteValues(array $values): array
	{
		$values['REQUISITES_RAW'] = [];
		$values['REQUISITES_ADDRESSES_RAW'] = [];

		if (!empty($values['REQUISITES']) && is_array($values['REQUISITES']))
		{
			foreach ($values['REQUISITES'] as $requisite)
			{
				if (!empty($requisite['requisiteData']))
				{
					try
					{
						$requisiteData = Json::decode($requisite['requisiteData']);
						$requisiteAddresses = $requisiteData['fields']['RQ_ADDR'] ?? [];

						if (!empty($requisiteAddresses) && is_array($requisiteAddresses))
						{
							foreach ($requisiteAddresses as $id => $requisiteAddressJson)
							{
								$formatter = AddressFormatter::getSingleInstance();
								$requisiteAddress= $formatter->formatLocationAddressArrayAsString(
									Json::decode($requisiteAddressJson)
								);
								$values['REQUISITES_ADDRESSES_RAW'][$id] = $requisiteAddress;
							}
						}

						$requisite['requisiteData'] = $requisiteData;

						$values['REQUISITES_RAW'][] = $requisite;
					}
					catch (\Exception $e)
					{
					}
				}
			}
		}

		unset($values['REQUISITES']);

		return $values;
	}
}
