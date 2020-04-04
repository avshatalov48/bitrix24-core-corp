<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;

class StubConnector extends Connector implements IWorkWithAttachedObject
{
	/** @var \Bitrix\Disk\AttachedObject  */
	protected $attachedObject;

	/**
	 * @inheritdoc
	 */
	public function getDataToShow()
	{
		return array();
	}

	/**
	 * @inheritdoc
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}

	/**
	 * @inheritdoc
	 */
	public function canRead($userId)
	{
		if(!$this->isSetAttachedObject())
		{
			return false;
		}

		$file = $this->getAttachedObject()->getObject();
		$securityContext = $file
			->getStorage()
			->getSecurityContext($userId)
		;

		return $file->canRead($securityContext);
	}

	/**
	 * @inheritdoc
	 */
	public function canUpdate($userId)
	{
		if(!$this->isSetAttachedObject())
		{
			return false;
		}

		$file = $this->getAttachedObject()->getObject();
		$securityContext = $file
			->getStorage()
			->getSecurityContext($userId)
		;

		return $file->canUpdate($securityContext);
	}

	/**
	 * @param AttachedObject $attachedObject
	 * @return $this
	 */
	public function setAttachedObject(AttachedObject $attachedObject)
	{
		$this->attachedObject = $attachedObject;

		return $this;
	}

	/**
	 * @return AttachedObject
	 */
	public function getAttachedObject()
	{
		return $this->attachedObject;
	}

	/**
	 * @return bool
	 */
	public function isSetAttachedObject()
	{
		return isset($this->attachedObject);
	}
}
