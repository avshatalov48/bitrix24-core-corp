<?php

namespace Bitrix\Disk\Security;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;

abstract class SecurityContext implements IErrorable
{
	const GUEST_USER = 'guest_user';

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var  int */
	protected $userId;

	/**
	 * Creates new instance of SecurityContext specific by user.
	 * @param \CUser|int $user User.
	 */
	public function __construct($user)
	{
		$this->userId = static::GUEST_USER;

		if ($user instanceof \CUser)
		{
			if($user->isAuthorized())
			{
				$this->userId = $user->getId();
			}
		}
		elseif((int)$user > 0)
		{
			$this->userId = (int)$user;
		}

		$this->errorCollection = new ErrorCollection;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param $targetId
	 * @return bool
	 */
	abstract public function canAdd($targetId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canChangeRights($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canChangeSettings($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canCreateWorkflow($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canDelete($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canMarkDeleted($objectId);

	/**
	 * @param $objectId
	 * @param $targetId
	 * @return bool
	 */
	abstract public function canMove($objectId, $targetId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canRead($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canRename($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canRestore($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canShare($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canUpdate($objectId);

	/**
	 * @param $objectId
	 * @return bool
	 */
	abstract public function canStartBizProc($objectId);

	/**
	 * @param $columnObjectId
	 * @return string
	 */
	abstract public function getSqlExpressionForList($columnObjectId, $columnCreatedBy);

	/**
	 * Load operations if we show one level with objects.
	 * @param $parentObjectId
	 * @return void
	 */
	public function preloadOperationsForChildren($parentObjectId)
	{
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}