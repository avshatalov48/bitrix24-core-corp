<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserFieldDataProvider extends DataProvider
{
	/** @var EntitySettings|null */
	protected $settings = null;
	/** @var \CCrmUserType|null  */
	protected $userTypeManager = null;

	function __construct(EntitySettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return EntitySettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get user field entity ID.
	 * @return string
	 */
	public function getUserFieldEntityID()
	{
		return $this->settings->getUserFieldEntityID();
	}

	/**
	 * Get user field manager.
	 * @return \CCrmUserType
	 */
	protected function getUserTypeManager()
	{
		global $USER_FIELD_MANAGER;
		if($this->userTypeManager === null)
		{
			$this->userTypeManager = new \CCrmUserType($USER_FIELD_MANAGER, $this->getUserFieldEntityID());
		}
		return $this->userTypeManager;
	}

	/**
	 * Get custom fields defined for entity
	 * @return array
	 */
	protected function getUserFields()
	{
		return $this->getUserTypeManager()->GetFields();
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$result = array();
		foreach($this->getUserFields() as $fieldName => $userField)
		{
			if ($userField['SHOW_FILTER'] === 'N' || $userField['USER_TYPE']['BASE_TYPE'] === 'file')
			{
				continue;
			}

			$typeID = $userField['USER_TYPE']['USER_TYPE_ID'];
			//$isMultiple = isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y';

			$fieldLabel = isset($userField['LIST_FILTER_LABEL']) ? $userField['LIST_FILTER_LABEL'] : '';
			if($fieldLabel === '')
			{
				if(isset($userField['LIST_COLUMN_LABEL']))
				{
					$fieldLabel = $userField['LIST_COLUMN_LABEL'];
				}
				elseif(isset($userField['EDIT_FORM_LABEL']))
				{
					$fieldLabel = $userField['EDIT_FORM_LABEL'];
				}
			}

			if($typeID === 'employee')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'custom_entity',
						'name' => $fieldLabel,
						'partial' => true
					)
				);
			}
			elseif($typeID === 'string' || $typeID === 'url' || $typeID === 'address' || $typeID === 'money')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array('type' => 'text', 'name' => $fieldLabel)
				);
				continue;
			}
			elseif($typeID === 'integer' || $typeID === 'double')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array('type' => 'number', 'name' => $fieldLabel)
				);
				continue;
			}
			elseif($typeID === 'boolean')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'checkbox',
						'name' => $fieldLabel,
						'data' => array('valueType' => 'numeric')
					)
				);
			}
			elseif($typeID === 'datetime' || $typeID === 'date')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'date',
						'name' => $fieldLabel,
						'data' => array('time' => $typeID === 'datetime')
					)
				);
			}
			elseif($typeID === 'enumeration'
				|| $typeID === 'iblock_element'
				|| $typeID === 'iblock_section'
				|| $typeID === 'crm_status'
			)
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'list',
						'name' => $fieldLabel,
						'partial' => true
					)
				);
			}
			elseif($typeID === 'crm')
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'custom_entity',
						'name' => $fieldLabel,
						'partial' => true
					)
				);
			}
			else
			{
				$result[$fieldName] = $this->createField(
					$fieldName,
					array(
						'type' => 'custom',
						'name' => $fieldLabel,
						'data' => array('value' => '')
					)
				);
			}
		}
		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID)
	{
		$userFields = $this->getUserFields();
		if(!isset($userFields[$fieldID]))
		{
			return null;
		}

		$userField = $userFields[$fieldID];
		$typeID = $userField['USER_TYPE']['USER_TYPE_ID'];
		$isMultiple = isset($userField['MULTIPLE']) && $userField['MULTIPLE'] === 'Y';
		$ID = $userField['ID'];
		if($typeID === 'employee')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => strtolower($fieldID), 'FIELD_ID' => $fieldID)
				)
			);
		}
		elseif($typeID === 'enumeration')
		{
			$entity = new \CUserFieldEnum();
			$dbResult = $entity->GetList(array('SORT' => 'ASC'), array('USER_FIELD_ID' => $ID));

			$items = array();
			if(is_object($dbResult))
			{
				while($ary = $dbResult->Fetch())
				{
					$items[$ary['ID']] = $ary['VALUE'];
				}
			}

			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $items
			);
		}
		elseif($typeID === 'iblock_element')
		{
			$entity = new \CUserTypeIBlockElement();
			$dbResult = $entity->GetList($userField);

			$items = array();
			if(is_object($dbResult))
			{
				$count = 0;
				while($ary = $dbResult->Fetch())
				{
					$items[$ary['ID']] = $ary['NAME'];

					if(++$count > 500)
					{
						break;
					}
				}
			}

			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $items
			);
		}
		elseif($typeID === 'iblock_section')
		{
			$entity = new \CUserTypeIBlockSection();
			$dbResult = $entity->GetList($userField);

			$items = array();
			if(is_object($dbResult))
			{
				$count = 0;
				while($ary = $dbResult->Fetch())
				{
					$items[$ary['ID']] = isset($ary['DEPTH_LEVEL']) && $ary['DEPTH_LEVEL']  > 1
						? str_repeat('. ', ($ary['DEPTH_LEVEL'] - 1)).$ary['NAME'] : $ary['NAME'];

					if(++$count > 500)
					{
						break;
					}
				}
			}

			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $items
			);
		}
		elseif($typeID === 'crm')
		{
			$settings = isset($userField['SETTINGS']) && is_array($userField['SETTINGS'])
				? $userField['SETTINGS'] : array();

			$entityTypeNames = array();
			$supportedEntityTypeNames = array(
				\CCrmOwnerType::LeadName,
				\CCrmOwnerType::DealName,
				\CCrmOwnerType::ContactName,
				\CCrmOwnerType::CompanyName
			);
			foreach($supportedEntityTypeNames as $entityTypeName)
			{
				if(isset($settings[$entityTypeName]) && $settings[$entityTypeName] === 'Y')
				{
					$entityTypeNames[] = $entityTypeName;
				}
			}

			return array(
				'params' => array('multiple' => 'N'),
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => strtolower($fieldID),
						'FIELD_ID' => $fieldID,
						'ENTITY_TYPE_NAMES' => $entityTypeNames,
						'IS_MULTIPLE' => $isMultiple
					)
				)
			);
		}
		elseif($typeID === 'crm_status')
		{
			$items = array();
			if(isset($userField['SETTINGS'])
				&& is_array($userField['SETTINGS'])
				&& isset($userField['SETTINGS']['ENTITY_TYPE'])
			)
			{
				$entityType = $userField['SETTINGS']['ENTITY_TYPE'];
				if($entityType !== '')
				{
					$items = \CCrmStatus::GetStatusList($entityType);
				}
			}

			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $items
			);
		}
		return null;
	}

	/**
	 * Create filter field.
	 * @param string $fieldID Field ID.
	 * @param array|null $params Field parameters (optional).
	 * @return Field
	 */
	protected function createField($fieldID, array $params = null)
	{
		return new Field($this, $fieldID, $params);
	}
}