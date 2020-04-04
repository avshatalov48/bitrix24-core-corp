<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Invoice\Invoice;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;

Loc::loadMessages(__FILE__);

class EntityBankDetail
{
	const ERR_INVALID_ENTITY_TYPE   = 201;
	const ERR_INVALID_ENTITY_ID     = 202;
	const ERR_ON_DELETE             = 203;
	const ERR_NOTHING_TO_DELETE     = 204;

	private static $singleInstance = null;

	private static $FIELD_INFOS = null;

	private static $rqFields = array(
		'RQ_BANK_NAME',
		'RQ_BANK_ADDR',
		'RQ_BANK_ROUTE_NUM',
		'RQ_BIK',
		'RQ_MFO',
		'RQ_ACC_NAME',
		'RQ_ACC_NUM',
		'RQ_IIK',
		'RQ_ACC_CURRENCY',
		'RQ_COR_ACC_NUM',
		'RQ_IBAN',
		'RQ_SWIFT',
		'RQ_BIC'
	);
	private static $rqFiltrableFields = null;
	private static $rqFieldMapByCountry = array(
		// RU
		1 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT'
		),
		// BY
		4 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_BIC',
			'RQ_ACC_CURRENCY',
			'RQ_SWIFT',
			'RQ_BANK_ADDR'
		),
		// KZ
		6 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_IIK',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT'
		),
		// UA
		14 => array(
			'RQ_BANK_NAME',
			'RQ_MFO',
			'RQ_ACC_NUM',
			'RQ_IBAN'
		),
		// DE
		46 => array(
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC'
		),
		// US
		122 => array(
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC'
		)
	);
	private static $rqFieldCountryMap = null;
	private static $rqFieldTitleMap = null;

	private static $requisite = null;

	private static $duplicateCriterionFieldsMap = null;

	protected function getRequisite()
	{
		if (self::$requisite === null)
		{
			self::$requisite = new EntityRequisite();
		}

		return self::$requisite;
	}

	public static function getSingleInstance()
	{
		if (self::$singleInstance === null)
			self::$singleInstance = new EntityBankDetail();

		return self::$singleInstance;
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
								\CCrmFieldInfoAttr::Immutable,
								\CCrmFieldInfoAttr::Hidden
						)
				),
				'ENTITY_ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable
						)
				),
				'COUNTRY_ID' => array('TYPE' => 'integer'),
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
				'ACTIVE' => array('TYPE' => 'char'),
				'SORT' => array('TYPE' => 'integer'),
				'RQ_BANK_NAME' => array('TYPE' => 'string'),
				'RQ_BANK_ADDR' => array('TYPE' => 'string'),
				'RQ_BANK_ROUTE_NUM' => array('TYPE' => 'string'),
				'RQ_BIK' => array('TYPE' => 'string'),
				'RQ_MFO' => array('TYPE' => 'string'),
				'RQ_ACC_NAME' => array('TYPE' => 'string'),
				'RQ_ACC_NUM' => array('TYPE' => 'string'),
				'RQ_IIK' => array('TYPE' => 'string'),
				'RQ_ACC_CURRENCY' => array('TYPE' => 'string'),
				'RQ_COR_ACC_NUM' => array('TYPE' => 'string'),
				'RQ_IBAN' => array('TYPE' => 'string'),
				'RQ_SWIFT' => array('TYPE' => 'string'),
				'RQ_BIC' => array('TYPE' => 'string'),
				'COMMENTS' => array('TYPE' => 'string'),
				'ORIGINATOR_ID' => array('TYPE' => 'string')
			);
		}
		return self::$FIELD_INFOS;
	}

	public static function getBasicFieldsInfo()
	{
		$result = array();

		$bankDetail = self::getSingleInstance();
		$rqFieldsMap = array_fill_keys($bankDetail->getRqFields(), true);

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
				'title' => GetMessage('CRM_BANK_DETAIL_EXPORT_FIELD_ID'),
				'type' => 'integer'
			),
			'NAME' => array(
				'title' => GetMessage('CRM_BANK_DETAIL_EXPORT_FIELD_NAME'),
				'type' => 'string'
			),
			'ACTIVE' => array(
				'title' => GetMessage('CRM_BANK_DETAIL_EXPORT_FIELD_ACTIVE'),
				'type' => 'boolean'
			),
			'SORT' => array(
				'title' => GetMessage('CRM_BANK_DETAIL_EXPORT_FIELD_SORT'),
				'type' => 'integer'
			),
			'COMMENTS' => array(
				'title' => GetMessage('CRM_BANK_DETAIL_EXPORT_FIELD_COMMENTS'),
				'type' => 'string'
			)
		);

		return $result;
	}

	public function getList($params)
	{
		return BankDetailTable::getList($params);
	}

	public function getCountByFilter($filter = array())
	{
		return BankDetailTable::getCountByFilter($filter);
	}

	public function getById($id)
	{
		$result = BankDetailTable::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	public function checkBeforeAdd($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\AddResult();
		$entity = BankDetailTable::getEntity();

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
			BankDetailTable::checkFields($result, null, $fields);

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

		$result = BankDetailTable::add($fields);
		$id = $result->isSuccess() ? (int)$result->getId() : 0;
		if ($id > 0)
		{
			$entityTypeId = isset($fields['ENTITY_TYPE_ID']) ? (int)$fields['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
			$entityId = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
			DuplicateBankDetailCriterion::registerByParent($entityTypeId, $entityId);
		}

		//region Send event
		if ($id > 0)
		{
			$event = new Main\Event('crm', 'OnAfterBankDetailAdd', array('id' => $id, 'fields' => $fields));
			$event->send();
		}
		//endregion Send event

		return $result;
	}

	public function checkBeforeUpdate($id, $fields)
	{
		unset($fields['ID'], $fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

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
		$entity = BankDetailTable::getEntity();
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
			BankDetailTable::checkFields($result, $id, $fields);

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

		$parentInfoAfterUpdate = $entityBeforeUpdate = array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'ENTITY_ID' => 0
		);
		$parentInfoBeforeUpdate = self::getOwnerEntityById($id);
		if ($parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === \CCrmOwnerType::Requisite
			&& $parentInfoBeforeUpdate['ENTITY_ID'] > 0)
		{
			$parentInfoAfterUpdate = $parentInfoBeforeUpdate;
			$entityBeforeUpdate = EntityRequisite::getOwnerEntityById($parentInfoBeforeUpdate['ENTITY_ID']);
		}
		if (isset($fields['ENTITY_TYPE_ID']))
			$parentInfoAfterUpdate['ENTITY_TYPE_ID'] = (int)$fields['ENTITY_TYPE_ID'];
		if (isset($fields['ENTITY_ID']))
			$parentInfoAfterUpdate['ENTITY_ID'] = (int)$fields['ENTITY_ID'];
		if ($parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === \CCrmOwnerType::Requisite
			&& $parentInfoBeforeUpdate['ENTITY_ID'] > 0
			&& $parentInfoAfterUpdate['ENTITY_TYPE_ID'] === \CCrmOwnerType::Requisite
			&& $parentInfoAfterUpdate['ENTITY_ID'] > 0
			&& $parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === $parentInfoAfterUpdate['ENTITY_TYPE_ID']
			&& $parentInfoBeforeUpdate['ENTITY_ID'] === $parentInfoAfterUpdate['ENTITY_ID'])
		{
			$entityAfterUpdate = $entityBeforeUpdate;
		}
		else
		{
			$entityAfterUpdate = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
				'ENTITY_ID' => 0
			);
			if ($parentInfoAfterUpdate['ENTITY_TYPE_ID'] === \CCrmOwnerType::Requisite
				&& $parentInfoAfterUpdate['ENTITY_ID'] > 0)
			{
				$entityAfterUpdate = EntityRequisite::getOwnerEntityById($parentInfoAfterUpdate['ENTITY_ID']);
			}
		}
		unset($parentInfoBeforeUpdate, $parentInfoAfterUpdate);
		$entityTypeIdModified = $entityIdModified = false;
		$entityTypeId = $entityAfterUpdate['ENTITY_TYPE_ID'];
		if (\CCrmOwnerType::IsDefined($entityTypeId)
			&& \CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
			&& $entityTypeId !== $entityBeforeUpdate['ENTITY_TYPE_ID'])
		{
			$entityTypeIdModified = true;
		}
		$entityId = $entityAfterUpdate['ENTITY_ID'];
		if ($entityId > 0 && $entityBeforeUpdate['ENTITY_ID'] > 0 && $entityId !== $entityBeforeUpdate['ENTITY_ID'])
		{
			$entityIdModified = true;
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

		$result = BankDetailTable::update($id, $fields);
		if ($result->isSuccess())
		{
			if ($entityTypeIdModified || $entityIdModified)
			{
				DuplicateBankDetailCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'], $entityBeforeUpdate['ENTITY_ID']
				);

				DuplicateBankDetailCriterion::unregister($entityTypeId, $entityId);
			}

			if (isset($fields['ENTITY_TYPE_ID']) && isset($fields['ENTITY_ID']))
			{
				$entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
				$entityId = (int)$fields['ENTITY_ID'];
				if ($entityTypeId === \CCrmOwnerType::Requisite && $entityId > 0)
					DuplicateBankDetailCriterion::registerByParent($entityTypeId, $entityId);
			}
			else
			{
				DuplicateBankDetailCriterion::registerByBankDetail($id);
			}
		}

		//region Send event
		if ($result->isSuccess())
		{
			$event = new Main\Event('crm', 'OnAfterBankDetailUpdate', array('id' => $id, 'fields' => $fields));
			$event->send();
		}
		//endregion Send event

		return $result;
	}

	public function delete($id, $options = array())
	{
		$entityInfo = array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'ENTITY_ID' => 0
		);
		$parentInfo = self::getOwnerEntityById($id);
		if ($parentInfo['ENTITY_TYPE_ID'] === \CCrmOwnerType::Requisite)
			$entityInfo = EntityRequisite::getOwnerEntityById($parentInfo['ENTITY_ID']);
		unset($parentInfo);

		$result = BankDetailTable::delete($id);
		if ($result->isSuccess()
			&& \CCrmOwnerType::IsDefined($entityInfo['ENTITY_TYPE_ID']) && $entityInfo['ENTITY_ID'] > 0)
		{
			DuplicateBankDetailCriterion::registerByEntity($entityInfo['ENTITY_TYPE_ID'], $entityInfo['ENTITY_ID']);
		}

		//region Send event
		if ($result->isSuccess())
		{
			$event = new Main\Event('crm', 'OnAfterBankDetailDelete', array('id' => $id));
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

		//Usually check is disabled for suspended types (SuspendedRequisite)
		$enableTypeCheck = !isset($options['enableCheck']) || $options['enableCheck'] === true;
		if ($enableTypeCheck && !self::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		if ($entityId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_INVALID_ENTITY_ID'),
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
						GetMessage('CRM_BANKDETAIL_ERR_ON_DELETE', array('#ID#', $row['ID'])),
						self::ERR_ON_DELETE
					)
				);
			}
		}

		if ($cnt === 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_NOTHING_TO_DELETE'),
					self::ERR_NOTHING_TO_DELETE
				)
			);
		}

		return $result;
	}

	public function getRqFields()
	{
		return self::$rqFields;
	}

	public function getRqFiltrableFields()
	{
		if (self::$rqFiltrableFields === null)
		{
			self::$rqFiltrableFields = array(
				'RQ_BANK_NAME',
				'RQ_BANK_ROUTE_NUM',
				'RQ_BIK',
				'RQ_MFO',
				'RQ_ACC_NAME',
				'RQ_ACC_NUM',
				'RQ_IIK',
				'RQ_COR_ACC_NUM',
				'RQ_IBAN',
				'RQ_SWIFT',
				'RQ_BIC'
			);
		}

		return self::$rqFiltrableFields;
	}

	public static function getAllowedRqFieldCountries()
	{
		return array_keys(self::$rqFieldMapByCountry);
	}

	public function getFieldsTitles($countryId = 0)
	{
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

		Loc::loadMessages(Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/bankdetail.php');

		foreach (BankDetailTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($rqFields[$fieldName]))
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
				$fieldTitle = (isset($fieldInfo['title']) && !empty($fieldInfo['title'])) ? $fieldInfo['title'] : GetMessage('CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_FIELD');
				$result[$fieldName] = is_string($fieldTitle) ? $fieldTitle : '';
			}
		}

		return $result;
	}

	public function getFormFieldsTypes()
	{
		return array(
			'RQ_BANK_ADDR' => 'textarea',
			'COMMENTS' => 'textarea'
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
		foreach (BankDetailTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($fieldInfo['reference']))
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

		return $result;
	}

	public function getFormFieldsInfoByCountry($countryId)
	{
		$result = array();

		$countryId = (int)$countryId;
		if ($countryId <= 0 || !isset(self::$rqFieldMapByCountry[$countryId]))
			$countryId = 122;    // US by default

		$fieldsInfo = $this->getFormFieldsInfo($countryId);
		$fieldsByCountry = array();
		if (is_array(self::$rqFieldMapByCountry[$countryId]))
			$fieldsByCountry = self::$rqFieldMapByCountry[$countryId];

		$result['NAME'] = $fieldsInfo['NAME'];
		foreach ($fieldsByCountry as $fieldName)
		{
			if (isset($fieldsInfo[$fieldName]))
				$result[$fieldName] = $fieldsInfo[$fieldName];
		}
		$result['COMMENTS'] = $fieldsInfo['COMMENTS'];

		return $result;
	}

	public function getRqFieldByCountry()
	{
		return self::$rqFieldMapByCountry;
	}

	public function getRqFieldsCountryMap()
	{
		if (self::$rqFieldCountryMap === null)
		{
			$map = array();
			foreach (self::$rqFieldMapByCountry as $countryId => $fieldList)
			{
				foreach ($fieldList as $fieldName)
				{
					if (!isset($map[$fieldName]) || !is_array($map[$fieldName]))
						$map[$fieldName] = array();
					if (!in_array($countryId, $map[$fieldName], true))
						$map[$fieldName][] = $countryId;
				}
			}
			self::$rqFieldCountryMap = $map;
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
						Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/bankdetail.php',
						$langId
					);
					foreach ($titleMap as $fieldName => &$titlesByCountry)
					{
						if (isset($titlesByCountry[$countryId]))
						{
							$messageId = 'CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_FIELD';
							$altMessageId = 'CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_'.strtoupper($langId).'_FIELD';
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

	public static function checkEntityType($entityTypeId)
	{
		$entityTypeId = intval($entityTypeId);

		if ($entityTypeId !== \CCrmOwnerType::Requisite)
			return false;

		return true;
	}

	public function validateEntityExists($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = $this->getRequisite();
			if (!$requisite->exists($entityId))
				return false;
		}
		else
		{
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

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = $this->getRequisite();
			if (!$requisite->checkReadPermission($entityId))
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

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = $this->getRequisite();
			if (!$requisite->checkUpdatePermission($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function prepareViewData($fields, $fieldsInView = array(), $options = array())
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

		if (!is_array($fieldsInView))
			$fieldsInView = array();

		$result = array(
			'title' => '',
			'fields' => array()
		);

		$fieldsInfo = $this->getFormFieldsInfo();

		foreach ($fields as $fieldName => $fieldValue)
		{
			if ($fieldValue instanceof Main\Type\DateTime)
				$fieldValue = $fieldValue->toString();

			if ($fieldName === 'NAME')
			{
				$result['title'] = $fieldValue;
			}
			else
			{
				if (isset($fieldsInfo[$fieldName])
					&& (empty($fieldsInView) || in_array($fieldName, $fieldsInView, true)))
				{
					$fieldInfo = $fieldsInfo[$fieldName];
					$textValue = strval($fieldValue);

					$resultItem = array(
						'name' => $fieldName,
						'title' => $fieldInfo['title'],
						'type' => $fieldInfo['type'],
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

		return $result;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkCreatePermissionOwnerEntity($r['ENTITY_TYPE_ID']);
		}

		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkUpdatePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		if(intval($entityTypeID)<=0 && intval($entityID) <= 0)
		{
			return EntityRequisite::checkReadPermissionOwnerEntity();
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function getOwnerEntityById($id)
	{
		$result = array();

		if ($id <= 0)
			return array();

		$row = BankDetailTable::getList(array(
				'filter' => array('=ID' => $id),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
				'limit' => 1
		));

		$r = $row->fetch();

		$result['ENTITY_TYPE_ID'] = isset($r['ENTITY_TYPE_ID']) ? (int)$r['ENTITY_TYPE_ID'] : 0;
		$result['ENTITY_ID'] = isset($r['ENTITY_ID']) ? (int)$r['ENTITY_ID'] : 0;

		return $result;
	}

	/**
	 * Parse form data from specified source
	 * @param array $formData Data source
	 * @return array
	 */
	public static function parseFormData(array $formData)
	{
		$result = array();

		if (is_array($formData) && !empty($formData))
		{
			foreach ($formData as $pseudoId => $formFields)
			{
				$fields = array();
				$fieldNames = array_merge(
					array('ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
					self::$rqFields,
					array('COMMENTS')
				);
				foreach ($fieldNames as $fieldName)
				{
					if (isset($formData[$fieldName]))
					{
						if ($fieldName === 'ENTITY_TYPE_ID'
							|| $fieldName === 'ENTITY_ID'
							|| $fieldName === 'COUNTRY_ID')
						{
							$fields[$fieldName] = (int)$formData[$fieldName];
						}
						else
						{
							$fields[$fieldName] = trim(strval($formData[$fieldName]));
						}
					}
				}
				foreach ($fields as $fieldName => $fieldValue)
					$result[$fieldName] = $fieldValue;
			}
		}

		return $result;
	}

	public static function getDuplicateCriterionFieldsMap()
	{
		if (self::$duplicateCriterionFieldsMap === null)
		{
			self::$duplicateCriterionFieldsMap = array(
				1 => array(        // ru
					'RQ_ACC_NUM'
				),
				4 => array(        // by
					'RQ_ACC_NUM'
				),
				6 => array(        // kz
					'RQ_IIK'
				),
				14 => array(       // ua
					'RQ_ACC_NUM'
				),
				46 => array(       // de
					'RQ_ACC_NUM',
					'RQ_IBAN'
				),
				122 => array(      // us
					'RQ_ACC_NUM',
					'RQ_IBAN'
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
		$rqEntityTypeId = \CCrmOwnerType::Requisite;

		$Ids = array_keys($entityInfos);
		$IdsSql = implode(',', $Ids);
		$sql = "SELECT B2.ENTITY_ID, B1.{$typeNameSql}, B2.CNT".PHP_EOL.
			"FROM b_crm_bank_detail B1".PHP_EOL.
			"  INNER JOIN (".PHP_EOL.
			"    SELECT R0.ENTITY_ID AS ENTITY_ID, MIN(B0.ID) AS MIN_ID, COUNT(1) AS CNT".PHP_EOL.
			"    FROM b_crm_bank_detail B0".PHP_EOL.
			"      INNER JOIN b_crm_requisite R0 ON B0.ENTITY_TYPE_ID = {$rqEntityTypeId} AND B0.ENTITY_ID = R0.ID".PHP_EOL.
			"      INNER JOIN b_crm_preset P0 ON R0.PRESET_ID = P0.ID AND P0.COUNTRY_ID = {$countryId}".PHP_EOL.
			"    WHERE R0.ENTITY_ID IN ({$IdsSql}) AND R0.ENTITY_TYPE_ID = {$entityTypeId}".PHP_EOL.
			"      AND B0.{$typeNameSql} IS NOT NULL AND B0.{$typeNameSql} != ''".PHP_EOL.
			"    GROUP BY R0.ENTITY_TYPE_ID, R0.ENTITY_ID".PHP_EOL.
			"  ) B2 ON B1.ID = B2.MIN_ID".PHP_EOL;
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
	 * Unbind from seed entity and bind to target.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $seedBankDetailId Seed bank detail ID.
	 * @param int $targEntityId Target entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public static function rebindBankDetail($entityTypeId, $targEntityId, $seedBankDetailId)
	{
		if (!self::checkEntityType($entityTypeId))
		{
			throw new Main\ArgumentException(GetMessage('CRM_BANKDETAIL_ERR_INVALID_ENTITY_TYPE'), 'entityTypeId');
		}

		$bankDetail = self::getSingleInstance();
		$res = $bankDetail->getList(
			array(
				'select' => array('ID'),
				'filter' => array('ID' => $seedBankDetailId))
		);
		while($fields = $res->Fetch())
		{
			$bankDetail->update($fields['ID'], array('ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $targEntityId));
		}
	}

	/**
	 * Returns the bank detail of the specified owner entities.
	 * @param int $entityTypeId Entity type ID (Requisite for example).
	 * @param array $ownerIds List of owners IDs.
	 * @param array $ownerList List of owners (It is used to obtain the PRESET_COUNTRY_ID field values as an
	 *                         alternative to the COUNTRY_ID field of bank details).
	 *
	 * @return array Bank details structured as hierarchy $result[$requisiteId][$bankDetailId]...
	 */
	public static function getByOwners($entityTypeId, $ownerIds, $ownerList = array())
	{
		$result = array();

		if (self::checkEntityType($entityTypeId) && is_array($ownerIds) && !empty($ownerIds))
		{
			// load bank detail fields map
			$countryMap = array();
			$bankDetailFieldsMap = array();
			$bankDetail = self::getSingleInstance();
			$selectMap = array('ID' => true, 'ENTITY_ID' => true, 'COUNTRY_ID' => true);
			$res = $bankDetail->getList(
				array(
					'order' => array('ENTITY_ID', 'SORT', 'ID'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => $entityTypeId,
						'@ENTITY_ID' => $ownerIds
					),
					'select' => array('ID', 'ENTITY_ID', 'COUNTRY_ID')
				)
			);
			while ($row = $res->fetch())
			{
				$bankDetailId = (int)$row['ID'];
				$ownerId = (int)$row['ENTITY_ID'];
				$countryId = (int)$row['COUNTRY_ID'];
				if ($countryId <= 0 && $ownerId > 0 && is_array($ownerList[$ownerId])
					&& isset($ownerList[$ownerId]['PRESET_COUNTRY_ID']))
				{
					$countryId = (int)$ownerList[$ownerId]['PRESET_COUNTRY_ID'];
				}
				if ($bankDetailId > 0 && $ownerId > 0 && $countryId > 0)
				{
					if (!isset($result[$ownerId][$bankDetailId]))
						$result[$ownerId][$bankDetailId] = array(
							'ID' => $bankDetailId,
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'ENTITY_ID' => $ownerId,
							'COUNTRY_ID' => $countryId
						);
					if (!isset($countryMap[$countryId]))
						$countryMap[$countryId] = array();
				}
			}
			foreach ($bankDetail->getRqFieldByCountry() as $countryId => $fields)
			{
				$fields[] = 'COMMENTS';
				if (isset($countryMap[$countryId]))
				{
					foreach ($fields as $fieldName)
					{
						if (!isset($selectMap[$fieldName]))
							$selectMap[$fieldName] = true;
					}
					if (!isset($bankDetailFieldsMap[$countryId]))
						$bankDetailFieldsMap[$countryId] = $fields;
				}
			}
			unset($countryMap);

			// load bank details
			$bankDetailBasicFields = array_keys(self::getBasicFieldsInfo());
			foreach ($bankDetailBasicFields as $fieldName)
			{
				if (!isset($selectMap[$fieldName]))
					$selectMap[$fieldName] = true;
			}
			$res = $bankDetail->getList(
				array(
					'order' => array('ENTITY_ID', 'SORT', 'ID'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => $entityTypeId,
						'@ENTITY_ID' => $ownerIds
					),
					'select' => array_keys($selectMap)
				)
			);
			while ($row = $res->fetch())
			{
				$ownerId = (int)$row['ENTITY_ID'];
				$bankDetailId = (int)$row['ID'];
				if (is_array($result[$ownerId][$bankDetailId]))
				{
					foreach ($bankDetailBasicFields as $fieldName)
					{
						if (!isset($result[$ownerId][$bankDetailId][$fieldName]))
							$result[$ownerId][$bankDetailId][$fieldName] = $row[$fieldName];
					}
					foreach ($bankDetailFieldsMap[$result[$ownerId][$bankDetailId]['COUNTRY_ID']] as $fieldName)
						$result[$ownerId][$bankDetailId][$fieldName] = $row[$fieldName];
				}
			}
		}

		return $result;
	}

	/**
	 * Unbind banking detail from old entity of one type and bind them to new entity of another type.
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
			"UPDATE b_crm_bank_detail SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} 
					WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
	}
}
