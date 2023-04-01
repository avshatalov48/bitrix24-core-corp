<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2022 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityAddress;

class Requisite
{
	private $splitAccountFields = true;

	public static function instance(): self
	{
		return new self();
	}

	public function convertSettingsToOptions(array $settings): array
	{
		$dictPresets = $this->getPresets();
		$dictPresets = array_combine(
			array_column($dictPresets, 'id'),
			array_column($dictPresets, 'fields')
		);

		$presets = [];
		foreach (($options['presets'] ?? []) as $preset)
		{
			if (!empty($preset['disabled']))
			{
				continue;
			}

			$dictPresetFields = $dictPresets[$preset['id']]['fields'] ?? null;
			if (!$dictPresetFields)
			{
				continue;
			}
			$dictPresetFields = array_combine(
				array_column($dictPresetFields, 'name'),
				$dictPresetFields
			);

			$fields = [];
			foreach (($preset['fields'] ?? []) as $field)
			{
				if (!empty($field['disabled']))
				{
					continue;
				}

				$dictField = $dictPresetFields[$field['name']] ?? null;
				if (!$dictField)
				{
					continue;
				}

				$fields[] = [
					'name' => $field['name'],
					'label' => $dictField['label'] === $field['label'] ? '' : $field['label'],
				];
			}

			$presets[] = [
				'id' => $preset['id'],
				'fields' => $fields,
			];
		}

		return [
			'presets' => $this->getPresets(),
		];
	}

	public function convertOptionsToSettings(array $options): array
	{
		return [];

		$presets = $options['presets'] ?? [];
		$presets = array_map(
			function (array $preset): array
			{
				$fieldNames = ['RQ_ACC'];
				$fields = array_filter(
					$preset['fields'],
					function (array $field) use ($fieldNames): bool
					{
						return in_array($field['name'], $fieldNames);
					}
				);
				$fields = array_map(
					function (array $field): array
					{
						return [
							'name' => $field['name'],
						];
					},
					$fields
				);
				return [

					'id' => $preset['id'],
					'fields' => $fields,
				];
			},
			$presets
		);


		return [
			'presets' => $presets,
		];
	}

	public function getDefaultPreset(int $entityTypeId): ?array
	{
		if ($entityTypeId !== \CCrmOwnerType::Company)
		{
			$entityTypeId = \CCrmOwnerType::Company;
		}

		$id = EntityRequisite::getDefaultPresetId($entityTypeId);
		return $id ? $this->getPreset($id) : null;
	}

	public function getPreset(int $id): ?array
	{
		return current(array_filter(
			$this->getPresets(),
			function (array $preset) use ($id)
			{
				return $preset['id'] === $id;
			}
		)) ?: null;
	}

	public function getPresets(): array
	{
		$list = [];
		foreach (array_keys(EntityPreset::getActiveItemList()) as $id)
		{
			$preset = EntityPreset::getSingleInstance()->getById($id);
			if (!$preset)
			{
				continue;
			}

			$countryId = (int)$preset['COUNTRY_ID'];
			$presetSettings = is_array($preset['SETTINGS'] ?? 0) ? $preset['SETTINGS'] : [];
			$fieldNames = EntityPreset::getSingleInstance()->settingsGetFields($presetSettings);
			$fieldNames = array_column($fieldNames, 'FIELD_NAME');
			$hasAddress = in_array(EntityRequisite::ADDRESS, $fieldNames);

			$fields = $this->getReqFields(
				$countryId,
				array_diff(
					$fieldNames,
					[EntityRequisite::ADDRESS]
				)
			);
			if ($hasAddress)
			{
				$fields = array_merge($fields, $this->getAddressTypes($countryId));
			}

			if ($this->splitAccountFields)
			{
				foreach ($this->getBankingFields($countryId) as $accountField)
				{
					//$accountField['name'] = 'RQ_ACC_' . $accountField['name'];
					$fields[] = $accountField;
				}
			}
			else
			{
				$fields[] = [
					'name' => 'RQ_ACC',
					'type' => 'account',
					'label' => 'Banking account',
					'fields' => $this->getBankingFields($countryId),
				];
			}

			$validationMap = EntityRequisite::getSingleInstance()->getRqFieldValidationMap();
			$fields = array_map(
				function (array $field) use ($validationMap)
				{
					$name = $field['name'];
					$validators = $validationMap[$name] ?? [];
					if (Main\Type\Collection::isAssociative($validators))
					{
						$validators = [$validators];
					}

					foreach ($validators as $validator)
					{
						if ($validator['type'] !== 'length')
						{
							continue;
						}

						$field['size'] = [
							'min' => (int)($validator['params']['min'] ?? 0),
							'max' => (int)($validator['params']['max'] ?? 0),
						];
						break;
					}

					return $field;
				},
				$fields
			);

			$list[] = [
				'id' => (int)$preset['ID'],
				'label' => $preset['NAME'],
				'countryId' => $countryId,
				'fields' => $fields,
			];
		}

		return $list;
	}

	private function getReqFields(int $countryId, array $whiteNamesList): array
	{
		$fields = EntityRequisite::getSingleInstance()->getFormFieldsInfo($countryId);

		$fields = array_filter(
			$fields,
			function (array $field, $name) use ($whiteNamesList)
			{
				return $field['isRQ']
					&& in_array($name, $whiteNamesList)
					&& self::convertType($field['formType'] ?: $field['type'])
				;
			},
			ARRAY_FILTER_USE_BOTH
		);

		return array_map(
			function (array $field, $name)
			{
				return [
					'id' => $name,
					'name' => $name,
					'type' => self::convertType($field['formType'] ?: $field['type']),
					'label' => $field['title'],
					'multiple' => $field['multiple'],
					'required' => $field['required'],
					//'entity_name' => '',
					//'entity_field_name' => '',
				];
			},
			$fields,
			array_keys($fields)
		);
	}

	private static function convertType(string $type): string
	{
		switch ($type)
		{
			case 'text':
				//return Internals\FieldTable::TYPE_ENUM_TEXT;

			case 'string':
				return Internals\FieldTable::TYPE_ENUM_STRING;

			case 'double':
				return Internals\FieldTable::TYPE_ENUM_FLOAT;

			case 'integer':
				return Internals\FieldTable::TYPE_ENUM_INT;

			case 'boolean':
				return Internals\FieldTable::TYPE_ENUM_BOOL;

			case 'datetime':
				return Internals\FieldTable::TYPE_ENUM_DATETIME;

			default:
				return '';
		}
	}

	public function getAddressField(int $entityTypeId, string $fieldName): ?array
	{
		if (!$fieldName)
		{
			return null;
		}

		$preset = $this->getDefaultPreset($entityTypeId);
		if (!$preset)
		{
			return [];
		}

		foreach ($preset['fields'] as $field)
		{
			if ($field['name'] === $fieldName)
			{
				return $field;
			}
		}

		return null;
	}

	private function getAddressTypes(int $countryId): array
	{
		$zoneId = EntityRequisite::getAddressZoneByCountry($countryId);

		//ENTITY_TYPE_ID & ENTITY_ID = Requisite relation
		//ANCHOR_TYPE_ID & ANCHOR_ID = Contact&Company relation

		$types = [];
		$fields = EntityAddress::getFieldsInfo();
		$shortLabels = EntityAddress::getShortLabels();

		foreach ((EntityAddressType::getZoneMap()[$zoneId]['types'] ?? []) as $typeId)
		{
			$fields = array_filter(
				$fields,
				function (array $field, $name): bool
				{
					return !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::Immutable)
						&& !\CCrmFieldInfoAttr::isFieldHasAttribute($field, \CCrmFieldInfoAttr::ReadOnly)
						&& !in_array($name, ['COUNTRY_CODE'])
					;
				},
				ARRAY_FILTER_USE_BOTH
			);

			$types[] = [
				'id' => $typeId,
				'type' => 'address',
				'name' => EntityRequisite::ADDRESS . '_' . EntityAddressType::resolveName($typeId),
				'label' => EntityAddressType::getDescription($typeId),
				'fields' => array_map(
					function (array $field, $name) use ($typeId, $shortLabels): array
					{
						$visible = true;
						if ($name === 'LOC_ADDR_ID')
						{
							$visible = false;
						}

						return [
							'type' => $field['TYPE'],
							'name' => $name,
							'label' => ($shortLabels[$name] ?? '') ?: EntityAddress::getLabel($name, $typeId),
							'visible' => $visible,
						];
					},
					$fields,
					array_keys($fields)
				),
			];
		}

		return $types;
	}

	public function getBankingFields(int $countryId): array
	{
		$banking = array_intersect_key(
			EntityBankDetail::getSingleInstance()->getFormFieldsInfo($countryId),
			array_flip(EntityBankDetail::getSingleInstance()->getRqFieldByCountry()[$countryId] ?? [])
		);
		$banking = array_filter(
			$banking,
			function (array $field)
			{
				return $field['isRQ'];
			}
		);

		return array_map(
			function (array $field, string $name)
			{
				return [
					'name' => $name,
					'type' => $field['type'], //$field['formType'] for datetime is text
					'label' => $field['title'],
					'multiple' => $field['multiple'],
					'required' => $field['required'],
				];
			},
			$banking,
			array_keys($banking)
		);
	}

	private static function makeOperationFields(array $fields, array $values, string $prefix = ''): array
	{
		$result = [];
		foreach ($fields as $field)
		{
			$name = $field['name'];
			$realName = $name;
			if ($prefix)
			{
				$realName = explode($prefix, $name)[1] ?? '';
				if (!$realName)
				{
					continue;
				}
			}

			$value = $values[$name] ?? false;
			if ($value != false && $value !== '')
			{
				$result[$realName] = $value;
			}
		}

		return $result;
	}

	public function fill(int $entityTypeId, int $entityId, array $values, ?int $requisitePresetId = null): Result
	{
		$result = new Result();

		$presetId = (int)($requisitePresetId ?? $this->getDefaultPreset($entityTypeId)['id'] ?? null);
		if ($presetId <= 0)
		{
			return $result;
		}

		$preset = $this->getPreset($presetId);
		if (!$preset)
		{
			return $result;
		}

		$requisite = [];
		$addresses = [];
		$account = self::makeOperationFields(
			$this->getBankingFields($preset['countryId']),
			$values
		);

		foreach ($preset['fields'] as $field)
		{
			switch ($field['type'])
			{
				case 'address':
					$value = $values[$field['name']] ?? [];
					if ($value)
					{
						$addresses[$field['id']] = self::makeOperationFields($field['fields'], $value);
					}
					break;

				case 'account':
					$account = self::makeOperationFields(
						$field['fields'],
						$values[$field['name']] ?? []
					);
					break;

				default:
					$requisite[] = $field;
					break;
			}
		}

		$requisite = self::makeOperationFields($requisite, $values);


		$id = (int)EntityRequisite::getSingleInstance()->getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
				'=PRESET_ID' => $presetId,
			],
			'limit' => 1,
		])->fetch()['ID'] ?? 0;

		if (!$id)
		{
			$requisite['ENTITY_TYPE_ID'] = $entityTypeId;
			$requisite['ENTITY_ID'] = $entityId;
			$requisite['PRESET_ID'] = $presetId;
			$requisite['NAME'] = Loc::getMessage('CRM_WEBFORM_REQUISITE_DEFAULT_NAME');
			$result = EntityRequisite::getSingleInstance()->add($requisite);
			if ($result->isSuccess() && $result instanceof Main\ORM\Data\Result)
			{
				$id = $result->getId();
			}
		}
		else
		{
			$result = EntityRequisite::getSingleInstance()->update($id, $requisite);
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		foreach ($addresses as $addressTypeId => $address)
		{
			$address['ANCHOR_TYPE_ID'] = $entityTypeId;
			$address['ANCHOR_ID'] = $entityId;
			EntityAddress::register(
				\CCrmOwnerType::Requisite,
				$id,
				$addressTypeId,
				$address
			);
		}

		$accountId = (int)(EntityBankDetail::getSingleInstance()->getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
				'=ENTITY_ID' => $id,
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetch()['ID'] ?? 0);
		if ($accountId)
		{
			$result = EntityBankDetail::getSingleInstance()->update($accountId, $account);
		}
		else
		{
			$account['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
			$account['ENTITY_ID'] = $id;
			$account['NAME'] = 'Default name';
			$result = EntityBankDetail::getSingleInstance()->add($account);
		}

		return $result;
	}

	public function load(int $entityTypeId, int $entityId, ?int $requisitePresetId = null): Result
	{
		$result = new Result();

		$splitMode = $this->splitAccountFields;
		$this->splitAccountFields = false;
		$preset =
			$requisitePresetId !== null && $requisitePresetId >= 0
				? $this->getPreset($requisitePresetId)
				: $this->getDefaultPreset($entityTypeId)
		;
		$this->splitAccountFields = $splitMode;

		if (!$preset)
		{
			$result->addError(new Error("Default preset not found for entity type `{$entityTypeId}`."));
			return $result;
		}

		$values = [];
		$keys = array_map(
			function (array $field): string
			{
				return $field['name'];
			},
			array_filter(
				$preset['fields'],
				function (array $field): bool
				{
					return !in_array($field['type'], ['address', 'account']);
				}
			)
		);

		$keys[] = 'ID';
		$requisiteValues = (EntityRequisite::getSingleInstance()->getList([
			'select' => $keys,
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
				'=PRESET_ID' => $preset['id'],
			],
			'limit' => 1,
		])->fetch() ?: []);

		foreach ($requisiteValues as $fieldName => $value)
		{
			if (mb_strpos($fieldName, 'RQ_') !== 0)
			{
				$requisiteValues['RQ_'. $fieldName] = $value;
				unset($requisiteValues[$fieldName]);
			}
		}
		$values = $values + $requisiteValues;

		if (!$values)
		{
			return $result;
		}

		$id = (int) ($values['ID'] ?: $values['RQ_ID']);
		unset($values['ID']);
		unset($values['RQ_ID']);

		foreach ($this->getAddressTypes($preset['countryId']) as $address)
		{
			$keys = array_map(
				function (array $field)
				{
					return $field['name'];
				},
				$address['fields']
			);

			//$address['ANCHOR_TYPE_ID'] = $entityTypeId;
			//$address['ANCHOR_ID'] = $entityId;
			$row = (new EntityAddress())->getList([
				'filter' => [
					'=TYPE_ID' => $address['id'],
					'=ANCHOR_TYPE_ID' => $entityTypeId,
					'=ANCHOR_ID' => $entityId,
				],
				'order' => [
					'ADDRESS_1' => 'DESC',
					'LOC_ADDR_ID' => 'DESC',
				],
			])->fetch();

			$val = [];
			foreach ($keys as $key)
			{
				$v = $row[$key] ?? '';
				if ($v === null || $v === '')
				{
					continue;
				}

				if ($key === 'LOC_ADDR_ID' && !$v)
				{
					continue;
				}

				$val[$key] = $v;
			}

			if ($val)
			{
				$values[$address['name']] = $val;
			}
		}

		$keys = array_map(
			function (array $item)
			{
				return $item['name'];
			},
			$this->getBankingFields($preset['countryId'])
		);
		$bankFieldsValues = (EntityBankDetail::getSingleInstance()->getList([
			'select' => $keys,
			'filter' => [
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
				'=ENTITY_ID' => $id,
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetch() ?: []);
		$values = $values + $bankFieldsValues;

		foreach ($values as $key => $value)
		{
			if ($value === '' || $value === null)
			{
				unset($values[$key]);
			}
		}

		$values['requisiteId'] = $id;
		$values['presetId'] = (int)$preset['id'];

		$result->setData($values);
		return $result;
	}

	public static function separateFieldValues(int $entityTypeId, array $values): array
	{
		$isSupportRequisites = in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]);

		$reqValues = [];
		$entityValues = [];
		$prefix = 'RQ_';
		$prefixLen = mb_strlen($prefix);
		foreach ($values as $key => $value)
		{
			if (mb_substr($key, 0, $prefixLen) !== $prefix)
			{
				$entityValues[$key] = $value;
				continue;
			}

			if ($isSupportRequisites)
			{
				//$key = mb_substr($key, $prefixLen);
				$reqValues[$key] = $value;
			}
		}

		return [$reqValues, $entityValues];
	}
}
