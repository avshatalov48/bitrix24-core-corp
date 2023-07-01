<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Crm\Integrity\ActualRanking;
use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateBankDetailCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicateRequisiteCriterion;
use Bitrix\Crm\Merger\EntityMerger;

abstract class BaseEntityMatcher
{
	const GENERAL_FIELD_TYPE = 1;
	const MULTI_FIELD_TYPE = 2;
	const REQUISITE_FIELD_TYPE = 3;
	const BANK_DETAIL_FIELD_TYPE = 4;

	const DUPLICATE_CONTROL_MODES = [
		'MERGE' => 'MERGE',
		'REPLACE' => 'REPLACE',
		'NONE' => 'NONE'
	];

	protected $fields = [];
	protected $properties = [];

	protected $relation = [];
	protected $duplicateControl = null;
	protected $assignedById = null;

	public function __construct()
	{
		$this->duplicateControl = static::getDefaultDuplicateMode();
	}

	public static function getDefaultDuplicateMode()
	{
		return self::DUPLICATE_CONTROL_MODES['MERGE'];
	}

	/**
	 * @return \CCrmContact|\CCrmCompany|string
	 */
	abstract protected function getEntityClassName();

	/**
	 * @return string
	 */
	abstract protected function getEntityMergerClassName();

	/**
	 * @return int|void
	 */
	abstract protected function getEntityTypeId();

	/**
	 * @return string|void
	 */
	abstract protected function getEntityTypeName();

	protected function getEntity()
	{
		$entityClassName = $this->getEntityClassName();

		return new $entityClassName(false);
	}

	protected function getEntityFields()
	{
		return $this->fields;
	}

	protected function prepareFields()
	{
		$fields = [];

		if (!empty($this->properties[self::GENERAL_FIELD_TYPE]))
		{
			foreach ($this->properties[self::GENERAL_FIELD_TYPE] as $property)
			{
				if (!empty($property['CRM_FIELD_CODE']))
				{
					$this->prepareGeneralField($fields, $property);
				}
			}
		}

		if (!empty($this->properties[self::MULTI_FIELD_TYPE]))
		{
			foreach ($this->properties[self::MULTI_FIELD_TYPE] as $property)
			{
				if (!empty($property['CRM_FIELD_CODE']))
				{
					$this->prepareMultiField($fields, $property);
				}
			}
		}

		return $fields;
	}

	/**
	 * @param $fields
	 * @param $property
	 *
	 * Common fields for both entities:
	 *
	 * ID
	 * DATE_CREATE
	 * DATE_MODIFY
	 * CREATED_BY_ID
	 * MODIFY_BY_ID
	 * ASSIGNED_BY_ID
	 * OPENED
	 * ADDRESS
	 * COMMENTS
	 * LEAD_ID
	 * WEBFORM_ID
	 * ORIGINATOR_ID
	 * ORIGIN_ID
	 * ORIGIN_VERSION
	 * HAS_PHONE
	 * HAS_EMAIL
	 * HAS_IMOL
	 * SEARCH_CONTENT
	 */
	protected function prepareGeneralField(&$fields, $property)
	{
		$fields[$property['CRM_FIELD_CODE']] = $property['VALUE'];
	}

	protected function prepareMultiField(&$fields, $property)
	{
		$fieldMulti = \CCrmFieldMulti::ParseComplexName($property['CRM_FIELD_CODE'], true);

		if (!empty($fieldMulti))
		{
			if (!isset($fields['FM'][$fieldMulti['TYPE']]) || !is_array($fields['FM'][$fieldMulti['TYPE']]))
			{
				$fields['FM'][$fieldMulti['TYPE']] = [];
			}

			if (!is_array($property['VALUE']))
			{
				$property['VALUE'] = [$property['VALUE']];
			}

			foreach ($property['VALUE'] as $value)
			{
				$fieldName = 'n'.count($fields['FM'][$fieldMulti['TYPE']]);
				$fields['FM'][$fieldMulti['TYPE']][$fieldName] = $fieldMulti + ['VALUE' => $value];
			}
		}
	}

	protected function getDuplicateSearchParameters()
	{
		return [];
	}

	protected function getRequisitesDuplicateCriteria()
	{
		$duplicateCriteria = [];

		if (!empty($this->properties[self::REQUISITE_FIELD_TYPE]))
		{
			foreach ($this->properties[self::REQUISITE_FIELD_TYPE] as $property)
			{
				if (!empty($property['VALUE']))
				{
					$duplicateCriteria[] = new DuplicateRequisiteCriterion(
						FieldSynchronizer::getDefaultCountryId(),
						$property['CRM_FIELD_CODE'],
						$property['VALUE']
					);
				}
			}
		}

		if (!empty($this->properties[self::BANK_DETAIL_FIELD_TYPE]))
		{
			foreach ($this->properties[self::BANK_DETAIL_FIELD_TYPE] as $property)
			{
				if (!empty($property['VALUE']))
				{
					$duplicateCriteria[] = new DuplicateBankDetailCriterion(
						FieldSynchronizer::getDefaultCountryId(),
						$property['CRM_FIELD_CODE'],
						$property['VALUE']
					);
				}
			}
		}

		return $duplicateCriteria;
	}

	protected function getDuplicatesList($fields)
	{
		$list = [];

		$duplicateCriteria = ActualEntitySelector::createDuplicateCriteria($fields, $this->getDuplicateSearchParameters());
		$duplicateCriteria = array_merge($duplicateCriteria, $this->getRequisitesDuplicateCriteria());

		/** @var DuplicateCriterion $criterion */
		foreach ($duplicateCriteria as $criterion)
		{
			/** @var Duplicate $duplicate */
			$duplicate = $criterion->find($this->getEntityTypeId());

			if (!empty($duplicate))
			{
				$list = array_merge($list, $duplicate->getEntityIDs());
			}
		}

		return array_unique($list);
	}

	protected function loadOriginalFields($entityId)
	{
		$fields = [];

		if (!empty($entityId))
		{
			$entityClassName = $this->getEntityClassName();

			$dbResult = $entityClassName::GetListEx(
				[],
				['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				['*', 'UF_*']
			);
			$fields = $dbResult->Fetch();

			if (!is_array($fields))
			{
				$fields = [];
			}
		}

		return $fields;
	}

	protected function loadOriginalMultiFields($entityId)
	{
		$multiFields = [];

		if (!empty($entityId))
		{
			$entityMultiFields = \CCrmFieldMulti::GetEntityFields($this->getEntityTypeName(), $entityId, null);

			foreach ($entityMultiFields as $multiField)
			{
				$multiFields[$multiField['TYPE_ID']][$multiField['ID']] = [
					'VALUE' => $multiField['VALUE'],
					'VALUE_TYPE' => $multiField['VALUE_TYPE']
				];
			}
		}

		return $multiFields;
	}

	protected function getFieldsToUpdate($entityId, $fields)
	{
		$mergerClassName = $this->getEntityMergerClassName();

		switch ($this->duplicateControl)
		{
			case self::DUPLICATE_CONTROL_MODES['MERGE']:
				$entityFields = $this->loadOriginalFields($entityId);
				$entityFields['FM'] = $this->loadOriginalMultiFields($entityId);

				/** @var EntityMerger $merger */
				$merger = new $mergerClassName(0, false);
				$merger->mergeFields($fields, $entityFields, false, ['ENABLE_UPLOAD' => true]);

				break;
			case self::DUPLICATE_CONTROL_MODES['REPLACE']:
				$entityFields = [
					'FM' => $this->loadOriginalMultiFields($entityId)
				];

				/** @var EntityMerger $merger */
				$merger = new $mergerClassName(0, false);
				$merger->mergeFields($fields, $entityFields, false, ['ENABLE_UPLOAD' => true]);

				break;
			default:
				$entityFields = [];
		}

		return $entityFields;
	}

	public function matchRequisites($entityId)
	{
		$requisites = [];

		if (!empty($this->properties[self::REQUISITE_FIELD_TYPE]))
		{
			$matcher = new RequisiteMatcher($this->getEntityTypeId(), $entityId);

			$matcher->setProperties($this->properties[self::REQUISITE_FIELD_TYPE]);
			$matcher->setDuplicateControlMode($this->duplicateControl);

			$requisites = $matcher->match();
		}

		$bankDetails = [];

		if (!empty($this->properties[self::BANK_DETAIL_FIELD_TYPE]))
		{
			$matcher = new BankDetailMatcher($this->getEntityTypeId(), $entityId);

			$matcher->setProperties($this->properties[self::BANK_DETAIL_FIELD_TYPE]);
			$matcher->setDuplicateControlMode($this->duplicateControl);

			if (!empty($requisites))
			{
				$matcher->setMatchedRequisites($requisites);
			}

			$bankDetails = $matcher->match();
		}

		return [$requisites, $bankDetails];
	}

	public function setProperties(array $properties)
	{
		$this->properties = $properties;
		$this->fields = $this->prepareFields();
	}

	public function setAssignedById($assignedById)
	{
		$this->assignedById = $assignedById;
	}

	public function setDuplicateControlMode($mode)
	{
		if (array_key_exists($mode, self::DUPLICATE_CONTROL_MODES))
		{
			$this->duplicateControl = self::DUPLICATE_CONTROL_MODES[$mode];
		}
	}

	protected function isDuplicateControlEnabled()
	{
		return in_array(
			$this->duplicateControl,
			[
				self::DUPLICATE_CONTROL_MODES['MERGE'],
				self::DUPLICATE_CONTROL_MODES['REPLACE']
			]
		);
	}

	public function setRelation($entityTypeId, $entityId)
	{
		$this->relation[$entityTypeId] = $entityId;
	}

	public function search()
	{
		$fields = $this->getEntityFields();
		$duplicates = $this->getDuplicatesList($fields);

		$ranking = new ActualRanking();
		$ranking->rank($this->getEntityTypeId(), $duplicates);

		return (int)$ranking->getEntityId();
	}

	protected function getFieldsToCreate(array $fields)
	{
		if (!empty($this->assignedById))
		{
			$fields['ASSIGNED_BY_ID'] = $this->assignedById;
		}

		return $fields;
	}

	public function create()
	{
		$fields = $this->getEntityFields();
		$fields = $this->getFieldsToCreate($fields);

		// ToDo DISABLE_USER_FIELD_CHECK && REGISTER_SONET_EVENT options?
		$entityId = $this->getEntity()->Add($fields, true, [
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true
		]);

		if (!empty($entityId))
		{
			$this->matchRequisites($entityId);

			$arErrors = [];

			\CCrmBizProcHelper::AutoStartWorkflows(
				$this->getEntityTypeId(),
				$entityId,
				\CCrmBizProcEventType::Create,
				$arErrors
			);
		}

		return $entityId;
	}

	public function update($entityId)
	{
		$updateState = false;

		$fields = $this->getEntityFields();
		$fieldsToUpdate = $this->getFieldsToUpdate($entityId, $fields);

		if (!empty($fieldsToUpdate))
		{
			$updateState = $this->getEntity()->Update($entityId, $fieldsToUpdate, true, true, [
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true
			]);
		}

		$this->matchRequisites($entityId);

		return $updateState;
	}

	public function match()
	{
		$entityId = null;

		if ($this->isDuplicateControlEnabled())
		{
			$duplicateId = $this->search();

			if (!empty($duplicateId) && $this->update($duplicateId))
			{
				$entityId = $duplicateId;
			}
		}

		if (empty($entityId))
		{
			$entityId = $this->create();
		}

		return $entityId ? $entityId : null;
	}
}