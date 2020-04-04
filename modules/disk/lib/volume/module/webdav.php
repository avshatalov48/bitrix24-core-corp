<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Webdav extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'webdav';

	/**
	 * Returns true if module installed and available to measure.
	 * @return boolean
	 */
	public function isMeasureAvailable()
	{
		$enable =
			//parent::isMeasureAvailable() &&
			\Bitrix\Main\ModuleManager::isModuleInstalled('iblock') &&
			count($this->getIblockList()) > 0
		;
		return $enable;
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 */
	public function measure($collectData = array())
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$indicatorType = $connection->getSqlHelper()->forSql(static::className());
		$indicatorIblockType = $connection->getSqlHelper()->forSql(Volume\Module\Iblock::className());
		$ownerId = (string)$this->getOwner();

		$includeIblockIds = array();

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
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				SUM(FILE_SIZE),
				SUM(FILE_COUNT)
			FROM 
				b_disk_volume
			WHERE 
				INDICATOR_TYPE = '{$indicatorIblockType}'
				AND IBLOCK_ID IN(". implode(',', $includeIblockIds) .")
		";

		$columnList = Volume\QueryHelper::prepareInsert(
			array(
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
				'FILE_SIZE',
				'FILE_COUNT',
			),
			$this->getSelect()
		);

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}


	/**
	 * Returns iblock list corresponding to module.
	 * @return array
	 */
	public function getIblockList()
	{
		static $iblockList;
		if(!$iblockList)
		{
			$iblockList = array();
			if (\Bitrix\Main\Loader::includeModule(self::getModuleId()))
			{
				//\Bitrix\Disk\Configuration::isSuccessfullyConverted()

				$result = \Bitrix\Iblock\IblockTable::getList(array(
					'select' => array('ID', 'IBLOCK_TYPE_ID', 'NAME', 'CODE'),
					'filter' => array(
						'=IBLOCK_TYPE_ID' => 'library',
						'CODE' => '%_files%',
					)
				));
				foreach ($result as $iblock)
				{
					$iblockList[$iblock['ID']] = $iblock;
				}
			}
		}

		return $iblockList;
	}
}

