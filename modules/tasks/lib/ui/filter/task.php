<?php
// @DEPRECATED ! DONT CHANGE! DONT USE!
namespace Bitrix\Tasks\Ui\Filter;

use Bitrix\Main\Grid;
use Bitrix\Main\Context;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Tasks\Util;


class Task
{
	protected static $filterId = '';
	protected static $filterSuffix = '';
	protected static $groupId = 0;
	protected static $userId = 0;
	protected static $gridOptions = null;
	protected static $filterOptions = null;

	//	public static function getDefaultPresetKey()
	//	{
	//		static $out = null;
	//
	//		if($out === null)
	//		{
	//			$out = self::getFilterOptions()->getDefaultFilterId();
	//		}
	//
	//		return $out;
	//	}

	/**
	 * Get available fields in filter.
	 * @return array
	 */
	//	protected static function getAvailableFields()
	//	{
	//		$fields = array(
	//			'ID',
	//			'TITLE',
	////			'REAL_STATUS',
	//			'STATUS',
	//			'PROBLEM',
	//			'PARAMS',
	//			'PRIORITY',
	//			'MARK',
	//			'ALLOW_TIME_TRACKING',
	//			'DEADLINE',
	//			'CREATED_DATE',
	//			'CLOSED_DATE',
	//			'DATE_START',
	//			'START_DATE_PLAN',
	//			'END_DATE_PLAN',
	//			'RESPONSIBLE_ID',
	//			'CREATED_BY',
	//			'ACCOMPLICE',
	//			'AUDITOR',
	//			'TAG',
	//			'ACTIVE',
	//			'ROLEID',
	//		);
	//
	//		if(static::getGroupId() == 0)
	//			$fields[]='GROUP_ID';
	//
	//		return $fields;
	//	}

	/**
	 * @return Grid\Options
	 */
	//	public static function getGridOptions()
	//	{
	//		if (is_null(static::$gridOptions) || !(static::$gridOptions instanceof Grid\Options))
	//		{
	//			static::$gridOptions = new Grid\Options(static::getFilterId());
	//		}
	//
	//		return static::$gridOptions;
	//	}

	//	/**
	//	 * @return string
	//	 */
	//	public static function getFilterId()
	//	{
	////		if(!static::$filterId)
	//		{
	//			$stateInstance = static::getListStateInstance();
	//			$roleId = 4096;//$stateInstance->getUserRole();
	////			$section = $stateInstance->getSection();
	//			$typeFilter = 'ADVANCED';//\CTaskListState::VIEW_SECTION_ADVANCED_FILTER == $section ? 'ADVANCED' : 'MAIN';
	//
	////			$state = $stateInstance->getState();
	//			$presetSelected = 'N';//array_key_exists('PRESET_SELECTED', $state) && $state['PRESET_SELECTED']['ID'] == -10  ? 'Y' : 'N';
	//
	//			static::$filterId = 'TASKS_GRID_ROLE_ID_' . $roleId . '_' . (int)(static::getGroupId() > 0).'_'.$typeFilter.'_'.$presetSelected.static::$filterSuffix;
	//		}
	//
	//		return static::$filterId;
	//	}
	//
	//	/**
	//	 * @param $filterId
	//	 */
	//	public static function setFilterId($filterId)
	//	{
	//		static::$filterId = $filterId;
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	public static function getPresets()
	//	{
	//		$presets = array(
	//			'filter_tasks_in_progress' => array(
	//				'name' => Loc::getMessage('TASKS_PRESET_IN_PROGRESS'),
	//				'default' => true,
	//				'fields' => array(
	//					'STATUS' => array(
	//						\CTasks::STATE_PENDING,
	//						\CTasks::STATE_IN_PROGRESS
	//					)
	//				)
	//			),
	//			'filter_tasks_completed' => array(
	//				'name' => Loc::getMessage('TASKS_PRESET_COMPLETED'),
	//				'default' => false,
	//				'fields' => array(
	//					'STATUS' => array(
	//						\CTasks::STATE_COMPLETED
	//					)
	//				)
	//			),
	//			'filter_tasks_deferred' => array(
	//				'name' => Loc::getMessage('TASKS_PRESET_DEFERRED'),
	//				'default' => false,
	//				'fields' => array(
	//					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_DEFERRED
	//				)
	//			),
	//			'filter_tasks_expire' => array(
	//				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED'),
	//				'default' => false,
	//				'fields' => array(
	//					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
	//				)
	//			),
	//			'filter_tasks_expire_candidate' => array(
	//				'name' => Loc::getMessage('TASKS_PRESET_EXPIRED_CAND'),
	//				'default' => false,
	//				'fields' => array(
	//					'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
	//				)
	//			)
	//		);
	//
	//		return $presets;
	//	}
	//
	//	/**
	//	 * @param bool $processGridFilter
	//	 *
	//	 * @return array
	//	 */
	//	public static function processFilter($isMobile = false)
	//	{
	//		$arrFilter = array();
	//		if($isMobile)
	//		{
	//			return $arrFilter;
	//		}
	//
	//		$arrFilter = self::internalProcessFilter();
	//
	//		return $arrFilter;
	////		$stateFilter = static::processStateFilter();
	////		$specialFilter = static::processSpecialPresetsFilter();
	//		$gridFilter = $processGridFilter ? static::processGridFilter($stateFilter) : array();
	//	}
	//
	//	/**
	//	 * Main point of create filter for tasks getlist
	//	 *
	//	 * @param array $gridFilter - Filter from grid
	//	 * @param null $userId 		- userId from arParams
	//	 * @param null $groupId 	- groupId from arParams
	//	 * @param array $params 	- any params
	//	 *
	//	 * @return array arrFilter
	//	 */
	//	private static function internalProcessFilter($userId = null, $groupId = null, array $params = array())
	//	{
	//		$filters = static::getFilters();
	//		$filterData = static::getFilterData();
	//		$arrFilter = array();
	//
	//		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData[ 'FILTER_APPLIED' ] != true)
	//		{
	//			$arrFilter['MEMBER'] = Util\User::getId();
	//
	//			return $arrFilter;
	//		}
	//
	//		return array();
	//	}
	//
	//
	//	/**
	//	 * @param array $stateFilter
	//	 *
	//	 * @return array
	//	 */
	//	protected static function processGridFilter(&$stateFilter = array())
	//	{
	//		$filters = static::getFilters();
	//		$filterData = static::getFilterData();
	//		$arrFilter = array();
	//
	//
	//		if (!array_key_exists('FILTER_APPLIED', $filterData) || $filterData[ 'FILTER_APPLIED' ] != true)
	//		{
	//			//			$arrFilter['CHECK_PERMISSIONS'] = 'Y';
	//			//			$arrFilter["SUBORDINATE_TASKS"] = "N";
	//
	//			//			$arrFilter['::SUBFILTER-ALL']['::LOGIC']='OR';
	//			//			$arrFilter['::SUBFILTER-ALL']["CREATED_BY"] = Util\User::getId();
	//			//			$arrFilter['::SUBFILTER-ALL']["RESPONSIBLE_ID"] = Util\User::getId();
	//			//			$arrFilter['::SUBFILTER-ALL']["ACCOMPLICE"] = array(Util\User::getId());
	//			//			$arrFilter['::SUBFILTER-ALL']["AUDITOR"] = array(Util\User::getId());
	//
	//			$arrFilter['MEMBER'] = Util\User::getId();
	//
	//			return $arrFilter;
	//		}
	//
	//		$arrFilter = array();
	//
	//		if (array_key_exists('FIND', $filterData) && !empty($filterData[ 'FIND' ]))
	//		{
	//			$arrFilter[ '*%SEARCH_INDEX' ] = trim($filterData[ 'FIND' ]);
	//		}
	//
	//		foreach ($filters as $filterRow)
	//		{
	//			switch ($filterRow[ 'type' ])
	//			{
	//				default:
	//					if (array_key_exists($filterRow[ 'id' ], $filterData) && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						if (is_numeric($filterData[ $filterRow[ 'id' ] ]) && !($filterRow[ 'id' ] == 'TITLE' && !empty($filterData[ $filterRow[ 'id' ] ])))
	//						{
	//							$arrFilter[ $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] ];
	//						}
	//						else
	//						{
	//							$arrFilter[ '%' . $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] ];
	//						}
	//					}
	//					break;
	//				case 'date':
	//					if($filterRow[ 'id' ] == 'ACTIVE' && !empty($filterData[ $filterRow[ 'ACTIVE' ] ]))
	//					{
	//						$arrFilter['ACTIVE']['START'] = $filterData[ $filterRow[ 'id' ] . '_from' ];
	//						$arrFilter['ACTIVE']['END'] = $filterData[ $filterRow[ 'id' ] . '_to' ];
	//
	//						continue;
	//					}
	//
	//					if (array_key_exists($filterRow[ 'id' ] . '_from', $filterData) && !empty($filterData[ $filterRow[ 'id' ] . '_from' ]))
	//					{
	//						$arrFilter[ '>=' . $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] . '_from' ];
	//					}
	//					if (array_key_exists($filterRow[ 'id' ] . '_to', $filterData) && !empty($filterData[ $filterRow[ 'id' ] . '_to' ]))
	//					{
	//						$arrFilter[ '<=' . $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] . '_to' ];
	//					}
	//					break;
	//				case 'number':
	//					if (array_key_exists($filterRow[ 'id' ] . '_from', $filterData) && !empty($filterData[ $filterRow[ 'id' ] . '_from' ]))
	//					{
	//						$arrFilter[ '>=' . $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] . '_from' ];
	//					}
	//					if (array_key_exists($filterRow[ 'id' ] . '_to', $filterData) && !empty($filterData[ $filterRow[ 'id' ] . '_to' ]))
	//					{
	//						$arrFilter[ '<=' . $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] . '_to' ];
	//					}
	//
	//					if (
	//						array_key_exists('>=' . $filterRow[ 'id' ], $arrFilter)
	//						&& array_key_exists('<=' . $filterRow[ 'id' ], $arrFilter)
	//						&& $arrFilter[ '>=' . $filterRow[ 'id' ] ] == $arrFilter[ '<=' . $filterRow[ 'id' ] ]
	//					)
	//					{
	//						$arrFilter[ $filterRow[ 'id' ] ] = $arrFilter[ '>=' . $filterRow[ 'id' ] ];
	//						unset($arrFilter[ '>=' . $filterRow[ 'id' ] ], $arrFilter[ '<=' . $filterRow[ 'id' ] ]);
	//					}
	//					break;
	//				case 'list':
	//					if ($filterRow[ 'id' ] == 'PARAMS' && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						$subfilter = array();
	//						foreach($filterData[ $filterRow[ 'id' ] ] as $param)
	//						{
	//
	//							switch ($param)
	//							{
	//								case 'FAVORITE':
	//									$subfilter["FAVORITE"] = 'Y';
	//									break;
	//								case 'MARKED':
	//									$subfilter["!MARK"] = false;
	//									break;
	//								case 'OVERDUED':
	//									$subfilter["OVERDUED"] = "Y";
	//									break;
	//								case 'IN_REPORT':
	//									$subfilter["ADD_IN_REPORT"] = "Y";
	//									break;
	//								case 'SUBORDINATE':
	//									// Don't set SUBORDINATE_TASKS for admin, it will cause all tasks to be showed
	//									if (!\Bitrix\Tasks\Util\User::isSuper())
	//									{
	//										$subfilter["SUBORDINATE_TASKS"] = "Y";
	//									}
	//									break;
	//								case 'ANY_TASK':
	//									unset($stateFilter['::SUBFILTER-ROOT']['MEMBER']);
	//									unset($stateFilter['MEMBER']);
	//									break;
	//							}
	//						}
	//
	//						$arrFilter['::SUBFILTER-PARAMS']=$subfilter;
	//						$arrFilter['::SUBFILTER-PARAMS']['::LOGIC']='OR';
	//					}
	//					else if ($filterRow[ 'id' ] == 'PROBLEM' && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						switch($filterData[ $filterRow[ 'id' ] ])
	//						{
	//							case \CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL:
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'REAL_STATUS' ][] = \CTasks::STATE_SUPPOSEDLY_COMPLETED;
	//								$arrFilter['!RESPONSIBLE_ID'] = self::getUserId();
	//								$arrFilter['=CREATED_BY'] = self::getUserId();
	//								break;
	////							case \CTaskListState::VIEW_TASK_CATEGORY_DEFERRED:
	////								$arrFilter['::SUBFILTER-PROBLEM'][ 'REAL_STATUS' ][] = \CTasks::STATE_DEFERRED;
	////								break;
	//							case \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED:
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::METASTATE_EXPIRED;
	//								if(self::$groupId ==0 &&(!array_key_exists('GROUP_ID', $filterData) || !$filterData['GROUP_ID']))
	//								{
	//									$arrFilter['::SUBFILTER-PROBLEM'][ 'MEMBER' ] = self::getUserId();
	//								}
	//								break;
	//							case \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES:
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::METASTATE_EXPIRED_SOON;
	////								$arrFilter['::SUBFILTER-PROBLEM'][ '!AUDITOR' ] = self::getUserId();
	//
	//								$arrFilter['::SUBFILTER-PROBLEM'][ '::SUBFILTER-O' ]['::LOGIC']='OR';
	//								$arrFilter['::SUBFILTER-PROBLEM'][ '::SUBFILTER-O' ][ 'ACCOMPLICE' ] = self::getUserId();
	//
	////								$arrFilter['::SUBFILTER-PROBLEM'][ 'MEMBER' ] = self::getUserId();
	//
	////								if(array_key_exists('ROLEID', $filterData) && $filterData['ROLEID'] == Counter\Role::ORIGINATOR)
	////								{
	//									$arrFilter['::SUBFILTER-PROBLEM'][ '::SUBFILTER-O' ]['RESPONSIBLE_ID'] = self::getUserId();
	////								}
	//
	//
	//								break;
	//							case \CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE:
	//								$arrFilter[ 'DEADLINE' ] = '';
	//								if(self::$groupId ==0 &&(!array_key_exists('GROUP_ID', $filterData) || !$filterData['GROUP_ID']))
	//								{
	//									$arrFilter['::SUBFILTER-PROBLEM'][ 'MEMBER' ] = self::getUserId();
	//								}
	//
	//								if (array_key_exists('ROLEID', $filterData))
	//								{
	//									if ($filterData['ROLEID'] == Counter\Role::RESPONSIBLE)
	//									{
	//
	//									}
	//									else
	//									{
	//										$arrFilter['::SUBFILTER-PROBLEM']['!ACCOMPLICE'] = self::getUserId();
	//										$arrFilter['::SUBFILTER-PROBLEM']['!AUDITOR'] = self::getUserId();
	//									}
	//								}
	//
//								if(array_key_exists('ROLEID', $filterData) && $filterData['ROLEID'] == Counter\Role::ORIGINATOR)
	//								{
	//
	//								}
	//								else
	//								{
	////									$arrFilter['::SUBFILTER-PROBLEM'][ '!CREATED_BY' ] = self::getUserId();
	//									$arrFilter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
	//								}
	//
	//
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::METASTATE_VIRGIN_NEW;
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::STATE_NEW;
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::STATE_PENDING;
	//								$arrFilter['::SUBFILTER-PROBLEM'][ 'STATUS' ][] = \CTasks::STATE_IN_PROGRESS;
	//								break;
	//							case \CTaskListState::VIEW_TASK_CATEGORY_NEW:
	//								if(self::$groupId ==0 &&(!array_key_exists('GROUP_ID', $filterData) || !$filterData['GROUP_ID']))
	//								{
	//									$arrFilter['::SUBFILTER-PROBLEM'][ 'MEMBER' ] = self::getUserId();
	//								}
	//
	//								if (array_key_exists('ROLEID', $filterData))
//								{
	//									if ($filterData['ROLEID'] == Counter\Role::RESPONSIBLE)
	//									{
	//
	//									}
	//									else
	//									{
	//										$arrFilter['::SUBFILTER-PROBLEM']['!AUDITOR'] = self::getUserId();
	//									}
	//								}
	//
	//								$arrFilter['::SUBFILTER-PROBLEM']['!CREATED_BY'] = self::getUserId();
	//
	//								$arrFilter['VIEWED'] = 0;
	//								$arrFilter['VIEWED_BY'] = self::getUserId();
	//								break;
	//							default:
	//
	//								break;
	//						}
	//					}
	//					elseif ($filterRow[ 'id' ] == 'STATUS' && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						$arrFilter[ 'REAL_STATUS' ] = $filterData[ $filterRow[ 'id' ] ];
	//					}
	//					elseif ($filterRow[ 'id' ] == 'ROLEID' && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						switch($filterData[ $filterRow[ 'id' ] ])
	//						{
	//							default:
	//								$arrFilter['::SUBFILTER-ROOT']['MEMBER'] = self::getUserId();
	//							break;
	//
	//							case 'view_role_responsible':
	//								$arrFilter['=RESPONSIBLE_ID'] = self::getUserId();
	//								break;
	//							case 'view_role_accomplice':
	//								$arrFilter['=ACCOMPLICE'] = self::getUserId();
	//								break;
	//							case 'view_role_auditor':
	//								$arrFilter['=AUDITOR'] = self::getUserId();
	//								break;
	//							case 'view_role_originator':
	//									$arrFilter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
	//									$arrFilter['=CREATED_BY'] = self::getUserId();
	//								break;
	//						}
	//					}
	//					elseif (array_key_exists($filterRow[ 'id' ], $filterData) && !empty($filterData[ $filterRow[ 'id' ] ]))
	//					{
	//						$arrFilter[ $filterRow[ 'id' ] ] = $filterData[ $filterRow[ 'id' ] ];
	//					}
	//					break;
	//			}
	//		}
	//
	//		// state member! refactoring!
	//		if (!array_key_exists('ROLEID', $filterData) || !$filterData['ROLEID'])
	//		{
	//			//			if(self::$groupId ==0 && (!array_key_exists('GROUP_ID', $filterData) || !$filterData['GROUP_ID']))
	//			//			{
	//				$arrFilter['MEMBER'] = self::getUserId();
	//			//			}
	//		}
	//
	//		// TEMP HACK
	//		if (count($arrFilter['GROUP_ID']) > 100)
	//		{
	//			$count = count($arrFilter['GROUP_ID']);
	//			$arrFilter['%GROUP_ID'] = array_slice($arrFilter['%GROUP_ID'], $count - 100);
	//		}
	//
	//		if ($filterData['PARAMS'] && in_array('ANY_TASK', $filterData['PARAMS']))
	//		{
	//			unset($arrFilter['MEMBER']);
	//		}
	//
	//		//		$arrFilter['CHECK_PERMISSIONS'] = 'Y';
	//		//		$arrFilter["SUBORDINATE_TASKS"] = "N";
	//
	//		return $arrFilter;
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	public static function getFilters()
	//	{
	//		static $filters = array();
	//
	//		if (empty($filters))
	//		{
	//			$filters = static::getFilterRaw();
	//			$defaultFilters = self::getDefaultFilterFields();
	//
	//			foreach ($defaultFilters as $fieldId)
	//			{
	//				if (array_key_exists($fieldId, $filters))
	//				{
	//					if(!array_key_exists('default', $filters[$fieldId]))
	//					{
	//						$filters[$fieldId]['default'] = true;
	//					}
	//				}
	//			}
	//		}
	//
	//		return $filters;
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	private static function getDefaultFilterFields()
	//	{
	//		$roleId = \CTaskListState::getInstance(Util\User::getId())->getUserRole();
	//
	//		switch ($roleId)
	//		{
	//			case \CTaskListState::VIEW_ROLE_ACCOMPLICE:
	//			case \CTaskListState::VIEW_ROLE_RESPONSIBLE:
	//				$defaultFields = array(
	//					'CREATED_BY',
	//					'DEADLINE',
	//					'STATUS',
	//					'GROUP_ID',
	//					'PROBLEM',
	//				);
	//				break;
	//			case \CTaskListState::VIEW_ROLE_ORIGINATOR:
	//				$defaultFields = array(
	//					'RESPONSIBLE_ID',
	//					'STATUS',
	//					'DEADLINE',
	//					'GROUP_ID',
	//					'PROBLEM',
	//				);
	//				break;
	//			default:
	//				$defaultFields = array(
	//					'CREATED_BY',
	//					'RESPONSIBLE_ID',
	//					'STATUS',
	//					'DEADLINE',
	//					'PROBLEM',
	//					'GROUP_ID'
	//				);
	//				break;
	//		}
	//
	//		return $defaultFields;
	//	}

	/**
	 * @return array
	 */
	//	public static function getVisibleColumns()
	//	{
	//		$columns = static::getGridOptions()->GetVisibleColumns();
	//
	//		if (empty($columns))
	//		{
	//			$columns = self::getDefaultVisibleColumns();
	//		}
	//
	//		return $columns;
	//	}

	/**
	 * @return array
	 */
	//	private static function getDefaultVisibleColumns()
	//	{
	//		/*
	//		$stateInstance = static::getListStateInstance();
	//		$roleId = $stateInstance->getUserRole();
	//		$section = $stateInstance->getSection();
	//		$typeFilter = \CTaskListState::VIEW_SECTION_ADVANCED_FILTER == $section ? 'ADVANCED' : 'MAIN';
	//
	//		if($typeFilter == 'ADVANCED')
	//		{
	//			$roleId = 'default';
	//		}
	//
	//		switch ($roleId)
	//		{
	//			case \CTaskListState::VIEW_ROLE_ACCOMPLICE:
	//			case \CTaskListState::VIEW_ROLE_RESPONSIBLE:
	//				$defaultColumns = array(
	//					'TITLE',
	//					'DEADLINE',
	//					'CREATED_BY',
	////					'ORIGINATOR_NAME',
	//				);
	//				break;
	//			case \CTaskListState::VIEW_ROLE_ORIGINATOR:
	//				$defaultColumns = array(
	//					'TITLE',
	//					'DEADLINE',
	//					'RESPONSIBLE_ID',
	////					'RESPONSIBLE_NAME'
	//				);
	//				break;
	//			case \CTaskListState::VIEW_ROLE_AUDITOR:
	//				$defaultColumns = array(
	//					'TITLE',
	//					'DEADLINE',
	//					'CREATED_BY',
	////					'ORIGINATOR_NAME',
	//					'RESPONSIBLE_ID',
	////					'RESPONSIBLE_NAME'
	//				);
	//				break;
	//			default:*/
	//				$defaultColumns = array(
	//					'TITLE',
	//					'DEADLINE',
	//					'CREATED_BY',
//					'ORIGINATOR_NAME',
	//					'RESPONSIBLE_ID',
//					'RESPONSIBLE_NAME'
	//				);
	//
	//		/*break;
	//}
	//*/
	//		return $defaultColumns;
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	protected static function getFilterRaw()
	//	{
	//		$fields = static::getAvailableFields();
	//		$filter = array();
	//
	//		if (in_array('CREATED_BY', $fields))
	//		{
	//			$filter['CREATED_BY'] = array(
	//				'id' => 'CREATED_BY',
	//				'name' => Loc::getMessage('TASKS_HELPER_FLT_CREATED_BY'),
	//				'params' => array('multiple' => 'Y'),
	//				'type' => 'custom_entity',
	//				'selector' => array(
	//					'TYPE' => 'user',
	//					'DATA' => array(
	//						'ID' => 'user',
	//						'FIELD_ID' => 'CREATED_BY'
	//					)
	//				)
	//			);
	//		}
	//
	//		if (in_array('RESPONSIBLE_ID', $fields))
	//		{
	//			$filter['RESPONSIBLE_ID'] = array(
	//				'id' => 'RESPONSIBLE_ID',
	//				'name' => Loc::getMessage('TASKS_HELPER_FLT_RESPONSIBLE_ID'),
	//				'params' => array('multiple' => 'Y'),
	//				'type' => 'custom_entity',
	//				'selector' => array(
	//					'TYPE' => 'user',
	//					'DATA' => array(
	//						'ID' => 'user',
	//						'FIELD_ID' => 'RESPONSIBLE_ID'
	//					)
	//				)
	//			);
	//		}
	//
	//		if (in_array('STATUS', $fields))
	//		{
	//			$filter['STATUS'] = array(
	//				'id' => 'STATUS',
	//				'name' => Loc::getMessage('TASKS_FILTER_STATUS'),
	//				'type' => 'list',
	//				'params' => array(
	//					'multiple' => 'Y'
	//				),
	//				'items' => array(
	//					//					\CTasks::METASTATE_VIRGIN_NEW => Loc::getMessage('TASKS_STATUS_1'),
	//					\CTasks::STATE_PENDING => Loc::getMessage('TASKS_STATUS_2'),
	//					\CTasks::STATE_IN_PROGRESS => Loc::getMessage('TASKS_STATUS_3'),
	//					\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_STATUS_4'),
	//					\CTasks::STATE_COMPLETED => Loc::getMessage('TASKS_STATUS_5')
	//				)
	//			);
	//		}
	//
	//		if (in_array('DEADLINE', $fields))
	//		{
	//			$filter['DEADLINE'] = array(
	//				'id' => 'DEADLINE',
	//				'name' => Loc::getMessage('TASKS_FILTER_DEADLINE'),
	//				'type' => 'date'
	//			);
	//		}
	//
	//		if (in_array('GROUP_ID', $fields))
	//		{
	//			$filter['GROUP_ID'] = array(
	//				'id' => 'GROUP_ID',
	//				'name' => Loc::getMessage('TASKS_HELPER_FLT_GROUP'),
	//				'params' => array('multiple' => 'Y'),
	//				'type' => 'custom_entity',
	//				'selector' => array(
	//					'TYPE' => 'group',
	//					'DATA' => array(
	//						'ID' => 'group',
	//						'FIELD_ID' => 'GROUP_ID'
	//					)
	//				)
	//			);
	//		}
	//
	//		if (in_array('PROBLEM', $fields))
	//		{
	//			$filter['PROBLEM'] = array(
	//				'id' => 'PROBLEM',
	//				'name' => Loc::getMessage('TASKS_FILTER_PROBLEM'),
	//				'type' => 'list',
	//				'items' => self::getAllowedTaskCategories()
	//			);
	//		}
	//
	//		if (in_array('PARAMS', $fields))
	//		{
	//			$filter['PARAMS'] = array(
	//				'id' => 'PARAMS',
	//				'name' => Loc::getMessage('TASKS_FILTER_PARAMS'),
	//				'type' => 'list',
	//				'params' => array(
	//					'multiple' => 'Y'
	//				),
	//				'items' => array(
	//					'MARKED'=>Loc::getMessage('TASKS_FILTER_PARAMS_MARKED'),
	//					'IN_REPORT'=>Loc::getMessage('TASKS_FILTER_PARAMS_IN_REPORT'),
	//					'OVERDUED'=>Loc::getMessage('TASKS_FILTER_PARAMS_OVERDUED'),
	////					'SUBORDINATE'=>Loc::getMessage('TASKS_FILTER_PARAMS_SUBORDINATE'),
	//					'FAVORITE'=>Loc::getMessage('TASKS_FILTER_PARAMS_FAVORITE'),
	//					'ANY_TASK'=>Loc::getMessage('TASKS_FILTER_PARAMS_ANY_TASK')
	//				)
	//			);
	//		}
	//
	//		if (in_array('ID', $fields))
	//		{
	//			$filter['ID'] = array(
	//				'id' => 'ID',
	//				'name' => Loc::getMessage('TASKS_FILTER_ID'),
	//				'type' => 'number'
	//			);
	//		}
	//		if (in_array('TITLE', $fields))
	//		{
	//			$filter['TITLE'] = array(
	//				'id' => 'TITLE',
	//				'name' => Loc::getMessage('TASKS_FILTER_TITLE'),
	//				'type' => 'string'
	//			);
	//		}
	//		if (in_array('PRIORITY', $fields))
	//		{
	//			$filter['PRIORITY'] = array(
	//				'id' => 'PRIORITY',
	//				'name' => Loc::getMessage('TASKS_PRIORITY'),
	//				'type' => 'list',
	//				'items' => array(
	//					1 => Loc::getMessage('TASKS_PRIORITY_1'),
	//					2 => Loc::getMessage('TASKS_PRIORITY_2'),
	//				)
	//			);
	//		}
	//		if (in_array('MARK', $fields))
	//		{
	//			$filter['MARK'] = array(
	//				'id' => 'MARK',
	//				'name' => Loc::getMessage('TASKS_FILTER_MARK'),
	//				'type' => 'list',
	//				'items' => array(
	//					'P' => Loc::getMessage('TASKS_MARK_P'),
	//					'N' => Loc::getMessage('TASKS_MARK_N')
	//				)
	//			);
	//		}
	//		if (in_array('ALLOW_TIME_TRACKING', $fields))
	//		{
	//			$filter['ALLOW_TIME_TRACKING'] = array(
	//				'id' => 'ALLOW_TIME_TRACKING',
	//				'name' => Loc::getMessage('TASKS_FILTER_ALLOW_TIME_TRACKING'),
	//				'type' => 'list',
	//				'items' => array(
	//					'Y' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_Y'),
	//					'N' => Loc::getMessage('TASKS_ALLOW_TIME_TRACKING_N'),
	//				)
	//			);
	//		}
	//		if (in_array('CREATED_DATE', $fields))
	//		{
	//			$filter['CREATED_DATE'] = array(
	//				'id' => 'CREATED_DATE',
	//				'name' => Loc::getMessage('TASKS_FILTER_CREATED_DATE'),
	//				'type' => 'date'
	//			);
	//		}
	//		if (in_array('CLOSED_DATE', $fields))
	//		{
	//			$filter['CLOSED_DATE'] = array(
	//				'id' => 'CLOSED_DATE',
	//				'name' => Loc::getMessage('TASKS_FILTER_CLOSED_DATE'),
	//				'type' => 'date'
	//			);
	//		}
	//		if (in_array('DATE_START', $fields))
	//		{
	//			$filter['DATE_START'] = array(
	//				'id' => 'DATE_START',
	//				'name' => Loc::getMessage('TASKS_FILTER_DATE_START'),
	//				'type' => 'date'
	//			);
	//		}
	//		if (in_array('START_DATE_PLAN', $fields))
	//		{
	//			$filter['START_DATE_PLAN'] = array(
	//				'id' => 'START_DATE_PLAN',
	//				'name' => Loc::getMessage('TASKS_FILTER_START_DATE_PLAN'),
	//				'type' => 'date'
	//			);
	//		}
	//		if (in_array('END_DATE_PLAN', $fields))
	//		{
	//			$filter['END_DATE_PLAN'] = array(
	//				'id' => 'END_DATE_PLAN',
	//				'name' => Loc::getMessage('TASKS_FILTER_END_DATE_PLAN'),
	//				'type' => 'date'
	//			);
	//		}
	//
	//		if (in_array('ACTIVE', $fields))
	//		{
	//			$filter['ACTIVE'] = array(
	//				'id' => 'ACTIVE',
	//				'name' => Loc::getMessage('TASKS_FILTER_ACTIVE'),
	//				'type' => 'date'
	//			);
	//		}
	//
	//		if (in_array('ACCOMPLICE', $fields))
	//		{
	//			$filter['ACCOMPLICE'] = array(
	//				'id' => 'ACCOMPLICE',
	//				'name' => Loc::getMessage('TASKS_HELPER_FLT_ACCOMPLICES'),
	//				'params' => array('multiple' => 'Y'),
	//				'type' => 'custom_entity',
	//				'selector' => array(
	//					'TYPE' => 'user',
	//					'DATA' => array(
	//						'ID' => 'user',
	//						'FIELD_ID' => 'ACCOMPLICE'
	//					)
	//				)
	//			);
	//		}
	//		if (in_array('AUDITOR', $fields))
	//		{
	//			$filter['AUDITOR'] = array(
	//				'id' => 'AUDITOR',
	//				'name' => Loc::getMessage('TASKS_HELPER_FLT_AUDITOR'),
	//				'params' => array('multiple' => 'Y'),
	//				'type' => 'custom_entity',
	//				'selector' => array(
	//					'TYPE' => 'user',
	//					'DATA' => array(
	//						'ID' => 'user',
	//						'FIELD_ID' => 'AUDITOR'
	//					)
	//				)
	//			);
	//		}
	//
	//		if (in_array('TAG', $fields))
	//		{
	//			$filter['TAG'] = array(
	//				'id' => 'TAG',
	//				'name' => Loc::getMessage('TASKS_FILTER_TAG'),
	//				'type' => 'string'
	//			);
	//		}
	//
	//		if (in_array('ROLEID', $fields))
	//		{
	//			$roles = \CTaskListState::getKnownRoles();
	//			foreach($roles as $roleId)
	//			{
	//				$roleCodeName = strtolower(\CTaskListState::resolveConstantCodename($roleId));
	//				$items[ $roleCodeName ] = Counter\Role::getRoleName($roleId);
	//			}
	//			$filter['ROLEID'] = array(
	//				'id' => 'ROLEID',
	//				'name' => Loc::getMessage('TASKS_FILTER_ROLEID'),
	//				'type' => 'list',
	//				'default'=>true,
	//				'items'=> $items
	//			);
	//		}
	//
	//		return $filter;
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	protected static function getAllowedTaskCategories()
	//	{
	//		$list = array();
	//
	//		$taskCategories = array(
	//			\CTaskListState::VIEW_TASK_CATEGORY_DEFERRED,
	//			\CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
	//			\CTaskListState::VIEW_TASK_CATEGORY_NEW,
	//			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
	//			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
	//			\CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL,
	//		);
	//
	//		foreach ($taskCategories as $categoryId)
	//		{
	////			if(static::getListStateInstance()->isCategoryExists((int)$categoryId))
	////			{
	//				$list[$categoryId] = \CTaskListState::getTaskCategoryName($categoryId);
	////			}
	//		}
	//
	//		return $list;
	//	}

	/**
	 * @return \CTaskListState|null
	 */
	public static function getListStateInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$instance = \CTaskListState::getInstance(static::getUserId());
		}
		return $instance;
	}

	/**
	 * @return int
	 */
	public static function getUserId()
	{
		if (!static::$userId)
		{
			static::$userId = Util\User::getId();
		}

		return static::$userId;
	}

	/**
	 * @param $userId
	 */
	public static function setUserId($userId)
	{
		static::$userId = $userId;
	}

	/**
	 * @return array
	 */
	//	protected static function getFilterData()
	//	{
	//		$filters = static::getFilters();
	//		$filterOptions = static::getFilterOptions();
	//
	//		return $filterOptions->getFilter($filters);
	//	}

	//	public static function initRoleFilter()
	//	{
	//		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
	//		if ($request->isAjaxRequest())
	//		{
	//			return false;
	//		}
	//
	//		$filterOptions = static::getFilterOptions();
	//		$filter = $filterOptions->getFilter();
	//		if (array_key_exists('ROLEID', $filter))
	//		{
	//			$roleId = $filter['ROLEID'];
	//		}
	//		else
	//		{
	//			$roleCode = $request['F_STATE'];
	//
	//			switch ($roleCode)
	//			{
	//				case 'sR400': // i do
	//					$roleId = 'view_role_responsible';
	//					break;
	//				case 'sR800': // acc
	//					$roleId = 'view_role_accomplice';
	//					break;
	//				case 'sRc00': // au
	//					$roleId = 'view_role_auditor';
	//					break;
	//				case 'sRg00': // orig
	//					$roleId = 'view_role_originator';
	//					break;
	//				default: // all
	//					$roleId = '';
	//					break;
	//			}
	//		}
	//		$currentPresetId = $filterOptions->getCurrentFilterId();
	//		$filterSettings = $filterOptions->getFilterSettings($currentPresetId);
	//
	//		if(!array_key_exists('ROLEID', $filterSettings['fields']) || !$filterSettings['fields']['ROLEID'])
	//		{
	//			if ($roleId)
	//			{
	//				$filterSettings['additional']['ROLEID'] = $roleId;
	//			}
	//			else
	//			{
	//				unset($filterSettings['additional']['ROLEID']);
	//			}
	//		}
	//
	//		$filterOptions->setFilterSettings($currentPresetId, $filterSettings, true, false);
	//		$filterOptions->save();
	//
	//		return $roleId;
	//	}

	/**
	 * @return Filter\Options
	 */
	//	public static function getFilterOptions()
	//	{
	////		if (is_null(static::$filterOptions) || !(static::$filterOptions instanceof Filter\Options))
	////		{
	////			static::$filterOptions = new Filter\Options(static::getFilterId(), static::getPresets());
	////		}
	//
	//		return new Filter\Options(static::getFilterId(), static::getPresets());
	//	}
	//
	//	/**
	//	 * @return array
	//	 */
	//	protected static function processStateFilter()
	//	{
	//		$listStateInstance = static::getListStateInstance();
	//		$listCtrlInstance = static::getListCtrlInstance();
	//		$filterCtrlInstance = static::getFilterCtrlInstance();
	//
	//		if ($listStateInstance->getSection() == \CTaskListState::VIEW_SECTION_ADVANCED_FILTER)
//		{
	//			$listCtrlInstance->useAdvancedFilterObject($filterCtrlInstance);
//		}
	//
	//		// getCommonFilter contains filtering by GROUP_ID and submode-filter conditions (ONLY_ROOT_TASKS and SAME_GROUP_PARENT)
	//		return array_merge($listCtrlInstance->getFilter(), $listCtrlInstance->getCommonFilter());
	//	}

	/**
	 * @return \CTaskListCtrl|null
	 */
	public static function getListCtrlInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$instance = \CTaskListCtrl::getInstance(static::getUserId());
		}

		return $instance;
	}

	/**
	 * @return \CTaskFilterCtrl|null
	 */
	private static function getFilterCtrlInstance()
	{
		static $instance = null;

		if (is_null($instance))
		{
			$instance = \CTaskFilterCtrl::getInstance(static::getUserId(), (static::getGroupId() > 0));
		}

		return $instance;
	}

	/**
	 * @return int
	 */
	public static function getGroupId()
	{
		return self::$groupId;
	}

	/**
	 * @param $groupId
	 */
	public static function setGroupId($groupId)
	{
		self::$groupId = $groupId;
	}

	/**
	 * @return \CTaskListState|null
	 */
	public static function listStateInit()
	{
		$listStateInstance = self::getListStateInstance();
		$listCtrlInstance = self::getFilterCtrlInstance();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();

		$listCtrlInstance->SwitchFilterPreset(\CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS);
		$listStateInstance->setSection(\CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
		$listStateInstance->setTaskCategory(\CTaskListState::VIEW_TASK_CATEGORY_ALL);

		$stateParam = (array)$request[ 'F_STATE' ];

		$needSaveState = false;
		if(!empty($stateParam))
		{
			foreach($stateParam as $state)
			{
				$symbol = substr($state, 0, 2);
				$value = \CTaskListState::decodeState(substr($state, 2));

				switch ($symbol)
				{
					case 'sV':    // set view
						$availableModes = $listStateInstance->getAllowedViewModes();
						if (in_array($value, $availableModes))
						{
							$listStateInstance->setViewMode($value);
						}
						else
						{
							$listStateInstance->setViewMode(\CTaskListState::VIEW_MODE_LIST);
						}

						$needSaveState=true;
						break;

//					case 'sC':    // set category
//						$listStateInstance->setTaskCategory($value);
//
//						$needSaveState=true;
//						break;

//					case 'eS':    // enable submode
//						$listStateInstance->switchOnSubmode($value);
//
//						$needSaveState=true;
//						break;
//
//					case 'dS':    // disable submode
//						$listStateInstance->switchOffSubmode($value);
//
//						$needSaveState=true;
//						break;
				}
			}
		}
//
//		if($needSaveState)
//		{
		$listStateInstance->saveState(); // to db
//		}

		return $listStateInstance;
	}
}