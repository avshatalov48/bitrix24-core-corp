<?php

namespace Bitrix\Disk\Internals\Grid;

use Bitrix\Main\Localization\Loc;

/**
 * Class TrashCanOptions
 * @package Bitrix\Disk\Internals\Grid
 * @internal
 */
final class TrashCanOptions extends FolderListOptions
{
	/**
	 * Returns grid id.
	 * @return string
	 */
	public function getGridId()
	{
		return 'trashcan_' . $this->storage->getId();
	}

	/**
	 * Returns default sorting.
	 *
	 * @return array
	 */
	public function getDefaultSorting()
	{
		return array('DELETE_TIME' => 'DESC');
	}

	/**
	 * Returns default columns which will be shown by default view.
	 * @return array
	 */
	public function getDefaultColumns()
	{
		return array(
			'NAME',
			'DELETE_TIME',
			'DELETE_USER',
			'FORMATTED_SIZE',
		);
	}

	public function getPossibleColumnForSorting()
	{
		return array_merge(
			array(
				'DELETE_TIME' => array(
					'ALIAS' => 'DELETE_TIME',
					'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_DELETE_TIME')
				),
			),
			parent::getPossibleColumnForSorting()
		);
	}

	/**
	 * Tells if sort mode is mix.
	 * @return bool
	 */
	protected function isMixSortMode()
	{
		return true;
	}
}