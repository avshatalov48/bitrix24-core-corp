<?php

namespace Bitrix\Disk\Internals\Grid;

use \Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;

/**
 * Class FolderListOptions
 * @package Bitrix\Disk\Internals\Grid
 * @internal
 */
class FolderListOptions
{
	const COUNT_ON_PAGE = 50;

	const SORT_MODE_MIX      = 'mix';
	const SORT_MODE_ORDINARY = 'ord';

	const VIEW_MODE_GRID    = 'grid';
	const VIEW_MODE_TILE    = 'tile';

	const VIEW_TILE_SIZE_M  = 'm';
	const VIEW_TILE_SIZE_XL = 'xl';

	/** @var \Bitrix\Disk\Storage */
	protected $storage;
	/** @var \CGridOptions  */
	private $gridOptions;

	/**
	 * FolderListOptions constructor.
	 * @param Disk\Storage $storage
	 */
	public function __construct(Disk\Storage $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * Returns grid id.
	 * @return string
	 */
	public function getGridId()
	{
		return 'folder_list_' . $this->storage->getId();
	}

	public static function extractStorageId($gridId)
	{
		return explode('folder_list_', $gridId)[1] ?? null;
	}

	/**
	 * Returns columns which may use to sorting.
	 * @return array
	 */
	public function getPossibleColumnForSorting()
	{
		return array(
			'UPDATE_TIME' => array(
				'ALIAS' => 'UPDATE_TIME',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_UPDATE_TIME_2')
			),
			'ID' => array(
				'ALIAS' => 'ID',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_ID')
			),
			'NAME' => array(
				'ALIAS' => 'NAME',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_NAME')
			),
			'FORMATTED_SIZE' => array(
				'ALIAS' => 'SIZE',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_FORMATTED_SIZE')
			),
		);
	}

	/**
	 * Returns default sorting.
	 *
	 * @return array
	 */
	public function getDefaultSorting()
	{
		return array('UPDATE_TIME' => 'DESC');
	}

	/**
	 * Returns columns for sorting for current user.
	 * @return array
	 */
	public function getSortingColumns()
	{
		$gridSort = $this->getGridOptions()->getSorting(array(
			'sort' => $this->getDefaultSorting(),
			'vars' => array('by' => 'by', 'order' => 'order')
		));
		$sorting = $gridSort['sort'];
		$possibleColumnForSorting = $this->getPossibleColumnForSorting();

		$byColumn = key($sorting);
		if(!isset($possibleColumnForSorting[$byColumn]) || (mb_strtolower($sorting[$byColumn]) !== 'desc' && mb_strtolower($sorting[$byColumn]) !== 'asc'))
		{
			$sorting = array();
		}

		$order = $sorting;
		$byColumn = key($order);
		$sortingColumns = array();
		if(!$this->isMixSortMode())
		{
			$sortingColumns['TYPE'] = array(SORT_NUMERIC, SORT_ASC);
		}

		if(isset($possibleColumnForSorting[$byColumn]['ALIAS']))
		{
			$sortingColumns[$possibleColumnForSorting[$byColumn]['ALIAS']] = mb_strtolower($order[$byColumn]) === 'asc' ? SORT_ASC : SORT_DESC;
		}

		if($byColumn !== 'NAME')
		{
			$sortingColumns[$possibleColumnForSorting['NAME']['ALIAS']] = SORT_ASC;
		}

		return $sortingColumns;
	}

	/**
	 * Returns visible columns.
	 * @return array
	 */
	public function getVisibleColumns()
	{
		if($this->getViewMode() === self::VIEW_MODE_GRID)
		{
			return $this->gridOptions->getVisibleColumns()?: $this->getDefaultColumns();
		}

		return $this->getDefaultColumns();
	}

	/**
	 * Returns default columns which will be shown by default view.
	 * @return array
	 */
	public function getDefaultColumns()
	{
		return array(
			'NAME',
			'UPDATE_TIME',
			'FORMATTED_SIZE',
		);
	}

	/**
	 * Returns data to order in select orm-parameters.
	 * @return array
	 */
	public function getOrderForOrm()
	{
		$order = array();
		foreach($this->getSortingColumns() as $columnName => $columnData)
		{
			if(is_array($columnData))
			{
				$order[$columnName] = in_array(SORT_DESC, $columnData, true) ? 'DESC' : 'ASC';
			}
			else
			{
				$order[$columnName] = SORT_DESC === $columnData ? 'DESC' : 'ASC';
			}
		}
		unset($columnName, $columnData);

		return $order;
	}

	/**
	 * Returns grid sorting options (@see \CGridOptions).
	 * @return array
	 */
	public function getGridOptionsSorting()
	{
		$gridSort = $this->getGridOptions()->getSorting(array(
			'sort' => $this->getDefaultSorting(),
			'vars' => array('by' => 'by', 'order' => 'order')
		));

		return array($gridSort['sort'], $gridSort['vars']);
	}

	/**
	 * Returns grid mode for view (grid or tile).
	 * @return string
	 */
	public function getViewMode()
	{
		return $this->getGridSpecificOptions()['viewMode'];
	}

	public function getViewSize()
	{
		return $this->getGridSpecificOptions()['viewSize']?: self::VIEW_TILE_SIZE_M;
	}

	/**
	 * Returns grid mode for view (grid or tile).
	 * @return string
	 */
	public function getSortMode()
	{
		return $this->getGridSpecificOptions()['sortMode'];
	}

	/**
	 * Tells if sort mode is mix.
	 * @return bool
	 */
	protected function isMixSortMode()
	{
		return $this->getSortMode() === self::SORT_MODE_MIX;
	}

	private function getGridSpecificOptions()
	{
		$options = \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, $this->getGridId());
		if ($options === false)
		{
			$options = \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, 'grid', [
				'sortMode' => self::SORT_MODE_ORDINARY,
				'viewMode' => self::VIEW_MODE_TILE,
				'viewSize' => self::VIEW_TILE_SIZE_M,
			]);
		}

		return $options;
	}

	/**
	 * Stores view mode (grid or tile) for folder list.
	 * @param string $mode
	 * @return void
	 */
	public function storeViewMode($mode)
	{
		$mode = mb_strtolower($mode);
		if($mode !== self::VIEW_MODE_GRID && $mode !== self::VIEW_MODE_TILE)
		{
			$mode = self::VIEW_MODE_GRID;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, $this->getGridId(), array(
			'sortMode' => $this->getSortMode(),
			'viewMode' => $mode,
			'viewSize' => $this->getViewSize(),
		));
	}

	/**
	 * Stores sort mode for folder list.
	 * @param string $mode
	 * @return void
	 */
	public function storeSortMode($mode)
	{
		$mode = mb_strtolower($mode);
		if($mode !== self::SORT_MODE_ORDINARY && $mode !== self::SORT_MODE_MIX)
		{
			$mode = self::SORT_MODE_ORDINARY;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, $this->getGridId(), array(
			'sortMode' => $mode,
			'viewMode' => $this->getViewMode(),
			'viewSize' => $this->getViewSize(),
		));
	}

	public function storeViewSize($size)
	{
		$size = mb_strtolower($size);
		if($size !== self::VIEW_TILE_SIZE_M && $size !== self::VIEW_TILE_SIZE_XL)
		{
			$size = self::VIEW_TILE_SIZE_M;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, $this->getGridId(), array(
			'sortMode' => $this->getSortMode(),
			'viewMode' => $this->getViewMode(),
			'viewSize' => $size,
		));
	}

	/**
	 * Returns page size.
	 * @return int
	 */
	public function getPageSize()
	{
		$navParams = $this->getGridOptions()->getNavParams(array('nPageSize' => static::COUNT_ON_PAGE));
		return (int)$navParams['nPageSize'];
	}

	public function getNavigation()
	{
		$nav = new PageNavigation('nav-' . $this->getGridId());
		$nav
			->allowAllRecords(false)
			->setPageSize($this->getPageSize())
		;

		return $nav;
	}

	private function getGridOptions()
	{
		if($this->gridOptions === null)
		{
			$this->gridOptions = new \CGridOptions($this->getGridId());
		}
		return $this->gridOptions;
	}
}