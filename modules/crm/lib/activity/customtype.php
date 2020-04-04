<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Activity\Entity\CustomTypeTable;
use Bitrix\Crm\Activity\Provider\Custom as Provider;
use Bitrix\Crm\Entry\AddException;
use Bitrix\Crm\Entry\UpdateException;
use Bitrix\Crm\Entry\DeleteException;

Loc::loadMessages(__FILE__);

class CustomType
{
	const MAX_ITEM_COUNT = 50;
	private static $existMap = array();
	private static $fieldInfos = null;
	/**
	 * Get metadata fields.
	 * @return array
	 */
	public static function getFieldsInfo()
	{
		if(!self::$fieldInfos)
		{
			self::$fieldInfos = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'CREATED_DATE' => array(
					'TYPE' => 'date',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'SORT' => array(
					'TYPE' => 'integer'
				)
			);
		}
		return self::$fieldInfos;
	}
	/**
	 * Check if entry already exists.
	 * @param int $ID Entry ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function exists($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return false;
		}

		if(isset(self::$existMap[$ID]))
		{
			return self::$existMap[$ID];
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = CustomTypeTable::getList(array('select' => array('ID'), 'filter' => array('=ID' => $ID)));
		return (self::$existMap[$ID] = is_array($dbResult->fetch()));
	}
	/**
	 * Retrieve entry by ID.
	 * @param int $ID Entry ID.
	 * @return array|null
	 */
	public static function get($ID)
	{
		/** @var Main\DB\Result $dbResult */
		$dbResult = CustomTypeTable::getById($ID);
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}
	/**
	 * Get entry list
	 * @param array $params List params.
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($params)
	{
		return CustomTypeTable::getList($params);
	}
	/**
	 * Get all entries
	 * @param array $sort Sorting params.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAll(array $sort = null)
	{
		/** @var Main\DB\Result $dbResult */
		$dbResult = CustomTypeTable::getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'limit' => self::MAX_ITEM_COUNT
			)
		);

		$all = array();
		while($fields = $dbResult->fetch())
		{
			$all[] = $fields;
		}

		if($sort !== null)
		{
			$effectiveSort = array();
			foreach($sort as $fieldID => $order)
			{
				if($order === SORT_DESC || $order === SORT_ASC)
				{
					$effectiveSort[$fieldID] = $order;
				}
				else
				{
					$effectiveSort[$fieldID] = strcasecmp($order, 'DESC') ? SORT_DESC : SORT_ASC;
				}
			}
			if(!empty($effectiveSort))
			{
				Main\Type\Collection::sortByColumn($all, $effectiveSort);
			}
		}
		return $all;
	}
	/**
	 * Get total quantity
	 * @return int
	 */
	public static function getCount()
	{
		return CustomTypeTable::getCount();
	}

	public static function getUserFieldTypes()
	{
		/** @var Main\DB\Result $dbResult */
		$dbResult = CustomTypeTable::getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'limit' => self::MAX_ITEM_COUNT
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$ID = (int)$fields['ID'];
			$entityID = self::prepareUserFieldEntityID($ID);
			$result[$entityID] = array(
				'ID' => $entityID,
				'NAME' => str_replace('#NAME#', $fields['NAME'], Loc::getMessage('CRM_ACTIVITY_CUST_TYPE_UF_NAME')),
				'DESC' => str_replace('#NAME#', $fields['NAME'], Loc::getMessage('CRM_ACTIVITY_CUST_TYPE_UF_DESCR'))
			);
		}

		return $result;
	}

	/**
	 * Prepare JavaScript infos
	 * @param array $IDs Entry IDs to add.
	 * @return array
	 */
	public static function getJavaScriptInfos(array $IDs = null, $encode = false)
	{
		$infos = array();
		$map = is_array($IDs) ? array_fill_keys($IDs, true) : null;
		foreach(self::getAll() as $entry)
		{
			$ID = (int)$entry['ID'];
			if($map === null || isset($map[$ID]))
			{
				$name = isset($entry['NAME']) ? $entry['NAME'] : '';
				if($name !== '' && $encode)
				{
					$name = htmlspecialcharsbx($name);
				}
				$infos[] = array('id' => $ID, 'name' => ($name !== '' ? $name : $ID));
			}
		}
		return $infos;
	}

	/**
	 * Add new entry.
	 * @param array $fields Fields.
	 * @return int
	 * @throws \Bitrix\Crm\Entry\AddException
	 */
	public static function add(array $fields)
	{
		$data = array();
		$data['NAME'] = isset($fields['NAME']) ? trim($fields['NAME']) : '';
		if($data['NAME'] === '')
		{
			throw new AddException(\CCrmOwnerType::CustomActivityType, array("Field 'NAME' is required."));
		}

		$data['SORT'] = isset($fields['SORT']) ? max((int)$fields['SORT'], 0) : 0;
		$data['CREATED_DATE'] = new Date();

		/** @var Main\Entity\AddResult $result */
		$result = null;
		try
		{
			$result = CustomTypeTable::add($data);
		}
		catch(\Exception $ex)
		{
			throw new AddException(\CCrmOwnerType::CustomActivityType, array($ex->getMessage()), 0, '', 0, $ex);
		}

		if(!$result->isSuccess())
		{
			throw new AddException(\CCrmOwnerType::CustomActivityType, $result->getErrorMessages());
		}

		return $result->getId();
	}
	/**
	 * Update entry fields.
	 * @param int $ID Entry ID.
	 * @param array $fields Entry fields.
	 * @return void
	 * @throws \Bitrix\Crm\Entry\UpdateException
	 */
	public static function update($ID, array $fields)
	{
		$data = array();

		$name = isset($fields['NAME']) ? trim($fields['NAME']) : '';
		if($name !== '')
		{
			$data['NAME'] = $name;
		}

		if(isset($fields['SORT']))
		{
			$data['SORT'] = max((int)$fields['SORT'], 0);
		}

		if(empty($data))
		{
			return;
		}

		/** @var Main\Entity\UpdateResult $result */
		$result = null;
		try
		{
			$result = CustomTypeTable::update($ID, $data);
		}
		catch(\Exception $ex)
		{
			throw new UpdateException(\CCrmOwnerType::CustomActivityType, $ID, array($ex->getMessage()), 0, '', 0, $ex);
		}

		if(!$result->isSuccess())
		{
			throw new UpdateException(\CCrmOwnerType::CustomActivityType, $ID, $result->getErrorMessages());
		}
	}
	/**
	 * Delete entry by ID.
	 * @param int $ID Entry ID.
	 * @return void
	 * @throws \Bitrix\Crm\Entry\DeleteException
	 */
	public static function delete($ID)
	{
		if(!self::exists($ID))
		{
			throw new DeleteException(\CCrmOwnerType::CustomActivityType, $ID, array(), DeleteException::NOT_FOUND);
		}

		if(self::hasDependencies($ID))
		{
			throw new DeleteException(\CCrmOwnerType::CustomActivityType, $ID, array(), DeleteException::DEPENDENCIES_FOUND);
		}

		/** @var Main\Entity\DeleteResult $result */
		$result = null;
		try
		{
			$result = CustomTypeTable::delete($ID);
		}
		catch(\Exception $ex)
		{
			throw new DeleteException(\CCrmOwnerType::CustomActivityType, $ID, array($ex->getMessage()), 0, '', 0, $ex);
		}

		$success = $result->isSuccess();
		if(!$success)
		{
			throw new DeleteException(\CCrmOwnerType::CustomActivityType, $ID, $result->getErrorMessages());
		}

		unset(self::$existMap[$ID]);
		self::eraseUserFields($ID);
	}
	/**
	 * Check if exist activities are bound to spacified type ID.
	 * @param int $ID Entry ID.
	 * @return bool
	 */
	public static function hasDependencies($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}
		$ID = max($ID, 0);

		$query = new Query(ActivityTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=PROVIDER_ID', Provider::PROVIDER_ID);
		$query->addFilter('=PROVIDER_TYPE_ID', $ID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		return is_array($dbResult->fetch());
	}
	/**
	 * Prepare User field entity ID.
	 * @param integer $ID Entry ID.
	 * @return string
	 */
	public static function prepareUserFieldEntityID($ID)
	{
		return $ID > 0 ? "CRM_ACT_CUST_{$ID}" : '';
	}
	/**
	 * Remove all user fields those are belong to specified activity type entry.
	 * @param integer $ID Actiity type entry ID (must be greater than 0).
	 * @return void
	 */
	public static function eraseUserFields($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return;
		}

		/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		$entityID = self::prepareUserFieldEntityID($ID);
		$manager = new \CCrmFields($USER_FIELD_MANAGER, $entityID);
		foreach($manager->GetFields() as $field)
		{
			$manager->DeleteField($field['ID']);
		}

		//region Clear components cache
		/** \CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;
		$CACHE_MANAGER->ClearByTag('crm_fields_list_'.$entityID);
		//endregion
	}
}