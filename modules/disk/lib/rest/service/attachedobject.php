<?php


namespace Bitrix\Disk\Rest\Service;


use Bitrix\Disk;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;

final class AttachedObject extends Base
{
	/**
	 * Returns AttachedObject by id.
	 * @param int $id Id of storage.
	 * @return Disk\AttachedObject
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function get($id)
	{
		$attachedObject = $this->getAttachedObjectById($id);
		if(!$attachedObject->canRead($this->userId))
		{
			throw new AccessException;
		}

		return $attachedObject;
	}

	private function getAttachedObjectById($id)
	{
		$attachedObject = Disk\AttachedObject::getById($id, array('OBJECT'));
		if(!$attachedObject || !$attachedObject->getFile())
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $attachedObject;
	}
}