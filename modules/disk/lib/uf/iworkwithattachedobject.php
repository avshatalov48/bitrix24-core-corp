<?php


namespace Bitrix\Disk\Uf;


use Bitrix\Disk\AttachedObject;

interface IWorkWithAttachedObject
{
	/**
	 * @param AttachedObject $attachedObject
	 * @return $this
	 */
	public function setAttachedObject(AttachedObject $attachedObject);

	/**
	 * @return AttachedObject
	 */
	public function getAttachedObject();

	/**
	 * @return bool
	 */
	public function isSetAttachedObject();
}