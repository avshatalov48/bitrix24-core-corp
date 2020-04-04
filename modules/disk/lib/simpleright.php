<?php


namespace Bitrix\Disk;

/**
 * Class SimpleRight
 * @package Bitrix\Disk
 * @internal
 */
final class SimpleRight extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var string */
	protected $accessCode;

	/**
	 * @return string
	 */
	public function getAccessCode()
	{
		return $this->accessCode;
	}

	/**
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'ACCESS_CODE' => 'accessCode',
		);
	}

} 