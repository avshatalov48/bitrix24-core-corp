<?php

namespace Bitrix\Disk\Volume;
use \Bitrix\Disk\Volume;

interface IVolumeIndicator
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect.
	 * @return self
	 */
	public function measure($collectData = array());

	/**
	 * Recalculates percent from total file size per row selected by filter.
	 * @param string|Volume\IVolumeIndicator $totalSizeIndicator Use this indicator as total volume.
	 * @param string|Volume\IVolumeIndicator $excludeSizeIndicator Exclude indicator's volume from total volume.
	 * @return self
	 */
	public function recalculatePercent($totalSizeIndicator, $excludeSizeIndicator);

	/**
	 * Loads file list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingFileList($additionalFilter = array());

	/**
	 * Loads folder list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingFolderList($additionalFilter = array());

	/**
	 * Loads file version list corresponding to indicator's filter.
	 * @param array $additionalFilter Additional parameters to filter file list.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCorrespondingUnnecessaryVersionList($additionalFilter = array());

	/**
	 * Preforms data preparation.
	 * @return self
	 */
	public function prepareData();

	/**
	 * @return string The fully qualified name of this class.
	 */
	public static function className();

	/**
	 * @return string The short indicator name of this class.
	 */
	public static function getIndicatorId();

	/**
	 * Deletes objects selecting by filter.
	 * @return self
	 */
	public function purify();

	/**
	 * Returns calculation result set.
	 * @param array $collectedData List types of collected data to return.
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getMeasurementResult($collectedData = array());

	/**
	 * Unset calculated values.
	 * @return self
	 */
	public function resetMeasurementResult();

	/**
	 * Tells true if total result is available.
	 * @return boolean
	 */
	public function isResultAvailable();

	/**
	 * Returns total volume size of objects selecting by filter.
	 * @return integer
	 */
	public function getTotalSize();

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return integer
	 */
	public function getTotalCount();

	/**
	 * Returns total volume size of objects on disk.
	 * @return integer
	 */
	public function getDiskSize();

	/**
	 * Returns total amount of objects on disk.
	 * @return integer
	 */
	public function getDiskCount();

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return integer
	 */
	public function getTotalVersion();

	/**
	 * Returns total amount of attached objects selecting by filter.
	 * @return integer
	 */
	public function getTotalAttached();

	/**
	 * Returns total volume size of preview files.
	 * @return double
	 */
	public function getPreviewSize();

	/**
	 * Returns total amount of preview files.
	 * @return double
	 */
	public function getPreviewCount();

	/**
	 * Returns total amount of external links to objects selecting by filter.
	 * @return integer
	 */
	public function getTotalLink();

	/**
	 * Returns total number sharing of objects selecting by filter.
	 * @return integer
	 */
	public function getTotalSharing();

	/**
	 * Returns total amount of files without links and attached object.
	 * @return integer
	 */
	public function getUnnecessaryVersionSize();

	/**
	 * Returns total count of files without links and attached object.
	 * @return integer
	 */
	public function getUnnecessaryVersionCount();

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return integer[]
	 */
	public function loadTotals();

	/**
	 * Finds entity object by filter.
	 * @param string[] $filter Array filter set to find entity object.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter);

	/**
	 * Returns title of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return string
	 */
	public static function getTitle(Volume\Fragment $fragment);

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime
	 */
	public static function getUpdateTime(Volume\Fragment $fragment);

	/**
	 * Sets filter parameters.
	 * @param string $key Parameter name to filter.
	 * @param string $value Parameter value.
	 * @return $this
	 */
	public function addFilter($key, $value);

	/**
	 * Gets filter parameters.
	 * @param string[] $defaultFilter Default filter set.
	 * @return array
	 */
	public function getFilter(array $defaultFilter = array());

	/**
	 * Gets filter parameter by key.
	 *
	 * @param string $key Parameter name to filter.
	 * @param string $acceptedListModificators List of accepted filter modificator. Defaults are '=<>!@%'.
	 *
	 * @return mixed|null
	 */
	public function getFilterValue($key, $acceptedListModificators = '=<>!@%');

	/**
	 * Clear filter parameters.
	 * @param string $key Parameter name to unset.
	 * @return $this
	 */
	public function unsetFilter($key = '');

	/**
	 * Restores filter state from saved $measurement result.
	 * @param int|array $measurementResult The id of result row or row from table.
	 * @return $this
	 */
	public function restoreFilter($measurementResult);

	/**
	 * Sets filter id.
	 * @param int $filterId Stored filter id.
	 * @return void
	 */
	public function setFilterId($filterId);

	/**
	 * Gets stored filter id.
	 * @return int
	 */
	public function getFilterId();

	/**
	 * Sets select field.
	 * @param string $alias Parameter alias.
	 * @param string $statement Parameter value.
	 * @return $this
	 */
	public function addSelect($alias, $statement);

	/**
	 * Gets select fields.
	 * @return array
	 */
	public function getSelect();

	/**
	 * Sets sort order parameters.
	 * @param string[] $order Sort order parameters and directions.
	 * @return $this
	 */
	public function setOrder($order);

	/**
	 * Gets sort order parameters
	 * @return array
	 */
	public function getOrder();

	/**
	 * Sets limit result rows count.
	 * @param int $limit Limit value.
	 * @return $this
	 */
	public function setLimit($limit);

	/**
	 * Gets limit result rows count.
	 * @return int
	 */
	public function getLimit();

	/**
	 * Sets offset in result.
	 * @param int $offset Offset value.
	 * @return $this
	 */
	public function setOffset($offset);

	/**
	 * Gets offset in result.
	 * @return int
	 */
	public function getOffset();

	/**
	 * Gets owner id.
	 * @return int
	 */
	public function getOwner();

	/**
	 * Sets owner id.
	 * @param int $ownerId User id.
	 * @return $this
	 */
	public function setOwner($ownerId);
}
