<?php

namespace Bitrix\Disk\Volume\Storage;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;


/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class TrashCan extends Volume\Storage\Storage
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE]): self
	{
		$this->addFilter('!DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure($collectData);

		return $this;
	}

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @throws ArgumentException
	 * @return static
	 */
	public function recalculatePercent($totalSizeIndicator = '\\Bitrix\\Disk\\Volume\\Module\\Disk', $excludeSizeIndicator = null): self
	{
		if (is_string($totalSizeIndicator) && !empty($totalSizeIndicator) && class_exists($totalSizeIndicator))
		{
			/** @var Volume\Module\Disk $totalSizeIndicator */
			$totalSizeIndicator = new $totalSizeIndicator();
		}
		if (!($totalSizeIndicator instanceof Volume\IVolumeIndicator))
		{
			throw new ArgumentException('Wrong parameter totalSizeIndicator');
		}
		$totalSizeIndicator->setOwner($this->getOwner());
		$totalSizeIndicator->loadTotals();
		$total = $totalSizeIndicator->getTotalSize() + $totalSizeIndicator->getPreviewSize();

		if ($total > 0)
		{
			$filter = $this->getFilter(
				[
					'=INDICATOR_TYPE' => static::className(),
					'=OWNER_ID' => $this->getOwner(),
					'>FILE_COUNT' => 0,
				],
				VolumeTable::getEntity()
			);
			$where = Query::buildFilterSql(VolumeTable::getEntity(), $filter);

			$tableName = VolumeTable::getTableName();

			$sql = 'UPDATE '.$tableName.' SET PERCENT = ROUND((FILE_SIZE + PREVIEW_SIZE) * 100 / '.$total.', 4) WHERE '.$where;

			$connection = Application::getConnection();
			if ($connection->lock(self::$lockName, self::$lockTimeout))
			{
				$connection->queryExecute($sql);
				$connection->unlock(self::$lockName);
			}
		}

		return $this;
	}

	/**
	 * @param Volume\Fragment $fragment Storage entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): ?string
	{
		$storage = $fragment->getStorage();
		if (!$storage instanceof Disk\Storage)
		{
			throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Storage::className());
		}

		$url = $storage->getProxyType()->getBaseUrlTashcanList();

		$testUrl = trim($url, '/');
		if (
			$testUrl == '' ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_FOLDER_LIST ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_TRASHCAN_LIST ||
			$testUrl == Disk\ProxyType\Base::SUFFIX_DISK
		)
		{
			return null;
		}

		return $url;
	}
}

