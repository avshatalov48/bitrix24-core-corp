<?php

namespace Bitrix\Sign\Factory;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkFieldService;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Main;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Connector\Field\CrmEntity;
use CCrmOwnerType;

class FieldValue
{
	private readonly ProfileProvider $profileProvider;
	/**
	 * @var array<int, <string, array>>
	 */
	private array $fieldSetMemoryCacheByMemberId = [];
	private readonly MemberDynamicFieldInfoProvider $memberDynamicFieldProvider;
	private readonly HcmLinkFieldService $hcmLinkFieldService;

	public function __construct(
		?ProfileProvider $profileProvider = null,
		?MemberDynamicFieldInfoProvider $memberDynamicFieldProvider = null,
		?HcmLinkFieldService $hcmLinkFieldService = null,
	)
	{
		$container = Container::instance();
		$this->profileProvider = $profileProvider ?? $container->getServiceProfileProvider();
		$this->memberDynamicFieldProvider = $memberDynamicFieldProvider ?? $container->getMemberDynamicFieldProvider();
		$this->hcmLinkFieldService = $hcmLinkFieldService ?? $container->getHcmLinkFieldService();
	}

	public function createByBlock(
		Item\Block $block,
		Item\Field $field,
		Item\Member $member,
		Item\Document $document,
	): ?Item\Field\Value
	{
		if (BlockCode::isSignature($block->code) || BlockCode::isStamp($block->code))
		{
			$valueFileId = $block->data['fileId'] ?? null;

			return is_int($valueFileId) ? new Item\Field\Value(0, fileId: $valueFileId) : null;
		}
		if (BlockCode::isCommon($block->code))
		{
			$valueString = $block->data['text'] ?? null;

			return is_string($valueString) ? new Item\Field\Value(0, text: $valueString) : null;
		}

		$this->correctBlockCodeByFieldCode($block, $document);

		return match (true)
		{
			BlockCode::isReference($block->code) => $this->getCrmReferenceFieldValue($field, $member, $document),
			BlockCode::isRequisites($block->code) => $this->getRequisitesFieldValue($field, $member),
			BlockCode::isB2eReference($block->code) => $this->getB2eReferenceFieldValue($block, $field, $member, $document),
			BlockCode::isMemberDynamic($block->code) => $this->getMemberDynamicFieldValue($field, $member),
			BlockCode::isHcmLinkReference($block->code) => $this->getHcmLinkFieldValue($field, $member, $document),
			default => null,
		};
	}

	private function correctBlockCodeByFieldCode(Item\Block $block, Item\Document $document): void
	{
		if (isset($block->data['field']))
		{
			[,$fieldName] = NameHelper::parseFieldCode(
				$block->data['field'],
				\CCrmOwnerType::ResolveName($document->entityTypeId),
			);

			if (str_starts_with($fieldName, 'RQ_'))
			{
				$block->code = match($block->code) {
					\Bitrix\Sign\Type\BlockCode::B2E_MY_REFERENCE => \Bitrix\Sign\Type\BlockCode::MY_REQUISITES,
					default => $block->code,
				};
			}
		}
	}

	private function getCrmReferenceFieldValue(Item\Field $field, Item\Member $member, Item\Document $document): ?Item\Field\Value
	{
		$entityType = \CCrmOwnerType::ResolveName($document->entityTypeId);

		[
			'fieldCode' => $fieldCode,
			'fieldType' => $fieldType,
			'subfieldCode' => $subfieldCode,
		] = NameHelper::parse($field->name);

		[$entityFieldType, $fieldName] = NameHelper::parseFieldCode($fieldCode, $entityType);

		if (
			$fieldType !== FieldType::ADDRESS
			&& $fieldType !== FieldType::LIST
			&& $fieldType !== FieldType::ENUMERATION
		)
		{
			$crmEntityId = $this->getCrmEntityId($member, $document, $entityFieldType);
			if ($crmEntityId !== null)
			{
				$value = CRM::getEntityFieldValue(
					$crmEntityId,
					$fieldCode,
					$document->id,
					$member->presetId,
				);
				if ($value !== null && isset($value['text']))
				{
					return new Item\Field\Value(0, text: $value['text']);
				}
			}
		}

		if (str_starts_with($fieldName, 'UF_CRM_' . $entityType))
		{
			return $this->getDocumentFieldValue($document, $fieldName, $fieldType, $subfieldCode);
		}
		elseif (str_starts_with($fieldName, 'RQ_'))
		{
			return $this->getRequisitesFieldValue($field, $member);
		}
		elseif (
			!empty($subfieldCode)
			&& !empty($fieldType)
			&& $fieldType === FieldType::ADDRESS
		)
		{
			$value = Field::getUserFieldValue($member->entityId, [
				'entityId' => 'CRM_' . $entityFieldType,
				'sourceName' => $fieldName,
				'type' => $fieldType,
				'userFieldId' => $fieldType,
			], $subfieldCode);

			if ($value !== null)
			{
				return new Item\Field\Value(0, text: (string)$value);
			}
		}
		else
		{
			$connector = new CrmEntity($field, $member);
			$fetchedFieldValue = $connector->fetchFields()->getFirst();

			if ($fetchedFieldValue === null)
			{
				return null;
			}

			if (
				(($fetchedFieldValue->data instanceof Main\Type\DateTime) === true)
				|| is_string($fetchedFieldValue->data)
				|| is_int($fetchedFieldValue->data)
				|| is_float($fetchedFieldValue->data)
			)
			{
				return new Item\Field\Value(0, text: (string)$fetchedFieldValue->data);
			}
		}

		return null;
	}

	private function getRequisitesFieldValue(Item\Field $field, Item\Member $member): ?Item\Field\Value
	{
		[
			'fieldCode' => $fieldCode,
			'subfieldCode' => $subFieldCode,
		] = NameHelper::parse($field->name);

		$fieldsSetValue = $this->getFieldSetValues($member)[$fieldCode] ?? null;
		if ($subFieldCode !== '')
		{
			$value = $this->getFieldSetValues($member)[$fieldCode][$subFieldCode] ?? null;
			if (is_string($value))
			{
				return new Item\Field\Value(fieldId: 0, text: $value);
			}

			return null;
		}

		if (is_string($fieldsSetValue))
		{
			return new Item\Field\Value(fieldId: 0, text: $fieldsSetValue);
		}

		return null;
	}

	/**
	 * @param Item\Member $member
	 *
	 * @return array<string, array>
	 */
	private function getFieldSetValues(Item\Member $member): array
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return [];
		}
		if (!isset($this->fieldSetMemoryCacheByMemberId[$member->id]))
		{
			$this->fieldSetMemoryCacheByMemberId[$member->id] = \Bitrix\Crm\Integration\Sign\Form::getFieldSetValues(
				match ($member->entityType)
				{
					\Bitrix\Sign\Type\Member\EntityType::CONTACT => \CCrmOwnerType::Contact,
					\Bitrix\Sign\Type\Member\EntityType::COMPANY => \CCrmOwnerType::Company,
					default => 0,
				},
								   $member->entityId,
				requisitePresetId: $member->presetId,
			);;
		}

		return $this->fieldSetMemoryCacheByMemberId[$member->id];
	}

	private function getB2eReferenceFieldValue(Item\Block $block, Item\Field $field, Item\Member $member, Item\Document $document): ?Item\Field\Value
	{
		['fieldCode' => $fieldCode, 'subfieldCode' => $subfieldCode] = NameHelper::parse($field->name);
		if (str_starts_with($fieldCode, Field::USER_FIELD_CODE_PREFIX))
		{
			$fieldCode = mb_substr($fieldCode, mb_strlen(Field::USER_FIELD_CODE_PREFIX));
		}

		if (!$this->profileProvider->isProfileField($fieldCode))
		{
			return $this->getCrmReferenceFieldValue($field, $member, $document);
		}

		$entityId = $block->role === Role::ASSIGNEE
			? $document->representativeId
			: $member->entityId
		;

		$originalValue = $field->type === FieldType::LIST;

		$profileFieldData = $this->profileProvider->loadFieldData(
			userId: $entityId,
			fieldName: $fieldCode,
			subFieldName: $subfieldCode,
			originalValue: $originalValue,
		);
		if ($profileFieldData->value === '')
		{
			return null;
		}

		return new Item\Field\Value(0, text: $profileFieldData->value, trusted: $profileFieldData->isLegal);
	}

	private function getDocumentFieldValue(
		Item\Document $document,
		string $fieldName,
		string $fieldType,
		string $subfieldCode,
	): Item\Field\Value
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()?->getFactory($document->entityTypeId);
		$value = $factory->getItem($document->entityId)?->get($fieldName);

		if (
			$fieldType === FieldType::ADDRESS
			&& Main\Loader::includeModule('fileman')
			&& Main\Loader::includeModule('location')
		)
		{
			$value = $this->parseAddressSubfieldValue($value, $subfieldCode);
		}

		return new Item\Field\Value(0, text: (string)$value);
	}

	private function parseAddressSubfieldValue(mixed $value, string $subfieldCode): string
	{
		[,,$addressId] = AddressType::parseValue($value);
		if (!$addressId)
		{
			return '';
		}

		/** @var Address $address */
		$address = Address::load($addressId);
		if (!$address)
		{
			return '';
		}

		if (!empty($subfieldCode))
		{
			$value = $address->getFieldValue(FieldType::ADDRESS_SUBFIELD_MAP[$subfieldCode]);
		}
		else
		{
			$value = $address->toString(
				FormatService::getInstance()->findDefault(LANGUAGE_ID),
				\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
			);
		}

		return (string)$value;
	}

	private function getCrmEntityId(Item\Member $member, Item\Document $document, string $entityTypeName): ?int
	{
		if (
			$entityTypeName === CCrmOwnerType::CompanyName
			&& $member->entityType === \Bitrix\Sign\Type\Member\EntityType::COMPANY
		)
		{
			return $member->entityId;
		}
		elseif (
			$entityTypeName === CCrmOwnerType::ContactName
			&& $member->entityType === \Bitrix\Sign\Type\Member\EntityType::CONTACT
		)
		{
			return $member->entityId;
		}
		elseif (in_array($entityTypeName, [CCrmOwnerType::SmartB2eDocumentName, CCrmOwnerType::SmartDocumentName], true))
		{
			return $document->id;
		}

		return null;
	}

	private function getMemberDynamicFieldValue(Item\Field $field, Item\Member $member): ?Item\Field\Value
	{
		if ($member->id === null)
		{
			return null;
		}

		['fieldCode' => $fieldCode, 'subfieldCode' => $subfieldCode] = NameHelper::parse($field->name);

		$value = $this->memberDynamicFieldProvider->loadFieldData(
			memberId: $member->id,
			fieldName: $fieldCode,
			subFieldName: $subfieldCode,
			isOriginalValue: $field->type === FieldType::LIST,
		);

		if ($value === '')
		{
			return null;
		}

		return new Item\Field\Value(0, text: $value, trusted: true);
	}

	private function getHcmLinkFieldValue(
		Item\Field $field,
		Item\Member $member,
		Item\Document $document,
	): ?Item\Field\Value
	{
		if ($member->employeeId === null)
		{
			return null;
		}

		if (!$this->hcmLinkFieldService->isAvailable())
		{
			return null;
		}

		['fieldCode' => $fieldCode] = NameHelper::parse($field->name);
		$parsedName = $this->hcmLinkFieldService->parseName($fieldCode);
		if (!$parsedName)
		{
			return null;
		}
		if ($parsedName->integrationId !== $document->hcmLinkCompanyId)
		{
			return null;
		}

		$valueId = new Item\Field\HcmLink\HcmLinkFieldValueId(
			fieldId: $parsedName->id,
			employeeId: $member->employeeId,
		);

		return new Item\Field\Value(
			fieldId: 0,
			text: '',
			trusted: true,
			hcmLinkFieldValueId: $valueId,
		);
	}
}