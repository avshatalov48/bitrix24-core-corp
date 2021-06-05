<?php

namespace Bitrix\Disk\Document\Contract;

interface CloudImportInterface
{
	/**
	 * Public name storage of documents. May show in user interface.
	 * @return string
	 */
	public static function getStorageName();

	/**
	 * Lists folder contents
	 * @param $path
	 * @param $folderId
	 * @return mixed
	 */
	public function listFolder($path, $folderId);
}