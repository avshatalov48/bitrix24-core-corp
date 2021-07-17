<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

final class TrackedObjectManager
{
	/** @var  ErrorCollection */
	protected $errorCollection;
	protected $dataToInsert = [];


	/**
	 * Constructor RecentlyUsedManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		Application::getInstance()->addBackgroundJob([$this, 'finalize']);
	}

	public function finalize(): void
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		foreach ($this->dataToInsert as $item)
		{
			[
				'userId' => $userId,
				'object' => $object,
				'attachedObject' => $attachedObject,
				'canRead' => $canRead,
			] = $item;

			if (!($object instanceof File))
			{
				continue;
			}

			if ($canRead || $rightsManager->hasSimpleRight($userId, $object->getId()))
			{
				$this->processPush($userId, $object, $attachedObject);
			}
		}
	}

	/**
	 * @param int|Disk\Internals\Model $object Object id.
	 * @return null|Disk\Internals\Model
	 */
	private static function resolveObject($object)
	{
		if ($object instanceof Disk\Internals\Model)
		{
			return $object;
		}
		$object = (int)$object;
		if ($object > 0)
		{
			return Disk\Internals\Model::getById($object);
		}
		return null;
	}

	/**
	 * Push in recently used new object.
	 * @param mixed|int|User|\CAllUser $user User.
	 * @param Disk\File $object Object id.
	 * @return void
	 */
	public function pushFile($user, File $object, bool $canRead = null): void
	{
		$userId = User::resolveUserId($user);
		if ($userId === null)
		{
			return;
		}

		$this->dataToInsert[] = [
			'userId' => (int)$userId,
			'object' => $object,
			'attachedObject' => null,
			'canRead' => $canRead,
		];
	}

	public function pushAttachedObject($user, AttachedObject $attachedObject, bool $canRead = null): void
	{
		$userId = User::resolveUserId($user);
		if ($userId === null)
		{
			return;
		}

		$this->dataToInsert[] = [
			'userId' => (int)$userId,
			'object' => $attachedObject->getFile(),
			'attachedObject' => $attachedObject,
			'canRead' => $canRead,
		];
	}

	protected function processPush($user, $object, AttachedObject $attachedObject = null): bool
	{
		if (
			!($object = self::resolveObject($object))
			||
			!Disk\Document\DocumentHandler::isEditable(
				mb_substr(
					$object->getName(),
					mb_strrpos($object->getName(), '.')
				)
			)
			||
			!($userId = User::resolveUserId($user))
		)
		{
			return false;
		}

		$objectId = $object->getId();
		$realObjectId = $objectId;
		if ($object instanceof Disk\FileLink &&
			method_exists($object, 'getRealObjectId'))
		{
			$realObjectId = $object->getRealObjectId();
		}

		$fields = [
			'USER_ID' => $userId,
			'OBJECT_ID' => $objectId,
		];

		if (Disk\Internals\TrackedObjectTable::getList([
				'select' => ['ID'],
				'filter' => $fields,
			])->fetch()
		)
		{
			Disk\Internals\TrackedObjectTable::updateBatch(
				['UPDATE_TIME' => new DateTime()],
				$fields
			);
		}
		else
		{
			Disk\Internals\TrackedObjectTable::add(
				$fields + [
					'REAL_OBJECT_ID' => $realObjectId,
					'ATTACHED_OBJECT_ID' => $attachedObject? $attachedObject->getId() : null,
				]
			);
		}

		return true;
	}

	public function refresh(Disk\Internals\Model $object): void
	{
		if ($object = self::resolveObject($object))
		{
			$realObjectId = $object->getId();
			if ($object instanceof Disk\FileLink &&
				method_exists($object, 'getRealObjectId'))
			{
				$realObjectId = $object->getRealObjectId();
			}
			Disk\Internals\TrackedObjectTable::updateBatch(
				['UPDATE_TIME' => new DateTime()],
				['REAL_OBJECT_ID' => $realObjectId]
			);
		}
	}
}