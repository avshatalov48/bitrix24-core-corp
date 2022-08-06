<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

abstract class EntityAddressConverter
{
	const LOGGER_TAG = 'CRM_ENTITY_ADDRESS_CONVERTER';

	protected $entityTypeId = CCrmOwnerType::Undefined;
	/** @var int */
	protected $defaultPresetId = 0;
	/** @var array|null */
	protected $defaultPresetData = null;
	/** @var bool */
	protected $enablePermissionCheck = false;
	/** @var bool */
	protected $needInitialize = true;
	/** @var array|null */
	protected $presetsWithAddressMap = null;

	/**
	 * @param int $entityTypeId Entity type ID.
	 * @param int $presetId Default preset ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 */
	public function __construct(int $entityTypeId, int $presetId = 0, bool $enablePermissionCheck = true)
	{
		$this->entityTypeId = $entityTypeId;
		$this->defaultPresetId = $presetId;
		$this->enablePermissionCheck = $enablePermissionCheck;
	}

	//region Logging
	protected static function getLogEntityTypeName(int $entityTypeId, string $type = 's')
	{
		$typeMap = [
			's' => [
				CCrmOwnerType::Undefined => 'entity ('.CCrmOwnerType::ResolveName($entityTypeId).')',
				CCrmOwnerType::Company => 'company',
				CCrmOwnerType::Contact => 'contact',
				CCrmOwnerType::Deal => 'deal',
				CCrmOwnerType::Lead => 'lead'
			],
			'm' => [
				CCrmOwnerType::Undefined => 'entities ('.CCrmOwnerType::ResolveName($entityTypeId).')',
				CCrmOwnerType::Company => 'companies',
				CCrmOwnerType::Contact => 'contacts',
				CCrmOwnerType::Deal => 'deals',
				CCrmOwnerType::Lead => 'leads'
			]
		];

		if (!in_array($type, array_keys($typeMap), true))
		{
			$type = 's';
		}

		$entityTypeKey = $entityTypeId;
		if (!in_array($entityTypeId, array_keys($typeMap[$type], true)))
		{
			$entityTypeKey = CCrmOwnerType::Undefined;
		}

		return $typeMap[$type][$entityTypeKey];
	}
	protected function log(string $type, string $message = '')
	{
		Logger::log(
			[
				'TYPE' => $type,
				'TAG' => static::LOGGER_TAG,
				'MESSAGE' => $message
			]
		);
	}
	public function logInfo(string $message = '')
	{
		// Disable info logging
		$this->log(Logger::TYPE_INFO, $message);
	}
	public function logError(string $message = '')
	{
		$this->log(Logger::TYPE_ERROR, $message);
	}
	//endregion Logging

	public function initialize()
	{
		if ($this->needInitialize)
		{
			$presetId = $this->defaultPresetId;
			if ($presetId <= 0)
			{
				try
				{
					$presetId = EntityRequisite::getDefaultPresetId($this->entityTypeId);
				}
				catch (SystemException $e)
				{
				}
			}
			if ($presetId > 0)
			{
				$presetData = EntityPreset::getSingleInstance()->getById($presetId);
				if (is_array($presetData))
				{
					$this->defaultPresetData = $presetData;
				}
				else
				{
					$presetId = 0;
				}
			}
			$this->defaultPresetId = $presetId;
			$this->needInitialize = false;
		}
	}

	public function start()
	{
		$this->logInfo(
			'Addresses of '.static::getLogEntityTypeName($this->entityTypeId, 'm').' conversion start'
		);
	}

	protected function checkEntityPermissions(int $id)
	{
		if($this->enablePermissionCheck)
		{
			if(!(\CCrmAuthorizationHelper::CheckReadPermission($this->entityTypeId, $id)
				&& \CCrmAuthorizationHelper::CheckUpdatePermission($this->entityTypeId, $id)))
			{
				throw new EntityAddressConverterException(
					$this->entityTypeId,
					$this->defaultPresetId,
					EntityAddressConverterException::ACCESS_DENIED
				);
			}
		}
	}

	protected function getSourceEntityAddresses(int $id)
	{
		$result = [];

		foreach(EntityAddress::getListByOwner($this->entityTypeId, $id) as $addressTypeID => $address)
		{
			if(EntityAddress::isEmpty($address))
			{
				continue;
			}

			$result[$addressTypeID] = array_merge(
				$address,
				[
					'ANCHOR_TYPE_ID' => $this->entityTypeId,
					'ANCHOR_ID' => $id,
					'IS_DEF' => true
				]
			);
		}

		return $result;
	}

	protected function getEntityRequisiteMaps(int $entityId)
	{
		$requisite = EntityRequisite::getSingleInstance();
		$requisiteAddressMap = [];
		$requisitePresetMap = [];
		$presetIds = array_keys(EntityRequisite::getPresetWithAddressMap());
		if (!empty($presetIds))
		{
			$res = $requisite->getList(
				array(
					'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
					'select' => ['ID', 'PRESET_ID'],
					'filter' => [
						'@PRESET_ID' => $presetIds,
						'=ENTITY_TYPE_ID' => $this->entityTypeId,
						'=ENTITY_ID' => $entityId
					]
				)
			);
			while($row = $res->fetch())
			{
				$requisiteId = (int)$row['ID'];
				$requisiteAddressMap[$requisiteId] = EntityRequisite::getAddresses($requisiteId);
				$requisitePresetMap[$requisiteId] = (int)$row['PRESET_ID'];
			}
		}

		return [$requisiteAddressMap, $requisitePresetMap];
	}

	protected function addEntityRequisite(int $entityId, $requisiteAddressMap, $requisitePresetMap)
	{
		$requisiteId = 0;
		
		$requisite = EntityRequisite::getSingleInstance();

		$presetId = $this->defaultPresetId > 0 ? $this->defaultPresetId : 0;
		if ($presetId <= 0 && !empty($requisiteAddressMap))
		{
			reset($requisiteAddressMap);
			$firstRequisiteId = key($requisiteAddressMap);
			if (isset($requisitePresetMap[$firstRequisiteId]) && $requisitePresetMap[$firstRequisiteId] > 0)
			{
				$presetId = $requisitePresetMap[$firstRequisiteId];
			}
		}
		if ($presetId <= 0)
		{
			throw new EntityAddressConverterException(
				$this->entityTypeId,
				$presetId,
				EntityAddressConverterException::ERR_CANT_PICK_PRESET_FOR_REQUISITE
			);
		}
		$requisiteAddResult = $requisite->add(
			array(
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'ENTITY_ID' => $entityId,
				'PRESET_ID' => $presetId,
				'NAME' => CCrmOwnerType::GetCaption($this->entityTypeId, $entityId, false),
				'SORT' => 500,
				'ADDRESS_ONLY' => 'Y',
				'ACTIVE' => 'Y'
			)
		);
		if($requisiteAddResult->isSuccess())
		{
			$requisiteId = (int)$requisiteAddResult->getId();
		}
		
		return $requisiteId;
	}

	protected function getEntityDefaultRequisiteId($entityId, $requisiteAddressMap)
	{
		$requisiteId = 0;

		$requisite = EntityRequisite::getSingleInstance();
		$settings = $requisite->loadSettings($this->entityTypeId, $entityId);
		if (is_array($settings) && isset($settings['REQUISITE_ID_SELECTED'])
			&& $settings['REQUISITE_ID_SELECTED'] > 0)
		{
			$requisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
			if (!isset($requisiteAddressMap[$requisiteId]) || !$requisite->exists($requisiteId))
			{
				$requisiteId = 0;
			}
		}

		return $requisiteId;
	}

	protected function matchRequisiteAddressAndSetDef($addressTypeId, $address, $requisiteAddressMap)
	{
		$result = false;

		foreach ($requisiteAddressMap as $requisiteId => $requisiteAddresses)
		{
			if(isset($requisiteAddresses[$addressTypeId])
				&& !EntityAddress::isEmpty($requisiteAddresses[$addressTypeId])
				&& $this->isAddressesMatch($address, $requisiteAddresses[$addressTypeId]))
			{
				EntityAddress::setDef(
					CCrmOwnerType::Requisite,
					$requisiteId,
					$addressTypeId
				);
				/*$this->logInfo(
					'EntityAddress::setDef('.CCrmOwnerType::Requisite.', '.$requisiteId.', '.
					$addressTypeId.')'
				);*/
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function setEntityAddresses(int $id, array $addresses)
	{
		if(!empty($addresses))
		{
			list($requisiteAddressMap, $requisitePresetMap) = $this->getEntityRequisiteMaps($id);
			$advancedRequisiteId = 0;
			foreach ($addresses as $addressTypeId => $address)
			{
				$isFound = false;
				$isRegistered = false;
				if ($advancedRequisiteId <= 0)
				{
					$isFound = $this->matchRequisiteAddressAndSetDef(
						$addressTypeId,
						$address,
						$requisiteAddressMap
					);
					if (!$isFound)
					{
						if (count($requisiteAddressMap) === 1)
						{
							$requisiteAddresses = reset($requisiteAddressMap);
							$requisiteId = key($requisiteAddressMap);
						}
						else
						{
							$requisiteId = $this->getEntityDefaultRequisiteId($id, $requisiteAddressMap);
						}
						if ($requisiteId > 0)
						{
							if (!isset($requisiteAddresses[$addressTypeId])
								|| EntityAddress::isEmpty($requisiteAddresses[$addressTypeId]))
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
					}
				}
				if (!$isFound && !$isRegistered)
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

	public function convertEntity(int $id)
	{
		$this->initialize();

		$this->checkEntityPermissions($id);

		$this->setEntityAddresses($id, $this->getSourceEntityAddresses($id));
	}

	public function convert(array $entityIds)
	{
		/*$this->logInfo(
			'Convert '.static::getLogEntityTypeName($this->entityTypeId, 'm').': '.implode(', ', $entityIds)
		);*/
		foreach ($entityIds as $id)
		{
			$id = (int)$id;
			if ($id > 0)
			{
				$isError = false;
				$errorMessage = '';
				try
				{
					$this->convertEntity($id);
				}
				catch(EntityAddressConverterException $e)
				{
					$isError = true;
					$errorMessage = $e->getLocalizedMessage();
				}
				catch(SystemException $e)
				{
					$isError = true;
					$errorMessage = $e->getMessage();
				}
				if ($isError)
				{
					$this->logError(
						Loc::getMessage(
							'CRM_ENT_ADDR_CONV_'.CCrmOwnerType::ResolveName($this->entityTypeId),
							[
								'#ID#' => $id,
								'#ERROR_MESSAGE#' => $errorMessage
							]
						)
					);
				}
			}
		}
	}

	public function complete()
	{
		$this->logInfo(
			'Addresses of '.static::getLogEntityTypeName($this->entityTypeId, 'm').' conversion completed'
		);
	}

	protected function isAddressesMatch(array $a, array $b)
	{
		$result = true;
		$matchFields = ['ADDRESS_1', 'ADDRESS_2', 'CITY', 'POSTAL_CODE', 'REGION', 'PROVINCE', 'COUNTRY'];
		foreach ($matchFields as $fieldName)
		{
			if ((isset($a[$fieldName]) ? $a[$fieldName] : '') !== (isset($b[$fieldName]) ? $b[$fieldName] : ''))
			{
				$result = false;
				break;
			}
		}

		return $result;
	}
}