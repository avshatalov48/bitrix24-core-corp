<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk;
use Bitrix\Disk\Volume;


interface IVolumeIndicatorModule
{
	/**
	 * Returns true if module installed and available to measure.
	 * @return boolean
	 */
	public function isMeasureAvailable(): bool;

	/**
	 * Returns storage corresponding to module.
	 * @return Disk\Storage[]|array
	 */
	public function getStorageList(): array;

	/**
	 * Returns folder list corresponding to module.
	 * @param Disk\Storage $storage Module's storage.
	 * @return Disk\Folder[]|array
	 */
	public function getFolderList($storage): array;

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode(): array;

	/**
	 * Returns special folder xml_id code list.
	 * @return string[]
	 */
	public static function getSpecialFolderXmlId(): array;

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array;

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList(): array;

	/**
	 * Returns list of user fields corresponding to entity.
	 * @param string $entityClass Class name of entity.
	 * @return array
	 */
	public function getUserTypeFieldList(string $entityClass): array;

	/**
	 * Returns iblock list corresponding to module.
	 * @return array
	 */
	public function getIblockList(): array;

	/**
	 * Returns entity list attached to disk object corresponding to module.
	 * @return string[]
	 */
	public function getAttachedEntityList(): array;

	/**
	 * Returns entity specific corresponding to module.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return array
	 */
	public static function getSpecific(Volume\Fragment $fragment): array;

	/**
	 * Returns module identifier.
	 * @return string
	 */
	public static function getModuleId(): string;
}
