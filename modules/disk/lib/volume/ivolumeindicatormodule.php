<?php

namespace Bitrix\Disk\Volume;
use \Bitrix\Disk\Volume;

interface IVolumeIndicatorModule
{
	/**
	 * Returns true if module installed and available to measure.
	 * @return boolean
	 */
	public function isMeasureAvailable();

	/**
	 * Returns storage corresponding to module.
	 * @return \Bitrix\Disk\Storage[]|array
	 */
	public function getStorageList();

	/**
	 * Returns folder list corresponding to module.
	 * @param \Bitrix\Disk\Storage $storage Module's storage.
	 * @return \Bitrix\Disk\Folder[]|array
	 */
	public function getFolderList($storage);

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode();

	/**
	 * Returns special folder xml_id code list.
	 * @return string[]
	 */
	public static function getSpecialFolderXmlId();

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType();

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList();

	/**
	 * Returns list of user fields corresponding to entity.
	 * @param string $entityClass Class name of entity.
	 * @return array
	 */
	public function getUserTypeFieldList($entityClass);

	/**
	 * Returns iblock list corresponding to module.
	 * @return array
	 */
	public function getIblockList();

	/**
	 * Returns entity list attached to disk object corresponding to module.
	 * @return array
	 */
	public function getAttachedEntityList();

	/**
	 * Returns entity specific corresponding to module.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return array
	 */
	public static function getSpecific(Volume\Fragment $fragment);

	/**
	 * Returns module identifier.
	 * @return string
	 */
	public static function getModuleId();
}
