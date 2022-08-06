<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Crm\EntityAddressType;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Location;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Crm\EntityAddress;
use CCrmOwnerType;
use CUserTypeEntity;

Loc::loadMessages(__FILE__);

class EntityUfAddressConverter extends EntityAddressConverter
{
	const LOGGER_TAG = 'CRM_ENTITY_UF_ADDRESS_CONVERTER';

	protected $sourceEntityTypeId = CCrmOwnerType::Undefined;
	protected $sourceUserFieldName = '';

	public function __construct(
		int $entityTypeId,
		int $sourceEntityTypeId, string $sourceUserFieldName,
		int $presetId = 0, bool $enablePermissionCheck = true
	)
	{
		parent::__construct($entityTypeId, $presetId, $enablePermissionCheck);
		$this->sourceEntityTypeId = $sourceEntityTypeId;
		$this->sourceUserFieldName = $sourceUserFieldName;
	}

	protected function checkOnStart()
	{
		if (!CCrmOwnerType::IsDefined($this->sourceEntityTypeId))
		{
			throw new SystemException(
				Loc::getMessage('CRM_ENT_UF_ADDR_CONV_ERR_INVALID_SOURCE_ENTITY_TYPE_ID')
			);
		}
		if (!is_string($this->sourceUserFieldName) || $this->sourceUserFieldName === '')
		{
			throw new SystemException(
				Loc::getMessage('CRM_ENT_UF_ADDR_CONV_ERR_INVALID_USER_FIELD_NAME')
			);
		}
		$userFieldExists = true;
		$res = CUserTypeEntity::GetList(
			[],
			[
				'ENTITY_ID' => 'CRM_'.CCrmOwnerType::ResolveName($this->sourceEntityTypeId),
				'FIELD_NAME' => $this->sourceUserFieldName,
				'USER_TYPE_ID' => 'address',
				'MULTIPLE' => 'N',
			]
		);
		$row = null;
		if (is_object($res))
		{
			$row = $res->Fetch();
		}
		if (!is_array($row))
		{
			$userFieldExists = false;
		}
		unset($res, $row);
		if (!$userFieldExists)
		{
			throw new SystemException(
				Loc::getMessage('CRM_ENT_UF_ADDR_CONV_ERR_USER_FIELD_NOT_FOUND')
			);
		}
	}

	public function start()
	{
		$userFieldName = $this->sourceUserFieldName;
		if (mb_strlen($userFieldName) > 50)
		{
			$userFieldName = mb_substr($userFieldName, 0, 37).'...'.mb_substr($userFieldName, -10);
		}
		$this->logInfo(
			'Conversion of addresses from user fields of '.
			static::getLogEntityTypeName($this->sourceEntityTypeId, 'm').' to '.
			static::getLogEntityTypeName($this->entityTypeId, 's').
			' details is started (SOURCE_ENTITY_TYPE_ID: '.
			$this->sourceEntityTypeId.', SOURCE_USER_FIELD_NAME: "'.$userFieldName.'").'
		);

		$isError = false;
		$errorMessage = '';
		try
		{
			$this->checkOnStart();
		}
		catch(SystemException $e)
		{
			$isError = true;
			$errorMessage = $e->getMessage();
		}
		if ($isError)
		{
			$errorMessage = Loc::getMessage(
				'CRM_ENT_UF_ADDR_CONV_ERR_ON_START',
				['#ERROR_MESSAGE#' => $errorMessage]
			);
			$this->logError($errorMessage);
			throw new SystemException($errorMessage);
		}
	}

	protected function getAddressFieldsFromUserFieldValue($entityId, $value)
	{
		$addressFields = [];

		if (Loader::includeModule('fileman')
			&& EntityAddress::isLocationModuleIncluded()
			&& is_string($value) && $value !== '')
		{
			list($addressText, $addressCoords) = AddressType::parseValue($value);
			if (is_string($addressText) && $addressText !== '' || is_array($addressCoords))
			{
				$addressFields = [
					'ADDRESS_1' => $addressText,
					'ADDRESS_2' => '',
					'CITY' => '',
					'POSTAL_CODE' => '',
					'REGION' => '',
					'PROVINCE' => '',
					'COUNTRY' => '',
					'COUNTRY_CODE' => '',
					'ANCHOR_TYPE_ID' => $this->entityTypeId,
					'ANCHOR_ID' => $entityId,
					'IS_DEF' => true
				];
				$addresslanguageId = EntityAddress::getDefaultLanguageId();
				$locationAddress = (new Address($addresslanguageId))
					->setFieldValue(Address\FieldType::ADDRESS_LINE_1, $addressText);
				if (is_array($addressCoords))
				{
					$locationAddress->setLatitude($addressCoords[0]);
					$locationAddress->setLongitude($addressCoords[1]);
				}
				$addressFields['LOC_ADDR'] = $locationAddress;
			}
		}

		return $addressFields;
	}

	protected function getSelfEntityUfAddresses(int $id)
	{
		$result = [];

		if (Loader::includeModule('fileman') && EntityAddress::isLocationModuleIncluded())
		{
			$res = null;
			/** @var  $entityClass \CCrmCompany|\CCrmContact */
			$entityClass = "\\CCrm".ucfirst(mb_strtolower(CCrmOwnerType::ResolveName($this->sourceEntityTypeId)));
			$res = $entityClass::GetListEx(
				[],
				['=ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				[$this->sourceUserFieldName]
			);
			if(is_object($res))
			{
				$row = $res->Fetch();
				if (is_array($row) && isset($row[$this->sourceUserFieldName]))
				{
					$address = $this->getAddressFieldsFromUserFieldValue($id, $row[$this->sourceUserFieldName]);
					if (!empty($address))
					{
						$result[EntityAddressType::Delivery] = $address;
					}
				}
			}
			unset($res, $row);
		}

		return $result;
	}

	protected function setEntityAddresses(int $id, array $addresses)
	{
		if(!empty($addresses))
		{
			list($requisiteAddressMap, $requisitePresetMap) = $this->getEntityRequisiteMaps($id);
			$requisiteCount = count($requisiteAddressMap);
			$advancedRequisiteId = 0;
			foreach ($addresses as $addressTypeId => $address)
			{
				$isRegistered = false;
				if ($advancedRequisiteId <= 0)
				{
					$firstRequisiteId = 0;
					if ($requisiteCount > 0)
					{
						reset($requisiteAddressMap);
						$firstRequisiteId = key($requisiteAddressMap);
					}
					if ($requisiteCount !== 1)
					{
						$requisiteId = $this->getEntityDefaultRequisiteId($id, $requisiteAddressMap);
					}
					if ($requisiteId <= 0)
					{
						$requisiteId = $firstRequisiteId;
					}
					if ($requisiteId > 0)
					{
						EntityAddress::register(
							CCrmOwnerType::Requisite,
							$requisiteId,
							$addressTypeId,
							$address
						);
						/*$this->logInfo(
							'EntityAddress::register('.CCrmOwnerType::Requisite.', '.$addressTypeId.', '.
							$requisiteId.')'
						);*/
						$isRegistered = true;
					}
				}
				if (!$isRegistered)
				{
					if ($advancedRequisiteId <= 0)
					{
						$advancedRequisiteId = $this->addEntityRequisite(
							$id,
							$requisiteAddressMap,
							$requisitePresetMap
						);
					}
					if($advancedRequisiteId > 0)
					{
						EntityAddress::register(
							CCrmOwnerType::Requisite,
							$advancedRequisiteId,
							$addressTypeId,
							$address
						);
						/*$this->logInfo(
							'EntityAddress::register('.\CCrmOwnerType::Requisite.', '.$advancedRequisiteId.', '.
							$addressTypeId.')'
						);*/
					}
					else
					{
						throw new EntityAddressConverterException(
							$this->entityTypeId,
							$this->defaultPresetId,
							EntityAddressConverterException::CREATION_FAILED
						);
					}
				}
			}
		}
	}

	public function convertEntityFromSelf(int $id)
	{
		$this->setEntityAddresses($id, $this->getSelfEntityUfAddresses($id));
	}

	protected function getAnotherfEntityUfAddresses(int $id)
	{
		$result = [];

		if (Loader::includeModule('fileman') && EntityAddress::isLocationModuleIncluded())
		{
			$userFieldName = $this->sourceUserFieldName;
			$entityTypeNameLower = mb_strtolower(CCrmOwnerType::ResolveName($this->entityTypeId));
			$entityTypeNameUpper = mb_strtoupper($entityTypeNameLower);
			$sourceEntityTypeNameLower = mb_strtolower(CCrmOwnerType::ResolveName($this->sourceEntityTypeId));
			$query =
				"SELECT U.$userFieldName".PHP_EOL.
				"FROM b_crm_$entityTypeNameLower C".PHP_EOL.
				"  INNER JOIN b_crm_$sourceEntityTypeNameLower D ON C.ID = $id".PHP_EOL.
				"    AND C.ID = D.${entityTypeNameUpper}_ID".PHP_EOL.
				"  INNER JOIN b_uts_crm_$sourceEntityTypeNameLower U ON D.ID = U.VALUE_ID AND".PHP_EOL.
				"    U.$userFieldName IS NOT NULL AND U.$userFieldName != ''".PHP_EOL.
				"ORDER BY D.DATE_CREATE DESC, D.ID DESC".PHP_EOL.
				"LIMIT 1";
			$res = Application::getConnection()->query($query);
			$row = $res->fetch();
			if (is_array($row) && isset($row[$userFieldName]))
			{
				$address = $this->getAddressFieldsFromUserFieldValue($id, $row[$userFieldName]);
				if (!empty($address))
				{
					$result[EntityAddressType::Delivery] = $address;
				}
			}
		}

		return $result;
	}

	public function convertEntityFromAnother(int $id)
	{
		$this->setEntityAddresses($id, $this->getAnotherfEntityUfAddresses($id));
	}

	public function convertEntity(int $id)
	{
		$this->initialize();

		$this->checkEntityPermissions($id);

		if ($this->entityTypeId === $this->sourceEntityTypeId)
		{
			$this->convertEntityFromSelf($id);
		}
		else
		{
			$this->convertEntityFromAnother($id);
		}
	}

	public function complete()
	{
		$this->logInfo(
			'Conversion of addresses from user fields of '.
			static::getLogEntityTypeName($this->sourceEntityTypeId, 'm').' to '.
			static::getLogEntityTypeName($this->entityTypeId, 's').' details is completed.'
		);
	}
}