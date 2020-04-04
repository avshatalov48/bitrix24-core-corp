<?php


namespace Bitrix\Disk\Rest\Service;


use Bitrix\Disk;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;

final class Version extends Base
{
	/**
	 * Returns version by id.
	 *
	 * @param int $id Id of version.
	 *
	 * @return Disk\Version
	 * @throws AccessException
	 */
	protected function get($id)
	{
		$version = $this->getVersionById($id);
		$file = $version->getObject();

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canRead($securityContext))
		{
			throw new AccessException;
		}

		return $version;
	}

	/**
	 * Sends file content.
	 *
	 * The method is invoked by \CRestUtil::METHOD_DOWNLOAD.
	 *
	 * @param int $id Id of version.
	 *
	 * @throws RestException
	 */
	protected function download($id)
	{
		$version = $this->get($id);

		$fileData = $version->getFile();
		if(!$fileData)
		{
			throw new RestException('Could not get content of version');
		}
		\CFile::viewByUser($fileData, array('force_download' => true, 'cache_time' => 0, 'attachment_name' => $version->getName()));
	}


	private function getVersionById($id)
	{
		$version = Disk\Version::getById($id, array('OBJECT'));
		if(!$version || !$version->getObject())
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $version;
	}
}