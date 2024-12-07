<?php

namespace Bitrix\Sign\Factory;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Connector\MemberConnectorFactory;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\Field\ConnectorType;
use Bitrix\Sign\Type\Field\EntityType;
use Bitrix\Sign\Type\FieldType;

final class Field
{
	public const USER_FIELD_CODE_PREFIX = 'USER_';

	private const BLOCK_CODE_TO_FIELD_TYPE_MAP = [
		BlockCode::DATE => FieldType::STRING,
		BlockCode::TEXT => FieldType::STRING,
		BlockCode::NUMBER => FieldType::STRING,
		BlockCode::MY_STAMP => FieldType::STAMP,
		BlockCode::STAMP => FieldType::STAMP,
		BlockCode::MY_SIGN => FieldType::SIGNATURE,
		BlockCode::SIGN => FieldType::SIGNATURE,
	];

	private const NOT_REQUIRED_FIELD_TYPES = [
		FieldType::PATRONYMIC,
		FieldType::POSITION,
	];

	private const ADDRESS_SUBFIELD_CODES = [
		'ADDRESS_1',
		'ADDRESS_2',
		'CITY',
		'POSTAL_CODE',
		'REGION',
		'PROVINCE',
		'COUNTRY',
	];
	private const REQUIRED_ADDRESS_SUBFIELDS = [
		'ADDRESS_1',
		'CITY',
		'POSTAL_CODE',
	];

	private const SNILS_FIELD_CODE = 'UF_LEGAL_SNILS';

	private MemberConnectorFactory $memberConnectorFactory;

	public function __construct()
	{
		$this->memberConnectorFactory = new MemberConnectorFactory();
	}

	public static function getUserFieldValue(int $userId, array $field, string $subFieldName = '', bool $originalValue = false): mixed
	{
		global $USER_FIELD_MANAGER;

		$result = $USER_FIELD_MANAGER->GetUserFieldValue(
			entity_id: $field['entityId'],
			field_id: $field['sourceName'],
			value_id: $userId,
			LANG: LANGUAGE_ID,
		);

		if (empty($result) || $originalValue)
		{
			return $result;
		}

		if ($field['type'] === 'enumeration')
		{
			$enumResultDb = \CUserFieldEnum::GetList([], [
				'USER_FIELD_ID' => $field['userFieldId'],
				'ID' => $result,
			]);

			$userFieldCollection = [];
			while ($enumResult = $enumResultDb->Fetch())
			{
				$userFieldCollection[] = $enumResult['VALUE'];
			}

			return implode(', ', $userFieldCollection);
		}
		elseif (
			$field['type'] === 'address'
			&& Main\Loader::includeModule('fileman')
			&& Main\Loader::includeModule('location')
		)
		{
			[,, $addressId] = AddressType::parseValue($result);

			if (empty($addressId))
			{
				return '';
			}
			else
			{
				/** @var Address $address */
				$address = Address::load($addressId);
				if (!$address)
				{
					return '';
				}

				if (!empty($subFieldName))
				{
					return $address->getFieldValue(FieldType::ADDRESS_SUBFIELD_MAP[$subFieldName]);
				}
				else
				{
					return $address->toString(
						FormatService::getInstance()->findDefault(LANGUAGE_ID),
						\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
					);
				}
			}
		}

		return $result;
	}

	public function createByBlocks(
		Item\BlockCollection $blocks,
		?Item\Member $member,
		?Item\Document $document = null
	): Item\FieldCollection
	{
		if (!Main\Loader::includeModule('crm'))
		{
			return new Item\FieldCollection();
		}

		$documentRepository = Container::instance()->getDocumentRepository();
		if ($member !== null && $document === null)
		{
			$document = $documentRepository->getById($member->documentId);
		}

		$registeredFields = new Item\FieldCollection();
		foreach ($blocks as $block)
		{
			$fields = $this->createFieldsByBlock($block, $registeredFields, $member, $document);
			$registeredFields->mergeFieldsWithNoneIncludedName($fields);
		}

		return $registeredFields;
	}

	private function createFieldsByBlock(
		Item\Block $block,
		Item\FieldCollection $registeredFields,
		?Item\Member $member,
		?Item\Document $document,
	): Item\FieldCollection
	{
		$fieldType = self::BLOCK_CODE_TO_FIELD_TYPE_MAP[$block->code] ?? null;

		if (BlockCode::isCommon($block->code))
		{
			$party = $member?->party ?? 0;
			$fieldName = NameHelper::create($block->code, $fieldType, $party);

			return new Item\FieldCollection(
				new Item\Field(
					0,
					$party,
					$fieldType,
					$fieldName,
					label: null, // simple blocks doesnt contains label because it not include in form
				),
			);
		}
		if ($member === null || $document === null)
		{
			return new Item\FieldCollection();
		}

		if ($fieldType === null)
		{
			return match ($block->code)
			{
				BlockCode::REFERENCE, BlockCode::MY_REFERENCE => $this->createCrmReferenceFields(
					$block,
					$registeredFields,
					$member,
				),
				BlockCode::REQUISITES, BlockCode::MY_REQUISITES => $this->createRequisiteFields(
					$block,
					$member,
				),
				BlockCode::B2E_REFERENCE, BlockCode::B2E_MY_REFERENCE => $this->createB2eReferenceFields(
					$block,
					$member,
					$document,
					$registeredFields,
				),
			};
		}

		if (BlockCode::isSignature($block->code))
		{
			$signField = $registeredFields->findFirst(
				fn(Item\Field $field) => $field->type === FieldType::SIGNATURE && $field->party === $member->party,
			);
			if ($signField !== null)
			{
				return new Item\FieldCollection($signField);
			}
		}
		elseif (BlockCode::isStamp($block->code))
		{
			$stampField = $registeredFields->findFirst(
				fn(Item\Field $field) => $field->type === FieldType::STAMP && $field->party === $member->party,
			);
			if ($stampField !== null)
			{
				return new Item\FieldCollection($stampField);
			}
		}
		elseif ($block->code === BlockCode::NUMBER)
		{
			$numField = $registeredFields->findFirst(
				fn(Item\Field $field) => NameHelper::parse($field->name)['blockCode'] === BlockCode::NUMBER,
			);
			if ($numField !== null)
			{
				return new Item\FieldCollection($numField);
			}
		}

		$fieldName = NameHelper::create($block->code, $fieldType, $member->party);

		return new Item\FieldCollection(
			new Item\Field(
				0,
				$member->party,
				$fieldType,
				$fieldName,
				label: null, // simple blocks doesnt contains label because it not include in form
			),
		);
	}

	private function createCrmReferenceFields(
		Item\Block $block,
		Item\FieldCollection $registeredFields,
		Item\Member $member,
	): Item\FieldCollection
	{
		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = new CRM\FieldCode($fieldCode);
		$fieldDescription = $fieldCode->getDescription($member->presetId);
		$fieldType = match ($fieldDescription['TYPE'] ?? '')
		{
			FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
			FieldType::LIST => FieldType::LIST,
			FieldType::DOUBLE => FieldType::DOUBLE,
			FieldType::INTEGER => FieldType::INTEGER,
			FieldType::ADDRESS => FieldType::ADDRESS,
			default => FieldType::STRING,
		};
		$itemsDescription = $fieldDescription['ITEMS'] ?? null;
		$items = null;
		if ($itemsDescription !== null)
		{
			$items = new Item\Field\ItemCollection();
			foreach ($itemsDescription as $itemDescription)
			{
				$items->add(
					new Item\Field\Item(
						id: $itemDescription['ID'], value: $itemDescription['VALUE'],
					),
				);
			}
		}

		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode->getCode());

		$field = $registeredFields->findFirst(
			static fn(Item\Field $field) => $field->name === $fieldName,
		);

		if ($field !== null)
		{
			return new Item\FieldCollection($field);
		}

		$field = new Item\Field(
			0,
			$member->party,
			$fieldType,
			$fieldName,
			$fieldDescription['CAPTION'] ?? null,
			connectorType: ConnectorType::CRM_ENTITY,
			// todo: dont use \CCrmOwnerType
			entityType: (
				$fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartDocumentName
				|| $fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartB2eDocumentName
			)
				? EntityType::DOCUMENT
				: EntityType::MEMBER
			,
			entityCode: $fieldCode->getEntityFieldCode(),
			items: $items,
		);

		if ($field->type === FieldType::ADDRESS)
		{
			$field->subfields = $this->createAddressSubfieldsByField($field);
		}

		return new Item\FieldCollection($field);
	}

	private function createRequisiteFields(
		Item\Block $block,
		Item\Member $member,
	): Item\FieldCollection
	{
		$memberConnector = $this->memberConnectorFactory->createRequisiteConnector($member);
		if ($memberConnector === null)
		{
			return new Item\FieldCollection();
		}

		$result = new Item\FieldCollection();

		$requisiteFields = $memberConnector->fetchRequisite(
			new Item\Connector\FetchRequisiteModifier($member->presetId),
		);
		foreach ($requisiteFields as $requisiteField)
		{
			$fieldCode = new CRM\FieldCode($requisiteField->name);
			$fieldDescription = $fieldCode->getDescription() ?? [];

			$fieldType = match ($fieldDescription['TYPE'] ?? null)
			{
				FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
				FieldType::LIST => FieldType::LIST,
				FieldType::DOUBLE => FieldType::DOUBLE,
				FieldType::INTEGER => FieldType::INTEGER,
				FieldType::ADDRESS => FieldType::ADDRESS,
				default => FieldType::STRING,
			};
			$itemsDescription = $fieldDescription['ITEMS'] ?? null;
			$items = null;
			if ($itemsDescription !== null)
			{
				$items = new Item\Field\ItemCollection();
				foreach ($itemsDescription as $itemDescription)
				{
					$items->add(
						new Item\Field\Item(
							id: $itemDescription['ID'], value: $itemDescription['VALUE'],
						),
					);
				}
			}

			$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $requisiteField->name);
			$fieldEntityType = $fieldCode->getEntityTypeName() === \CCrmOwnerType::SmartDocumentName
				? EntityType::DOCUMENT
				: EntityType::MEMBER
			;
			$field = new Item\Field(
				0,
				$member->party,
				$fieldType,
				$fieldName,
				$requisiteField->label,
				ConnectorType::REQUISITE,
				// todo: dont use \CCrmOwnerType
				$fieldEntityType,
				items: $items,
			);

			if ($field->type === FieldType::ADDRESS)
			{
				$field->subfields = $this->createAddressSubfieldsByField($field);
			}

			$result->add($field);
		}

		return $result;
	}

	private function createAddressSubfieldsByField(Item\Field $field): Item\FieldCollection
	{
		$result = new Item\FieldCollection();
		$parsedParentFieldName = NameHelper::parse($field->name);

		foreach (self::ADDRESS_SUBFIELD_CODES as $addressSubfieldCode)
		{
			$field = new Item\Field(
				0, $field->party, FieldType::STRING, // all address subfields has string type
				NameHelper::create(
					$parsedParentFieldName['blockCode'],
					$parsedParentFieldName['fieldType'],
					$parsedParentFieldName['party'],
					$parsedParentFieldName['fieldCode'],
					$addressSubfieldCode,
				), $this->getAddressFieldLabels()[$addressSubfieldCode] ?? null,
			);

			$field->required = in_array($addressSubfieldCode, self::REQUIRED_ADDRESS_SUBFIELDS, true);

			$result->add($field);
		}

		return $result;
	}

	private function getAddressFieldLabels(): array
	{
		// todo: encapsulate it to another class
		if (!Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return \Bitrix\Crm\EntityAddress::getLabels();
	}

	private function createB2eReferenceFields(
		Item\Block $block,
		Item\Member $member,
		Item\Document $document,
		Item\FieldCollection $registeredFields,
	): Item\FieldCollection
	{
		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return new Item\FieldCollection();
		}

		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return new Item\FieldCollection();
		}

		$profileProvider = Container::instance()->getServiceProfileProvider();
		if (!$profileProvider->isProfileField($fieldCode))
		{
			return $this->createCrmReferenceFields($block, $registeredFields, $member);
		}

		if ($block->role === Type\Member\Role::ASSIGNEE && $document->representativeId === null)
		{
			return new Item\FieldCollection();
		}

		$fieldDescription = $profileProvider->getDescriptionByFieldName($fieldCode);
		$fieldType = match ($fieldDescription['type'] ?? '')
		{
			FieldType::SNILS => FieldType::SNILS,
			FieldType::FIRST_NAME => FieldType::FIRST_NAME,
			FieldType::LAST_NAME => FieldType::LAST_NAME,
			FieldType::PATRONYMIC => FieldType::PATRONYMIC,
			FieldType::POSITION => FieldType::POSITION,
			FieldType::DATE, FieldType::DATETIME => FieldType::DATE,
			FieldType::LIST, FieldType::ENUMERATION => FieldType::LIST,
			FieldType::DOUBLE => FieldType::DOUBLE,
			FieldType::INTEGER => FieldType::INTEGER,
			FieldType::ADDRESS => FieldType::ADDRESS,
			default => FieldType::STRING
		};

		if ($fieldCode === self::SNILS_FIELD_CODE)
		{
			$fieldType = FieldType::SNILS;
		}

		$fieldCode = self::USER_FIELD_CODE_PREFIX . $fieldCode;
		$fieldName = NameHelper::create($block->code, $fieldType, $member->party, $fieldCode);

		$field = $registeredFields->findFirst(
			static fn(Item\Field $field) => $field->name === $fieldName,
		);
		if ($field !== null)
		{
			return new Item\FieldCollection($field);
		}

		$itemCollection = new Item\Field\ItemCollection();
		if(
			$fieldType === FieldType::LIST
			&& is_array($fieldDescription['items'])
		)
		{
			foreach ($fieldDescription['items'] as $item)
			{
				$itemCollection->add(
					new Item\Field\Item(
						id: $item['id'], value: $item['value'],
					),
				);
			}
		}

		$field = new Item\Field(
			blankId: 0,
			party: $member->party,
			type: $fieldType,
			name: $fieldName,
			label: $fieldDescription['caption'] ?? '',
			items: $itemCollection->isEmpty() ? null : $itemCollection,
			required: in_array($fieldType, self::NOT_REQUIRED_FIELD_TYPES, true) ? false : null,
		);

		if ($field->type === FieldType::ADDRESS)
		{
			$field->subfields = $this->createAddressSubfieldsByField($field);
		}

		return new Item\FieldCollection($field);
	}

	public function createByRequired(
		Item\Document $document,
		Item\MemberCollection $members,
		Item\B2e\RequiredField $requiredField
	): ?Item\Field
	{
		$blockFactory = new \Bitrix\Sign\Blanks\Block\Factory();
		$legalInfoFields = (new LegalInfoProvider())->getFieldsItems();
		foreach ($legalInfoFields as $legalField)
		{
			if ($legalField->type !== $requiredField->type)
			{
				continue;
			}

			$code = BlockCode::getB2eReferenceCodeByRole($requiredField->role);
			$firstByRole = $members->findFirstByRole($requiredField->role);
			if (!$firstByRole)
			{
				return null;
			}

			$block = $blockFactory->makeItem(
				document: $document,
				code: $code,
				party: $firstByRole->party,
				data: ['field' => $legalField->name],
				skipSecurity: true,
				role: $requiredField->role,
			);

			return $this->createByBlocks(new Item\BlockCollection($block), $firstByRole)->getFirst();
		}

		return null;
	}
}
