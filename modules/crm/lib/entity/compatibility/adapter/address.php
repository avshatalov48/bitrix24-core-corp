<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Entity\AddressValidator;
use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Address extends Adapter
{
	private const ADDRESS_TO_COMPATIBLE_MAP = [
		'ADDRESS_1' => 'ADDRESS',
		'ADDRESS_2' => 'ADDRESS_2',
		'CITY' => 'ADDRESS_CITY',
		'POSTAL_CODE' => 'ADDRESS_POSTAL_CODE',
		'REGION' => 'ADDRESS_REGION',
		'PROVINCE' => 'ADDRESS_PROVINCE',
		'COUNTRY' => 'ADDRESS_COUNTRY',
		'COUNTRY_CODE' => 'ADDRESS_COUNTRY_CODE',
		'LOC_ADDR_ID' => 'ADDRESS_LOC_ADDR_ID',
		'LOC_ADDR' => 'ADDRESS_LOC_ADDR',
	];

	/** @var int */
	private $entityTypeId;
	/** @var int */
	private $addressType;

	/** @var EntityAddress */
	private $entityAddress = EntityAddress::class;

	final public function __construct(int $entityTypeId, int $addressType)
	{
		$this->entityTypeId = $entityTypeId;
		$this->addressType = $addressType;
	}

	/**
	 * Exists only for testing purposes, do not use it in your code.
	 * This method is subject to change and is not covered by backwards compatibility
	 *
	 * @param EntityAddress $address
	 * @return $this
	 */
	final protected function setEntityAddress(EntityAddress $address): self
	{
		$this->entityAddress = $address;

		return $this;
	}

	final protected function doCheckRequiredFields(
		array $fields,
		array $compatibleOptions,
		array $requiredFields,
		?int $id = null,
		bool $enrichCurrentFieldsWithPrevious = false
	): Result
	{
		$result = new Result();

		if (in_array('ADDRESS', $requiredFields, true))
		{
			if ($id > 0 && $enrichCurrentFieldsWithPrevious)
			{
				$fields = $this->enrichCurrentFieldsWithPrevious($id, $fields);
			}

			$validator = new AddressValidator($this->entityTypeId, 0, $fields);

			$isSuccess = $validator->checkPresence();
			if (!$isSuccess)
			{
				Container::getInstance()->getLocalization()->loadMessages();

				$result->addError(Field::getRequiredEmptyError('ADDRESS', Loc::getMessage('CRM_COMMON_ADDRESS')));
			}
		}

		return $result;
	}

	private function enrichCurrentFieldsWithPrevious(int $id, array $currentFields): array
	{
		$previousFields = $this->entityAddress::getByOwner($this->addressType, $this->entityTypeId, $id);
		if (!is_array($previousFields))
		{
			return $currentFields;
		}

		return array_merge($this->mapAddressToCompatibleFields($previousFields), $currentFields);
	}

	final protected function doPerformAdd(array &$fields, array $compatibleOptions): Result
	{
		$id = (int)($fields[Item::FIELD_NAME_ID] ?? 0);
		if ($id <= 0)
		{
			$result = new Result();
			$result->addError(new Error('ID is required for address procession'));

			return $result;
		}

		$addressFields = $this->mapCompatibleToAddressFields($fields);

		if (!$this->isAddressEmpty($addressFields))
		{
			$this->entityAddress::register(
				$this->entityTypeId,
				$id,
				$this->addressType,
				$addressFields,
			);
		}

		return new Result();
	}

	private function mapCompatibleToAddressFields(array $compatibleFields): array
	{
		$addressFieldNames = array_keys(self::ADDRESS_TO_COMPATIBLE_MAP);

		$result = [];
		foreach ($addressFieldNames as $addressFieldName)
		{
			$result[$addressFieldName] = $compatibleFields[$this->getCompatibleFieldName($addressFieldName)] ?? null;
		}

		return $result;
	}

	private function mapAddressToCompatibleFields(array $addressFields): array
	{
		$result = [];
		foreach ($addressFields as $fieldName => $value)
		{
			$compatibleFieldName = $this->getCompatibleFieldName($fieldName);
			if (empty($compatibleFieldName))
			{
				continue;
			}

			$result[$fieldName] = $value;
		}

		return $result;
	}

	private function getCompatibleFieldName(string $entityAddressFieldName): string
	{
		$compatibleFieldName = self::ADDRESS_TO_COMPATIBLE_MAP[$entityAddressFieldName] ?? '';
		if ($compatibleFieldName === '')
		{
			return '';
		}

		return ($this->resolvePrefix() . $compatibleFieldName);
	}

	private function resolvePrefix(): string
	{
		if ($this->addressType === EntityAddressType::Registered)
		{
			return 'REG_';
		}

		return '';
	}

	private function isAddressEmpty(array $addressFields): bool
	{
		return (
			$this->entityAddress::isEmpty($addressFields)
			&& empty($addressFields['LOC_ADDR'])
			&& $addressFields['LOC_ADDR_ID'] <= 0
		);
	}

	final protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result
	{
		$addressFields = $this->mapCompatibleToAddressFields($fields);

		if (isset($fields['ADDRESS_DELETE']) && $fields['ADDRESS_DELETE'] === 'Y')
		{
			$this->entityAddress::unregister(
				$this->entityTypeId,
				$id,
				$this->addressType,
			);
		}
		elseif (!$this->isAddressEmpty($addressFields))
		{
			$this->entityAddress::register(
				$this->entityTypeId,
				$id,
				$this->addressType,
				$addressFields,
			);
		}

		return new Result();
	}

	final protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		/**
		 * Deletion of address is done in Cleaner
		 * @see \Bitrix\Crm\Cleaning\CleaningManager::getCleaner
		 */

		return new Result();
	}
}
