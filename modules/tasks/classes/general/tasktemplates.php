<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 */
global $APPLICATION, $DB;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Template;

Loc::loadMessages(__FILE__);

class CTaskTemplates
{
	const TYPE_FOR_NEW_USER =		0x01;

	const CURRENT_SITE_ID =			'*';

	private $_errors = array();

	/**
	 * Get tasks fields info (for rest, etc)
	 *
	 * @return array
	 */
	public static function getFieldsInfo()
	{
		global $USER_FIELD_MANAGER;

		$fields = [
			"ID" => [
				'type'    => 'integer',
				'primary' => true
			],
			"PARENT_ID"   => [
				'type'    => 'integer',
				'default' => 0
			],
			"TITLE" => [
				'type'    => 'string',
				'required' => true
			],
			"DESCRIPTION" => [
				'type'    => 'string'
			],

			"PRIORITY"    => [
				'type'    => 'enum',
				'values'  => [
					\CTasks::PRIORITY_HIGH    => Loc::getMessage('TASKS_FIELDS_PRIORITY_HIGH'),
					\CTasks::PRIORITY_AVERAGE => Loc::getMessage('TASKS_FIELDS_PRIORITY_AVERAGE'),
					\CTasks::PRIORITY_LOW     => Loc::getMessage('TASKS_FIELDS_PRIORITY_LOW')
				],
				'default' => \CTasks::PRIORITY_AVERAGE
			],


			"GROUP_ID" => [
				'type'    => 'integer',
				'default' => 0
			],
			"STAGE_ID" => [
				'type'    => 'integer',
				'default' => 0
			],

			"CREATED_BY"     => [
				'type'     => 'integer',
				'required' => true
			],
			"RESPONSIBLE_ID" => [
				'type'     => 'integer',
				'required' => true
			],
			"DEPENDS_ON" => [
				'type'     => 'integer',
				'required' => true
			],
			"RESPONSIBLES" => [
				'type'     => 'array'
			],
			"ACCOMPLICES" => [
				'type'     => 'array'
			],
			"AUDITORS" => [
				'type'     => 'array'
			],
			"STATUS"      => [
				'type'    => 'enum',
				'values'  => [
					\CTasks::STATE_PENDING              => Loc::getMessage('TASKS_FIELDS_STATUS_PENDING'),
					\CTasks::STATE_IN_PROGRESS          => Loc::getMessage('TASKS_FIELDS_STATUS_IN_PROGRESS'),
					\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_FIELDS_STATUS_SUPPOSEDLY_COMPLETED'),
					\CTasks::STATE_COMPLETED            => Loc::getMessage('TASKS_FIELDS_STATUS_COMPLETED'),
					\CTasks::STATE_DEFERRED             => Loc::getMessage('TASKS_FIELDS_STATUS_DEFERRED')
				],
				'default' => \CTasks::STATE_PENDING
			],

			"MULTITASK" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"REPLICATE" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"SITE_ID"        => [
				'type' => 'string',
			],
			"TASK_CONTROL"          => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"ADD_IN_REPORT"         => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],

			"XML_ID" => [
				'type'    => 'string',
				'default' => null
			],


			'DEADLINE_AFTER' => [
				'type'    => 'datetime',
				'default' => null
			],
			'START_DATE_PLAN_AFTER'=> [
				'type'    => 'datetime',
				'default' => null
			],
			'END_DATE_PLAN_AFTER' => [
				'type'    => 'datetime',
				'default' => null
			],

			'TPARAM_TYPE' => [
				'type'    => 'enum',
				'values'=>[
					1 => Loc::getMessage('TASKS_FIELDS_Y'),
					0 => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 0
			],
			'TPARAM_REPLICATION_COUNT' => [
				'type'    => 'integer',
				'default' => 0
			],
			'REPLICATE_PARAMS' => [
				'type'    => 'string'
			],

			'BASE_TEMPLATE_ID' => [
				'type'    => 'integer',
				'default' => null
			],
			'TEMPLATE_CHILDREN_COUNT' => [
				'type'    => 'integer',
				'default' => null
			],

		];

		foreach ($fields as $fieldId => &$fieldData)
		{
			$fieldData = array_merge(['title' => Loc::getMessage('TASKS_TEMPLATE_FIELDS_'.$fieldId)], $fieldData);
		}
		unset($fieldData);


		$uf = $USER_FIELD_MANAGER->GetUserFields("TASKS_TASK_TEMPLATE");
		foreach ($uf as $key => $item)
		{
			$fields[$key] = [
				'title' => $item['USER_TYPE']['DESCRIPTION'],
				'type'  => $item['USER_TYPE_ID']
			];
		}

		return $fields;
	}

	function GetErrors()
	{
		return $this->_errors;
	}


	function CheckFields(&$arFields, $ID = false, $arParams = array())
	{
		global $APPLICATION;

		if(intval($ID))
		{
			if(!is_array($this->currentData))
				$this->currentData = self::GetById($ID)->fetch();
		}

		////////////////////////////////////
		// deal with TYPE

		if(intval($this->currentData['ID']) && isset($arFields['TPARAM_TYPE']) && $this->currentData['TPARAM_TYPE'] != $arFields['TPARAM_TYPE'])
		{
			$this->_errors[] = array("text" => 'You can not change TYPE of an existing template', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
			unset($arFields['TPARAM_TYPE']); // You may not change template type of an existing template
		}

		// check allowed value
		if(isset($arFields['TPARAM_TYPE']))
		{
			if($arFields['TPARAM_TYPE'] != static::TYPE_FOR_NEW_USER) // white list here later
				$this->_errors[] = array("text" => 'Unknown template type id passed', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
		}

		// unset some data, if type is TYPE_FOR_NEW_USER
		if((!intval($ID) && $arFields['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER) || (intval($ID) && $this->currentData['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER))
		{
			$arFields['BASE_TEMPLATE_ID'] = ''; // such kind of template can not have parent template ...
			$arFields['REPLICATE_PARAMS'] = serialize(array()); // ... and replication params
			$arFields['RESPONSIBLE_ID'] = '0'; // ... and responsible_id
			$arFields['RESPONSIBLES'] = serialize(array(0)); // ... and other responsibles
			$arFields['MULTITASK'] = 'N'; // ... and be a multitask
		}

		////////////////////////////////////
		// deal with RESPONSIBLES

		if(
			($ID === false && $arFields['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER)
			|| ($ID !== false && $this->currentData['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER)
		)
		{
			if(isset($arFields["RESPONSIBLE_ID"]))
			{

				$r = CUser::GetByID($arFields["RESPONSIBLE_ID"]);
				if (!$r->Fetch())
				{
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID_EX"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID_EX");
				}
			}
			else
			{
				if($ID === false)
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID");
			}
		}

		////////////////////////////////////
		// deal with other data

		if ((is_set($arFields, "TITLE") || $ID === false) && $arFields["TITLE"] == '')
		{
			$this->_errors[] = array("text" => GetMessage("TASKS_BAD_TITLE"), "id" => "ERROR_BAD_TASKS_TITLE");
		}

		if(array_key_exists('TPARAM_REPLICATION_COUNT', $arFields))
		{
			$arFields['TPARAM_REPLICATION_COUNT'] = intval($arFields['TPARAM_REPLICATION_COUNT']);
		}
		elseif(!$ID)
		{
			$arFields['TPARAM_REPLICATION_COUNT'] = 1;
		}

		/*
		if(!$ID)
		{
			// since this time we dont allow to create tasks with a non-bbcode description
			if($arFields['DESCRIPTION_IN_BBCODE'] == 'N')
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_DESCRIPTION_IN_BBCODE_NO_NOT_ALLOWED"), "id" => "ERROR_TASKS_DESCRIPTION_IN_BBCODE_NO_NOT_ALLOWED");
			}
			else
			{
				$arFields['DESCRIPTION_IN_BBCODE'] = 'Y';
			}
		}
		*/

		if (isset($arFields['BASE_TEMPLATE_ID']))
		{
			try
			{
				if(intval($arFields['BASE_TEMPLATE_ID']))
				{
					$template = static::GetList(array(), array('ID' => $arFields['BASE_TEMPLATE_ID']), false, false, array('ID'))->fetch();
					if(!is_array($template))
						$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BASE_TEMPLATE_ID_NOT_EXISTS"), "id" => "ERROR_TASKS_BASE_TEMPLATE_ID_NOT_EXISTS");

					// you cannot add a template with both PARENT_ID and BASE_TEMPLATE_ID set. BASE_TEMPLATE_ID has greather priority
					if(isset($arFields['PARENT_ID']))
						$arFields['PARENT_ID'] = '';

					// you cannot add REPLICATE parameters here in case of BASE_TEMPLATE_ID is set
					if(isset($arFields['REPLICATE']))
						$arFields['REPLICATE'] = serialize(array());

					$arFields['REPLICATE_PARAMS'] = serialize(array());
				}
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
				$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BAD_BASE_TEMPLATE_ID"), "id" => "ERROR_TASKS_BAD_BASE_TEMPLATE_ID");
			}
		}

		// move 0 to null in PARENT_ID to avoid constraint and query problems
		// todo: move PARENT_ID, GROUP_ID and other "foreign keys" to the unique way of keeping absense of relation: null, 0 or ''
		if(array_key_exists('PARENT_ID', $arFields))
		{
			$parentId = intval($arFields['PARENT_ID']);
			if(!intval($parentId))
			{
				$arFields['PARENT_ID'] = false;
			}
		}

		if (is_set($arFields, "PARENT_ID") && intval($arFields["PARENT_ID"]) > 0)
		{
			$r = CTasks::GetList(array(), array("ID" => $arFields["PARENT_ID"]));
			if (!$r->Fetch())
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_BAD_PARENT_ID"), "id" => "ERROR_TASKS_BAD_PARENT_ID");
			}
		}

		if (
			isset($arFields['FILES'])
			&& isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			// Are there any files?
			if ($arFields['FILES'] !== false)
			{
				// There is must be serialized array
				$arFilesIds = unserialize($arFields['FILES'], ['allowed_classes' => false]);

				if (is_array($arFilesIds))
				{
					$ar = CTaskFiles::checkFilesAccessibilityByUser($arFilesIds, (int) $arParams['USER_ID']);

					// If we have one file, that is not accessible, than emit error
					foreach ($arFilesIds as $fileId)
					{
						if (
							( ! isset($ar['f' . $fileId]) )
							|| ($ar['f' . $fileId] === false)
						)
						{
							$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
						}
					}
				}
				else
				{
					$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
				}
			}
		}

		// REPLICATE_PARAMS comes serialized 0_o
		if((string) $arFields['REPLICATE_PARAMS'] != '')
		{
			$params = \Bitrix\Tasks\Util\Type::unSerializeArray($arFields['REPLICATE_PARAMS']);

			$arFields['REPLICATE_PARAMS'] = serialize(self::parseReplicationParams($params));
		}

		// ensure RESPONSIBLES filled anyway
		if(!$ID)
		{
			// set RESPONSIBLES from RESPONSIBLE_ID if not set on add()
			if((string) $arFields['RESPONSIBLES'] == '')
			{
				$arFields['RESPONSIBLES'] = serialize(array($arFields['RESPONSIBLE_ID']));
			}
		}
		else
		{
			if((string) $arFields['RESPONSIBLES'] == '' && (string) $this->currentData['RESPONSIBLES'] == '')
			{
				$arFields['RESPONSIBLES'] = serialize(array($arFields['RESPONSIBLE_ID']));
			}
		}

		if (!empty($this->_errors))
		{
			$e = new CAdminException($this->_errors);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (is_set($arFields, "PRIORITY") && !in_array($arFields["PRIORITY"], Array(0, 1, 2)))
			$arFields["PRIORITY"] = 1;

		return true;
	}

	/**
	 * @param $arFields
	 * @param $arParams
	 * @return false|int
	 *
	 * @deprecated since tasks 22.900.0 use Tasks\Control\Template instead
	 */
	function Add($arFields, $arParams = [])
	{
		$userId = 0;
		if (array_key_exists('USER_ID', $arParams))
		{
			$userId = (int) $arParams['USER_ID'];
		}

		$manager = new Bitrix\Tasks\Control\Template($userId);

		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			$manager->withCheckFileRights();
		}

		try
		{
			$template = $manager->add($arFields);
		}
		catch (\Bitrix\Tasks\Control\Exception\TemplateAddException $e)
		{
			$this->_errors[] = $e->getMessage();
			return false;
		}
		catch (\Exception $e)
		{
			$this->_errors[] = $e->getMessage();
			return false;
		}

		return $template->getId();
	}

	/**
	 * @param $id
	 * @param $arFields
	 * @param $arParams
	 * @return bool
	 *
	 * @deprecated since tasks 22.900.0 use Tasks\Control\Template instead
	 */
	function Update($id, $arFields, $arParams = [])
	{
		$id = (int) $id;
		if ($id < 1)
		{
			return false;
		}

		if (isset($arParams['USER_ID']))
		{
			$userId = (int) $arParams['USER_ID'];
		}
		else
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
			if(!$userId)
			{
				$userId = \Bitrix\Tasks\Util\User::getAdminId(); // compatibility
			}
		}

		$manager = new \Bitrix\Tasks\Control\Template($userId);

		if (isset($arParams['SKIP_AGENT_PROCESSING']))
		{
			$manager->withSkipAgent();
		}
		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			$manager->withCheckFileRights();
		}

		try
		{
			$template = $manager->update($id, $arFields);
		}
		catch(\Bitrix\Tasks\Control\Exception\TemplateUpdateException $e)
		{
			$this->_errors[] = $e->getMessage();
			return false;
		}
		catch (\Exception $e)
		{
			$this->_errors[] = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * @param $id
	 * @param array $params
	 * @return bool
	 *
	 * @deprecated since tasks 22.900.0 use Tasks\Control\Template instead
	 */
	public static function Delete($id, array $params = [])
	{
		$id = (int)$id;

		if ($id < 1)
		{
			return false;
		}

		$userId = \Bitrix\Tasks\Util\User::getId();
		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getAdminId(); // compatibility
		}

		$manager = new \Bitrix\Tasks\Control\Template($userId);

		if (
			isset($params['UNSAFE_DELETE_ONLY'])
			&& ($params['UNSAFE_DELETE_ONLY'] !== false && $params['UNSAFE_DELETE_ONLY'] !== 'N')
		)
		{
			$manager->withUnsafeDelete();
		}

		try
		{
			return $manager->delete($id);
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	// may be in a future.... may be...
	public static function DeleteBatch(array $ids, array $params = [])
	{
		$result = [];
		$ids = array_filter(array_unique(array_map('intval', $ids)));

		if (empty($ids))
		{
			return false;
		}

		foreach($ids as $id)
		{
			$result[$id] = self::Delete($id, $params);
		}

		return $result;
	}

	/**
	 * @param $arOrder
	 * @param $arFilter
	 * @param array $arNavParams
	 * @param array $arParams
	 * @param array $arSelect
	 * @return bool|CDBResult
	 *
	 * @global $DB CDatabase
	 */
	public static function GetList($arOrder, $arFilter = array(), $arNavParams = array(), $arParams = array(), $arSelect = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$arOrder = is_array($arOrder) ? $arOrder : [];
		$arFilter = is_array($arFilter) ? $arFilter : [];
		$arNavParams = is_array($arNavParams) ? $arNavParams : [];
		$arParams = is_array($arParams) ? $arParams : [];
		$arSelect = is_array($arSelect) ? $arSelect : [];

		$provider = new \Bitrix\Tasks\Provider\TemplateProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getList($arOrder, $arFilter, $arSelect, $arParams, $arNavParams);
	}

	public static function GetCount($includeSubTemplates = false, array $params = [])
	{
		global $DB, $USER_FIELD_MANAGER;

		if (!array_key_exists('USER_ID', $params))
		{
			$params['USER_ID'] = (int) \Bitrix\Tasks\Util\User::getId();
		}

		$provider = new \Bitrix\Tasks\Provider\TemplateProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getCount($includeSubTemplates, $params);
	}

	function GetFilter($arFilter, $arParams)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "CREATED_BY":
				case "TASK_ID":
				case "GROUP_ID":
				case "TPARAM_TYPE":
				case "ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "RESPONSIBLE":
					$arSqlSearch[] = CTasks::FilterCreate("TT.RESPONSIBLE_ID", $val, "number", $bFullJoin, $cOperationType);
					break;

				case "TAGS":
					$val = '%i:%;s:%:"'.$val.'";%';
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "TITLE":
				case "ZOMBIE":
				case "XML_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "REPLICATE":
				case "PRIORITY":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				case 'SEARCH_INDEX':
					$fieldsToLike = array(
						"TT.TITLE",
						"TT.DESCRIPTION",
						"CONCAT_WS(' ', CU.NAME, CU.LAST_NAME, CU.SECOND_NAME, CU.LOGIN, RU.NAME, RU.LAST_NAME, RU.SECOND_NAME, RU.LOGIN)"
					);

					$filter = '(';
					$filter .= CTasks::FilterCreate("TT.ID", (int)$val, "number", $bFullJoin, $cOperationType);

					foreach ($fieldsToLike as $field)
					{
						$filter .= " OR ".CTasks::FilterCreate($field, $val, "string", $bFullJoin, "S");
					}
					$filter .= ')';

					$arSqlSearch[] = $filter;

					break;

				/*
				case "TEMPLATE_CHILDREN_COUNT":
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, "number", $bFullJoin, $cOperationType);
					break;
				*/

				case "BASE_TEMPLATE_ID":

					$parentColumnName = 	Template\DependencyTable::getPARENTIDColumnName();
					$columnName = 			Template\DependencyTable::getIDColumnName();
					$cOperationType = 		'I'; // force to "identical" for this field, in any case

					$val = (string) $val;
					if($val === '' || $val === '0')
						$val = false;

					//$includeSubtree = 		$arParams['INCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['INCLUDE_TEMPLATE_SUBTREE'] === 'Y';
					$excludeSubtree = 	$arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y';

					if($excludeSubtree)
					{
						$arSqlSearch[] = "TT.ID NOT IN (SELECT ".$columnName." FROM ".Template\DependencyTable::getTableName()." WHERE ".$parentColumnName." = '".intval($val)."')";
					}
					else
					{
						$arSqlSearch[] = '('.($val ? "TD.".$parentColumnName." = '".intval($val)."'" : "TD.".$parentColumnName." = '0' OR TD.".$parentColumnName." IS NULL").')';
						//$arSqlSearch[] = CTasks::FilterCreate("TD.".Template\DependencyTable::getPARENTIDColumnName(), $val, "number", $bFullJoin, $cOperationType);
					}

					break;
			}
		}

		return $arSqlSearch;
	}

	public static function GetByID($ID, $arParams = array())
	{
		if(!intval($ID))
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
			return $dbResult;
		}

		$select = array_key_exists('select', $arParams) ? $arParams['select'] : ['*', 'UF_*'];
		$order = array_key_exists('order', $arParams) ? $arParams['order'] : [];

		return CTaskTemplates::GetList($order, ["ID" => $ID], [], $arParams, $select);
	}

	/**
	 * @deprecated
	 */
	public static function parseReplicationParams(array $params = []): array
	{
		// todo: use \Bitrix\Tasks\Item\Task\Template\Field\ReplicateParams::createValueStructure() here

		$allowed = [
			'PERIOD' => true,
			'EVERY_DAY' => true,
			'WORKDAY_ONLY' => true,
			'EVERY_WEEK' => true,
			'WEEK_DAYS' => true,
			'MONTHLY_TYPE' => true,
			'MONTHLY_DAY_NUM' => true,
			'MONTHLY_MONTH_NUM_1' => true,
			'MONTHLY_WEEK_DAY_NUM' => true,
			'MONTHLY_WEEK_DAY' => true,
			'MONTHLY_MONTH_NUM_2' => true,
			'YEARLY_TYPE' => true,
			'YEARLY_DAY_NUM' => true,
			'YEARLY_MONTH_1' => true,
			'YEARLY_WEEK_DAY_NUM' => true,
			'YEARLY_WEEK_DAY' => true,
			'YEARLY_MONTH_2' => true,
			'START_DATE' => true,
			'END_DATE' => true,
			'TIME' => true,
			'TIMEZONE_OFFSET' => true,
			'DAILY_MONTH_INTERVAL' => true,
			'REPEAT_TILL' => true,
			'TIMES' => true,
			'NEXT_EXECUTION_TIME' => true,
		];
		foreach ($params as $fld => $value)
		{
			if (!$allowed[$fld])
			{
				unset($params[$fld]);
			}
		}

		$params['EVERY_DAY'] = ((int)($params['EVERY_DAY'] ?? null) ?: 1);
		$params['EVERY_WEEK'] = ((int)($params['EVERY_WEEK'] ?? null) ?: 1);
		$params['MONTHLY_DAY_NUM'] = ((int)($params['MONTHLY_DAY_NUM'] ?? null) ?: 1);
		$params['MONTHLY_MONTH_NUM_1'] = ((int)($params['MONTHLY_MONTH_NUM_1'] ?? null) ?: 1);
		$params['MONTHLY_MONTH_NUM_2'] = ((int)($params['MONTHLY_MONTH_NUM_2'] ?? null) ?: 1);
		$params['YEARLY_DAY_NUM'] = ((int)($params['YEARLY_DAY_NUM'] ?? null) ?: 1);

		$params['PERIOD'] = (string)($params['PERIOD'] ?? null);
		$params['WEEK_DAYS'] = ($params['WEEK_DAYS'] ?? null);
		$params['TIME'] = ($params['TIME'] ?? '');
		$params['WORKDAY_ONLY'] = (($params['WORKDAY_ONLY'] ?? null) === 'Y' ? 'Y' : 'N');
		$params['END_DATE'] = ($params['END_DATE'] ?? null);

		$params['MONTHLY_TYPE'] = static::parseTypeSelector($params['MONTHLY_TYPE'] ?? null);
		$params['YEARLY_TYPE'] = static::parseTypeSelector($params['YEARLY_TYPE'] ?? null);

        if ($params['PERIOD'] === '')
        {
            $params['PERIOD'] = 'daily';
        }
        if (!is_array($params['WEEK_DAYS']))
        {
            $params['WEEK_DAYS'] = [];
        }

		$time = 3600 * 5; // five hours
		if (trim($params['TIME']) !== '')
		{
			$time = \Bitrix\Tasks\UI::parseTimeAmount($params['TIME'], 'HH:MI');
		}
		$params['TIME'] = \Bitrix\Tasks\UI::formatTimeAmount($time, 'HH:MI');

		if (array_key_exists('TIMEZONE_OFFSET', $params))
		{
			$params['TIMEZONE_OFFSET'] = (int)$params['TIMEZONE_OFFSET'];
		}

		// for old templates
		if (!array_key_exists('REPEAT_TILL', $params) && (string)$params['END_DATE'] !== '')
		{
			$params['REPEAT_TILL'] = 'date';
		}

		return $params;
	}

    private static function parseTypeSelector($type)
    {
        $type = intval($type);
        if($type < 1 || $type > 2)
        {
            $type = 1;
        }

        return $type;
    }
}