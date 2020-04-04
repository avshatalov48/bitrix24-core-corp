<?php


namespace Bitrix\Disk;


final class Right extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var int */
	protected $taskId;
	/** @var string */
	protected $accessCode;
	/** @var string */
	protected $domain;
	/** @var int */
	protected $negative;

	/**
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

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
	 * @return int
	 */
	public function getTaskId()
	{
		return $this->taskId;
	}

	/**
	 * @return bool
	 */
	public function isNegative()
	{
		return (bool)$this->negative;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'TASK_ID' => 'taskId',
			'ACCESS_CODE' => 'accessCode',
			'DOMAIN' => 'domain',
			'NEGATIVE' => 'negative',
		);
	}

} 