<?php

namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Invoice\Invoice;
use Bitrix\Crm\Order;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EntityRequisite
{
	const ERR_INVALID_ENTITY_TYPE          = 201;
	const ERR_INVALID_ENTITY_ID            = 202;
	const ERR_ON_DELETE                    = 203;
	const ERR_NOTHING_TO_DELETE            = 204;
	const ERR_COMPANY_NOT_EXISTS           = 205;
	const ERR_CONTACT_NOT_EXISTS           = 206;
	const ERR_INVALID_IMP_PRESET_ID        = 207;
	const ERR_IMP_PRESET_NOT_EXISTS        = 208;
	const ERR_DEF_IMP_PRESET_NOT_DEFINED   = 209;
	const ERR_ACCESS_DENIED_COMPANY_UPDATE = 210;
	const ERR_ACCESS_DENIED_CONTACT_UPDATE = 211;
	const ERR_NO_ADDRESSES_TO_IMPORT       = 212;
	const ERR_IMP_PRESET_HAS_NO_ADDR_FIELD = 213;
	const ERR_DUP_CTRL_MODE_SKIP           = 214;
	const ERR_CREATE_REQUISITE             = 215;

	const CONFIG_TABLE_NAME = 'b_crm_requisite_cfg';

	const INN = 'RQ_INN'; //Individual Taxpayer Identification Number
	const KPP = 'RQ_KPP';
	const OGRN = 'RQ_OGRN';
	const OGRNIP = 'RQ_OGRNIP';
	const OKVED = 'RQ_OKVED';
	const IFNS = 'RQ_IFNS';
	const ADDRESS = 'RQ_ADDR';
	const PERSON_FULL_NAME = 'RQ_NAME';
	const PERSON_FIRST_NAME = 'RQ_FIRST_NAME';
	const PERSON_SECOND_NAME = 'RQ_SECOND_NAME';
	const PERSON_LAST_NAME = 'RQ_LAST_NAME';
	const COMPANY_NAME = 'RQ_COMPANY_NAME';
	const COMPANY_FULL_NAME = 'RQ_COMPANY_FULL_NAME';
	const COMPANY_REG_DATE = 'RQ_COMPANY_REG_DATE';
	const COMPANY_DIRECTOR = 'RQ_DIRECTOR';
	const COMPANY_ACCOUNTANT = 'RQ_ACCOUNTANT';
	const EDRPOU = 'RQ_EDRPOU';

	private static $singleInstance = null;

	private static $fixedPresetList = null;
	private static $FIELD_INFOS = null;

	private static $allowedRqFieldCountryIds = array(1, 4, 6, 14, 46, 122);
	private static $rqFieldCountryMap = null;
	private static $rqFieldTitleMap = null;
	private static $userFieldTitles = null;

	private static $duplicateCriterionFieldsMap = null;

	static public $sUFEntityID = 'CRM_REQUISITE';

	public static function getSingleInstance()
	{
		if (self::$singleInstance === null)
			self::$singleInstance = new EntityRequisite();

		return self::$singleInstance;
	}

	public function getUfId()
	{
		return RequisiteTable::getUfId();
	}

	public function getList($params)
	{
		$addrFieldsMap = array();
		foreach ($this->getAddressFields() as $fieldName)
		{
			$addrFieldsMap = array_merge(
				$addrFieldsMap,
				array(
					$fieldName.'_ADDRESS_1' => $fieldName.'.ADDRESS_1',
					$fieldName.'_ADDRESS_2' => $fieldName.'.ADDRESS_2',
					$fieldName.'_CITY' => $fieldName.'.CITY',
					$fieldName.'_POSTAL_CODE' => $fieldName.'.POSTAL_CODE',
					$fieldName.'_REGION' => $fieldName.'.REGION',
					$fieldName.'_PROVINCE' => $fieldName.'.PROVINCE',
					$fieldName.'_COUNTRY' => $fieldName.'.COUNTRY',
					$fieldName.'_COUNTRY_CODE' => $fieldName.'.COUNTRY_CODE'
				)
			);
		}
		unset($fieldName);

		// rewrite order
		if (is_array($params['order']))
		{
			$newOrder = array();
			foreach ($params['order'] as $k => $v)
			{
				if (is_numeric($k))
				{
					if ($v !== self::ADDRESS && isset($addrFieldsMap[$v]))
						$v = $addrFieldsMap[$v];
				}
				else
				{
					if ($k !== self::ADDRESS && isset($addrFieldsMap[$k]))
						$k = $addrFieldsMap[$k];
				}
				$newOrder[$k] = $v;
			}
			$params['order'] = $newOrder;
			unset($k, $v, $newOrder);
		}

		// rewrite select
		if (is_array($params['select']))
		{
			$newSelect = array();
			foreach ($params['select'] as $k => $v)
			{
				if ($v !== self::ADDRESS)
				{
					if (isset($addrFieldsMap[$v]))
					{
						$k = $v;
						$v = $addrFieldsMap[$v];
					}
					$newSelect[$k] = $v;
				}
			}
			$params['select'] = $newSelect;
			unset($k, $v, $newSelect);
		}

		// rewrite filter
		if (is_array($params['filter']))
			$params['filter'] = $this->rewriteFilterAddressFields($params['filter'], $addrFieldsMap);

		return RequisiteTable::getList($params);
	}

	protected function rewriteFilterAddressFields(&$filter, &$addressFieldsMap)
	{
		static $sqlWhere = null;
		$newFilter = array();

		foreach ($filter as $k => $v)
		{
			if ($k !== 'LOGIC' && !is_numeric($k))
			{
				if (!$sqlWhere)
					$sqlWhere = new \CSQLWhere();
				list($fieldName,) = array_values($sqlWhere->MakeOperation($k));
				if (!empty($fieldName))
				{
					if ($fieldName !== self::ADDRESS && isset($addressFieldsMap[$fieldName]))
						$k = str_replace($fieldName, $addressFieldsMap[$fieldName], $k);
				}
			}

			if (is_array($v))
			{
				$v = $this->rewriteFilterAddressFields($v, $addressFieldsMap);
			}

			$newFilter[$k] = $v;
		}

		return $newFilter;
	}

	public function getCountByFilter($filter = array())
	{
		return RequisiteTable::getCountByFilter($filter);
	}

	public function getById($id)
	{
		$result = RequisiteTable::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	public static function getByExternalId($externalId, array $select = null)
	{
		if($select === null)
		{
			$select = array('*');
		}

		$result = RequisiteTable::getList(array('select' => $select, 'filter' => array('=XML_ID' => $externalId)));
		$fields = $result->fetch();
		return (is_array($fields)? $fields : null);
	}

	public function exists($id)
	{
		$id = (int)$id;
		if ($id <= 0)
			return false;

		$row = $this->getList(
			array(
				'filter' => array('=ID' => $id),
				'select' => array('ID'),
				'limit' => 1
			)
		)->fetch();

		if (!is_array($row))
			return false;

		return true;
	}

	public function checkBeforeAdd($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$this->separateAddressFields($fields);

		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\AddResult();
		$entity = RequisiteTable::getEntity();

		try
		{
			// set fields with default values
			foreach ($entity->getFields() as $field)
			{
				$fieldName = $field->getName();
				if ($field instanceof Entity\ScalarField && !array_key_exists($fieldName, $fields))
				{
					$defaultValue = $field->getDefaultValue();

					if ($defaultValue !== null)
					{
						$fields[$fieldName] = $field->getDefaultValue();
					}
				}
				else if ($field instanceof Entity\BooleanField && array_key_exists($fieldName, $fields))
				{
					if ($fields[$fieldName] !== 'Y' && $fields[$fieldName] !== 'N'
						&& $fields[$fieldName] !== true && $fields[$fieldName] !== false)
					{
						$fields[$fieldName] = $field->getDefaultValue();
					}
				}
			}

			// uf values
			$userFields = array();

			// separate userfields
			if ($entity->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields($entity->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $fields))
					{
						// copy value
						$userFields[$userfield['FIELD_NAME']] = $fields[$userfield['FIELD_NAME']];

						// remove original
						unset($fields[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			RequisiteTable::checkFields($result, null, $fields);

			// check uf data
			if (!empty($userFields))
			{
				if (!$USER_FIELD_MANAGER->CheckFields($entity->getUfId(), false, $userFields))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new Entity\EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($fields + $userFields))
			{
				$result->addError(new Entity\EntityError("There is no data to add."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	public function add($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$addresses = null;
		if (isset($fields[self::ADDRESS]))
		{
			$addresses = $fields[self::ADDRESS];
			unset($fields[self::ADDRESS]);
		}

		// rewrite some fields
		$entity = RequisiteTable::getEntity();
		foreach ($entity->getFields() as $field)
		{
			$fieldName = $field->getName();
			if ($field instanceof Entity\BooleanField && array_key_exists($fieldName, $fields))
			{
				if ($fields[$fieldName] !== 'Y' && $fields[$fieldName] !== 'N'
					&& $fields[$fieldName] !== true && $fields[$fieldName] !== false)
				{
					$fields[$fieldName] = $field->getDefaultValue();
				}
			}
		}

		$result = RequisiteTable::add($fields);
		$id = $result->isSuccess() ? (int)$result->getId() : 0;
		if ($id > 0)
		{
			$entityTypeId = isset($fields['ENTITY_TYPE_ID']) ? (int)$fields['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
			$entityId = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;

			if (is_array($addresses))
			{
				if(!\CCrmOwnerType::IsDefined($entityTypeId) || $entityId <= 0)
				{
					$entityTypeId = \CCrmOwnerType::Requisite;
					$entityId = $id;
				}

				foreach($addresses as $addressTypeID => $address)
				{
					if(!is_array($address) || empty($address))
					{
						continue;
					}

					EntityAddress::register(
						\CCrmOwnerType::Requisite,
						$id,
						$addressTypeID,
						array(
							'ANCHOR_TYPE_ID' => $entityTypeId,
							'ANCHOR_ID' => $entityId,
							'ADDRESS_1' => isset($address['ADDRESS_1']) ? $address['ADDRESS_1'] : null,
							'ADDRESS_2' => isset($address['ADDRESS_2']) ? $address['ADDRESS_2'] : null,
							'CITY' => isset($address['CITY']) ? $address['CITY'] : null,
							'POSTAL_CODE' => isset($address['POSTAL_CODE']) ? $address['POSTAL_CODE'] : null,
							'REGION' => isset($address['REGION']) ? $address['REGION'] : null,
							'PROVINCE' => isset($address['PROVINCE']) ? $address['PROVINCE'] : null,
							'COUNTRY' => isset($address['COUNTRY']) ? $address['COUNTRY'] : null,
							'COUNTRY_CODE' => isset($address['COUNTRY_CODE']) ? $address['COUNTRY_CODE'] : null
						)
					);
				}
			}

			DuplicateRequisiteCriterion::registerByEntity($entityTypeId, $entityId);

			//region Send event
			if ($id > 0)
			{
				$event = new Main\Event('crm', 'OnAfterRequisiteAdd', array('id' => $id, 'fields' => $fields));
				$event->send();
			}
			//endregion
		}

		return $result;
	}

	public function addFromData($entityTypeId, $entityId, $requisiteData)
	{
		$result = array();

		$signer = new \Bitrix\Main\Security\Sign\Signer();

		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		if (self::checkEntityType($entityTypeId)
			&& $this->validateEntityExists($entityTypeId, $entityId)
			&& $this->validateEntityUpdatePermission($entityTypeId, $entityId))
		{
			if (is_array($requisiteData))
			{
				foreach ($requisiteData as $index => $data)
				{
					if (isset($data['REQUISITE_ID'])
						&& isset($data['REQUISITE_DATA'])
						&& is_string($data['REQUISITE_DATA'])
						&& !empty($data['REQUISITE_DATA'])
						&& isset($data['REQUISITE_DATA_SIGN'])
						&& is_string($data['REQUISITE_DATA_SIGN'])
						&& !empty($data['REQUISITE_DATA_SIGN']))
					{
						$isValidData = false;

						if($signer->validate(
							$data['REQUISITE_DATA'],
							$data['REQUISITE_DATA_SIGN'],
							'crm.requisite.edit-'.$entityTypeId))
						{
							$isValidData = true;
						}

						if ($isValidData)
						{
							$requisiteId = (int)$data['REQUISITE_ID'];
							if ($requisiteId === 0)
							{
								$requisiteData = array();
								try
								{
									$requisiteData = \Bitrix\Main\Web\Json::decode($data['REQUISITE_DATA']);
								}
								catch (\Bitrix\Main\SystemException $e)
								{}

								$fields = (is_array($requisiteData) && is_array($requisiteData['fields'])) ?
									$requisiteData['fields'] : null;

								if (is_array($fields)
									&& isset($fields['ENTITY_TYPE_ID'])
									&& isset($fields['ENTITY_ID']))
								{
									// prepare fields
									$curDateTime = new \Bitrix\Main\Type\DateTime();
									$curUserId = \CCrmSecurityHelper::GetCurrentUserID();
									$fields['DATE_CREATE'] = $curDateTime;
									$fields['CREATED_BY_ID'] = $curUserId;
									$fields['ENTITY_TYPE_ID'] = $entityTypeId;
									$fields['ENTITY_ID'] = $entityId;
									if (isset($fields['ID']))
										unset($fields['ID']);
									if (isset($fields['DATE_MODIFY']))
										unset($fields['DATE_MODIFY']);
									if (isset($fields['MODIFY_BY_ID']))
										unset($fields['MODIFY_BY_ID']);

									$addResult = $this->add($fields);
									if($addResult && $addResult->isSuccess())
										$result[$index] = $addResult->getId();
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public function checkBeforeUpdate($id, $fields)
	{
		unset($fields['ID'], $fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$this->separateAddressFields($fields);

		// rewrite some fields
		$entity = RequisiteTable::getEntity();
		foreach ($entity->getFields() as $field)
		{
			$fieldName = $field->getName();
			if ($field instanceof Entity\BooleanField && array_key_exists($fieldName, $fields))
			{
				if ($fields[$fieldName] !== 'Y' && $fields[$fieldName] !== 'N'
					&& $fields[$fieldName] !== true && $fields[$fieldName] !== false)
				{
					$fields[$fieldName] = $field->getDefaultValue();
				}
			}
		}

		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\UpdateResult();
		$entity = RequisiteTable::getEntity();
		$entity_primary = $entity->getPrimaryArray();

		// normalize primary
		if ($id === null)
		{
			$id = array();

			// extract primary from data array
			foreach ($entity_primary as $key)
			{
				/** @var Entity\ScalarField $field  */
				$field = $entity->getField($key);
				if ($field->isAutocomplete())
				{
					continue;
				}

				if (!isset($fields[$key]))
				{
					throw new Main\ArgumentException(sprintf(
						'Primary `%s` was not found when trying to query %s row.', $key, $entity->getName()
					));
				}

				$id[$key] = $fields[$key];
			}
		}
		elseif (is_scalar($id))
		{
			if (count($entity_primary) > 1)
			{
				throw new Main\ArgumentException(sprintf(
					'Require multi primary {`%s`}, but one scalar value "%s" found when trying to query %s row.',
					join('`, `', $entity_primary), $id, $entity->getName()
				));
			}

			$id = array($entity_primary[0] => $id);
		}

		// validate primary
		if (is_array($id))
		{
			if(empty($id))
			{
				throw new Main\ArgumentException(sprintf(
					'Empty primary found when trying to query %s row.', $entity->getName()
				));
			}

			foreach (array_keys($id) as $key)
			{
				if (!in_array($key, $entity_primary, true))
				{
					throw new Main\ArgumentException(sprintf(
						'Unknown primary `%s` found when trying to query %s row.',
						$key, $entity->getName()
					));
				}
			}
		}
		else
		{
			throw new Main\ArgumentException(sprintf(
				'Unknown type of primary "%s" found when trying to query %s row.', gettype($id), $entity->getName()
			));
		}
		foreach ($id as $key => $value)
		{
			if (!is_scalar($value) && !($value instanceof Main\Type\Date))
			{
				throw new Main\ArgumentException(sprintf(
					'Unknown value type "%s" for primary "%s" found when trying to query %s row.',
					gettype($value), $key, $entity->getName()
				));
			}
		}

		try
		{
			// uf values
			$ufdata = array();

			// separate userfields
			if ($entity->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields($entity->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $fields))
					{
						// copy value
						$ufdata[$userfield['FIELD_NAME']] = $fields[$userfield['FIELD_NAME']];

						// remove original
						unset($fields[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			RequisiteTable::checkFields($result, $id, $fields);

			// check uf data
			if (!empty($ufdata))
			{
				if (!$USER_FIELD_MANAGER->CheckFields($entity->getUfId(), end($id), $ufdata))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new Entity\EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($fields + $ufdata))
			{
				$result->addError(new Entity\EntityError("There is no data to update."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	public function update($id, $fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$addresses = null;
		if (isset($fields[self::ADDRESS]))
		{
			$addresses = $fields[self::ADDRESS];
			unset($fields[self::ADDRESS]);
		}

		$entityBeforeUpdate = null;
		$entityTypeId = \CCrmOwnerType::Undefined;
		$entityId = 0;
		$entityTypeIdModified = $entityIdModified = false;
		if (isset($fields['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
			if ($entityBeforeUpdate === null)
				$entityBeforeUpdate = EntityRequisite::getOwnerEntityById($id);
			if (\CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
				&& $entityBeforeUpdate['ENTITY_ID'] > 0)
			{
				if ($entityTypeId !== $entityBeforeUpdate['ENTITY_TYPE_ID'])
					$entityTypeIdModified = true;
				if (!$entityIdModified)
					$entityId = (int)$entityBeforeUpdate['ENTITY_ID'];
			}
		}
		if (isset($fields['ENTITY_ID']))
		{
			$entityId = (int)$fields['ENTITY_ID'];
			if ($entityBeforeUpdate === null)
				$entityBeforeUpdate = EntityRequisite::getOwnerEntityById($id);
			if (\CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
				&& $entityBeforeUpdate['ENTITY_ID'] > 0)
			{
				if ($entityId !== $entityBeforeUpdate['ENTITY_ID'])
					$entityIdModified = true;
				if (!$entityTypeIdModified)
					$entityTypeId = (int)$entityBeforeUpdate['ENTITY_TYPE_ID'];
			}
		}

		// rewrite some fields
		$entity = RequisiteTable::getEntity();
		foreach ($entity->getFields() as $field)
		{
			$fieldName = $field->getName();
			if ($field instanceof Entity\BooleanField && array_key_exists($fieldName, $fields))
			{
				if ($fields[$fieldName] !== 'Y' && $fields[$fieldName] !== 'N'
					&& $fields[$fieldName] !== true && $fields[$fieldName] !== false)
				{
					$fields[$fieldName] = $field->getDefaultValue();
				}
			}
		}

		$result = RequisiteTable::update($id, $fields);
		if ($result->isSuccess())
		{
			if (is_array($addresses))
			{
				foreach($addresses as $addressTypeId => $address)
				{
					if(!is_array($address) || empty($address))
					{
						continue;
					}

					if(isset($address['DELETED']) && $address['DELETED'] === 'Y')
					{
						RequisiteAddress::unregister(\CCrmOwnerType::Requisite, $id, $addressTypeId);
						continue;
					}

					$actualAddressFields = array();
					if(isset($address['ADDRESS_1']))
					{
						$actualAddressFields['ADDRESS_1'] = $address['ADDRESS_1'];
					}
					if(isset($address['ADDRESS_2']))
					{
						$actualAddressFields['ADDRESS_2'] = $address['ADDRESS_2'];
					}
					if(isset($address['CITY']))
					{
						$actualAddressFields['CITY'] = $address['CITY'];
					}
					if(isset($address['POSTAL_CODE']))
					{
						$actualAddressFields['POSTAL_CODE'] = $address['POSTAL_CODE'];
					}
					if(isset($address['REGION']))
					{
						$actualAddressFields['REGION'] = $address['REGION'];
					}
					if(isset($address['PROVINCE']))
					{
						$actualAddressFields['PROVINCE'] = $address['PROVINCE'];
					}
					if(isset($address['COUNTRY']))
					{
						$actualAddressFields['COUNTRY'] = $address['COUNTRY'];
					}
					if(isset($address['COUNTRY_CODE']))
					{
						$actualAddressFields['COUNTRY_CODE'] = $address['COUNTRY_CODE'];
					}

					if(!empty($actualAddressFields))
					{
						$dbResult = RequisiteTable::getList(
							array(
								'filter' => array('=ID' => $id),
								'select' => array('ENTITY_TYPE_ID', 'ENTITY_ID')
							)
						);

						$actualFields = $dbResult->fetch();
						if(is_array($actualFields))
						{
							$anchorTypeID = isset($actualFields['ENTITY_TYPE_ID']) ? (int)$actualFields['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
							$anchorID = isset($actualFields['ENTITY_ID']) ? (int)$actualFields['ENTITY_ID'] : 0;
							if(!\CCrmOwnerType::IsDefined($anchorTypeID) || $anchorID <= 0)
							{
								$anchorTypeID = \CCrmOwnerType::Requisite;
								$anchorID = $id;
							}

							$actualAddressFields['ANCHOR_TYPE_ID'] = $anchorTypeID;
							$actualAddressFields['ANCHOR_ID'] = $anchorID;

							EntityAddress::register(\CCrmOwnerType::Requisite, $id, $addressTypeId, $actualAddressFields);
						}
					}
				}
			}

			if ($entityTypeIdModified || $entityIdModified)
			{
				DuplicateRequisiteCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'], $entityBeforeUpdate['ENTITY_ID']
				);

				DuplicateBankDetailCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'], $entityBeforeUpdate['ENTITY_ID']
				);

				DuplicateRequisiteCriterion::unregister($entityTypeId, $entityId);
			}

			if (isset($fields['ENTITY_TYPE_ID']) && isset($fields['ENTITY_ID']))
			{
				DuplicateRequisiteCriterion::registerByEntity(
					(int)$fields['ENTITY_TYPE_ID'], (int)$fields['ENTITY_ID']
				);
				if ($entityTypeIdModified || $entityIdModified)
				{
					DuplicateBankDetailCriterion::registerByEntity(
						(int)$fields['ENTITY_TYPE_ID'], (int)$fields['ENTITY_ID']
					);
				}
			}
			else
			{
				DuplicateRequisiteCriterion::registerByRequisite($id);
				if ($entityTypeIdModified || $entityIdModified)
				{
					DuplicateBankDetailCriterion::registerByParent(\CCrmOwnerType::Requisite, $id);
				}
			}
		}

		//region Send event
		if ($result->isSuccess())
		{
			$event = new Main\Event('crm', 'OnAfterRequisiteUpdate', array('id' => $id, 'fields' => $fields));
			$event->send();
		}
		//endregion Send event

		return $result;
	}

	public function delete($id, $options = array())
	{
		$entityInfo = self::getOwnerEntityById($id);

		EntityLink::unregisterByRequisite($id);
		RequisiteAddress::deleteByEntityId($id);

		$bankDetail = EntityBankDetail::getSingleInstance();
		$bankDetail->deleteByEntity(\CCrmOwnerType::Requisite, $id);

		$result = RequisiteTable::delete($id);

		if ($result->isSuccess()
			&& \CCrmOwnerType::IsDefined($entityInfo['ENTITY_TYPE_ID']) && $entityInfo['ENTITY_ID'] > 0)
		{
			DuplicateRequisiteCriterion::RegisterByEntity($entityInfo['ENTITY_TYPE_ID'], $entityInfo['ENTITY_ID']);
		}

		//region Send event
		if ($result->isSuccess())
		{
			$event = new Main\Event('crm', 'OnAfterRequisiteDelete', array('id' => $id));
			$event->send();
		}
		//endregion Send event

		return $result;
	}

	public function deleteByEntity($entityTypeId, $entityId, $options = array())
	{
		$result = new Main\Result();

		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		//Usually check is disabled for suspended types (SuspendedContact and SuspendedCompany)
		$enableTypeCheck = !isset($options['enableCheck']) || $options['enableCheck'] === true;
		if ($enableTypeCheck && !self::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		if ($entityId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_INVALID_ENTITY_ID'),
					self::ERR_INVALID_ENTITY_ID
				)
			);
			return $result;
		}

		$res = $this->getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ENTITY_ID' => $entityId
				),
				'select' => array('ID')
			)
		);
		$cnt = 0;
		while ($row = $res->fetch())
		{
			$cnt++;
			$delResult = $this->delete($row['ID']);
			if (!$delResult->isSuccess())
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_REQUISITE_ERR_ON_DELETE', array('#ID#', $row['ID'])),
						self::ERR_ON_DELETE
					)
				);
			}
		}

		if ($cnt === 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_NOTHING_TO_DELETE'),
					self::ERR_NOTHING_TO_DELETE
				)
			);
		}

		return $result;
	}

	public function getEntityRequisiteIDs($entityTypeId, $entityId)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		$dbResult = $this->getList(
			array(
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeId, '=ENTITY_ID' => $entityId),
				'select' => array('ID')
			)
		);

		$results = array();
		while ($fields = $dbResult->fetch())
		{
			$results[] = $fields['ID'];
		}
		return $results;
	}

	/**
	 * Unbind requisites from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		Main\Application::getConnection()->queryExecute(/** @lang text */
			"UPDATE b_crm_requisite SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} 
					WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
	}

	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable)
				),
				'ENTITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
							\CCrmFieldInfoAttr::Required,
							\CCrmFieldInfoAttr::Immutable)
				),
				'PRESET_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
							\CCrmFieldInfoAttr::Required,
							\CCrmFieldInfoAttr::Immutable)
				),
				'DATE_CREATE' => array(
						'TYPE' => 'datetime',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
						'TYPE' => 'datetime',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'CREATED_BY_ID' => array(
						'TYPE' => 'user',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
						'TYPE' => 'user',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array('TYPE' => 'string'),
				'CODE' => array('TYPE' => 'string'),
				'XML_ID' => array('TYPE' => 'string'),
				'ORIGINATOR_ID' => array('TYPE' => 'string'),
				'ACTIVE' => array('TYPE' => 'char'),
				'SORT' => array('TYPE' => 'integer'),
				'RQ_NAME' => array('TYPE' => 'string'),
				'RQ_FIRST_NAME' => array('TYPE' => 'string'),
				'RQ_LAST_NAME' => array('TYPE' => 'string'),
				'RQ_SECOND_NAME' => array('TYPE' => 'string'),
				'RQ_COMPANY_NAME' => array('TYPE' => 'string'),
				'RQ_COMPANY_FULL_NAME' => array('TYPE' => 'string'),
				'RQ_COMPANY_REG_DATE' => array('TYPE' => 'datetime'),
				'RQ_DIRECTOR' => array('TYPE' => 'string'),
				'RQ_ACCOUNTANT' => array('TYPE' => 'string'),
				'RQ_CEO_NAME' => array('TYPE' => 'string'),
				'RQ_CEO_WORK_POS' => array('TYPE' => 'string'),
				'RQ_CONTACT' => array('TYPE' => 'string'),
				'RQ_EMAIL' => array('TYPE' => 'string'),
				'RQ_PHONE' => array('TYPE' => 'string'),
				'RQ_FAX' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_SER' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_NUM' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_PERS_NUM' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_DATE' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_ISSUED_BY' => array('TYPE' => 'string'),
				'RQ_IDENT_DOC_DEP_CODE' => array('TYPE' => 'string'),
				'RQ_INN' => array('TYPE' => 'string'),
				'RQ_KPP' => array('TYPE' => 'string'),
				'RQ_USRLE' => array('TYPE' => 'string'),
				'RQ_IFNS' =>array('TYPE' => 'string'),
				'RQ_OGRN' =>array('TYPE' => 'string'),
				'RQ_OGRNIP' => array('TYPE' => 'string'),
				'RQ_OKPO' => array('TYPE' => 'string'),
				'RQ_OKTMO' => array('TYPE' => 'string'),
				'RQ_OKVED' => array('TYPE' => 'string'),
				'RQ_EDRPOU' => array('TYPE' => 'string'),
				'RQ_DRFO' => array('TYPE' => 'string'),
				'RQ_KBE' => array('TYPE' => 'string'),
				'RQ_IIN' => array('TYPE' => 'string'),
				'RQ_BIN' => array('TYPE' => 'string'),
				'RQ_ST_CERT_SER' => array('TYPE' => 'string'),
				'RQ_ST_CERT_NUM' => array('TYPE' => 'string'),
				'RQ_ST_CERT_DATE' => array('TYPE' => 'string'),
				'RQ_VAT_PAYER' => array('TYPE' => 'char'),
				'RQ_VAT_ID' => array('TYPE' => 'string'),
				'RQ_VAT_CERT_SER' => array('TYPE' => 'string'),
				'RQ_VAT_CERT_NUM' => array('TYPE' => 'string'),
				'RQ_VAT_CERT_DATE' => array('TYPE' => 'string'),
				'RQ_RESIDENCE_COUNTRY' => array('TYPE' => 'string'),
				'RQ_BASE_DOC' => array('TYPE' => 'string')
			);
		}
		return self::$FIELD_INFOS;
	}

	public static function getBasicFieldsInfo()
	{
		$result = array();

		$requisite = self::getSingleInstance();
		$rqFieldsMap = array_fill_keys($requisite->getRqFields(), true);

		foreach (self::getFieldsInfo() as $fieldName => $fieldInfo)
		{
			if (!isset($rqFieldsMap[$fieldName]))
				$result[$fieldName] = $fieldInfo;
		}

		return $result;
	}

	public static function getBasicExportFieldsInfo()
	{
		$result = array(
			'ID' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_ID'),
				'type' => 'integer'
			),
			'PRESET_ID' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_PRESET_ID'),
				'type' => 'integer'
			),
			'PRESET_NAME' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_PRESET_NAME'),
				'type' => 'string'
			),
			'PRESET_COUNTRY_ID' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_PRESET_COUNTRY_ID'),
				'type' => 'integer'
			),
			'PRESET_COUNTRY_NAME' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_PRESET_COUNTRY_NAME'),
				'type' => 'string'
			),
			'NAME' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_NAME'),
				'type' => 'string'
			),
			'ACTIVE' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_ACTIVE'),
				'type' => 'boolean'
			),
			'SORT' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_SORT'),
				'type' => 'integer'
			)
		);

		return $result;
	}

	private static $rqFields = array(
		'RQ_NAME',
		'RQ_FIRST_NAME',
		'RQ_LAST_NAME',
		'RQ_SECOND_NAME',
		'RQ_COMPANY_NAME',
		'RQ_COMPANY_FULL_NAME',
		'RQ_COMPANY_REG_DATE',
		'RQ_DIRECTOR',
		'RQ_ACCOUNTANT',
		'RQ_CEO_NAME',
		'RQ_CEO_WORK_POS',
		'RQ_ADDR',
		'RQ_CONTACT',
		'RQ_EMAIL',
		'RQ_PHONE',
		'RQ_FAX',
		'RQ_IDENT_DOC',
		'RQ_IDENT_DOC_SER',
		'RQ_IDENT_DOC_NUM',
		'RQ_IDENT_DOC_PERS_NUM',
		'RQ_IDENT_DOC_DATE',
		'RQ_IDENT_DOC_ISSUED_BY',
		'RQ_IDENT_DOC_DEP_CODE',
		'RQ_INN',
		'RQ_KPP',
		'RQ_USRLE',
		'RQ_IFNS',
		'RQ_OGRN',
		'RQ_OGRNIP',
		'RQ_OKPO',
		'RQ_OKTMO',
		'RQ_OKVED',
		'RQ_EDRPOU',
		'RQ_DRFO',
		'RQ_KBE',
		'RQ_IIN',
		'RQ_BIN',
		'RQ_ST_CERT_SER',
		'RQ_ST_CERT_NUM',
		'RQ_ST_CERT_DATE',
		'RQ_VAT_PAYER',
		'RQ_VAT_ID',
		'RQ_VAT_CERT_SER',
		'RQ_VAT_CERT_NUM',
		'RQ_VAT_CERT_DATE',
		'RQ_RESIDENCE_COUNTRY',
		'RQ_BASE_DOC'
	);

	private static $rqFiltrableFields = null;

	public function getRqFields()
	{
		return self::$rqFields;
	}

	public function getRqFiltrableFields()
	{
		if (self::$rqFiltrableFields === null)
		{
			self::$rqFiltrableFields = array(
				'RQ_NAME',
				'RQ_FIRST_NAME',
				'RQ_LAST_NAME',
				'RQ_SECOND_NAME',
				'RQ_COMPANY_NAME',
				'RQ_COMPANY_FULL_NAME',
				'RQ_ADDR',
				'RQ_CONTACT',
				'RQ_EMAIL',
				'RQ_PHONE',
				'RQ_FAX',
				'RQ_IDENT_DOC_SER',
				'RQ_IDENT_DOC_NUM',
				'RQ_IDENT_DOC_PERS_NUM',
				'RQ_INN',
				'RQ_KPP',
				'RQ_USRLE',
				'RQ_OKPO',
				'RQ_EDRPOU',
				'RQ_DRFO',
				'RQ_IIN',
				'RQ_BIN',
				'RQ_ST_CERT_SER',
				'RQ_ST_CERT_NUM',
				'RQ_VAT_ID',
				'RQ_VAT_PAYER',
				'RQ_VAT_CERT_SER',
				'RQ_VAT_CERT_NUM'
			);
		}
		
		return self::$rqFiltrableFields;
	}

	public function getAddressFields()
	{
		return array('RQ_ADDR');
	}

	public static function getAddressFiltrableFields()
	{
		return array(
			'ADDRESS_1',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY'
		);
	}

	public function separateAddressFields(&$fields)
	{
		$addrFields = array();

		foreach ($this->getAddressFields() as $prefix)
		{
			if (array_key_exists($prefix, $fields))
				unset($fields[$prefix]);
			foreach ($this->getAddressFieldPostfixes() as $postfix)
			{
				if (array_key_exists($prefix.$postfix, $fields))
				{
					$addrFields[$prefix.$postfix] = $fields[$prefix.$postfix];
					unset($fields[$prefix.$postfix]);
				}
			}
		}

		return $addrFields;
	}

	public function resolveAddressTypeByFieldName($fieldName)
	{
		return $fieldName === 'RQ_ADDR' ? RequisiteAddress::Primary : RequisiteAddress::Undefined;
	}

	public function resolveFieldNameByAddressType($addrType)
	{
		return $addrType === RequisiteAddress::Primary ? 'RQ_ADDR' : '';
	}

	public function getUserFields()
	{
		global $USER_FIELD_MANAGER;
		$result = array();

		foreach ($USER_FIELD_MANAGER->GetUserFields($this->getUfId()) as $field)
			$result[] = $field['FIELD_NAME'];

		return $result;
	}

	public static function getAllowedRqFieldCountries()
	{
		return self::$allowedRqFieldCountryIds;
	}

	public function getUserFieldsTitles()
	{
		global $USER_FIELD_MANAGER;

		if (self::$userFieldTitles === null)
		{
			$titles = array();

			foreach ($USER_FIELD_MANAGER->GetUserFields($this->getUfId(), 0, LANGUAGE_ID) as $fieldInfo)
			{
				$fieldTitle = '';
				if (isset($fieldInfo['EDIT_FORM_LABEL']) && !empty($fieldInfo['EDIT_FORM_LABEL']))
					$fieldTitle = $fieldInfo['EDIT_FORM_LABEL'];
				if (isset($fieldInfo['LIST_COLUMN_LABEL']) && !empty($fieldInfo['LIST_COLUMN_LABEL']))
					$fieldTitle = $fieldInfo['LIST_COLUMN_LABEL'];
				$titles[$fieldInfo['FIELD_NAME']] = is_string($fieldTitle) ? $fieldTitle : '';
			}

			self::$userFieldTitles = $titles;
		}

		return self::$userFieldTitles;
	}

	public function updateUserFieldTitle($ufId, $title, $checkExist = true)
	{
		$ufId = (int)$ufId;
		if ($ufId <= 0)
		{
			return false;
		}

		if (!is_string($title) || strlen($title) <= 0)
		{
			return false;
		}

		if ($checkExist)
		{
			$userType  = new \CUserTypeEntity;
			$res = $userType->GetList(array(), array('ENTITY_ID' => $this->getUfId(), 'ID' => $ufId));
			if (!(is_object($res) && $res->Fetch()))
			{
				return false;
			}
		}

		$fields = array(
			'EDIT_FORM_LABEL' => array(),
			'LIST_COLUMN_LABEL' => array(),
			'LIST_FILTER_LABEL' => array()
		);

		$langDbResult = \CLanguage::GetList($by = '', $order = '');
		while($lang = $langDbResult->Fetch())
		{
			$lid = $lang['LID'];
			foreach (array_keys($fields) as $key)
			{
				$fields[$key][$lid] = $title;
			}
		}

		$userField = new \CUserTypeEntity();

		return $userField->Update($ufId, $fields);
	}

	public function deleteUserField($ufId, $checkExist = true)
	{
		$ufId = (int)$ufId;
		if ($ufId <= 0)
		{
			return false;
		}

		if ($checkExist)
		{
			$userType  = new \CUserTypeEntity;
			$res = $userType->GetList(array(), array('ENTITY_ID' => $this->getUfId(), 'ID' => $ufId));
			if (!(is_object($res) && $res->Fetch()))
			{
				return false;
			}
		}

		$userField = new \CUserTypeEntity();

		$res = $userField->Delete($ufId);

		return (bool)$res;
	}

	public function getFieldsTitles($countryId = 0)
	{
		global $USER_FIELD_MANAGER;
		$result = array();

		$countryId = (int)$countryId;
		if (!in_array($countryId, self::getAllowedRqFieldCountries()))
		{
			$countryId = EntityPreset::getCurrentCountryId();
			if ($countryId <= 0)
				$countryId = 122;
		}

		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
			$rqFields[$rqFieldName] = true;

		$rqFieldTitleMap = $this->getRqFieldTitleMap();

		Loc::loadMessages(Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/requisite.php');

		foreach (RequisiteTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($fieldInfo['reference']) && $fieldInfo['data_type'] !== 'Address')
				continue;

			if (isset($rqFields[$fieldName]) && $fieldInfo['data_type'] !== 'Address')
			{
				$title = '';
				if (isset($rqFieldTitleMap[$fieldName][$countryId]))
				{
					if (empty($rqFieldTitleMap[$fieldName][$countryId]))
						$title = $fieldName;
					else
						$title = $rqFieldTitleMap[$fieldName][$countryId];

				}
				$result[$fieldName] = $title;
			}
			else
			{
				$fieldTitle = (isset($fieldInfo['title']) && !empty($fieldInfo['title'])) ? $fieldInfo['title'] : GetMessage('CRM_REQUISITE_ENTITY_'.$fieldName.'_FIELD');
				$result[$fieldName] = is_string($fieldTitle) ? $fieldTitle : '';
			}
		}

		$result = array_merge($result, $this->getUserFieldsTitles());

		return $result;
	}

	public function getRqFieldsCountryMap()
	{
		if (self::$rqFieldCountryMap === null)
		{
			// ru - 1, by - 4, kz - 6, ua - 14, de - 46, us - 122
			self::$rqFieldCountryMap = array(
				'RQ_NAME' => array(1, 4, 6, 14, 46, 122),
				'RQ_FIRST_NAME' => array(1, 46, 122),
				'RQ_LAST_NAME' => array(1, 46, 122),
				'RQ_SECOND_NAME' => array(1),
				'RQ_COMPANY_NAME' => array(1, 4, 6, 14, 46, 122),
				'RQ_COMPANY_FULL_NAME' => array(1, 4, 6),
				'RQ_COMPANY_REG_DATE' => array(1, 4),
				'RQ_DIRECTOR' => array(1, 4, 14),
				'RQ_ACCOUNTANT' => array(1, 4, 14),
				'RQ_CEO_NAME' => array(6),
				'RQ_CEO_WORK_POS' => array(6),
				'RQ_ADDR' => array(1, 4, 6, 14, 46, 122),
				'RQ_CONTACT' => array(1, 4, 6, 14, 46, 122),
				'RQ_EMAIL' => array(1, 4, 6, 14, 46, 122),
				'RQ_PHONE' => array(1, 4, 6, 14, 46, 122),
				'RQ_FAX' => array(1, 4, 6, 14, 46, 122),
				'RQ_IDENT_DOC' => array(1, 4),
				'RQ_IDENT_DOC_SER' => array(1, 4),
				'RQ_IDENT_DOC_NUM' => array(1, 4),
				'RQ_IDENT_DOC_PERS_NUM' => array(4),
				'RQ_IDENT_DOC_DATE' => array(1, 4),
				'RQ_IDENT_DOC_ISSUED_BY' => array(1, 4),
				'RQ_IDENT_DOC_DEP_CODE' => array(1),
				'RQ_INN' => array(1, 4, 6, 14, 46),
				'RQ_KPP' => array(1),
				'RQ_USRLE' => array(46),
				'RQ_IFNS' => array(1),
				'RQ_OGRN' => array(1),
				'RQ_OGRNIP' => array(1),
				'RQ_OKPO' => array(1, 4, 6),
				'RQ_OKTMO' => array(1),
				'RQ_OKVED' => array(1),
				'RQ_EDRPOU' => array(14),
				'RQ_DRFO' => array(14),
				'RQ_KBE' => array(6),
				'RQ_IIN' => array(6),
				'RQ_BIN' => array(6),
				'RQ_ST_CERT_SER' => array(1),
				'RQ_ST_CERT_NUM' => array(1, 4),
				'RQ_ST_CERT_DATE' => array(1, 4),
				'RQ_VAT_PAYER' => array(14),
				'RQ_VAT_ID' => array(46,122),
				'RQ_VAT_CERT_SER' => array(6),
				'RQ_VAT_CERT_NUM' => array(6, 14),
				'RQ_VAT_CERT_DATE' => array(6),
				'RQ_RESIDENCE_COUNTRY' => array(6),
				'RQ_BASE_DOC' => array(4)
			);
		}

		return self::$rqFieldCountryMap;
	}

	public function getRqFieldTitleMap()
	{
		if (self::$rqFieldTitleMap === null)
		{
			$titleMap = array();
			$countryIds = array();
			foreach ($this->getRqFieldsCountryMap() as $fieldName => $fieldCountryIds)
			{
				if (is_array($fieldCountryIds))
				{
					foreach ($fieldCountryIds as $countryId)
					{
						$titleMap[$fieldName][$countryId] = '';
						if (!isset($countryIds[$countryId]))
							$countryIds[$countryId] = true;
					}
				}
			}
			foreach (array_keys($countryIds) as $countryId)
			{
				$langId = '';
				switch ($countryId)
				{
					case 1:                // ru
						$langId = 'ru';
						break;
					case 4:                // by
						$langId = 'by';
						break;
					case 6:                // kz
						$langId = 'kz';
						break;
					case 14:               // ua
						$langId = 'ua';
						break;
					case 46:               // de
						$langId = 'de';
						break;
					case 122:              // us
						$langId = 'en';
						break;
				}

				if (!empty($langId))
				{
					$messages = Loc::loadLanguageFile(
						Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/requisite.php',
						$langId
					);
					foreach ($titleMap as $fieldName => &$titlesByCountry)
					{
						if (isset($titlesByCountry[$countryId]))
						{
							$messageId = 'CRM_REQUISITE_ENTITY_'.$fieldName.'_FIELD';
							$altMessageId = 'CRM_REQUISITE_ENTITY_'.$fieldName.'_'.strtoupper($langId).'_FIELD';
							$title = GetMessage($altMessageId);

							if (isset($messages[$altMessageId]))
							{
								$titlesByCountry[$countryId] = $messages[$altMessageId];
							}
							else if (is_string($title) && !empty($title))
							{
								$titlesByCountry[$countryId] = $title;
							}
							else if (isset($messages[$messageId]))
							{
								$titlesByCountry[$countryId] = $messages[$messageId];
							}
						}
					}
					unset($titlesByCountry);
				}
			}
			self::$rqFieldTitleMap = $titleMap;
		}

		return self::$rqFieldTitleMap;
	}

	public function getFormFieldsTypes()
	{
		return array(
			'RQ_VAT_PAYER' => 'checkbox'
		);
	}

	public function getFormFieldsInfo($countryId = 0)
	{
		$result = array();

		$formTypes = $this->getFormFieldsTypes();
		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
			$rqFields[$rqFieldName] = true;
		$fieldTitles = $this->getFieldsTitles($countryId);
		foreach (RequisiteTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($fieldInfo['reference']) && $fieldInfo['data_type'] !== 'Address')
				continue;

			$fieldTitle = (isset($fieldTitles[$fieldName])) ? $fieldTitles[$fieldName] : '';
			$result[$fieldName] = array(
				'title' => is_string($fieldTitle) ? $fieldTitle : '',
				'type' => $fieldInfo['data_type'],
				'required' => (isset($fieldInfo['required']) && $fieldInfo['required']),
				'formType' => isset($formTypes[$fieldName]) ? $formTypes[$fieldName] : 'text',
				'isRQ' => isset($rqFields[$fieldName]),
				'isUF' => false
			);
		}

		$result = array_merge($result, $this->getFormUserFieldsInfo());

		return $result;
	}

	public function getFormUserFieldsInfo()
	{
		global $USER_FIELD_MANAGER;
		$result = array();

		foreach ($USER_FIELD_MANAGER->GetUserFields($this->getUfId(), 0, LANGUAGE_ID) as $fieldInfo)
		{
			$fieldTitle = '';
			if (isset($fieldInfo['EDIT_FORM_LABEL']) && !empty($fieldInfo['EDIT_FORM_LABEL']))
				$fieldTitle = $fieldInfo['EDIT_FORM_LABEL'];
			if (isset($fieldInfo['LIST_COLUMN_LABEL']) && !empty($fieldInfo['LIST_COLUMN_LABEL']))
				$fieldTitle = $fieldInfo['LIST_COLUMN_LABEL'];
			$result[$fieldInfo['FIELD_NAME']] = array(
				'id' => (int)$fieldInfo['ID'],
				'title' => is_string($fieldTitle) ? $fieldTitle : '',
				'type' => $fieldInfo['USER_TYPE_ID'],
				'required' => (isset($fieldInfo['MANDATORY']) && $fieldInfo['MANDATORY'] === 'Y'),
				'formType' => '',
				'isRQ' => true,
				'isUF' => true
			);
		}

		return $result;
	}

	public static function checkEntityType($entityTypeId)
	{
		$entityTypeId = intval($entityTypeId);

		if ($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
			return false;

		return true;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact
		)
		{
			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckCreatePermission($entityType);
		}

		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact
		)
		{
			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckUpdatePermission($entityType, $entityID);
		}

		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact
		)
		{
			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckDeletePermission($entityType, $entityID);
		}

		return false;
	}

	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(intval($entityTypeID)<=0 && intval($entityID) <= 0)
		{
			return (\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Company, 0) &&
					\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Contact, 0));
		}

		if ($entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact
		)
		{
			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityID);
		}

		return false;
	}

	public function checkUpdatePermission($id)
	{
		$r = $this->getById($id);
		if(is_array($r))
			return self::checkUpdatePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		else
			return false;
	}

	public function checkReadPermission($id = 0)
	{
		if(intval($id)<=0)
		{
			return self::checkReadPermissionOwnerEntity();
		}

		$r = $this->getById($id);
		if(is_array($r))
			return self::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		else
			return false;
	}

	public function validateEntityExists($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if (!self::checkEntityType($entityTypeId))
			return false;

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			if (!\CCrmCompany::Exists($entityId))
				return false;
		}
		else if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			if (!\CCrmContact::Exists($entityId))
				return false;
		}

		return true;
	}

	public function validateEntityReadPermission($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityId <= 0)
			return false;

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			if (!\CCrmCompany::CheckReadPermission($entityId))
				return false;
		}
		else if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			if (!\CCrmContact::CheckReadPermission($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function validateEntityUpdatePermission($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityId <= 0)
			return false;

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			if (!\CCrmCompany::CheckUpdatePermission($entityId))
				return false;
		}
		else if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			if (!\CCrmContact::CheckUpdatePermission($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function prepareViewData($fields, $fieldsInViewMap, $options = array())
	{
		$optionValueHtml = false;
		$optionValueText = true;

		if (is_array($options) && isset($options['VALUE_HTML'])
			&& ($options['VALUE_HTML'] === 'Y' || $options['VALUE_HTML'] === true))
		{
			$optionValueHtml = true;
		}

		if (is_array($options) && isset($options['VALUE_TEXT']) &&
			!($options['VALUE_TEXT'] === 'Y' || $options['VALUE_TEXT'] === true))
		{
			$optionValueText = false;
		}
		else if ($optionValueHtml)
		{
			$optionValueText = false;
		}

		$result = array(
			'title' => '',
			'fields' => array()
		);

		// rewrite titles
		$presetFieldTitles = array();
		$presetId = 0;
		$presetCountryId = 0;
		$currentCountryId = EntityPreset::getCurrentCountryId();
		if (isset($fields['PRESET_ID']))
			$presetId = (int)$fields['PRESET_ID'];
		if ($presetId > 0)
		{
			$preset = EntityPreset::getSingleInstance();
			$presetInfo = $preset->getById($presetId);
			if (is_array($presetInfo) && is_array($presetInfo['SETTINGS']))
			{
				$presetCountryId = $presetInfo['COUNTRY_ID'];
				$presetFieldsInfo = $preset->settingsGetFields($presetInfo['SETTINGS']);
				foreach ($presetFieldsInfo as $fieldInfo)
				{
					if (isset($fieldInfo['FIELD_NAME']))
					{
						$presetFieldTitles[$fieldInfo['FIELD_NAME']] =
							(isset($fieldInfo['FIELD_TITLE'])) ? strval($fieldInfo['FIELD_TITLE']) : "";
					}
				}
			}
			unset($preset, $presetInfo, $presetFieldsInfo, $fieldInfo);
		}
		unset($presetId);

		$fieldsInfo = $this->getFormFieldsInfo($presetCountryId);

		if (!empty($presetFieldTitles))
		{
			foreach ($fieldsInfo as $fieldName => &$fieldInfo)
			{
				if (isset($presetFieldTitles[$fieldName])
					&& !empty($presetFieldTitles[$fieldName]))
				{
					$fieldInfo['title'] = strval($presetFieldTitles[$fieldName]);
				}
			}
			unset($fieldInfo);
		}

		$addrFormFieldParsed = array();
		foreach ($fields as $fieldName => $fieldValue)
		{
			$skip = false;
			if ($fieldValue instanceof Main\Type\DateTime)
				$fieldValue = $fieldValue->toString();

			if ($fieldName === 'NAME')
			{
				$result['title'] = $fieldValue;
			}
			else
			{
				if (isset($fieldsInViewMap[$fieldName]) && isset($fieldsInfo[$fieldName]))
				{
					$fieldInfo = $fieldsInfo[$fieldName];
					if ($fieldInfo['isRQ'])
					{
						$textValue = '';
						if ($fieldInfo['type'] === 'boolean')
						{
							if ($fieldInfo['isUF'])
							{
								$fieldValue = intval($fieldValue) > 0 ? 'Y' : 'N';
							}
							else
							{
								if (is_bool($fieldValue))
								{
									$fieldValue = $fieldValue ? 'Y' : 'N';
								}
							}
							$textValue = ($fieldValue === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'));
						}
						else if ($fieldInfo['type'] === 'Address')
						{
							if ($fieldName !== EntityRequisite::ADDRESS)
							{
								$skip = true;
							}
							else
							{
								$addressTypeInfos = RequisiteAddress::getClientTypeInfos();
								$addressTypeInfosDesc = RequisiteAddress::getTypeInfos();
								foreach ($addressTypeInfos as $k => $typeInfo)
								{
									$addressTypeInfos[$k]['desc'] = isset($addressTypeInfosDesc[$typeInfo['id']]) ?
										$addressTypeInfosDesc[$typeInfo['id']]['DESCRIPTION'] : '';
								}
								unset($addressTypeInfosDesc, $k, $typeInfo);
								$addresses = $fieldValue;
								if (is_array($addresses) && !empty($addresses))
								{
									foreach ($addressTypeInfos as $addressTypeInfo)
									{
										if (is_array($addresses[$addressTypeInfo['id']]))
										{
											$textValue = Format\RequisiteAddressFormatter::formatByCountry(
												$addresses[$addressTypeInfo['id']],
												$presetCountryId,
												array(
													'SEPARATOR' => Format\AddressSeparator::NewLine,
													'NL2BR' => false,
													'TYPE_ID' => RequisiteAddress::Primary
												)
											);
											if (!empty($textValue))
											{
												$resultItem = array(
													'name' => $fieldName,
													'title' => $addressTypeInfo['name'],
													'type' => $addressTypeInfo['desc'],
													'subType' => $addressTypeInfo['id'],
													'formType' => $fieldInfo['formType']
												);
												if ($optionValueText)
												{
													$resultItem['textValue'] = $textValue;
												}
												if ($optionValueHtml)
												{
													$resultItem['htmlValue'] = nl2br(htmlspecialcharsbx($textValue));
												}
												$result['fields'][] = $resultItem;
											}
										}
									}
								}
								$addrFormFieldParsed[$fieldName] = true;
								$skip = true;
							}
						}
						else
						{
							$textValue = strval($fieldValue);

							if (strlen($textValue) <= 0)
							{
								$skip = true;
							}
						}

						if ($fieldInfo['title'] == '')
						{
							$skip = true;
						}

						if (!$skip)
						{
							$resultItem = array(
								'name' => $fieldName,
								'title' => $fieldInfo['title'],
								'type' => $fieldInfo['type'],
								'subType' => 0,
								'formType' => $fieldInfo['formType']
							);
							if ($optionValueText)
							{
								$resultItem['textValue'] = $textValue;
							}
							if ($optionValueHtml)
							{
								$resultItem['htmlValue'] = nl2br(htmlspecialcharsbx($textValue));;
							}
							$result['fields'][] = $resultItem;
						}
					}
				}
			}
		}

		return $result;
	}

	public function loadSettings($entityTypeID, $entityId)
	{
		$result = array();

		$entityTypeID = (int)$entityTypeID;
		$entityId = (int)$entityId;

		global $DB;
		$tableName = self::CONFIG_TABLE_NAME;
		$entityTypeID = $DB->ForSql($entityTypeID);
		$dbResult = $DB->Query(
			"SELECT SETTINGS FROM {$tableName} WHERE ENTITY_TYPE_ID = '{$entityTypeID}' AND ENTITY_ID = {$entityId}",
			false, 'File: '.__FILE__.'<br/>Line: '.__LINE__
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		$settingsValue = is_array($fields) && isset($fields['SETTINGS']) ? $fields['SETTINGS'] : '';
		$settings = null;
		if (!empty($settingsValue))
			$settings = unserialize($settingsValue);
		if (is_array($settings))
			$result = $settings;

		return $result;
	}

	public function saveSettings($entityTypeId, $entityId, $settings)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		global $DB, $DBType;
		$tableName = self::CONFIG_TABLE_NAME;
		$entityTypeId = $DB->ForSql($entityTypeId);
		$settingsValue = $DB->ForSql(serialize($settings));

		switch (strtoupper(strval($DBType)))
		{
			case 'MYSQL':
				$sql =
					"INSERT INTO {$tableName} (ENTITY_ID, ENTITY_TYPE_ID, SETTINGS)".PHP_EOL.
					"  VALUES({$entityId}, {$entityTypeId}, '{$settingsValue}')".PHP_EOL.
					"  ON DUPLICATE KEY UPDATE SETTINGS = '{$settingsValue}'".PHP_EOL;
				$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
				break;
			case 'MSSQL':
				$updateSql =
					"UPDATE {$tableName} SET SETTINGS = '{$settingsValue}'".PHP_EOL.
					"WHERE ENTITY_ID = {$entityId} AND ENTITY_TYPE_ID = {$entityTypeId}".PHP_EOL;
				$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
				if(is_object($dbResult) && $dbResult->AffectedRowsCount() == 0)
				{
					$insertSql =
						"INSERT INTO {$tableName} (ENTITY_ID, ENTITY_TYPE_ID, SETTINGS)".PHP_EOL.
						"VALUES ({$entityId}, {$entityTypeId}, '{$settingsValue}')".PHP_EOL;
					$DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
				}
				break;
			case 'ORACLE':
				$sql =
					"MERGE INTO {$tableName}".PHP_EOL.
					"  USING (SELECT {$entityId} ENTITY_ID, {$entityTypeId} ENTITY_TYPE_ID FROM dual) source".PHP_EOL.
					"    ON".PHP_EOL.
					"    (".PHP_EOL.
					"      source.ENTITY_ID = {$tableName}.ENTITY_ID".PHP_EOL.
					"      AND source.ENTITY_TYPE_ID = {$tableName}.ENTITY_TYPE_ID".PHP_EOL.
					"    )".PHP_EOL.
					"WHEN MATCHED THEN".PHP_EOL.
					"  UPDATE SET {$tableName}.SETTINGS = '{$settingsValue}'".PHP_EOL.
					"WHEN NOT MATCHED THEN".PHP_EOL.
					"  INSERT (ENTITY_ID, ENTITY_TYPE_ID, SETTINGS)".PHP_EOL.
					"    VALUES ({$entityId}, {$entityTypeId}, '{$settingsValue}')".PHP_EOL;
				$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
				break;
		}
	}

	public function getDefaultRequisiteInfoLinked($entityList)
	{
		$requisiteIdLinked = 0;
		$bankDetailIdLinked = 0;
		$bankDetail = null;

		if (is_array($entityList))
		{
			foreach ($entityList as $entityInfo)
			{
				$entityTypeId = isset($entityInfo['ENTITY_TYPE_ID']) ? (int)$entityInfo['ENTITY_TYPE_ID'] : 0;
				if ($entityTypeId < 0)
					$entityTypeId = 0;
				$entityId = isset($entityInfo['ENTITY_ID']) ? (int)$entityInfo['ENTITY_ID'] : 0;
				if ($entityId < 0)
					$entityId = 0;

				if ($entityTypeId > 0 && $entityId > 0)
				{
					if ($entityTypeId === \CCrmOwnerType::Deal
						|| $entityTypeId === \CCrmOwnerType::Quote
						|| $entityTypeId === \CCrmOwnerType::Invoice)
					{
						if ($row = EntityLink::getList(
							array(
								'filter' => array(
									'=ENTITY_TYPE_ID' => $entityTypeId,
									'=ENTITY_ID' => $entityId
								),
								'select' => array('REQUISITE_ID', 'BANK_DETAIL_ID'),
								'limit' => 1
							)
						)->fetch())
						{
							if (isset($row['REQUISITE_ID']) && $row['REQUISITE_ID'] > 0)
								$requisiteIdLinked = (int)$row['REQUISITE_ID'];
							if ($requisiteIdLinked > 0 && isset($row['BANK_DETAIL_ID']) && $row['BANK_DETAIL_ID'] > 0)
								$bankDetailIdLinked = (int)$row['BANK_DETAIL_ID'];
						}
						unset($row);

						if ($requisiteIdLinked > 0)
						{
							break;
						}
					}
					else if (self::checkEntityType($entityTypeId))
					{
						$settings = $this->loadSettings($entityTypeId, $entityId);
						if (is_array($settings))
						{
							if (isset($settings['REQUISITE_ID_SELECTED']))
							{
								$requisiteIdLinked = (int)$settings['REQUISITE_ID_SELECTED'];
								if ($requisiteIdLinked < 0)
									$requisiteIdLinked = 0;
							}
							if (isset($settings['BANK_DETAIL_ID_SELECTED']))
							{
								$bankDetailIdLinked = (int)$settings['BANK_DETAIL_ID_SELECTED'];
								if ($bankDetailIdLinked < 0)
									$bankDetailIdLinked = 0;
							}
						}
						unset($settings);

						if ($requisiteIdLinked === 0)
						{
							$res = $this->getList(
								array(
									'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
									'filter' => array(
										'=ENTITY_TYPE_ID' => $entityTypeId,
										'=ENTITY_ID' => $entityId
									),
									'select' => array('ID'),
									'limit' => 1
								)
							);
							if ($row = $res->fetch())
								$requisiteIdLinked = (int)$row['ID'];
							unset($res, $row);
						}
						if ($requisiteIdLinked > 0)
						{
							if ($bankDetailIdLinked === 0)
							{
								if ($bankDetail === null)
									$bankDetail = EntityBankDetail::getSingleInstance();
								$res = $bankDetail->getList(
									array(
										'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
										'filter' => array(
											'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
											'=ENTITY_ID' => $requisiteIdLinked
										),
										'select' => array('ID'),
										'limit' => 1
									)
								);
								if ($row = $res->fetch())
									$bankDetailIdLinked = (int)$row['ID'];
								unset($res, $row);
							}

							break;
						}
					}
				}
			}
		}

		return array('REQUISITE_ID' => $requisiteIdLinked, 'BANK_DETAIL_ID' => $bankDetailIdLinked);
	}

	public function getDefaultMyCompanyRequisiteInfoLinked($entityList)
	{
		$mcRequisiteIdLinked = 0;
		$mcBankDetailIdLinked = 0;
		$bankDetail = null;

		if (is_array($entityList))
		{
			foreach ($entityList as $entityInfo)
			{
				$entityTypeId = isset($entityInfo['ENTITY_TYPE_ID']) ? (int)$entityInfo['ENTITY_TYPE_ID'] : 0;
				if ($entityTypeId < 0)
					$entityTypeId = 0;
				$entityId = isset($entityInfo['ENTITY_ID']) ? (int)$entityInfo['ENTITY_ID'] : 0;
				if ($entityId < 0)
					$entityId = 0;

				if ($entityTypeId > 0 && $entityId > 0)
				{
					if ($entityTypeId === \CCrmOwnerType::Deal
						|| $entityTypeId === \CCrmOwnerType::Quote
						|| $entityTypeId === \CCrmOwnerType::Invoice)
					{
						if ($row = EntityLink::getList(
							array(
								'filter' => array(
									'=ENTITY_TYPE_ID' => $entityTypeId,
									'=ENTITY_ID' => $entityId
								),
								'select' => array('MC_REQUISITE_ID', 'MC_BANK_DETAIL_ID'),
								'limit' => 1
							)
						)->fetch())
						{
							if (isset($row['MC_REQUISITE_ID']) && $row['MC_REQUISITE_ID'] > 0)
								$mcRequisiteIdLinked = (int)$row['MC_REQUISITE_ID'];
							if ($mcRequisiteIdLinked > 0 && isset($row['MC_BANK_DETAIL_ID']) && $row['MC_BANK_DETAIL_ID'] > 0)
								$mcBankDetailIdLinked = (int)$row['MC_BANK_DETAIL_ID'];
						}
						unset($row);

						if ($mcRequisiteIdLinked > 0)
						{
							break;
						}
					}
					else if (self::checkEntityType($entityTypeId))
					{
						$settings = $this->loadSettings($entityTypeId, $entityId);
						if (is_array($settings))
						{
							if (isset($settings['REQUISITE_ID_SELECTED']))
							{
								$mcRequisiteIdLinked = (int)$settings['REQUISITE_ID_SELECTED'];
								if ($mcRequisiteIdLinked < 0)
									$mcRequisiteIdLinked = 0;
							}
							if (isset($settings['BANK_DETAIL_ID_SELECTED']))
							{
								$mcBankDetailIdLinked = (int)$settings['BANK_DETAIL_ID_SELECTED'];
								if ($mcBankDetailIdLinked < 0)
									$mcBankDetailIdLinked = 0;
							}
						}
						unset($settings);

						if ($mcRequisiteIdLinked === 0)
						{
							$res = $this->getList(
								array(
									'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
									'filter' => array(
										'=ENTITY_TYPE_ID' => $entityTypeId,
										'=ENTITY_ID' => $entityId
									),
									'select' => array('ID'),
									'limit' => 1
								)
							);
							if ($row = $res->fetch())
								$mcRequisiteIdLinked = (int)$row['ID'];
							unset($res, $row);
						}
						if ($mcRequisiteIdLinked > 0)
						{
							if ($mcBankDetailIdLinked === 0)
							{
								if ($bankDetail === null)
									$bankDetail = EntityBankDetail::getSingleInstance();
								$res = $bankDetail->getList(
									array(
										'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
										'filter' => array(
											'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
											'=ENTITY_ID' => $mcRequisiteIdLinked
										),
										'select' => array('ID'),
										'limit' => 1
									)
								);
								if ($row = $res->fetch())
									$mcBankDetailIdLinked = (int)$row['ID'];
								unset($res, $row);
							}

							break;
						}
					}
				}
			}
		}

		return array('MC_REQUISITE_ID' => $mcRequisiteIdLinked, 'MC_BANK_DETAIL_ID' => $mcBankDetailIdLinked);
	}

	public function getAddressFieldMap($addressTypeId)
	{
		$fieldName = $this->resolveFieldNameByAddressType($addressTypeId);

		if (!empty($fieldName))
		{
			$addrFieldMap = array(
				'ADDRESS_1' => $fieldName.'_ADDRESS_1',
				'ADDRESS_2' => $fieldName.'_ADDRESS_2',
				'CITY' => $fieldName.'_CITY',
				'POSTAL_CODE' => $fieldName.'_POSTAL_CODE',
				'REGION' => $fieldName.'_REGION',
				'PROVINCE' => $fieldName.'_PROVINCE',
				'COUNTRY' => $fieldName.'_COUNTRY',
				'COUNTRY_CODE' => $fieldName.'_COUNTRY_CODE'
			);
		}
		else
		{
			$addrFieldMap = array(
				'ADDRESS_1' => 'RQ_ADDR_ADDRESS_1',
				'ADDRESS_2' => 'RQ_ADDR_ADDRESS_2',
				'CITY' => 'RQ_ADDR_CITY',
				'POSTAL_CODE' => 'RQ_ADDR_POSTAL_CODE',
				'REGION' => 'RQ_ADDR_REGION',
				'PROVINCE' => 'RQ_ADDR_PROVINCE',
				'COUNTRY' => 'RQ_ADDR_COUNTRY',
				'COUNTRY_CODE' => 'RQ_ADDR_COUNTRY_CODE'
			);
		}

		return $addrFieldMap;
	}

	public function getAddressFieldPostfixes()
	{
		return array(
			'_ADDRESS_1',
			'_ADDRESS_2',
			'_CITY',
			'_POSTAL_CODE',
			'_REGION',
			'_PROVINCE',
			'_COUNTRY',
			'_COUNTRY_CODE'
		);
	}

	public function prepareFormattedAddress(array $fields, $typeId = RequisiteAddress::Undefined)
	{
		$result = '';
		$typeId = (int)$typeId;
		$fieldName = $this->resolveFieldNameByAddressType($typeId);

		if (!empty($fieldName))
		{
			return Format\EntityAddressFormatter::format(
				array(
					'ADDRESS_1' => isset($fields[$fieldName.'_ADDRESS']) ? $fields[$fieldName.'_ADDRESS'] : '',
					'ADDRESS_2' => isset($fields[$fieldName.'_ADDRESS_2']) ? $fields[$fieldName.'_ADDRESS_2'] : '',
					'CITY' => isset($fields[$fieldName.'_CITY']) ? $fields[$fieldName.'_CITY'] : '',
					'POSTAL_CODE' => isset($fields[$fieldName.'_POSTAL_CODE']) ? $fields[$fieldName.'_POSTAL_CODE'] : '',
					'REGION' => isset($fields[$fieldName.'_REGION']) ? $fields[$fieldName.'_REGION'] : '',
					'PROVINCE' => isset($fields[$fieldName.'_PROVINCE']) ? $fields[$fieldName.'_PROVINCE'] : '',
					'COUNTRY' => isset($fields[$fieldName.'_COUNTRY']) ? $fields[$fieldName.'_COUNTRY'] : '',
					'COUNTRY_CODE' => isset($fields[$fieldName.'_COUNTRY_CODE']) ? $fields[$fieldName.'_COUNTRY_CODE'] : ''
				)
			);
		}

		return $result;
	}

	public static function intertalizeFormData(array $formData, $entityTypeID, array &$requisites, array &$bankDetails)
	{
		$signer = new Main\Security\Sign\Signer();
		foreach($formData as $requisiteID => $requisiteData)
		{
			if($requisiteID > 0 && isset($requisiteData['DELETED']) && strtoupper($requisiteData['DELETED']) === 'Y')
			{
				$requisites[$requisiteID] = array('isDeleted' => true);
				continue;
			}

			$requisiteSign = isset($requisiteData['SIGN']) ? $requisiteData['SIGN'] : '';
			$requisiteJsonData = isset($requisiteData['DATA']) ? $requisiteData['DATA'] : '';
			if(!$signer->validate($requisiteJsonData, $requisiteSign, "crm.requisite.edit-{$entityTypeID}"))
			{
				$requisiteJsonData = '';
			}

			if($requisiteJsonData === '')
			{
				continue;
			}

			$decodedData = Main\Web\Json::decode($requisiteJsonData);
			if(isset($decodedData['fields']) && is_array($decodedData['fields']))
			{
				$requisites[$requisiteID] = array('fields' => $decodedData['fields']);
			}

			$bankDetailList = isset($decodedData['bankDetailFieldsList'])
				? $decodedData['bankDetailFieldsList'] : null;
			if(is_array($bankDetailList))
			{
				$deletedBankDetailList = isset($decodedData['deletedBankDetailList'])
					&& is_array($decodedData['deletedBankDetailList'])
						? $decodedData['deletedBankDetailList'] : array();

				foreach($deletedBankDetailList as $bankDetailID)
				{
					if(isset($bankDetailList[$bankDetailID]))
					{
						$bankDetailList[$bankDetailID] = array('isDeleted' => true);
					}
				}
				$bankDetails[$requisiteID] = $bankDetailList;
			}
		}
	}

	/**
	 * Parse form data from specified source
	 * @param array $formData Data source.
	 * @return array
	 */
	public static function parseFormData(array $formData)
	{
		$fields = array();
		if(isset($formData['NAME']))
		{
			$fields['NAME'] = trim($formData['NAME']);
		}

		if(isset($formData['PRESET_ID']))
		{
			$fields['PRESET_ID'] = (int)$formData['PRESET_ID'];
		}

		if(isset($formData['CODE']))
		{
			$fields['CODE'] = trim($formData['CODE']);
		}

		if(isset($formData['XML_ID']))
		{
			$fields['XML_ID'] = trim($formData['XML_ID']);
		}

		if(isset($formData['ACTIVE']))
		{
			$fields['ACTIVE'] = $formData['ACTIVE'] === 'Y';
		}

		if(isset($formData['SORT']))
		{
			$fields['SORT'] = (int)$formData['SORT'];
		}

		if(isset($formData[self::ADDRESS]) && is_array($formData[self::ADDRESS]))
		{
			$fields[self::ADDRESS] = $formData[self::ADDRESS];
		}

		$entity = self::getSingleInstance();
		$fieldNames = $entity->getRqFields();
		foreach ($fieldNames as $fieldName)
		{
			//If we have more than one address type
			//$addrType = $entity->resolveAddressTypeByFieldName($fieldName);
			//if($addrType !== RequisiteAddress::Undefined) { ... }
			if($fieldName === 'RQ_ADDR')
			{
				$addrMap = $entity->getAddressFieldMap(RequisiteAddress::Primary);
				foreach($addrMap as $k => $v)
				{
					if(isset($formData[$v]))
					{
						$fields[$v] = trim($formData[$v]);
					}
				}
			}
			elseif(isset($formData[$fieldName]))
			{
				$fields[$fieldName] = trim($formData[$fieldName]);
			}
		}
		unset($fieldNames, $fieldName);

		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->EditFormAddFields(
			$entity->getUfId(),
			$fields,
			array('FORM' => $formData)
		);
		return $fields;
	}

	/**
	 * Load entity addresses
	 * @param int $id Entity ID.
	 * @return array
	 */
	public static function getAddresses($id)
	{
		if(!is_int($id))
		{
			$id = (int)$id;
		}

		if($id <= 0)
		{
			return array();
		}

		$dbResult = AddressTable::getList(
			array('filter' => array('ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite, 'ENTITY_ID' => $id))
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$typeId = (int)$ary['TYPE_ID'];
			$results[$typeId] = array(
				'ADDRESS_1' => isset($ary['ADDRESS_1']) ? $ary['ADDRESS_1'] : '',
				'ADDRESS_2' => isset($ary['ADDRESS_2']) ? $ary['ADDRESS_2'] : '',
				'CITY' => isset($ary['CITY']) ? $ary['CITY'] : '',
				'POSTAL_CODE' => isset($ary['POSTAL_CODE']) ? $ary['POSTAL_CODE'] : '',
				'REGION' => isset($ary['REGION']) ? $ary['REGION'] : '',
				'PROVINCE' => isset($ary['PROVINCE']) ? $ary['PROVINCE'] : '',
				'COUNTRY' => isset($ary['COUNTRY']) ? $ary['COUNTRY'] : '',
				'COUNTRY_CODE' => isset($ary['COUNTRY_CODE']) ? $ary['COUNTRY_CODE'] : ''
			);
		}
		return $results;
	}

	public static function getFixedPresetList()
	{
		if (self::$fixedPresetList === null)
		{
			self::$fixedPresetList = array(
				// ru
				0 => array(
					'ID' => 1,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_COMPANY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_RU_COMPANY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_OGRN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_KPP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_COMPANY_REG_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_OKTMO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 9,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							),
							9 => array(
								'ID' => 10,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							),
							10 => array(
								'ID' => 11,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							)
						),
						'LAST_FIELD_ID' => 11
					)
				),
				1 => array(
					'ID' => 2,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_RU_INDIVIDUAL#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							),
							3 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							),
							4 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_SECOND_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							),
							5 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 560
							),
							6 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_OGRNIP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_OKVED',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							),
							9 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							)
						),
						'LAST_FIELD_ID' => 8
					)
				),
				2 => array(
					'ID' => 3,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_RU_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_SECOND_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_IDENT_DOC_ISSUED_BY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 9,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DEP_CODE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							),
							9 => array(
								'ID' => 10,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							)
						),
						'LAST_FIELD_ID' => 10
					)
				),
				// by
				3 => array(
					'ID' => 4,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_COMPANY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_COMPANY_REG_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_BASE_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 9,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							)
						),
						'LAST_FIELD_ID' => 9
					)
				),
				4 => array(
					'ID' => 5,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_IDENT_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_IDENT_DOC_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_PERS_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_IDENT_DOC_ISSUED_BY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							)
						),
						'LAST_FIELD_ID' => 8
					)
				),
				5 => array(
					'ID' => 6,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_INDIVIDUAL#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ST_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_ST_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							)
						),
						'LAST_FIELD_ID' => 6
					)
				),
				// kz
				6 => array(
					'ID' => 7,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_INDIVIDUAL#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_KBE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_VAT_CERT_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_VAT_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 9,
								'FIELD_NAME' => 'RQ_RESIDENCE_COUNTRY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							),
							9 => array(
								'ID' => 10,
								'FIELD_NAME' => 'RQ_CEO_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							),
							10 => array(
								'ID' => 11,
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							),
							11 => array(
								'ID' => 12,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							)
						),
						'LAST_FIELD_ID' => 12
					)
				),
				7 => array(
					'ID' => 8,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_KBE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IIN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_BIN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_VAT_CERT_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							),
							8 => array(
								'ID' => 9,
								'FIELD_NAME' => 'RQ_VAT_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							),
							9 => array(
								'ID' => 10,
								'FIELD_NAME' => 'RQ_RESIDENCE_COUNTRY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							),
							10 => array(
								'ID' => 11,
								'FIELD_NAME' => 'RQ_CEO_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							),
							11 => array(
								'ID' => 12,
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							),
							12 => array(
								'ID' => 13,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 630
							)
						),
						'LAST_FIELD_ID' => 13
					)
				),
				8 => array(
					'ID' => 9,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							)
						),
						'LAST_FIELD_ID' => 3
					)
				),
				// ua
				9 => array(
					'ID' => 10,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '14',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_UA_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_EDRPOU',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_VAT_PAYER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							),
							6 => array(
								'ID' => 7,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							),
							7 => array(
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							)
						),
						'LAST_FIELD_ID' => 8
					)
				),
				10 => array(
					'ID' => 11,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '14',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_UA_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_DRFO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_VAT_PAYER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							),
							5 => array(
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							)
						),
						'LAST_FIELD_ID' => 6
					)
				),
				// de
				11 => array(
					'ID' => 12,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '46',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_DE_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_USRLE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							),
							3 => array(
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							),
							4 => array(
								'ID' => 5,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							)
						),
						'LAST_FIELD_ID' => 5
					)
				),
				12 => array(
					'ID' => 13,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '46',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_DE_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							)
						),
						'LAST_FIELD_ID' => 3
					)
				),
				// us
				13 => array(
					'ID' => 14,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '122',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_US_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							)
						),
						'LAST_FIELD_ID' => 3
					)
				),
				14 => array(
					'ID' => 15,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '122',
					'NAME' => GetMessage('CRM_REQUISITE_FIXED_PRESET_PERSON'),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_US_PERSON#',
					'SORT' => 500,
					'SETTINGS' => array(
						'FIELDS' => array(
							0 => array(
								'ID' => 1,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							),
							1 => array(
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							),
							2 => array(
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							)
						),
						'LAST_FIELD_ID' => 3
					)
				)
			);
		}

		return self::$fixedPresetList;
	}

	public function getFieldsOfFixedPresets()
	{
		$result = array();

		$preset = EntityPreset::getSingleInstance();

		$iResult = array();
		foreach (self::getFixedPresetList() as $row)
		{
			if (is_array($row['SETTINGS']))
			{
				$fields = $preset->settingsGetFields($row['SETTINGS']);
				if (is_array($fields))
				{
					foreach ($fields as $fieldInfo)
					{
						if (isset($fieldInfo['FIELD_NAME']) && !isset($iResult[$fieldInfo['FIELD_NAME']]))
							$iResult[$fieldInfo['FIELD_NAME']] = true;
					}
				}
			}
		}
		foreach (array_merge($this->getRqFields(), $this->getUserFields()) as $fieldName)
		{
			if (isset($iResult[$fieldName]))
				$result[] = $fieldName;
		}

		return $result;
	}

	public function getFieldsOfFixedPresetsByCountry()
	{
		$result = array();

		$preset = EntityPreset::getSingleInstance();

		$iResult = array();
		foreach (self::getFixedPresetList() as $row)
		{
			if (is_array($row['SETTINGS']) && isset($row['COUNTRY_ID']) && $row['COUNTRY_ID'] > 0)
			{
				$countryId = (int)$row['COUNTRY_ID'];
				$fields = $preset->settingsGetFields($row['SETTINGS']);
				if (is_array($fields))
				{
					foreach ($fields as $fieldInfo)
					{
						if (isset($fieldInfo['FIELD_NAME']) && !isset($iResult[$fieldInfo['FIELD_NAME']]))
						{
							if (!isset($iResult[$countryId]))
								$iResult[$countryId] = array();
							$iResult[$countryId][$fieldInfo['FIELD_NAME']] = true;
						}
					}
				}
			}
		}
		foreach (array_keys($iResult) as $countryId)
		{
			foreach (array_merge($this->getRqFields(), $this->getUserFields()) as $fieldName)
			{
				if (isset($iResult[$countryId][$fieldName]))
				{
					if (!isset($result[$countryId]))
						$result[$countryId] = array();
					$result[$countryId][] = $fieldName;
				}
			}
		}

		return $result;
	}

	public static function installDefaultPresets()
	{
		if (!Main\Loader::includeModule('crm'))
			return;

		// Detect current country id
		$bitrix24Path = Main\Application::getDocumentRoot().'/bitrix/modules/bitrix24/';
		$bitrix24 = Main\IO\Directory::isDirectoryExists($bitrix24Path);
		unset($bitrix24Path);
		$languageId = '';
		if ($bitrix24)
		{
			if (defined('B24_LANGUAGE_ID'))
				$languageId = B24_LANGUAGE_ID;
			else
				$languageId = substr((string)Main\Config\Option::get('main', '~controller_group_name'), 0, 2);
		}
		if ($languageId == '')
		{
			$siteIterator = \Bitrix\Main\SiteTable::getList(array(
				'select' => array('LID', 'LANGUAGE_ID'),
				'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
			));
			if ($site = $siteIterator->fetch())
				$languageId = (string)$site['LANGUAGE_ID'];
			unset($site, $siteIterator);
		}
		if ($languageId == '')
			$languageId = 'en';
		switch ($languageId)
		{
			case 'de':
				$countryCode = 'DE';
				break;
			case 'ru':
				$countryCode = 'RU';
				break;
			case 'ua':
			case 'ur':
				$countryCode = 'UA';
				break;
			case 'kz':
				$countryCode = 'KZ';
				break;
			case 'by':
				$countryCode = 'BY';
				break;
			default:
				$countryCode = 'US';
				break;
		}
		$countryId = (int)GetCountryIdByCode($countryCode);
		Main\Config\Option::set('crm', 'crm_requisite_preset_country_id', $countryId);
		unset($bitrix24);

		if($countryId > 0)
		{
			$preset = EntityPreset::getSingleInstance();
			$row = $preset->getList(
				array(
					'filter' => array('=ENTITY_TYPE_ID' => EntityPreset::Requisite),
					'select' => array('ID'),
					'limit' => 1
				)
			)->fetch();
			if (!is_array($row))
			{
				$fixedPresetList = self::getFixedPresetList();
				$sort = 500;
				$datetimeEntity = new Main\DB\SqlExpression(Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction());
				foreach ($fixedPresetList as $presetData)
				{
					if ($countryId === intval($presetData['COUNTRY_ID']))
					{
						$sort += 10;
						PresetTable::add(
							array(
								'ENTITY_TYPE_ID' => EntityPreset::Requisite,
								'COUNTRY_ID' => $countryId,
								'DATE_CREATE' => $datetimeEntity,
								'CREATED_BY_ID' => 0,
								'NAME' => $presetData['NAME'],
								'ACTIVE' => $presetData['ACTIVE'],
								'SORT' => $sort,
								'XML_ID' => $presetData['XML_ID'],
								'SETTINGS' => $presetData['SETTINGS']
							)
						);
					}
				}
			}
		}
	}

	public function prepareEntityListFilterFields(&$filterFields)
	{
		$allowedCountries = self::getAllowedRqFieldCountries();
		$preset = EntityPreset::getSingleInstance();
		$fieldList = $preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'active',
			array('FILTER_BY_COUNTRY_IDS' => $allowedCountries)
		);
		if (empty($fieldList))
			return;
		$activeCountries = array();
		$activeFieldsByCountry = array();
		foreach ($fieldList as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
			{
				$activeFieldsByCountry[$countryId][$fieldName] = true;
				$activeCountries[$countryId] = true;
			}
		}
		if (empty($activeCountries))
			return;
		$currentCountryId = EntityPreset::getCurrentCountryId();
		$hideCountry = (count($activeCountries) === 1 && isset($activeCountries[$currentCountryId]));
		$countrySort = array();
		if (isset($activeCountries[$currentCountryId]))
		{
			$countrySort[] = $currentCountryId;
		}
		foreach (array_keys($activeCountries) as $countryId)
		{
			if ($countryId !== $currentCountryId)
				$countrySort[] = $countryId;
		}
		$fieldTitleMap = $this->getRqFieldTitleMap();
		$fieldsFormTypes = $this->getFormFieldsTypes();
		$filtrableFields = array();
		foreach ($this->getRqFiltrableFields() as $fieldName)
			$filtrableFields[$fieldName] = true;
		$countryList = EntityPreset::getCountryList();
		$addressTypeList = array();
		foreach(RequisiteAddress::getClientTypeInfos() as $typeInfo)
			$addressTypeList[$typeInfo['id']] = $typeInfo['name'];
		foreach ($countrySort as $countryId)
		{
			if (isset($countryList[$countryId]))
			{
				foreach ($this->getRqFields() as $fieldName)
				{
					if (isset($filtrableFields[$fieldName])
						&& isset($activeFieldsByCountry[$countryId][$fieldName])
						&& isset($fieldTitleMap[$fieldName][$countryId])
						&& !empty($fieldTitleMap[$fieldName][$countryId]))
					{
						if ($fieldName === EntityRequisite::ADDRESS)
						{
							if (!empty($addressTypeList))
							{
								/*foreach ($addressTypeList as $addressTypeId => $addressTypeName)
								{*/
									$addressTypeId = RequisiteAddress::Undefined;
									$addressTypeName = $fieldTitleMap[$fieldName][$countryId];
									$addressLabels = RequisiteAddress::getShortLabels(RequisiteAddress::Primary);
									foreach (array_keys(EntityRequisite::getAddressFieldMap(RequisiteAddress::Primary)) as $addrFieldKey)
									{
										if ($addrFieldKey === 'ADDRESS_2' || $addrFieldKey === 'COUNTRY_CODE')
											continue;

										$filterFields[] = array(
											'id' => "$fieldName|$countryId|$addressTypeId|$addrFieldKey",
											'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
												($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.
												$addressTypeName.' - '.ToLower($addressLabels[$addrFieldKey]),
											'type' => 'text'
										);
									}
								/*}*/
							}
						}
						else
						{
							$formType = isset($fieldsFormTypes[$fieldName]) ? $fieldsFormTypes[$fieldName] : 'text';
							$filterFields[] = array(
								'id' => "$fieldName|$countryId",
								'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
									($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.
									$fieldTitleMap[$fieldName][$countryId],
								'type' => $formType
							);
						}
					}
				}
			}
		}
	}

	public function prepareEntityListFilter(array &$filter)
	{
		$rqFilter = array();

		$rqFieldCountryMap = $this->getRqFieldsCountryMap();
		if (!is_array($rqFieldCountryMap) || empty($rqFieldCountryMap))
			return;

		$rqFieldFormTypes = $this->getFormFieldsTypes();

		foreach ($filter as $filterFieldId => $filterFieldValue)
		{
			$fieldName = '';
			$countryId = 0;
			$addressTypeId = 0;
			$addressFieldName = '';
			$fieldParsed = false;

			$matches = array();
			if (preg_match('/^(RQ_\w+)\|(\d+)\|(\d+)\|(\w+)$/'.BX_UTF_PCRE_MODIFIER, $filterFieldId, $matches))
			{
				$fieldName = $matches[1];
				$countryId = (int)$matches[2];
				$addressTypeId = (int)$matches[3];
				$addressFieldName = $matches[4];
				
				$fieldParsed = (is_array($rqFieldCountryMap[$fieldName])
					&& in_array($countryId, $rqFieldCountryMap[$fieldName], true)
					&& (RequisiteAddress::isDefined($addressTypeId) || $addressTypeId === RequisiteAddress::Undefined)
					&& in_array($addressFieldName, EntityRequisite::getAddressFiltrableFields(), true));
			}
			else if (preg_match('/^(RQ_\w+)\|(\d+)$/'.BX_UTF_PCRE_MODIFIER, $filterFieldId, $matches))
			{
				$fieldName = $matches[1];
				$countryId = (int)$matches[2];

				$fieldParsed = (is_array($rqFieldCountryMap[$fieldName])
					&& in_array($countryId, $rqFieldCountryMap[$fieldName], true));
			}

			if ($fieldParsed)
			{
				if ($fieldName === EntityRequisite::ADDRESS)
				{
					$rqFilter[] = array(
						'COUNTRY_ID' => $countryId,
						'FIELD_NAME' => $fieldName,
						'OPERATION' => '%',
						'VALUE' => $filterFieldValue,
						'PARAMS' => array(
							'ADDRESS_TYPE' => $addressTypeId,
							'ADDRESS_FIELD' => $addressFieldName
						)
					);
				}
				else
				{
					$fieldFormType = isset($rqFieldFormTypes[$fieldName]) ? $rqFieldFormTypes[$fieldName] : 'text';
					if ($fieldFormType === 'checkbox')
					{
						$operation = '=';
					}
					else
					{
						$operation = '%';
					}
					$rqFilter[] = array(
						'COUNTRY_ID' => $countryId,
						'FIELD_NAME' => $fieldName,
						'ADDRESS_TYPE' => RequisiteAddress::Undefined,
						'ADDRESS_FIELD' => '',
						'OPERATION' => $operation,
						'VALUE' => $filterFieldValue
					);
				}

				unset($filter[$filterFieldId]);
			}
		}

		if (!empty($rqFilter))
		{
			if(!is_array($filter['RQ']))
				$filter['RQ'] = array();
			$filter['RQ'] = array_merge($filter['RQ'], $rqFilter);
		}
	}
	
	public function prepareEntityListExternalFilter(&$filter, $params = array())
	{
		$entityTypeId = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		if ($entityTypeId === \CCrmOwnerType::Undefined)
		{
			return;
		}

		$masterAlias = isset($params['MASTER_ALIAS']) ? $params['MASTER_ALIAS'] : '';
		if($masterAlias === '')
		{
			$masterAlias = 'L';
		}

		$masterIdentity = isset($params['MASTER_IDENTITY']) ? $params['MASTER_IDENTITY'] : '';
		if($masterIdentity === '')
		{
			$masterIdentity = 'ID';
		}

		$allowedCountries = array();
		foreach (EntityRequisite::getAllowedRqFieldCountries() as $countryId)
			$allowedCountries[$countryId] = true;

		$allowedAddressTypes = null;
		$filtrableAddressFields = null;
		$fieldsInfo = array();
		$joins = array();
		$c = 0;
		foreach($filter['RQ'] as $filterInfo)
		{
			if (is_array($filterInfo))
			{
				if (isset($filterInfo['COUNTRY_ID']) && $filterInfo['COUNTRY_ID'] > 0)
				{
					$countryId = (int)$filterInfo['COUNTRY_ID'];
					if (isset($allowedCountries[$countryId]))
					{
						if (!isset($fieldsInfo[$countryId]))
						{
							$fieldsInfo[$countryId] = array(
								'ENTITY_TYPE_ID' => array('FIELD' => 'RQ.ENTITY_TYPE_ID', 'TYPE' => 'int'),
								'PRESET.COUNTRY_ID' => array('FIELD' => 'PR.COUNTRY_ID', 'TYPE' => 'int')
							);
							foreach ($this->getFormFieldsInfo($countryId) as $fieldName => $fieldInfo)
							{
								if ($fieldInfo['isRQ'] && !$fieldInfo['isUF']
									&& isset($fieldInfo['title']) && !empty($fieldInfo['title']))
								{
									$fieldType = $fieldInfo['type'];
									switch ($fieldInfo['type'])
									{
										case 'boolean':
											$fieldType = 'string';
											break;
										case 'integer':
											$fieldType = 'int';
											break;
									}
									$fieldsInfo[$countryId][$fieldName] = array(
										'FIELD' => "RQ.$fieldName",
										'TYPE' => $fieldType
									);
								}
							}
							unset($fieldName, $fieldInfo);

							if ($filtrableAddressFields === null)
							{
								$filtrableAddressFields =
									EntityRequisite::getAddressFiltrableFields();
							}
							foreach ($filtrableAddressFields as $addressField)
							{
								$fieldsInfo[$countryId]["ADDRESS.$addressField"] =
									array('FIELD' => "AR.$addressField", 'TYPE' => 'string');
							}
						}

						if (isset($fieldsInfo[$countryId]))
						{
							if (isset($filterInfo['FIELD_NAME'])
								&& is_string($filterInfo['FIELD_NAME'])
								&& !empty($filterInfo['FIELD_NAME'])
								&& is_array($fieldsInfo[$countryId][$filterInfo['FIELD_NAME']]))
							{
								$fieldName = $filterInfo['FIELD_NAME'];
								if (isset($filterInfo['OPERATION'])
									&& is_string($filterInfo['OPERATION'])
									&& preg_match('/^\!*\+*(>=|>|<=|<|@|=%|%=|%|\?|=)*$/', $filterInfo['OPERATION']))
								{
									$operation = $filterInfo['OPERATION'];

									if ($fieldName === EntityRequisite::ADDRESS)
									{
										if (is_array($filterInfo['PARAMS'])
											&& isset($filterInfo['PARAMS']['ADDRESS_TYPE'])
											&& isset($filterInfo['PARAMS']['ADDRESS_FIELD'])
											&& is_string($filterInfo['PARAMS']['ADDRESS_FIELD']))
										{
											if ($allowedAddressTypes === null)
												$allowedAddressTypes = array_keys(RequisiteAddress::getTypeInfos());

											$addressType = (int)$filterInfo['PARAMS']['ADDRESS_TYPE'];
											if ($addressType === RequisiteAddress::Undefined)
											{
												$addressType = $allowedAddressTypes;
											}
											if (is_array($addressType)
												|| in_array($addressType, $allowedAddressTypes, true))
											{
												$addressTypeCompare = is_array($addressType)
													? 'IN ('.implode(',', $addressType).')'
													: "= $addressType";
												$addressField = $filterInfo['PARAMS']['ADDRESS_FIELD'];
												if (in_array($addressField, $filtrableAddressFields, true))
												{
													$filterPart = array(
														'ENTITY_TYPE_ID' => $entityTypeId,
														$operation.'ADDRESS.'.$addressField => $filterInfo['VALUE'],
														'PRESET.COUNTRY_ID' => $countryId
													);
													$c++;
													$alias = "RQ{$c}";
													$where = \CSqlUtil::PrepareWhere(
														$fieldsInfo[$countryId], $filterPart, $joins)
													;
													$joins[] = array(
														'TYPE' => 'INNER',
														'SQL' => "INNER JOIN (".
															"SELECT DISTINCT RQ.ENTITY_ID FROM b_crm_requisite RQ".
															" INNER JOIN b_crm_preset PR ON RQ.PRESET_ID = PR.ID".
															" INNER JOIN b_crm_addr AR".
															" ON AR.TYPE_ID {$addressTypeCompare}".
															" AND AR.ENTITY_TYPE_ID = ".\CCrmOwnerType::Requisite.
															" AND AR.ENTITY_ID = RQ.ID".
															" WHERE {$where}".
															") {$alias}".
															" ON {$masterAlias}.{$masterIdentity} = {$alias}.ENTITY_ID"
													);
												}
											}
										}
									}
									else
									{
										$filterPart = array(
											'ENTITY_TYPE_ID' => $entityTypeId,
											$operation.$fieldName => $filterInfo['VALUE'],
											'PRESET.COUNTRY_ID' => $countryId
										);
										$c++;
										$alias = "RQ{$c}";
										$where = \CSqlUtil::PrepareWhere($fieldsInfo[$countryId], $filterPart, $joins);
										$joins[] = array(
											'TYPE' => 'INNER',
											'SQL' => "INNER JOIN (".
												"SELECT DISTINCT RQ.ENTITY_ID FROM b_crm_requisite RQ".
												" INNER JOIN b_crm_preset PR ON RQ.PRESET_ID = PR.ID".
												" WHERE {$where}".
												") {$alias} ON {$masterAlias}.{$masterIdentity} = {$alias}.ENTITY_ID"
										);
									}
								}
							}
						}
					}
				}
			}
		}

		if(!empty($joins))
		{
			if(!isset($filter['__JOINS']))
			{
				$filter['__JOINS'] = $joins;
			}
			else
			{
				$filter['__JOINS'] = array_merge($filter['__JOINS'], $joins);
			}
		}
	}

	public function prepareEntityListHeaderFields(&$headers, $options = array())
	{
		$prefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX'])) ?
			$options['PREFIX'] : 'RQ_';

		$allowedCountries = self::getAllowedRqFieldCountries();
		$preset = EntityPreset::getSingleInstance();
		$fieldList = $preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'active',
			array('FILTER_BY_COUNTRY_IDS' => $allowedCountries)
		);
		if (empty($fieldList))
			return;
		$activeCountries = array();
		$activeFieldsByCountry = array();
		foreach ($fieldList as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
			{
				$activeFieldsByCountry[$countryId][$fieldName] = true;
				$activeCountries[$countryId] = true;
			}
		}
		if (empty($activeCountries))
			return;
		$currentCountryId = EntityPreset::getCurrentCountryId();
		$hideCountry = (count($activeCountries) === 1 && isset($activeCountries[$currentCountryId]));
		$countrySort = array();
		if (isset($activeCountries[$currentCountryId]))
		{
			$countrySort[] = $currentCountryId;
		}
		foreach (array_keys($activeCountries) as $countryId)
		{
			if ($countryId !== $currentCountryId)
				$countrySort[] = $countryId;
		}
		$fieldTitleMap = $this->getRqFieldTitleMap();
		$userFieldInfo = $this->getFormUserFieldsInfo();
		$countryList = EntityPreset::getCountryList();
		foreach ($countrySort as $countryId)
		{
			if (isset($countryList[$countryId]))
			{
				foreach (array_merge($this->getRqFields(), $this->getUserFields()) as $fieldName)
				{
					$isUserField = isset($userFieldInfo[$fieldName]);
					if (isset($activeFieldsByCountry[$countryId][$fieldName])
						&& ((isset($fieldTitleMap[$fieldName][$countryId])
								&& !empty($fieldTitleMap[$fieldName][$countryId]))
							|| $isUserField))
					{
						if ($isUserField)
						{
							$fieldTitle = isset($userFieldInfo[$fieldName]['title']) ?
								$userFieldInfo[$fieldName]['title'] : '';
						}
						else
						{
							$fieldTitle = $fieldTitleMap[$fieldName][$countryId];
						}
						$headers[] = array(
							'id' => $prefix."$fieldName|$countryId",
							'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
								($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.$fieldTitle,
							'sort' => false,
							'default' => false,
							'editable' => false,
							'type' => 'custom'
						);
					}
				}
			}
		}
	}

	public function normalizeEntityListFields(&$entityFields, $headers, $options = array())
	{
		$result = false;

		$prefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX'])) ?
			$options['PREFIX'] : 'RQ_';

		if (!empty($prefix) && is_array($entityFields) && !empty($entityFields) && is_array($headers))
		{
			$headerRqFields = array();
			$prefixLength = strlen($prefix);
			foreach ($headers as $headerInfo)
			{
				if (isset($headerInfo['id']) && strlen($headerInfo['id']) > $prefixLength
					&& substr($headerInfo['id'], 0, $prefixLength) === $prefix)
				{
					$headerRqFields[$headerInfo['id']] = true;
				}
			}
			foreach ($entityFields as $k => $fieldName)
			{
				if (strlen($fieldName) > $prefixLength && substr($fieldName, 0, $prefixLength) === $prefix
					&& !isset($headerRqFields[$fieldName]))
				{
					$result = true;
					unset($entityFields[$k]);
				}
			}
		}

		return $result;
	}

	public function separateEntityListRqFields(&$entityFields, $options = array())
	{
		$result = array();

		$prefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX'])) ?
			$options['PREFIX'] : 'RQ_';

		if (!empty($prefix) && is_array($entityFields) && !empty($entityFields))
		{
			$prefixLength = strlen($prefix);
			foreach ($entityFields as $k => $fieldName)
			{
				if (strlen($fieldName) > $prefixLength && substr($fieldName, 0, $prefixLength) === $prefix)
				{
					$result[] = $fieldName;
					unset($entityFields[$k]);
				}
			}
		}

		return $result;
	}

	public function prepareEntityListFieldsValues(&$entityList, $entityTypeId, $entityIds, $select, $options = array())
	{
		if (EntityRequisite::checkEntityType($entityTypeId)
			&& is_array($entityList) && !empty($entityList)
			&& is_array($entityIds) && !empty($entityIds)
			&& is_array($select) && !empty($select))
		{
			$exportMode = (is_array($options) && isset($options['EXPORT_TYPE'])
				&& ($options['EXPORT_TYPE'] === 'csv' || $options['EXPORT_TYPE'] === 'excel')) ?
				$options['EXPORT_TYPE'] : '';
			$prefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX'])) ?
				$options['PREFIX'] : 'RQ_';

			$rqFieldCountryMap = $this->getRqFieldsCountryMap();
			$userFields = array_fill_keys($this->getUserFields(), true);

			// parse fields
			$prefixLength = strlen($prefix);
			$countryFieldMap = array();
			$countries = array();
			$fields = array();
			$selectInfo = array();
			foreach ($select as $fieldString)
			{
				if (strlen($fieldString) <= $prefixLength)
					continue;

				$fieldPrefix = '';
				$fieldName = '';
				$countryId = 0;
				$fieldParsed = false;
				if (
					preg_match(
						'/^((UF_|RQ_)\w+)\|(\d+)$/'.BX_UTF_PCRE_MODIFIER,
						substr($fieldString, $prefixLength),
						$matches
					)
				)
				{
					$fieldName = $matches[1];
					$fieldPrefix = $matches[2];
					$countryId = (int)$matches[3];

					$fieldParsed = (
						$fieldPrefix === 'RQ_' ?
							(is_array($rqFieldCountryMap[$fieldName])
								&& in_array($countryId, $rqFieldCountryMap[$fieldName], true))
							: (isset($userFields[$fieldName])
								&& in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true))
					);
				}
				if ($fieldParsed)
				{
					$countries[$countryId] = true;
					$fields[$fieldName] = true;
					$countryFieldMap[$countryId][$fieldName] = true;
					$selectInfo[$fieldString] = array(
						'FIELD_NAME' => $fieldName,
						'COUNTRY_ID' => $countryId
					);
				}
			}

			if (!empty($countries))
			{
				$selectAddress = false;
				$fieldList = array_keys($fields);
				if (isset($fields['ID']))
					unset($fields['ID']);
				if (isset($fields['ENTITY_ID']))
					unset($fields['ENTITY_ID']);
				if (isset($fields['PRESET.COUNTRY_ID']))
					unset($fields['PRESET.COUNTRY_ID']);
				if (isset($fields[EntityRequisite::ADDRESS]))
				{
					$selectAddress = true;
					unset($fields[EntityRequisite::ADDRESS]);
				}

				$res = $this->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => (int)$entityTypeId,
							'=ENTITY_ID' => $entityIds,
							'=PRESET.COUNTRY_ID' => array_keys($countries)
						),
						'select' => array_merge(
							array(
								0 => 'ID',
								1 => 'ENTITY_ID',
								'PRESET_COUNTRY_ID' => 'PRESET.COUNTRY_ID'
							),
							array_keys($fields)
						)
					)
				);

				$rqFieldTypeInfo = array();
				$resultData = array();
				$addressRelations = array();
				$requisiteCountries = array();
				while ($row = $res->fetch())
				{
					$requisiteId = (int)$row['ID'];
					$entityId = (int)$row['ENTITY_ID'];
					$countryId = (int)$row['PRESET_COUNTRY_ID'];
					$requisiteCountries[$requisiteId] = $countryId;

					if (!is_array($rqFieldTypeInfo[$countryId]))
						$rqFieldTypeInfo[$countryId] = EntityRequisite::getFormFieldsInfo($countryId);

					if (isset($countryFieldMap[$countryId]))
					{
						foreach ($select as $fieldString)
						{
							if (isset($selectInfo[$fieldString]))
							{
								$fieldName = $selectInfo[$fieldString]['FIELD_NAME'];
								$fieldCountryId = $selectInfo[$fieldString]['COUNTRY_ID'];

								if (!is_array($resultData[$entityId][$fieldString]))
									$resultData[$entityId][$fieldString] = array();

								if ($countryId === $fieldCountryId)
								{
									$value = isset($row[$fieldName]) ? $row[$fieldName] : null;
									if ($fieldName === EntityRequisite::ADDRESS)
									{
										$resultData[$entityId][$fieldString][$requisiteId] = array();
										$addressRelations[$requisiteId] =
										&$resultData[$entityId][$fieldString][$requisiteId];
									}
									else
									{
										$resultData[$entityId][$fieldString][$requisiteId] =
											isset($countryFieldMap[$countryId][$fieldName]) ? $value : null;
									}
								}
							}
						}
					}
				}

				// collect addresses
				if (!empty($addressRelations))
				{
					$addr = new RequisiteAddress();
					$res = $addr->getList(
						array(
							'filter' => array(
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
								'ENTITY_ID' => array_keys($addressRelations)
							)
						)
					);

					while ($row = $res->fetch())
					{
						$requisiteId = (int)$row['ENTITY_ID'];
						$addressTypeId = (int)$row['TYPE_ID'];

						if (!is_array($addressRelations[$requisiteId]))
							$addressRelations[$requisiteId] = array();
						$addressRelations[$requisiteId][$addressTypeId] = $row;
					}
				}

				foreach ($entityList as $entityId => &$entityFields)
				{
					foreach ($select as $fieldString)
					{
						if (!isset($selectInfo[$fieldString]))
						{
							$entityFields['~'.$fieldString] = array();
							$entityFields[$fieldString] = null;
							continue;
						}

						$fieldName = $selectInfo[$fieldString]['FIELD_NAME'];
						$fieldCountryId = $selectInfo[$fieldString]['COUNTRY_ID'];
						$currentCountryId = EntityPreset::getCurrentCountryId();

						$valueData = is_array($resultData[$entityId][$fieldString]) ?
							$resultData[$entityId][$fieldString] : array();
						$entityFields['~'.$fieldString] = $valueData;

						$valueIsSet = false;
						$valueIsArray = false;
						$value = null;
						if ($fieldName === EntityRequisite::ADDRESS)
						{
							foreach ($valueData as $requisiteId => $addressList)
							{
								if (is_array($addressList) && !empty($addressList)
									&& isset($requisiteCountries[$requisiteId]))
								{
									foreach ($addressList as $addressTypeId => $addressData)
									{
										if (is_array($addressData) && !empty($addressData))
										{
											if (!empty($exportMode))
											{
												$addressValue = Format\RequisiteAddressFormatter::formatByCountry(
													$addressData,
													$fieldCountryId,
													array(
														'SEPARATOR' => Format\AddressSeparator::NewLine,
														'NL2BR' => false,
														'TYPE_ID' => RequisiteAddress::Primary,
														'HTML_ENCODE' => ($exportMode === 'excel')
													)
												);

												if ($valueIsSet)
												{
													if (!$valueIsArray)
													{
														$value = array($value);
														$valueIsArray = true;
													}
													$value[] = $addressValue;
												}
												else
												{
													$value = $addressValue;
													$valueIsSet = true;
												}
											}
											else
											{
												$addressValue = nl2br(
													Format\RequisiteAddressFormatter::formatByCountry(
														$addressData,
														$fieldCountryId,
														array(
															'SEPARATOR' => Format\AddressSeparator::HtmlLineBreak,
															'NL2BR' => false,
															'TYPE_ID' => RequisiteAddress::Primary,
															'HTML_ENCODE' => true
														)
													)
												);

												if ($valueIsSet)
												{
													$style = ' class="rq-multi-val-sep"';
												}
												else
												{
													$value = '';
													$style = '';
												}
												$value .= '<div'.$style.'>'.$addressValue.'</div>';
												$valueIsSet = true;
											}
										}
									}
								}
							}
						}
						else
						{
							foreach ($valueData as $requisiteId => $subValue)
							{
								if ($subValue != '' && isset($requisiteCountries[$requisiteId]))
								{
									$countryId = $requisiteCountries[$requisiteId];
									if ($fieldCountryId === $countryId
										&& is_array($rqFieldTypeInfo[$countryId][$fieldName]))
									{
										$fieldInfo = $rqFieldTypeInfo[$countryId][$fieldName];
										if (isset($fieldInfo['type']))
										{
											switch ($fieldInfo['type'])
											{
												case 'string':
													break;
												case 'boolean':
													if (isset($fieldInfo['isUF']) && $fieldInfo['isUF'])
														$subValue = intval($subValue) > 0 ? 'Y' : 'N';
													$subValue =
														($subValue === 'Y') ?
															GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
													break;
												case 'double':
													break;
												case 'datetime':
													if ($subValue instanceof Main\Type\DateTime)
														$subValue = $subValue->toString();
													break;
											}

											if (!empty($exportMode))
											{
												if ($valueIsSet)
												{
													if (!$valueIsArray)
													{
														$value = array($value);
														$valueIsArray = true;
													}
													$value[] = $subValue;
												}
												else
												{
													$value = htmlspecialcharsbx($subValue);
													$valueIsSet = true;
												}
											}
											else
											{
												if ($valueIsSet)
												{
													$style = ' class="rq-multi-val-sep"';
												}
												else
												{
													$value = '';
													$style = '';
												}
												$value .= '<div'.$style.'>'.htmlspecialcharsbx($subValue).'</div>';
												$valueIsSet = true;
											}
										}
									}
								}
							}
						}
						$entityFields[$fieldString] = $value;
					}
				}
				unset($entityFields);
			}
		}
	}

	/**
	 * Preparing the details of the company or contact for export.
	 * @param int $entityTypeId Entity type ID.
	 * @param array $entityIds List of entities IDs.
	 * @param array $options Options, such as prefix and others.
	 *
	 * @return array Array with two elements: 'HEADERS' and 'EXPORT_DATA'. 'HEADERS' - List of headers for export the
	 * requisites, addresses, bank details. 'EXPORT_DATA' - Requisite entities structured as
	 * hierarchy $result[$entityId][$requisiteId]...
	 *
	 * @throws Main\NotSupportedException
	 */
	public function prepareEntityListRequisiteExportData($entityTypeId, $entityIds, $options = array())
	{
		$result = array(
			'HEADERS' => array(),
			'EXPORT_DATA' => array()
		);

		$requisiteHeaders = array();
		$requisiteList = array();

		$rqPrefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX']))
			? $options['PREFIX']
			: (is_array($options) && isset($options['RQ_PREFIX']) && is_string($options['RQ_PREFIX'])
				? $options['RQ_PREFIX'] : 'RQ_');
		$bdPrefix = (is_array($options) && isset($options['BD_PREFIX']) && is_string($options['BD_PREFIX'])) ?
			$options['BD_PREFIX'] : 'BD_';

		$fillRequisiteHeaders = !(is_array($options) && isset($options['FILL_HEADERS'])
			&& $options['FILL_HEADERS'] !== 'Y');

		$rqHeadersByCountry = array();
		$bdHeadersByCountry = array();
		$countryList = null;
		$fieldTitleMap = null;

		$rqFieldInfo = array();
		$userFieldInfo = $this->getFormUserFieldsInfo();

		if ($fillRequisiteHeaders)
		{
			$countryList = EntityPreset::getCountryList();
			$fieldTitleMap = $this->getRqFieldTitleMap();
		}

		if (EntityRequisite::checkEntityType($entityTypeId)
			&& is_array($entityIds) && !empty($entityIds))
		{
			// load presets
			$presetIdsMap = array_fill_keys(self::getPresetsByEntities($entityTypeId, $entityIds), true);

			$requisiteFieldsSelectMap = array_fill_keys(
				array(
					'ID',
					'PRESET_ID',
					'NAME',
					'ACTIVE',
					'SORT'
				),
				true
			);
			$presetCountriesMap = array();
			$rqFieldsCountryMap = array();
			$presetList = array();
			$allowedCountries = array_fill_keys($this->getAllowedRqFieldCountries(), true);
			$allowedRqFieldsMap = array();
			foreach ($this->getRqFieldsCountryMap() as $fieldName => $fieldCountries)
				$allowedRqFieldsMap[$fieldName] = array_fill_keys($fieldCountries, true);
			$allowedUserFieldsMap = array_fill_keys(array_keys($userFieldInfo), true);
			$presetsByEntityType = self::getPresetsByEntityType($entityTypeId);
			$preset = EntityPreset::getSingleInstance();
			if (is_array($presetsByEntityType) && !empty($presetsByEntityType))
			{
				$res = $preset->getList(array(
					'order' => array(),
					'filter' => array(
						'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
						'@ID' => $presetsByEntityType
					),
					'select' => array('ID', 'NAME', 'COUNTRY_ID', 'SETTINGS')
				));
				while ($row = $res->fetch())
				{
					$id = (int)$row['ID'];
					$presetUsed = isset($presetIdsMap[$id]);
					$name = $row['NAME'];
					$countryId = (int)$row['COUNTRY_ID'];
					if ($countryId > 0 && isset($allowedCountries[$countryId]) && is_array($row['SETTINGS']))
					{
						$presetCountriesMap[$countryId] = true;
						$presetFieldsMap = array();

						// sort preset fields
						$sortData = array(
							'FIELD_NAME' => array(),
							'SORT' => array(),
							'ID' => array()
						);
						foreach ($preset->settingsGetFields($row['SETTINGS']) as $fieldInfo)
						{
							$fieldId = isset($fieldInfo['ID']) ? (int)$fieldInfo['ID'] : 0;
							$fieldSort = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
							$fieldName = isset($fieldInfo['FIELD_NAME']) ? $fieldInfo['FIELD_NAME'] : '';
							if (is_string($fieldName) && strlen($fieldName) > 0 &&
								(isset($allowedRqFieldsMap[$fieldName][$countryId])
									|| isset($allowedUserFieldsMap[$fieldName])))
							{
								$sortData['ID'][] = $fieldId;
								$sortData['SORT'][] = $fieldSort;
								$sortData['FIELD_NAME'][] = $fieldName;
							}
						}
						if (!empty($sortData['ID']) && count($sortData['ID']) > 1)
						{
							array_multisort(
								$sortData['SORT'], SORT_ASC, SORT_NUMERIC,
								$sortData['ID'], SORT_ASC, SORT_NUMERIC,
								$sortData['FIELD_NAME']
							);
						}
						foreach ($sortData['FIELD_NAME'] as $fieldName)
						{
							if ($presetUsed)
								$presetFieldsMap[$fieldName] = true;
							$rqFieldsCountryMap[$countryId][$fieldName] = true;
						}
						unset($sortData);

						if ($presetUsed)
						{
							$presetFields = array_keys($presetFieldsMap);
							foreach ($presetFields as $fieldName)
							{
								if (!isset($requisiteFieldsSelectMap[$fieldName]))
									$requisiteFieldsSelectMap[$fieldName] = true;
							}
							$presetList[$id] = array(
								'ID' => $id,
								'NAME' => $name,
								'COUNTRY_ID' => $countryId,
								'FIELDS' => $presetFields
							);
						}
					}
				}

			}
			unset(
				$allowedRqFieldsMap, $allowedUserFieldsMap, $presetsByEntityType, $res, $row, $presetUsed,
				$id, $name, $countryId, $presetFieldsMap, $fieldInfo, $presetFields, $fieldId, $fieldSort,
				$fieldName
			);

			$requisiteBasicExportFields = self::getBasicExportFieldsInfo();

			// fill requisite headers
			$addressLabels = null;
			$addressFields = null;
			$countrySort = null;
			if ($fillRequisiteHeaders)
			{
				foreach ($requisiteBasicExportFields as $fieldName => $fieldInfo)
				{
					$fieldTitle = (is_array($fieldInfo) && isset($fieldInfo['title'])) ? $fieldInfo['title'] : '';
					if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
						$fieldTitle = $fieldName;
					if (!isset($rqHeadersByCountry[0]))
						$rqHeadersByCountry[0] = array();
					$rqHeadersByCountry[0][] = array(
						'id' => $rqPrefix."$fieldName",
						'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').': '.$fieldTitle,
						'sort' => false,
						'default' => false,
						'editable' => false,
						'type' => 'custom'
					);
				}
				$hideCountry = false/*(count($presetCountriesMap) <= 1)*/;
				$currentCountryId = EntityPreset::getCurrentCountryId();
				if ($countrySort === null)
				{
					$countrySort = array();
					if (isset($presetCountriesMap[$currentCountryId]))
						$countrySort[] = $currentCountryId;
					foreach (array_keys($presetCountriesMap) as $countryId)
					{
						if ($countryId !== $currentCountryId)
							$countrySort[] = $countryId;
					}
				}
				foreach ($countrySort as $countryId)
				{
					foreach (array_keys($rqFieldsCountryMap[$countryId]) as $fieldName)
					{
						if (isset($userFieldInfo[$fieldName]))
						{
							$fieldTitle = isset($userFieldInfo[$fieldName]['title']) ?
								$userFieldInfo[$fieldName]['title'] : '';
						}
						else
						{
							$fieldTitle = isset($fieldTitleMap[$fieldName][$countryId]) ?
								$fieldTitleMap[$fieldName][$countryId] : '';
						}

						if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
							$fieldTitle = $fieldName;

						if (!isset($rqHeadersByCountry[$countryId]))
							$rqHeadersByCountry[$countryId] = array();
						$rqHeadersByCountry[$countryId][] = array(
							'id' => $rqPrefix."$fieldName|$countryId",
							'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
								($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.$fieldTitle,
							'sort' => false,
							'default' => false,
							'editable' => false,
							'type' => 'custom'
						);

						// headers for separated address fields
						if ($fieldName === self::ADDRESS)
						{
							$addressTypeLabel = GetMessage('CRM_REQUISITE_EXPORT_ADDRESS_TYPE_LABEL');
							if (!is_string($addressTypeLabel) || strlen($addressTypeLabel) <= 0)
								$addressTypeLabel = $fieldName.'_TYPE';
							if ($addressLabels === null)
							{
								$addressLabels = array_merge(
									array('TYPE' => $addressTypeLabel),
									RequisiteAddress::getShortLabels(RequisiteAddress::Primary)
								);
							}
							if ($addressFields === null)
							{
								$addressFields = array_merge(
									array('TYPE'),
									array_keys(EntityRequisite::getAddressFieldMap(RequisiteAddress::Primary))
								);
							}
							foreach ($addressFields as $addrFieldName)
							{
								if ($addrFieldName === 'COUNTRY_CODE')
									continue;

								if (!isset($rqHeadersByCountry[$countryId]))
									$rqHeadersByCountry[$countryId] = array();
								$rqHeadersByCountry[$countryId][] = array(
									'id' => $rqPrefix."{$fieldName}_{$addrFieldName}|$countryId",
									'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
										($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.
										$fieldTitle.' - '.ToLower($addressLabels[$addrFieldName]),
									'sort' => false,
									'default' => false,
									'editable' => false,
									'type' => 'custom'
								);
							}
						}
					}
				}
			}

			// load addresses
			$addresses = RequisiteAddress::getByEntities($entityTypeId, $entityIds);

			// get fields info
			foreach (array_keys($presetCountriesMap) as $countryId)
				$rqFieldInfo[$countryId] = $this->getFormFieldsInfo($countryId);

			// load requisites
			if (is_array($entityIds) && !empty($entityIds))
			{
				$res = $this->getList(
					array(
						'order' => array('SORT', 'ID'),
						'select' => array_merge(array('ENTITY_ID'), array_keys($requisiteFieldsSelectMap)),
						'filter' => array('@ENTITY_ID' => $entityIds, '=ENTITY_TYPE_ID' => $entityTypeId)
					)
				);
				while ($row = $res->fetch())
				{
					$entityId = (int)$row['ENTITY_ID'];
					$requisiteId = (int)$row['ID'];
					$presetId = (int)$row['PRESET_ID'];
					if ($entityId > 0 && $requisiteId > 0 && $presetId > 0
						&& isset($presetList[$presetId])
						&& isset($presetList[$presetId]['COUNTRY_ID']))
					{
						if (!isset($requisiteList[$entityId]))
							$requisiteList[$entityId] = array();
						if (!isset($requisiteList[$entityId][$requisiteId]))
							$requisiteList[$entityId][$requisiteId] = array();
						$countryId = $presetList[$presetId]['COUNTRY_ID'];
						foreach (array_keys($requisiteBasicExportFields) as $fieldName)
						{
							switch ($fieldName)
							{
								case 'ID':
								case 'PRESET_ID':
									$requisiteList[$entityId][$requisiteId][$fieldName] = (int)$row[$fieldName];
									break;
								case 'PRESET_NAME':
									$requisiteList[$entityId][$requisiteId][$fieldName] =
										isset($presetList[$presetId]['NAME']) ? $presetList[$presetId]['NAME'] : '';
									break;
								case 'PRESET_COUNTRY_ID':
									$requisiteList[$entityId][$requisiteId][$fieldName] = $countryId;
									break;
								case 'PRESET_COUNTRY_NAME':
									if ($countryList === null)
										$countryList = EntityPreset::getCountryList();
									$requisiteList[$entityId][$requisiteId][$fieldName] = $countryList[$countryId];
									break;
								case 'ACTIVE':
									$requisiteList[$entityId][$requisiteId][$fieldName] =
										(isset($row[$fieldName]) && $row[$fieldName] === 'Y') ?
											GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
									break;
								default:
									$requisiteList[$entityId][$requisiteId][$fieldName] =
										isset($row[$fieldName]) ? $row[$fieldName] : '';
							}
						}
						if (is_array($presetList[$presetId]['FIELDS'])
							&& !empty($presetList[$presetId]['FIELDS']))
						{
							foreach ($presetList[$presetId]['FIELDS'] as $fieldName)
							{
								if ($fieldName === self::ADDRESS)
								{
									$requisiteList[$entityId][$requisiteId][$fieldName] =
										is_array($addresses[$entityId][$requisiteId]) ?
											$addresses[$entityId][$requisiteId] : array();
								}
								else
								{
									$value = $row[$fieldName];
									$fieldType = '';
									$isUF = false;
									if (is_array($rqFieldInfo[$countryId][$fieldName]))
									{
										$fieldType = $rqFieldInfo[$countryId][$fieldName]['type'];
										$isUF = $rqFieldInfo[$countryId][$fieldName]['isUF'];
									}
									switch ($fieldType)
									{
										case 'boolean':
											if ($isUF)
												$value = (intval($value) > 0) ? 'Y' : 'N';
											$value = ($value === 'Y') ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
											break;

										case 'datetime':
											if ($value instanceof Main\Type\DateTime)
												$value = $value->toString();
											break;
									}
									$requisiteList[$entityId][$requisiteId][$fieldName] = $value;
									unset($value);
								}
							}
						}
					}
				}
			}

			// load bank details
			$requisiteListById = array();
			foreach ($requisiteList as $enttityId => $entityRequisites)
			{
				foreach (array_keys($entityRequisites) as $requisiteId)
					$requisiteListById[$requisiteId] = &$entityRequisites[$requisiteId];
			}
			unset($enttityId, $entityRequisites, $requisiteId, $requisiteFields);
			$bankDetailList = EntityBankDetail::getByOwners(
				\CCrmOwnerType::Requisite, array_keys($requisiteListById),
				$requisiteListById
			);
			$bankDetail = EntityBankDetail::getSingleInstance();
			$bankDetailBasicExportFields = EntityBankDetail::getBasicExportFieldsInfo();
			$bankDetailRqFieldCountryMap = $bankDetail->getRqFieldByCountry();
			foreach ($requisiteList as $entityId => $entityRequisites)
			{
				foreach (array_keys($entityRequisites) as $requisiteId)
				{
					if (is_array($bankDetailList[$requisiteId]))
					{
						foreach ($bankDetailList[$requisiteId] as $bankDetailId => $bankDetailFields)
						{
							$countryId = isset($requisiteListById[$requisiteId]['PRESET_COUNTRY_ID'])
								? (int)$requisiteListById[$requisiteId]['PRESET_COUNTRY_ID']
								: (isset($bankDetailFields['COUNTRY_ID']) ? (int)$bankDetailFields['COUNTRY_ID'] : 0);
							if (!isset($requisiteList[$entityId][$requisiteId]['BANK_DETAILS']))
								$requisiteList[$entityId][$requisiteId]['BANK_DETAILS'] = array();
							$bankDetailExportFields = array();
							foreach (array_keys($bankDetailBasicExportFields) as $fieldName)
							{
								switch ($fieldName)
								{
									case 'ID':
									case 'COUNTRY_ID':
										$bankDetailExportFields[$fieldName] =
											isset($bankDetailFields[$fieldName]) ?
												(int)$bankDetailFields[$fieldName] : 0;
										break;
									case 'COUNTRY_NAME':
										if ($countryList === null)
											$countryList = EntityPreset::getCountryList();
										$bankDetailExportFields[$fieldName] = $countryList[$countryId];
										break;
									case 'ACTIVE':
										$bankDetailExportFields[$fieldName] =
											(isset($bankDetailFields[$fieldName])
												&& $bankDetailFields[$fieldName] === 'Y') ?
												GetMessage('MAIN_YES') : GetMessage('MAIN_NO');;
										break;
									default:
										$bankDetailExportFields[$fieldName] =
											isset($bankDetailFields[$fieldName]) ? $bankDetailFields[$fieldName] : '';
								}
							}
							if (is_array($bankDetailRqFieldCountryMap[$countryId]))
							{
								foreach ($bankDetailRqFieldCountryMap[$countryId] as $fieldName)
								{
									$bankDetailExportFields[$fieldName] = isset($bankDetailFields[$fieldName]) ?
										$bankDetailFields[$fieldName] : '';
								}
							}
							$requisiteList[$entityId][$requisiteId]['BANK_DETAILS'][$bankDetailId] =
								$bankDetailExportFields;
						}
						unset($bankDetailExportFields);
					}
				}
			}

			// fill bank detail headers
			if ($fillRequisiteHeaders)
			{
				foreach ($bankDetailBasicExportFields as $fieldName => $fieldInfo)
				{
					$fieldTitle = (is_array($fieldInfo) && isset($fieldInfo['title'])) ? $fieldInfo['title'] : '';
					if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
						$fieldTitle = $fieldName;
					if (!isset($bdHeadersByCountry[0]))
						$bdHeadersByCountry[0] = array();
					$bdHeadersByCountry[0][] = array(
						'id' => $bdPrefix."$fieldName",
						'name' => GetMessage('CRM_BANK_DETAIL_FILTER_PREFIX').': '.$fieldTitle,
						'sort' => false,
						'default' => false,
						'editable' => false,
						'type' => 'custom'
					);
				}
				$bankDetailRqFieldTitleMap = $bankDetail->getRqFieldTitleMap();
				$hideCountry = false/*(count($countrySort) <= 1)*/;
				foreach ($countrySort as $countryId)
				{
					if (!isset($bankDetailRqFieldCountryMap[$countryId]))
						continue;

					foreach ($bankDetailRqFieldCountryMap[$countryId] as $fieldName)
					{
						$fieldTitle = isset($bankDetailRqFieldTitleMap[$fieldName][$countryId]) ?
							$bankDetailRqFieldTitleMap[$fieldName][$countryId] : '';
						if (!is_string($fieldTitle) || strlen($fieldTitle) <= 0)
							$fieldTitle = $fieldName;
						if (!isset($bdHeadersByCountry[$countryId]))
							$bdHeadersByCountry[$countryId] = array();
						$bdHeadersByCountry[$countryId][] = array(
							'id' => $bdPrefix."$fieldName|$countryId",
							'name' => GetMessage('CRM_BANK_DETAIL_FILTER_PREFIX').
								($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.$fieldTitle,
							'sort' => false,
							'default' => false,
							'editable' => false,
							'type' => 'custom'
						);
					}
				}
			}

			// fill headers
			if ($fillRequisiteHeaders)
			{
				$indexes = array_merge(array(0), $countrySort);
				foreach ($indexes as $index)
				{
					if (isset($rqHeadersByCountry[$index]))
					{
						$requisiteHeaders = array_merge($requisiteHeaders, $rqHeadersByCountry[$index]);
						unset($rqHeadersByCountry[$index]);
					}
				}
				foreach ($indexes as $index)
				{
					if(isset($bdHeadersByCountry[$index]))
					{
						$requisiteHeaders = array_merge($requisiteHeaders, $bdHeadersByCountry[$index]);
						unset($bdHeadersByCountry[$index]);
					}
				}
			}
		}

		$result['HEADERS'] = $requisiteHeaders;
		$result['EXPORT_DATA'] = $requisiteList;

		return $result;
	}

	/**
	 * Converts export data to flat format.
	 * @param array $requisiteExportData Requisite's export data structured as hierarchy
	 * $result[$entityId][$requisiteId]...
	 * @param array $requisiteHeaders List of headers for export the requisites, addresses, bank details.
	 * @param array $options Options, such as prefix and others.
	 *
	 * @return array Converted data in flat format
	 */
	public function entityListRequisiteExportDataFormatMultiline($requisiteExportData, $requisiteHeaders,
		$options = array())
	{
		$result = array();

		$rqPrefix = (is_array($options) && isset($options['PREFIX']) && is_string($options['PREFIX']))
			? $options['PREFIX']
			: (is_array($options) && isset($options['RQ_PREFIX']) && is_string($options['RQ_PREFIX'])
				? $options['RQ_PREFIX'] : 'RQ_');
		$bdPrefix = (is_array($options) && isset($options['BD_PREFIX']) && is_string($options['BD_PREFIX'])) ?
			$options['BD_PREFIX'] : 'BD_';

		$exportType = (is_array($options) && isset($options['EXPORT_TYPE'])
			&& ($options['EXPORT_TYPE'] === 'csv' || $options['EXPORT_TYPE'] === 'excel')) ?
			$options['EXPORT_TYPE'] : 'csv';

		$addressTypeList = null;

		foreach ($requisiteExportData as $entityId => $requisiteList)
		{
			$entityData = array();

			// make empty row
			$emptyRow = array();
			foreach ($requisiteHeaders as $header)
				$emptyRow[$header['id']] = '';

			// format export data
			foreach ($requisiteList as $requisiteFields)
			{
				$countryId = (int)$requisiteFields['PRESET_COUNTRY_ID'];
				// make multiline groups
				$groups = array();
				$groups[] = array(
					'type' => 'requisite',
					'prefix' => $rqPrefix,
					'elements' => array($requisiteFields),
					'index' => array(),
					'skipFields' => array(EntityRequisite::ADDRESS, 'BANK_DETAILS'),
					'count' => 1
				);
				$elements = array();
				if (is_array($requisiteFields[EntityRequisite::ADDRESS]))
				{
					if ($addressTypeList === null)
					{
						$addressTypeList = array();
						foreach(RequisiteAddress::getClientTypeInfos() as $typeInfo)
							$addressTypeList[$typeInfo['id']] = $typeInfo['name'];
					}
					foreach ($requisiteFields[EntityRequisite::ADDRESS] as $addressTypeId => $address)
					{
						// format full address
						$fullAddressValue = Format\RequisiteAddressFormatter::formatByCountry(
							$address,
							$countryId,
							array(
								'SEPARATOR' => Format\AddressSeparator::NewLine,
								'NL2BR' => false,
								'TYPE_ID' => RequisiteAddress::Primary,
								'HTML_ENCODE' => ($exportType === 'excel')
							)
						);
						$element = array(
							EntityRequisite::ADDRESS.'_TYPE' => isset($addressTypeList[$addressTypeId]) ?
								$addressTypeList[$addressTypeId] : $addressTypeId,
							EntityRequisite::ADDRESS => $fullAddressValue
						);
						foreach ($address as $addrFieldName => $addrFieldValue)
						{
							$element[EntityRequisite::ADDRESS.'_'.$addrFieldName] = $addrFieldValue;
						}
						$elements[] = $element;
						unset($addrFieldName, $addrFieldValue, $element);
					}
				}
				$groups[] = array(
					'type' => 'address',
					'prefix' => $rqPrefix,
					'elements' => $elements,
					'index' => array(),
					'skipFields' => array(),
					'count' => count($elements)
				);
				$elements = is_array($requisiteFields['BANK_DETAILS']) ? $requisiteFields['BANK_DETAILS'] : array();
				$groups[] = array(
					'type' => 'bankDetail',
					'prefix' => $bdPrefix,
					'elements' => $elements,
					'index' => array(),
					'skipFields' => array(),
					'count' => count($elements)
				);
				unset($elements);

				// get rows count for current requisite and fill each group's indexes
				$rowCount = 0;
				foreach ($groups as &$group)
				{
					if ($group['count'] > $rowCount)
						$rowCount = $group['count'];

					foreach (array_keys($group['elements']) as $key)
						$group['index'][] = $key;
				}
				unset($group);

				for ($i = 0; $i < $rowCount; $i++)
				{
					$rowData = $emptyRow;
					foreach ($groups as $group)
					{
						if ($group['count'] > $i)
						{
							
							foreach ($group['elements'][$group['index'][$i]] as $fieldName => $value)
							{
								if (!in_array($fieldName, $group['skipFields'], true))
								{
									$headerId = $group['prefix'].$fieldName;
									if(isset($rowData[$headerId]))
									{
										$rowData[$headerId] = $value;
									}
									else
									{
										$headerId .= '|'.$countryId;
										if(isset($rowData[$headerId]))
											$rowData[$headerId] = $value;
									}
								}
							}
						}
					}
					$entityData[] = array_values($rowData);
				}
			}

			$result[$entityId] = $entityData;
		}

		return $result;
	}

	/**
	 * Imports requisites for the company or contact. Now supports only import for addresses.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID for import
	 * @param string $dupControlType Duplicate control type ("NO_CONTROL", "REPLACE", "MERGE", "SKIP")
	 * @param int $presetId Preset ID for import
	 * @param array $fields Fields of the requisite to import
	 * @return Main\Result
	 * @deprecated Moved to Requisite\ImportHelper::importOldRequisiteAddresses.
	 */
	public function importEntityRequisite($entityTypeId, $entityId, $dupControlType, $presetId = 0, $fields)
	{
		$result = new Main\Result();

		if(!in_array($dupControlType, array('REPLACE', 'MERGE', 'SKIP'), true))
			$dupControlType = 'NO_CONTROL';
		$rqImportMode = 'MERGE';
		switch ($dupControlType)
		{
			case 'REPLACE':
			case 'SKIP':
				$rqImportMode = $dupControlType;
				break;
		}
		if ($rqImportMode === 'SKIP')
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_DUP_CTRL_MODE_SKIP'),
					self::ERR_DUP_CTRL_MODE_SKIP
				)
			);
			return $result;
		}

		if (!self::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		if ($entityId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_INVALID_ENTITY_ID'),
					self::ERR_INVALID_ENTITY_ID
				)
			);
			return $result;
		}

		if (!$this->validateEntityExists($entityTypeId, $entityId))
		{
			$errMsg = '';
			$errCode = 0;
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Company:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_COMPANY_NOT_EXISTS', array('#ID#' => $entityId));
					$errCode = self::ERR_COMPANY_NOT_EXISTS;
					break;
				case \CCrmOwnerType::Contact:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_CONTACT_NOT_EXISTS', array('#ID#' => $entityId));
					$errCode = self::ERR_CONTACT_NOT_EXISTS;
					break;
			}
			$result->addError(new Main\Error($errMsg, $errCode));
			return $result;
		}

		if (!self::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errMsg = '';
			$errCode = 0;
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Company:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_ACCESS_DENIED_COMPANY_UPDATE', array('#ID#' => $entityId));
					$errCode = self::ERR_ACCESS_DENIED_COMPANY_UPDATE;
					break;
				case \CCrmOwnerType::Contact:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_ACCESS_DENIED_CONTACT_UPDATE', array('#ID#' => $entityId));
					$errCode = self::ERR_ACCESS_DENIED_CONTACT_UPDATE;
					break;
			}
			$result->addError(new Main\Error($errMsg, $errCode));
			return $result;
		}

		$entityTypeName = \CCrmOwnerType::GetDescription($entityTypeId);
		if ($presetId === 0)
		{
			$presetId = self::getDefaultPresetId($entityTypeId);

			if ($presetId <= 0)
			{
				$result->addError(
					new Main\Error(
						GetMessage(
							'CRM_REQUISITE_ERR_DEF_IMP_PRESET_NOT_DEFINED',
							array('#ENTITY_TYPE#' => $entityTypeName)
						),
						self::ERR_DEF_IMP_PRESET_NOT_DEFINED
					)
				);
				return $result;
			}
		}

		$presetId = (int)$presetId;
		if ($presetId <= 0)
		{
			$result->addError(
				new Main\Error(GetMessage('CRM_REQUISITE_ERR_INVALID_IMP_PRESET_ID'), self::ERR_INVALID_IMP_PRESET_ID)
			);
			return $result;
		}

		$preset = EntityPreset::getSingleInstance();
		$presetInfo = $preset->getById($presetId);
		$fieldsInPreset = array();
		if (!is_array($presetInfo))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_IMP_PRESET_NOT_EXISTS', array('#ID#' => $presetId)),
					self::ERR_IMP_PRESET_NOT_EXISTS
				)
			);
			return $result;
		}
		$presetName = EntityPreset::formatName($presetId, $presetInfo['NAME']);
		if (is_array($presetInfo['SETTINGS']))
		{
			$presetFieldsInfo = $preset->settingsGetFields($presetInfo['SETTINGS']);
			foreach ($presetFieldsInfo as $fieldInfo)
			{
				if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
					$fieldsInPreset[$fieldInfo['FIELD_NAME']] = true;
			}
		}
		if (!isset($fieldsInPreset[self::ADDRESS]))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_IMP_PRESET_HAS_NO_ADDR_FIELD', array('#ID#' => $presetId)),
					self::ERR_IMP_PRESET_HAS_NO_ADDR_FIELD
				)
			);
			return $result;
		}
		unset($preset, $presetInfo, $presetHasAddress, $presetFieldsInfo, $fieldInfo);

		$addresses = array();
		$rqAddrTypeInfos = RequisiteAddress::getTypeInfos();
		$addressFields = array(
			'ADDRESS_1',
			'ADDRESS_2',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY',
			'COUNTRY_CODE'
		);
		$rqAddrTypes = array_keys($rqAddrTypeInfos);
		if (is_array($fields)
			&& is_array($fields[self::ADDRESS])
			&& !empty($fields[self::ADDRESS]))
		{
			foreach ($fields[self::ADDRESS] as $addrTypeId => $address)
			{
				if (in_array($addrTypeId, $rqAddrTypes, true) && !RequisiteAddress::isEmpty($address))
				{
					foreach ($addressFields as $fieldName)
					{
						$addresses[$addrTypeId][$fieldName] =
							isset($address[$fieldName]) ? $address[$fieldName] : null;
					}
				}
			}
		}
		if (empty($addresses))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_REQUISITE_ERR_NO_ADDRESSES_TO_IMPORT', array('#ID#' => $presetId)),
					self::ERR_NO_ADDRESSES_TO_IMPORT
				)
			);
			return $result;
		}

		$rqIsFound = false;
		$rqListResult = $this->getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'=PRESET_ID' => $presetId,
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ENTITY_ID' => $entityId
				)
			)
		);
		while($rqRow = $rqListResult->fetch())
		{
			$requisiteId = (int)$rqRow['ID'];
			$rqIsFound = true;
			$requisiteAddresses = EntityRequisite::getAddresses($requisiteId);
			foreach($addresses as $addrTypeId => $address)
			{
				// $rqImportMode may be only 'REPLACE' or 'MERGE'
				if(!isset($requisiteAddresses[$addrTypeId])
					|| RequisiteAddress::isEmpty($requisiteAddresses[$addrTypeId])
					|| ($rqImportMode === 'REPLACE'
						&&  !RequisiteAddress::areEquals($addresses[$addrTypeId], $requisiteAddresses[$addrTypeId])))
				{
					EntityAddress::register(\CCrmOwnerType::Requisite, $requisiteId, $addrTypeId, $address);
				}
			}
		}
		if (!$rqIsFound)
		{
			$requsiteFields = array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'PRESET_ID' => $presetId,
				'NAME' => $presetName,
				'SORT' => 500,
				'ACTIVE' => 'Y'
			);
			foreach (array_keys($fieldsInPreset) as $fieldName)
			{
				if (isset($fields[$fieldName]))
					$requsiteFields[$fieldName] = $fields[$fieldName];
			}
			$requisiteAddResult = $this->add($requsiteFields);
			if(!$requisiteAddResult->isSuccess())
			{
				$rqAddErrors = $requisiteAddResult->getErrorMessages();
				$rqAddErrorStr = GetMessage(
					'CRM_REQUISITE_ERR_CREATE_REQUISITE',
					array(
						'#ENTITY_TYPE_NAME_GENITIVE#' => GetMessage(
							'CRM_REQUISITE_ERR_'.$entityTypeName.'_GENITIVE'
						),
						'#ID#' => $entityId,
					)
				);
				if (is_array($rqAddErrors) && !empty($rqAddErrors))
					$rqAddErrorStr .= ': '.$rqAddErrors[0];
				$result->addError(
					new Main\Error(
						$rqAddErrorStr,
						self::ERR_CREATE_REQUISITE
					)
				);
				return $result;
			}
		}

		return $result;
	}

	/**
	 * Unbind all requisites from seed entity and bind to target.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @return void
	 */
	public static function rebind($entityTypeID, $seedID, $targID)
	{
		$entity = self::getSingleInstance();
		$res = $entity->getList(
			array(
				'select' => array('ID'),
				'filter' => array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $seedID))
		);
		while($fields = $res->Fetch())
		{
			$entity->update($fields['ID'], array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $targID));
		}
	}

	/**
	 * Unbind requisite from seed entity and bind to target.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $targEntityID Target entity ID.
	 * @param int $seedRequisiteID Seed requisite ID.
	 * @return void
	 */
	public static function rebindRequisite($entityTypeID, $targEntityID, $seedRequisiteID)
	{
		$requisite = self::getSingleInstance();
		$res = $requisite->getList(
			array(
				'select' => array('ID'),
				'filter' => array('ID' => $seedRequisiteID))
		);
		while($fields = $res->Fetch())
		{
			$requisite->update($fields['ID'], array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $targEntityID));
		}
	}

	public static function getOwnerEntityById($id)
	{
		$result = array();

		if ($id <= 0)
			return array();

		$row = RequisiteTable::getList(array(
				'filter' => array('=ID' => $id),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
				'limit' => 1
		));

		$r = $row->fetch();

		$result['ENTITY_TYPE_ID'] = isset($r['ENTITY_TYPE_ID']) ? (int)$r['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$result['ENTITY_ID'] = isset($r['ENTITY_ID']) ? (int)$r['ENTITY_ID'] : 0;

		return $result;
	}

	/**
	 * Try to find default preset ID for specified entity type.
	 * @param int $entityTypeId Entity type ID (Company and Contact are supported only).
	 * @return int
	 * @throws Main\NotSupportedException
	 */
	public static function getDefaultPresetId($entityTypeId)
	{
		$presetId = self::getDefaultPresetIdFromOption($entityTypeId);
		if ($presetId > 0)
			return $presetId;

		$countryCode = EntityPreset::getCountryCodeById(EntityPreset::getCurrentCountryId());
		$personType = '';
		if($entityTypeId === \CCrmOwnerType::Company)
		{
			$personType = $countryCode === 'RU' ? 'COMPANY' : 'LEGALENTITY';
		}
		elseif($entityTypeId === \CCrmOwnerType::Contact)
		{
			$personType = 'PERSON';
		}

		$xmlID = str_replace(
			array('%COUNTRY%', '%PERSON%'),
			array($countryCode, $personType),
			'#CRM_REQUISITE_PRESET_DEF_%COUNTRY%_%PERSON%#'
		);

		$preset = EntityPreset::getSingleInstance();
		$res = $preset->getList(array('filter' => array('=XML_ID' => $xmlID), 'select' => array('ID')));
		$fields = $res->fetch();

		$presetId = is_array($fields) && isset($fields['ID']) ? (int)$fields['ID'] : 0;

		if ($presetId > 0)
			self::setDefaultPresetId($entityTypeId, $presetId);

		return $presetId;
	}

	/**
	 * Try to get default preset ID for specified entity type from the option.
	 * @param int $entityTypeId Entity type ID (Company and Contact are supported only).
	 * @return int
	 * @throws Main\NotSupportedException
	 */
	public static function getDefaultPresetIdFromOption($entityTypeId)
	{
		$presetId = 0;

		if($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$optionValue = Main\Config\Option::get('crm', 'requisite_default_presets');
		if ($optionValue !== '')
			$optionValue = unserialize($optionValue);
		if (!is_array($optionValue))
			$optionValue = array();

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		if (!empty($entityTypeName))
		{
			if (isset($optionValue[$entityTypeName]))
			{
				$presetId = (int)$optionValue[$entityTypeName];
				if ($presetId < 0)
					$presetId = 0;
			}
		}

		return $presetId;
	}

	public static function setDefaultPresetId($entityTypeId, $presetId)
	{
		if($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$origPresetId = $presetId;
		$presetId = (int)$presetId;
		$notFound = false;
		if ($presetId > 0)
		{
			$preset = EntityPreset::getSingleInstance();
			$row = $preset->getList(
				array(
					'filter' => array('=ID' => $presetId),
					'select' => array('ID')
				)
			)->fetch();
			if (!is_array($row))
				$notFound = true;
		}
		else
		{
			$notFound = true;
		}
		if ($notFound)
		{
			throw new Main\ObjectNotFoundException("Preset with ID '$origPresetId' is not found");
		}
		unset($notFound, $origPresetId);

		$optionValue = Main\Config\Option::get('crm', 'requisite_default_presets');
		if ($optionValue !== '')
			$optionValue = unserialize($optionValue);
		if (!is_array($optionValue))
			$optionValue = array();

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		$optionValue[$entityTypeName] = $presetId;
		Main\Config\Option::set('crm', 'requisite_default_presets', serialize($optionValue));
	}

	public static function getDuplicateCriterionFieldsMap()
	{
		if (self::$duplicateCriterionFieldsMap === null)
		{
			self::$duplicateCriterionFieldsMap = array(
				1 => array(      // ru
					'RQ_INN',
					'RQ_OGRN',
					'RQ_OGRNIP'
				),
				4 => array(      // by
					'RQ_INN'
				),
				6 => array(      // kz
					'RQ_INN',
					'RQ_BIN'
				),
				14 => array(     // ua
					'RQ_INN',
					'RQ_EDRPOU',
				),
				46 => array(     // de
					'RQ_INN'
				),
				122 => array(    // us
					'RQ_VAT_ID'
				)
			);
		}

		return self::$duplicateCriterionFieldsMap;
	}
	public static function formatDuplicateCriterionScope($countryId)
	{
		$countryId = (int)$countryId;
		if ($countryId <= 0)
			return '';

		return 'CY_'.sprintf('%03d', $countryId);
	}
	public static function getCountryIdByDuplicateCriterionScope($scope)
	{
		$result = 0;
		$scope = strval($scope);

		$matches = array();
		if (preg_match('/^CY_(\d{3})$/', $scope, $matches))
		{
			$result = (int)$matches[1];
		}

		return $result;
	}
	public static function getCountryCodeByDuplicateCriterionScope($scope)
	{
		$result = '';

		$countryId = self::getCountryIdByDuplicateCriterionScope($scope);
		if ($countryId > 0)
		{
			$result = EntityPreset::getCountryCodeById($countryId);
		}

		return $result;
	}
	public function getDuplicateCriterionFieldsDescriptions($scopeKeys = true)
	{
		$result = array();

		$fieldTitleMap = $this->getRqFieldTitleMap();

		foreach(self::getDuplicateCriterionFieldsMap() as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
			{
				$key = $scopeKeys ? self::formatDuplicateCriterionScope($countryId) : $countryId;
				if (isset($fieldTitleMap[$fieldName][$countryId]))
				{
					if (!is_array($result[$fieldName]))
						$result[$fieldName] = array();
					if (!is_array($result[$fieldName][$key]))
						$result[$fieldName][$key] = array();
					$result[$fieldName][$key] = $fieldTitleMap[$fieldName][$countryId];
				}
			}
		}

		return $result;
	}
	public static function prepareEntityInfoBatch($entityTypeId, &$entityInfos, $scope, $typeName, $options = null)
	{
		if (!($entityTypeId === \CCrmOwnerType::Company || $entityTypeId === \CCrmOwnerType::Contact))
		{
			return;
		}

		if(empty($entityInfos))
		{
			return;
		}
		
		$countryId = self::getCountryIdByDuplicateCriterionScope($scope);
		if ($countryId <= 0)
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$typeNameSql = $sqlHelper->forSql($typeName, 32);

		$Ids = array_keys($entityInfos);
		$IdsSql = implode(',', $Ids);
		$sql = "SELECT R1.ENTITY_ID, R1.{$typeNameSql}, R2.CNT".PHP_EOL.
			"FROM b_crm_requisite R1".PHP_EOL.
			"  INNER JOIN (".PHP_EOL.
			"    SELECT MIN(R0.ID) AS MIN_ID, COUNT(1) AS CNT".PHP_EOL.
			"    FROM b_crm_requisite R0 INNER JOIN b_crm_preset P0 ON R0.PRESET_ID = P0.ID AND P0.COUNTRY_ID = {$countryId}".PHP_EOL.
			"    WHERE R0.ENTITY_ID IN ({$IdsSql}) AND R0.ENTITY_TYPE_ID = {$entityTypeId}".PHP_EOL.
			"      AND R0.{$typeNameSql} IS NOT NULL AND R0.{$typeNameSql} != ''".PHP_EOL.
			"    GROUP BY R0.ENTITY_TYPE_ID, R0.ENTITY_ID".PHP_EOL.
			"  ) R2 ON R1.ID = R2.MIN_ID";
		$result = $connection->query($sql);
		while($fields = $result->fetch())
		{
			$id = (int)$fields['ENTITY_ID'];
			if(isset($entityInfos[$id]))
			{
				$entityInfos[$id][$typeName] = array(
					'FIRST_VALUE' => $fields[$typeName],
					'TOTAL' => (int)$fields['CNT']
				);
			}
		}
	}

	/**
	 * Returns the identifiers of the presets used in the requisites of the specified entities.
	 * @param int $entityTypeId Entity type ID (Company and Contact are supported only).
	 * @param array $entityIds List of entities IDs.
	 *
	 * @return array
	 */
	public static function getPresetsByEntities($entityTypeId, $entityIds)
	{
		$result = array();

		if (!($entityTypeId === \CCrmOwnerType::Company || $entityTypeId === \CCrmOwnerType::Contact)
			|| !is_array($entityIds))
		{
			return $result;
		}

		foreach ($entityIds as $k => $v)
			$entityIds[$k] = (int)$v;

		$connection = Main\Application::getConnection();
		$idsSql = implode(',', $entityIds);
		$tableName = RequisiteTable::getTableName();
		$sql = "SELECT DISTINCT PRESET_ID".PHP_EOL.
			"FROM ".$tableName.PHP_EOL.
			"WHERE ENTITY_ID IN (".$idsSql.") AND ENTITY_TYPE_ID = ".$entityTypeId;
		$res = $connection->query($sql);
		while($fields = $res->fetch())
			$result[] = (int)$fields['PRESET_ID'];

		return $result;
	}

	/**
	 * Returns the identifiers of the presets used in the requisites of the specified entity type.
	 * @param int $entityTypeId Entity type ID (Company and Contact are supported only).
	 *
	 * @throws Main\NotSupportedException
	 *
	 * @return array
	 */
	public static function getPresetsByEntityType($entityTypeId)
	{
		$result = array();

		if($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$connection = Main\Application::getConnection();
		$tableName = RequisiteTable::getTableName();
		$sql = "SELECT DISTINCT PRESET_ID from {$tableName} WHERE ENTITY_TYPE_ID = {$entityTypeId}";
		$res = $connection->query($sql);
		while($row = $res->fetch())
		{
			$presetId = (int)$row['PRESET_ID'];
			if ($presetId > 0)
				$result[] = $presetId;
		}

		return $result;
	}

	public static function getLinksByOwner($ownerEntityTypeId, $ownerEntityId)
	{
		$query = new Main\Entity\Query(Crm\Requisite\LinkTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new Main\Entity\ReferenceField('RQ',
				Crm\RequisiteTable::getEntity(),
				array(
					'=ref.ID' => 'this.REQUISITE_ID',
					'=ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression($ownerEntityTypeId),
					'=ref.ENTITY_ID' => new Main\DB\SqlExpression($ownerEntityId)
				),
				array('join_type' => 'INNER')
			)
		);

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}

	public static function getLinks($entityTypeId, $entityId)
	{
		$dbResult = Crm\Requisite\LinkTable::getList(
			array(
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeId, '=ENTITY_ID' => $entityId),
				'select' => array('*')
			)
		);
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}

	public static function setLinks(array $links)
	{
		foreach($links as $link)
		{
			$entityTypeID = isset($link['ENTITY_TYPE_ID'])
				? (int)$link['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
			$entityID = isset($link['ENTITY_ID'])
				? (int)$link['ENTITY_ID'] : 0;
			$requisiteID = isset($link['REQUISITE_ID'])
				? (int)$link['REQUISITE_ID'] : 0;

			if(!(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0 && $requisiteID > 0))
			{
				continue;
			}

			//region check if requisite exists
			$requisiteFields = RequisiteTable::getList(
				array(
					'filter' => array('=ID' => $requisiteID),
					'select' => array('ID'),
					'limit' => 1
				)
			)->fetch();

			if(!is_array($requisiteFields))
			{
				continue;
			}
			//region

			EntityLink::register(
				$entityTypeID,
				$entityID,
				$requisiteID,
				isset($link['BANK_DETAIL_ID']) ? (int)$link['BANK_DETAIL_ID'] : 0,
				isset($link['MC_REQUISITE_ID']) ? (int)$link['MC_REQUISITE_ID'] : 0,
				isset($link['MC_BANK_DETAIL_ID']) ? (int)$link['MC_BANK_DETAIL_ID'] : 0
			);
		}
	}
}
