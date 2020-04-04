<?php
namespace Bitrix\Tasks\Ui;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class Filter
{
	/**
	 * UI Filter prefix.
	 */
	const FILTER_PREFIX = 'TASKS_V13';

    /**
     * Get instance of grid.
     * @return \Bitrix\Main\UI\Filter\Options|null
     */
	public static function getGrid()
	{
		static $grid = null;

		if ($grid === null)
		{
			$grid = new \Bitrix\Main\UI\Filter\Options(static::getGridId(), static::getPresets());
		}

		return $grid;
	}

	/**
	 * Get id of grid.
	 * @return string
	 */
	public static function getGridId()
	{
		return static::FILTER_PREFIX;
	}

	/**
	 * Get meta status - sum of several statuses.
	 * @param string $code Code of meta status.
	 * @return string|boolean
	 */
	protected function getMetaStatus($code)
	{
		switch ($code)
		{
			case 'IN_PROGRESS':
				return '@' . implode(',', array(
					\CTasks::STATE_NEW,
					\CTasks::STATE_PENDING,
					\CTasks::STATE_IN_PROGRESS
				));
			case 'DEFERRED':
				return '@' . implode(',', array(
					\CTasks::STATE_DEFERRED
				));
			case 'COMPLETED':
				return '@' . implode(',', array(
					\CTasks::STATE_SUPPOSEDLY_COMPLETED,
					\CTasks::STATE_COMPLETED,
					\CTasks::STATE_DECLINED
				));
		}

		return false;
	}

	/**
	 * Get counters with problem.
	 * @return array
	 */
	public static function getCounters()
	{
		return array(
			'NEW' => Loc::getMessage('TASKS_HELPER_FLT_PROBLEM_NEW'),
			'WO_DEADLINE' => Loc::getMessage('TASKS_HELPER_FLT_PROBLEM_WO_DEADLINE'),
			'EXPIRED' => Loc::getMessage('TASKS_HELPER_FLT_PROBLEM_EXPIRED'),
			'EXPIRED_CANDIDATES' => Loc::getMessage('TASKS_HELPER_FLT_PROBLEM_EXPIRED_CANDIDATES')
		);
	}

	/**
	 * Get filter for tasks.
	 * @return array
	 */
	public static function getFilter()
	{
		static $filter = null;

		if ($filter === null)
		{
			$filter = array();
			$filter['ID'] = array(
				'id' => 'ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_ID'),
				'default' => false,
				'type' => 'number'
			);
			$filter['TITLE'] = array(
				'id' => 'TITLE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_TITLE'),
				'default' => true,
				'type' => 'string'
			);
			$filter['PRIORITY'] = array(
				'id' => 'PRIORITY',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_PRIORITY'),
				'default' => false,
				'type' => 'list',
				'items' => array(
					\CTasks::PRIORITY_HIGH => Loc::getMessage('TASKS_HELPER_FLT_PRIORITY_HIGH')
				)
			);
			$filter['REAL_STATUS'] = array(
				'id' => 'REAL_STATUS',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_REAL_STATUS'),
				'default' => true,
				'type' => 'list',
				'items' => array(
					static::getMetaStatus('IN_PROGRESS') => Loc::getMessage('TASKS_HELPER_FLT_REAL_STATUS_IN_PROGRESS'),
					static::getMetaStatus('DEFERRED') => Loc::getMessage('TASKS_HELPER_FLT_REAL_STATUS_DEFERRED'),
					static::getMetaStatus('COMPLETED') => Loc::getMessage('TASKS_HELPER_FLT_REAL_STATUS_COMPLETED')
				)
			);
			$filter['PROBLEM'] = array(
				'id' => 'PROBLEM',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_PROBLEM'),
				'default' => false,
				'type' => 'list',
				'items' => static::getCounters()
			);
			$filter['DEADLINE'] = array(
				'id' => 'DEADLINE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_DEADLINE'),
				'default' => true,
				'type' => 'date'
			);
			$filter['CREATED_DATE'] = array(
				'id' => 'CREATED_DATE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_CREATED_DATE'),
				'default' => false,
				'type' => 'date'
			);
			$filter['CLOSED_DATE'] = array(
				'id' => 'CLOSED_DATE',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_CLOSED_DATE'),
				'default' => false,
				'type' => 'date'
			);
			$filter['RESPONSIBLE_ID'] = array(
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_RESPONSIBLE_ID'),
				'default' => false,
				'type' => 'custom_entity',
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array(
						'ID' => 'user',
						'FIELD_ID' => 'RESPONSIBLE_ID'
					)
				)
			);
			$filter['CREATED_BY'] = array(
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_CREATED_BY'),
				'default' => false,
				'type' => 'custom_entity',
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array(
						'ID' => 'user',
						'FIELD_ID' => 'CREATED_BY'
					)
				)
			);
			$filter['GROUP_ID'] = array(
				'id' => 'GROUP_ID',
				'name' => Loc::getMessage('TASKS_HELPER_FLT_GROUP'),
				'default' => true,
				'type' => 'custom_entity',
				'selector' => array(
					'TYPE' => 'group',
					'DATA' => array(
						'ID' => 'group',
						'FIELD_ID' => 'GROUP_ID'
					)
				)
			);
		}

		return $filter;
	}

	/**
	 * Get filter presets.
	 * @return array
	 */
	public static function getPresets()
	{
		static $presets = null;

		if ($presets === null)
		{
			/*$uid = \Bitrix\Tasks\Util\User::getId();
			if ($uid)
			{
				if (($uname = \CUser::getById($uid)->fetch()))
				{
					$uname = \CUser::FormatName(\CSite::GetNameFormat(false), $uname);
				}
			}*/

			$presets = array();
			// by statuses
			foreach (array('IN_PROGRESS', 'DEFERRED', 'COMPLETED') as $code)
			{
				$presets['filter_tasks_' . strtolower($code)] = array(
					'name' => Loc::getMessage('TASKS_HELPER_FLT_PRESET_' . $code),
					'default' => $code == 'IN_PROGRESS',
					'fields' => array(
						'REAL_STATUS' => static::getMetaStatus($code),
						'RESPONSIBLE_ID' => '',
						'RESPONSIBLE_ID_name' => '',
						'CREATED_BY' => '',
						'TITLE' => '',
						'DEADLINE' => ''
					)
				);
			}
		}

		return $presets;
	}

	/**
	 * Fill filter array with new items from main.filter.
	 * @param array $filter Current filter.
	 * @return array
	 */
	public static function fillFilter(array $filter = array())
	{
		$grid = static::getGrid();
		$gridFilter = static::getFilter();
		$search = $grid->GetFilter($gridFilter);
		if (!isset($search['FILTER_APPLIED']))
		{
			$search = array();
		}
		if (!empty($search))
		{
			foreach ($gridFilter as $key => $item)
			{
				//fill filter by type
				if ($item['type'] == 'date')
				{
					if (isset($search[$key . '_from']) && $search[$key . '_from']!='')
					{
						$filter['>='.$key] = $search[$key . '_from'] . ' 00:00:00';
					}
					if (isset($search[$key . '_to']) && $search[$key . '_to']!='')
					{
						$filter['<='.$key] = $search[$key . '_to'] . ' 23:59:00';
					}
				}
				elseif ($item['type'] == 'number')
				{
					if (isset($search[$key . '_from']) && $search[$key . '_from'] != '')
					{
						$filter['>'.$key] = $search[$key . '_from'];
					}
					if (isset($search[$key . '_to']) && $search[$key . '_to'] != '')
					{
						$filter['<'.$key] = $search[$key . '_to'];
					}
					if (
						isset($filter['>'.$key]) && isset($filter['<'.$key]) &&
						$filter['>'.$key] == $filter['<'.$key]
					)
					{
						$filter[$key] = $filter['<'.$key];
						unset($filter['>'.$key], $filter['<'.$key]);
					}
				}
				elseif (isset($search[$key]))
				{
					if (substr($search[$key], 0, 1) == '@')
					{
						$search[$key] = explode(',', substr($search[$key], 1));
					}

					if (isset($gridFilter[$key]['flt_key']))
					{
						$filter[$gridFilter[$key]['flt_key']] = $search[$key];
					}
					else
					{
						$filter[$key] = $search[$key];
					}
				}
			}
			//search index
			if (isset($search['FIND']) && trim($search['FIND']) != '')
			{
				$filter['*%SEARCH_INDEX'] = trim($search['FIND']);
			}
		}

		// counters
		if (isset($filter['PROBLEM']))
		{
			$deadline = new DateTime;

			$filter['REAL_STATUS'] = array(
				\CTasks::STATE_NEW,
				\CTasks::STATE_PENDING,
				\CTasks::STATE_IN_PROGRESS
			);

			switch ($filter['PROBLEM'])
			{
				case 'NEW':
					$filter['REAL_STATUS'] = \CTasks::STATE_NEW;
					$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
					break;
				case 'WO_DEADLINE':
					$filter['=DEADLINE'] = '';
					$filter['!REFERENCE:RESPONSIBLE_ID'] = 'CREATED_BY';
					break;
				case 'EXPIRED':
					$filter['<DEADLINE'] = $deadline;
					break;
				case 'EXPIRED_CANDIDATES':
					$deadlineCandidate = new DateTime;
					$filter['>DEADLINE'] = $deadline;
					$filter['<DEADLINE'] = $deadlineCandidate->add('+' . (24 - date('G')) . ' hours');
					break;
			}
		}

		return $filter;
	}
}