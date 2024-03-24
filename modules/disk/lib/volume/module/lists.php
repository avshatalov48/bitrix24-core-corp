<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Lists extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'lists';

	/**
	 * Returns true if module installed and available to measure.
	 * @return boolean
	 */
	public function isMeasureAvailable(): bool
	{
		$enable =
			//parent::isMeasureAvailable() &&
			\Bitrix\Main\ModuleManager::isModuleInstalled(self::getModuleId()) &&
			\Bitrix\Main\ModuleManager::isModuleInstalled('iblock') &&
			count($this->getIblockList()) > 0
		;
		return $enable;
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = []): self
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$indicatorType = $sqlHelper->forSql(static::className());
		$indicatorIblockType = $sqlHelper->forSql(Volume\Module\Iblock::className());
		$ownerId = (string)$this->getOwner();

		$includeIblockIds = [];

		$webdavIblockList = $this->getIblockList();
		if (count($webdavIblockList) > 0)
		{
			foreach ($webdavIblockList as $iblock)
			{
				$includeIblockIds[] = $iblock['ID'];
			}
		}

		$agr = new Volume\Module\Iblock();
		$agr
			->setOwner($this->getOwner())
			->addFilter('@IBLOCK_ID', $includeIblockIds)
			->purify()
			->measure();


		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $sqlHelper->getCurrentDateTimeFunction(). " as CREATE_TIME,
				COALESCE(SUM(FILE_SIZE), 0),
				COALESCE(SUM(FILE_COUNT), 0)
			FROM 
				b_disk_volume
			WHERE 
				INDICATOR_TYPE = '{$indicatorIblockType}'
				AND IBLOCK_ID IN(". implode(',', $includeIblockIds) .")
		";

		$columnList = Volume\QueryHelper::prepareInsert(
			[
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
				'FILE_SIZE',
				'FILE_COUNT',
			],
			$this->getSelect()
		);

		$tableName = $sqlHelper->quote(VolumeTable::getTableName());

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}


	/**
	 * Returns iblock list corresponding to module.
	 * @return array
	 */
	public function getIblockList(): array
	{
		static $iblockList;
		if (!$iblockList)
		{
			$iblockList = [];
			if (
				\Bitrix\Main\Loader::includeModule('iblock') &&
				\Bitrix\Main\Loader::includeModule(self::getModuleId())
			)
			{
				$result = \Bitrix\Iblock\IblockTable::getList([
					'select' => ['ID'],
					'filter' => [
						'=IBLOCK_TYPE_ID' => 'lists',
					]
				]);
				foreach ($result as $iblock)
				{
					$iblockList[$iblock['ID']] = $iblock;
				}
			}
		}

		return $iblockList;
	}
}

