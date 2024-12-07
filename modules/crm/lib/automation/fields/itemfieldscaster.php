<?php

namespace Bitrix\Crm\Automation\Fields;

use Bitrix\Bizproc\Fields\ICaster;
use Bitrix\Bizproc\FieldType;
use Bitrix\Crm;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Types\EnumType;

class ItemFieldsCaster implements ICaster
{
	private Crm\Item $item;
	private array $fieldsMap;

	public function __construct(Crm\Item $item, array $fieldsMap)
	{
		$this->item = $item;
		$this->fieldsMap = $fieldsMap;
	}

	private function getFactory(): Crm\Service\Factory
	{
		return Crm\Service\Container::getInstance()->getFactory($this->item->getEntityTypeId());
	}

	public function internalize(array $values): array
	{
		$internalizedValues = [];

		foreach ($values as $fieldId => $fieldValue)
		{
			if (array_key_exists($fieldId, $this->fieldsMap))
			{
				continue;
			}

			$internalizedValues[static::internalizeFieldId($fieldId)] = $fieldValue;
		}

		return $internalizedValues;
	}

	public static function internalizeFieldsIds(array $values): array
	{
		$result = [];
		foreach ($values as $fieldId => $fieldValue)
		{
			$result[static::internalizeFieldId($fieldId)] = $fieldValue;
		}

		return $result;
	}

	private static function internalizeFieldId(string $fieldId): string
	{
		$map = [
			Crm\Item::FIELD_NAME_OBSERVERS => 'OBSERVER_IDS',
			Crm\Item::FIELD_NAME_PRODUCTS => 'PRODUCT_IDS',
			Crm\Item::FIELD_NAME_CONTACTS => 'CONTACT_IDS',
		];

		return $map[$fieldId] ?? $fieldId;
	}

	public function externalize(array $values): array
	{
		$this->externalizeContactFields($values);
		$this->externalizeStageFields($values);

		$externalValues = [];
		foreach ($values as $fieldId => $fieldValue)
		{
			if (!array_key_exists($fieldId, $this->fieldsMap))
			{
				continue;
			}

			$externalFieldId = $this->externalizeFieldId($fieldId);
			$externalValues[$externalFieldId] = $this->externalizeValue($fieldId, $fieldValue);
		}

		if (!$this->item->isNew() && isset($externalValues['OPPORTUNITY']))
		{
			$externalValues['IS_MANUAL_OPPORTUNITY'] = $externalValues['OPPORTUNITY'] > 0 ? 'Y' : 'N';
		}

		return $externalValues;
	}

	private function externalizeContactFields(array& $values): void
	{
		if (isset($values[Crm\Item::FIELD_NAME_CONTACT_ID]))
		{
			if (!isset($values['CONTACT_IDS']) || !is_array($values['CONTACT_IDS']))
			{
				$values['CONTACT_IDS'] = [];
			}
			$values['CONTACT_IDS'][] = $values[Crm\Item::FIELD_NAME_CONTACT_ID];
			unset($values[Crm\Item::FIELD_NAME_CONTACT_ID]);
		}
	}

	private function externalizeStageFields(array& $values): void
	{
		if (
			isset($values[Crm\Item::FIELD_NAME_STAGE_ID])
			&& $this->getFactory()->isCategoriesSupported()
			&& !isset($values[Crm\Item::FIELD_NAME_CATEGORY_ID])
		)
		{
			$stage = $this->getFactory()->getStage($values[Crm\Item::FIELD_NAME_STAGE_ID]);
			$values[Crm\Item::FIELD_NAME_CATEGORY_ID] = $stage['CATEGORY_ID'];
		}
	}

	private function externalizeFieldId(string $fieldId): string
	{
		$map = [
			'OBSERVER_IDS' => Crm\Item::FIELD_NAME_OBSERVERS,
			'PRODUCT_IDS' => Crm\Item::FIELD_NAME_PRODUCTS,
		];

		if ($fieldId === Crm\Item::FIELD_NAME_CONTACTS)
		{
			return 'CONTACT_IDS';
		}

		return array_key_exists($fieldId, $map) ? $map[$fieldId] : $fieldId;
	}

	private function externalizeValue(string $fieldId, $value)
	{
		if ($this->isUserField($fieldId) && $this->fieldsMap[$fieldId]['Type'] === FieldType::SELECT)
		{
			return $this->externalizeEnumerationField($fieldId, $value);
		}

		if ($this->fieldsMap[$fieldId]['Type'] === FieldType::USER)
		{
			$complexDocumentId = \CCrmBizProcHelper::ResolveDocumentId(
				$this->item->getEntityTypeId(),
				$this->item->getId()
			);
			$userIds = \CBPHelper::extractUsers($value, $complexDocumentId);
			if (!$this->fieldsMap[$fieldId]['Multiple'])
			{
				return $userIds[0] ?? null;
			}

			return $userIds;
		}

		if (is_array($value))
		{
			$converter = fn ($currentValue) => $this->externalizeValue($fieldId, $currentValue);

			return
				$this->fieldsMap[$fieldId]['Multiple']
					? array_map($converter, $value)
					: $converter(array_values($value)[0] ?? null)
				;
		}

		switch ($this->fieldsMap[$fieldId]['Type'])
		{
			case FieldType::BOOL:
				return \CBPHelper::getBool($value);

			case FieldType::FILE:
				$file = false;
				\CCrmFileProxy::TryResolveFile($value, $file, ['ENABLE_ID' => true]);

				return $file;

			case FieldType::DATETIME:
				if ($value && is_string($value))
				{
					return new DateTime($value);
				}

			default:
				return $value;
		}
	}

	private function externalizeEnumerationField(string $fieldId, $value): mixed
	{
		$entityResult = \CUserTypeEntity::GetList([], [
			'ENTITY_ID' => $this->getFactory()->getUserFieldEntityId(),
			'FIELD_NAME' => $fieldId,
		]);
		$userTypeEntity = $entityResult->Fetch();
		if (!is_array($userTypeEntity))
		{
			return null;
		}

		$enumXmlMap = [];
		$enumValueMap = [];

		$enumResult = EnumType::getList($userTypeEntity);
		while ($enum = $enumResult->GetNext())
		{
			$enumXmlMap[$enum['XML_ID']] = $enum['ID'];
			$enumValueMap[$enum['VALUE']] = $enum['ID'];
		}

		$results = [];

		$convertSingleValue = function ($singleValue) use (&$results, $enumXmlMap, $enumValueMap)
		{
			if (isset($enumXmlMap[$singleValue]))
			{
				$results[] = $enumXmlMap[$singleValue];
			}
			elseif (isset($enumValueMap[$singleValue]))
			{
				$results[] = $enumValueMap[$singleValue];
			}
		};

		if (is_array($value))
		{
			array_walk_recursive(
				$value,
				$convertSingleValue,
			);
		}
		else
		{
			$convertSingleValue($value);
		}

		$results = array_unique($results);

		$isMultiple = \CBPHelper::getBool($userTypeEntity['MULTIPLE'] ?? 'N');
		if (!$isMultiple)
		{
			return !empty($results) ? $results[0] : null;
		}

		return $results;
	}

	private function isUserField(string $fieldId): bool
	{
		return mb_substr($fieldId, 0, 3) === 'UF_';
	}
}