<?php

namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Format\RequisiteAddressFormatter;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Requisite\Country;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Localization\Translation;
use Bitrix\Main\Result;
use Bitrix\Main\Text\Encoding;
use Bitrix\Location\Entity\Address;
use CCrmFieldInfoAttr;
use CCrmInstantEditorHelper;
use CCrmOwnerType;

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
	const ERR_FIELD_LENGTH_MIN             = 216;
	const ERR_FIELD_LENGTH_MAX             = 217;

	const CONFIG_TABLE_NAME = 'b_crm_requisite_cfg';

	const INN = 'RQ_INN'; //Individual Taxpayer Identification Number
	const IIN = 'RQ_IIN';
	const VAT_ID = 'RQ_VAT_ID';
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

	public const XML_ID_DEFAULT_PRESET_RU_COMPANY = '#CRM_REQUISITE_PRESET_DEF_RU_COMPANY#';
	public const XML_ID_DEFAULT_PRESET_RU_INDIVIDUAL = '#CRM_REQUISITE_PRESET_DEF_RU_INDIVIDUAL#';
	public const XML_ID_DEFAULT_PRESET_RU_PERSON = '#CRM_REQUISITE_PRESET_DEF_RU_PERSON#';

	private static $singleInstance = null;

	private static $fixedPresetList = null;
	private static $FIELD_INFOS = null;

	private static $allowedRqFieldCountryIds = array(1, 4, 6, 14, 34, 46, 77, 110, 122, 132);
	private static $rqFieldCountryMap = null;
	private static $rqFieldTitleMap = null;
	private static $userFieldTitles = null;

	private static $rqFieldValidationMap = null;

	private static $duplicateCriterionFieldsMap = null;

	private static $phrasesMap = [];

	private static $rqListFieldItemsMap = [];

	protected static $presetsWithAddressMap = null;

	protected static $countryAddressZoneMap = null;

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
					$fieldName.'_COUNTRY_CODE' => $fieldName.'.COUNTRY_CODE',
					$fieldName.'_LOC_ADDR_ID' => $fieldName.'.LOC_ADDR_ID'
				)
			);
		}
		unset($fieldName);

		// rewrite order
		if (isset($params['order']) && is_array($params['order']))
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
				[$fieldName,] = array_values($sqlWhere->MakeOperation($k));
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

	/**
	 * @param $presetId
	 * @return int
	 */
	public function getCountryIdByPresetId($presetId)
	{
		$countryId = 0;
		$presetId = (int)$presetId;

		if ($presetId > 0)
		{
			$preset = EntityPreset::getSingleInstance();
			$res = $preset->getList(
				[
					'filter' => array(
						'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
						'=ID' => $presetId
					),
					'select' => ['COUNTRY_ID'],
					'limit' => 1
				]
			);
			if (($row = $res->fetch()) && isset($row['COUNTRY_ID']))
			{
				$countryId = (int)$row['COUNTRY_ID'];
			}
		}

		return $countryId;
	}

	/**
	 * @param $requisiteId
	 * @return int
	 */
	public function getCountryIdByRequisiteId($requisiteId)
	{
		$countryId = 0;
		$requisiteId = (int)$requisiteId;

		if ($requisiteId > 0)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$res = $requisite->getList(
				[
					'filter' => array(
						'=ID' => $requisiteId
					),
					'select' => ['COUNTRY_ID' => 'PRESET.COUNTRY_ID'],
					'limit' => 1
				]
			);
			if (($row = $res->fetch()) && isset($row['COUNTRY_ID']))
			{
				$countryId = (int)$row['COUNTRY_ID'];
			}
		}

		return $countryId;
	}

	public function getRqFieldValidationMap()
	{
		if (self::$rqFieldValidationMap === null)
		{
			self::$rqFieldValidationMap = [
				'RQ_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_FIRST_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_LAST_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_SECOND_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_COMPANY_ID' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_COMPANY_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_COMPANY_FULL_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 300]]],
				'RQ_COMPANY_REG_DATE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_DIRECTOR' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_ACCOUNTANT' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_CEO_NAME' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_CEO_WORK_POS' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_CONTACT' => [['type' => 'length', 'params' => ['min' => null, 'max' => 150]]],
				'RQ_EMAIL' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_PHONE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_FAX' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_IDENT_TYPE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_IDENT_DOC' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_IDENT_DOC_SER' => [['type' => 'length', 'params' => ['min' => null, 'max' => 25]]],
				'RQ_IDENT_DOC_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 25]]],
				'RQ_IDENT_DOC_PERS_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 25]]],
				'RQ_IDENT_DOC_DATE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_IDENT_DOC_ISSUED_BY' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_IDENT_DOC_DEP_CODE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 25]]],
				'RQ_INN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 15]]],
				'RQ_KPP' => [['type' => 'length', 'params' => ['min' => null, 'max' => 9]]],
				'RQ_USRLE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_IFNS' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_OGRN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 13]]],
				'RQ_OGRNIP' => [['type' => 'length', 'params' => ['min' => null, 'max' => 15]]],
				'RQ_OKPO' => [['type' => 'length', 'params' => ['min' => null, 'max' => 12]]],
				'RQ_OKTMO' => [['type' => 'length', 'params' => ['min' => null, 'max' => 11]]],
				'RQ_OKVED' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_EDRPOU' => [['type' => 'length', 'params' => ['min' => null, 'max' => 10]]],
				'RQ_DRFO' => [['type' => 'length', 'params' => ['min' => null, 'max' => 10]]],
				'RQ_KBE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 2]]],
				'RQ_IIN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 12]]],
				'RQ_BIN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 12]]],
				'RQ_ST_CERT_SER' => [['type' => 'length', 'params' => ['min' => null, 'max' => 10]]],
				'RQ_ST_CERT_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 15]]],
				'RQ_ST_CERT_DATE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_VAT_ID' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_VAT_CERT_SER' => [['type' => 'length', 'params' => ['min' => null, 'max' => 10]]],
				'RQ_VAT_CERT_NUM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 15]]],
				'RQ_VAT_CERT_DATE' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_RESIDENCE_COUNTRY' => [['type' => 'length', 'params' => ['min' => null, 'max' => 128]]],
				'RQ_BASE_DOC' => [['type' => 'length', 'params' => ['min' => null, 'max' => 255]]],
				'RQ_REGON' => [['type' => 'length', 'params' => ['min' => null, 'max' => 9]]],
				'RQ_KRS' => [['type' => 'length', 'params' => ['min' => null, 'max' => 10]]],
				'RQ_PESEL' => [['type' => 'length', 'params' => ['min' => null, 'max' => 11]]],
				'RQ_LEGAL_FORM' => [['type' => 'length', 'params' => ['min' => null, 'max' => 80]]],
				'RQ_SIRET' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_SIREN' => [['type' => 'length', 'params' => ['min' => null, 'max' => 15]]],
				'RQ_CAPITAL' => [['type' => 'length', 'params' => ['min' => null, 'max' => 30]]],
				'RQ_RCS' => [['type' => 'length', 'params' => ['min' => null, 'max' => 50]]],
				'RQ_CNPJ' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_STATE_REG' => [['type' => 'length', 'params' => ['min' => null, 'max' => 25]]],
				'RQ_MNPL_REG' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_CPF' => [['type' => 'length', 'params' => ['min' => null, 'max' => 20]]],
				'RQ_SIGNATURE' => ['type' => 'file_type', 'params' => [ 'onlyImage' => true, ]],
				'RQ_STAMP' => ['type' => 'file_type', 'params' => [ 'onlyImage' => true, ]],
			];
		}

		return self::$rqFieldValidationMap;
	}

	public function checkRqFieldsBeforeSave($requisiteId, $fields)
	{
		$result = new Main\Result();

		$countryId = 0;
		$presetId = 0;
		$requisiteId = (int)$requisiteId;
		$isUpdate = ($requisiteId > 0);

		if (isset($fields['PRESET_ID']))
		{
			$presetId = (int)$fields['PRESET_ID'];
		}

		if ($presetId > 0)
		{
			$countryId = $this->getCountryIdByPresetId($presetId);
		}
		else if ($isUpdate)
		{
			$countryId = $this->getCountryIdByRequisiteId($requisiteId);
		}

		if ($countryId < 0)
		{
			$countryId = 0;
		}

		$validationMap = $this->getRqFieldValidationMap();
		$titleMap = $this->getRqFieldTitleMap();

		foreach ($fields as $fieldName => $fieldValue)
		{
			if (isset($validationMap[$fieldName]) && is_array($validationMap[$fieldName]))
			{
				foreach ($validationMap[$fieldName] as $validateInfo)
				{
					if (
						isset($validateInfo['type'])
						&& is_array($validateInfo['params'])
					)
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
											'CRM_REQUISITE_FIELD_VALIDATOR_LENGTH_MIN',
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
											'CRM_REQUISITE_FIELD_VALIDATOR_LENGTH_MAX',
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

	public function checkBeforeAdd($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$result = $this->checkRqFieldsBeforeSave(0, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

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

		$allowSetSystemFields = $options['ALLOW_SET_SYSTEM_FIELDS'] ?? false;
		if (!$allowSetSystemFields)
		{
			$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
			$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		}

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

		$addressOnly = false;
		if (isset($fields['ADDRESS_ONLY']) &&
			($fields['ADDRESS_ONLY'] === true || $fields['ADDRESS_ONLY'] === 'Y'))
		{
			$addressOnly = true;
		}
		if ($addressOnly)
		{
			$fields = $this->removeNonAddressFields($fields);
		}

		$result = $this->checkRqFieldsBeforeSave(0, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = RequisiteTable::add($fields);
		$id = $result->isSuccess() ? (int)$result->getId() : 0;
		if ($id > 0)
		{
			$entityTypeId = isset($fields['ENTITY_TYPE_ID']) ? (int)$fields['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
			$entityId = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;

			if (is_array($addresses))
			{
				if(!CCrmOwnerType::IsDefined($entityTypeId) || $entityId <= 0)
				{
					$entityTypeId = CCrmOwnerType::Requisite;
					$entityId = $id;
				}

				foreach($addresses as $addressTypeID => $address)
				{
					if(!is_array($address) || empty($address))
					{
						continue;
					}

					EntityAddress::register(
						CCrmOwnerType::Requisite,
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
							'COUNTRY_CODE' => isset($address['COUNTRY_CODE']) ? $address['COUNTRY_CODE'] : null,
							'LOC_ADDR_ID' => isset($address['LOC_ADDR_ID']) ? (int)$address['LOC_ADDR_ID'] : 0,
							'LOC_ADDR' => isset($address['LOC_ADDR']) ? $address['LOC_ADDR'] : null
						)
					);
				}
			}

			if (($entityTypeId === CCrmOwnerType::Company || $entityTypeId === CCrmOwnerType::Contact)
				&& $entityId > 0)
			{
				DuplicateRequisiteCriterion::registerByEntity($entityTypeId, $entityId);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					$entityTypeId,
					$entityId,
					[FieldCategory::REQUISITE]
				);
				//endregion Register volatile duplicate criterion fields
			}

			//region Send event
			$event = new Main\Event('crm', 'OnAfterRequisiteAdd', array('id' => $id, 'fields' => $fields));
			$event->send();
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

		$result = $this->checkRqFieldsBeforeSave($id, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

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
		$id = intval($id);
		$requisite = $this->getList(
			array(
				'filter' => ['=ID' => $id],
				'select' => array_merge(['ADDRESS_ONLY'], self::getFileFields()),
				'limit' => 1,
			)
		)->fetch();
		if(!is_array($requisite))
		{
			$result = new Main\Result();
			$result->addError(new Main\Error("The Requisite with ID '{$id}' is not found"));
			return $result;
		}

		unset($fields['ID'], $fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$addresses = null;
		if (isset($fields[self::ADDRESS]))
		{
			$addresses = $fields[self::ADDRESS];
			unset($fields[self::ADDRESS]);
		}

		$addressOnly = false;
		if (isset($fields['ADDRESS_ONLY']))
		{
			$addressOnly = ($fields['ADDRESS_ONLY'] === true || $fields['ADDRESS_ONLY'] === 'Y');
		}
		elseif ($requisite['ADDRESS_ONLY'] === 'Y')
		{
			$addressOnly = true;
		}

		$entityBeforeUpdate = null;
		$entityTypeId = CCrmOwnerType::Undefined;
		$entityId = 0;
		$entityTypeIdModified = $entityIdModified = false;
		if (isset($fields['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
			if ($entityBeforeUpdate === null)
				$entityBeforeUpdate = EntityRequisite::getOwnerEntityById($id);
			if (
				CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
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
			if (
				CCrmOwnerType::IsDefined($entityBeforeUpdate['ENTITY_TYPE_ID'])
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

		if ($addressOnly)
		{
			$fields = $this->clearNonAddressFields($fields);
		}

		$result = $this->checkRqFieldsBeforeSave($id, $fields);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = RequisiteTable::update($id, $fields);
		if ($result->isSuccess())
		{
			if ($addressOnly)
			{
				$GLOBALS['USER_FIELD_MANAGER']->Delete($this->getUfId(), $id);
			}
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
						RequisiteAddress::unregister(CCrmOwnerType::Requisite, $id, $addressTypeId);
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
					if(isset($address['LOC_ADDR_ID']))
					{
						$actualAddressFields['LOC_ADDR_ID'] = (int)$address['LOC_ADDR_ID'];
					}
					if(isset($address['LOC_ADDR']))
					{
						$actualAddressFields['LOC_ADDR'] = $address['LOC_ADDR'];
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
							$anchorTypeID = isset($actualFields['ENTITY_TYPE_ID']) ? (int)$actualFields['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
							$anchorID = isset($actualFields['ENTITY_ID']) ? (int)$actualFields['ENTITY_ID'] : 0;
							if(!CCrmOwnerType::IsDefined($anchorTypeID) || $anchorID <= 0)
							{
								$anchorTypeID = CCrmOwnerType::Requisite;
								$anchorID = $id;
							}

							$actualAddressFields['ANCHOR_TYPE_ID'] = $anchorTypeID;
							$actualAddressFields['ANCHOR_ID'] = $anchorID;

							EntityAddress::register(CCrmOwnerType::Requisite, $id, $addressTypeId, $actualAddressFields);
						}
					}
				}
			}
			foreach (self::getFileFields() as $fileField)
			{
				if (
					array_key_exists($fileField, $fields)
					&& $requisite[$fileField] != $fields[$fileField]
				)
				{
					\CFile::Delete($requisite[$fileField]);
				}
			}

			if ($entityTypeIdModified || $entityIdModified)
			{
				DuplicateRequisiteCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'],
					$entityBeforeUpdate['ENTITY_ID']
				);
				DuplicateBankDetailCriterion::registerByEntity(
					$entityBeforeUpdate['ENTITY_TYPE_ID'],
					$entityBeforeUpdate['ENTITY_ID']
				);
				DuplicateRequisiteCriterion::unregister($entityTypeId, $entityId);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					$entityBeforeUpdate['ENTITY_TYPE_ID'],
					$entityBeforeUpdate['ENTITY_ID'],
					[FieldCategory::REQUISITE, FieldCategory::BANK_DETAIL]
				);
				DuplicateVolatileCriterion::register(
					$entityTypeId,
					$entityId,
					[FieldCategory::REQUISITE, FieldCategory::BANK_DETAIL]
				);
				//endregion Register volatile duplicate criterion fields
			}

			if (isset($fields['ENTITY_TYPE_ID']) && isset($fields['ENTITY_ID']))
			{
				DuplicateRequisiteCriterion::registerByEntity(
					(int)$fields['ENTITY_TYPE_ID'], (int)$fields['ENTITY_ID']
				);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					(int)$fields['ENTITY_TYPE_ID'],
					(int)$fields['ENTITY_ID'],
					[FieldCategory::REQUISITE]
				);
				//endregion Register volatile duplicate criterion fields

				if ($entityTypeIdModified || $entityIdModified)
				{
					DuplicateBankDetailCriterion::registerByEntity(
						(int)$fields['ENTITY_TYPE_ID'], (int)$fields['ENTITY_ID']
					);

					//region Register volatile duplicate criterion fields
					DuplicateVolatileCriterion::register(
						(int)$fields['ENTITY_TYPE_ID'],
						(int)$fields['ENTITY_ID'],
						[FieldCategory::BANK_DETAIL]
					);
					//endregion Register volatile duplicate criterion fields
				}
			}
			else
			{
				DuplicateRequisiteCriterion::registerByRequisite($id);

				//region Register volatile duplicate criterion fields
				DuplicateVolatileCriterion::register(
					CCrmOwnerType::Requisite,
					$id,
					[FieldCategory::REQUISITE]
				);
				//endregion Register volatile duplicate criterion fields

				if ($entityTypeIdModified || $entityIdModified)
				{
					DuplicateBankDetailCriterion::registerByParent(CCrmOwnerType::Requisite, $id);

					//region Register volatile duplicate criterion fields
					DuplicateVolatileCriterion::register(
						CCrmOwnerType::Requisite,
						$id,
						[FieldCategory::BANK_DETAIL]
					);
					//endregion Register volatile duplicate criterion fields
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
		$requisite = $this->getList([
			'filter' => ['=ID' => $id],
			'select' => array_merge(['ID'], self::getFileFields()),
			'limit' => 1,
		])->fetch();

		$entityInfo = self::getOwnerEntityById($id);

		EntityLink::unregisterByRequisite($id);
		RequisiteAddress::deleteByEntityId($id);

		$bankDetail = EntityBankDetail::getSingleInstance();
		$bankDetail->deleteByEntity(CCrmOwnerType::Requisite, $id);

		$result = RequisiteTable::delete($id);

		if ($result->isSuccess())
		{
			foreach (self::getFileFields() as $fileField)
			{
				if ($requisite[$fileField] > 0)
				{
					\CFile::Delete($requisite[$fileField]);
				}
			}
		}
		if ($result->isSuccess()
			&& \CCrmOwnerType::IsDefined($entityInfo['ENTITY_TYPE_ID']) && $entityInfo['ENTITY_ID'] > 0)
		{
			DuplicateRequisiteCriterion::RegisterByEntity($entityInfo['ENTITY_TYPE_ID'], $entityInfo['ENTITY_ID']);

			//region Register volatile duplicate criterion fields
			DuplicateVolatileCriterion::register(
				$entityInfo['ENTITY_TYPE_ID'],
				$entityInfo['ENTITY_ID'],
				[FieldCategory::REQUISITE]
			);
			//endregion Register volatile duplicate criterion fields
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

	public function checkCountryId(int $countryId): bool
	{
		return in_array($countryId, self::$allowedRqFieldCountryIds, true);
	}

	public function checkRqFieldCountryId(string $fieldName, int $countryId): bool
	{
		$rqFieldsCountryMap = $this->getRqFieldsCountryMap();
		if (is_array($rqFieldsCountryMap[$fieldName]) && in_array($countryId, $rqFieldsCountryMap[$fieldName], true))
		{
			return true;
		}

		return false;
	}

	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			$requisite = static::getSingleInstance();

			self::$FIELD_INFOS = [
				'ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'ENTITY_TYPE_ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					]
				],
				'ENTITY_ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					]
				],
				'PRESET_ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					]
				],
				'DATE_CREATE' => [
					'TYPE' => 'datetime',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'DATE_MODIFY' => [
					'TYPE' => 'datetime',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'CREATED_BY_ID' => [
					'TYPE' => 'user',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'MODIFY_BY_ID' => [
					'TYPE' => 'user',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'NAME' => ['TYPE' => 'string'],
				'CODE' => ['TYPE' => 'string'],
				'XML_ID' => ['TYPE' => 'string'],
				'ORIGINATOR_ID' => ['TYPE' => 'string'],
				'ACTIVE' => ['TYPE' => 'char'],
				'ADDRESS_ONLY' => ['TYPE' => 'char'],
				'SORT' => ['TYPE' => 'integer'],
				'RQ_NAME' => ['TYPE' => 'string'],
				'RQ_FIRST_NAME' => ['TYPE' => 'string'],
				'RQ_LAST_NAME' => ['TYPE' => 'string'],
				'RQ_SECOND_NAME' => ['TYPE' => 'string'],
				'RQ_COMPANY_ID' => ['TYPE' => 'string'],
				'RQ_COMPANY_NAME' => ['TYPE' => 'string'],
				'RQ_COMPANY_FULL_NAME' => ['TYPE' => 'string'],
				'RQ_COMPANY_REG_DATE' => ['TYPE' => 'string'],
				'RQ_DIRECTOR' => ['TYPE' => 'string'],
				'RQ_ACCOUNTANT' => ['TYPE' => 'string'],
				'RQ_CEO_NAME' => ['TYPE' => 'string'],
				'RQ_CEO_WORK_POS' => ['TYPE' => 'string'],
				'RQ_CONTACT' => ['TYPE' => 'string'],
				'RQ_EMAIL' => ['TYPE' => 'string'],
				'RQ_PHONE' => ['TYPE' => 'string'],
				'RQ_FAX' => ['TYPE' => 'string'],
				'RQ_IDENT_TYPE' => [
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => $requisite->getAllowedRqListFieldsStatusEntitities('RQ_IDENT_TYPE')
				],
				'RQ_IDENT_DOC' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_SER' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_NUM' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_PERS_NUM' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_DATE' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_ISSUED_BY' => ['TYPE' => 'string'],
				'RQ_IDENT_DOC_DEP_CODE' => ['TYPE' => 'string'],
				'RQ_INN' => ['TYPE' => 'string'],
				'RQ_KPP' => ['TYPE' => 'string'],
				'RQ_USRLE' => ['TYPE' => 'string'],
				'RQ_IFNS' => ['TYPE' => 'string'],
				'RQ_OGRN' => ['TYPE' => 'string'],
				'RQ_OGRNIP' => ['TYPE' => 'string'],
				'RQ_OKPO' => ['TYPE' => 'string'],
				'RQ_OKTMO' => ['TYPE' => 'string'],
				'RQ_OKVED' => ['TYPE' => 'string'],
				'RQ_EDRPOU' => ['TYPE' => 'string'],
				'RQ_DRFO' => ['TYPE' => 'string'],
				'RQ_KBE' => ['TYPE' => 'string'],
				'RQ_IIN' => ['TYPE' => 'string'],
				'RQ_BIN' => ['TYPE' => 'string'],
				'RQ_ST_CERT_SER' => ['TYPE' => 'string'],
				'RQ_ST_CERT_NUM' => ['TYPE' => 'string'],
				'RQ_ST_CERT_DATE' => ['TYPE' => 'string'],
				'RQ_VAT_PAYER' => ['TYPE' => 'char'],
				'RQ_VAT_ID' => ['TYPE' => 'string'],
				'RQ_VAT_CERT_SER' => ['TYPE' => 'string'],
				'RQ_VAT_CERT_NUM' => ['TYPE' => 'string'],
				'RQ_VAT_CERT_DATE' => ['TYPE' => 'string'],
				'RQ_RESIDENCE_COUNTRY' => ['TYPE' => 'string'],
				'RQ_BASE_DOC' => ['TYPE' => 'string'],
				'RQ_REGON' => ['TYPE' => 'string'],
				'RQ_KRS' => ['TYPE' => 'string'],
				'RQ_PESEL' => ['TYPE' => 'string'],
				'RQ_LEGAL_FORM' => ['TYPE' => 'string'],
				'RQ_SIRET' => ['TYPE' => 'string'],
				'RQ_SIREN' => ['TYPE' => 'string'],
				'RQ_CAPITAL' => ['TYPE' => 'string'],
				'RQ_RCS' => ['TYPE' => 'string'],
				'RQ_CNPJ' => ['TYPE' => 'string'],
				'RQ_STATE_REG' => ['TYPE' => 'string'],
				'RQ_MNPL_REG' => ['TYPE' => 'string'],
				'RQ_CPF' => ['TYPE' => 'string'],
				'RQ_SIGNATURE' => [
					'TYPE' => 'file',
					'VALUE_TYPE' => 'image',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Hidden,
					],
				],
				'RQ_STAMP' => [
					'TYPE' => 'file',
					'VALUE_TYPE' => 'image',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Hidden,
					],
				],
			];
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
			'ADDRESS_ONLY' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_ADDRESS_ONLY'),
				'type' => 'boolean'
			),
			'SORT' => array(
				'title' => GetMessage('CRM_REQUISITE_EXPORT_FIELD_SORT'),
				'type' => 'integer'
			)
		);

		return $result;
	}

	private static $rqFields = [
		'RQ_NAME',
		'RQ_FIRST_NAME',
		'RQ_LAST_NAME',
		'RQ_SECOND_NAME',
		'RQ_COMPANY_ID',
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
		'RQ_IDENT_TYPE',
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
		'RQ_BASE_DOC',
		'RQ_REGON',
		'RQ_KRS',
		'RQ_PESEL',
		'RQ_LEGAL_FORM',
		'RQ_SIRET',
		'RQ_SIREN',
		'RQ_CAPITAL',
		'RQ_RCS',
		'RQ_CNPJ',
		'RQ_STATE_REG',
		'RQ_MNPL_REG',
		'RQ_CPF',
		'RQ_SIGNATURE',
		'RQ_STAMP',
	];

	private static $rqlistFields = [
		'RQ_IDENT_TYPE',
	];

	private static $rqFiltrableFields = null;

	public function getRqFields()
	{
		return self::$rqFields;
	}

	public function getRqListFields(): array
	{
		return self::$rqlistFields;
	}

	public function isRqListField(string $fieldName): bool
	{
		return in_array($fieldName, $this->getRqListFields(), true);
	}

	public function getAllowedRqListFieldsStatusEntitities(string $fieldName = ''): array
	{
		$result = [];

		$rqFieldsCountryMap = $this->getRqFieldsCountryMap();
		$rqListFields = ($fieldName !== '') ? [$fieldName] : $this->getRqListFields();
		foreach ($rqListFields as $fieldName)
		{
			if (is_array($rqFieldsCountryMap[$fieldName]))
			{
				foreach ($rqFieldsCountryMap[$fieldName] as $countryId)
				{
					$countryCode = EntityPreset::getCountryCodeById($countryId);
					$result[] = "{$fieldName}_{$countryCode}";
				}
			}
		}

		return $result;
	}

	public function getRqFiltrableFields()
	{
		if (self::$rqFiltrableFields === null)
		{
			self::$rqFiltrableFields = [
				'RQ_NAME',
				'RQ_FIRST_NAME',
				'RQ_LAST_NAME',
				'RQ_SECOND_NAME',
				'RQ_COMPANY_ID',
				'RQ_COMPANY_NAME',
				'RQ_COMPANY_FULL_NAME',
				'RQ_ADDR',
				'RQ_CONTACT',
				'RQ_EMAIL',
				'RQ_PHONE',
				'RQ_FAX',
				'RQ_IDENT_TYPE',
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
				'RQ_VAT_CERT_NUM',
				'RQ_REGON',
				'RQ_KRS',
				'RQ_PESEL',
				'RQ_SIRET',
				'RQ_SIREN',
				'RQ_RCS',
				'RQ_CNPJ',
				'RQ_STATE_REG',
				'RQ_MNPL_REG',
				'RQ_CPF',
			];
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
		return $fieldName === 'RQ_ADDR' ? EntityAddressType::Primary : EntityAddressType::Undefined;
	}

	public function resolveFieldNameByAddressType($addrType)
	{
		return $addrType === EntityAddressType::Primary ? 'RQ_ADDR' : '';
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

		if (!is_string($title) || $title == '')
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

		$by = '';
		$order = '';
		$langDbResult = \CLanguage::GetList($by, $order);
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
				$fieldTitle =
					(isset($fieldInfo['title']) && !empty($fieldInfo['title']))
					? $fieldInfo['title']
					: GetMessage('CRM_REQUISITE_ENTITY_'.$fieldName.'_FIELD')
				;
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
			// ru - 1, by - 4, kz - 6, ua - 14, br - 34, de - 46, 77 - co, pl - 110, fr - 132, us - 122
			self::$rqFieldCountryMap = [
				'RQ_NAME' => [1, 4, 6, 14, 46, 122],
				'RQ_FIRST_NAME' => [1, 34, 46, 77, 110, 122, 132],
				'RQ_LAST_NAME' => [1, 34, 46, 77, 110, 122, 132],
				'RQ_SECOND_NAME' => [1],
				'RQ_COMPANY_ID' => [110],
				'RQ_COMPANY_NAME' => [1, 4, 6, 14, 34, 46, 77, 110, 122, 132],
				'RQ_COMPANY_FULL_NAME' => [1, 4, 6, 77, 110],
				'RQ_COMPANY_REG_DATE' => [1, 4],
				'RQ_DIRECTOR' => [1, 4, 14],
				'RQ_ACCOUNTANT' => [1, 4, 14],
				'RQ_CEO_NAME' => [6],
				'RQ_CEO_WORK_POS' => [6],
				'RQ_ADDR' => [1, 4, 6, 14, 34, 46, 77, 110, 122, 132],
				'RQ_CONTACT' => [1, 4, 6, 14, 46, 122],
				'RQ_EMAIL' => [1, 4, 6, 14, 46, 122],
				'RQ_PHONE' => [1, 4, 6, 14, 46, 122],
				'RQ_FAX' => [1, 4, 6, 14, 46, 122],
				'RQ_IDENT_TYPE' => [77],
				'RQ_IDENT_DOC' => [1, 4, 77, 132],
				'RQ_IDENT_DOC_SER' => [1, 4],
				'RQ_IDENT_DOC_NUM' => [1, 4, 34, 77, 132],
				'RQ_IDENT_DOC_PERS_NUM' => [4],
				'RQ_IDENT_DOC_DATE' => [1, 4],
				'RQ_IDENT_DOC_ISSUED_BY' => [1, 4],
				'RQ_IDENT_DOC_DEP_CODE' => [1],
				'RQ_INN' => [1, 4, 6, 14, 46, 77, 110],
				'RQ_KPP' => [1],
				'RQ_USRLE' => [46],
				'RQ_IFNS' => [1],
				'RQ_OGRN' => [1],
				'RQ_OGRNIP' => [1],
				'RQ_OKPO' => [1, 4, 6],
				'RQ_OKTMO' => [1],
				'RQ_OKVED' => [1, 132],
				'RQ_EDRPOU' => [14],
				'RQ_DRFO' => [14],
				'RQ_KBE' => [6],
				'RQ_IIN' => [6],
				'RQ_BIN' => [6],
				'RQ_ST_CERT_SER' => [1],
				'RQ_ST_CERT_NUM' => [1, 4],
				'RQ_ST_CERT_DATE' => [1, 4],
				'RQ_VAT_PAYER' => [14],
				'RQ_VAT_ID' => [46, 110, 122, 132],
				'RQ_VAT_CERT_SER' => [6],
				'RQ_VAT_CERT_NUM' => [6, 14],
				'RQ_VAT_CERT_DATE' => [6],
				'RQ_RESIDENCE_COUNTRY' => [6],
				'RQ_BASE_DOC' => [4],
				'RQ_REGON' => [110],
				'RQ_KRS' => [110],
				'RQ_PESEL' => [110],
				'RQ_LEGAL_FORM' => [Country::ID_FRANCE],
				'RQ_SIRET' => [Country::ID_FRANCE],
				'RQ_SIREN' => [Country::ID_FRANCE],
				'RQ_CAPITAL' => [Country::ID_FRANCE],
				'RQ_RCS' => [Country::ID_FRANCE],
				'RQ_CNPJ' => [Country::ID_BRAZIL],
				'RQ_STATE_REG' => [Country::ID_BRAZIL],
				'RQ_MNPL_REG' => [Country::ID_BRAZIL],
				'RQ_CPF' => [Country::ID_BRAZIL],
				'RQ_SIGNATURE' => [
					Country::ID_RUSSIA,
					Country::ID_BELARUS,
					Country::ID_UKRAINE,
					Country::ID_GERMANY,
					Country::ID_USA,
					Country::ID_COLOMBIA,
					Country::ID_KAZAKHSTAN,
					Country::ID_POLAND,
					Country::ID_FRANCE,
					Country::ID_BRAZIL,
				],
				'RQ_STAMP' => [
					Country::ID_RUSSIA,
					Country::ID_BELARUS,
					Country::ID_UKRAINE,
					Country::ID_GERMANY,
					Country::ID_USA,
					Country::ID_COLOMBIA,
					Country::ID_KAZAKHSTAN,
					Country::ID_POLAND,
					Country::ID_FRANCE,
					Country::ID_BRAZIL,
				],
			];
		}

		return self::$rqFieldCountryMap;
	}

	public function getUsedCountries(): array
	{
		return RequisiteTable::getUsedCountries();
	}

	protected function loadPhrases(int $countryId): array
	{
		$phrases = [];

		if ($this->checkCountryId($countryId))
		{
			$countryCode = EntityPreset::getCountryCodeById($countryId);
			$countryCodeLower = mb_strtolower($countryCode);
			$phrasesConfig = [];
			$filePath= Main\IO\Path::normalize(
				Main\Application::getDocumentRoot().
				"/bitrix/modules/crm/lib/requisite/phrases/requisite_$countryCodeLower.php"
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

		if ($phraseId !== '' && $this->checkCountryId($countryId))
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

	protected function getDefaultListFieldItems(string $statusEntityId): array
	{
		$result = [];

		if (is_string($statusEntityId) && $statusEntityId !== '')
		{
			if (!isset(static::$rqListFieldItemsMap[$statusEntityId]))
			{
				$matches = [];
				if (preg_match('/^(RQ_[A-Z0-9_]+)_([A-Z]{2})$/', $statusEntityId, $matches))
				{
					$fieldName = $matches[1];
					$countryCode = $matches[2];
					$countryId = GetCountryIdByCode($countryCode);
					if ($countryId > 0)
					{
						if (
							$this->isRqListField($fieldName)
							&& $this->checkRqFieldCountryId($fieldName, $countryId)
						)
						{
							if ($fieldName === 'RQ_IDENT_TYPE')
							{
								$statusIds = [
									'CIVILREG',
									'IDCARD',
									'CITIZENCARD',
									'IMMCARD',
									'FOREIGNERID',
									'NIT',
									'PASSPORT',
									'FOREIGNIDDOC',
									'EXTNIT',
									'NUIP',
								];
							}
							else
							{
								$statusIds = [];
							}

							$sort = 0;
							$sortStep = 10;
							$phrasePrefix = 'CRM_REQUISITE_ENTITY';
							$phraseSuffix = 'ENUM';
							foreach ($statusIds as $statusId)
							{
								$sort += $sortStep;
								static::$rqListFieldItemsMap[$statusEntityId][] = [
									'ENTITY_ID' => $statusEntityId,
									'STATUS_ID' => $statusId,
									'NAME' => $this->getPhrase(
										"{$phrasePrefix}_{$fieldName}_{$statusId}_{$countryCode}_{$phraseSuffix}",
										$countryId
									),
									'SORT' => $sort,
								];
							}
						}
					}
				}
			}

			if (isset(static::$rqListFieldItemsMap[$statusEntityId]))
			{
				$result = static::$rqListFieldItemsMap[$statusEntityId];
			}
		}

		return $result;
	}

	/**
	 * Creation of list fields elements by default on the event of changing the settings of preset
	 *
	 * @param array $presetFields
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function processPresetSettingsChange(array $presetFields): void
	{
		if (
			is_array($presetFields['SETTINGS'])
			&& is_array($presetFields['SETTINGS']['FIELDS'])
			&& isset($presetFields['COUNTRY_ID'])
			&& $presetFields['COUNTRY_ID'] > 0
		)
		{
			$listFieldMap = array_fill_keys($this->getRqListFields(), true);
			$statusEntityMap = [];

			$fields = $presetFields['SETTINGS']['FIELDS'];
			$presetCountryId = (int)$presetFields['COUNTRY_ID'];
			foreach ($fields as $field)
			{
				if (
					isset($field['FIELD_NAME'])
					&& is_string($field['FIELD_NAME'])
					&& $field['FIELD_NAME'] !== ''
					&& isset($listFieldMap[$field['FIELD_NAME']])
					&& $this->checkRqFieldCountryId($field['FIELD_NAME'], $presetCountryId)
				)
				{
					$countryCode = EntityPreset::getCountryCodeById($presetCountryId);
					if (is_string($countryCode) && $countryCode !== '')
					{
						$statusEntityMap[$field['FIELD_NAME'] . '_' . $countryCode] = true;
					}
				}
			}

			if (!empty($statusEntityMap))
			{
				$res = StatusTable::getList(
					[
						'select' => ['ENTITY_ID'],
						'filter' => ['@ENTITY_ID' => array_keys($statusEntityMap)],
						'group' => ['ENTITY_ID'],
					]
				);
				while ($row = $res->fetch())
				{
					if (isset($statusEntityMap[$row['ENTITY_ID']]))
					{
						unset($statusEntityMap[$row['ENTITY_ID']]);
					}
				}
			}

			if (!empty($statusEntityMap))
			{
				foreach (array_keys($statusEntityMap) as $statusEntityId)
				{
					$this->installDefaultListFieldItems($statusEntityId);
				}
			}
		}
	}

	public function installDefaultListFieldItems(string $statusEntityId): void
	{
		$items = $this->getDefaultListFieldItems($statusEntityId);

		if (!empty($items))
		{
			\CCrmStatus::BulkCreate($statusEntityId, $items);
		}
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
						$phraseId = "CRM_REQUISITE_ENTITY_{$fieldName}_{$countryCodes[$countryId]}_FIELD";
						$phrase = static::getPhrase($phraseId, $countryId);
						$titleMap[$fieldName][$countryId] = ($phrase === null) ? '' : $phrase;
					}
				}
			}
			self::$rqFieldTitleMap = $titleMap;
		}

		return self::$rqFieldTitleMap;
	}

	public function getFormFieldsTypes()
	{
		return [
			'RQ_VAT_PAYER' => 'checkbox',
			'RQ_IDENT_TYPE' => 'crm_status',
			'RQ_STAMP' => 'image',
			'RQ_SIGNATURE' => 'image',
		];
	}

	public function getFormFieldsInfo($countryId = 0)
	{
		$result = array();

		$formTypes = $this->getFormFieldsTypes();
		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
			$rqFields[$rqFieldName] = true;
		$fieldTitles = $this->getFieldsTitles($countryId);
		$fieldsInfo = self::getFieldsInfo();
		foreach (RequisiteTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($fieldInfo['reference']) && $fieldInfo['data_type'] !== 'Address')
				continue;

			$fieldType = $fieldInfo['data_type'] ?? 'string';
			if (($fieldsInfo[$fieldName]['TYPE'] ?? null) === 'file')
			{
				$fieldType = $fieldsInfo[$fieldName]['VALUE_TYPE'] == 'image' ? 'image' : 'file';
			}

			$fieldTitle =  $fieldTitles[$fieldName] ?? '';
			$result[$fieldName] = array(
				'title' => is_string($fieldTitle) ? $fieldTitle : '',
				'type' => $fieldType,
				'required' => (isset($fieldInfo['required']) && $fieldInfo['required']),
				'formType' => $formTypes[$fieldName] ?? 'text',
				'multiple' => false,
				'settings' => null,
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
				'multiple' => (isset($fieldInfo['MULTIPLE']) && $fieldInfo['MULTIPLE'] === 'Y'),
				'settings' => isset($fieldInfo['SETTINGS']) ? $fieldInfo['SETTINGS'] : null,
				'formType' => '',
				'isRQ' => true,
				'isUF' => true
			);
		}

		return $result;
	}

	public function getRqListFieldItems(string $listFieldName, int $countryId): array
	{
		$countryCode = EntityPreset::getCountryCodeById($countryId);
		$statusEntityId = "{$listFieldName}_{$countryCode}";
		$fakeTitlePhraseId = "CRM_REQUISITE_ENTITY_{$statusEntityId}_ENUM_FAKE_TITLE";
		$fakeValue = '';
		return CCrmInstantEditorHelper::PrepareListOptions(
			StatusTable::getStatusesList($statusEntityId),
			[
				'NOT_SELECTED' => $this->getPhrase($fakeTitlePhraseId, $countryId),
				'NOT_SELECTED_VALUE' => $fakeValue
			]
		);
	}

	public function getRqListFieldValueTitles(array $requisiteFields, int $countryId = 0): array
	{
		$result = [];

		$requisite = EntityRequisite::getSingleInstance();

		if ($countryId <= 0)
		{
			$countryId = 0;
			if (isset($requisiteFields['PRESET_ID']) && $requisiteFields['PRESET_ID'] > 0)
			{
				$presetId = (int)$requisiteFields['PRESET_ID'];
				$countryId = $requisite->getCountryIdByPresetId($presetId);
			}
		}

		if ($countryId > 0)
		{
			foreach ($this->getRqListFields() as $fieldName)
			{
				if (
					isset($requisiteFields[$fieldName])
					&& $this->checkRqFieldCountryId($fieldName, $countryId)
				)
				{
					$result[$fieldName] = $requisite->getRqListFieldValueTitle(
						$fieldName,
						$countryId,
						$requisiteFields[$fieldName]
					);
				}
			}
		}

		return $result;
	}

	public function getRqListFieldValueTitle(string $listFieldName, int $countryId, string $listFieldValue): string
	{
		$result = '';

		$items = $this->getRqListFieldItems($listFieldName, $countryId);

		foreach ($items as $itemInfo)
		{
			if ($listFieldValue === (string)$itemInfo['VALUE'])
			{
				$result = $itemInfo['NAME'];
				break;
			}
		}

		return $result;
	}

	public function getRqListFieldFormData(string $listFieldName, int $countryId): array
	{
		$countryCode = EntityPreset::getCountryCodeById($countryId);
		$statusEntityId = "{$listFieldName}_{$countryCode}";

		return [
			'items' => $this->getRqListFieldItems($listFieldName, $countryId),
			'defaultValue' => null,
			'innerConfig' => \CCrmInstantEditorHelper::prepareInnerConfig(
				'crm_status',
				'crm.status.setItems',
				$statusEntityId,
				['']
			),
		];
	}

	protected function removeNonAddressFields($fields)
	{
		$rqFields = $this->getRqFields();
		$userFields = $this->getUserFields();
		foreach ($fields as $fieldName => $fieldValue)
		{
			if(
				$fieldName !== \Bitrix\Crm\EntityRequisite::ADDRESS &&
				(in_array($fieldName, $rqFields) || in_array($fieldName, $userFields)))
			{
				unset($fields[$fieldName]);
			}
		}
		return $fields;
	}

	protected function clearNonAddressFields($fields)
	{
		$rqFields = $this->getRqFields();
		$userFields = $this->getUserFields();
		foreach ($fields as $fieldName => $fieldValue)
		{
			if($fieldName !== \Bitrix\Crm\EntityRequisite::ADDRESS && in_array($fieldName, $rqFields))
			{
				$fields[$fieldName] = false;
			}
			if(in_array($fieldName, $userFields))
			{
				unset($fields[$fieldName]);
			}
		}
		return $fields;
	}

	public static function checkEntityType($entityTypeId)
	{
		$entityTypeId = intval($entityTypeId);

		if ($entityTypeId !== CCrmOwnerType::Company && $entityTypeId !== CCrmOwnerType::Contact)
			return false;

		return true;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID, int $categoryId = 0)
	{
		$entityTypeID = (int)$entityTypeID;

		if ($entityTypeID === CCrmOwnerType::Company || $entityTypeID === CCrmOwnerType::Contact)
		{
			return Crm\Service\Container::getInstance()->getUserPermissions()->checkAddPermissions($entityTypeID, $categoryId);
		}

		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		$entityTypeID = (int)$entityTypeID;
		$entityID = (int)$entityID;

		$userPermissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if ($entityTypeID === CCrmOwnerType::Company && $entityID > 0 && \CCrmCompany::isMyCompany($entityID))
		{
			return $userPermissions->getMyCompanyPermissions()->canUpdate();
		}

		if ($entityTypeID === CCrmOwnerType::Company || $entityTypeID === CCrmOwnerType::Contact)
		{
			return $userPermissions->checkUpdatePermissions($entityTypeID, $entityID);
		}

		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID, $entityID)
	{
		$entityTypeID = (int)$entityTypeID;
		$entityID = (int)$entityID;

		$userPermissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if ($entityTypeID === CCrmOwnerType::Company && $entityID > 0 && \CCrmCompany::isMyCompany($entityID))
		{
			return $userPermissions->getMyCompanyPermissions()->canDelete();
		}

		if ($entityTypeID === CCrmOwnerType::Company || $entityTypeID === CCrmOwnerType::Contact)
		{
			return $userPermissions->checkDeletePermissions($entityTypeID, $entityID);
		}

		return false;
	}

	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0, $categoryId = null)
	{
		$entityTypeID = (int)$entityTypeID;
		$entityID = (int)$entityID;

		$userPermissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if ($entityTypeID <= 0 && $entityID <= 0)
		{
			return
				$userPermissions->checkReadPermissions(CCrmOwnerType::Company, 0, 0)
				&& $userPermissions->checkReadPermissions(CCrmOwnerType::Contact, 0, 0)
			;
		}

		if ($entityTypeID === CCrmOwnerType::Company && $entityID > 0 && \CCrmCompany::isMyCompany($entityID))
		{
			return $userPermissions->getMyCompanyPermissions()->canReadBaseFields($entityID);
		}

		if ($entityTypeID === CCrmOwnerType::Company || $entityTypeID === CCrmOwnerType::Contact)
		{
			return $entityID > 0
				? $userPermissions->checkReadPermissions($entityTypeID, $entityID)
				: $userPermissions->checkReadPermissions($entityTypeID, 0, (int)$categoryId) //  $categoryId should be = 0 instead of null to check access to default category by default
			;
		}

		return false;
	}

	public function checkUpdatePermission($id)
	{
		$r = static::getOwnerEntityById($id);
		if(is_array($r) && !empty($r))
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

		$r = static::getOwnerEntityById($id);
		if(is_array($r) && !empty($r))
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

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			if (!\CCrmCompany::Exists($entityId))
				return false;
		}
		else if ($entityTypeId === CCrmOwnerType::Contact)
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

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			$userPermissions = Crm\Service\Container::getInstance()->getUserPermissions();
			if (\CCrmCompany::isMyCompany($entityId))
			{
				return $userPermissions->getMyCompanyPermissions()->canReadBaseFields($entityId);
			}

			return $userPermissions->checkReadPermissions($entityTypeId, $entityId);
		}
		else if ($entityTypeId === CCrmOwnerType::Contact)
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

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			if (!\CCrmCompany::CheckUpdatePermission($entityId))
				return false;
		}
		else if ($entityTypeId === CCrmOwnerType::Contact)
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

	public function prepareViewData($fields, $fieldsInViewMap, $options = [])
	{
		$optionValueHtml = false;
		$optionValueText = true;
		$optionAllowFileValues = false;

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

		if (isset($options['ALLOW_FILE_VALUES']) && ($options['ALLOW_FILE_VALUES'] === true))
		{
			$optionAllowFileValues = true;
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
				$presetCountryId = ($presetInfo['COUNTRY_ID'] > 0) ? $presetInfo['COUNTRY_ID'] : $currentCountryId;
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
						if (!$optionAllowFileValues && in_array($fieldInfo['type'], ['file', 'image']))
						{
							$skip = true;
						}
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
								$addressTypes = EntityAddressType::getAllDescriptions();
								$addresses = $fieldValue;
								if (is_array($addresses) && !empty($addresses))
								{
									foreach ($addresses as $addressTypeId => $address)
									{
										if (isset($addressTypes[$addressTypeId]) && is_array($address))
										{
											$textValue = AddressFormatter::getSingleInstance()->formatTextMultiline(
												$address,
												RequisiteAddressFormatter::getFormatByCountryId($presetCountryId)
											);
											if (!empty($textValue))
											{
												$resultItem = array(
													'name' => $fieldName,
													'title' => $addressTypes[$addressTypeId],
													'type' => 'address',
													'subType' => EntityAddressType::resolveName($addressTypeId),
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

							if ($fieldInfo['formType'] === 'crm_status')
							{
								$valueTitle = $this->getRqListFieldValueTitle(
									$fieldName,
									$presetCountryId,
									$textValue
								);
								if ($valueTitle !== '')
								{
									$textValue = $valueTitle;
								}
								unset($valueTitle);
							}

							if ($textValue === '')
							{
								$skip = true;
							}
						}

						if ($fieldInfo['title'] === '')
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

	public function prepareViewDataFormatted($fields, $fieldsInViewMap, $options = array())
	{
		$viewData = $this->prepareViewData($fields, $fieldsInViewMap, $options);
		$titleParts = static::getFormattedTitleParts();
		$fieldValues = [];
		foreach ($viewData['fields'] as $field)
		{
			$fieldValues[$field['name']] = [
				'title' => $field['title'],
				'value' => isset($field['textValue']) ? $field['textValue'] : $field['htmlValue']
			];
		}
		$title = $viewData['title'];
		$usedFields = [];
		foreach ($titleParts as $titlePartFields)
		{
			$titleCandidate = [];
			foreach ($titlePartFields as $fieldName)
			{
				if (isset($fieldValues[$fieldName]['value']) && $fieldValues[$fieldName]['value'] != '')
				{
					$titleCandidate[] = $fieldValues[$fieldName]['value'];
				}
			}
			if (!empty($titleCandidate))
			{
				$title = implode(' ', $titleCandidate);
				$usedFields = $titlePartFields;
				break;
			}
		}
		$subtitleParts = [];
		foreach ($fieldValues as $fieldName => $field)
		{
			if (!in_array($fieldName, $usedFields) && $field['value'] != '')
			{
				$subtitleParts[] = $field['title']. ': '.$field['value'];
			}
		}

		$viewData['title'] = $title;
		$viewData['subtitle'] = implode(', ', $subtitleParts);

		return $viewData;
	}

	public static function getFormattedTitleParts()
	{
		return [
			['RQ_COMPANY_NAME'],
			['RQ_COMPANY_FULL_NAME'],
			['RQ_NAME'],
			['RQ_LAST_NAME', 'RQ_FIRST_NAME', 'RQ_SECOND_NAME']
		];
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
			$settings = unserialize($settingsValue, ['allowed_classes' => false]);
		if (is_array($settings))
			$result = $settings;

		return $result;
	}

	public function saveSettings($entityTypeId, $entityId, $settings)
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		global $DB;
		$tableName = self::CONFIG_TABLE_NAME;
		$entityTypeId = $DB->ForSql($entityTypeId);
		$settingsValue = $DB->ForSql(serialize($settings));

		$sql =
			"INSERT INTO {$tableName} (ENTITY_ID, ENTITY_TYPE_ID, SETTINGS)".PHP_EOL.
			"  VALUES({$entityId}, {$entityTypeId}, '{$settingsValue}')".PHP_EOL.
			"  ON DUPLICATE KEY UPDATE SETTINGS = '{$settingsValue}'".PHP_EOL;
		$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}

	public function getEntityRequisiteBindings(int $entityTypeId, int $entityId, ?int $companyId, ?int $contactId): array
	{
		$entityList = [];
		$entityList[] = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,
		];
		if($companyId > 0)
		{
			$entityList[] = [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $companyId,
			];
		}
		if($contactId > 0)
		{
			$entityList[] = [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_ID' => $contactId,
			];
		}

		return (array) $this->getDefaultRequisiteInfoLinked($entityList);
	}

	public function getDefaultMyCompanyEntityRequisiteBindings(
		int $entityTypeId,
		int $entityId,
		?int $myCompanyId
	): array
	{
		$entityList = [
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			],
		];

		if ($myCompanyId > 0)
		{
			$entityList[] = [
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $myCompanyId,
			];
		}

		return (array)$this->getDefaultMyCompanyRequisiteInfoLinked($entityList);
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
					if (isset(EntityLink::getAvailableEntityTypeIds()[$entityTypeId]))
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
											'=ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
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
					if (isset(EntityLink::getAvailableEntityTypeIds()[$entityTypeId]))
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
											'=ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
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
				'COUNTRY_CODE' => $fieldName.'_COUNTRY_CODE',
				'LOC_ADDR_ID' => $fieldName.'_LOC_ADDR_ID'
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
				'COUNTRY_CODE' => 'RQ_ADDR_COUNTRY_CODE',
				'LOC_ADDR_ID' => 'RQ_ADDR_LOC_ADDR_ID'
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
			'_COUNTRY_CODE',
			'_LOC_ADDR_ID'
		);
	}

	public function prepareFormattedAddress(array $fields, $typeId = RequisiteAddress::Undefined)
	{
		$result = '';
		$typeId = (int)$typeId;
		$prefix = $this->resolveFieldNameByAddressType($typeId);

		if (!empty($prefix))
		{
			return AddressFormatter::getSingleInstance()->formatTextComma(
				[
					'ADDRESS_1' => isset($fields[$prefix.'_ADDRESS']) ? $fields[$prefix.'_ADDRESS'] : '',
					'ADDRESS_2' => isset($fields[$prefix.'_ADDRESS_2']) ? $fields[$prefix.'_ADDRESS_2'] : '',
					'CITY' => isset($fields[$prefix.'_CITY']) ? $fields[$prefix.'_CITY'] : '',
					'POSTAL_CODE' => isset($fields[$prefix.'_POSTAL_CODE']) ? $fields[$prefix.'_POSTAL_CODE'] : '',
					'REGION' => isset($fields[$prefix.'_REGION']) ? $fields[$prefix.'_REGION'] : '',
					'PROVINCE' => isset($fields[$prefix.'_PROVINCE']) ? $fields[$prefix.'_PROVINCE'] : '',
					'COUNTRY' => isset($fields[$prefix.'_COUNTRY']) ? $fields[$prefix.'_COUNTRY'] : '',
					'COUNTRY_CODE' => isset($fields[$prefix.'_COUNTRY_CODE']) ? $fields[$prefix.'_COUNTRY_CODE'] : '',
					'LOC_ADDR_ID' => isset($fields[$prefix.'_LOC_ADDR_ID']) ? (int)$fields[$prefix.'_LOC_ADDR_ID'] : 0
				]
			);
		}

		return $result;
	}

	public static function internalizeAddresses(&$fields)
	{
		$addressFieldName = EntityRequisite::ADDRESS;
		if (is_array($fields) && isset($fields[$addressFieldName]))
		{
			if (is_array($fields[$addressFieldName]))
			{
				$allowedRqAddrTypeMap = array_fill_keys(EntityAddressType::getAllIDs(), true);
				foreach ($fields[$addressFieldName] as $addressTypeId => $addressJson)
				{
					$addressTypeId = (int)$addressTypeId;
					$addressSuccess = false;
					$locationAddress = null;
					if (isset($allowedRqAddrTypeMap[$addressTypeId]))
					{
						if (is_array($addressJson)
							&& isset($addressJson['DELETED'])
							&& $addressJson['DELETED'] === 'Y')
						{
							$fields[$addressFieldName][$addressTypeId] = ['DELETED' => 'Y'];
							$addressSuccess = true;
						}
						else if (is_string($addressJson) && $addressJson !== ''
							&& RequisiteAddress::isLocationModuleIncluded())
						{
							$locationAddress = Address::fromJson(
								EntityAddress::prepareJsonValue($addressJson)
							);
							if ($locationAddress)
							{
								$fields[$addressFieldName][$addressTypeId] = [
									'LOC_ADDR' => $locationAddress
								];
								$addressSuccess = true;
							}
						}
					}
					if (!$addressSuccess)
					{
						unset($fields[$addressFieldName][$addressTypeId]);
					}
				}
			}
			else
			{
				unset($fields[$addressFieldName]);
			}
		}
	}

	public static function intertalizeFormData(array $formData, $entityTypeID, array &$requisites, array &$bankDetails)
	{
		$signer = new Main\Security\Sign\Signer();
		foreach($formData as $requisiteID => $requisiteData)
		{
			if(
				(int)$requisiteID > 0
				&& isset($requisiteData['DELETED'])
				&& mb_strtoupper($requisiteData['DELETED']) === 'Y'
			)
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
				EntityRequisite::internalizeAddresses($decodedData['fields']);
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

	public static function saveFormData($entityTypeId, $entityId, $entityRequisites, $entityBankDetails): Result
	{
		$result = new Result();
		$resultData = [];

		if(!empty($entityRequisites))
		{
			$requisite = new self();
			foreach($entityRequisites as $requisiteID => $requisiteData)
			{
				if(isset($requisiteData['isDeleted']) && $requisiteData['isDeleted'] === true)
				{
					$saveRequisiteResult = $requisite->delete($requisiteID);
					if (!$saveRequisiteResult->isSuccess())
					{
						$result->addErrors($saveRequisiteResult->getErrors());
					}
					else
					{
						$resultData['deletedRequisites'][] = (int)$requisiteID;
					}
					continue;
				}

				$requisiteFields = $requisiteData['fields'];
				$requisiteFields['ENTITY_TYPE_ID'] = $entityTypeId;
				$requisiteFields['ENTITY_ID'] = $entityId;

				if((int)$requisiteID > 0)
				{
					$saveRequisiteResult = $requisite->update($requisiteID, $requisiteFields);
					if ($saveRequisiteResult->isSuccess())
					{
						$resultData['updatedRequisites'][] = (int)$requisiteID;
					}
					else
					{
						$result->addErrors($saveRequisiteResult->getErrors());
					}
				}
				else
				{
					$saveRequisiteResult = $requisite->add($requisiteFields);
					if ($saveRequisiteResult->isSuccess())
					{
						$resultData['addedRequisites'][$requisiteID] = $saveRequisiteResult->getId();
					}
					else
					{
						$result->addErrors($saveRequisiteResult->getErrors());
					}
				}
			}
		}
		if(!empty($entityBankDetails))
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			foreach($entityBankDetails as $requisiteID => $bankDetails)
			{
				$isAddressOnly = isset($entityRequisites[$requisiteID])
					&& isset($entityRequisites[$requisiteID]['fields']['ADDRESS_ONLY'])
					&& $entityRequisites[$requisiteID]['fields']['ADDRESS_ONLY'] === 'Y';

				if ($isAddressOnly)
				{
					$bankDetail->deleteByEntity(CCrmOwnerType::Requisite, $requisiteID);
				}
				else
				{
					foreach ($bankDetails as $pseudoID => $bankDetailFields)
					{
						if (isset($bankDetailFields['isDeleted']) && $bankDetailFields['isDeleted'] === true)
						{
							if ((int)$pseudoID > 0)
							{
								$saveBankDetailsResult = $bankDetail->delete($pseudoID);
								if ($saveBankDetailsResult->isSuccess())
								{
									$resultData['deletedBankDetails'][] = (int)$pseudoID;
								}
								else
								{
									$result->addErrors($saveBankDetailsResult->getErrors());
								}
							}
							continue;
						}

						if ((int)$pseudoID > 0)
						{
							$saveBankDetailsResult = $bankDetail->update($pseudoID, $bankDetailFields);
							if ($saveBankDetailsResult->isSuccess())
							{
								$resultData['updatedBankDetails'][] = (int)$pseudoID;
							}
							else
							{
								$result->addErrors($saveBankDetailsResult->getErrors());
							}
						}
						else
						{
							if ((int)$requisiteID <= 0 && isset($resultData['addedRequisites'][$requisiteID]))
							{
								$requisiteID = $resultData['addedRequisites'][$requisiteID];
							}

							if ((int)$requisiteID > 0)
							{
								$bankDetailFields['ENTITY_ID'] = $requisiteID;
								$bankDetailFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
								$saveBankDetailsResult = $bankDetail->add($bankDetailFields);
								if ($saveBankDetailsResult->isSuccess())
								{
									$resultData['addedBankDetails'][$pseudoID] = $saveBankDetailsResult->getId();
								}
								else
								{
									$result->addErrors($saveBankDetailsResult->getErrors());
								}
							}
						}
					}
				}
			}
		}

		return $result->setData($resultData);
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

		if(isset($formData['ADDRESS_ONLY']))
		{
			$fields['ADDRESS_ONLY'] = $formData['ADDRESS_ONLY'] === 'Y';
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
			array('filter' => array('ENTITY_TYPE_ID' => CCrmOwnerType::Requisite, 'ENTITY_ID' => $id))
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
				'COUNTRY_CODE' => isset($ary['COUNTRY_CODE']) ? $ary['COUNTRY_CODE'] : '',
				'LOC_ADDR_ID' => isset($ary['LOC_ADDR_ID']) ? (int)$ary['LOC_ADDR_ID'] : 0
			);
		}
		return $results;
	}

	public static function getFixedPresetList()
	{
		$requisite = static::getSingleInstance();

		if (self::$fixedPresetList === null)
		{
			self::$fixedPresetList = [
				// ru
				0 => [
					'ID' => 1,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_COMPANY_RU_TITLE', 1),
					'ACTIVE' => 'Y',
					'XML_ID' => static::XML_ID_DEFAULT_PRESET_RU_COMPANY,
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_OGRN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_KPP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_COMPANY_REG_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_OKTMO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
							11 => [
								'ID' => 12,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							],
							12 => [
								'ID' => 13,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 630
							],
						],
						'LAST_FIELD_ID' => 13,
					]
				],
				1 => [
					'ID' => 2,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL_RU_TITLE', 1),
					'ACTIVE' => 'Y',
					'XML_ID' => static::XML_ID_DEFAULT_PRESET_RU_INDIVIDUAL,
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							],
							3 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							],
							4 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_SECOND_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							],
							5 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 560
							],
							6 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_OGRNIP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_OKVED',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
							11 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							],
						],
						'LAST_FIELD_ID' => 10,
					]
				],
				2 => [
					'ID' => 3,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '1',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_RU_TITLE', 1),
					'ACTIVE' => 'Y',
					'XML_ID' => static::XML_ID_DEFAULT_PRESET_RU_PERSON,
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_SECOND_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_IDENT_DOC_ISSUED_BY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DEP_CODE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
							11 => [
								'ID' => 12,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							],
						],
						'LAST_FIELD_ID' => 12,
					]
				],
				// by
				3 => [
					'ID' => 4,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_COMPANY_BY_TITLE', 4),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_COMPANY_REG_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_BASE_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
						],
						'LAST_FIELD_ID' => 11,
					]
				],
				4 => [
					'ID' => 5,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_BY_TITLE', 4),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_IDENT_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_IDENT_DOC_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_PERS_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_IDENT_DOC_ISSUED_BY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_IDENT_DOC_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
						],
						'LAST_FIELD_ID' => 10,
					]
				],
				5 => [
					'ID' => 6,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '4',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL_BY_TITLE', 4),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BY_INDIVIDUAL#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ST_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_ST_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
						],
						'LAST_FIELD_ID' => 8,
					]
				],
				// kz
				6 => [
					'ID' => 7,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_INDIVIDUAL_KZ_TITLE', 6),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_INDIVIDUAL#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_KBE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_VAT_CERT_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_VAT_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_RESIDENCE_COUNTRY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_CEO_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
							11 => [
								'ID' => 12,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							],
							12 => [
								'ID' => 13,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 630
							],
							13 => [
								'ID' => 14,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 640
							],
						],
						'LAST_FIELD_ID' => 14,
					]
				],
				7 => [
					'ID' => 8,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_KZ_TITLE', 6),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_OKPO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_KBE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IIN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_BIN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_VAT_CERT_SER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_VAT_CERT_DATE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_RESIDENCE_COUNTRY',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_CEO_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610
							],
							11 => [
								'ID' => 12,
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 620
							],
							12 => [
								'ID' => 13,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 630
							],
							13 => [
								'ID' => 14,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 640,
							],
							14 => [
								'ID' => 15,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 650,
							],
						],
						'LAST_FIELD_ID' => 15,
					]
				],
				8 => [
					'ID' => 9,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '6',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_KZ_TITLE', 6),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_KZ_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540,
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
						],
						'LAST_FIELD_ID' => 5,
					]
				],
				// ua
				9 => [
					'ID' => 10,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '14',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_UA_TITLE', 14),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_UA_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_EDRPOU',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_VAT_PAYER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
						],
						'LAST_FIELD_ID' => 10,
					]
				],
				10 => [
					'ID' => 11,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '14',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_UA_TITLE', 14),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_UA_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_DRFO',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_VAT_PAYER',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
						],
						'LAST_FIELD_ID' => 8,
					]
				],
				// de
				11 => [
					'ID' => 12,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '46',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_DE_TITLE', 46),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_DE_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_USRLE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
						],
						'LAST_FIELD_ID' => 7,
					]
				],
				12 => [
					'ID' => 13,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '46',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_DE_TITLE', 46),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_DE_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
						],
						'LAST_FIELD_ID' => 5,
					]
				],
				// co
				13 => [
					'ID' => 14,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '77',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_CO_TITLE', 77),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_CO_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_TYPE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
						],
						'LAST_FIELD_ID' => 8,
					]
				],
				14 => [
					'ID' => 15,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '77',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_CO_TITLE', 77),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_CO_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_TYPE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
						],
						'LAST_FIELD_ID' => 8,
					]
				],
				// us
				15 => [
					'ID' => 16,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '122',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_US_TITLE', 122),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_US_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
						],
						'LAST_FIELD_ID' => 5,
					]
				],
				16 => [
					'ID' => 17,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '122',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_US_TITLE', 122),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_US_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'Y',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
						],
						'LAST_FIELD_ID' => 5,
					]
				],
				// pl
				17 => [
					'ID' => 18,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '110',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_PL_TITLE', 110),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_PL_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_FULL_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_INN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_REGON',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_KRS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_COMPANY_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600
							],
						],
						'LAST_FIELD_ID' => 10,
					]
				],
				18 => [
					'ID' => 19,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '110',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_PL_TITLE', 110),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_PL_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_PESEL',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560
							],
						],
						'LAST_FIELD_ID' => 6,
					]
				],
				// fr
				19 => [
					'ID' => 20,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '132',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_FR_TITLE', 132),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_FR_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510,
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LEGAL_FORM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520,
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_SIRET',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530,
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_SIREN',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540,
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_OKVED',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550,
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_CAPITAL',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560,
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_RCS',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570,
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_VAT_ID',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580,
							],
							8 => [
								'ID' => 9,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 590,
							],
							9 => [
								'ID' => 10,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 600,
							],
							10 => [
								'ID' => 11,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 610,
							],
						],
						'LAST_FIELD_ID' => 11,
					],
				],
				20 => [
					'ID' => 21,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '132',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_FR_TITLE', 132),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_FR_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510,
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520,
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530,
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540,
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550,
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560,
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570,
							],
						],
						'LAST_FIELD_ID' => 7,
					],
				],
				// br
				21 => [
					'ID' => 22,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '34',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_LEGALENTITY_BR_TITLE', 34),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BR_LEGALENTITY#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_CNPJ',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510,
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_COMPANY_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520,
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_STATE_REG',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530,
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_MNPL_REG',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540,
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550,
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560,
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570,
							],
						],
						'LAST_FIELD_ID' => 7,
					],
				],
				22 => [
					'ID' => 23,
					'ENTITY_TYPE_ID' => '8',
					'COUNTRY_ID' => '34',
					'NAME' => $requisite->getPhrase('CRM_REQUISITE_FIXED_PRESET_PERSON_BR_TITLE', 34),
					'ACTIVE' => 'Y',
					'XML_ID' => '#CRM_REQUISITE_PRESET_DEF_BR_PERSON#',
					'SORT' => 500,
					'SETTINGS' => [
						'FIELDS' => [
							0 => [
								'ID' => 1,
								'FIELD_NAME' => 'RQ_CPF',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 510,
							],
							1 => [
								'ID' => 2,
								'FIELD_NAME' => 'RQ_FIRST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 520,
							],
							2 => [
								'ID' => 3,
								'FIELD_NAME' => 'RQ_LAST_NAME',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 530,
							],
							3 => [
								'ID' => 4,
								'FIELD_NAME' => 'RQ_IDENT_DOC_NUM',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 540,
							],
							4 => [
								'ID' => 5,
								'FIELD_NAME' => 'RQ_STATE_REG',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 550,
							],
							5 => [
								'ID' => 6,
								'FIELD_NAME' => 'RQ_ADDR',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 560,
							],
							6 => [
								'ID' => 7,
								'FIELD_NAME' => 'RQ_SIGNATURE',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 570,
							],
							7 => [
								'ID' => 8,
								'FIELD_NAME' => 'RQ_STAMP',
								'FIELD_TITLE' => '',
								'IN_SHORT_LIST' => 'N',
								'SORT' => 580,
							],
						],
						'LAST_FIELD_ID' => 8,
					],
				],
			];
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
				$languageId = mb_substr((string)Main\Config\Option::get('main', '~controller_group_name'), 0, 2);
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
			case 'pl':
				$countryCode = 'PL';
				break;
			case 'co':
				$countryCode = 'CO';
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
				$requisite = EntityRequisite::getSingleInstance();
				$fixedPresetList = self::getFixedPresetList();
				$sort = 500;
				$datetimeEntity = new Main\DB\SqlExpression(
					Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction()
				);
				foreach ($fixedPresetList as $presetData)
				{
					if ($countryId === intval($presetData['COUNTRY_ID']))
					{
						$sort += 10;
						$presetFields = [
							'ENTITY_TYPE_ID' => EntityPreset::Requisite,
							'COUNTRY_ID' => $countryId,
							'DATE_CREATE' => $datetimeEntity,
							'CREATED_BY_ID' => 0,
							'NAME' => $presetData['NAME'],
							'ACTIVE' => $presetData['ACTIVE'],
							'SORT' => $sort,
							'XML_ID' => $presetData['XML_ID'],
							'SETTINGS' => $presetData['SETTINGS']
						];
						$preset->clearCache();
						PresetTable::add($presetFields);
						$requisite->processPresetSettingsChange($presetFields);
					}
				}
			}
		}
	}

	public function prepareEntityListFilterFields(&$filterFields)
	{
		$allowedCountries = self::getAllowedRqFieldCountries();
		$preset = EntityPreset::getSingleInstance();
		$requisite = EntityRequisite::getSingleInstance();
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
							$addressTypeId = RequisiteAddress::Undefined;
							$addressTypeName = $fieldTitleMap[$fieldName][$countryId];
							$addressLabels = RequisiteAddress::getShortLabels(RequisiteAddress::Primary);
							foreach (
								array_keys(EntityRequisite::getAddressFieldMap(RequisiteAddress::Primary))
								as $addrFieldKey
							)
							{
								if ($addrFieldKey === 'ADDRESS_2'
									|| $addrFieldKey === 'COUNTRY_CODE'
									|| $addrFieldKey === 'LOC_ADDR_ID')
								{
									continue;
								}

								$filterFields[] = array(
									'id' => "$fieldName|$countryId|$addressTypeId|$addrFieldKey",
									'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
										($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.
										$addressTypeName.' - '.ToLower($addressLabels[$addrFieldKey]),
									'type' => 'text'
								);
							}
						}
						else
						{
							$isListField = false;
							if ($requisite->isRqListField($fieldName))
							{
								$formType = 'list';
								$isListField = true;
							}
							else
							{
								$formType = isset($fieldsFormTypes[$fieldName]) ? $fieldsFormTypes[$fieldName] : 'text';
							}
							$filterFields[] = array(
								'id' => "$fieldName|$countryId",
								'name' => GetMessage('CRM_REQUISITE_FILTER_PREFIX').
									($hideCountry ? '' : ' ('.$countryList[$countryId].')').': '.
									$fieldTitleMap[$fieldName][$countryId],
								'type' => $formType
							);
							if ($isListField)
							{
								$items = [];
								foreach ($requisite->getRqListFieldItems($fieldName, $countryId) as $item)
								{
									$items[$item['VALUE']] = $item['NAME'];
								}
								$filterFields['items'] = $items;
							}
						}
					}
				}
			}
		}
	}

	public function prepareEntityListFilter(array &$filter)
	{
		$rqFilter = array();

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

				$fieldParsed =
					$this->checkRqFieldCountryId($fieldName, $countryId)
					&& (
						EntityAddressType::isDefined($addressTypeId)
						|| $addressTypeId === EntityAddressType::Undefined
					)
					&& in_array($addressFieldName, EntityRequisite::getAddressFiltrableFields(), true)
				;
			}
			else if (preg_match('/^(RQ_\w+)\|(\d+)$/'.BX_UTF_PCRE_MODIFIER, $filterFieldId, $matches))
			{
				$fieldName = $matches[1];
				$countryId = (int)$matches[2];

				$fieldParsed = $this->checkRqFieldCountryId($fieldName, $countryId);
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
					if ($fieldFormType === 'checkbox' || $fieldFormType === 'crm_status')
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
		$entityTypeId = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		if ($entityTypeId === CCrmOwnerType::Undefined)
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

		$allowedAddressTypeMap = null;
		$filtrableAddressFields = null;
		$fieldsInfo = array();
		$joins = array();
		$whereConditions = [];
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
											if ($allowedAddressTypeMap === null)
											{
												$allowedAddressTypeMap = array_fill_keys(
													EntityAddressType::getAllIDs(),
													true
												);
											}

											$addressType = (int)$filterInfo['PARAMS']['ADDRESS_TYPE'];
											if ($addressType === RequisiteAddress::Undefined)
											{
												$addressType = EntityAddressType::getAllIDs();
											}
											if (is_array($addressType)
												|| isset($allowedAddressTypeMap[$addressType]))
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
													$joinType = 'INNER';

													if (
														$filterInfo['VALUE'] === false
														&& $filterInfo['OPERATION'] === '='
													)
													{
														$joinType = 'LEFT';
														$whereFilterPart = $filterPart;

														unset($whereFilterPart[$operation.$fieldName]);
														$whereFilterPart['!'.$fieldName] = false;

														$prepareWhere = \CSqlUtil::PrepareWhere(
															$fieldsInfo[$countryId],
															$whereFilterPart,
															$joins
														);

														$whereConditions[] = [
															'TYPE' => 'WHERE',
															'SQL' =>
																"{$masterAlias}.{$masterIdentity} NOT IN (".
																" SELECT DISTINCT RQ.ENTITY_ID FROM b_crm_requisite RQ".
																" INNER JOIN b_crm_preset PR ON RQ.PRESET_ID = PR.ID".
																" INNER JOIN b_crm_addr AR".
																" ON AR.TYPE_ID {$addressTypeCompare}".
																" AND AR.ENTITY_TYPE_ID = ". CCrmOwnerType::Requisite.
																" AND AR.ENTITY_ID = RQ.ID".
																" WHERE {$prepareWhere})"
														];
													}

													$joins[] = array(
														'TYPE' => $joinType,
														'SQL' => $joinType." JOIN (".
															"SELECT DISTINCT RQ.ENTITY_ID FROM b_crm_requisite RQ".
															" INNER JOIN b_crm_preset PR ON RQ.PRESET_ID = PR.ID".
															" INNER JOIN b_crm_addr AR".
															" ON AR.TYPE_ID {$addressTypeCompare}".
															" AND AR.ENTITY_TYPE_ID = ". CCrmOwnerType::Requisite.
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

										$joinType = 'INNER';

										// if search all entities which have null or empty value for $fieldName (use ^%^ in filter)
										if (
											$filterInfo['VALUE'] === false
											&& $filterInfo['OPERATION'] === '='
										)
										{
											$joinType = 'LEFT';
											$whereFilterPart = $filterPart;

											unset($whereFilterPart[$operation.$fieldName]);
											$whereFilterPart['!'.$fieldName] = false;

											$prepareWhere = \CSqlUtil::PrepareWhere(
												$fieldsInfo[$countryId],
												$whereFilterPart,
												$joins
											);
											$whereConditions[] = [
												'TYPE' => 'WHERE',
												'SQL' =>
													"{$masterAlias}.{$masterIdentity} NOT IN (".
													" SELECT DISTINCT RQ.ENTITY_ID FROM b_crm_requisite RQ".
													" INNER JOIN b_crm_preset PR ON RQ.PRESET_ID = PR.ID".
													" WHERE {$prepareWhere})"
											];
										}

										$joins[] = array(
											'TYPE' => $joinType,
											'SQL' => $joinType." JOIN (".
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

		if (!empty($whereConditions))
		{
			if(!isset($filter['__CONDITIONS']))
			{
				$filter['__CONDITIONS'] = $whereConditions;
			}
			else
			{
				$filter['__CONDITIONS'] = array_merge($filter['__CONDITIONS'], $whereConditions);
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
			$prefixLength = mb_strlen($prefix);
			foreach ($headers as $headerInfo)
			{
				if (isset($headerInfo['id']) && mb_strlen($headerInfo['id']) > $prefixLength
					&& mb_substr($headerInfo['id'], 0, $prefixLength) === $prefix)
				{
					$headerRqFields[$headerInfo['id']] = true;
				}
			}
			foreach ($entityFields as $k => $fieldName)
			{
				if (mb_strlen($fieldName) > $prefixLength && mb_substr($fieldName, 0, $prefixLength) === $prefix
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
			$prefixLength = mb_strlen($prefix);
			foreach ($entityFields as $k => $fieldName)
			{
				if (mb_strlen($fieldName) > $prefixLength && mb_substr($fieldName, 0, $prefixLength) === $prefix)
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

			$userFields = array_fill_keys($this->getUserFields(), true);

			// parse fields
			$prefixLength = mb_strlen($prefix);
			$countryFieldMap = array();
			$countries = array();
			$fields = array();
			$selectInfo = array();
			foreach ($select as $fieldString)
			{
				if (mb_strlen($fieldString) <= $prefixLength)
					continue;

				$fieldName = '';
				$countryId = 0;
				$fieldParsed = false;
				if (
					preg_match(
						'/^((UF_|RQ_)\w+)\|(\d+)$/'.BX_UTF_PCRE_MODIFIER,
						mb_substr($fieldString, $prefixLength),
						$matches
					)
				)
				{
					$fieldName = $matches[1];
					$fieldPrefix = $matches[2];
					$countryId = (int)$matches[3];

					$fieldParsed =
						$fieldPrefix === 'RQ_'
							? $this->checkRqFieldCountryId($fieldName, $countryId)
							: (isset($userFields[$fieldName]) && $this->checkCountryId($countryId))
					;
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
				if (isset($fields['ID']))
					unset($fields['ID']);
				if (isset($fields['ENTITY_ID']))
					unset($fields['ENTITY_ID']);
				if (isset($fields['PRESET.COUNTRY_ID']))
					unset($fields['PRESET.COUNTRY_ID']);
				if (isset($fields[EntityRequisite::ADDRESS]))
				{
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

				$rqFieldTypeInfo = [];
				$resultData = [];
				$addressRelations = [];
				$requisiteCountries = [];
				$rqListFieldMap = [];
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
									if ($this->isRqListField($fieldName))
									{
										if (!is_array($rqListFieldMap[$fieldName][$countryId]))
										{
											$rqListFieldMap[$fieldName][$countryId] = [];
											foreach ($this->getRqListFieldItems($fieldName, $countryId) as $item)
											{
												if (!isset($rqListFieldMap[$fieldName][$countryId][$item['VALUE']]))
												{
													$rqListFieldMap[$fieldName][$countryId][$item['VALUE']] =
														$item['NAME']
													;
												}
											}
											unset($item);
										}
										$value = $rqListFieldMap[$fieldName][$countryId][$value] ?? "";
									}
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
				unset($rqListFieldMap);

				// collect addresses
				if (!empty($addressRelations))
				{
					$addr = new RequisiteAddress();
					$res = $addr->getList(
						array(
							'filter' => array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
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
												$formatter = AddressFormatter::getSingleInstance();
												$formatId = RequisiteAddressFormatter::getFormatByCountryId(
													$fieldCountryId
												);
												if ($exportMode === 'excel')
												{
													$addressValue = $formatter->formatTextMultilineSpecialchar(
														$addressData,
														$formatId
													);
												}
												else
												{
													$addressValue = $formatter->formatTextMultiline(
														$addressData,
														$formatId
													);
												}
												unset($formatter);

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
												$formatter = AddressFormatter::getSingleInstance();
												$addressValue = nl2br(
													$formatter->formatHtmlMultilineSpecialchar(
														$addressData,
														RequisiteAddressFormatter::getFormatByCountryId($fieldCountryId)
													)
												);
												unset($formatter);
												if ($addressValue !== '')
												{
													$typeId = $addressData['TYPE_ID'] ?? 0;
													$descriptions = EntityAddressType::getDescriptions(
														EntityAddressType::getAvailableIds()
													);
													if (isset($descriptions[$typeId]))
													{
														$addressValue = $descriptions[$typeId].':<br>'.$addressValue;
													}
												}

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
					'ADDRESS_ONLY',
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
							if (is_string($fieldName) && $fieldName <> '' &&
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
					if (!is_string($fieldTitle) || $fieldTitle == '')
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

						if (!is_string($fieldTitle) || $fieldTitle == '')
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
							if (!is_string($addressTypeLabel) || $addressTypeLabel == '')
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
				$rqListFieldMap = [];
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
								case 'ADDRESS_ONLY':
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
									if ($this->isRqListField($fieldName))
									{
										if (!is_array($rqListFieldMap[$fieldName][$countryId]))
										{
											$rqListFieldMap[$fieldName][$countryId] = [];
											foreach ($this->getRqListFieldItems($fieldName, $countryId) as $item)
											{
												if (!isset($rqListFieldMap[$fieldName][$countryId][$item['VALUE']]))
												{
													$rqListFieldMap[$fieldName][$countryId][$item['VALUE']] =
														$item['NAME']
													;
												}
											}
											unset($item);
										}
										$value = $rqListFieldMap[$fieldName][$countryId][$value] ?? "";
									}
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
				unset($rqListFieldMap);
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
				CCrmOwnerType::Requisite, array_keys($requisiteListById),
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
									case 'ADDRESS_ONLY':
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
					if (!is_string($fieldTitle) || $fieldTitle == '')
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
						if (!is_string($fieldTitle) || $fieldTitle == '')
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

		$addressTypes = null;

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
					if ($addressTypes === null)
					{
						$addressTypes = EntityAddressType::getAllDescriptions();
					}
					foreach ($requisiteFields[EntityRequisite::ADDRESS] as $addressTypeId => $address)
					{
						// format full address
						$formatter = AddressFormatter::getSingleInstance();
						$formatId = RequisiteAddressFormatter::getFormatByCountryId($countryId);
						if ($exportType === 'excel')
						{
							$fullAddressValue = $formatter->formatTextMultilineSpecialchar($address, $formatId);
						}
						else
						{
							$fullAddressValue = $formatter->formatTextMultiline($address, $formatId);
						}
						unset($formatter, $formatId);
						$element = array(
							EntityRequisite::ADDRESS.'_TYPE' => isset($addressTypes[$addressTypeId]) ?
								$addressTypes[$addressTypeId] : $addressTypeId,
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
	public function importEntityRequisite($entityTypeId, $entityId, $dupControlType, $presetId = 0, $fields = [])
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
				case CCrmOwnerType::Company:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_COMPANY_NOT_EXISTS', array('#ID#' => $entityId));
					$errCode = self::ERR_COMPANY_NOT_EXISTS;
					break;
				case CCrmOwnerType::Contact:
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
				case CCrmOwnerType::Company:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_ACCESS_DENIED_COMPANY_UPDATE', array('#ID#' => $entityId));
					$errCode = self::ERR_ACCESS_DENIED_COMPANY_UPDATE;
					break;
				case CCrmOwnerType::Contact:
					$errMsg = GetMessage('CRM_REQUISITE_ERR_ACCESS_DENIED_CONTACT_UPDATE', array('#ID#' => $entityId));
					$errCode = self::ERR_ACCESS_DENIED_CONTACT_UPDATE;
					break;
			}
			$result->addError(new Main\Error($errMsg, $errCode));
			return $result;
		}

		$entityTypeName = CCrmOwnerType::GetDescription($entityTypeId);
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
		$addressFields = array(
			'ADDRESS_1',
			'ADDRESS_2',
			'CITY',
			'POSTAL_CODE',
			'REGION',
			'PROVINCE',
			'COUNTRY',
			'COUNTRY_CODE',
			'LOC_ADDR_ID'
		);
		$addressTypeMap = array_fill_keys(EntityAddressType::getAllIDs(), true);
		if (is_array($fields)
			&& is_array($fields[self::ADDRESS])
			&& !empty($fields[self::ADDRESS]))
		{
			foreach ($fields[self::ADDRESS] as $addrTypeId => $address)
			{
				if (isset($addressTypeMap[$addrTypeId]) && !RequisiteAddress::isEmpty($address))
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
					EntityAddress::register(CCrmOwnerType::Requisite, $requisiteId, $addrTypeId, $address);
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
				'ACTIVE' => 'Y',
				'ADDRESS_ONLY' => 'N'
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

		$result['ENTITY_TYPE_ID'] = isset($r['ENTITY_TYPE_ID']) ? (int)$r['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
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
		if($entityTypeId === CCrmOwnerType::Company)
		{
			$personType = $countryCode === 'RU' ? 'COMPANY' : 'LEGALENTITY';
		}
		elseif($entityTypeId === CCrmOwnerType::Contact)
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

		if($entityTypeId !== CCrmOwnerType::Company && $entityTypeId !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$optionValue = Main\Config\Option::get('crm', 'requisite_default_presets');
		if ($optionValue !== '')
			$optionValue = unserialize($optionValue, ['allowed_classes' => false]);
		if (!is_array($optionValue))
			$optionValue = array();

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
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
		if($entityTypeId !== CCrmOwnerType::Company && $entityTypeId !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
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
			$optionValue = unserialize($optionValue, ['allowed_classes' => false]);
		if (!is_array($optionValue))
			$optionValue = array();

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$optionValue[$entityTypeName] = $presetId;
		Main\Config\Option::set('crm', 'requisite_default_presets', serialize($optionValue));
	}

	public static function getDuplicateCriterionFieldsMap()
	{
		if (self::$duplicateCriterionFieldsMap === null)
		{
			self::$duplicateCriterionFieldsMap = [
				1 => [      // ru
					'RQ_INN',
					'RQ_OGRN',
					'RQ_OGRNIP',
				],
				4 => [      // by
					'RQ_INN',
				],
				6 => [      // kz
					'RQ_INN',
					'RQ_BIN',
				],
				14 => [     // ua
					'RQ_INN',
					'RQ_EDRPOU',
				],
				34 => [     // br
					'RQ_CNPJ',
					'RQ_CPF',
					'RQ_IDENT_DOC_NUM',
				],
				46 => [     // de
					'RQ_INN',
				],
				77 => [     // co
					'RQ_INN',
				],
				110 => [    // pl
					'RQ_INN',
					'RQ_VAT_ID',
				],
				132 => [    // fr
					'RQ_VAT_ID',
				],
				122 => [    // us
					'RQ_VAT_ID',
				],
			];
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

		if (!($entityTypeId === CCrmOwnerType::Company || $entityTypeId === CCrmOwnerType::Contact)
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

		if($entityTypeId !== CCrmOwnerType::Company && $entityTypeId !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
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

	public static function getPresetWithAddressMap(array $options = [])
	{
		if (static::$presetsWithAddressMap === null
			|| (isset($options['reset']) && $options['reset'] === true))
		{
			$result = [];
			$preset = EntityPreset::getSingleInstance();
			$res = $preset->getList(array(
				'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
				'filter' => ['=ENTITY_TYPE_ID' => EntityPreset::Requisite],
				'select' => ['ID', 'SETTINGS']
			));
			while ($row = $res->fetch())
			{
				if (is_array($row['SETTINGS']))
				{
					foreach ($preset->settingsGetFields($row['SETTINGS']) as $fieldInfo)
					{
						if (isset($fieldInfo['FIELD_NAME']) && $fieldInfo['FIELD_NAME'] === EntityRequisite::ADDRESS)
						{
							$result[(int)$row['ID']] = true;
							break;
						}
					}
				}
			}
			static::$presetsWithAddressMap = $result;
		}

		return static::$presetsWithAddressMap;
	}

	public function resetPresetWithAddressMap()
	{
		static::$presetsWithAddressMap = null;
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
				? (int)$link['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
			$entityID = isset($link['ENTITY_ID'])
				? (int)$link['ENTITY_ID'] : 0;
			$requisiteID = isset($link['REQUISITE_ID'])
				? (int)$link['REQUISITE_ID'] : 0;

			if(!(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0 && $requisiteID > 0))
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

	public static function setDef($entityTypeId, $entityId, $requisiteId,
		$bankDetailId = null, $ignorePermissions = false)
	{
		$result = false;

		$entityTypeId = $entityTypeId > 0 ? (int)$entityTypeId : 0;
		$entityId = $entityId > 0 ? (int)$entityId : 0;
		$requisiteId = $requisiteId > 0 ? (int)$requisiteId : 0;
		$isBankDetailIdSet = ($bankDetailId !== null);
		$bankDetailId = $bankDetailId > 0 ? (int)$bankDetailId : 0;
		$ignorePermissions = (bool)$ignorePermissions;

		if ($entityTypeId > 0
			&& ($entityTypeId === CCrmOwnerType::Company
				|| $entityTypeId === CCrmOwnerType::Contact)
			&& $entityId > 0 && $requisiteId > 0)
		{

			$requisite = EntityRequisite::getSingleInstance();
			if ($requisite->validateEntityExists($entityTypeId, $entityId)
				&& ($ignorePermissions || $requisite->checkUpdatePermission($requisiteId)))
			{
				$settings = $requisite->loadSettings($entityTypeId, $entityId);
				if (!is_array($settings))
				{
					$settings = [];
				}
				$skipChangeBankDetailId = false;
				if (!$isBankDetailIdSet)
				{
					$prevRequisiteId =  0;
					if (isset($settings['REQUISITE_ID_SELECTED']) && $settings['REQUISITE_ID_SELECTED'] > 0)
					{
						$prevRequisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
					}
					$isRequisiteChange = ($prevRequisiteId !== $requisiteId);
					if (!$isRequisiteChange
						&& isset($settings['BANK_DETAIL_ID_SELECTED'])
						&& $settings['BANK_DETAIL_ID_SELECTED'] > 0)
					{
						$skipChangeBankDetailId = true;
					}
				}
				$settings['REQUISITE_ID_SELECTED'] = $requisiteId;
				if (!$skipChangeBankDetailId)
				{
					$settings['BANK_DETAIL_ID_SELECTED'] = $bankDetailId;
				}
				$requisite->saveSettings($entityTypeId, $entityId, $settings);
				EntityAddress::setDef(CCrmOwnerType::Requisite, $requisiteId);
				$result = true;
			}
		}

		return $result;
	}

	public static function getRequisiteFeedbackFormParams()
	{
		if (!Main\Loader::includeModule('ui'))
		{
			return null;
		}
		global $USER;
		$feedbackForm = new \Bitrix\UI\Form\FeedbackForm('requisites');
		$feedbackForm->setFormParams([
			['zones' => ['ru', 'by', 'kz'], 'id' => '183', 'lang' => 'ru', 'sec' => 'nrv9xe'],
			['zones' => ['en'], 'id' => '191', 'lang' => 'en', 'sec' => 'qrumu5'],
			['zones' => ['de'], 'id' => '189', 'lang' => 'de', 'sec' => 'ma40xr'],
			['zones' => ['ua'], 'id' => '193', 'lang' => 'ua', 'sec' => 'v8upd2'],
			['zones' => ['com.br'], 'id' => '185', 'lang' => 'br', 'sec' => 'wa4dt8'],
			['zones' => ['es'], 'id' => '187', 'lang' => 'la', 'sec' => 'nnyr0e'],
		]);
		$email = '';
		if(is_object($USER))
		{
			$email = $USER->GetEmail();
		}
		$domain = defined('BX24_HOST_NAME') ?
			BX24_HOST_NAME : Main\Config\Option::get('main', 'server_name', '');
		$feedbackForm->setPresets([
			'c_email' => $email,
			'from_domain' => $domain,
		]);
		return $feedbackForm->getJsObjectParams();
	}

	public static function getCountryAddressZoneMap()
	{
		if (self::$countryAddressZoneMap === null)
		{
			self::$countryAddressZoneMap = [
				1 => 'ru',
				4 => 'by',
				6 => 'kz',
				14 => 'ua',
				34 => 'br',
				46 => 'de',
				77 => 'co',
				110 => 'pl',
				132 => 'fr',
				122 => 'en',
			];
		}

		return self::$countryAddressZoneMap;
	}

	public static function getAddressZoneByCountry(int $countryId) : string
	{
		$result = '';

		$countryAddressZoneMap = self::getCountryAddressZoneMap();
		if (isset($countryAddressZoneMap[$countryId]))
		{
			$result = $countryAddressZoneMap[$countryId];
		}

		return $result;
	}

	public static function onAfterPresetAdd(\Bitrix\Main\ORM\Event $event): void
	{
		$params = is_object($event) ? $event->getParameters() : [];
		if (is_array($params) && is_array($params['fields']))
		{
			$requisite = EntityRequisite::getSingleInstance();
			$requisite->processPresetSettingsChange($params['fields']);
		}
	}

	public static function onAfterPresetUpdate(\Bitrix\Main\ORM\Event $event): void
	{
		$params = is_object($event) ? $event->getParameters() : [];
		if (is_array($params) && is_array($params['fields']))
		{
			$requisite = EntityRequisite::getSingleInstance();
			$requisite->processPresetSettingsChange($params['fields']);
		}
	}

	public static function onAfterPresetDelete(\Bitrix\Main\ORM\Event $event): void
	{
		// No need to do anything yet
		// it is possible to delete list field items in the future
	}

	public static function getFileFields(): array
	{
		$fileFields = [];
		foreach (self::getFieldsInfo() as $fieldId => $fieldInfo)
		{
			if (in_array($fieldInfo['TYPE'], ['file', 'image']))
			{
				$fileFields[] = $fieldId;
			}
		}

		return $fileFields;
	}
}
