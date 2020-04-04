<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM;

interface IVolumeIndicator
{
	/**
	 * The fully qualified name of this class.
	 * @return string
	 */
	public static function className();

	/**
	 * The short indicator name of this class.
	 * @return string
	 */
	public static function getIndicatorId();

	/**
	 * Runs measure test.
	 * @return self
	 */
	public function measure();

	/**
	 * Runs measure test for tables.
	 * @return self
	 */
	public function measureEntity();

	/**
	 * Runs measure test for files.
	 * @return self
	 */
	public function measureFiles();

	/**
	 * Deletes objects selecting by filter.
	 * @return self
	 */
	public function purify();

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return integer[]
	 */
	public function loadTotals();

	/**
	 * Returns total volume size.
	 * @return integer
	 */
	public function getTotalSize();

	/**
	 * Returns total volume size of tables.
	 * @return integer
	 */
	public function getEntitySize();

	/**
	 * Returns total count of entities corresponding to indicator.
	 * @return integer
	 */
	public function getEntityCount();

	/**
	 * Returns total volume size of files.
	 * @return integer
	 */
	public function getFileSize();

	/**
	 * Returns total amount of files corresponding to indicator.
	 * @return integer
	 */
	public function getFileCount();

	/**
	 * Returns total volume size of files on disk.
	 * @return integer
	 */
	public function getDiskSize();

	/**
	 * Returns total amount of files on disk.
	 * @return integer
	 */
	public function getDiskCount();

	/**
	 * Returns total volume size of activities and associated files.
	 * @return integer
	 */
	public function getActivitySize();

	/**
	 * Returns total amount of activities and associated files.
	 * @return integer
	 */
	public function getActivityCount();

	/**
	 * Returns total volume size of events and associated files.
	 * @return integer
	 */
	public function getEventSize();

	/**
	 * Returns total amount of events and associated files.
	 * @return integer
	 */
	public function getEventCount();

	/**
	 * Returns title of the entity object.
	 * @return string
	 */
	public function getTitle();

	/**
	 * Returns entity list.
	 * @return string[]
	 */
	public static function getEntityList();

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList();

	/**
	 * Returns special folder list.
	 * @return \Bitrix\Disk\Folder[]|null
	 */
	public function getSpecialFolderList();

	/**
	 * Returns entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getDiskConnector($entityClass);

	/**
	 * Returns Socialnetwork log entity list attached to disk object.
	 * @param string $entityClass Class name of entity.
	 * @return string|null
	 */
	public static function getLiveFeedConnector($entityClass);

	/**
	 * Returns list of user fields corresponding to entity.
	 * @param string $entityClass Class name of entity.
	 * @return array
	 */
	public function getUserTypeFieldList($entityClass);

	/**
	 * Can filter applied to the indicator.
	 * @return boolean
	 */
	public function canBeFiltered();

	/**
	 * Sets filter parameters.
	 * @param string $key Parameter name to filter.
	 * @param string|string[] $value Parameter value.
	 * @return $this
	 */
	public function addFilter($key, $value);

	/**
	 * Replace filter parameters.
	 * @param array $filter Filter key = value pair.
	 * @return $this
	 */
	public function setFilter(array $filter);

	/**
	 * Gets filter parameter by key.
	 * @param string $key Parameter name to filter.
	 * @param mixed|null $defaultValue Default value.
	 * @return mixed|null
	 */
	public function getFilterValue($key, $defaultValue = null);

	/**
	 * Gets filter parameters.
	 * @param string[] $defaultFilter Default filter set.
	 * @return array
	 */
	public function getFilter(array $defaultFilter = array());

	/**
	 * Returns query.
	 * @return ORM\Query\Query
	 */
	public function prepareQuery();

	/**
	 * Setups filter params into query.
	 * @param ORM\Query\Query $query Query.
	 * @return boolean
	 */
	public function prepareFilter(ORM\Query\Query $query);

	/**
	 * Gets owner id.
	 * @return int
	 */
	public function getOwner();

	/**
	 * Sets owner id.
	 * @param int $ownerId User id.
	 * @return void
	 */
	public function setOwner($ownerId);

	/**
	 * Component action list for measure process.
	 * @param array $componentCommandAlias Command alias.
	 * @return array
	 */
	public function getActionList($componentCommandAlias);

	/**
	 * Tells that is is participated in the total volume.
	 * @return boolean
	 */
	public function isParticipatedTotal();
}
