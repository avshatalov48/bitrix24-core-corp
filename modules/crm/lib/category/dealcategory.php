<?php
namespace Bitrix\Crm\Category;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Category\Entity\DealCategoryTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Color\DealStageColorScheme;
use Bitrix\Crm\Entry\AddException;
use Bitrix\Crm\Entry\UpdateException;
use Bitrix\Crm\Entry\DeleteException;

class DealCategory
{
	/** @var bool */
	private static $langIncluded = false;
	private static $stageList = array();
	private static $existMap = array();
	private static $all = null;
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
				'IS_LOCKED' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
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
		$dbResult = DealCategoryTable::getList(array('select' => array('ID'), 'filter' => array('=ID' => $ID)));
		return (self::$existMap[$ID] = is_array($dbResult->fetch()));
	}

	/**
	 * Check if entry exists and is not locked.
	 * @param int $ID Entry ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function isEnabled($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return false;
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategoryTable::getList(array('select' => array('ID', 'IS_LOCKED'), 'filter' => array('=ID' => $ID)));

		$fields = $dbResult->fetch();
		return is_array($fields) && (!isset($fields['IS_LOCKED']) || $fields['IS_LOCKED'] === 'N');
	}

	/**
	 * Retrieve entry by ID.
	 * @param int $ID Entry ID.
	 * @return array|null
	 */
	public static function get($ID)
	{
		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategoryTable::getById($ID);
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}

	/**
	 * Retrive entry name
	 * @param int $ID Entry ID.
	 * @return string
	 */
	public static function getName($ID)
	{
		if($ID < 0)
		{
			return '';
		}

		foreach(self::getAll(true) as $entry)
		{
			if($ID == $entry['ID'])
			{
				return isset($entry['NAME']) ? $entry['NAME'] : "[{$ID}]";
			}
		}

		return "[{$ID}]";
	}

	/**
	 * Retrive items for select options
	 * @param bool $enableDefault Add default category (default category is virtual and has ID = 0).
	 * @return array
	 */
	public static function getSelectListItems($enableDefault = true)
	{
		$results = array();
		foreach(self::getAll($enableDefault) as $entry)
		{
			$results[$entry['ID']] = $entry['NAME'];
		}
		return $results;
	}

	/**
	 * Prepare select options for specified items
	 * @param array $IDs Item IDs.
	 * @return array
	 */
	public static function prepareSelectListItems(array $IDs)
	{
		$map = array_fill_keys($IDs, true);
		$results = array();
		foreach(self::getAll(true) as $entry)
		{
			if(isset($map[$entry['ID']]))
			{
				$results[$entry['ID']] = $entry['NAME'];
			}
		}
		return $results;
	}

	/**
	 * Prepare stage filter info
	 * @param array $infos Destination filter data.
	 * @param array|null $params Field params
	 * @return array
	 */
	public static function getStageFilterInfo(array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		if(!isset($params['id']))
		{
			$params['id'] = 'STAGE_ID';
		}

		if(!isset($params['id']))
		{
			$params['name'] = GetMessage('CRM_DEAL_CATEGORY_STAGE_FILTER');
		}

		return array_merge(array('type' => 'group_list', 'groups' => self::getStageGroupInfos()), $params);
	}

	/**
	 * Prepare stage grouping data according to categories
	 * @return array
	 */
	public static function getStageGroupInfos()
	{
		$result = array(array('items' => self::getStageList(0)));
		foreach(self::getAll(false) as $entry)
		{
			$result[] = array('name' => $entry['NAME'], 'items' => self::getStageList($entry['ID']));
		}
		return $result;
	}

	/**
	 * Get all entry IDs
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAllIDs()
	{
		$results = array();
		foreach(self::getAll(true) as $entry)
		{
			$results[] = (int)$entry['ID'];
		}
		return $results;
	}

	/**
	 * Get name of default category.
	 * Returns user-defined value if assigned or system default value.
	 * @return string
	 * @throws Main\ArgumentNullException
	 */
	public static function getDefaultCategoryName()
	{
		$name = Main\Config\Option::get('crm', 'default_deal_category_name', '', '');
		if($name === '')
		{
			self::includeLangFile();
			$name = GetMessage('CRM_DEAL_CATEGORY_DEFAULT');
		}
		return $name;
	}

	/**
	 * Assign user-defined value for name of default category.
	 * @param string $name Default Category Name.
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function setDefaultCategoryName($name)
	{
		$name = trim($name);
		if($name === '')
		{
			Main\Config\Option::delete('crm', array('name' => 'default_deal_category_name'));
		}
		else
		{
			Main\Config\Option::set('crm', 'default_deal_category_name', $name, '');
		}

		if(self::$all !== null)
		{
			self::$all = null;
		}
	}

	/**
	 * Get Sort of default category.
	 * Returns user-defined value if assigned or 0.
	 * @return int
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getDefaultCategorySort()
	{
		return max(
			(int)Main\Config\Option::get('crm', 'default_deal_category_sort', '0', ''),
			0
		);
	}

	/**
	 * Assign user-defined value for sort of default category.
	 * @param int $sort Default category sort.
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function setDefaultCategorySort($sort)
	{
		if(!is_int($sort))
		{
			$sort = (int)$sort;
		}

		if($sort <= 0)
		{
			Main\Config\Option::delete('crm', array('name' => 'default_deal_category_sort'));
		}
		else
		{
			Main\Config\Option::set('crm', 'default_deal_category_sort', $sort, '');
		}

		if(self::$all !== null)
		{
			self::$all = null;
		}
	}

	/**
	 * Check if exist one or more user-defined categories.
	 * @return bool
	 */
	public static function isCustomized()
	{
		return count(self::getAll(false)) > 0;
	}

	/**
	 * Get all entries
	 * @param bool $enableDefault Add default category (default category is virtual and has ID = 0).
	 * @param array $sort Sorting params.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getAll($enableDefault = false, array $sort = null)
	{
		if(self::$all === null)
		{
			/** @var Main\DB\Result $dbResult */
			$dbResult = DealCategoryTable::getList(
				array(
					'filter' => array('=IS_LOCKED' => 'N'),
					'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
				)
			);

			$defaultSort = self::getDefaultCategorySort();
			$default = array(
				'ID' => 0,
				'NAME' => self::getDefaultCategoryName(),
				'SORT' => $defaultSort,
				'IS_DEFAULT' => true
			);

			self::$all = array();
			$defaultIndex = -1;
			while($fields = $dbResult->fetch())
			{
				self::$all[] = $fields;
				if($defaultIndex < 0 && $fields['SORT'] > $defaultSort)
				{
					$defaultIndex = count(self::$all) - 1;
				}
			}

			if($defaultIndex >= 0)
			{
				array_splice(self::$all, $defaultIndex, 0, array($default));
			}
			else
			{
				self::$all[] = $default;
			}
		}

		$results = self::$all;
		if(!$enableDefault)
		{
			$defaultIndex = -1;
			for($i = 0, $length = count($results); $i < $length; $i++)
			{
				if($results[$i]['ID'] === 0)
				{
					$defaultIndex = $i;
					break;
				}
			}

			if($defaultIndex >= 0)
			{
				array_splice($results, $defaultIndex, 1);
			}
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
				Main\Type\Collection::sortByColumn($results, $effectiveSort);
			}
		}
		return $results;
	}

	/**
	 * Get total quantity
	 * @return int
	 */
	public static function getCount()
	{
		return count(self::getAll(true));
	}

	/**
	 * Get entry list
	 * @param array $params List params.
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($params)
	{
		return DealCategoryTable::getList($params);
	}

	/**
	 * Resolve category entry ID by name.
	 * @param string $name Entry name.
	 * @return int
	 */
	public static function resolveByName($name)
	{
		if(!is_string($name))
		{
			return -1;
		}

		if($name === '')
		{
			return 0;
		}

		foreach(self::getAll(true) as $entry)
		{
			if(isset($entry['NAME']) && $entry['NAME'] === $name)
			{
				return (int)$entry['ID'];
			}
		}
		return -1;
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
			throw new AddException(\CCrmOwnerType::DealCategory, array("Field 'NAME' is required."));
		}

		$data['SORT'] = isset($fields['SORT']) ? max((int)$fields['SORT'], 0) : 0;
		$data['CREATED_DATE'] = new Date();

		$limit = RestrictionManager::getDealCategoryLimit();
		$data['IS_LOCKED'] = ($limit > 0 && $limit <= self::getCount()) ? 'Y' : 'N';

		/** @var Main\Entity\AddResult $result */
		$result = null;
		try
		{
			$result = DealCategoryTable::add($data);
		}
		catch(\Exception $ex)
		{
			throw new AddException(\CCrmOwnerType::DealCategory, array($ex->getMessage()), 0, '', 0, $ex);
		}

		if(!$result->isSuccess())
		{
			throw new AddException(\CCrmOwnerType::DealCategory, $result->getErrorMessages());
		}

		$ID = $result->getId();
		self::createDefaultStages($ID);

		//region Setup default rights
		$permissionEntity = DealCategory::convertToPermissionEntityType($ID);

		$role = new \CCrmRole();
		$roleDbResult = $role->GetList();
		while($roleFields = $roleDbResult->Fetch())
		{
			$roleID = (int)$roleFields['ID'];
			$roleRelation = \CCrmRole::GetRolePerms($roleID);
			if(isset($roleRelation[$permissionEntity]))
			{
				continue;
			}

			if(!isset($roleRelation[$permissionEntity]))
			{
				$roleRelation[$permissionEntity] = \CCrmRole::GetDefaultPermissionSet();
			}
			$fields = array('RELATION' => $roleRelation);
			$role->Update($roleID, $fields);
		}
		//endregion

		if(self::$all !== null)
		{
			self::$all = null;
		}

		return $ID;
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

		$limit = RestrictionManager::getDealCategoryLimit();
		if($limit > 0 && $limit <= self::getCount())
		{
			unset($data['IS_LOCKED']);
		}

		if(empty($data))
		{
			return;
		}

		/** @var Main\Entity\UpdateResult $result */
		$result = null;
		try
		{
			$result = DealCategoryTable::update($ID, $data);
		}
		catch(\Exception $ex)
		{
			throw new UpdateException(\CCrmOwnerType::DealCategory, $ID, array($ex->getMessage()), 0, '', 0, $ex);
		}

		if(!$result->isSuccess())
		{
			throw new UpdateException(\CCrmOwnerType::DealCategory, $ID, $result->getErrorMessages());
		}

		if(self::$all !== null)
		{
			self::$all = null;
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
			throw new DeleteException(\CCrmOwnerType::DealCategory, $ID, array(), DeleteException::NOT_FOUND);
		}

		if(self::hasDependencies($ID))
		{
			throw new DeleteException(\CCrmOwnerType::DealCategory, $ID, array(), DeleteException::DEPENDENCIES_FOUND);
		}

		/** @var Main\Entity\DeleteResult $result */
		$result = null;
		try
		{
			$result = DealCategoryTable::delete($ID);
		}
		catch(\Exception $ex)
		{
			throw new DeleteException(\CCrmOwnerType::DealCategory, $ID, array($ex->getMessage()), 0, '', 0, $ex);
		}

		$success = $result->isSuccess();
		if(!$success)
		{
			throw new DeleteException(\CCrmOwnerType::DealCategory, $ID, $result->getErrorMessages());
		}

		unset(self::$existMap[$ID]);
		self::eraseStages($ID);
		self::erasePermissions($ID);
		self::removeColorScheme($ID);

		if(self::$all !== null)
		{
			self::$all = null;
		}
	}

	/**
	 * Check if category has deals.
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

		$query = new Query(DealTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=CATEGORY_ID', $ID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		return is_array($dbResult->fetch());
	}

	/**
	 * Check if specified status entity belong to deal category entry
	 * @param string $entityID Status entity ID.
	 * @return bool
	 */
	public static function hasStatusEntity($entityID)
	{
		return(self::convertFromStatusEntityID($entityID) > 0);
	}

	/**
	 * Add to destination array status entity type infos for all registered deal category entries.
	 * @param array $entityTypes Destination array.
	 */
	public static function prepareStatusEntityInfos(array &$entityTypes,  $enableDefault = false)
	{
		self::includeLangFile();
		foreach(self::getAll($enableDefault) as $entry)
		{
			$ID = $entry['ID'];
			$name = isset($entry['NAME']) ? $entry['NAME'] : '';

			if($name === '')
			{
				$name = $ID;
			}

			if($ID > 0)
			{
				$typeID = self::convertToStatusEntityID($ID);
				$entityTypes[$typeID] =
					array(
						'ID' => $typeID,
						'NAME' => GetMessage('CRM_DEAL_CATEGORY_STATUS_ENTITY', array('#CATEGORY#' => $name)),
						'PARENT_ID' => 'DEAL_STAGE',
						'SEMANTIC_INFO' => \CCrmStatus::GetDealStageSemanticInfo(self::prepareStageNamespaceID($ID))
					);
			}
			elseif($enableDefault)
			{
				$entityTypes['DEAL_STAGE'] =
					array(
						'ID' => 'DEAL_STAGE',
						'NAME' => GetMessage('CRM_DEAL_CATEGORY_STATUS_ENTITY', array('#CATEGORY#' => $name)),
						'SEMANTIC_INFO' => \CCrmStatus::GetDealStageSemanticInfo()
					);
			}
		}
	}

	/**
	 * Convert deal category entry ID to status entity ID.
	 * @param int $ID Entry ID.
	 * @return string
	 */
	public static function convertToStatusEntityID($ID)
	{
		return "DEAL_STAGE_{$ID}";
	}

	/**
	 * Try to convert status entity ID to deal category entry ID.
	 * Returns 0 if entityID is equal to 'DEAL_STAGE'
	 * Returns -1 if conversion failed.
	 * @param string $entityID Status entity ID.
	 * @return int
	 */
	public static function convertFromStatusEntityID($entityID)
	{
		if($entityID === 'DEAL_STAGE')
		{
			return 0;
		}

		if(!(preg_match("/DEAL_STAGE_(\d+)/", $entityID, $m) === 1 && is_array($m) && count($m) === 2))
		{
			return -1;
		}

		return (int)$m[1];
	}

	/**
	 * Get stage infos for specified deal category entry.
	 * @param int $ID Deal category entry ID.
	 * @return string
	 */
	public static function getStatusEntityID($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		return $ID > 0 ? self::convertToStatusEntityID($ID) : 'DEAL_STAGE';
	}

	/**
	 * Create default deal stage set for specified deal category entry.
	 * @param int $ID Deal category ID.
	 * @return void
	 */
	public static function createDefaultStages($ID)
	{
		\CCrmStatus::BulkCreate(
			self::convertToStatusEntityID($ID),
			\CCrmStatus::GetDefaultDealStages(self::prepareStageNamespaceID($ID))
		);
	}

	/**
	 * Prepare Deal stage namespace identifier.
	 * For example if category entry ID is 15 then string "C15" will be returned.
	 * @param int $ID Entry ID.
	 * @return string
	 */
	public static function prepareStageNamespaceID($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		return ($ID > 0 ? "C{$ID}" : "");
	}

	/**
	 * Check if specified Stage ID includes namespace ID of specified Category.
	 * @param string $stageID Stage ID.
	 * @param int $ID Category ID.
	 * @return bool
	 */
	public static function hasStageNamespaceID($stageID, $ID)
	{
		$nid = self::prepareStageNamespaceID($ID);
		return $nid !== '' && strpos($stageID, "{$nid}:") === 0;
	}

	/**
	 * Remove namespace identifier from stage ID.
	 * For example if stage ID is "C15:WON" (where prefix "C15:" is namespace identifier) then string "WON" will be returned.
	 * @param string $stageID Deal stage ID.
	 * @return mixed
	 */
	public static function removeStageNamespaceID($stageID)
	{
		if(preg_match("/C\d+:([a-z0-9_]+)/i", $stageID, $m) === 1 && is_array($m) && count($m) === 2)
		{
			return $m[1];
		}
		return $stageID;
	}

	/**
	 * Prepare unique stage ID.
	 * For example if entry ID is 15 and stage ID is "PREPARATION", then string "C15:PREPARATION" will be returnted.
	 * Where "C15" is namespace ID and "PREPARATION" is specific stage ID.
	 * @param int $ID Entry ID.
	 * @param string $stageID Specific stage ID (for example "NEW" or "WON")
	 * @return string
	 */
	public static function prepareStageID($ID, $stageID)
	{
		$nid = self::prepareStageNamespaceID($ID);
		return $nid !== '' ? "{$nid}:{$stageID}" : $stageID;
	}

	/**
	 * Issue new stage ID.
	 * @param $entityID
	 * @return int
	 * @throws Main\NotSupportedException
	 */
	public static function issueStageID($entityID)
	{
		$ID = self::convertFromStatusEntityID($entityID);
		if($ID <= 0)
		{
			return '';
		}

		$connection = Main\Application::getConnection();
		$entityID = $connection->getSqlHelper()->forSql($entityID);
		//Offset for namespace (for example "C15:")
		$offset = strlen($ID) + 3;
		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$sql = "SELECT SUBSTRING(STATUS_ID, {$offset}) AS MAX_STATUS_ID FROM b_crm_status WHERE ENTITY_ID = '{$entityID}' AND CAST(SUBSTRING(STATUS_ID, {$offset}) AS UNSIGNED) > 0 ORDER BY CAST(SUBSTRING(STATUS_ID, {$offset}) AS UNSIGNED) DESC LIMIT 1";
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$sql = "SELECT TOP 1 SUBSTRING(STATUS_ID, {$offset}, LEN(STATUS_ID)) AS MAX_STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$entityID}' AND CAST((CASE WHEN ISNUMERIC(SUBSTRING(STATUS_ID, {$offset}, LEN(STATUS_ID))) > 0 THEN SUBSTRING(STATUS_ID, {$offset}, LEN(STATUS_ID)) ELSE '0' END) AS INT) > 0 ORDER BY CAST((CASE WHEN ISNUMERIC(SUBSTRING(STATUS_ID, {$offset}, LEN(STATUS_ID))) > 0 THEN STATUS_ID ELSE '0' END) AS INT) DESC";
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$sql = "SELECT SUBSTR(STATUS_ID, {$offset}) AS MAX_STATUS_ID FROM (SELECT STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$entityID}' AND COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?', {$offset})), 0) > 0 ORDER BY COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?', {$offset})), 0) DESC) WHERE ROWNUM <= 1";
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}

		$dbResult = $connection->query($sql);
		$ary = $dbResult->fetch();
		$num = (is_array($ary) && isset($ary['MAX_STATUS_ID']) ? (int)$ary['MAX_STATUS_ID'] : 0) + 1;
		return self::prepareStageID($ID, (string)$num);
	}

	/**
	 * Resolve category entry ID from stage ID.
	 * If stage ID contains namespace ID.
	 * For example if stageID is "C15:NEW", then "C15" is namespace ID and category entry ID is 15.
	 * @param string $stageID Stage ID.
	 * @return int
	 */
	public static function resolveFromStageID($stageID)
	{
		if(!(preg_match("/C(\d+):[a-z0-9_]+/i", $stageID, $m) === 1 && is_array($m) && count($m) === 2))
		{
			return 0;
		}
		return (int)$m[1];
	}

	/**
	 * Get stage infos for specified deal category entry.
	 * @param int $ID Deal category entry ID.
	 * @return array
	 */
	public static function getStageInfos($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return \CCrmStatus::GetStatus('DEAL_STAGE');
		}

		return \CCrmStatus::GetStatus(self::convertToStatusEntityID($ID));
	}

	/**
	 * Get stage list for specified deal category entry.
	 * @param int $ID Deal category entry ID.
	 * @return array
	 */
	public static function getStageList($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		$ID = max($ID, 0);
		if(isset(self::$stageList[$ID]))
		{
			return self::$stageList[$ID];
		}

		$list = \CCrmStatus::GetStatusList($ID > 0 ? self::convertToStatusEntityID($ID) : 'DEAL_STAGE');
		return (self::$stageList[$ID] = $list);
	}

	public static function getFullStageList()
	{
		$list = array();
		foreach(self::getStageGroupInfos() as $group)
		{
			$name = isset($group['name']) ? $group['name'] : '';
			$items = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
			foreach($items as $k => $v)
			{
				$list[$k] = $name !== '' ? "{$name} / {$v}" : $v;
			}
		}
		return $list;
	}

	public static function getStageName($stageID, $ID = -1)
	{
		if($stageID === '')
		{
			return '';
		}

		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID < 0)
		{
			$ID = self::resolveFromStageID($stageID);
		}

		$list = self::getStageList($ID);
		return isset($list[$stageID]) ? $list[$stageID] : $stageID;
	}

	public static function getStageByName($name, $ID = 0)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID < 0)
		{
			return '';
		}

		foreach(self::getStageList($ID) as $stageID => $stageName)
		{
			if($stageName === $name)
			{
				return $stageID;
			}
		}

		return '';
	}

	public static function hasStage($stageID, $ID = -1)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID < 0)
		{
			$ID = self::resolveFromStageID($stageID);
		}

		$list = self::getStageList($ID);
		return isset($list[$stageID]);
	}

	/**
	 * Remove all deal stages those are belong to specified deal category entry.
	 * @param int $ID Deal category entry ID (must be greater than 0).
	 * @return void
	 */
	public static function eraseStages($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID > 0)
		{
			\CCrmStatus::Erase(self::convertToStatusEntityID($ID));
		}

	}

	/**
	 * Remove all permissions those are belong to specified deal category entry.
	 * @param int $ID Deal category entry ID (must be greater than 0).
	 * @return void
	 */
	public static function erasePermissions($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID > 0)
		{
			\CCrmRole::EraseEntityPermissons(self::convertToPermissionEntityType($ID));
		}
	}

	/**
	 * Remove color scheme that is belong to specified deal category entry.
	 * @param int $ID Deal category entry ID (must be greater than 0).
	 * @return void
	 */
	public static function removeColorScheme($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID > 0)
		{
			DealStageColorScheme::removeByCategory($ID);
		}
	}

	/**
	 * Prepare Form ID fom specified category
	 * @param int $ID Entry ID.
	 * @param int $sourceFormID Initial form ID.
	 * @return string
	 */
	public static function prepareFormID($ID, $sourceFormID, $useUpperCase = true)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if($ID <= 0)
		{
			return $sourceFormID;
		}

		$key = $useUpperCase ? 'C' : 'c';
		return "{$sourceFormID}_{$key}_{$ID}";
	}

	public static function getPermissionEntityTypeList()
	{
		$results = array();
		foreach(self::getAll(false) as $entry)
		{
			$results[] = self::convertToPermissionEntityType($entry['ID']);
		}
		return $results;
	}

	/**
	 * Convert deal category entry ID to permission entity type.
	 * @param int $ID Entry ID.
	 * @return string
	 */
	public static function convertToPermissionEntityType($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}
		return $ID > 0 ? "DEAL_C{$ID}" : 'DEAL';
	}

	/**
	 * Try to convert permission entity type to deal category entry ID.
	 * Returns 0 if entityType is equal to 'DEAL'
	 * Returns -1 if conversion failed.
	 * @param string $entityType Permission entity type.
	 * @return int
	 */
	public static function convertFromPermissionEntityType($entityType)
	{
		if($entityType === 'DEAL')
		{
			return 0;
		}

		if(!(preg_match("/DEAL_C(\d+)/", $entityType, $m) === 1 && is_array($m) && count($m) === 2))
		{
			return -1;
		}

		return (int)$m[1];
	}

	/**
	 * Check if specified permission entity belong to deal category entry
	 * @param string $entityType Permission entity.
	 * @return bool
	 */
	public static function hasPermissionEntity($entityType)
	{
		return(self::convertFromPermissionEntityType($entityType) > 0);
	}

	public static function getAllPermissionEntityTypes()
	{
		$results = array();
		foreach(self::getAll(false) as $entry)
		{
			$results[] = self::convertToPermissionEntityType($entry['ID']);
		}
		return $results;
	}

	public static function getPermissionRoleConfigurations()
	{
		$results = array();
		self::includeLangFile();
		foreach(self::getAll(false) as $entry)
		{
			$ID = $entry['ID'];
			$name = isset($entry['NAME']) ? $entry['NAME'] : '';

			if($name === '')
			{
				$name = $ID;
			}
			$entityType = self::convertToPermissionEntityType($ID);
			$results[$entityType] = array(
				'TYPE' => $entityType,
				'NAME' =>  GetMessage('CRM_DEAL_CATEGORY_PERMISSION_ENTITY', array('#CATEGORY#' => $name)),
				'FIELDS' => array('STAGE_ID' => self::getStageList($ID))
			);
		}
		return $results;
	}

	/**
	 * Check if user may read deal categories.
	 * @param \CCrmPerms|null $userPermissions
	 * @return bool
	 */
	public static function checkReadPermission($userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions =  \CCrmPerms::GetCurrentUserPermissions();
		}

		return $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	/**
	 * Prepare JavaScript infos
	 * @param array $IDs Entry IDs to add.
	 * @param boolean $encode Enable html encoding of names.
	 * @return array
	 */
	public static function getJavaScriptInfos(array $IDs = null, $encode = false)
	{
		$infos = array();
		$map = is_array($IDs) ? array_fill_keys($IDs, true) : null;
		foreach(self::getAll(true) as $entry)
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
	 * Apply restrictions for deal gategory limit
	 * @param $limit
	 * @throws Main\NotSupportedException
	 */
	public static function applyMaximumLimitRestrictions($limit)
	{
		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$connection = Main\Application::getConnection();
		if($limit <= 0)
		{
			//There is no limit
			$connection->queryExecute("UPDATE b_crm_deal_category SET IS_LOCKED = 'N'");
		}
		elseif($limit === 1)
		{
			//All user categories are disabled
			$connection->queryExecute("UPDATE b_crm_deal_category SET IS_LOCKED = 'Y'");
		}
		else
		{
			$sqlLimit = ($limit - 1);
			$queries = array();
			if($connection instanceof Main\DB\MysqlCommonConnection)
			{
				$queries[] = "
					UPDATE b_crm_deal_category C
						LEFT JOIN
							(SELECT ID FROM b_crm_deal_category ORDER BY SORT ASC, ID ASC LIMIT {$sqlLimit}) T
						ON C.ID = T.ID
					SET C.IS_LOCKED = (CASE WHEN T.ID IS NULL THEN 'Y' ELSE 'N' END)";
			}
			elseif($connection instanceof Main\DB\MssqlConnection)
			{
				$queries[] = "
					UPDATE C
						SET C.IS_LOCKED = (CASE WHEN T.ID IS NULL THEN 'Y' ELSE 'N' END)
						FROM  B_CRM_DEAL_CATEGORY C
							LEFT JOIN (SELECT TOP {$sqlLimit} ID FROM B_CRM_DEAL_CATEGORY ORDER BY SORT ASC, ID ASC) T
							ON C.ID = T.ID";
			}
			elseif($connection instanceof Main\DB\OracleConnection)
			{
				$queries[] = "UPDATE B_CRM_DEAL_CATEGORY SET IS_LOCKED = 'Y' WHERE ID IN (SELECT ID FROM B_CRM_DEAL_CATEGORY WHERE ROWNUM > {$sqlLimit} ORDER BY SORT ASC, ID ASC)";
				$queries[] = "UPDATE B_CRM_DEAL_CATEGORY SET IS_LOCKED = 'N' WHERE ID IN (SELECT ID FROM B_CRM_DEAL_CATEGORY WHERE ROWNUM <= {$sqlLimit} ORDER BY SORT ASC, ID ASC)";
			}
			else
			{
				$dbType = $connection->getType();
				throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
			}

			foreach($queries as $query)
			{
				$connection->queryExecute($query);
			}
		}
	}

	/**
	 * Add namespace identifier to existing stage IDs if it was not added.
	 * @param string $categoryID Category ID.
	 * @return void
	 */
	public static function correctStageNamespaceID($categoryID)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}

		if($categoryID <= 0)
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityID = self::convertToStatusEntityID($categoryID);
		$entitySql = $sqlHelper->forSql($entityID, 50);

		$stageIDs = array_keys(self::getStageList($categoryID));
		foreach($stageIDs as $stageID)
		{
			if(self::hasStageNamespaceID($stageID, $categoryID))
			{
				continue;
			}

			$newStageID = self::prepareStageID($categoryID, self::removeStageNamespaceID($stageID));
			if($newStageID === $stageID)
			{
				continue;
			}

			$stageSql = $sqlHelper->forSql($stageID, 50);
			$newStageSql = $sqlHelper->forSql($newStageID, 50);

			if($connection instanceof Main\DB\MysqlCommonConnection)
			{
				try
				{
					$connection->startTransaction();

					$connection->queryExecute("
						UPDATE b_crm_status SET STATUS_ID = '{$newStageSql}'
							WHERE STATUS_ID = '{$stageSql}' AND ENTITY_ID = '{$entitySql}'"
					);

					$connection->queryExecute("
						UPDATE b_crm_deal SET STAGE_ID = '{$newStageSql}'
							WHERE STAGE_ID = '{$stageSql}' AND CATEGORY_ID = {$categoryID}"
					);

					$connection->queryExecute("
						UPDATE b_crm_deal_stage_history SET STAGE_ID = '{$newStageSql}'
							WHERE STAGE_ID = '{$stageSql}' AND CATEGORY_ID = {$categoryID}"
					);

					$connection->queryExecute("
						UPDATE b_crm_deal_sum_stat SET STAGE_ID = '{$newStageSql}'
							WHERE STAGE_ID = '{$stageSql}' AND CATEGORY_ID = {$categoryID}"
					);

					$connection->queryExecute("
						UPDATE b_crm_deal_inv_stat SET STAGE_ID = '{$newStageSql}'
							WHERE STAGE_ID = '{$stageSql}' AND CATEGORY_ID = {$categoryID}"
					);

					$connection->queryExecute("
						UPDATE b_crm_deal_act_stat SET STAGE_ID = '{$newStageSql}'
							WHERE STAGE_ID = '{$stageSql}' AND CATEGORY_ID = {$categoryID}"
					);

					$connection->commitTransaction();
				}
				catch(Main\Db\SqlException $e)
				{
					$connection->rollbackTransaction();
				}
			}
		}
	}

	public static function getFieldCaption($fieldName)
	{
		self::includeLangFile();
		$result = GetMessage("CRM_DEAL_CATEGORY_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}

	/**
	 * Include class language file.
	 * @return void
	 */
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}