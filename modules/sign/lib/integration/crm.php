<?php
namespace Bitrix\Sign\Integration;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\WebForm\EntityFieldProvider;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Result;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;
use Bitrix\Sign\File;
use Bitrix\Sign\Integration\CRM\FieldCode;
use Bitrix\Sign\Type\FieldType;

Loc::loadMessages(__FILE__);

class CRM
{
	/**
	 * Returns CRM statuses.
	 * @param string $entityId Entity id.
	 * @return array
	 */
	private static function getStatuses(string $entityId): array
	{
		static $statuses = [];

		$entityId = mb_strtoupper($entityId);

		if (!$statuses)
		{
			$res = \Bitrix\Crm\StatusTable::getList();
			while ($row = $res->fetch())
			{
				if (!isset($statuses[$row['ENTITY_ID']]))
				{
					$statuses[$row['ENTITY_ID']] = [];
				}

				$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row['NAME'];
			}
		}

		return $statuses[$entityId] ?? [];
	}

	/**
	 * Returns enumeration's type full variants.
	 * @param int $fieldId User field id.
	 * @return array
	 */
	private static function getEnumItems(int $fieldId): array
	{
		static $enumItems = [];

		if (!array_key_exists($fieldId, $enumItems))
		{
			$enumItems[$fieldId] = [];
			$res = \CUserFieldEnum::getList([], [
				'USER_FIELD_ID' => $fieldId,
			]);
			while ($row = $res->fetch())
			{
				$enumItems[$fieldId][$row['ID']] = $row['VALUE'];
			}
		}

		return $enumItems[$fieldId];
	}

	/**
	 * Prepares value of user field.
	 * @param mixed $value Field value.
	 * @param string $entityType Entity type.
	 * @param string $fieldCode Field code.
	 * @return mixed
	 */
	private static function prepareUfFieldValue($value, string $entityType, string $fieldCode)
	{
		static $entities = [];

		if (!array_key_exists($entityType, $entities))
		{
			$res = \CUserTypeEntity::GetList([], [
				'ENTITY_ID' => 'CRM_' . $entityType,
			]);
			while ($row = $res->fetch())
			{
				$entities[$entityType][$row['FIELD_NAME']] = $row;
			}
		}

		if ($entities[$entityType][$fieldCode] ?? null)
		{
			$typeId = $entities[$entityType][$fieldCode]['USER_TYPE_ID'];
			$value =
				is_object($value)
					? $value
					: (array)$value;

			if ($typeId === 'boolean')
			{
				$value = array_map(static function ($itemVal)
				{
					return Loc::getMessage('SIGN_CORE_INTEGRATION_UF_FIELD_BOOLEAN_' . $itemVal);
				},
					$value);
			}
			elseif ($typeId === 'enumeration')
			{
				$enumItems = self::getEnumItems($entities[$entityType][$fieldCode]['ID']);
				$value = array_map(function ($itemVal) use ($enumItems)
				{
					return $enumItems[$itemVal] ?? $itemVal;
				},
					$value);
			}
			elseif ($typeId === 'crm_status')
			{
				$statusType = $entities[$entityType][$fieldCode]['SETTINGS']['ENTITY_TYPE'] ?? null;
				if ($statusType)
				{
					$statuses = self::getStatuses($statusType);
					$value = array_map(function ($itemVal) use ($statuses)
					{
						return $statuses[$itemVal] ?? $itemVal;
					},
						$value);
				}
			}
			elseif ($typeId === 'file')
			{
				$value = array_shift($value);
				if ($value > 0)
				{
					$file = (new File((int)$value));
					if ($file->isImage())
					{
						$value = $file->getUriPath();
					}
				}
			}
			elseif ($typeId === 'datetime' && $value instanceof \Bitrix\Main\Type\DateTime)
			{
				$value = (string)$value;
			}
			elseif (
				$typeId === 'address'
				&& is_array($value)
				&& Loader::includeModule('fileman')
				&& Loader::includeModule('location')
			)
			{
				[,, $addressId] = AddressType::parseValue(array_shift($value));

				$value = '';
				if ($addressId)
				{
					/** @var Address $address */
					$address = Address::load($addressId);
					if ($address)
					{
						if (!empty($subFieldName))
						{
							$value = $address->getFieldValue(FieldType::ADDRESS_SUBFIELD_MAP[$subFieldName]);
						}
						else
						{
							$value = $address->toString(
								FormatService::getInstance()->findDefault(LANGUAGE_ID),
								\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA
							);
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * By field code resolves entity and returns its value from specified entity.
	 *
	 * @param int $entityId Entity id.
	 * @param string $fieldCode Field code.
	 * @return array|null <text|src => ...>
	 */
	public static function getEntityFieldValue(
		int $entityId,
		string $fieldCode,
		?int $documentId = null,
		?int $presetId = null
	): ?array
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		$fieldCodeInstance = new FieldCode($fieldCode);
		$entityTypeName = $fieldCodeInstance->getEntityTypeName();
		$entityTypeId = $fieldCodeInstance->getEntityTypeId();
		$entityFieldCode = $fieldCodeInstance->getEntityFieldCode();

		if (in_array(null, [$entityTypeName, $entityTypeId, $entityFieldCode], true))
		{
			return null;
		}

		$isPicture = false;

		// skip some bad fields
		if (in_array($entityFieldCode, ['ORIGIN_VERSION', 'LINK']))
		{
			return null;
		}

		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		// communications
		$communicationTypes = ['PHONE', 'EMAIL', 'WEB', 'IM'];
		if (in_array($entityFieldCode, $communicationTypes))
		{
			$communications = self::getCommunications(
				$entityId,
				$factory->getEntityName(),
				$communicationTypes
			);
			foreach ($communications as $communication)
			{
				if ($communication['type'] === $entityFieldCode)
				{
					return ['text' => $communication['value']];
				}
			}

			return null;
		}

		// base field
		$item = $factory->getItem($entityId);
		if (!$item)
		{
			return null;
		}

		$value = null;
		if ($factory->isFieldExists($entityFieldCode))
		{
			try
			{
				$value = $item->get($entityFieldCode);
			}
			catch (\Bitrix\Main\ArgumentException $exception)
			{
				return null;
			}
		}
		elseif($documentId !== null && $presetId !== null)
		{
			$value = self::getRequisitesEntityFieldSetValues($entityTypeId, $entityId, $presetId)[$fieldCode]['value'] ?? null;
		}

		if ($value === null)
		{
			return null;
		}

		$refRestStatuses = [
			'TYPE_ID' => 'CONTACT_TYPE',
			'COMPANY_TYPE' => 'COMPANY_TYPE',
			'EMPLOYEES' => 'EMPLOYEES',
			'INDUSTRY' => 'INDUSTRY',
			'SOURCE_ID' => 'SOURCE',
			'HONORIFIC' => 'HONORIFIC',
		];

		if (str_starts_with($entityFieldCode, 'UF_CRM_'))
		{
			$value = self::prepareUfFieldValue($value, $entityTypeName, $entityFieldCode);
			if (is_string($value) && str_starts_with($value, 'http'))
			{
				$isPicture = true;
			}
		}
		elseif ($refRestStatuses[$entityFieldCode] ?? null)
		{
			$statuses = self::getStatuses($refRestStatuses[$entityFieldCode]);
			$value = array_map(
				static function ($itemVal) use ($statuses) {
					return $statuses[$itemVal] ?? $itemVal;
				},
				(array)$value
			);
		}
		elseif ($entityFieldCode === 'PHOTO')
		{
			if (is_array($value))
			{
				$value = array_shift($value);
			}
			if ($value > 0)
			{
				$isPicture = true;
				$value = (new File((int)$value))->getUriPath();
			}
		}

		$key = $isPicture ? 'src' : 'text';

		if (is_array($value))
		{
			return [$key => implode(' ', $value)];
		}

		return [$key => (string)$value];
	}

	/**
	 * Returns full list of CRM entity fields.
	 * @return array
	 */
	public static function getEntityFields(): array
	{
		$fields = [];

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return $fields;
		}

		$fields = EntityFieldProvider::getFieldsTree();
		foreach ($fields as $key => $item)
		{
			if (strpos($key, 'DYNAMIC_') === 0)
			{
				$dynamicId = str_replace('DYNAMIC_', '', $key);
				$fields[$key]['DYNAMIC_ID'] = \CCrmOwnerType::resolveUserFieldEntityID($dynamicId);
			}
		}

		return $fields;
	}

	/**
	 * Returns contact detail url.
	 * @param string $id Contact id or marker to replace.
	 * @return string
	 */
	public static function getContactUrl(string $id): string
	{
		$path = '/crm/contact/details/#id#/';

		return str_replace('#id#', $id, $path);
	}

	/**
	 * Returns company detail url.
	 * @param string $id Company id or marker to replace.
	 * @return string
	 */
	public static function getCompanyUrl(string $id): string
	{
		$path = '/crm/company/details/#id#/';

		return str_replace('#id#', $id, $path);
	}

	/**
	 * Returns crm entity communications.
	 * @param int $entityId Entity id.
	 * @param string $entityType Entity type.
	 * @param array $types Communications type allowed.
	 * @return array
	 */
	private static function getCommunications(int $entityId, string $entityType, array $types = ['PHONE', 'EMAIL']): array
	{
		$communications = [];

		$res = \Bitrix\Crm\FieldMultiTable::getList([
			'filter' => [
				'=ENTITY_ID' => $entityType,
				'=ELEMENT_ID' => $entityId,
				'@TYPE_ID' => $types,
			],
			'order' => [
				'TYPE_ID' => 'asc',
			],
		]);
		while ($row = $res->fetch())
		{
			$communications[] = [
				'type' => $row['TYPE_ID'],
				'value' => $row['VALUE'],
			];
		}

		return $communications;
	}

	/**
	 * Returns crm contact communications.
	 * @param int $entityId Entity id
	 * @return array
	 */
	public static function getContactCommunications(int $entityId): array
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return self::getCommunications($entityId, \CCrmOwnerType::ContactName);
	}

	/**
	 * Returns crm company communications.
	 * @param int $entityId Entity id
	 * @return array
	 */
	public static function getCompanyCommunications(int $entityId): array
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return self::getCommunications($entityId, \CCrmOwnerType::CompanyName);
	}

	/**
	 * Returns form html code.
	 * @param string $documentHash Document hash.
	 * @param string $memberHash Member hash.
	 * @return array|null
	 */
	public static function getFormCode(string $documentHash, string $memberHash): ?array
	{
		// resolve doc and member
		$document = Document::getByHash($documentHash);
		$member = $document ? $document->getMemberByHash($memberHash) : null;
		if (!$document || !$member)
		{
			Error::getInstance()->addError(
				'DOCUMENT_FORM_NOT_FOUND',
				Loc::getMessage('SIGN_CORE_INTEGRATION_ERROR_DOCUMENT_FORM_NOT_FOUND')
			);

			return null;
		}

		return CRM\Form::getCode($document, $member);
	}

	/**
	 * Saves requisite field of crm entity.
	 * @param int $entityId Entity id.
	 * @param int $entityType Entity type.
	 * @param string $fieldCode Field code.
	 * @param File $file File instance.
	 * @return bool
	 */
	private static function upsertRequisiteField(int $entityId, int $entityType, string $fieldCode, File $file): bool
	{
		// retrieve current file to delete
		$fileOld = self::getRequisiteFile($entityId, $entityType, $fieldCode);

		$defaultRequisite = new DefaultRequisite(new ItemIdentifier($entityType, $entityId));
		$result = $defaultRequisite->upsertField($fieldCode, $file->getId());
		if (!$result->isSuccess())
		{
			$error = $result->getErrors()[0];
			Error::getInstance()->addError(
				$error->getCode(),
				$error->getMessage()
			);
		}
		else if ($fileOld && $fileOld->isExist())
		{
			$fileOld->unlink();
		}

		return $result->isSuccess();
	}

	/**
	 * Returns requisite field of crm entity.
	 * @param int $entityId Entity id.
	 * @param int $entityType Entity type.
	 * @param string $fieldCode Field code.
	 * @return File|null
	 */
	private static function getRequisiteFile(int $entityId, int $entityType, string $fieldCode): ?File
	{
		try
		{
			$defaultRequisite = new DefaultRequisite(new ItemIdentifier($entityType, $entityId));
		}
		catch (ArgumentOutOfRangeException $e)
		{
			return null;
		}
		catch (NotSupportedException $e)
		{
			return null;
		}

		$fields = $defaultRequisite->get();

		if ($fields[$fieldCode] ?? null)
		{
			return new File((int)$fields[$fieldCode]);
		}

		return null;
	}

	/**
	 * Saves company signature.
	 * @param int $companyId Company id.
	 * @param File $file File instance.
	 * @return bool
	 */
	public static function saveCompanySignature(int $companyId, File $file): bool
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return self::upsertRequisiteField($companyId, \CCrmOwnerType::Company, 'RQ_SIGNATURE', $file);
		}

		return false;
	}

	/**
	 * Saves company stamp.
	 * @param int $companyId Company id.
	 * @param File $file File instance.
	 * @return bool
	 */
	public static function saveCompanyStamp(int $companyId, File $file): bool
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return self::upsertRequisiteField($companyId, \CCrmOwnerType::Company, 'RQ_STAMP', $file);
		}

		return false;
	}

	/**
	 * Returns company signature.
	 * @param int $companyId Company id.
	 * @return File|null
	 */
	public static function getCompanySignature(int $companyId): ?File
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		return self::getRequisiteFile($companyId, \CCrmOwnerType::Company, 'RQ_SIGNATURE');
	}

	/**
	 * Returns company stamp.
	 * @param int $companyId Company id.
	 * @return File|null
	 */
	public static function getCompanyStamp(int $companyId): ?File
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		return self::getRequisiteFile($companyId, \CCrmOwnerType::Company, 'RQ_STAMP');
	}

	/**
	 * Returns contact signature.
	 * @param int $contactId Contact id.
	 * @return File|null
	 */
	public static function getContactSignature(int $contactId): ?File
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		return self::getRequisiteFile($contactId, \CCrmOwnerType::Contact, 'RQ_SIGNATURE');
	}

	/**
	 * Returns contact stamp.
	 * @param int $contactId Contact id.
	 * @return File|null
	 */
	public static function getContactStamp(int $contactId): ?File
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		return self::getRequisiteFile($contactId, \CCrmOwnerType::Contact, 'RQ_STAMP');
	}

	/**
	 * Returns field set id for crm requisites for contact.
	 * @param int $entityTypeId Entity type id.
	 * @return int
	 */
	private static function getRequisitesEntityFieldSetId(int $entityTypeId): int
	{
		$item = \Bitrix\Crm\Integration\Sign\Form::getFieldSet($entityTypeId);
		if ($item)
		{
			return $item->getId();
		}

		return 0;
	}

	/**
	 * Returns field set id for crm requisites for company.
	 * @return int
	 */
	public static function getRequisitesCompanyFieldSetId(): int
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return 0;
		}

		return self::getRequisitesEntityFieldSetId(\CCrmOwnerType::Company);
	}

	/**
	 * Returns field set id for crm requisites for contact.
	 * @return int
	 */
	public static function getRequisitesContactFieldSetId(): int
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return 0;
		}

		return self::getRequisitesEntityFieldSetId(\CCrmOwnerType::Contact);
	}

	/**
	 * Returns crm requisites values for entity.
	 *
	 * @param int $entityTypeId Entity type id.
	 * @param int $entityId Entity id.
	 * @param int $presetId
	 * @return array
	 */
	public static function getRequisitesEntityFieldSetValues(int $entityTypeId, int $entityId, ?int $presetId): array
	{
		$return = [];

		$values = \Bitrix\Crm\Integration\Sign\Form::getFieldSetValues(
			$entityTypeId,
			$entityId,
			[],
			$presetId
		);
		$item = \Bitrix\Crm\Integration\Sign\Form::getFieldSet($entityTypeId, $presetId);
		if ($item)
		{
			foreach ($item->getFields() as $field)
			{
				$code = $field['name'];

				if (is_array($values) && array_key_exists($code, $values))
				{
					if (is_array($values[$code]) && isset($values[$code]['CITY']))
					{
						$address = AddressFormatter::getSingleInstance()
							->formatTextComma($values[$code]);

						$return[$code] = [
							'label' => $field['label'],
							'value' => $address,
						];
						continue;
					}

					$return[$code] = [
						'label' => $field['label'],
						'value' => is_array($values[$code]) ? array_shift($values[$code]) : $values[$code],
					];
				}
			}
		}

		return $return;
	}

	/**
	 * Returns crm requisites values for company.
	 *
	 * @param Document $document
	 * @return array Array<label, value>.
	 * @throws LoaderException
	 */
	public static function getRequisitesCompanyFieldSetValues(Document $document): array
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return self::getRequisitesEntityFieldSetValues(
			\CCrmOwnerType::Company,
			$document->getCompanyId(),
			self::getMyDefaultPresetId(
				$document->getEntityId(),
				$document->getCompanyId()
			)
		);
	}

	/**
	 * Returns crm requisites values for contact.
	 * @param int $contactId Contact id.
	 * @return array Array<label, value>.
	 */
	public static function getRequisitesContactFieldSetValues(int $contactId, Document $document): array
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return [];
		}

		return self::getRequisitesEntityFieldSetValues(
			\CCrmOwnerType::Contact,
			$contactId,
			self::getOtherSidePresetId($document->getEntityId())
		);
	}

	/**
	 * Returns url to open numerator settings.
	 * @return string|null
	 */
	public static function getNumeratorUrl(): ?string
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$field = \Bitrix\Crm\Service\Container::getInstance()
				->getFactory(\CCrmOwnerType::SmartDocument)
				->getFieldsCollection()
				->getField(\Bitrix\Crm\Item\SmartDocument::FIELD_NAME_NUMBER)
			;

			$numerator = $field->getNumerator();
			if ($numerator)
			{
				$numeratorSettingsUrl = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getNumeratorSettingsUrl(
					$numerator->getId(),
					$field->getNumeratorType(),
				);
				if ($numeratorSettingsUrl)
				{
					$numeratorSettingsUrl->addParams([
						'IS_HIDE_NUMERATOR_NAME' => 1,
					]);
					return $numeratorSettingsUrl->getLocator();
				}
			}

		}

		return null;
	}

	/**
	 * Returns director's name from requisites.
	 * @param int $companyId Company id.
	 * @return string|null
	 */
	public static function getDirectorName(int $companyId): ?string
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$defaultRequisite = new DefaultRequisite(new ItemIdentifier(\CCrmOwnerType::Company, $companyId));
			$fields = $defaultRequisite->get();

			return $fields['RQ_DIRECTOR'] ?? null;
		}

		return null;
	}

	/**
	 * Returns crm owner type id for company.
	 * @return int
	 */
	public static function getOwnerTypeCompany(): int
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return \CCrmOwnerType::Company;
		}

		return 0;
	}

	/**
	 * Returns crm owner type id for contact.
	 * @return int
	 */
	public static function getOwnerTypeContact(): int
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			return \CCrmOwnerType::Contact;
		}

		return 0;
	}

	public static function getMyDefaultPresetId(
		int $documentEntityId,
		int $companyId = 0,
		?int $documentEntityTypeId = null,
		bool $checkCrmPermissions = true,
	): ?int
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		$requisites = $documentEntityTypeId === \CCrmOwnerType::SmartB2eDocument
			? null
			: self::getLinkedRequisites($documentEntityId, 'MC_REQUISITE_ID')
		;

		if (!$requisites && $companyId > 0)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $companyId)
			);
			$defaultRequisite->setCheckPermissions($checkCrmPermissions);

			$requisites = $defaultRequisite->get();
		}

		return $requisites['PRESET_ID'] ?? null;
	}

	/**
	 * @param int $documentEntityId
	 *
	 * @return int|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getOtherSidePresetId(int $documentEntityId): ?int
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return null;
		}

		$requisites = self::getLinkedRequisites($documentEntityId, 'REQUISITE_ID');

		return $requisites['PRESET_ID'] ?? null;
	}

	public static function createDefaultRequisite(int $documentEntityId, int $entityId, int $entityType): AddResult|Result
	{
		$result = new Result();
		$defaultPresetId = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Company);

		$preset = EntityPreset::getSingleInstance()->getById($defaultPresetId);

		$requisiteFields = [];
		$requisiteFields['ENTITY_TYPE_ID'] = $entityType;
		$requisiteFields['ENTITY_ID'] = $entityId;
		$requisiteFields['PRESET_ID'] = $preset['ID'];
		$requisiteFields['NAME'] = $preset['NAME'];

		$saveRequisiteResult = EntityRequisite::getSingleInstance()->add(
			$requisiteFields,
			['DISABLE_REQUIRED_USER_FIELD_CHECK' => true]
		);

		if (!$saveRequisiteResult->isSuccess())
		{
			$result->addErrors($saveRequisiteResult->getErrors());
		}

		$requisites = self::getLinkedRequisites($documentEntityId, 'MC_REQUISITE_ID');
		EntityLink::register(
			entityTypeId: \CCrmOwnerType::SmartDocument,
			entityId: $documentEntityId,
			requisiteId: $saveRequisiteResult->getId(),
			mcRequisiteId: $requisites['ID'] ?? 0,
		);

		return $saveRequisiteResult;
	}

	/**
	 * @param int $documentEntityId
	 * @param string $requisiteField
	 *
	 * @return array|null
	 * @throws ArgumentException
	 */
	public static function getLinkedRequisites(int $documentEntityId, string $requisiteField): ?array
	{
		$requisites = [];
		$link = EntityLink::getByEntity(\CCrmOwnerType::SmartDocument, $documentEntityId);
		$linkedRequisiteId = null;

		if ($link)
		{
			$requisiteId = $link[$requisiteField] ?? null;
			$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
		}

		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}

		return $requisites;
	}

	public static function validatePresetFields(int $presetId): Result
	{
		$result = new Result();
		$presetData = EntityPreset::getSingleInstance()->getById($presetId);

		$fields = [];
		if ($presetData && isset($presetData['SETTINGS']) && is_array($presetData['SETTINGS']))
		{
			$fields = EntityPreset::getSingleInstance()->settingsGetFields($presetData['SETTINGS']);
		}

		if (empty($fields))
		{
			return $result->addError(new \Bitrix\Main\Error(
				Loc::getMessage(
					'SIGN_CORE_INTEGRATION_CRM_TEMPLATE_FIELDS_EMPTY',
					[
						'{presetName}' => $presetData['NAME'],
					]
				),
				'SIGN_DOCUMENT_CRM_TEMPLATE_FIELDS_EMPTY',
				[
					'href' => '/crm/configs/preset/'.\CCrmOwnerType::Requisite.'/edit/'.$presetId.'/',
					'button' => Loc::getMessage('SIGN_CORE_INTEGRATION_CRM_TEMPLATE_FIELDS_ADD_ADDITIONAL'),
				]
			));
		}

		return $result;
	}
}
