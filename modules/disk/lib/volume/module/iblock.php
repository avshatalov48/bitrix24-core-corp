<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;
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
		$sqlHelper = $connection->getSqlHelper();
		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		$excludeIblockIds = array();

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

		$filter = $this->getFilter(array('!@IBLOCK_ID' => $excludeIblockIds));

		$includeIblockIds = array();
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
		$groupColumns = array();
		if (count($includeIblockIds) > 0)
		{
			$groupColumns[] = 'IBLOCK_ID';
			$groupBySql = 'GROUP BY src.IBLOCK_ID';
			$groupSelectSql = ', src.IBLOCK_ID';
		}

		// iblock filter
		$filterIblockSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(array('!@IBLOCK_ID' => $excludeIblockIds)),
			array('IBLOCK_ID' => 'iblock.ID')
		);

		// section filter
		$filterSectionSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(array('!@IBLOCK_ID' => $excludeIblockIds)),
			array('IBLOCK_ID' => 'section.IBLOCK_ID')
		);

		// element filter
		$filterElementSql = Volume\QueryHelper::prepareWhere(
			$this->getFilter(array('!@IBLOCK_ID' => $excludeIblockIds)),
			array('IBLOCK_ID' => 'element.IBLOCK_ID')
		);

		// Scan User fields specific to module
		$entityUserFieldSource = $this->prepareUserFieldSourceSql(array(
			'table' => 'b_iblock_element',
			'relation' => 'ID',
			'select' => array('IBLOCK_ID' => 'ID'),
		));
		if ($entityUserFieldSource != '')
		{
			$entityUserFieldSource = " UNION {$entityUserFieldSource} ";
		}


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
						iblock.ID as IBLOCK_ID
					FROM 
						b_file f
						INNER JOIN b_iblock iblock on iblock.PICTURE = f.ID
					WHERE 
						1 = 1
						{$filterIblockSql}
					GROUP BY
						iblock.ID
				)
				/*-- section --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						section.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_section section
							on section.PICTURE = f.ID
					WHERE
						1 = 1
						{$filterSectionSql}
					GROUP BY
						section.IBLOCK_ID
				)
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						section.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_section section
							on section.DETAIL_PICTURE = f.ID
					WHERE
						1 = 1
						{$filterSectionSql}
					GROUP BY
						section.IBLOCK_ID
				)
				/*-- element --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						element.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_element element 
							on element.PREVIEW_PICTURE = f.ID
					WHERE
						1 = 1
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
				)
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						element.IBLOCK_ID
					FROM
						b_file f
						INNER JOIN b_iblock_element element
							on element.DETAIL_PICTURE = f.ID
					WHERE
						1 = 1
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
				)
				/*-- property --*/
				UNION
				(
					SELECT
						COUNT(f.ID) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
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
						1 = 1
						{$filterElementSql}
					GROUP BY
						element.IBLOCK_ID
				)
				{$entityUserFieldSource}
			) src
			{$groupBySql}
		";

		$columnList = Volume\QueryHelper::prepareInsert(
			array_merge(array(
				'INDICATOR_TYPE',
				'OWNER_ID',
				'CREATE_TIME',
				'FILE_SIZE',
				'FILE_COUNT',
			),$groupColumns),
			$this->getSelect()
		);

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList()
	{
		static $entityList;
		if (!isset($entityList))
		{
			$entityList = array();

			$excludeIblockIds = array();

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

			$filter = $this->getFilter(array('!@IBLOCK_ID' => $excludeIblockIds));

			$excludeIblockIds = array();
			if (isset($filter['!@IBLOCK_ID']))
			{
				$excludeIblockIds = $filter['!@IBLOCK_ID'];
			}
			elseif (isset($filter['!IBLOCK_ID']))
			{
				$excludeIblockIds[] = $filter['IBLOCK_ID'];
			}

			$includeIblockIds = array();
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
			$excludeEntityIds = array();
			foreach ($excludeIblockIds as $iblockId)
			{
				$excludeEntityIds[] = 'IBLOCK_'.$iblockId;
				$excludeEntityIds[] = 'IBLOCK_'.$iblockId.'_SECTION';
			}

			// include iblocks
			$includeEntityIds = array();
			foreach ($includeIblockIds as $iblockId)
			{
				$includeEntityIds[] = 'IBLOCK_'.$iblockId;
				$includeEntityIds[] = 'IBLOCK_'.$iblockId.'_SECTION';
			}

			$filter = array(
				'ENTITY_ID'     => 'IBLOCK_%',
				'=USER_TYPE_ID' => array(
					\CUserTypeFile::USER_TYPE_ID,
					\Bitrix\Disk\Uf\FileUserType::USER_TYPE_ID,
					\Bitrix\Disk\Uf\VersionUserType::USER_TYPE_ID,
				),
			);
			$userFieldList = \Bitrix\Main\UserFieldTable::getList(array('filter' => $filter));
			if ($userFieldList->getSelectedRowsCount() > 0)
			{
				foreach ($userFieldList as $userField)
				{
					$entityName = $userField['ENTITY_ID'];
					if (isset($entityList[$entityName])) continue;

					// exclude
					if (count($excludeIblockIds) > 0 && in_array($entityName, $excludeIblockIds)) continue;
					// include
					if (count($includeEntityIds) > 0 && !in_array($entityName, $includeEntityIds)) continue;

					/** @var \Bitrix\Main\Entity\Base $ent */
					$ent = \Bitrix\Main\Entity\Base::compileEntity($entityName, array(), array(
						'namespace' => __NAMESPACE__,
						'uf_id'     => $entityName,
					));

					$entityList[$entityName] = $ent->getDataClass();
				}
			}
		}

		return $entityList;
	}
}
