<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Crm\Item;
use Bitrix\Crm\Multifield;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\WebForm\Requisite;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main;
use Bitrix\Main\Result;
use Bitrix\Sign;
use Bitrix\Sign\Contract;
use Bitrix\Sign\File;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Type\Member\EntityType;
use CCrmOwnerType;

class FillFields implements Contract\Operation
{
	private const USER_FIELD_PREFIX = 'UF_';
	private const SUPPORTED_MEMBER_ENTITY_TYPES = [
		EntityType::COMPANY,
		EntityType::CONTACT,
		EntityType::USER,
	];

	private static array $resolvedItems = [];

	private DocumentRepository $documentRepository;
	private Service\Providers\ProfileProvider $profileProvider;
	private Service\Providers\MemberDynamicFieldInfoProvider $memberDynamicFieldProvider;

	/**
	 * @param list<array{name: string, value: string}> $fields
	 * @param Sign\Item\Member $member
	 * @param DocumentRepository|null $documentRepository
	 * @param Service\Providers\ProfileProvider|null $profileProvider
	 */
	public function __construct(
		private readonly array $fields,
		private readonly Sign\Item\Member $member,
		?DocumentRepository $documentRepository = null,
		?Service\Providers\ProfileProvider $profileProvider = null,
		?Service\Providers\MemberDynamicFieldInfoProvider $memberDynamicFieldProvider = null,
	)
	{
		$container = Service\Container::instance();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->profileProvider = $profileProvider ?? $container->getServiceProfileProvider();
		$this->memberDynamicFieldProvider = $memberDynamicFieldProvider ?? $container->getMemberDynamicFieldProvider();
	}

	public function launch(): Result
	{
		if (!$this->isSupportedEntityType())
		{
			return new Result();
		}

		$document = $this->documentRepository->getById($this->member->documentId);
		if ($document === null)
		{
			return (new Result())->addError(new Main\Error("Member document doesnt exist"));
		}

		$parsedFields = $this->parseFields();

		$fieldsByEntityType = [];
		foreach ($parsedFields as $fieldCode => $field)
		{
			if ($this->isUserProfileField($fieldCode))
			{
				$this->updateUserProfileField($field, $document, $fieldCode);
				continue;
			}

			if ($this->isMemberDynamicField($fieldCode))
			{
				$this->updateMemberDynamicField($field, $fieldCode);

				continue;
			}

			[$field, $entityName, $item, $parsedFieldName] = $this->extractFieldItem($field, $document);
			$field = $this->checkFieldIsFile($field);

			if (!$item)
			{
				continue;
			}

			$fieldName = $field['name'] ?? '';
			if (str_starts_with($fieldName, $entityName . "_"))
			{
				$fieldName = $parsedFieldName;
			}

			if (
				str_starts_with($fieldName, 'UF_')
				&& $field['type'] === FieldType::ADDRESS
				&& is_array($field['value'])
			)
			{
				$field['value'] = $this->getAddressUserFieldValue($fieldName, $field, $entityName, $document);
				unset($field['subName']);
			}

			if (isset($field['subName']) && !str_starts_with($fieldName, 'RQ_'))
			{
				$fieldsByEntityType[$field['entityTypeId']][$fieldName][$field['subName']] = $field['value'];
			}
			else
			{
				$fieldsByEntityType[$field['entityTypeId']][$fieldName] = $field['value'];
			}
		}

		if (!empty($fieldsByEntityType))
		{
			return $this->updateEntityField($fieldsByEntityType, $document);
		}

		return new Result();
	}

	private function isSupportedEntityType(): bool
	{
		return in_array(
			mb_strtolower($this->member->entityType),
			self::SUPPORTED_MEMBER_ENTITY_TYPES,
			true
		);
	}

	private function resolveByEntity(string $entityTypeId, int $entityId): ?Item
	{
		if (!empty(static::$resolvedItems[$entityTypeId][$entityId]))
		{
			return static::$resolvedItems[$entityTypeId][$entityId];
		}

		static::$resolvedItems[$entityTypeId][$entityId] = Container::getInstance()
			?->getFactory($entityTypeId)
			?->getItem($entityId);

		return static::$resolvedItems[$entityTypeId][$entityId];
	}

	private function checkFieldIsFile(array $field): array
	{
		if (in_array($field['type'], [FieldType::SIGNATURE, FieldType::STAMP, FieldType::FILE]))
		{
			$file = new File([
				'name' => $field['value']['name'],
				'type' => $field['value']['type'],
				'content' => $field['value']['content'],
			]);
			$field['value'] = $file->save();
		}

		return $field;
	}

	private function setItemField(Item $item, string $fieldName, mixed $value): void
	{
		try
		{
			if ($fieldName === 'FM')
			{
				$fm = $item->getFm();
				foreach ($value as $typeId => $dataValue)
				{
					foreach ($fm as $multiField)
					{
						if ($multiField->getTypeId() === $typeId && $dataValue === $multiField->getValue())
						{
							continue 2;
						}
					}
					if ($dataValue !== null && !is_string($dataValue))
					{
						continue;
					}

					$fm->add((new Multifield\Value())
						->setTypeId($typeId)
						->setValueType('WORK')
						->setValue($dataValue))
					;

				}
				$value = $fm;
			}

			$item->set($fieldName, $value);
			$item->save();
		}
		catch (Main\ArgumentException $e)
		{
		}
	}

	private function updateEntityField(array $fieldsByEntityType, Document $document): Result
	{
		foreach ($fieldsByEntityType as $entityTypeId => $values)
		{
			[$rqValues, $entityValues] = Requisite::separateFieldValues($entityTypeId, $values);
			if ($rqValues)
			{
				Requisite::instance()->fill(
					$entityTypeId,
					$this->member->entityId,
					$rqValues,
					$this->member->presetId
				);
			}

			$entityId = in_array($entityTypeId, [CCrmOwnerType::SmartDocument,  CCrmOwnerType::SmartB2eDocument], true)
				? $document->entityId
				: $this->member->entityId
			;
			$item = $this->resolveByEntity($entityTypeId, $entityId);

			if ($item !== null)
			{
				foreach ($entityValues as $entityFieldKey => $entityFieldValue)
				{
					$this->setItemField($item, $entityFieldKey, $entityFieldValue);
				}
			}
		}

		return new Result();
	}

	/**
	 * @param array $field
	 * @param \Bitrix\Sign\Item\Document|null $document
	 *
	 * @return array{0: array, 1:string, 2:Item|null, 3:string}
	 */
	public function extractFieldItem(array $field, ?Sign\Item\Document $document): array
	{
		$explodedField = explode('.', $field['name']);
		$field['name'] = mb_strstr($field['name'], '.', true) ? : $field['name'];

		if (isset($explodedField[1]) && $explodedField[1] === FieldType::ADDRESS)
		{
			$field['subName'] = $explodedField[count($explodedField) - 1];
		}

		[$fieldEntityType, $fieldName] = Sign\Helper\Field\NameHelper::parseFieldCode(
			$field['name'],
			\CCrmOwnerType::ResolveName($document->entityTypeId),
		);

		foreach ([
			\CCrmFieldMulti::PHONE,
			\CCrmFieldMulti::EMAIL,
			\CCrmFieldMulti::LINK,
			\CCrmFieldMulti::WEB,
			\CCrmFieldMulti::IM,
		] as $type)
		{
			if (str_starts_with($field['name'], $fieldEntityType . '_' . $type))
			{
				$field['subName'] = $type;
				$field['name'] = 'FM';
				break;
			}
		}

		$entityTypeId = CCrmOwnerType::ResolveId($fieldEntityType);

		$entityId = in_array($fieldEntityType, [\CCrmOwnerType::SmartDocumentName, \CCrmOwnerType::SmartB2eDocumentName], true)
			? $document->entityId
			: $this->member->entityId
		;
		$item = $this->resolveByEntity(
			$entityTypeId,
			$entityId
		);

		if (!$item)
		{
			$entityTypeId = CCrmOwnerType::ResolveId($this->member->entityType);
			$item = $this->resolveByEntity(
				$entityTypeId,
				$this->member->entityId
			);
		}

		$field['entityTypeId'] = $entityTypeId;

		return [$field, $fieldEntityType, $item, $fieldName];
	}

	private function isUserProfileField(string $fieldCode): bool
	{
		return $this->profileProvider->isFieldCodeUserProfileField($fieldCode);
	}

	private function updateUserProfileField(array $field, Document $document, string $fieldCode): void
	{
		$fieldName = $this->profileProvider->getProfileFieldNameByFieldCode($fieldCode);
		$value = $field['value'] ?? null;
		if ($value === null)
		{
			return;
		}
		$userId = $this->getUserId($document, $this->member);
		if ($userId === null)
		{
			return;
		}

		if ($field['type'] === FieldType::ADDRESS)
		{
			$field['value'] = $this->convertKeysToLocationFormat($field['value']);
		}


		$this->profileProvider->updateFieldData($userId, $fieldName, $field['value']);
	}

	private function getUserId(Document $document, Sign\Item\Member $member): ?int
	{
		if ($member->entityType === EntityType::USER)
		{
			return $member->entityId;
		}

		return $document->representativeId;
	}

	private function prepareUpdateAddressType(array $value, ?int $addressId, array $coords): string|bool
	{
		try
		{
			$result = [
				'languageId' => LANGUAGE_ID,
				'latitude' => $coords['latitude'],
				'longitude' => $coords['longitude'],
				'fieldCollection' => $value,
				'links' => [],
				'location' => null,
			];

			if ($addressId !== null)
			{
				$result['id'] = $addressId;
			}

			return Main\Web\Json::encode($result, JSON_UNESCAPED_UNICODE);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	private function getAddressUserFieldValue(
		string $fieldName,
		array $field,
		string $entityName,
		Document $document
	): string|bool
	{
		global $USER_FIELD_MANAGER;

		$value = $this->convertKeysToLocationFormat($field['value']);

		$factory = Container::getInstance()?->getFactory($document->entityTypeId);

		if (
			in_array($document->entityTypeId, [CCrmOwnerType::SmartB2eDocument, CCrmOwnerType::SmartDocument], true)
			&& str_starts_with($fieldName, 'UF_' . $factory->getUserFieldEntityId())
		)
		{
			$ufValue = $factory->getItem($document->entityId)?->get($fieldName);
		}
		else
		{
			$ufValue = $USER_FIELD_MANAGER->GetUserFieldValue(
				entity_id: $entityName,
				field_id: $fieldName,
				value_id: $this->member->entityId,
				LANG: LANGUAGE_ID
			);
		}

		if ($ufValue)
		{
			[
				,
				$coords,
				$addressId
			] = AddressType::parseValue($ufValue);
			if ($addressId)
			{
				return (string)$this->prepareUpdateAddressType(
					$value,
					$addressId,
					[
						'longitude' => $coords[1] ?? '',
						'latitude' => $coords[0] ?? '',
					]
				);
			}
		}
		else
		{
			return (string)$this->prepareUpdateAddressType(
				$value,
				null,
				[
					'longitude' => '',
					'latitude' => '',
				]
			);
		}

		return '';
	}

	/**
	 * @return array<string, array{name: string, value: string|array, type: string}>
	 */
	private function parseFields(): array
	{
		$parsedFields = [];
		foreach ($this->fields as $field)
		{
			[
				'fieldCode' => $fieldCode,
				'fieldType' => $fieldType,
				'subfieldCode' => $subfieldCode
			] = Sign\Helper\Field\NameHelper::parse($field['name']);

			if (isset($parsedFields[$fieldCode]))
			{
				if ($fieldType === FieldType::ADDRESS)
				{
					$parsedFields[$fieldCode]['value'][$subfieldCode] = $field['value'];
				}
			}
			else
			{
				$field['type'] = $fieldType;

				if ($fieldType === FieldType::ADDRESS)
				{
					$value = $field['value'];
					$field['value'] = [];
					$field['value'][$subfieldCode] = $value;
				}

				$parsedFields[$fieldCode] = $field;
			}
		}

		return $parsedFields;
	}

	public function convertKeysToLocationFormat(array $value): array
	{
		$result = [];
		foreach ($value as $key => $item)
		{
			$result[FieldType::ADDRESS_SUBFIELD_MAP[$key]] = $item;
		}

		return $result;
	}

	private function isMemberDynamicField(string $fieldCode): bool
	{
		return $this->memberDynamicFieldProvider->isFieldCodeMemberDynamicField($fieldCode);
	}

	private function updateMemberDynamicField(array $field, string $fieldCode): void
	{
		$value = $field['value'] ?? null;
		if ($value === null)
		{
			return;
		}

		if ($field['type'] === FieldType::ADDRESS)
		{
			$value = $this->convertKeysToLocationFormat($value);
		}

		$this->memberDynamicFieldProvider->updateFieldData($this->member->id, $fieldCode, $value);
	}
}
