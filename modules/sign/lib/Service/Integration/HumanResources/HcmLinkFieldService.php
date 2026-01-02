<?php

namespace Bitrix\Sign\Service\Integration\HumanResources;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\HumanResources;
use Bitrix\HumanResources\Item;
use Bitrix\Main\Result;
use Bitrix\Sign\Item\Field\HcmLink\HcmLinkFieldParsedName;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkFieldRequestResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\BlockParty;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Type\Integration\HumanResources\FieldSerializedIndex;

class HcmLinkFieldService
{
	private const CATEGORY_REPRESENTATIVE = 'REPRESENTATIVE';
	private const CATEGORY_EMPLOYEE = 'EMPLOYEE';
	private const CATEGORY_COMPANY = 'COMPANY';

	private const NAME_SEPARATOR = '_';
	public const FIELD_PREFIX = 'HCMFIELD';

	public function getFieldsForSelector(int $hcmLinkCompanyId, bool $withEmployee = true): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$employeeFieldCollection = HumanResources\Service\Container::getHcmLinkFieldRepository()
			->getByCompanyIdAndEntityType(
				$hcmLinkCompanyId,
				HumanResources\Type\HcmLink\FieldEntityType::EMPLOYEE
			)
		;

		$response = [
			self::CATEGORY_REPRESENTATIVE => [
				'CAPTION' => '',
				'FIELDS' => $this->mapFieldCollectionForSelector(
					fieldCollection: $employeeFieldCollection,
					entityName: self::CATEGORY_REPRESENTATIVE,
					party: BlockParty::NOT_LAST_PARTY,
				),
			],
		];

		if ($withEmployee)
		{
			$response[self::CATEGORY_EMPLOYEE] = [
				'CAPTION' => '',
				'FIELDS' => $this->mapFieldCollectionForSelector(
					fieldCollection: $employeeFieldCollection,
					entityName: self::CATEGORY_EMPLOYEE,
					party: BlockParty::LAST_PARTY,
				),
			];
		}

		return $response;
	}

	public function extractCompanyIdFromFieldName(string $fieldName): ?int
	{
		return $this->parseName($fieldName)?->integrationId;
	}

	private function mapFieldName(Item\HcmLink\Field $field, int $party): string
	{
		return implode(self::NAME_SEPARATOR, [
			FieldSerializedIndex::FIELD_PREFIX_INDEX->value => self::FIELD_PREFIX,
			FieldSerializedIndex::FIELD_COMPANY_INDEX->value => $field->companyId,
			FieldSerializedIndex::FIELD_TYPE_INDEX->value => $field->type->value,
			FieldSerializedIndex::FIELD_PARTY_INDEX->value => $party,
			FieldSerializedIndex::FIELD_ID->value => $field->id,
		]);
	}

	private function mapFieldCollectionForSelector(
		Item\Collection\HcmLink\FieldCollection $fieldCollection,
		string $entityName,
		int $party,
	): array
	{
		return array_values(
			$fieldCollection->map(
				fn (Item\HcmLink\Field $field) => [
					'type' => $field->type->value,
					'entity_name' => $entityName,
					'name' => $this->mapFieldName($field, $party),
					'caption' => $field->title,
					'multiple' => false,
					'required' => false,
					'hidden' => false,
				],
			),
		);
	}

	public function isAvailable(): bool
	{
		return Container::instance()->getHcmLinkService()->isAvailable()
			&& Loader::includeModule('humanresources')
			&& class_exists('\Bitrix\HumanResources\Type\HcmLink\FieldType')
			;
	}

	public function parseName(string $serializedName): ?HcmLinkFieldParsedName
	{
		if (!str_starts_with($serializedName, self::FIELD_PREFIX))
		{
			return null;
		}

		$maxPartCount = count(FieldSerializedIndex::values());
		$explodedName = explode(self::NAME_SEPARATOR, $serializedName, $maxPartCount);

		if (count($explodedName) !== $maxPartCount)
		{
			return null;
		}

		$companyId = (int)$explodedName[FieldSerializedIndex::FIELD_COMPANY_INDEX->value];
		$type = (int)$explodedName[FieldSerializedIndex::FIELD_TYPE_INDEX->value];
		$id = (int)$explodedName[FieldSerializedIndex::FIELD_ID->value];
		$party = (int)$explodedName[FieldSerializedIndex::FIELD_PARTY_INDEX->value];

		return new HcmLinkFieldParsedName(
			integrationId: $companyId,
			id: $id,
			type: $type,
			party: $party,
		);
	}

	/**
	 * @param string $fieldName
	 *
	 * @return FieldType::*
	 */
	public function getFieldTypeByName(string $fieldName): string
	{
		if (!$this->isAvailable())
		{
			return FieldType::STRING;
		}

		$parsedName = $this->parseName($fieldName);
		if (!$parsedName)
		{
			return FieldType::STRING;
		}

		$hcmLinkType = HumanResources\Type\HcmLink\FieldType::tryFrom($parsedName->type);
		if ($hcmLinkType === null)
		{
			return FieldType::STRING;
		}

		return match ($hcmLinkType)
		{
			HumanResources\Type\HcmLink\FieldType::SNILS => FieldType::SNILS,
			HumanResources\Type\HcmLink\FieldType::FIRST_NAME => FieldType::FIRST_NAME,
			HumanResources\Type\HcmLink\FieldType::LAST_NAME => FieldType::LAST_NAME,
			HumanResources\Type\HcmLink\FieldType::PATRONYMIC_NAME => FieldType::PATRONYMIC,
			HumanResources\Type\HcmLink\FieldType::PHONE => FieldType::PHONE,
			HumanResources\Type\HcmLink\FieldType::EMAIL => FieldType::EMAIL,
			HumanResources\Type\HcmLink\FieldType::BIRTHDAY => FieldType::DATE,
			HumanResources\Type\HcmLink\FieldType::POSITION => FieldType::POSITION,
			default => FieldType::STRING,
		};
	}

	public function getFieldById(int $id): ?Item\HcmLink\Field
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return HumanResources\Service\Container::getHcmLinkFieldRepository()->getById($id);
	}

	public function getFieldValue(
		array $entityIds,
		array $fieldIds,
	): Result|HumanResources\Result\Service\HcmLink\GetFieldValueResult
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error('Integration not available'));
		}

		return HumanResources\Service\Container::getHcmLinkFieldValueService()->getFieldValue($entityIds, $fieldIds);
	}

	public function requestFieldValues(
		int $companyId,
		array $employeeIds,
		array $fieldIds,
		array $signerMemberIdByEmployeeIdMap,
	): Result|HcmLinkFieldRequestResult
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error('Integration not available'));
		}

		$result = HumanResources\Service\Container::getHcmLinkFieldValueService()
			->requestFieldValue(
				$companyId,
				$employeeIds,
				$fieldIds,
				$signerMemberIdByEmployeeIdMap
			)
		;

		if ($result instanceof HumanResources\Result\Service\HcmLink\JobServiceResult)
		{
			return new HcmLinkFieldRequestResult($result->job->id);
		}

		return $result;
	}

	public function getHcmRequiredFieldSelectorNameByType(
		int $integrationId,
		string $fieldType,
		int $party,
	): ?string
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		$hcmLinkFieldType = $this->getSpecialHcmFieldTypeBySignFieldType($fieldType);
		if ($hcmLinkFieldType === null)
		{
			return null;
		}

		$fields = HumanResources\Service\Container::getHcmLinkFieldRepository()->getByCompany($integrationId);
		foreach ($fields as $field)
		{
			if ($field->type === $hcmLinkFieldType)
			{
				return $this->mapFieldName($field, $party);
			}
		}

		return null;
	}

	public function getSpecialHcmFieldTypeBySignFieldType(string $fieldType): ?HumanResources\Type\HcmLink\FieldType
	{
		return match ($fieldType)
		{
			FieldType::SNILS => HumanResources\Type\HcmLink\FieldType::SNILS,
			FieldType::FIRST_NAME => HumanResources\Type\HcmLink\FieldType::FIRST_NAME,
			FieldType::LAST_NAME => HumanResources\Type\HcmLink\FieldType::LAST_NAME,
			FieldType::PATRONYMIC => HumanResources\Type\HcmLink\FieldType::PATRONYMIC_NAME,
			FieldType::POSITION => HumanResources\Type\HcmLink\FieldType::POSITION,
			FieldType::EXTERNAL_ID => HumanResources\Type\HcmLink\FieldType::DOCUMENT_REGISTRATION_NUMBER,
			FieldType::EXTERNAL_DATE => HumanResources\Type\HcmLink\FieldType::DOCUMENT_DATE,
			default => null,
		};
	}

	public function convertHcmLinkIntTypeToLegalInfoType(int $type): ?string
	{
		return match (HumanResources\Type\HcmLink\FieldType::tryFrom($type))
		{
			HumanResources\Type\HcmLink\FieldType::SNILS => FieldType::SNILS,
			HumanResources\Type\HcmLink\FieldType::FIRST_NAME => FieldType::FIRST_NAME,
			HumanResources\Type\HcmLink\FieldType::LAST_NAME => FieldType::LAST_NAME,
			HumanResources\Type\HcmLink\FieldType::PATRONYMIC_NAME => FieldType::PATRONYMIC,
			HumanResources\Type\HcmLink\FieldType::POSITION => FieldType::POSITION,
			default => null,
		};
	}

	public function isDateSettingFieldById(int $id): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		$field = HumanResources\Service\Container::getHcmLinkFieldRepository()->getById($id);

		return $field?->type === HumanResources\Type\HcmLink\FieldType::DOCUMENT_DATE;
	}

	public function isExternalIdSettingFieldById(int $id): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		$field = HumanResources\Service\Container::getHcmLinkFieldRepository()->getById($id);

		return $field?->type === HumanResources\Type\HcmLink\FieldType::DOCUMENT_REGISTRATION_NUMBER;
	}

	public function isDocumentTypeSettingFieldById(int $id): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		$field = HumanResources\Service\Container::getHcmLinkFieldRepository()->getById($id);

		return $field?->type === HumanResources\Type\HcmLink\FieldType::DOCUMENT_UID;
	}
}