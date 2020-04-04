<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main;
use Bitrix\Tasks\Util\User;

IncludeModuleLangFile(__FILE__);


interface CTaskFilterCtrlInterface
{
	// Standart filter presets
	const ROOT_PRESET                        =  0;	// Root element for all presets
	const STD_PRESET_ACTIVE_MY_TASKS         = -1;
	const STD_PRESET_ACTIVE_I_AM_DOER        = -2;	// This is selected by default
	const STD_PRESET_ACTIVE_I_AM_ORIGINATOR  = -3;
	const STD_PRESET_ACTIVE_I_AM_AUDITOR     = -4;
	const STD_PRESET_DEFERRED_MY_TASKS       = -5;
	const STD_PRESET_COMPLETED_MY_TASKS      = -6;
	const STD_PRESET_ACTIVE_I_AM_RESPONSIBLE = -7;
	const STD_PRESET_ACTIVE_I_AM_ACCOMPLICE  = -8;
	const STD_PRESET_ALL_MY_TASKS            = -9;
	const STD_PRESET_EXPIRED                 = -11;
	const STD_PRESET_EXPIRED_SOON            = -12;
	const STD_PRESET_ALIAS_TO_DEFAULT        =  self::STD_PRESET_ALL_MY_TASKS;

	const SPEC_PRESET_FAVORITE_TASKS          = -10;

	// Identifications for CUserOptions
	// Warning: this is private constants, don't use it!!!
	const filterCategoryName  = 'tasks:mobile:filter';
	const filterParamPresetId = 'selected_preset_id';

	// For manifest of operations types
	const OP_EQUAL            = 0x001;		// no prefix for this case in filed name
	const OP_NOT_EQUAL        = 0x002;		// !
	const OP_SUBSTRING        = 0x003;		// %
	const OP_NOT_SUBSTRING    = 0x004;		// !%
	const OP_STRICTLY_LESS    = 0x010;		// <
	const OP_STRICTLY_GREATER = 0x011;		// >
	const OP_LESS_OR_EQUAL    = 0x012;		// <=
	const OP_GREATER_OR_EQUAL = 0x013;		// >=

	// Pseudo operations on date fields
	const OP_DATE_TODAY      = 0x005;		// #RC#
	const OP_DATE_YESTERDAY  = 0x006;		// #RC(-1)#
	const OP_DATE_TOMORROW   = 0x007;		// #RC1#
	const OP_DATE_CUR_WEEK   = 0x008;		// #R1C#
	const OP_DATE_PREV_WEEK  = 0x009;		// #R1C(-1)#
	const OP_DATE_NEXT_WEEK  = 0x00A;		// #R1C1#
	const OP_DATE_CUR_MONTH  = 0x00B;		// #R2C#
	const OP_DATE_PREV_MONTH = 0x00C;		// #R2C(-1)#
	const OP_DATE_NEXT_MONTH = 0x00D;		// #R2C1#
	const OP_DATE_LAST_DAYS  = 0x00E;		// #RC(-x)#
	const OP_DATE_NEXT_DAYS  = 0x00F;		// #RCx#

	// For manifest of field types
	const TYPE_TEXT     = 0x101;
	const TYPE_GROUP_ID = 0x102;
	const TYPE_USER_ID  = 0x103;
	const TYPE_STATUS   = 0x104;
	const TYPE_DATE     = 0x105;
	const TYPE_PRIORITY = 0x106;

	// Import modes
	const IMPORT_MODE_CREATE  = 0x01;	// Create new preset when import
	const IMPORT_MODE_REPLACE = 0x02;	// Replace existing preset during import


	/**
	 * List all available filter presets for given user.
	 * 
	 * @param boolean $bTreeMode - false by default. If true, than
	 * children filter presets will be placed at parent presets in '#Children' field.
	 * 
	 * @return array where keys are filter ids, and values are arrays,
	 * that contain key 'FilterName' with filter name and key 'ChildrenFilters'
	 * with array of children filters.
	 * 
	 * @example of return value
	 * array (
	 *  -1 => array(
	 *      'Name' => 'My tasks',
	 *      'Condition' => '...',
	 *      'Parent' => NULL	// This preset doesn't have parent
	 * ),
	 * -2 => array(
	 *      'Name' => "I'm responsible",
	 *      'Condition' => '...',
	 *      'Parent' => -1		// 'My tasks' is parent for this filter
	 * ), 
	 * ...
	 * )
	 */
	public function listFilterPresets($bTreeMode = false);


	/**
	 * Selects some filter preset. It will be automatically saved for given 
	 * user (through CUserOptions).
	 * 
	 * @param integer $presetId. Preset must be exists, otherwise exception will be throwed.
	 */
	public function switchFilterPreset($presetId);


	/**
	 * Get id of preset, currently selected for user (choose are saved in CUserOptions)
	 * 
	 * @return integer presetd id
	 */
	public function getSelectedFilterPresetId();


	/**
	 * Get selected filter as array for CTasks::GetList()
	 * 
	 * @return array filter
	 */
	public function getSelectedFilterPresetCondition();


	/**
	 * Get selected filter name
	 * 
	 * @return array filter
	 */
	public function getSelectedFilterPresetName();


	/**
	 * Create new preset for user
	 */
	public function createPreset($arPresetData);


	/**
	 * Remove user preset
	 */
	public function removePreset($presetId);
}


/**
 * Tasks filters controller
 */
class CTaskFilterCtrl implements CTaskFilterCtrlInterface
{
	protected static $instanceOfSelf = array();

	protected $userId = false;
	protected $loggedInUserId = false;
	protected $selectedFilterPreset = self::STD_PRESET_ALIAS_TO_DEFAULT;
	protected $arPresets = array();
	protected $paramName = '';
	private   $bGroupMode = null;

	private static $arOperationsMap = array(
		self::OP_EQUAL            => '',
		self::OP_NOT_EQUAL        => '!',
		self::OP_SUBSTRING        => '%',
		self::OP_NOT_SUBSTRING    => '!%',
		self::OP_STRICTLY_LESS    => '<',
		self::OP_STRICTLY_GREATER => '>',
		self::OP_LESS_OR_EQUAL    => '<=',
		self::OP_GREATER_OR_EQUAL => '>=',
		self::OP_DATE_TODAY       => '#RC#',
		self::OP_DATE_YESTERDAY   => '#RC(-1)#',
		self::OP_DATE_TOMORROW    => '#RC1#',
		self::OP_DATE_CUR_WEEK    => '#R1C#',
		self::OP_DATE_PREV_WEEK   => '#R1C(-1)#',
		self::OP_DATE_NEXT_WEEK   => '#R1C1#',
		self::OP_DATE_CUR_MONTH   => '#R2C#',
		self::OP_DATE_PREV_MONTH  => '#R2C(-1)#',
		self::OP_DATE_NEXT_MONTH  => '#R2C1#',
		self::OP_DATE_LAST_DAYS   => '#RC(-x)#',
		self::OP_DATE_NEXT_DAYS   => '#RCx#'
	);

	/**
	 * prevent creating through "new"
	 *
	 * @param $userId
	 * @param bool $bGroupMode
	 */
	private function __construct($userId, $bGroupMode = false)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);
		CTaskAssert::assert(is_bool($bGroupMode));

		$this->userId     = $userId;
		$this->bGroupMode = $bGroupMode;

		if (User::isAuthorized())
		{
			$this->loggedInUserId = User::getId();
			$this->paramName = self::filterParamPresetId . '_by_user_' . $this->loggedInUserId;
		}
		else
			$this->paramName = self::filterParamPresetId;

		$this->arPresets = $this->FetchFilterPresets();
	}


	// prevent clone of object
	public function __clone()
	{
		throw new Main\SystemException('clone is not allowed');
	}


	// prevent wakeup
	public function __wakeup()
	{
		throw new Main\SystemException('wakeup is not allowed');
	}


	/**
	 * Get instance of multiton filter controller
	 *
	 * @param integer $userId
	 * @param bool $bGroupMode
	 * @return CTaskFilterCtrl
	 */
	public static function getInstance($userId, $bGroupMode = false)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);
		CTaskAssert::assert(is_bool($bGroupMode));

		$key = $userId . '|' . ($bGroupMode ? 'Y' : 'N');

		if ( ! array_key_exists($key, self::$instanceOfSelf) )
			self::$instanceOfSelf[$key] = new self($userId, $bGroupMode);

		return (self::$instanceOfSelf[$key]);
	}


	/**
	 * Fetch predefined presets and presets from DB.
	 * 
	 * @return array of fetched filter presets. Includes predefined presets.
	 *
	 * @var CDatabase $DB
	 */
	protected function fetchFilterPresets()
	{
		global $DB;

		$arActiveStatuses = array(
			CTasks::METASTATE_VIRGIN_NEW,
			CTasks::METASTATE_EXPIRED,
			CTasks::STATE_NEW,
			CTasks::STATE_PENDING,
			CTasks::STATE_IN_PROGRESS
		);

		if ( ! $this->bGroupMode )
		{
			// Init list with predefined presets
			$arPresets = array(
				self::ROOT_PRESET => array(
					'Name'      => '/',
					'Parent'    => null,	// This preset doesn't have parent
					'Condition' => null		// This preset doesn't have condition
				),
				self::STD_PRESET_ACTIVE_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => array(CTasks::STATE_DECLINED, CTasks::STATE_SUPPOSEDLY_COMPLETED)
								)
							)
						)
					),
					'SQL: select count' =>
						"SELECT COUNT(TT.ID) AS CNT
						FROM (
							SELECT T.ID
							FROM b_tasks T
							INNER JOIN b_tasks_member TM on TM.TASK_ID = T.ID
							WHERE T.CREATED_BY = $this->userId
								AND T.RESPONSIBLE_ID != $this->userId
								AND TM.USER_ID = $this->userId
								AND T.STATUS != 4
								AND T.STATUS != 5
								AND T.ZOMBIE = 'N'

							UNION 

							SELECT T.ID
							FROM b_tasks T
							INNER JOIN b_tasks_member TM on TM.TASK_ID = T.ID
							WHERE T.RESPONSIBLE_ID = $this->userId
								AND TM.USER_ID = $this->userId
								AND T.STATUS != 4
								AND T.STATUS != 5
								AND T.ZOMBIE = 'N'

							UNION 

							SELECT T.ID
							FROM b_tasks T
							WHERE T.CREATED_BY = $this->userId
								AND (T.STATUS = 4 OR T.STATUS = 5)
								AND T.ZOMBIE = 'N'
						) AS TT
					"
				),
				self::STD_PRESET_ACTIVE_I_AM_DOER => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_I_AM_DOER'),
					'Parent'    => self::STD_PRESET_ACTIVE_MY_TASKS,	// 'My tasks' is the parent of this filter preset
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'DOER'    => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => CTasks::STATE_DECLINED
								)
							)
						)
					)
				),
				self::STD_PRESET_ACTIVE_I_AM_RESPONSIBLE => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_I_AM_RESPONSIBLE'),
					'Parent'    => self::STD_PRESET_ACTIVE_I_AM_DOER,	// 'I_AM_DOER' is the parent of this filter preset
					'Condition' => serialize(
						array(
							'::LOGIC'        => 'AND',
							'RESPONSIBLE_ID' => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => CTasks::STATE_DECLINED
								)
							)
						)
					)
				),
				self::STD_PRESET_ACTIVE_I_AM_ACCOMPLICE => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_I_AM_ACCOMPLICE'),
					'Parent'    => self::STD_PRESET_ACTIVE_I_AM_DOER,	// 'I_AM_DOER' is the parent of this filter preset
					'Condition' => serialize(
						array(
							'::LOGIC'    => 'AND',
							'ACCOMPLICE' => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => CTasks::STATE_DECLINED
								)
							)
						)
					)
				),
				self::STD_PRESET_ACTIVE_I_AM_ORIGINATOR => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_I_AM_ORIGINATOR'),
					'Parent'    => self::STD_PRESET_ACTIVE_MY_TASKS,	// 'My tasks' is the parent of this filter preset
					'Condition' => serialize(
						array(
							'::LOGIC'    => 'AND',
							'CREATED_BY' => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => array(CTasks::STATE_DECLINED, CTasks::STATE_SUPPOSEDLY_COMPLETED)
								)
							)
						)
					)
				),
				self::STD_PRESET_ACTIVE_I_AM_AUDITOR => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_I_AM_AUDITOR'),
					'Parent'    => self::STD_PRESET_ACTIVE_MY_TASKS,	// 'My tasks' is the parent of this filter preset
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'AUDITOR' => $this->userId,
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => CTasks::STATE_DECLINED
								)
							)
						)
					)
				),
				self::STD_PRESET_EXPIRED => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_EXPIRED'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => CTasks::METASTATE_EXPIRED
						)
					)
				),
				self::STD_PRESET_EXPIRED_SOON => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_EXPIRED_SOON'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => CTasks::METASTATE_EXPIRED_SOON
						)
					)
				),
				self::STD_PRESET_DEFERRED_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_DEFERRED_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => CTasks::STATE_DEFERRED
						)
					)
				),
				self::STD_PRESET_COMPLETED_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_COMPLETED_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => array(
								CTasks::STATE_SUPPOSEDLY_COMPLETED, 
								CTasks::STATE_COMPLETED
							)
						)
					)
				),
				self::STD_PRESET_ALL_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ALL_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId
						)
					)
				)
			);
		}
		else
		{
			// Init list with predefined presets
			$arPresets = array(
				self::ROOT_PRESET => array(
					'Name'      => '/',
					'Parent'    => null,	// This preset doesn't have parent
					'Condition' => null		// This preset doesn't have condition
				),
				self::STD_PRESET_ACTIVE_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ACTIVE_GROUP_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'::SUBFILTER-1' => array(
								'::LOGIC' => 'OR',
								'STATUS'  => $arActiveStatuses,
								'::SUBFILTER-1' => array(
									'::LOGIC'    => 'AND',
									'CREATED_BY' => $this->userId,
									'STATUS'     => CTasks::STATE_DECLINED
								)
							)
						)
					)
				),

				self::STD_PRESET_EXPIRED => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_EXPIRED'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => CTasks::METASTATE_EXPIRED
						)
					)
				),
				self::STD_PRESET_EXPIRED_SOON => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_EXPIRED_SOON'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'MEMBER'  => $this->userId,
							'STATUS'  => CTasks::METASTATE_EXPIRED_SOON
						)
					)
				),

				self::STD_PRESET_DEFERRED_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_DEFERRED_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'STATUS'  => CTasks::STATE_DEFERRED
						)
					)
				),
				self::STD_PRESET_COMPLETED_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_COMPLETED_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND',
							'STATUS'  => array(
								CTasks::STATE_SUPPOSEDLY_COMPLETED, 
								CTasks::STATE_COMPLETED
							)
						)
					)
				),
				self::STD_PRESET_ALL_MY_TASKS => array(
					'Name'      => GetMessage('TASKS_FILTER_PRESET_STD_PRESET_ALL_MY_TASKS'),
					'Parent'    => self::ROOT_PRESET,
					'Condition' => serialize(
						array(
							'::LOGIC' => 'AND'
						)
					)
				)
			);
		}

		$arPresets[self::SPEC_PRESET_FAVORITE_TASKS] = array(
			'Name'      => GetMessage('TASKS_FILTER_PRESET_SPEC_PRESET_FAVORITE_TASKS'),
			
			// commented out to prevent from figuring in tree list
			//'Parent'    => self::ROOT_PRESET,

			'Code' => 'FAVORITE',
			'Condition' => serialize(
				array_merge(
					array(
						'::LOGIC' => 'AND',
						'=FAVORITE' => 'Y',
					),
					(
						! $this->bGroupMode
						?
						array('MEMBER'  => $this->userId)
						:
						array()
					)
				)
			)
		);

		$arPresetsFromDb = array();
		$bNeedFetchFromDatabase = true;
		$cacheDir = $obCache = null;
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$obCache  =  new CPHPCache();
			$lifeTime =  CTasksTools::CACHE_TTL_UNLIM;
			$cacheDir = '/tasks/filter_presets/' . ($this->loggedInUserId % 300);
			$cacheId  = 'tasks_filters_presets_' . $this->loggedInUserId;

			if ($obCache->InitCache($lifeTime, $cacheId, $cacheDir))
			{
				$arPresetsFromDb = $obCache->GetVars();
				$bNeedFetchFromDatabase = false;
			}
		}

		if ($bNeedFetchFromDatabase)
		{
			$dbRes = $DB->query(
				"SELECT ID, NAME, PARENT, SERIALIZED_FILTER
				FROM b_tasks_filters
				WHERE USER_ID = " . (int) $this->loggedInUserId . "
				ORDER BY NAME, ID"
			);

			if ($dbRes)
			{
				while ($arData = $dbRes->fetch())
				{
					$arPresetsFromDb[(int)$arData['ID']] = array(
						'Name'      => $arData['NAME'],
						'Parent'    => (int) $arData['PARENT'],
						'Condition' => $arData['SERIALIZED_FILTER']
					);
				}
			}
			else
				CTaskAssert::log('DB error', CTaskAssert::ELL_ERROR, true);

			if (defined('BX_COMP_MANAGED_CACHE') && $obCache->StartDataCache())
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag('tasks_filters_presets_' . $this->loggedInUserId);
				$CACHE_MANAGER->EndTagCache();
				$obCache->EndDataCache($arPresetsFromDb);
			}
		}

		// Merge with predefined presets list
		foreach ($arPresetsFromDb as $presetId => $presetData)
			$arPresets[$presetId] = $presetData;

		return ($arPresets);
	}

	public function listFilterPresets($bTreeMode = false)
	{
		if ( ! $bTreeMode )
			return ($this->arPresets);
		else
			return (self::ConvertPresetsListToTree($this->arPresets));
	}

	public function listFilterSpecialPresets()
	{
		return array(
			self::SPEC_PRESET_FAVORITE_TASKS => $this->arPresets[self::SPEC_PRESET_FAVORITE_TASKS]
		);
	}

	protected function convertPresetsListToTree($arPresets)
	{
		// Remove top root element from presets
		if (array_key_exists(self::ROOT_PRESET, $arPresets))
			unset ($arPresets[self::ROOT_PRESET]);

		$curRoot = self::ROOT_PRESET;
		$arPresetsTree = $this->ConvertPresetsListToTreeHelper($arPresets, $curRoot);

		return ($arPresetsTree);
	}


	private function convertPresetsListToTreeHelper($arPresets, $curRoot)
	{
		$rc = array();

		foreach ($arPresets as $presetId => $arPresetData)
		{
			// current level items
			if ($arPresetData['Parent'] === $curRoot)
			{
				$rc[$presetId] = $arPresetData;
				unset ($arPresets[$presetId]);

				// collect children
				$arSubItems = $this->ConvertPresetsListToTreeHelper($arPresets, $presetId);
				if (count($arSubItems))
				{
					$rc[$presetId]['#Children'] = $arSubItems;

					foreach ($arSubItems as $subItemPresetId => $v)
						unset ($arPresets[$subItemPresetId]);
				}

				continue;
			}
		}

		return ($rc);
	}


	public function getSelectedFilterPresetId()
	{
		$rc = (int) CUserOptions::GetOption(
			self::filterCategoryName, 
			$this->paramName, 
			(string) self::STD_PRESET_ALIAS_TO_DEFAULT,	// by default
			$this->loggedInUserId
		);

		// ensure, that selected filter exists
		if ( ! in_array($rc, array_keys($this->arPresets), true) )
			$rc = self::STD_PRESET_ALIAS_TO_DEFAULT;

		return ($rc);
	}


	public function switchFilterPreset($presetId)
	{
		if ( ! CTaskAssert::isLaxIntegers($presetId) )
			throw new Main\ArgumentException();

		// ensure, that selected filter exists
		if ( ! in_array($presetId, array_keys($this->arPresets), true) )
			throw new Main\ArgumentException();

		$curPresetId = $this->getSelectedFilterPresetId();

		if ($presetId != $curPresetId)
		{
			CUserOptions::SetOption(
				self::filterCategoryName, 
				$this->paramName, 
				(string) $presetId,
				$bCommon = false,
				$this->loggedInUserId
			);
		}
	}


	public function getSelectedFilterPresetCondition()
	{
		$presetId = $this->GetSelectedFilterPresetId();

		return ($this->getFilterPresetConditionById($presetId));
	}

	public function checkExistsPresetById($presetId)
	{
		return array_key_exists($presetId, $this->arPresets);
	}

	public function getFilterPresetConditionById($presetId)
	{
		CTaskAssert::assertLaxIntegers ($presetId);
		$presetId = (int) $presetId;

		if ( !$this->checkExistsPresetById($presetId) )
			return (false);
		
		// Root preset is pseudo preset, it doesn't have a filter condition
		if ($presetId === self::ROOT_PRESET)
			return (false);

		return (array('::SUBFILTER-ROOT' => unserialize($this->arPresets[$presetId]['Condition'])));
	}


	public function getSelectedFilterPresetName()
	{
		$presetId = $this->GetSelectedFilterPresetId();

		return ($this->arPresets[$presetId]['Name']);
	}


	public function createPreset($arPresetData)
	{
		return ($this->addOrReplacePreset($arPresetData));
	}


	public function replacePreset($presetId, $arPresetData)
	{
		CTaskAssert::assert($presetId !== null);

		$rc = $this->addOrReplacePreset($arPresetData, $presetId);

		if ($rc === false)
			throw new TasksException('', TasksException::TE_UNKNOWN_ERROR);

		return ( (int) $presetId);
	}


	/**
	 * @param $arPresetData
	 * @param null $presetId
	 * @return mixed
	 * @throws Exception
	 *
	 * @var CDatabase $DB
	 * @var CCacheManager $CACHE_MANAGER
	 */
	private function addOrReplacePreset($arPresetData, $presetId = null)
	{
		global $DB, $CACHE_MANAGER;

		if ( ! (
			isset($arPresetData['Name'])
			&& (strlen($arPresetData['Name']) <= 100)
			&& isset($arPresetData['Parent'])
			&& isset($arPresetData['Condition'])
			&& is_array($arPresetData['Condition'])
			&& ($arPresetData['Parent'] === self::ROOT_PRESET)
		) )
		{
			throw new Main\ArgumentException('Invalid preset data');
		}

		// Remove CHECK_PERMISSIONS keys from arFilter, because CTasks::GetList() eat it
		$arSafeCondition = self::removeArrayKeyRecursively($arPresetData['Condition'], 'CHECK_PERMISSIONS');

		$arFields = array(
			'NAME'              => $arPresetData['Name'],
			'PARENT'            => (int) $arPresetData['Parent'],
			'SERIALIZED_FILTER' => serialize($arSafeCondition)
		);

		$arBinds = array('SERIALIZED_FILTER');

		// Replace existing preset?
		if ($presetId !== null)
		{
			CTaskAssert::assertLaxIntegers($presetId);
			CTaskAssert::assert($presetId > 0);

			$strUpdate = $DB->PrepareUpdate('b_tasks_filters', $arFields, 'tasks');
			$strSql = "UPDATE b_tasks_filters SET " . $strUpdate 
				. " WHERE ID=" . (int) $presetId . " AND USER_ID=" . (int) $this->userId;

			$DB->QueryBind($strSql, $arBinds, true);
			$rc = $presetId;
		}
		else
		{
			$arFields['USER_ID'] = (int) $this->userId;
			$rc = $DB->Add('b_tasks_filters', $arFields, $arBinds, 'tasks');
		}

		$this->reloadPresetsCache();
		$CACHE_MANAGER->ClearByTag('tasks_filters_presets_' . $this->userId);

		return ($rc);
	}


	/**
	 * @param int $presetId
	 *
	 * @var CDatabase $DB
	 */
	public function removePreset($presetId)
	{
		global $DB, $CACHE_MANAGER;

		CTaskAssert::assertLaxIntegers($presetId);
		CTaskAssert::assert($presetId > 0);

		// Switch to default preset, if preset to be removed is selected now
		if ($this->getSelectedFilterPresetId() == $presetId)
			$this->switchFilterPreset(self::STD_PRESET_ALIAS_TO_DEFAULT);

		$DB->query(
			"DELETE FROM b_tasks_filters
			WHERE ID = " . (int) $presetId 
				. " AND USER_ID = " . (int) $this->userId
		);

		$this->reloadPresetsCache();
		$CACHE_MANAGER->ClearByTag('tasks_filters_presets_' . $this->userId);
	}


	private function reloadPresetsCache()
	{
		$this->arPresets = $this->FetchFilterPresets();
	}


	public static function getManifest()
	{
		$arManifest = array(
			'Manifest version' => '1',
			'Fields' => array(
				'TITLE' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL,
						CTaskFilterCtrl::OP_SUBSTRING,
						CTaskFilterCtrl::OP_NOT_SUBSTRING
					),
					'Type' => CTaskFilterCtrl::TYPE_TEXT
				),
				'GROUP_ID' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_GROUP_ID
				),
				'CREATED_BY' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_USER_ID
				),
				'RESPONSIBLE_ID' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_USER_ID
				),
				'ACCOMPLICE' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_USER_ID
				),
				'AUDITOR' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_USER_ID
				),
				'STATUS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_STATUS
				),
				'PRIORITY' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_NOT_EQUAL
					),
					'Type' => CTaskFilterCtrl::TYPE_PRIORITY
				),
				'META:DEADLINE_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				),
				'META:DATE_START_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				),
				'META:START_DATE_PLAN_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				),
				'META:END_DATE_PLAN_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				),
				'META:CREATED_DATE_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				),
				'META:CLOSED_DATE_TS' => array(
					'Supported operations' => array(
						CTaskFilterCtrl::OP_DATE_YESTERDAY,
						CTaskFilterCtrl::OP_DATE_TODAY,
						CTaskFilterCtrl::OP_DATE_TOMORROW,
						CTaskFilterCtrl::OP_DATE_PREV_WEEK,
						CTaskFilterCtrl::OP_DATE_CUR_WEEK,
						CTaskFilterCtrl::OP_DATE_NEXT_WEEK,
						CTaskFilterCtrl::OP_DATE_PREV_MONTH,
						CTaskFilterCtrl::OP_DATE_CUR_MONTH,
						CTaskFilterCtrl::OP_DATE_NEXT_MONTH,
						CTaskFilterCtrl::OP_DATE_LAST_DAYS,
						CTaskFilterCtrl::OP_DATE_NEXT_DAYS,
						CTaskFilterCtrl::OP_EQUAL,
						CTaskFilterCtrl::OP_STRICTLY_LESS,
						CTaskFilterCtrl::OP_STRICTLY_GREATER
					),
					'Type' => CTaskFilterCtrl::TYPE_DATE
				)
			),
			'Operations map' => self::$arOperationsMap
		);

		return ($arManifest);
	}


	public function importFilterDataFromJs($arPresetData,
		$mode = self::IMPORT_MODE_CREATE, $presetId = null
	)
	{
		CTaskAssert::assert(
			in_array($mode, array(self::IMPORT_MODE_CREATE, self::IMPORT_MODE_REPLACE), true)
			&& is_array($arPresetData)
			&& (count($arPresetData) === 3)
			&& isset($arPresetData['Name'], $arPresetData['Parent'], $arPresetData['Condition'])
			&& ($arPresetData['Parent'] === self::ROOT_PRESET)
			&& (strlen($arPresetData['Name']))
			&& is_array($arPresetData['Condition'])
		);

		$arPresetData['Condition'] = self::convertItemForImport($arPresetData['Condition']);

		if ($mode === self::IMPORT_MODE_CREATE)
			$newPresetId = $this->createPreset($arPresetData);
		else
		{
			CTaskAssert::assertLaxIntegers($presetId);
			CTaskAssert::assert($presetId > 0);

			$newPresetId = $this->replacePreset($presetId, $arPresetData);
		}

		return ($newPresetId);
	}


	public function exportFilterDataForJs($presetId)
	{
		if ( ! isset($this->arPresets[$presetId]) )
			return (false);

		$arPresetData = $this->arPresets[$presetId];
		$arPresetData['Condition'] = unserialize($this->arPresets[$presetId]['Condition']);
		$arPresetData['Condition'] = self::convertItemForExport($arPresetData['Condition']);

		return ($arPresetData);
	}


	private static function convertItemForImport($arItem)
	{
		static $arManifest = false;
		static $arAllowedFields = false;
		$arResult = array();

		if ($arManifest === false)
		{
			$arManifest      = self::getManifest();
			$arAllowedFields = array_keys($arManifest['Fields']);
		}

		foreach ($arItem as $itemName => $itemData)
		{
			CTaskAssert::assert(
				(strlen($itemName) > 2)
				|| CTaskAssert::isLaxIntegers($itemName)
			);

			if ($itemName === '::LOGIC')
			{
				$arResult[$itemName] = $itemData;
				continue;
			}

			if (substr($itemName, 0, 12) === '::SUBFILTER-')
			{
				$arResult[$itemName] = self::convertItemForImport($itemData);
				continue;
			}

			CTaskAssert::assert(
				isset($itemData['operation'], $itemData['field'])
				&& array_key_exists('value', $itemData)
				&& CTaskAssert::isLaxIntegers($itemData['operation'])
				&& is_string($itemData['field'])
				&& ($itemData['field'] !== '')
			);

			if ( ! in_array($itemData['field'], $arAllowedFields, true) )
				throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);

			$arSupportedOperations = $arManifest['Fields'][$itemData['field']]['Supported operations'];
			$itemType = $arManifest['Fields'][$itemData['field']]['Type'];
			$operation = (int) $itemData['operation'];

			if ( ! in_array($operation, $arSupportedOperations, true) )
				throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);

			// Resolve operation prefix
			CTaskAssert::assert(isset(self::$arOperationsMap[$operation]));
			$operationPrefix = self::$arOperationsMap[$operation];

			$value = false;

			switch ($itemType)
			{
				case self::TYPE_DATE:
					if ( ! (
						is_string($itemData['value'])
						|| CTaskAssert::isLaxIntegers($itemData['value'])
					))
					{
						throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);
					}

					$value = self::convertDateStringToTimestamp($operation, $itemData['value']);
				break;

				case self::TYPE_TEXT:
					if ( ! is_string($itemData['value']) )
						throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);

					$value = (string) $itemData['value'];
				break;

				case self::TYPE_PRIORITY:
					if ( ! (
						is_string($itemData['value'])
						|| CTaskAssert::isLaxIntegers($itemData['value'])
					))
					{
						throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);
					}

					$value = (int) $itemData['value'];

					if ( ! in_array(
						$value,
						array(CTasks::PRIORITY_LOW, CTasks::PRIORITY_AVERAGE, CTasks::PRIORITY_HIGH),
						true
					))
					{
						throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);
					}
				break;

				case self::TYPE_STATUS:
					if (is_array($itemData['value']))
					{
						foreach ($itemData['value'] as $statusId)
						{
							if ( ! CTaskAssert::isLaxIntegers($statusId) )
								throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);
						}

						$value = array_map('intval', $itemData['value']);
					}
					else
					{
						$statusId = $itemData['value'];
						if ( ! CTaskAssert::isLaxIntegers($statusId) )
							throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);

						$value = (int) $statusId;
					}
				break;

				case self::TYPE_GROUP_ID:
				case self::TYPE_USER_ID:
					$entityId = $itemData['value'];
					if ( ! CTaskAssert::isLaxIntegers($entityId) )
						throw new TasksException('', TasksException::TE_FILTER_MANIFEST_MISMATCH);

					$value = (int) $entityId;
				break;

				default:
					CTaskAssert::assert(false);
				break;
			}

			$arKey = $operationPrefix . $itemData['field'];

			while (isset($arResult[$arKey]))
				$arKey = ' ' . $arKey;

			$arResult[$arKey] = $value;
		}

		return ($arResult);

		/*
			data in:
			array(
				'::LOGIC' => 'AND',
				array(
					'field'     => 'RESPONSIBLE_ID',
					'operation' => CTaskFilterCtrlInterface::OP_EQUAL,
					'value'     => 1
				),
				'::SUBFILTER-1' => array(
					'::LOGIC' => 'OR',
					array(
						'field'     => 'CREATED_BY',
						'operation' => CTaskFilterCtrlInterface::OP_EQUAL,
						'value'     => 1
					),
					array(
						'field'     => 'CREATED_BY',
						'operation' => CTaskFilterCtrlInterface::OP_NOT_EQUAL,
						'value'     => 2
					)
				),
				'::SUBFILTER-2' => array(
					'::LOGIC' => 'AND',
					array(
						'field'     => 'TITLE',
						'operation' => CTaskFilterCtrlInterface::OP_SUBSTRING,
						'value'     => 1
					),
					array(
						'field'     => 'TITLE',
						'operation' => CTaskFilterCtrlInterface::OP_NOT_SUBSTRING,
						'value'     => 2
					)
				)
			)

			data out:
			$arFilter = array(
				'::LOGIC' => 'AND',
				'RESPONSIBLE_ID' => 1,
				'::SUBFILTER-1' => array(
					'::LOGIC' => 'OR',
					'CREATED_BY' => 1,
					'!=CREATED_BY' => 2
				),
				'::SUBFILTER-2' => array(
					'::LOGIC' => 'AND',
					'%TITLE' => 'some interesting substring',
					'!%TITLE' => 'some not interesting substring'
				)
			);
		*/
	}


	private static function convertItemForExport($arItem)
	{
		static $arFields = null;
		$arResult = array();

		if ($arFields === null)
		{
			$arManifest = self::getManifest();
			$arFields   = $arManifest['Fields'];
		}

		foreach ($arItem as $itemName => $itemData)
		{
			CTaskAssert::assert(strlen($itemName) > 2);

			if ($itemName === '::LOGIC')
			{
				$arResult[$itemName] = $itemData;
				continue;
			}

			if (substr($itemName, 0, 12) === '::SUBFILTER-')
			{
				$arResult[$itemName] = self::convertItemForExport($itemData);
				continue;
			}

			$value = $itemData;

			$itemName = ltrim($itemName);

			// Resolve operation code and cutoff operation prefix from item name
			$operation = null;
			foreach (self::$arOperationsMap as $operationCode => $operationPrefix)
			{
				$pattern = '/^' . preg_quote($operationPrefix) . '[A-Za-z]/';
				if (preg_match($pattern, $itemName))
				{
					$operation = $operationCode;
					$itemName  = substr($itemName, strlen($operationPrefix));
					break;
				}
			}

			CTaskAssert::assert($operation !== null);

			// Process values for date fields
			if (isset($arFields[$itemName]['Type']) && ($arFields[$itemName]['Type'] === self::TYPE_DATE))
				$value = self::convertTimestampToDateString($operation, $value);

			$arResult[] = array(
				'field'     => $itemName,
				'operation' => $operation,
				'value'     => $value
			);
		}

		return ($arResult);

		/*
			data in:
			$arFilter = array(
				'::LOGIC' => 'AND',
				'RESPONSIBLE_ID' => 1,
				'::SUBFILTER-1' => array(
					'::LOGIC' => 'OR',
					'CREATED_BY' => 1,
					'!=CREATED_BY' => 2
				),
				'::SUBFILTER-2' => array(
					'::LOGIC' => 'AND',
					'%TITLE' => 'some interesting substring',
					'!%TITLE' => 'some not interesting substring'
				)
			);

			data out:
			array(
				'::LOGIC' => 'AND',
				array(
					'field'     => 'RESPONSIBLE_ID',
					'operation' => CTaskFilterCtrlInterface::OP_EQUAL,
					'value'     => 1
				),
				'::SUBFILTER-1' => array(
					'::LOGIC' => 'OR',
					array(
						'field'     => 'CREATED_BY',
						'operation' => CTaskFilterCtrlInterface::OP_EQUAL,
						'value'     => 1
					),
					array(
						'field'     => 'CREATED_BY',
						'operation' => CTaskFilterCtrlInterface::OP_NOT_EQUAL,
						'value'     => 2
					)
				),
				'::SUBFILTER-2' => array(
					'::LOGIC' => 'AND',
					array(
						'field'     => 'TITLE',
						'operation' => CTaskFilterCtrlInterface::OP_SUBSTRING,
						'value'     => 1
					),
					array(
						'field'     => 'TITLE',
						'operation' => CTaskFilterCtrlInterface::OP_NOT_SUBSTRING,
						'value'     => 2
					)
				)
			)
		*/
	}


	private static function convertTimestampToDateString($operation, $value)
	{
		switch ($operation)
		{
			case self::OP_DATE_TODAY:
			case self::OP_DATE_YESTERDAY:
			case self::OP_DATE_TOMORROW:
			case self::OP_DATE_CUR_WEEK:
			case self::OP_DATE_PREV_WEEK:
			case self::OP_DATE_NEXT_WEEK:
			case self::OP_DATE_CUR_MONTH:
			case self::OP_DATE_PREV_MONTH:
			case self::OP_DATE_NEXT_MONTH:
				$value = '';
			break;

			case self::OP_DATE_LAST_DAYS:
			case self::OP_DATE_NEXT_DAYS:
				$value = (string) (int) $value;
			break;

			default:
			case self::OP_EQUAL:
			case self::OP_NOT_EQUAL:
			case self::OP_STRICTLY_LESS:
			case self::OP_STRICTLY_GREATER:
			case self::OP_LESS_OR_EQUAL:
			case self::OP_GREATER_OR_EQUAL:
				$bTzWasDisabled = ! CTimeZone::enabled();

				if ($bTzWasDisabled)
					CTimeZone::enable();

				$value = (string) ConvertTimeStamp($value + CTimeZone::getOffset());

				if ($bTzWasDisabled)
					CTimeZone::disable();
			break;
		}

		return ($value);
	}


	private static function convertDateStringToTimestamp($operation, $value)
	{
		switch ($operation)
		{
			case self::OP_DATE_TODAY:
			case self::OP_DATE_YESTERDAY:
			case self::OP_DATE_TOMORROW:
			case self::OP_DATE_CUR_WEEK:
			case self::OP_DATE_PREV_WEEK:
			case self::OP_DATE_NEXT_WEEK:
			case self::OP_DATE_CUR_MONTH:
			case self::OP_DATE_PREV_MONTH:
			case self::OP_DATE_NEXT_MONTH:
				$value = '';
			break;

			case self::OP_DATE_LAST_DAYS:
			case self::OP_DATE_NEXT_DAYS:
				$value = (int) $value;
			break;

			default:
			case self::OP_EQUAL:
			case self::OP_NOT_EQUAL:
			case self::OP_STRICTLY_LESS:
			case self::OP_STRICTLY_GREATER:
			case self::OP_LESS_OR_EQUAL:
			case self::OP_GREATER_OR_EQUAL:
				$bTzWasDisabled = ! CTimeZone::enabled();

				if ($bTzWasDisabled)
					CTimeZone::enable();

				// get correct UnixTimestamp
				$value = (int) MakeTimeStamp($value) - CTimeZone::getOffset();

				if ($bTzWasDisabled)
					CTimeZone::disable();
			break;
		}

		return ($value);
	}


	private static function removeArrayKeyRecursively($ar, $dropKey)
	{
		if (is_array($ar))
		{
			if (array_key_exists($dropKey, $ar))
				unset($ar[$dropKey]);

			foreach(array_keys($ar) as $k)
				$ar[$k] = self::removeArrayKeyRecursively($ar[$k], $dropKey);
		}

		return ($ar);
	}
}
