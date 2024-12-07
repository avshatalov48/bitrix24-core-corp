<?php

namespace Bitrix\CrmMobile\Command;

use Bitrix\Crm\Currency;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Field;
use Bitrix\Crm\FileUploader\EntityFieldController;
use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\Multifield\Type\Link;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\CrmMobile\ProductGrid\UpdateCatalogProductsCommand;
use Bitrix\Location\Entity\Address;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\FileInputUtility;
use Bitrix\Mobile\Command;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;
use CCrmOwnerType;

Loader::requireModule('crm');

final class SaveEntityCommand extends Command
{
	use ReadsApplicationErrors;

	private Factory $factory;
	private Item $entity;
	private array $data;
	private ?Context $context;

	private array $temporaryFiles = [];

	public function __construct(Factory $factory, Item $entity, array $data, ?Context $context = null)
	{
		$this->factory = $factory;
		$this->entity = $entity;
		$this->data = $data;
		$this->context = $context;
	}

	public function execute(): Result
	{
		return $this->transaction(function () {
			$result = $this->prepareData($this->data);
			if (!$result->isSuccess())
			{
				return $result;
			}

			$fields = $result->getData();
			$entityTypeId = $this->factory->getEntityTypeId();

			if (!CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
			{
				$error = new Error(Loc::getMessage('MOBILE_INTEGRATION_CRM_COMMAND_UNSUPPORTED_ENTITY_ERROR'));

				return (new Result())->addError($error);
			}

			return $this->saveFactoryBasedEntities($fields);
		});
	}

	private function prepareData(array $data): Result
	{
		$result = new Result();

		try
		{
			$this->prepareAliasFields($data);
			$this->prepareImmutableFields($data);
			$this->prepareStageField($data);
			$this->prepareComment($data);
			$this->prepareFields($data);
			$this->prepareMultiFields($data);
			$this->prepareCurrencyIdForCompanyRevenue($data);
			$this->prepareProductRowData($data);

			$result->setData($data);
		}
		catch (\DomainException $exception)
		{
			$result->addError(new Error($exception->getMessage()));
		}

		return $result;
	}

	private function getAliasFieldNames(): array
	{
		$aliases = [];

		if ($this->factory->isObserversEnabled())
		{
			$aliases['OBSERVER_IDS'] = 'OBSERVER';
		}

		return $aliases;
	}

	private function prepareAliasFields(array &$fields): void
	{
		foreach ($this->getAliasFieldNames() as $original => $alias)
		{
			if (isset($fields[$alias]))
			{
				$fields[$original] = $fields[$alias];
				unset($fields[$alias]);
			}
		}
	}

	private function prepareImmutableFields(array &$fields): void
	{
		$immutableFields = [
			Item::FIELD_NAME_ID,
			Item::FIELD_NAME_CREATED_TIME,
			Item::FIELD_NAME_UPDATED_TIME,
		];

		$aliases = $this->factory->getFieldsMap();

		foreach ($immutableFields as $field)
		{
			unset($fields[$field]);

			if (isset($aliases[$field]))
			{
				unset($fields[$aliases[$field]]);
			}
		}
	}

	private function prepareStageField(array &$fields): void
	{
		if ($this->entity->isStagesEnabled())
		{
			$name = $this->entity::FIELD_NAME_STAGE_ID;
			$aliasName = $this->factory->getFieldsMap()[$name] ?? $name;

			$stageValue = $fields[$name] ?? $fields[$aliasName] ?? null;

			if (!empty($stageValue))
			{
				foreach ($this->factory->getStages($this->entity->getCategoryId()) as $stage)
				{
					if ($stage->getId() === $stageValue)
					{
						$fields[$name] = $fields[$aliasName] = $stage->getStatusId();
						break;
					}
				}
			}
		}
	}

	private function prepareComment(array &$fields): void
	{
		if (isset($fields['COMMENTS']) && $fields['COMMENTS'] !== '')
		{
			$fields['COMMENTS'] = trim(strip_tags($fields['COMMENTS']));
		}
	}

	private function prepareFields(array &$fields): void
	{
		foreach ($this->factory->getFieldsCollection() as $id => $field)
		{
			if (!isset($fields[$id]))
			{
				continue;
			}

			$fieldType = $field->getType();

			if ($field->isMultiple())
			{
				$this->prepareMultipleField($field, $fields[$id]);
			}

			if ($fieldType === Field::TYPE_DATE || $fieldType === Field::TYPE_DATETIME)
			{
				$this->prepareDatetimeField($field, $fields[$id]);
			}
			elseif ($fieldType === Field::TYPE_FILE)
			{
				$files = $fields[$id];
				if ($field instanceof Field\Photo && is_numeric($files))
				{
					continue;
				}

				$fields[$id] = $this->prepareFileField($field, $files);

			}
			elseif ($fieldType === 'money')
			{
				$this->prepareMoneyUserField($field, $fields[$id]);
			}
			elseif ($fieldType === 'crm')
			{
				$this->prepareCrmField($field, $fields[$id]);
			}
			elseif (
				$fieldType === 'crm_entity'
				|| $fieldType === 'crm_lead'
				|| $fieldType === 'crm_deal'
				|| $fieldType === 'crm_quote'
				|| $fieldType === 'crm_invoice'
				// these fields render with client_light type
				// || $fieldType === 'crm_contact'
				// || $fieldType === 'crm_company'
			)
			{
				$this->prepareCrmEntityField($field, $fields[$id]);
			}
		}
	}

	private function prepareMultipleField(Field $field, &$data): void
	{
		if ($field->getType() === Field::TYPE_FILE)
		{
			return;
		}

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (isset($value['value']))
				{
					$data[$key] = $value['value'];
				}
				// multi fields have 'id' and 'value' keys, others have raw data
				elseif (empty($value) || isset($value['id']))
				{
					unset($data[$key]);
				}
			}
		}
		else
		{
			$data = [];
		}
	}

	private function prepareDatetimeField(Field $field, &$data): void
	{
		$isDateTimeField = $field->getType() === Field::TYPE_DATETIME;
		$timezoneOffset = \CTimeZone::getOffset();
		$useTimezone = ($field->getUserField()['SETTINGS']['USE_TIMEZONE'] ?? 'Y') === 'Y';
		$isDynamicEntityType = \CCrmOwnerType::isPossibleDynamicTypeId($this->entity->getEntityTypeId());

		$createFromTimestamp = static function ($timestamp) use (
			$isDateTimeField,
			$timezoneOffset,
			$useTimezone,
			$isDynamicEntityType
		) {
			$object = $isDateTimeField
				? ($isDynamicEntityType && $useTimezone
					? DateTime::createFromTimestamp($timestamp + $timezoneOffset)
					: DateTime::createFromTimestamp($timestamp)
				)
				: Date::createFromTimestamp($timestamp + $timezoneOffset);

			if ($isDateTimeField && !$useTimezone)
			{
				$object = $object->toUserTime();
			}

			return $object;
		};

		if ($field->isMultiple())
		{
			if (!empty($data) && is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (!empty($value))
					{
						$data[$key] = $createFromTimestamp($value);
					}
					else
					{
						unset($data[$key]);
					}
				}
			}
		}
		else
		{
			$data = $createFromTimestamp($data);
		}
	}

	private function prepareMoneyUserField(Field $field, &$data): void
	{
		if ($field->isMultiple())
		{
			if (!empty($data) && is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (isset($value['amount']) && is_numeric($value['amount']))
					{
						$data[$key] = "{$value['amount']}|{$value['currency']}";
					}
					else
					{
						unset($data[$key]);
					}
				}
			}
		}
		elseif (isset($data['amount']) && is_numeric($data['amount']))
		{
			$data = "{$data['amount']}|{$data['currency']}";
		}
		else
		{
			$data = null;
		}
	}

	private function prepareCrmField(Field $field, &$data): void
	{
		if (!empty($data) && is_array($data))
		{
			$isMultipleEntityType = count(array_filter($field->getSettings(), static fn($val) => $val === 'Y')) > 1;
			foreach ($data as $key => $value)
			{
				if (!empty($value) && is_array($value))
				{
					[$entityTypeName, $entityId] = $value;

					if ($entityTypeName === DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID)
					{
						[$entityTypeId, $entityId] = DynamicMultipleProvider::parseId($entityId);
						$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
					}
					else
					{
						$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($entityTypeName);
					}

					if ($entityTypeAbbr)
					{
						$data[$key] = $isMultipleEntityType ? "{$entityTypeAbbr}_{$entityId}" : $entityId;
					}

				}
				else
				{
					unset($data[$key]);
				}
			}

			if (!$field->isMultiple())
			{
				$data = $data[0] ?? null;
			}
		}
		else
		{
			$data = $field->isMultiple() ? [] : null;
		}
	}

	private function prepareCrmEntityField(Field $field, &$data): void
	{
		if (!empty($data) && is_numeric($data))
		{
			$fieldType = $field->getType();
			$entityTypeName = null;

			if ($fieldType === 'crm_lead')
			{
				$entityTypeName = CCrmOwnerType::LeadName;
			}
			elseif ($fieldType === 'crm_deal')
			{
				$entityTypeName = CCrmOwnerType::DealName;
			}
			elseif ($fieldType === 'crm_quote')
			{
				$entityTypeName = CCrmOwnerType::QuoteName;
			}
			elseif ($fieldType === 'crm_invoice')
			{
				$entityTypeName = CCrmOwnerType::InvoiceName;
			}

			if ($entityTypeName !== null)
			{
				$data = [[$entityTypeName, (int)$data]];
			}
		}

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (!empty($value) && is_array($value))
				{
					[$entityTypeName, $entityId] = $value;

					if ($entityTypeName === DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID)
					{
						[, $entityId] = DynamicMultipleProvider::parseId($entityId);
					}

					$data[$key] = $entityId;
				}
				else
				{
					unset($data[$key]);
				}
			}

			if (!$field->isMultiple())
			{
				$data = $data[0] ?? null;
			}
		}
		else
		{
			$data = $field->isMultiple() ? [] : null;
		}
	}

	private function prepareFileField(Field $field, $sourceFiles)
	{
		if (empty($sourceFiles))
		{
			return $sourceFiles;
		}

		$resultFiles = $sourceFiles;

		if (!is_array($resultFiles) || Collection::isAssociative($resultFiles))
		{
			$resultFiles = [$resultFiles];
		}

		$isUserField = $field->isUserField();
		$tokens = array_column($resultFiles, 'token');
		$pendingFiles = $this->getPendingFiles($field, $tokens);
		$pendingFilesMap = $pendingFiles->getAll();

		foreach ($resultFiles as &$resultFile)
		{
			if (!empty($resultFile['copy']) && !empty($resultFile['value']) && is_array($resultFile['value']))
			{
				$resultFile = $resultFile['value'];
				continue;
			}

			if (empty($resultFile['token']))
			{
				if (is_array($resultFile))
				{
					$resultFile = null;
				}

				continue;
			}

			$pendingFile = $pendingFilesMap[$resultFile['token']];

			if ($pendingFile && $pendingFile->isValid())
			{
				$resultFile = $pendingFilesMap[$resultFile['token']]->getFileId();
				if ($resultFile > 0)
				{
					if ($isUserField)
					{
						$this->registerUserFileField($field, $resultFile);
					}
					else
					{
						$this->registerGeneralFileField($field, $resultFile);
					}
				}
			}
			else
			{
				throw new \DomainException('File upload error.');
			}
		}

		unset($resultFile);

		$pendingFiles->makePersistent();

		if ($field->isMultiple())
		{
			return $resultFiles;
		}

		return reset($resultFiles) ?: null;
	}

	private function getPendingFiles(Field $field, array $tokens): PendingFileCollection
	{
		$fileController = new EntityFieldController([
			'entityTypeId' => $this->entity->getEntityTypeId(),
			'entityId' => $this->entity->getId(),
			'categoryId' => $this->factory->isCategoriesSupported() ? $this->entity->getCategoryId() : null,
			'fieldName' => $field->getName(),
		]);

		return (new Uploader($fileController))->getPendingFiles($tokens);
	}

	private function registerUserFileField(Field $field, int $fileId): void
	{
		Container::getInstance()->getFileUploader()->registerFileId($field, $fileId);
	}

	private function registerGeneralFileField(Field $field, int $fileId): void
	{
		/** @var FileInputUtility $fileInputUtility */
		$fileInputUtility = FileInputUtility::instance();
		$controlId = mb_strtolower($field->getName()) . '_uploader';

		$fileInputUtility->registerControl($controlId, $controlId);
		$fileInputUtility->registerFile($controlId, $fileId);
	}

	private function prepareMultiFields(array &$fields): void
	{
		if (!$this->entity->hasField(Item::FIELD_NAME_FM))
		{
			return;
		}

		unset($fields[Link::ID]);

		$fields['FM'] = [];

		$currentFmFields = $this->entity->getFm()->toArray();
		foreach (\CCrmFieldMulti::GetEntityTypes() as $name => $info)
		{
			if ($name === Link::ID || !isset($fields[$name]))
			{
				continue;
			}

			$fmValues = [];
			$removedItems = $currentFmFields[$name] ?? [];

			if (!empty($fields[$name]) && is_array($fields[$name]))
			{
				foreach ($fields[$name] as $field)
				{
					if (empty($field['value']['VALUE_TYPE']))
					{
						$field['value']['VALUE_TYPE'] = key($info);
					}

					$fmValues[$field['id']] = $field['value'];
					unset($removedItems[$field['id']]);
				}

				unset($fields[$name]);
			}

			// processing of strange logic to remove FMs - need to clear 'VALUE'
			foreach ($removedItems as $id => $removedItem)
			{
				$removedItem['VALUE'] = '';
				$fmValues[$id] = $removedItem;
			}

			$fields['FM'][$name] = $fmValues;
		}
	}

	private function prepareProductRowData(array &$fields): void
	{
		if ($this->entity->getEntityTypeId() !== \CCrmOwnerType::Deal)
		{
			return;
		}

		if (empty($fields['PRODUCT_ROWS']))
		{
			return;
		}

		$existReserveIds = [];
		foreach ($fields['PRODUCT_ROWS'] as $productRow)
		{
			if (!isset($productRow['ID']) || !is_numeric($productRow['ID']))
			{
				continue;
			}
			$existReserveIds[] = $productRow['ID'];
		}
		$existReserves = self::getReserves($existReserveIds);

		foreach ($fields['PRODUCT_ROWS'] as $productRowIndex => $productRow)
		{
			$isAutoReservation = $productRow['INPUT_RESERVE_QUANTITY'] === $productRow['QUANTITY'];
			$existReserve =
				isset($productRow['ID']) && is_numeric($productRow['ID'])
					? $existReserves[(int)$productRow['ID']]
					: null;
			if ($existReserve)
			{
				$isAuto = $isAutoReservation && $existReserve['IS_AUTO'] === 'Y' ? 'Y' : 'N';
			}
			else
			{
				$isAuto =
					!isset($productRow['INPUT_RESERVE_QUANTITY'])
					|| $productRow['QUANTITY'] === 0
					|| $isAutoReservation
						? 'Y'
						: 'N';
			}
			$fields['PRODUCT_ROWS'][$productRowIndex]['IS_AUTO'] = $isAuto;
		}
	}

	private static function getReserves(array $productRowIds): ?array
	{
		$productRowsMap = [];
		$dbResult = ProductRowReservationTable::getList([
			'select' => [
				'ROW_ID',
				'IS_AUTO',
			],
			'filter' => [
				'=ROW_ID' => $productRowIds,
			],
		]);

		while ($productRow = $dbResult->fetch())
		{
			$productRowsMap[(int)$productRow['ROW_ID']] = $productRow;
		}

		return $productRowsMap;
	}

	private function prepareCurrencyIdForCompanyRevenue(array &$fields): void
	{
		if ($this->entity->getEntityTypeId() !== \CCrmOwnerType::Company)
		{
			return;
		}

		if (!$this->entity->hasField(Item::FIELD_NAME_CURRENCY_ID))
		{
			return;
		}

		$fields['CURRENCY_ID'] ??= $this->entity->getCurrencyId();
		$fields['CURRENCY_ID'] = $fields['CURRENCY_ID'] ?: Currency::getBaseCurrencyId();
	}

	private function saveFactoryBasedEntities(array $fields): Result
	{
		$result = new Result();

		$entityId = $this->entity->getId();
		$isNew = $entityId === 0;
		if ($isNew)
		{
			$operation = $this->factory->getAddOperation($this->entity, $this->context);
		}
		else
		{
			$operation = $this->factory->getUpdateOperation($this->entity, $this->context);
		}

		$productRows = $this->extractProductRowsData($fields);
		if (is_array($productRows))
		{
			$this->setProductRows($productRows);
			$res = (new UpdateCatalogProductsCommand($productRows))->execute();
			if (!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());

				return $result;
			}
		}

		$this->entity->setFromCompatibleData($fields);

		$res = $operation->launch();
		if ($res->isSuccess())
		{
			$result->setData(['ID' => $this->entity->getId()]);

			$this->onSavedFactoryBasedEntities($fields);
		}
		else
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}

	private function onSavedFactoryBasedEntities(array $fields)
	{
		if (
			$this->entity->getEntityTypeId() === \CCrmOwnerType::Lead
			&& array_key_exists('ADDRESS', $fields)
		)
		{
			$this->saveLeadAddress($fields['ADDRESS']);
		}
	}

	private function saveLeadAddress($address): void
	{
		if (
			!(
				is_null($address)
				|| is_string($address))
		)
		{
			return;
		}

		if (empty($address))
		{
			EntityAddress::unregister(
				\CCrmOwnerType::Lead,
				$this->entity->getId(),
				EntityAddressType::Primary
			);

			return;
		}

		if (!Loader::includeModule('location'))
		{
			return;
		}

		try
		{
			$locAddr = Address::fromJson(EntityAddress::prepareJsonValue($address));
		}
		catch (ArgumentException $exception)
		{
			$locAddr = Address::fromArray([
				'fieldCollection' => [
					Address\FieldType::ADDRESS_LINE_2 => $address,
				],
				'languageId' => LANGUAGE_ID,
			]);
		}

		EntityAddress::register(
			\CCrmOwnerType::Lead,
			$this->entity->getId(),
			EntityAddressType::Primary,
			[
				'LOC_ADDR' => $locAddr,
			]
		);
	}

	private function extractProductRowsData(array &$fields): ?array
	{
		$productRows = $fields[Item::FIELD_NAME_PRODUCTS] ?? null;

		unset($fields[Item::FIELD_NAME_PRODUCTS]);

		return $productRows;
	}

	private function setProductRows(array $productRows): void
	{
		foreach ($productRows as &$row)
		{
			if (isset($row['ID']) && !is_numeric($row['ID']))
			{
				unset($row['ID']);
			}
		}

		unset($row);

		$this->entity->setProductRowsFromArrays($productRows);
	}
}
