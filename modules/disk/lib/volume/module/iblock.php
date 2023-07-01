<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Iblock\PropertyTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Iblock extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'iblock';

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
		$ownerId = (string)$this->getOwner();

		$excludeIblockIds = [];

		// Exclude webdav iblocks
		$webdav = new Volume\Module\Webdav();
		$webdavIblockList = $webdav->getIblockList();
		if (count($webdavIblockList) > 0)
		{
			foreach ($webdavIblockList as $iblock)
			{
				$excludeIblockIds[] = $iblock['ID'];
			}
		}

		$filter = $this->getFilter($excludeIblockIds ? ['!@IBLOCK_ID' => $excludeIblockIds] : []);

		$includeIblockIds = [];
		if (isset($filter['@IBLOCK_ID']))
		{
			$includeIblockIds = $filter['@IBLOCK_ID'];
		}
		elseif (isset($filter['IBLOCK_ID']))
		{
			$includeIblockIds[] = $filter['IBLOCK_ID'];
		}
		elseif (isset($filter['=IBLOCK_ID']))
		{
			$includeIblockIds[] = $filter['@IBLOCK_ID'];
		}

		$groupSelectSql = '';
		$groupBySql = '';
		$groupColumns = [];
		if (count($includeIblockIds) > 0)
		{
			$groupColumns[] = 'IBLOCK_ID';
			$groupBySql = 'GROUP BY src.IBLOCK_ID';
			$groupSelectSql = ', src.IBLOCK_ID';
		}

		// iblock filter
		$filterIblockSql = '1 = 1';
		$filterSectionSql = '1 = 1';
		$filterElementSql = '1 = 1';
		if (!empty($excludeIblockIds))
		{
			$filterIblockSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter(['!@IBLOCK_ID' => $excludeIblockIds]),
				['IBLOCK_ID' => 'iblock.ID']
			);

			// section filter
			$filterSectionSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter(['!@IBLOCK_ID' => $excludeIblockIds]),
				['IBLOCK_ID' => 'section.IBLOCK_ID']
			);

			// element filter
			$filterElementSql = Volume\QueryHelper::prepareWhere(
				$this->getFilter(['!@IBLOCK_ID' => $excludeIblockIds]),
				['IBLOCK_ID' => 'element.IBLOCK_ID']
			);
		}

		// Scan User fields specific to module
		$entityUserFieldSource = $this->prepareUserFieldSourceSql([
			'table' => 'b_iblock_element',
			'relation' => 'ID',
			'select' => ['IBLOCK_ID' => 'ID'],
		]);
		if ($entityUserFieldSource != '')
		{
			$entityUserFieldSource = " UNION {$entityUserFieldSource} ";
		}

		// language=SQL
		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				SUM(src.FILE_SIZE) as FILE_SIZE,
				SUM(src.FILE_COUNT) as FILE_COUNT
				{$groupSelectSql}
			FROM 
			(
				/*-- iblock --*/
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT,
						SUM(f.FILE_SIZE) as FILE_SIZE,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						iblock.ID as IBLOCK_ID
					FROM 
						b_file f
						INNER JOIN b_iblock iblock on iblock.PICTURE = f.ID
					WHERE 
						{$filterIblockSql}
					GROUP BY
						iblock.ID
					ORDER BY NULL
				)
				/*-- section --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						section.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_section section
							on section.PICTURE = f.ID
					WHERE
						{$filterSectionSql}
					GROUP BY
						section.IBLOCK_ID
					ORDER BY NULL
				)
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT,
						SUM(f.FILE_SIZE) as FILE_SIZE,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						section.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_section section
							on section.DETAIL_PICTURE = f.ID
					WHERE
						{$filterSectionSql}
					GROUP BY
						section.IBLOCK_ID
					ORDER BY NULL
				)
				/*-- element --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT,
						SUM(f.FILE_SIZE) as FILE_SIZE, 
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						element.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_element element 
							on element.PREVIEW_PICTURE = f.ID
					WHERE
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
					ORDER BY NULL
				)
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT,
						SUM(f.FILE_SIZE) as FILE_SIZE,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						element.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_element element
							on element.DETAIL_PICTURE = f.ID
					WHERE
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
					ORDER BY NULL
				)
				/*-- property --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT,
						element.IBLOCK_ID
					FROM 
						b_iblock_element element
						INNER JOIN b_iblock_element_property property
							on element.ID = property.IBLOCK_ELEMENT_ID
							and property.IBLOCK_PROPERTY_ID in(
								SELECT id 
								FROM b_iblock_property 
								WHERE PROPERTY_TYPE = '". PropertyTable::TYPE_FILE ."' or USER_TYPE = 'FileMan'
							)
						INNER JOIN b_file f
							on f.ID = property.VALUE_NUM  
							and property.VALUE_NUM > 0
					WHERE
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
					ORDER BY NULL
				)
				{$entityUserFieldSource}
			) src
			{$groupBySql}
			ORDER BY NULL
		";

		$columnList = Volume\QueryHelper::prepareInsert(
			array_merge(
				[
					'INDICATOR_TYPE',
					'OWNER_ID',
					'CREATE_TIME',
					'FILE_SIZE',
					'FILE_COUNT',
				],
				$groupColumns
			),
			$this->getSelect()
		);

		$tableName = VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList(): array
	{
		static $entityList;
		if (!isset($entityList))
		{
			$entityList = [];

			$excludeIblockIds = [];

			// Exclude webdav iblocks
			$webdav = new Volume\Module\Webdav();
			$webdavIblockList = $webdav->getIblockList();
			if (count($webdavIblockList) > 0)
			{
				foreach ($webdavIblockList as $iblock)
				{
					$excludeIblockIds[] = $iblock['ID'];
				}
			}

			$filter = $this->getFilter(['!@IBLOCK_ID' => $excludeIblockIds]);

			$excludeIblockIds = [];
			if (isset($filter['!@IBLOCK_ID']))
			{
				$excludeIblockIds = $filter['!@IBLOCK_ID'];
			}
			elseif (isset($filter['!IBLOCK_ID']))
			{
				$excludeIblockIds[] = $filter['IBLOCK_ID'];
			}

			$includeIblockIds = [];
			if (isset($filter['@IBLOCK_ID']))
			{
				$includeIblockIds = $filter['@IBLOCK_ID'];
			}
			elseif (isset($filter['IBLOCK_ID']))
			{
				$includeIblockIds[] = $filter['IBLOCK_ID'];
			}
			elseif (isset($filter['=IBLOCK_ID']))
			{
				$includeIblockIds[] = $filter['@IBLOCK_ID'];
			}


			// Exclude iblocks
			$excludeEntityIds = [];
			foreach ($excludeIblockIds as $iblockId)
			{
				$excludeEntityIds[] = 'IBLOCK_'.$iblockId;
				$excludeEntityIds[] = 'IBLOCK_'.$iblockId.'_SECTION';
			}

			// include iblocks
			$includeEntityIds = [];
			foreach ($includeIblockIds as $iblockId)
			{
				$includeEntityIds[] = 'IBLOCK_'.$iblockId;
				$includeEntityIds[] = 'IBLOCK_'.$iblockId.'_SECTION';
			}

			$filter = [
				'ENTITY_ID'     => 'IBLOCK_%',
				'=USER_TYPE_ID' => [
					\CUserTypeFile::USER_TYPE_ID,
					Disk\Uf\FileUserType::USER_TYPE_ID,
					Disk\Uf\VersionUserType::USER_TYPE_ID,
				],
			];
			$userFieldList = \Bitrix\Main\UserFieldTable::getList(['filter' => $filter]);
			if ($userFieldList->getSelectedRowsCount() > 0)
			{
				foreach ($userFieldList as $userField)
				{
					$entityName = $userField['ENTITY_ID'];
					if (isset($entityList[$entityName]))
					{
						continue;
					}

					// exclude
					if (count($excludeIblockIds) > 0 && in_array($entityName, $excludeIblockIds)) continue;
					// include
					if (count($includeEntityIds) > 0 && !in_array($entityName, $includeEntityIds)) continue;

					/** @var \Bitrix\Main\Entity\Base $ent */
					$ent = \Bitrix\Main\Entity\Base::compileEntity($entityName, [], [
						'namespace' => __NAMESPACE__,
						'uf_id'     => $entityName,
					]);

					$entityList[$entityName] = $ent->getDataClass();
				}
			}
		}

		return $entityList;
	}
}
