<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2014 Bitrix
 * 
 * @access private
 */
// DEPRECATED

use \Bitrix\Main\Config\Option;

/**
 * Class CTaskColumnList
 */
class CTaskColumnList
{
	const COLUMN_ID                    = 1;
	const COLUMN_TITLE                 = 2;
	const COLUMN_ORIGINATOR            = 3;
	const COLUMN_RESPONSIBLE           = 4;
	const COLUMN_DEADLINE              = 5;
	const COLUMN_GRADE                 = 6;
	const COLUMN_UF_CRM                = 7;
	const COLUMN_PRIORITY              = 8;
	const COLUMN_STATUS                = 9;
	const COLUMN_GROUP_ID              = 10;
	const COLUMN_TIME_ESTIMATE         = 11;
	const COLUMN_ALLOW_TIME_TRACKING   = 12;
	const COLUMN_TIME_SPENT_IN_LOGS    = 13;
	const COLUMN_ALLOW_CHANGE_DEADLINE = 14;
	const COLUMN_CREATED_DATE          = 15;
	const COLUMN_CHANGED_DATE          = 16;
	const COLUMN_CLOSED_DATE           = 17;

	const SYS_COLUMN_CHECKBOX          = -1;
	const SYS_COLUMN_COMPLETE          = -2;
	const SYS_COLUMN_EMPTY             = -3;


	/**
	 * @return array of allowed columns (with meta description)
	 */
	public static function get(array $parameters = array())
	{
		static $cols;

		if($cols === null)
		{
			$cols = array(
				self::COLUMN_ID => array(
					'DB_COLUMN' => 'ID',
					'SORTABLE' => true
				),
				self::COLUMN_TITLE => array(
					'DB_COLUMN' => 'TITLE',
					'SORTABLE' => true
				),
				self::COLUMN_ORIGINATOR => array(
					'DB_COLUMN' => 'CREATED_BY',
					'SORTABLE' => true
				),
				self::COLUMN_RESPONSIBLE => array(
					'DB_COLUMN' => 'RESPONSIBLE_ID',
					'SORTABLE' => true
				),
				self::COLUMN_DEADLINE => array(
					'DB_COLUMN' => 'DEADLINE',
					'SORTABLE' => true
				),
				self::COLUMN_GRADE => array(
					'DB_COLUMN' => 'MARK',
					'SORTABLE' => true
				),
				self::COLUMN_PRIORITY => array(
					'DB_COLUMN' => 'PRIORITY',
					'SORTABLE' => true
				),
				self::COLUMN_STATUS => array(
					'DB_COLUMN' => 'STATUS'
				),
				self::COLUMN_GROUP_ID => array(
					'DB_COLUMN' => 'GROUP_ID'
				),
				self::COLUMN_TIME_ESTIMATE => array(
					'DB_COLUMN' => 'TIME_ESTIMATE',
					'SORTABLE' => true
				),
				self::COLUMN_ALLOW_TIME_TRACKING => array(
					'DB_COLUMN' => 'ALLOW_TIME_TRACKING',
					'SORTABLE' => true
				),
				self::COLUMN_TIME_SPENT_IN_LOGS => array(
					'DB_COLUMN' => 'TIME_SPENT_IN_LOGS'
				),
				self::COLUMN_ALLOW_CHANGE_DEADLINE => array(
					'DB_COLUMN' => 'ALLOW_CHANGE_DEADLINE',
					'SORTABLE' => true
				),
				self::COLUMN_CREATED_DATE => array(
					'DB_COLUMN' => 'CREATED_DATE',
					'SORTABLE' => true
				),
				self::COLUMN_CHANGED_DATE => array(
					'DB_COLUMN' => 'CHANGED_DATE',
					'SORTABLE' => true
				),
				self::COLUMN_CLOSED_DATE => array(
					'DB_COLUMN' => 'CLOSED_DATE',
					'SORTABLE' => true
				),
				self::COLUMN_UF_CRM => array(
					'DB_COLUMN' => 'UF_CRM_TASK'
				)
			);

			// add userfields, if any
			if(is_array($parameters['USER_FIELDS']))
			{
				$userfields = $parameters['USER_FIELDS'];
			}
			else
			{
				// simply skip for backward compatibility
				$userfields = array();
			}

			$canSortFilterUf = Option::get('tasks', 'task_list_uf_sort_filter', false);
			$ufNames = array_keys($userfields);
			foreach($ufNames as $ufName)
			{
				if($ufName == 'UF_CRM_TASK') // already there actually
				{
					continue;
				}

				$cols[] = array(
					'DB_COLUMN' => $ufName,
					'SORTABLE' => $canSortFilterUf && in_array($userfields[$ufName]['USER_TYPE_ID'], array('integer', 'string', 'double', 'boolean', 'date', 'datetime', 'enumeration'))
				);
			}

		}

		return ($cols);
	}
}


/**
 * Class CTaskColumnContext
 */
class CTaskColumnContext
{
	const CONTEXT_RESPONSIBLE = 1;
	const CONTEXT_ACCOMPLICE  = 2;
	const CONTEXT_AUDITOR     = 3;
	const CONTEXT_ORIGINATOR  = 4;
	const CONTEXT_ALL         = 5;
	const CONTEXT_TASK_DETAIL = 6;


	/**
	 * @return array of IDs of known contexts used for columns' management
	 */
	public static function get()
	{
		return (array(
			self::CONTEXT_ALL,
			self::CONTEXT_ORIGINATOR,
			self::CONTEXT_AUDITOR,
			self::CONTEXT_ACCOMPLICE,
			self::CONTEXT_RESPONSIBLE,
			self::CONTEXT_TASK_DETAIL
		));
	}
}


/**
 * Class CTaskColumnManager
 */
class CTaskColumnManager
{
	private $oPreset = null;


	/**
	 * @param CTaskColumnPresetManager $oColumnPresetManager
	 */
	public function __construct($oColumnPresetManager)
	{
		$this->oPreset = $oColumnPresetManager;
	}


	/**
	 * @param array $arColumnsId
	 */
	public function hideColumns($arColumnsId)
	{
		CTaskAssert::assert(is_array($arColumnsId));

		$arAllowedColumnsIDs = array_keys(CTaskColumnList::get());

		$arColumnsId = array_unique($arColumnsId);

		$arCurrentColumns = $this->getCurrentPresetColumns();

		$arNewColumns = array();
		foreach ($arCurrentColumns as $columnData)
		{
			CTaskAssert::assertLaxIntegers($columnData['ID']);
			CTaskAssert::assert(in_array($columnData['ID'], $arAllowedColumnsIDs));

			if ( ! in_array($columnData['ID'], $arColumnsId) )
			{
				$arNewColumns[] = array(
					'ID'    => $columnData['ID'],
					'WIDTH' => $columnData['WIDTH']
				);
			}
		}

		$this->setColumns($arNewColumns);
	}


	public function showColumns($arColumnsId)
	{
		CTaskAssert::assert(is_array($arColumnsId));

		$arAllowedColumnsIDs = array_keys(CTaskColumnList::get());

		$arColumnsId = array_unique($arColumnsId);

		$arColumns = $this->getCurrentPresetColumns();
		$arCurrentColumnsIds = array();
		foreach ($arColumns as &$columnData)
			$arCurrentColumnsIds[] = (int) $columnData['ID'];
		unset($columnData);

		foreach ($arColumnsId as $newColumnId)
		{
			CTaskAssert::assertLaxIntegers($newColumnId);
			CTaskAssert::assert(in_array($newColumnId, $arAllowedColumnsIDs));

			if ( ! in_array($newColumnId, $arCurrentColumnsIds) )
			{
				$arColumns[] = array(
					'ID'    => $newColumnId,
					'WIDTH' => 100
				);
			}
		}

		$this->setColumns($arColumns);
	}


	public function moveColumnAfter($movedColumnId, $moveAfterId = 0)
	{
		CTaskAssert::assertLaxIntegers($movedColumnId, $moveAfterId);
		$movedColumnId = (int) $movedColumnId;
		$moveAfterId   = (int) $moveAfterId;
		$arAllowedColumnsIDs = array_keys(CTaskColumnList::get());
		CTaskAssert::assert(
			($movedColumnId > 0)
			&& ($moveAfterId >= 0)
			&& in_array($movedColumnId, $arAllowedColumnsIDs)
		);

		$arCurrentColumns = $this->getCurrentPresetColumns();
		$arColumnToBeMoved = null;
		foreach ($arCurrentColumns as &$columnData)
		{
			if ($columnData['ID'] == $movedColumnId)
			{
				$arColumnToBeMoved = $columnData;
				break;
			}
		}
		unset($columnData);

		if ($arColumnToBeMoved === null)
			return (false);

		$arNewColumns = array();
		if ($moveAfterId === 0)
		{
			$arNewColumns[] = array(
				'ID'    => $movedColumnId,
				'WIDTH' => $arColumnToBeMoved['WIDTH']
			);

			foreach ($arCurrentColumns as &$columnData)
			{
				if ($columnData['ID'] == $movedColumnId)
					continue;

				$arNewColumns[] = array(
					'ID'    => $columnData['ID'],
					'WIDTH' => $columnData['WIDTH']
				);
			}
			unset($columnData);
		}
		else
		{
			foreach ($arCurrentColumns as $columnData)
			{
				$columnId = $columnData['ID'];

				if ($columnId == $movedColumnId)
					continue;

				$arNewColumns[] = array(
					'ID'    => $columnId,
					'WIDTH' => $columnData['WIDTH']
				);

				if ($columnId == $moveAfterId)
				{
					$arNewColumns[] = array(
						'ID'    => $arColumnToBeMoved['ID'],
						'WIDTH' => $arColumnToBeMoved['WIDTH']
					);
				}
			}
		}

		$this->setColumns($arNewColumns);
		return (true);
	}


	public function setColumns($arNewColumns)
	{
		$currentPresetId = $this->oPreset->getSelectedPresetId();
		if ($currentPresetId == CTaskColumnPresetManager::PRESET_DEFAULT)
		{
			$arPresets = $this->oPreset->getPresets();
			if (count($arPresets) == 1)
				$newPresetId = $this->oPreset->createPreset($arNewColumns, '__AUTO__');
			else
			{
				// use first not default preset
				$arPresetsIds = array_keys($arPresets);
				$newPresetId = array_pop($arPresetsIds);
				if ($newPresetId == CTaskColumnPresetManager::PRESET_DEFAULT)
					$newPresetId = array_pop($arPresetsIds);
			}

			$this->oPreset->selectPresetId($newPresetId);
			$currentPresetId = $newPresetId;
		}

		$this->oPreset->setColumns($currentPresetId, $arNewColumns);
	}


	public function getCurrentPresetColumns()
	{
		$presetId = $this->oPreset->getSelectedPresetId();
		$arPresets = $this->oPreset->getPresets();
		CTaskAssert::assert(isset($arPresets[$presetId]));
		$arPreset = $arPresets[$presetId];

		return (unserialize($arPreset['SERIALIZED_COLUMNS']));
	}
}


class CTaskColumnPresetManager
{
	const PRESET_DEFAULT = -1;

	const MINIMAL_COLUMN_WIDTH = 45;

	// private constants:
	const columnsCategoryName  = 'tasks:list:columns';
	const columnsParamPresetId = 'selected_preset_id';

	private static $instances = array();

	private $userId    = null;
	private $contextId = null;


	/**
	 * @param $userId
	 * @param $contextId
	 */
	private function __construct($userId, $contextId)
	{
		$this->userId = (int) $userId;

		CTaskAssert::assertLaxIntegers($contextId);
		$contextId = (int) $contextId;
		$arKnownContexts = CTaskColumnContext::get();
		CTaskAssert::assert(in_array($contextId, $arKnownContexts));

		$this->contextId = $contextId;
	}


	/**
	 * @param $userId
	 * @param $contextId
	 * @return CTaskColumnPresetManager
	 */
	public static function getInstance($userId, $contextId)
	{
		CTaskAssert::assertLaxIntegers($userId, $contextId);
		CTaskAssert::assert(($userId > 0) && ($contextId > 0));

		$key = (int) $userId . '|' . $contextId;

		// Cache instance in pool
		if ( ! isset(self::$instances[$key]) )
			self::$instances[$key] = new self($userId, $contextId);

		return (self::$instances[$key]);
	}


	/**
	 * @return integer ID of current context (CTaskColumnContext::CONTEXT_*)
	 */
	public function getUserId()
	{
		return ($this->userId);
	}


	/**
	 * @return integer ID of current context (CTaskColumnContext::CONTEXT_*)
	 */
	public function getContextId()
	{
		return ($this->contextId);
	}


	/**
	 * Get presets for currently used context
	 * @return array of presets
	 */
	public function getPresets()
	{
		global $DB;

		IncludeModuleLangFile(__FILE__);

		switch ($this->contextId)
		{
			case CTaskColumnContext::CONTEXT_RESPONSIBLE:
				$arPresets = array(
					self::PRESET_DEFAULT => array(
						'ID'                 => self::PRESET_DEFAULT,
						'USER_ID'            => $this->userId,
						'CONTEXT_ID'         => $this->contextId,
						'NAME'               => GetMessage('TASKS_COLUMN_MANAGER_DEFAULT_PRESET_NAME'),
						'SERIALIZED_COLUMNS' => serialize(array(
							array(
								'ID'    => CTaskColumnList::COLUMN_TITLE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_DEADLINE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_ORIGINATOR,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_GRADE,
								'WIDTH' => 65
							)
						))
					)
				);
			break;

			case CTaskColumnContext::CONTEXT_ORIGINATOR:
				$arPresets = array(
					self::PRESET_DEFAULT => array(
						'ID'                 => self::PRESET_DEFAULT,
						'USER_ID'            => $this->userId,
						'CONTEXT_ID'         => $this->contextId,
						'NAME'               => GetMessage('TASKS_COLUMN_MANAGER_DEFAULT_PRESET_NAME'),
						'SERIALIZED_COLUMNS' => serialize(array(
							array(
								'ID'    => CTaskColumnList::COLUMN_TITLE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_DEADLINE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_RESPONSIBLE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_GRADE,
								'WIDTH' => 65
							)
						))
					)
				);
			break;

			default:
			case CTaskColumnContext::CONTEXT_ALL:
			case CTaskColumnContext::CONTEXT_AUDITOR:
			case CTaskColumnContext::CONTEXT_ACCOMPLICE:
			case CTaskColumnContext::CONTEXT_TASK_DETAIL:
				$arPresets = array(
					self::PRESET_DEFAULT => array(
						'ID'                 => self::PRESET_DEFAULT,
						'USER_ID'            => $this->userId,
						'CONTEXT_ID'         => $this->contextId,
						'NAME'               => GetMessage('TASKS_COLUMN_MANAGER_DEFAULT_PRESET_NAME'),
						'SERIALIZED_COLUMNS' => serialize(array(
							array(
								'ID'    => CTaskColumnList::COLUMN_TITLE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_DEADLINE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_ORIGINATOR,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_RESPONSIBLE,
								'WIDTH' => 0
							),
							array(
								'ID'    => CTaskColumnList::COLUMN_GRADE,
								'WIDTH' => 65
							)
						))
					)
				);
			break;
		}

		$rc = $DB->query(
			"SELECT ID, USER_ID, CONTEXT_ID, NAME, SERIALIZED_COLUMNS
			FROM b_tasks_columns
			WHERE USER_ID = " . $this->userId . " AND CONTEXT_ID = " . $this->contextId
		);

		while ($ar = $rc->fetch())
			$arPresets[$ar['ID']] = $ar;

		return ($arPresets);
	}


	/**
	 * Create preset for current context
	 * @param array $columns
	 * @param string $name
	 *
	 * @return integer $presetId
	 */
	public function createPreset($columns = array(), $name = '')
	{
		global $DB;

		self::checkColumns($columns);

		if (empty($columns))
			$columns = array(array('ID' => CTaskColumnList::COLUMN_TITLE, 'WIDTH' => 1));

		$arFields = array(
			'USER_ID'            => $this->userId,
			'CONTEXT_ID'         => $this->contextId,
			'NAME'               => $name,
			'SERIALIZED_COLUMNS' => serialize($columns)
		);

		$presetId = $DB->add('b_tasks_columns', $arFields, array('SERIALIZED_COLUMNS'), 'tasks');

		return ($presetId);
	}


	public function renamePreset($presetId, $name)
	{
		global $DB;

		$arFields = array('NAME' => $name);

		$strUpdate = $DB->PrepareUpdate("b_tasks_columns", $arFields, "tasks");
		$strSql = "UPDATE b_tasks_columns SET " . $strUpdate
			. " WHERE ID=" . $presetId . " AND USER_ID = " . $this->userId . " AND CONTEXT_ID = " . $this->contextId;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	public function setColumns($presetId, $columns)
	{
		global $DB;

		CTaskAssert::assertLaxIntegers($presetId);
		$presetId = (int) $presetId;
		CTaskAssert::assert($presetId > 0);

		self::checkColumns($columns);

		if (empty($columns))
			$columns = array(array('ID' => CTaskColumnList::COLUMN_TITLE, 'WIDTH' => 1));

		$arFields = array(
			'SERIALIZED_COLUMNS' => serialize($columns)
		);

		$arBinds = array(
			'SERIALIZED_COLUMNS' => $arFields['SERIALIZED_COLUMNS']
		);

		$strUpdate = $DB->PrepareUpdate("b_tasks_columns", $arFields, "tasks");
		$strSql = "UPDATE b_tasks_columns SET " . $strUpdate
			. " WHERE ID=" . $presetId . " AND USER_ID = " . $this->userId . " AND CONTEXT_ID = " . $this->contextId;
		$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	private function checkColumns($columns)
	{
		$arAllowedColumnsIDs = array_keys(CTaskColumnList::get());

		CTaskAssert::assert(is_array($columns));

		foreach ($columns as &$columnData)
		{
			CTaskAssert::assert(
				isset($columnData['ID'], $columnData['WIDTH'])
				&& (count($columnData) == 2)
				&& CTaskAssert::isLaxIntegers($columnData['ID'], $columnData['WIDTH'])
				&& in_array($columnData['ID'], $arAllowedColumnsIDs)
				&& ($columnData['WIDTH'] >= 0)
			);
		}
		unset($columnData);
	}


	/**
	 * @return int selected preset ID for used context
	 */
	public function getSelectedPresetId()
	{
		$rc = (int) CUserOptions::GetOption(
			self::columnsCategoryName,
			self::columnsParamPresetId . '_' . $this->contextId,
			(string) self::PRESET_DEFAULT,	// by default
			$this->userId
		);

		return ($rc);
	}


	/**
	 * @param int $presetId set preset ID for used context
	 */
	public function selectPresetId($presetId)
	{
		CUserOptions::SetOption(
			self::columnsCategoryName,
			self::columnsParamPresetId . '_' . $this->contextId,
			(string) $presetId,
			$bCommon = false,
			$this->userId
		);
	}
}
