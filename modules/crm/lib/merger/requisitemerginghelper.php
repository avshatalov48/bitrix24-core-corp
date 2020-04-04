<?php

namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;

class RequisiteMergingHelper
{
	const ACTION_UNDEFINED = 0;
	const ACTION_REQUISITE_UPDATE = 1;
	const ACTION_REQUISITE_MOVE_DEPENDECIES = 2;
	const ACTION_REQUISITE_DELETE = 3;
	const ACTION_REQUISITE_REBIND = 4;
	const ACTION_BANK_DETAIL_UPDATE = 5;
	const ACTION_BANK_DETAIL_MOVE_DEPENDECIES = 6;
	const ACTION_BANK_DETAIL_DELETE = 7;
	const ACTION_BANK_DETAIL_REBIND = 8;

	private $entityTypeID;
	private $seedID;
	private $targID;

	private $presetList = array();
	private $bankDetailFieldsMap = array();
	
	private $actionList = array();

	private static $requisite = null;
	private static $bankDetail = null;

	public function __construct($entityTypeID, $seedID, $targID)
	{
		$entityTypeID = (int)$entityTypeID;
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}
		$this->entityTypeID = $entityTypeID;

		if(!is_int($seedID))
		{
			throw new Main\ArgumentTypeException('seedID', 'integer');
		}
		$this->seedID = $seedID;

		if(!is_int($targID))
		{
			throw new Main\ArgumentTypeException('targID', 'integer');
		}
		$this->targID = $targID;
	}

	protected function getRequisite()
	{
		if (self::$requisite === null)
		{
			self::$requisite = new Crm\EntityRequisite();
		}

		return self::$requisite;
	}

	protected function getBankDetail()
	{
		if (self::$bankDetail === null)
		{
			self::$bankDetail = new Crm\EntityBankDetail();
		}

		return self::$bankDetail;
	}

	/**
	 * Get entity requisites
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return array
	 */
	protected function getEntityRequisites($entityID, $roleID)
	{
		$requisiteList = array();

		$requisite = new Crm\EntityRequisite();
		$res = $requisite->getList(
			array(
				'order' => array('SORT', 'ID'),
				'select' => array('ID', 'PRESET_ID'),
				'filter' => array('=ENTITY_TYPE_ID' => $this->entityTypeID, '=ENTITY_ID' => $entityID)
			)
		);
		while ($row = $res->fetch())
		{
			$requisiteId = (int)$row['ID'];
			$presetId = (int)$row['PRESET_ID'];
			if ($requisiteId > 0 && $presetId > 0)
			{
				if (!isset($requisiteList[$requisiteId]))
					$requisiteList[$requisiteId] = array(
						'ID' => $requisiteId,
						'ENTITY_TYPE_ID' => $this->entityTypeID,
						'ENTITY_ID' => $entityID,
						'PRESET_ID' => $presetId
					);
				if (!isset($this->presetList[$presetId]))
					$this->presetList[$presetId] = array('ID' => $presetId);
			}
		}

		$allowedFieldsMap = array_fill_keys(
			array_merge(
				$requisite->getRqFields(),
				$requisite->getUserFields()
			),
			true
		);

		// load presets
		$selectMap = array('ID' => true, 'PRESET_ID' => true);
		if (!empty($this->presetList))
		{
			$preset = new Crm\EntityPreset();
			$res = $preset->getList(array(
				'order' => array('SORT' => 'ASC'),
				'filter' => array(
					'=ENTITY_TYPE_ID' => Crm\EntityPreset::Requisite,
					'@ID' => array_keys($this->presetList)
				),
				'select' => array('ID', 'COUNTRY_ID', 'SETTINGS')
			));
			while ($row = $res->fetch())
			{
				$id = (int)$row['ID'];
				$countryId = (int)$row['COUNTRY_ID'];
				if ($countryId > 0 && is_array($row['SETTINGS']))
				{
					$presetFieldsMap = array();
					foreach ($preset->settingsGetFields($row['SETTINGS']) as $fieldInfo)
					{
						if (isset($fieldInfo['FIELD_NAME']) && isset($allowedFieldsMap[$fieldInfo['FIELD_NAME']]))
							$presetFieldsMap[$fieldInfo['FIELD_NAME']] = true;
					}
					$presetFields = array_keys($presetFieldsMap);
					unset($presetFieldsMap);
					foreach ($presetFields as $fieldName)
					{
						if (!isset($selectMap[$fieldName]))
							$selectMap[$fieldName] = true;
					}
					$this->presetList[$id] = array(
						'ID' => $id,
						'COUNTRY_ID' => $countryId,
						'FIELDS' => $presetFields
					);
				}
			}
		}

		unset($allowedFieldsMap);

		if (!empty($requisiteList))
		{
			// load requisites
			$requisiteBasicFields = array_keys(Crm\EntityRequisite::getBasicFieldsInfo());
			foreach ($requisiteBasicFields as $fieldName)
			{
				if (!isset($selectMap[$fieldName]))
					$selectMap[$fieldName] = true;
			}
			$res = $requisite->getList(
				array(
					'order' => array('SORT', 'ID'),
					'select' => array_keys($selectMap),
					'filter' => array('=ENTITY_TYPE_ID' => $this->entityTypeID, '=ENTITY_ID' => $entityID)
				)
			);
			while ($row = $res->fetch())
			{
				$requisiteId = (int)$row['ID'];
				$presetId = (int)$row['PRESET_ID'];
				if ($requisiteId > 0 && $presetId > 0
					&& isset($requisiteList[$requisiteId]) && isset($this->presetList[$presetId]))
				{
					foreach ($requisiteBasicFields as $fieldName)
					{
						if (!isset($requisiteList[$requisiteId][$fieldName]))
							$requisiteList[$requisiteId][$fieldName] = $row[$fieldName];
					}
					$requisiteList[$requisiteId]['PRESET_COUNTRY_ID'] = $this->presetList[$presetId]['COUNTRY_ID'];

					if (is_array($this->presetList[$presetId]['FIELDS'])
						&& !empty($this->presetList[$presetId]['FIELDS']))
					{
						foreach ($this->presetList[$presetId]['FIELDS'] as $fieldName)
						{
							if ($fieldName === Crm\EntityRequisite::ADDRESS)
							{
								// load addresses
								$requisiteList[$requisiteId][$fieldName] =
									Crm\EntityRequisite::getAddresses($requisiteId);
							}
							else
							{
								$requisiteList[$requisiteId][$fieldName] = $row[$fieldName];
							}
						}
					}
				}
			}

			// load bank detail fields map
			$countryMap = array();
			$bankDetailList = array();
			$bankDetail = new Crm\EntityBankDetail();
			$selectMap = array('ID' => true, 'ENTITY_ID' => true, 'COUNTRY_ID' => true);
			$res = $bankDetail->getList(
				array(
					'order' => array('ENTITY_ID', 'SORT', 'ID'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
						'@ENTITY_ID' => array_keys($requisiteList)
					),
					'select' => array('ID', 'ENTITY_ID', 'COUNTRY_ID')
				)
			);
			while ($row = $res->fetch())
			{
				$bankDetailId = (int)$row['ID'];
				$requisiteId = (int)$row['ENTITY_ID'];
				$countryId = (int)$row['COUNTRY_ID'];
				if ($countryId <= 0 && $requisiteId > 0 && is_array($requisiteList[$requisiteId])
					&& isset($requisiteList[$requisiteId]['PRESET_COUNTRY_ID']))
				{
					$countryId = (int)$requisiteList[$requisiteId]['PRESET_COUNTRY_ID'];
				}
				if ($bankDetailId > 0 && $requisiteId > 0 && $countryId > 0)
				{
					if (!isset($bankDetailList[$bankDetailId]))
						$bankDetailList[$bankDetailId] = array(
							'ID' => $bankDetailId,
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'ENTITY_ID' => $requisiteId,
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
					if (!isset($this->bankDetailFieldsMap[$countryId]))
						$this->bankDetailFieldsMap[$countryId] = $fields;
				}
			}
			unset($countryMap);

			// load bank details
			$bankDetailBasicFields = array_keys(Crm\EntityBankDetail::getBasicFieldsInfo());
			foreach ($bankDetailBasicFields as $fieldName)
			{
				if (!isset($selectMap[$fieldName]))
					$selectMap[$fieldName] = true;
			}
			$res = $bankDetail->getList(
				array(
					'order' => array('ENTITY_ID', 'SORT', 'ID'),
					'filter' => array(
						'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
						'@ENTITY_ID' => array_keys($requisiteList)
					),
					'select' => array_keys($selectMap)
				)
			);
			while ($row = $res->fetch())
			{
				$bankDetailId = (int)$row['ID'];
				if (is_array($bankDetailList[$bankDetailId]))
				{
					foreach ($bankDetailBasicFields as $fieldName)
					{
						if (!isset($bankDetailList[$bankDetailId][$fieldName]))
							$bankDetailList[$bankDetailId][$fieldName] = $row[$fieldName];
					}
					foreach ($this->bankDetailFieldsMap[$bankDetailList[$bankDetailId]['COUNTRY_ID']] as $fieldName)
						$bankDetailList[$bankDetailId][$fieldName] = $row[$fieldName];
					$requisiteId = $bankDetailList[$bankDetailId]['ENTITY_ID'];
					if (!is_array($requisiteList[$requisiteId]['BD']))
						$requisiteList[$requisiteId]['BD'] = array();
					$requisiteList[$requisiteId]['BD'][$bankDetailId] = $bankDetailList[$bankDetailId];
				}
			}
			unset($bankDetailList);
		}

		return $requisiteList;
	}

	protected function isRequisiteNoConflicts(array $targRequisite, array $seedRequisite)
	{
		$result = false;
		$seedPresetId = isset($seedRequisite['PRESET_ID']) ? (int)$seedRequisite['PRESET_ID'] : 0;
		$targPresetId = isset($targRequisite['PRESET_ID']) ? (int)$targRequisite['PRESET_ID'] : 0;

		if ($seedPresetId > 0 && $seedPresetId === $targPresetId)
		{
			$presetId = $seedPresetId;

			if (is_array($this->presetList[$presetId]) && is_array($this->presetList[$presetId]['FIELDS'])
				&& !empty($this->presetList[$presetId]['FIELDS']))
			{
				$result = true;
				$resultModified = false;
				foreach ($this->presetList[$presetId]['FIELDS'] as $fieldName)
				{
					$seedValue = isset($seedRequisite[$fieldName]) ? $seedRequisite[$fieldName] : null;
					$targValue = isset($targRequisite[$fieldName]) ? $targRequisite[$fieldName] : null;
					if ($fieldName === Crm\EntityRequisite::ADDRESS)
					{
						$isSeedHasAddresses = (is_array($seedValue) && !empty($seedValue));
						$isTargHasAddresses = (is_array($targValue) && !empty($targValue));
						if ($isSeedHasAddresses && $isTargHasAddresses)
						{
							foreach ($seedValue as $seedAddrTypeId => $seedAddr)
							{
								foreach ($targValue as $targAddrTypeId => $targAddr)
								{
									if ($seedAddrTypeId === $targAddrTypeId)
									{
										if (!Crm\RequisiteAddress::areEquals($seedAddr, $targAddr))
										{
											$result = false;
											$resultModified = true;
											break;
										}
									}
								}
								if ($resultModified)
									break;
							}
						}
					}
					else
					{
						$isSeedValueEmpty = ($seedValue === null
							|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
						$isTargValueEmpty = ($targValue === null
							|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
						if (!$isSeedValueEmpty && !$isTargValueEmpty && $seedValue != $targValue)
						{
							$result = false;
							$resultModified = true;
						}
					}
					if ($resultModified)
						break;
				}
			}
		}

		return $result;
	}

	protected function isRequisiteMatchingByKey(array $targRequisite, array $seedRequisite)
	{
		$result = false;

		$dupFieldsMap = Crm\EntityRequisite::getDuplicateCriterionFieldsMap();

		$seedPresetId = isset($seedRequisite['PRESET_ID']) ? (int)$seedRequisite['PRESET_ID'] : 0;
		$targPresetId = isset($targRequisite['PRESET_ID']) ? (int)$targRequisite['PRESET_ID'] : 0;

		if ($seedPresetId > 0 && $seedPresetId === $targPresetId)
		{
			$presetId = $seedPresetId;

			if (is_array($this->presetList[$presetId]) && is_array($this->presetList[$presetId]['FIELDS'])
				&& !empty($this->presetList[$presetId]['FIELDS']))
			{
				$countryId = 0;
				if (isset($this->presetList[$presetId]['COUNTRY_ID']))
					$countryId = (int)$this->presetList[$presetId]['COUNTRY_ID'];
				if ($countryId > 0 && is_array($dupFieldsMap[$countryId]))
				{
					foreach ($dupFieldsMap[$countryId] as $fieldName)
					{
						if (in_array($fieldName, $this->presetList[$presetId]['FIELDS'], true))
						{
							$seedValue = isset($seedRequisite[$fieldName]) ? $seedRequisite[$fieldName] : null;
							$targValue = isset($targRequisite[$fieldName]) ? $targRequisite[$fieldName] : null;
							$isSeedValueEmpty = ($seedValue === null
								|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
							$isTargValueEmpty = ($targValue === null
								|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
							if (!$isSeedValueEmpty && !$isTargValueEmpty && $seedValue == $targValue)
							{
								$result = true;
								break;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	protected function isRequsiiteSuitableForMerging(array $targRequisite, array $seedRequisite)
	{
		if ($this->isRequisiteNoConflicts($targRequisite, $seedRequisite))
			return true;

		if ($this->isRequisiteMatchingByKey($targRequisite, $seedRequisite))
			return true;

		return false;
	}

	protected function mergeRequisite(array &$targRequisite, array $seedRequisite)
	{
		$seedPresetId = isset($seedRequisite['PRESET_ID']) ? (int)$seedRequisite['PRESET_ID'] : 0;
		$targPresetId = isset($targRequisite['PRESET_ID']) ? (int)$targRequisite['PRESET_ID'] : 0;

		if (!($seedPresetId > 0 && $seedPresetId === $targPresetId))
			throw new Main\SystemException('To merge, the requisites must be tied to the same preset.');

		$presetId = $seedPresetId;
		unset($seedPresetId, $targPresetId);

		$fieldsToUpdate = array();
		$addressesToRegister = array();

		if (is_array($this->presetList[$presetId]) && is_array($this->presetList[$presetId]['FIELDS'])
			&& !empty($this->presetList[$presetId]['FIELDS']))
		{
			foreach ($this->presetList[$presetId]['FIELDS'] as $fieldName)
			{
				$seedValue = isset($seedRequisite[$fieldName]) ? $seedRequisite[$fieldName] : array();
				$targValue = isset($targRequisite[$fieldName]) ? $targRequisite[$fieldName] : array();
				if ($fieldName === Crm\EntityRequisite::ADDRESS)
				{
					$isSeedHasAddresses = (is_array($seedValue) && !empty($seedValue));
					if ($isSeedHasAddresses)
					{
						if (!is_array($targRequisite[$fieldName]))
							$targRequisite[$fieldName] = array();
						foreach ($seedValue as $seedAddrTypeId => $seedAddr)
						{
							if (!isset($targValue[$seedAddrTypeId]))
							{
								$targRequisite[$fieldName][$seedAddrTypeId] = $seedAddr;
								$addressesToRegister[$seedAddrTypeId] = $seedAddr;
							}
						}
					}
				}
				else
				{
					$isSeedValueEmpty = ($seedValue === null
						|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
					$isTargValueEmpty = ($targValue === null
						|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
					if (!$isSeedValueEmpty && $isTargValueEmpty)
					{
						$targRequisite[$fieldName] = $seedValue;
						$fieldsToUpdate[$fieldName] = $seedValue;
					}
				}
			}
		}

		$this->mergeBankDetails($targRequisite, $seedRequisite);

		if (!empty($fieldsToUpdate))
		{
			$fields = $fieldsToUpdate;
			if (!empty($addressesToRegister))
			{
				$fields[Crm\EntityRequisite::ADDRESS] = $addressesToRegister;
			}
			$this->addMergeAction(
				self::ACTION_REQUISITE_UPDATE,
				array(
					'TARG_REQUISITE_ID' => $targRequisite['ID'],
					'FIELDS' => $fields
				)
			);
			unset($fields);
		}

		$this->addMergeAction(
			self::ACTION_REQUISITE_MOVE_DEPENDECIES,
			array(
				'TARG_ENTITY_TYPE_ID' => $targRequisite['ENTITY_TYPE_ID'],
				'TARG_ENTITY_ID' => $targRequisite['ENTITY_ID'],
				'SEED_ENTITY_TYPE_ID' => $seedRequisite['ENTITY_TYPE_ID'],
				'SEED_ENTITY_ID' => $seedRequisite['ENTITY_ID'],
				'TARG_REQUISITE_ID' => $targRequisite['ID'],
				'SEED_REQUISITE_ID' => $seedRequisite['ID']
			)
		);

		$this->addMergeAction(
			self::ACTION_REQUISITE_DELETE,
			array(
				'SEED_REQUISITE_ID' => $seedRequisite['ID']
			)
		);
	}

	protected function rebindRequisite(array &$targRequisites, array $seedRequisite)
	{
		$targRequisites[$seedRequisite['ID']] = $seedRequisite;
		$this->addMergeAction(
			self::ACTION_REQUISITE_REBIND,
			array(
				'TARG_ENTITY_TYPE_ID' => $this->entityTypeID,
				'TARG_ENTITY_ID' => $this->targID,
				'SEED_REQUISITE_ID' => $seedRequisite['ID']
			)
		);
	}

	protected function isBankDetailNoConflicts(array $targBankDetail, array $seedBankDetail)
	{
		$result = false;
		$seedCountryId = isset($seedBankDetail['COUNTRY_ID']) ? (int)$seedBankDetail['COUNTRY_ID'] : 0;
		$targCountryId = isset($targBankDetail['COUNTRY_ID']) ? (int)$targBankDetail['COUNTRY_ID'] : 0;

		if ($seedCountryId > 0 && $seedCountryId === $targCountryId)
		{
			$countryId = $seedCountryId;

			if (is_array($this->bankDetailFieldsMap[$countryId]) && !empty($this->bankDetailFieldsMap[$countryId]))
			{
				$result = true;
				foreach ($this->bankDetailFieldsMap[$countryId] as $fieldName)
				{
					$seedValue = isset($seedBankDetail[$fieldName]) ? $seedBankDetail[$fieldName] : null;
					$targValue = isset($targBankDetail[$fieldName]) ? $targBankDetail[$fieldName] : null;
					$isSeedValueEmpty = ($seedValue === null
						|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
					$isTargValueEmpty = ($targValue === null
						|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
					if (!$isSeedValueEmpty && !$isTargValueEmpty && $seedValue != $targValue)
					{
						$result = false;
						break;
					}
				}
			}
		}

		return $result;
	}

	protected function isBankDetailMatchingByKey(array $targBankDetail, array $seedBankDetail)
	{
		$result = false;

		$dupFieldsMap = Crm\EntityBankDetail::getDuplicateCriterionFieldsMap();

		$seedCountryId = isset($seedBankDetail['COUNTRY_ID']) ? (int)$seedBankDetail['COUNTRY_ID'] : 0;
		$targCountryId = isset($targBankDetail['COUNTRY_ID']) ? (int)$targBankDetail['COUNTRY_ID'] : 0;

		if ($seedCountryId > 0 && $seedCountryId === $targCountryId)
		{
			$countryId = $seedCountryId;

			if (is_array($this->bankDetailFieldsMap[$countryId]) && !empty($this->bankDetailFieldsMap[$countryId])
				&& is_array($dupFieldsMap[$countryId]))
			{
				foreach ($dupFieldsMap[$countryId] as $fieldName)
				{
					if (in_array($fieldName, $this->bankDetailFieldsMap[$countryId], true))
					{
						$seedValue = isset($seedBankDetail[$fieldName]) ? $seedBankDetail[$fieldName] : null;
						$targValue = isset($targBankDetail[$fieldName]) ? $targBankDetail[$fieldName] : null;
						$isSeedValueEmpty = ($seedValue === null
							|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
						$isTargValueEmpty = ($targValue === null
							|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
						if (!$isSeedValueEmpty && !$isTargValueEmpty && $seedValue == $targValue)
						{
							$result = true;
							break;
						}
					}
				}
			}
		}

		return $result;
	}

	protected function isBankDetailSuitableForMerging(array $targBankDetail, array $seedBankDetail)
	{
		if ($this->isBankDetailNoConflicts($targBankDetail, $seedBankDetail))
			return true;

		if ($this->isBankDetailMatchingByKey($targBankDetail, $seedBankDetail))
			return true;

		return false;
	}

	protected function mergeBankDetail(array $targRequisite, array $seedRequisite,
										array &$targBankDetail, array $seedBankDetail)
	{
		$seedCountryId = isset($seedBankDetail['COUNTRY_ID']) ? (int)$seedBankDetail['COUNTRY_ID'] : 0;
		$targCountryId = isset($targBankDetail['COUNTRY_ID']) ? (int)$targBankDetail['COUNTRY_ID'] : 0;

		if (!($seedCountryId > 0 && $seedCountryId === $targCountryId))
			throw new Main\SystemException('To merge, the bank details must relate to one country.');

		$countryId = $seedCountryId;
		unset($seedCountryId, $targCountryId);

		$fieldsToUpdate = array();

		if (is_array($this->bankDetailFieldsMap[$countryId]) && !empty($this->bankDetailFieldsMap[$countryId]))
		{
			foreach ($this->bankDetailFieldsMap[$countryId] as $fieldName)
			{
				$seedValue = isset($seedBankDetail[$fieldName]) ? $seedBankDetail[$fieldName] : array();
				$targValue = isset($targBankDetail[$fieldName]) ? $targBankDetail[$fieldName] : array();
				$isSeedValueEmpty = ($seedValue === null
					|| (is_string($seedValue) ? strlen($seedValue) === 0 : empty($seedValue)));
				$isTargValueEmpty = ($targValue === null
					|| (is_string($targValue) ? strlen($targValue) === 0 : empty($targValue)));
				if (!$isSeedValueEmpty && $isTargValueEmpty)
				{
					$targBankDetail[$fieldName] = $seedValue;
					$fieldsToUpdate[$fieldName] = $seedValue;
				}
			}
		}

		if (!empty($fieldsToUpdate))
		{
			$this->addMergeAction(
				self::ACTION_BANK_DETAIL_UPDATE,
				array(
					'TARG_BANK_DETAIL_ID' => $targBankDetail['ID'],
					'FIELDS' => $fieldsToUpdate
				)
			);
		}

		$this->addMergeAction(
			self::ACTION_BANK_DETAIL_MOVE_DEPENDECIES,
			array(
				'TARG_ENTITY_TYPE_ID' => $targRequisite['ENTITY_TYPE_ID'],
				'TARG_ENTITY_ID' => $targRequisite['ENTITY_ID'],
				'SEED_ENTITY_TYPE_ID' => $seedRequisite['ENTITY_TYPE_ID'],
				'SEED_ENTITY_ID' => $seedRequisite['ENTITY_ID'],
				'TARG_REQUISITE_ID' => $targBankDetail['ENTITY_ID'],
				'TARG_BANK_DETAIL_ID' => $targBankDetail['ID'],
				'SEED_REQUISITE_ID' => $seedBankDetail['ENTITY_ID'],
				'SEED_BANK_DETAIL_ID' => $seedBankDetail['ID']
			)
		);

		$this->addMergeAction(
			self::ACTION_BANK_DETAIL_DELETE,
			array(
				'SEED_BANK_DETAIL_ID' => $seedBankDetail['ID']
			)
		);
	}

	protected function rebindBankDetail(array &$targRequisite, $seedBankDetail)
	{
		$targRequisite['BD'][$seedBankDetail['ID']] = $seedBankDetail;
		$this->addMergeAction(
			self::ACTION_BANK_DETAIL_REBIND,
			array(
				'TARG_ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
				'TARG_ENTITY_ID' => $targRequisite['ID'],
				'SEED_BANK_DETAIL_ID' => $seedBankDetail['ID']
			)
		);
	}

	protected function mergeBankDetails(array &$targRequisite, array $seedRequisite)
	{
		if (is_array($seedRequisite['BD']) && !empty($seedRequisite['BD']) && is_array($targRequisite['BD']))
		{
			foreach ($seedRequisite['BD'] as $seedBankDetailId => $seedBankDetail)
			{
				$seedBankDetailMerged = false;
				foreach ($targRequisite['BD'] as $targBankDetailId => &$targBankDetail)
				{
					if ($this->isBankDetailSuitableForMerging($targBankDetail, $seedBankDetail))
					{
						$this->mergeBankDetail($targRequisite, $seedRequisite, $targBankDetail, $seedBankDetail);
						$seedBankDetailMerged = true;
						break;
					}
				}
				unset($targBankDetail);
				if (!$seedBankDetailMerged)
					$this->rebindBankDetail($targRequisite, $seedBankDetail);
			}
		}
	}

	protected function addMergeAction($mergeActionType, $params)
	{
		$this->actionList[] = array(
			'TYPE' => $mergeActionType,
			'PARAMS' => $params
		);
	}

	protected function processMergeActions()
	{
		foreach ($this->actionList as $action)
		{
			switch ($action['TYPE'])
			{
				case self::ACTION_REQUISITE_UPDATE:
					$this->getRequisite()->update(
						$action['PARAMS']['TARG_REQUISITE_ID'],
						$action['PARAMS']['FIELDS']
					);
					break;
				case self::ACTION_REQUISITE_MOVE_DEPENDECIES:
					Crm\Requisite\EntityLink::moveDependencies(
						$action['PARAMS']['TARG_REQUISITE_ID'],
						$action['PARAMS']['SEED_REQUISITE_ID']
					);
					break;
				case self::ACTION_REQUISITE_DELETE:
					$this->getRequisite()->delete(
						$action['PARAMS']['SEED_REQUISITE_ID']
					);
					break;
				case self::ACTION_REQUISITE_REBIND:
					Crm\EntityRequisite::rebindRequisite(
						$action['PARAMS']['TARG_ENTITY_TYPE_ID'],
						$action['PARAMS']['TARG_ENTITY_ID'],
						$action['PARAMS']['SEED_REQUISITE_ID']
					);
					break;
				case self::ACTION_BANK_DETAIL_UPDATE:
					$this->getBankDetail()->update(
						$action['PARAMS']['TARG_BANK_DETAIL_ID'],
						$action['PARAMS']['FIELDS']
					);
					break;
				case self::ACTION_BANK_DETAIL_MOVE_DEPENDECIES:
					Crm\Requisite\EntityLink::moveDependencies(
						$action['PARAMS']['TARG_ENTITY_TYPE_ID'],
						$action['PARAMS']['TARG_ENTITY_ID'],
						$action['PARAMS']['SEED_ENTITY_TYPE_ID'],
						$action['PARAMS']['SEED_ENTITY_ID'],
						$action['PARAMS']['TARG_REQUISITE_ID'],
						$action['PARAMS']['SEED_REQUISITE_ID'],
						$action['PARAMS']['TARG_BANK_DETAIL_ID'],
						$action['PARAMS']['SEED_BANK_DETAIL_ID']
					);
					break;
				case self::ACTION_BANK_DETAIL_DELETE:
					$this->getBankDetail()->delete(
						$action['PARAMS']['SEED_BANK_DETAIL_ID']
					);
					break;
				case self::ACTION_BANK_DETAIL_REBIND:
					Crm\EntityBankDetail::rebindBankDetail(
						$action['PARAMS']['TARG_ENTITY_TYPE_ID'],
						$action['PARAMS']['TARG_ENTITY_ID'],
						$action['PARAMS']['SEED_BANK_DETAIL_ID']
					);
					break;
			}
		}
	}
	
	public function merge()
	{
		$seedRequisites = $this->getEntityRequisites($this->seedID, EntityMerger::ROLE_SEED);
		$targRequisites = $this->getEntityRequisites($this->targID, EntityMerger::ROLE_TARG);

		if (!empty($seedRequisites))
		{
			foreach ($seedRequisites as $seedRequisiteId => $seedRequisite)
			{
				$seedRequisiteMerged = false;
				foreach ($targRequisites as $targRequisiteId => &$targRequisite)
				{
					if ($this->isRequsiiteSuitableForMerging($targRequisite, $seedRequisite))
					{
						$this->mergeRequisite($targRequisite, $seedRequisite);
						$seedRequisiteMerged = true;
						break;
					}
				}
				unset($targRequisite);
				if (!$seedRequisiteMerged)
					$this->rebindRequisite($targRequisites, $seedRequisite);
			}
		}

		$this->processMergeActions();
	}
}