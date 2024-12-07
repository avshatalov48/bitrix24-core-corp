<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Attribute\FieldOrigin;
use Bitrix\Crm\Entity\BankDetailValidator;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Localization\Translation;
use Bitrix\Main\Text\Encoding;
use CCrmEntitySelectorHelper;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

class EntityBankDetail
{
	const ERR_INVALID_ENTITY_TYPE   = 201;
	const ERR_INVALID_ENTITY_ID     = 202;
	const ERR_ON_DELETE             = 203;
	const ERR_NOTHING_TO_DELETE     = 204;
	const ERR_FIELD_LENGTH_MIN      = 205;
	const ERR_FIELD_LENGTH_MAX      = 206;
	const ERR_FIELD_REQUIRED        = 207;

	private static $singleInstance = null;

	private static $FIELD_INFOS = null;

	private static $rqFields = array(
		'RQ_BANK_NAME',
		'RQ_BANK_ADDR',
		'RQ_BANK_CODE',
		'RQ_AGENCY_NAME',
		'RQ_BANK_ROUTE_NUM',
		'RQ_BIK',
		'RQ_MFO',
		'RQ_ACC_NAME',
		'RQ_ACC_NUM',
		'RQ_IIK',
		'RQ_ACC_CURRENCY',
		'RQ_COR_ACC_NUM',
		'RQ_ACC_TYPE',
		'RQ_IBAN',
		'RQ_SWIFT',
		'RQ_BIC',
		'RQ_CODEB',
		'RQ_CODEG',
		'RQ_RIB',
	);
	private static $rqFiltrableFields = null;
	private static $rqFieldMapByCountry = [
		// RU
		1 => [
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT',
		],
		// BY
		4 => [
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_BIC',
			'RQ_ACC_CURRENCY',
			'RQ_SWIFT',
			'RQ_BANK_ADDR',
		],
		// KZ
		6 => [
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_IIK',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT',
		],
		// UA
		14 => [
			'RQ_BANK_NAME',
			'RQ_MFO',
			'RQ_ACC_NUM',
			'RQ_IBAN',
		],
		// BR
		34 => [
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_CODE',
			'RQ_AGENCY_NAME',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_ACC_TYPE',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
		],
		// DE
		46 => [
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
		],
		// CO
		77 => [
			'RQ_BANK_NAME',
			'RQ_IIK',
			//'RQ_ACC_TYPE',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
		],
		// PL
		110 => [
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
		],
		// FR
		132 => [
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
			'RQ_CODEB',
			'RQ_CODEG',
			'RQ_RIB',
		],
		// US
		122 => [
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC',
		],
	];
	private static $rqFieldCountryMap = null;
	private static $rqFieldTitleMap = null;

	private static $rqFieldValidationMap = null;

	private static $requisite = null;

	private static $duplicateCriterionFieldsMap = null;

	private static $phrasesMap = [];

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

	public static function checkCountryId(int $countryId): bool
	{
		return in_array($countryId, static::getAllowedRqFieldCountries(), true);
	}

	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = [
				'ID' => [
						'TYPE' => 'integer',
						'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly]
				],
				'ENTITY_TYPE_ID' => [
						'TYPE' => 'integer',
						'ATTRIBUTES' => [
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable,
								\CCrmFieldInfoAttr::Hidden
						]
				],
				'ENTITY_ID' => [
						'TYPE' => 'integer',
						'ATTRIBUTES' => [
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable
						]
				],
				'COUNTRY_ID' => ['TYPE' => 'integer'],
				'DATE_CREATE' => [
						'TYPE' => 'datetime',
						'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly]
				],
				'DATE_MODIFY' => [
						'TYPE' => 'datetime',
						'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly]
				],
				'CREATED_BY_ID' => [
						'TYPE' => 'user',
						'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly]
				],
				'MODIFY_BY_ID' => [
						'TYPE' => 'user',
						'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly]
				],
				'NAME' => ['TYPE' => 'string'],
				'CODE' => ['TYPE' => 'string'],
				'XML_ID' => ['TYPE' => 'string'],
				'ACTIVE' => ['TYPE' => 'char'],
				'SORT' => ['TYPE' => 'integer'],
				'RQ_BANK_NAME' => ['TYPE' => 'string'],
				'RQ_BANK_ADDR' => ['TYPE' => 'string'],
				'RQ_BANK_CODE' => ['TYPE' => 'string'],
				'RQ_AGENCY_NAME' => ['TYPE' => 'string'],
				'RQ_BANK_ROUTE_NUM' => ['TYPE' => 'string'],
				'RQ_BIK' => ['TYPE' => 'string'],
				'RQ_MFO' => ['TYPE' => 'string'],
				'RQ_ACC_NAME' => ['TYPE' => 'string'],
				'RQ_ACC_NUM' => ['TYPE' => 'string'],
				'RQ_ACC_TYPE' => ['TYPE' => 'string'],
				'RQ_IIK' => ['TYPE' => 'string'],
				'RQ_ACC_CURRENCY' => ['TYPE' => 'string'],
				'RQ_COR_ACC_NUM' => ['TYPE' => 'string'],
				'RQ_IBAN' => ['TYPE' => 'string'],
				'RQ_SWIFT' => ['TYPE' => 'string'],
				'RQ_BIC' => ['TYPE' => 'string'],
				'RQ_CODEB' => ['TYPE' => 'string'],
				'RQ_CODEG' => ['TYPE' => 'string'],
				'RQ_RIB' => ['TYPE' => 'string'],
				'COMMENTS' => ['TYPE' => 'string'],
				'ORIGINATOR_ID' => ['TYPE' => 'string']
			];
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

	public function exists(int $id, int $entityTypeId = 0, int $entityId = 0): bool
	{
		$filter = ['=ID' => $id];

		if ($entityTypeId !== 0)
		{
			if ($entityTypeId < 0)
			{
				$entityTypeId = 0;
			}
			$filter['=ENTITY_TYPE_ID'] = $entityTypeId;

			if ($entityId !== 0)
			{
				if ($entityId < 0)
				{
					$entityId = 0;
				}
				$filter['=ENTITY_ID'] = $entityId;
			}
		}

		$res = $this->getList(
			[
				'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
				'filter' => $filter,
				'select' => ['ID'],
				'limit' => 1
			]
		);
		$row = $res->fetch();
		if (is_array($row))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @return int
	 */
	public function getCountryIdByOwnerEntity($entityTypeId, $entityId)
	{
		$countryId = 0;
		$entityId = (int)$entityId;

		if ($entityTypeId === CCrmOwnerType::Requisite && $entityId > 0)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$countryId = $requisite->getCountryIdByRequisiteId($entityId);
		}

		return $countryId;
	}

	public function getPresetIdByOwnerEntity(int $entityTypeId, int $entityId): int
	{
		$presetId = 0;

		if ($entityTypeId === CCrmOwnerType::Requisite && $entityId > 0)
		{
			$presetId = EntityRequisite::getSingleInstance()->getPresetIdByRequisiteId($entityId);
		}

		return $presetId;
	}

	public function getCountryIdByFields(int $bankDetailId, array $fields): int
	{
		$countryId = 0;
		$entityTypeId = CCrmOwnerType::Undefined;
		$entityId = 0;
		$isUpdate = ($bankDetailId > 0);

		if (isset($fields['COUNTRY_ID']))
		{
			$countryId = (int)$fields['COUNTRY_ID'];
		}

		if ($isUpdate && $countryId <= 0)
		{
			if (isset($fields['ENTITY_TYPE_ID']))
			{
				$entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
			}
			if (isset($fields['ENTITY_ID']))
			{
				$entityId = (int)$fields['ENTITY_ID'];
			}
			$countryId = $this->getCountryIdByOwnerEntity($entityTypeId, $entityId);
		}

		if ($countryId < 0)
		{
			$countryId = 0;
		}

		return $countryId;
	}

	public function getRqFieldValidationMap()
	{
		if (self::$rqFieldValidationMap === null)
		{
			self::$rqFieldValidationMap = [
				'RQ_BANK_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_BANK_ADDR' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_BANK_CODE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_AGENCY_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_BANK_ROUTE_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 9]]],
				'RQ_BIK' => [['type' => 'length', 'params' => ['min' => null, 'max' => 11]]],
				'RQ_MFO' => [['type' => 'length', 'params' => ['min' => null, 'max' => 6]]],
				'RQ_ACC_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_ACC_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 34]]],
				'RQ_ACC_TYPE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_IIK' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_ACC_CURRENCY' => [['type' => 'length', 'params' => ['min' => null, 'max' => 100]]],
				'RQ_COR_ACC_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 34]]],
				'RQ_IBAN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 34]]],
				'RQ_SWIFT' => [['type' => 'length', 'params' => ['min' => null, 'max' => 11]]],
				'RQ_BIC' => [['type' => 'length', 'params' => ['min' => null, 'max' => 11]]],
				'RQ_CODEB' => [['type' => 'length', 'params' => ['min' => null, 'max' => 5]]],
				'RQ_CODEG' => [['type' => 'length', 'params' => ['min' => null, 'max' => 5]]],
				'RQ_RIB' => [['type' => 'length', 'params' => ['min' => null, 'max' => 2]]],
			];
		}

		return self::$rqFieldValidationMap;
	}

	public function getPresetIdByFields(int $bankDetailId, array $fields): int
	{
		$entityTypeId = (int)($fields['ENTITY_TYPE_ID'] ?? 0);
		$entityId = (int)($fields['ENTITY_ID'] ?? 0);

		$presetId = $this->getPresetIdByOwnerEntity($entityTypeId, $entityId);

		if ($presetId <= 0 && $bankDetailId > 0)
		{
			$ownerInfo = static::getOwnerEntityById($bankDetailId);
			$presetId = $this->getPresetIdByOwnerEntity(
				$ownerInfo['ENTITY_TYPE_ID'] ?? 0,
				$ownerInfo['ENTITY_ID'] ?? 0
			);
		}

		return $presetId;
	}

	public function checkRequiredFieldsBeforeSave(int $bankDetailId, array $fields, array $options = []): Main\Result
	{
		$result = new Main\Result();

		$isUpdate = ($bankDetailId > 0);
		$enableUserFieldCheck = !(
			isset($options['DISABLE_USER_FIELD_CHECK'])
			&& $options['DISABLE_USER_FIELD_CHECK'] === true
		);
		$enableRequiredUserFieldCheck = !(
			isset($options['DISABLE_REQUIRED_USER_FIELD_CHECK'])
			&& $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] === true
		);
		if ($enableUserFieldCheck && $enableRequiredUserFieldCheck)
		{
			$fieldCheckOptions =
				(
					isset($options['FIELD_CHECK_OPTIONS'])
					&& is_array($options['FIELD_CHECK_OPTIONS'])
				)
					? $options['FIELD_CHECK_OPTIONS']
					: []
			;

			$requiredFields = FieldAttributeManager::getRequiredFields(
				CCrmOwnerType::BankDetail,
				$bankDetailId,
				$fields,
				FieldOrigin::UNDEFINED,
				$fieldCheckOptions
			);

			$checkErrors = [];

			$requiredSystemFields = $requiredFields[FieldOrigin::SYSTEM] ?? [];
			if (!empty($requiredSystemFields))
			{
				$validator = new BankDetailValidator(
					$bankDetailId,
					$fields,
					$this->getCountryIdByFields($bankDetailId, $fields)
				);
				$validationErrors = [];
				foreach($requiredSystemFields as $fieldName)
				{
					if (!$isUpdate || array_key_exists($fieldName, $fields))
					{
						$validator->checkFieldPresence($fieldName, $validationErrors);
					}
				}

				if (!empty($validationErrors))
				{
					foreach ($validationErrors as $error)
					{
						$result->addError(new Main\Error($error['text'], static::ERR_FIELD_REQUIRED));
						$checkErrors[] = new Main\Error($error['text'], $error['id']);
					}
				}
			}

			if (!empty($checkErrors))
			{
				$result->setData(['CHECK_ERRORS' => $checkErrors]);
			}
		}

		return $result;
	}

	public function checkRqFieldsBeforeSave($bankDetailId, $fields)
	{
		$result = new Main\Result();

		$countryId = $this->getCountryIdByFields($bankDetailId, $fields);

		$validationMap = $this->getRqFieldValidationMap();
		$titleMap = $this->getRqFieldTitleMap();

		foreach ($fields as $fieldName => $fieldValue)
		{
			if (isset($validationMap[$fieldName]) && is_array($validationMap[$fieldName]))
			{
				foreach ($validationMap[$fieldName] as $validateInfo)
				{
					if (isset($validateInfo['type'])
						&& is_array($validateInfo['params']))
					{
						if ($validateInfo['type'] === 'length')
						{
							$params = $validateInfo['params'];
							$strValue = strval($fieldValue);
							if ($strValue !== '')
							{
								$title = ($countryId > 0 && isset($titleMap[$fieldName][$countryId])) ?
									$titleMap[$fieldName][$countryId] : $fieldName;
								$length = mb_strlen($strValue);
								if (isset($params['min']) && $params['min'] > 0)
								{
									if ($length < $params['min'])
									{
										$message = Loc::getMessage(
											'CRM_BANK_DETAIL_FIELD_VALIDATOR_LENGTH_MIN',
											[
												'#FIELD_TITLE#' => $title,
												'#MIN_LENGTH#' => $params['min']
											]
										);
										$result->addError(new Main\Error($message, self::ERR_FIELD_LENGTH_MIN));
									}
								}

								if (isset($params['max']) && $params['max'] > 0)
								{
									if ($length > $params['max'])
									{
										$message = Loc::getMessage(
											'CRM_BANK_DETAIL_FIELD_VALIDATOR_LENGTH_MAX',
											[
												'#FIELD_TITLE#' => $title,
												'#MAX_LENGTH#' => $params['max']
											]
										);
										$result->addError(new Main\Error($message, self::ERR_FIELD_LENGTH_MAX));
									}
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public function checkBeforeAdd($fields, $options = [])
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$result = $this->checkRequiredFieldsBeforeSave(0, $fields, $options);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->checkRqFieldsBeforeSave(0, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

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
		$entity = BankDetailTable::getEntity();
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

		$result = $this->checkRequiredFieldsBeforeSave(0, $fields, $options);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->checkRqFieldsBeforeSave(0, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = BankDetailTable::add($fields);
		$id = $result->isSuccess() ? (int)$result->getId() : 0;
		if ($id > 0)
		{
			$entityTypeId =
				isset($fields['ENTITY_TYPE_ID'])
					? (int)$fields['ENTITY_TYPE_ID']
					: CCrmOwnerType::Undefined
			;
			$entityId = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
			DuplicateBankDetailCriterion::registerByParent($entityTypeId, $entityId);
			if ($entityTypeId === CCrmOwnerType::Requisite)
			{
				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					CCrmOwnerType::Requisite,
					$entityId,
					[FieldCategory::BANK_DETAIL]
				);
				//endregion Register volatile duplicate criterion fields

				CCrmEntitySelectorHelper::clearPrepareRequisiteDataCacheByEntity(CCrmOwnerType::Requisite, $entityId);
			}
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

	public function checkBeforeUpdate($id, $fields, array $options = [])
	{
		$id = (int)$id;
		unset($fields['ID'], $fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$result = $this->checkRequiredFieldsBeforeSave($id, $fields, $options);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->checkRqFieldsBeforeSave($id, $fields);
		if (!$result->isSuccess())
		{
			return $result;
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
			'ENTITY_TYPE_ID' => CCrmOwnerType::Undefined,
			'ENTITY_ID' => 0
		);
		$parentInfoBeforeUpdate = self::getOwnerEntityById($id);
		if ($parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === CCrmOwnerType::Requisite
			&& $parentInfoBeforeUpdate['ENTITY_ID'] > 0)
		{
			$parentInfoAfterUpdate = $parentInfoBeforeUpdate;
			$entityBeforeUpdate = EntityRequisite::getOwnerEntityById($parentInfoBeforeUpdate['ENTITY_ID']);
		}
		if (isset($fields['ENTITY_TYPE_ID']))
			$parentInfoAfterUpdate['ENTITY_TYPE_ID'] = (int)$fields['ENTITY_TYPE_ID'];
		if (isset($fields['ENTITY_ID']))
			$parentInfoAfterUpdate['ENTITY_ID'] = (int)$fields['ENTITY_ID'];
		if ($parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === CCrmOwnerType::Requisite
			&& $parentInfoBeforeUpdate['ENTITY_ID'] > 0
			&& $parentInfoAfterUpdate['ENTITY_TYPE_ID'] === CCrmOwnerType::Requisite
			&& $parentInfoAfterUpdate['ENTITY_ID'] > 0
			&& $parentInfoBeforeUpdate['ENTITY_TYPE_ID'] === $parentInfoAfterUpdate['ENTITY_TYPE_ID']
			&& $parentInfoBeforeUpdate['ENTITY_ID'] === $parentInfoAfterUpdate['ENTITY_ID'])
		{
			$entityAfterUpdate = $entityBeforeUpdate;
		}
		else
		{
			$entityAfterUpdate = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Undefined,
				'ENTITY_ID' => 0
			);
			if ($parentInfoAfterUpdate['ENTITY_TYPE_ID'] === CCrmOwnerType::Requisite
				&& $parentInfoAfterUpdate['ENTITY_ID'] > 0)
			{
				$entityAfterUpdate = EntityRequisite::getOwnerEntityById($parentInfoAfterUpdate['ENTITY_ID']);
			}
		}
		unset($parentInfoAfterUpdate);
		$entityTypeIdModified = $entityIdModified = false;
		$entityTypeId = $entityAfterUpdate['ENTITY_TYPE_ID'];
		if (
			CCrmOwnerType::IsDefined($entityTypeId)
			&& CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
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
		$entity = BankDetailTable::getEntity();
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

		$result = $this->checkRequiredFieldsBeforeSave($id, $fields, $options);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->checkRqFieldsBeforeSave($id, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = BankDetailTable::update($id, $fields);
		if ($result->isSuccess())
		{
			if ($entityTypeIdModified || $entityIdModified)
			{
				DuplicateBankDetailCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'],
					$entityBeforeUpdate['ENTITY_ID']
				);
				DuplicateBankDetailCriterion::unregister($entityTypeId, $entityId);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					$entityBeforeUpdate['ENTITY_TYPE_ID'],
					$entityBeforeUpdate['ENTITY_ID'],
					[FieldCategory::BANK_DETAIL]
				);
				DuplicateVolatileCriterion::register(
					$entityTypeId,
					$entityId,
					[FieldCategory::BANK_DETAIL]
				);
				//endregion Register volatile duplicate criterion fields
			}

			if (isset($fields['ENTITY_TYPE_ID']) && isset($fields['ENTITY_ID']))
			{
				$entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
				$entityId = (int)$fields['ENTITY_ID'];
				if ($entityTypeId === CCrmOwnerType::Requisite && $entityId > 0)
				{
					DuplicateBankDetailCriterion::registerByParent($entityTypeId, $entityId);

					//region Register volatile duplicate criterion fields
					DuplicateVolatileCriterion::register(
						CCrmOwnerType::Requisite,
						$entityId,
						[FieldCategory::BANK_DETAIL]
					);
					//endregion Register volatile duplicate criterion fields
				}
			}
			else
			{
				DuplicateBankDetailCriterion::registerByBankDetail($id);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					CCrmOwnerType::BankDetail,
					$id,
					[FieldCategory::BANK_DETAIL]
				);
				//endregion Register volatile duplicate criterion fields
			}

			CCrmEntitySelectorHelper::clearPrepareRequisiteDataCacheByEntity(
				$parentInfoBeforeUpdate['ENTITY_TYPE_ID'],
				$parentInfoBeforeUpdate['ENTITY_ID'])
			;
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
			'ENTITY_TYPE_ID' => CCrmOwnerType::Undefined,
			'ENTITY_ID' => 0
		);
		$parentInfo = self::getOwnerEntityById($id);
		if ($parentInfo['ENTITY_TYPE_ID'] === CCrmOwnerType::Requisite)
		{
			$entityInfo = EntityRequisite::getOwnerEntityById($parentInfo['ENTITY_ID']);
		}

		$result = BankDetailTable::delete($id);
		if ($result->isSuccess()
			&& CCrmOwnerType::IsDefined($entityInfo['ENTITY_TYPE_ID']) && $entityInfo['ENTITY_ID'] > 0)
		{
			DuplicateBankDetailCriterion::registerByEntity($entityInfo['ENTITY_TYPE_ID'], $entityInfo['ENTITY_ID']);

			//region Register volatile duplicate criterion fields
			DuplicateVolatileCriterion::register(
				$entityInfo['ENTITY_TYPE_ID'],
				$entityInfo['ENTITY_ID'],
				[FieldCategory::BANK_DETAIL]
			);
			//endregion Register volatile duplicate criterion fields
		}

		//region Send event
		if ($result->isSuccess())
		{
			CCrmEntitySelectorHelper::clearPrepareRequisiteDataCacheByEntity(
				$parentInfo['ENTITY_TYPE_ID'],
				$parentInfo['ENTITY_ID'])
			;

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
				'RQ_BIC',
				'RQ_CODEB',
				'RQ_CODEG',
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
		if (!static::checkCountryId($countryId))
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
			'AUTOCOMPLETE' => 'requisite_autocomplete',
			'RQ_BANK_ADDR' => 'textarea',
			'COMMENTS' => 'textarea'
		);
	}

	public function getFormFieldsInfo($countryId = 0)
	{
		$result = [];

		$formTypes = $this->getFormFieldsTypes();
		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
		{
			$rqFields[$rqFieldName] = true;
		}
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
		$result = [];

		$countryId = (int)$countryId;
		if ($countryId <= 0 || !isset(self::$rqFieldMapByCountry[$countryId]))
		{
			$countryId = 122;    // US by default
		}

		$fieldsInfo = $this->getFormFieldsInfo($countryId);
		$fieldsByCountry = [];
		if (is_array(self::$rqFieldMapByCountry[$countryId]))
		{
			$fieldsByCountry = self::$rqFieldMapByCountry[$countryId];
		}

		$result['NAME'] = $fieldsInfo['NAME'];
		foreach ($fieldsByCountry as $fieldName)
		{
			if (isset($fieldsInfo[$fieldName]))
			{
				$result[$fieldName] = $fieldsInfo[$fieldName];
			}
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

	protected function loadPhrases(int $countryId): array
	{
		$phrases = [];

		if (static::checkCountryId($countryId))
		{
			$countryCode = EntityPreset::getCountryCodeById($countryId);
			$countryCodeLower = mb_strtolower($countryCode);
			$phrasesConfig = [];
			$filePath= Main\IO\Path::normalize(
				Main\Application::getDocumentRoot().
				"/bitrix/modules/crm/lib/requisite/phrases/bankdetail_$countryCodeLower.php"
			);
			if (file_exists($filePath))
			{
				include($filePath);
			}
			if (isset($phrasesConfig['encoding'])
				&& is_string($phrasesConfig['encoding'])
				&& $phrasesConfig['encoding'] !== ''
				&& is_array($phrasesConfig['phrases'])
				&& !empty($phrasesConfig['phrases']))
			{
				$phrases = $phrasesConfig['phrases'];
				$sourceEncoding = mb_strtolower($phrasesConfig['encoding']);
				$targetEncoding = Translation::getCurrentEncoding();
				$needConvertEncoding = ($sourceEncoding !== $targetEncoding);
				foreach ($phrases as $phraseId => $phrase)
				{
					if (is_string($phrase))
					{
						if ($needConvertEncoding && $phrase !== '')
						{
							$convertedValue = Encoding::convertEncoding(
								$phrase,
								$sourceEncoding,
								$targetEncoding
							);
							$phrases[$phraseId] =
								is_string($convertedValue) ? $convertedValue : $phraseId;
						}
					}
					else
					{
						$phrases[$phraseId] = null;
					}
				}
			}
		}

		return $phrases;
	}

	protected function getPhrase(string $phraseId, int $countryId): ?string
	{
		$phrase = null;

		if ($phraseId !== '' && static::checkCountryId($countryId))
		{
			if (!isset(static::$phrasesMap[$countryId]))
			{
				static::$phrasesMap[$countryId] = static::loadPhrases($countryId);
			}
			if (isset(static::$phrasesMap[$countryId][$phraseId]))
			{
				$phrase = static::$phrasesMap[$countryId][$phraseId];
			}
		}

		return $phrase;
	}

	public function getDefaultSectionTitle(int $countryId): string
	{
		$title = '';

		$countryCode = EntityPreset::getCountryCodeById($countryId);
		$title = $this->getPhrase("CRM_BANK_DETAIL_SECTION_{$countryCode}_TITLE", $countryId);
		if ($title === null)
		{
			$title = '';
		}

		return $title;
	}

	public function getRqFieldTitleMap()
	{
		if (self::$rqFieldTitleMap === null)
		{
			$titleMap = array();
			$countryCodes = [];
			foreach ($this->getRqFieldsCountryMap() as $fieldName => $fieldCountryIds)
			{
				if (is_array($fieldCountryIds))
				{
					foreach ($fieldCountryIds as $countryId)
					{
						if (!isset($countryCodes[$countryId]))
						{
							$countryCodes[$countryId] = EntityPreset::getCountryCodeById($countryId);
						}
						$phraseId = "CRM_BANK_DETAIL_ENTITY_{$fieldName}_{$countryCodes[$countryId]}_FIELD";
						$phrase = static::getPhrase($phraseId, $countryId);
						$titleMap[$fieldName][$countryId] = ($phrase === null) ? '' : $phrase;
					}
				}
			}
			self::$rqFieldTitleMap = $titleMap;
		}

		return self::$rqFieldTitleMap;
	}

	public static function checkEntityType($entityTypeId)
	{
		$entityTypeId = intval($entityTypeId);

		if ($entityTypeId !== CCrmOwnerType::Requisite)
			return false;

		return true;
	}

	public function validateEntityExists($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityTypeId === CCrmOwnerType::Requisite)
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

		if ($entityTypeId === CCrmOwnerType::Requisite)
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

		if ($entityTypeId === CCrmOwnerType::Requisite)
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

		if ($entityTypeID === CCrmOwnerType::Requisite)
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

		if ($entityTypeID === CCrmOwnerType::Requisite)
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

		if ($entityTypeID === CCrmOwnerType::Requisite)
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

		if ($entityTypeID === CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function getOwnerEntityById($id)
	{
		$result = [
			'ENTITY_TYPE_ID' => 0,
			'ENTITY_ID' => 0,
		];

		if ($id <= 0)
		{
			return $result;
		}

		$res = BankDetailTable::getList(array(
				'filter' => array('=ID' => $id),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
				'limit' => 1
		));

		$row = $res->fetch();

		$result['ENTITY_TYPE_ID'] = (int)($row['ENTITY_TYPE_ID'] ?? 0);
		$result['ENTITY_ID'] = (int)($row['ENTITY_ID'] ?? 0);

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
					'RQ_ACC_NUM',
				),
				4 => array(        // by
					'RQ_ACC_NUM',
				),
				6 => array(        // kz
					'RQ_IIK',
				),
				14 => array(       // ua
					'RQ_ACC_NUM',
				),
				34 => array(       // br
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
				46 => array(       // de
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
				77 => array(      // co
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
				110 => array(      // pl
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
				132 => array(      // fr
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
				122 => array(      // us
					'RQ_ACC_NUM',
					'RQ_IBAN',
				),
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
		if (!($entityTypeId === CCrmOwnerType::Company || $entityTypeId === CCrmOwnerType::Contact))
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
		$rqEntityTypeId = CCrmOwnerType::Requisite;

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
			$bankDetail->update(
				$fields['ID'],
				['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $targEntityId],
				['DISABLE_REQUIRED_USER_FIELD_CHECK' => true]
			);
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
							'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
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
