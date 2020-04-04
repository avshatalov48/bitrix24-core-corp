<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\Settings\ContactSettings;

class AddressRequisiteConverter extends EntityRequisiteConverter
{
	/** @var int */
	protected $presetID = 0;
	/** @var bool */
	protected $enablePermissionCheck = false;
	/**
	 * @param int $entityTypeID Entity type ID.
	 * @param int $presetID Preset ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 */
	public function __construct($entityTypeID, $presetID, $enablePermissionCheck = true)
	{
		parent::__construct($entityTypeID);

		$this->presetID = $presetID;
		$this->enablePermissionCheck = $enablePermissionCheck;
	}
	/**
	 * Check converter settings
	 * @return void
	 * @throws RequisiteConvertException
	 */
	public function validate()
	{
	}
	/**
	 * Process entity. Convert invoice requisites to entity requisites
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	public function processEntity($entityID)
	{
		if($this->enablePermissionCheck)
		{
			if(!(\CCrmAuthorizationHelper::CheckReadPermission($this->entityTypeID, $entityID)
				&& \CCrmAuthorizationHelper::CheckUpdatePermission($this->entityTypeID, $entityID)))
			{
				throw new AddressRequisiteConvertException(
					$this->entityTypeID,
					$this->presetID,
					AddressRequisiteConvertException::ACCESS_DENIED
				);
			}
		}

		$addresses = array();
		foreach(EntityAddress::getListByOwner($this->entityTypeID, $entityID) as $addressTypeID => $address)
		{
			if(EntityAddress::isEmpty($address))
			{
				continue;
			}

			$addresses[$addressTypeID] = array_merge(
				$address,
				array('ANCHOR_TYPE_ID' => $this->entityTypeID, 'ANCHOR_ID' => $entityID)
			);
		}

		if(empty($addresses))
		{
			return false;
		}

		$requisiteEntity = new EntityRequisite();
		$requisiteListResult = $requisiteEntity->getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'=PRESET_ID' => $this->presetID,
					'=ENTITY_TYPE_ID' => $this->entityTypeID,
					'=ENTITY_ID' => $entityID
				)
			)
		);

		$processedQty = 0;
		$isFound = false;
		while($fields = $requisiteListResult->fetch())
		{
			$requisiteID = (int)$fields['ID'];
			$requisiteAddresses = EntityRequisite::getAddresses($requisiteID);

			$added = false;
			foreach($addresses as $addressTypeID => $address)
			{
				if(isset($requisiteAddresses[$addressTypeID])
					&& !EntityAddress::isEmpty($requisiteAddresses[$addressTypeID]))
				{
					if(EntityAddress::areEquals($address, $requisiteAddresses[$addressTypeID]))
					{
						$isFound = true;
					}

					continue;
				}

				EntityAddress::register(\CCrmOwnerType::Requisite, $requisiteID, $addressTypeID, $address);
				$added = true;
			}

			if($added)
			{
				$processedQty++;
			}
		}

		if(!$isFound && $processedQty === 0)
		{
			$requisiteAddResult = $requisiteEntity->add(
				array(
					'ENTITY_TYPE_ID' => $this->entityTypeID,
					'ENTITY_ID' => $entityID,
					'PRESET_ID' => $this->presetID,
					'NAME' => \CCrmOwnerType::GetCaption($this->entityTypeID, $entityID, false),
					'SORT' => 500,
					'ACTIVE' => 'Y'
				)
			);
			if($requisiteAddResult->isSuccess())
			{
				$requisiteID = (int)$requisiteAddResult->getId();
				foreach($addresses as $addressTypeID => $address)
				{
					EntityAddress::register(\CCrmOwnerType::Requisite, $requisiteID, $addressTypeID, $address);
				}
				$processedQty++;
			}
			else
			{
				throw new AddressRequisiteConvertException(
					$this->entityTypeID,
					$this->presetID,
					AddressRequisiteConvertException::CREATION_FAILED
				);
			}
		}

		return $processedQty > 0;
	}
	/**
	 * Complete convertion process
	 * @return void
	 */
	public function complete()
	{
		if($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			ContactSettings::getCurrent()->enableOutmodedRequisites(false);
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Company)
		{
			CompanySettings::getCurrent()->enableOutmodedRequisites(false);
		}

		self::removeOutmodedFormFields($this->entityTypeID);
		self::removeOutmodedGridFields($this->entityTypeID);
	}

	/**
	 * Remove outmoded fields from interface form settings.
	 * @param int $entityTypeID Entity type ID.
	 * @return void
	 */
	protected static function removeOutmodedFormFields($entityTypeID)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$optionName = '';
		$fieldMap = array();
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$optionName = 'CRM_CONTACT_EDIT_V12';
			$fieldMap['ADDRESS'] = true;
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$optionName = 'CRM_COMPANY_EDIT_V12';
			$fieldMap['ADDRESS'] = true;
			$fieldMap['ADDRESS_LEGAL'] = true;
		}

		if($optionName === '' || empty($fieldMap))
		{
			return;
		}

		$dbResult = $connection->query(/** @lang MySQL */
			"SELECT ID, VALUE FROM b_user_option where CATEGORY = 'main.interface.form' AND NAME = '{$optionName}'"
		);

		$resetCache = false;
		while($ary = $dbResult->fetch())
		{
			$optionID = (int)$ary['ID'];
			$value = isset($ary['VALUE']) ? $ary['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$options = unserialize($value);
			if(!is_array($options) || empty($options) || !isset($options['tabs']) || !is_array($options['tabs']))
			{
				continue;
			}

			$changed = false;
			foreach($options['tabs'] as &$tab)
			{
				if(!isset($tab['id']) || $tab['id'] !== 'tab_1')
				{
					continue;
				}

				if(!isset($tab['fields']) || !is_array($tab['fields']))
				{
					continue;
				}

				$fieldQty = count($tab['fields']);
				for($index = 0; $index < $fieldQty; $index++)
				{
					$field = $tab['fields'][$index];
					if($field['type'] === 'section')
					{
						continue;
					}

					$fieldID = $field['id'];
					if(!isset($fieldMap[$fieldID]))
					{
						continue;
					}

					array_splice($tab['fields'], $index, 1, array());
					$changed = true;
				}
			}
			unset($tab);

			if($changed)
			{
				$sqlValue = $sqlHelper->forSql(serialize($options));
				$connection->queryExecute(/** @lang MySQL */
					"UPDATE b_user_option SET VALUE = '{$sqlValue}' WHERE ID ='{$optionID}'"
				);
				$resetCache = true;
			}
		}

		if($resetCache && isset($GLOBALS['CACHE_MANAGER']) && is_object($GLOBALS['CACHE_MANAGER']))
		{
			/** @global \CCacheManager $CACHE_MANAGER */
			global $CACHE_MANAGER;
			$CACHE_MANAGER->cleanDir('user_option');
		}
	}

	/**
	 * Remove outmoded fields from interface grid settings.
	 * @param int $entityTypeID Entity type ID.
	 * @return void
	 */
	protected static function removeOutmodedGridFields($entityTypeID)
	{
		$optionName = '';
		$fieldMap = array();
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$optionName = 'CRM_CONTACT_LIST_V12';
			$fieldMap = array(
				'FULL_ADDRESS' => true,
				'ADDRESS' => true,
				'ADDRESS_2' => true,
				'ADDRESS_CITY' => true,
				'ADDRESS_REGION' => true,
				'ADDRESS_PROVINCE' => true,
				'ADDRESS_POSTAL_CODE' => true,
				'ADDRESS_COUNTRY' => true
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$optionName = 'CRM_COMPANY_LIST_V12';
			$fieldMap = array(
				'ADDRESS' => true,
				'ADDRESS_2' => true,
				'ADDRESS_CITY' => true,
				'ADDRESS_REGION' => true,
				'ADDRESS_PROVINCE' => true,
				'ADDRESS_POSTAL_CODE' => true,
				'ADDRESS_COUNTRY' => true,
				'ADDRESS_LEGAL' => true,
				'REG_ADDRESS_2' => true,
				'REG_ADDRESS_CITY' => true,
				'REG_ADDRESS_REGION' => true,
				'REG_ADDRESS_PROVINCE' => true,
				'REG_ADDRESS_POSTAL_CODE' => true,
				'REG_ADDRESS_COUNTRY' => true
			);
		}

		if($optionName === '' || empty($fieldMap))
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$dbResult = $connection->query(/** @lang MySQL */
			"SELECT ID, VALUE FROM b_user_option WHERE CATEGORY = 'main.interface.grid' AND NAME LIKE '{$optionName}%'"
		);

		$resetCache = false;
		while ($ary = $dbResult->fetch())
		{
			$optionID = (int)$ary['ID'];
			$value = isset($ary['VALUE']) ? $ary['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$options = unserialize($value);
			if(!is_array($options) || empty($options) || !isset($options['views']) || !is_array($options['views']))
			{
				continue;
			}

			$changed = false;
			foreach($options['views'] as &$view)
			{
				if(!isset($view['columns']))
				{
					continue;
				}

				$cols = explode(',', $view['columns']);
				foreach($cols as $colIndex => $colName)
				{
					if(isset($fieldMap[$colName]))
					{
						$changed = true;
						unset($cols[$colIndex]);
					}
				}

				if($changed)
				{
					$view['columns'] = implode(',', $cols);
				}
			}
			unset($view);

			if($changed)
			{
				$sqlValue = $sqlHelper->forSql(serialize($options));
				$connection->queryExecute(/** @lang MySQL */
					"UPDATE b_user_option SET VALUE = '{$sqlValue}' WHERE ID ='{$optionID}'"
				);
				$resetCache = true;
			}
		}

		if($resetCache && isset($GLOBALS['CACHE_MANAGER']) && is_object($GLOBALS['CACHE_MANAGER']))
		{
			/** @global \CCacheManager $CACHE_MANAGER */
			global $CACHE_MANAGER;
			$CACHE_MANAGER->cleanDir('user_option');
		}
	}
}