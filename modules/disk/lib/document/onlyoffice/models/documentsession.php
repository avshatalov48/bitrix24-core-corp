<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Model;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\User;
use Bitrix\Disk\Version;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;

final class DocumentSession extends Model
{
	public const TYPE_VIEW = DocumentSessionTable::TYPE_VIEW;
	public const TYPE_EDIT = DocumentSessionTable::TYPE_EDIT;

	/** @var int */
	protected $id;
	/** @var int */
	protected $objectId;
	/** @var File */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var int */
	protected $userId;
	/** @var User */
	protected $user;
	/** @var int */
	protected $ownerId;
	/** @var User */
	protected $owner;
	/** @var bool */
	protected $isExclusive;
	/** @var string */
	protected $externalHash;
	/** @var DateTime */
	protected $createTime;
	/** @var int */
	protected $type;
	/** @var string */
	protected $context;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @return string
	 */
	public static function getTableClassName()
	{
		return DocumentSessionTable::class;
	}

	/**
	 * @return int
	 */
	public function getObjectId(): ?int
	{
		return $this->objectId;
	}

	public function getObject(): ?File
	{
		if ($this->object && $this->isLoadedAttribute('object') && $this->objectId == $this->object->getId())
		{
			return $this->object;
		}

		$this->object = File::loadById($this->objectId);
		$this->setAsLoadedAttribute('object');

		return $this->object;
	}

	/**
	 * @return int
	 */
	public function getVersionId(): ?int
	{
		return $this->versionId;
	}

	public function getVersion(): ?Version
	{
		if ($this->version && $this->isLoadedAttribute('version') && $this->versionId == $this->version->getId())
		{
			return $this->version;
		}

		$this->version = Version::loadById($this->versionId);
		$this->setAsLoadedAttribute('version');

		return $this->version;
	}

	public function isVersion(): bool
	{
		return (bool)$this->getVersionId();
	}

	public function getFilename(): string
	{
		if ($this->isVersion())
		{
			$version = $this->getVersion();

			return $version ? $version->getName() : '';
		}

		$file = $this->getObject();

		return $file ? $file->getName() : '';
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @return \Bitrix\Disk\EmptyUser|\Bitrix\Disk\SystemUser|User
	 */
	public function getUser(): ?User
	{
		if ($this->user && $this->isLoadedAttribute('user') && $this->userId == $this->user->getId())
		{
			return $this->user;
		}

		$this->user = User::getModelForReferenceField($this->userId, $this->user);
		$this->setAsLoadedAttribute('user');

		return $this->user;
	}

	/**
	 * @return int
	 */
	public function getOwnerId(): int
	{
		return $this->ownerId;
	}

	/**
	 * @return \Bitrix\Disk\EmptyUser|\Bitrix\Disk\SystemUser|User
	 */
	public function getOwner(): ?User
	{
		if ($this->owner && $this->isLoadedAttribute('owner') && $this->ownerId == $this->owner->getId())
		{
			return $this->owner;
		}

		$this->owner = User::getModelForReferenceField($this->ownerId, $this->owner);
		$this->setAsLoadedAttribute('owner');

		return $this->owner;
	}

	/**
	 * @return bool
	 */
	public function isExclusive(): bool
	{
		return $this->isExclusive;
	}

	/**
	 * @return string
	 */
	public function getExternalHash(): string
	{
		return $this->externalHash;
	}

	/**
	 * @return DateTime
	 */
	public function getCreateTime(): DateTime
	{
		return $this->createTime;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return (int)$this->type;
	}

	public function getContextRaw(): ?string
	{
		return $this->context;
	}

	public function getContext(): ?DocumentSessionContext
	{
		return DocumentSessionContext::buildFromJson($this->getContextRaw());
	}

	public function isView(): bool
	{
		return $this->getType() === self::TYPE_VIEW;
	}

	public function isEdit(): bool
	{
		return $this->getType() === self::TYPE_EDIT;
	}

	public function forkForUser(int $userId, ?DocumentSessionContext $context = null): ?self
	{
		return self::add([
			'EXTERNAL_HASH' => $this->getExternalHash(),
			'OBJECT_ID' => $this->getObjectId(),
			'VERSION_ID' => $this->getVersionId(),
			'USER_ID' => $userId,
			'OWNER_ID' => $this->getOwnerId(),
			'IS_EXCLUSIVE' => $this->isExclusive(),
			'TYPE' => $this->getType(),
			'CONTEXT' => $context? $context->toJson() : $this->getContextRaw(),
		], $this->errorCollection);
	}

	public function createEditSession(): ?self
	{
		$currentEditSession = self::load([
			'OBJECT_ID' => $this->getObjectId(),
			'VERSION_ID' => $this->getVersionId(),
			'TYPE' => self::TYPE_EDIT,
		]);

		if ($currentEditSession && $currentEditSession->getUserId() == $this->getUserId())
		{
			return $currentEditSession;
		}

		if ($currentEditSession)
		{
			return $currentEditSession->forkForUser($this->getUserId(), $this->getContext());
		}

		if ($this->isSingleUsageOfExternalHash())
		{
			return $this->transformToEdit()? $this : null;
		}

		return self::add([
			'OBJECT_ID' => $this->getObjectId(),
			'USER_ID' => $this->getUserId(),
			'OWNER_ID' => $this->getUserId(),
			'IS_EXCLUSIVE' => $this->isExclusive(),
			'VERSION_ID' => $this->getVersionId(),
			'TYPE' => self::TYPE_EDIT,
			'CONTEXT' => $this->getContextRaw(),
		], $this->errorCollection);
	}

	public function transformToEdit(): bool
	{
		return $this->update([
			'TYPE' => self::TYPE_EDIT,
		]);
	}

	public function canTransformToEdit(SecurityContext $securityContext): bool
	{
		$context = $this->getContext();
		if (!$context)
		{
			return $securityContext->canUpdate($this->getObjectId());
		}

		if ($context->getAttachedObject() && $context->getAttachedObject()->canUpdate($securityContext->getUserId()))
		{
			return true;
		}

		return $securityContext->canUpdate($this->getObjectId());
	}

	public function canTransformUserToEdit(CurrentUser $user): bool
	{
		$file = $this->getObject();
		if (!$file)
		{
			return false;
		}

		$storage = $file->getStorage();
		if (!$storage)
		{
			return false;
		}

		$securityContext = $storage->getSecurityContext($user);

		return $this->canTransformToEdit($securityContext);
	}

	protected function isSingleUsageOfExternalHash(): bool
	{
		return DocumentSessionTable::getCount([
			'=EXTERNAL_HASH' => $this->getExternalHash(),
			'=OBJECT_ID' => $this->getObjectId(),
		]) <= 1;
	}

	public function delete(): bool
	{
		return $this->deleteInternal();
	}

	public static function getMapAttributes()
	{
		return [
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'VERSION_ID' => 'versionId',
			'USER_ID' => 'userId',
			'OWNER_ID' => 'ownerId',
			'IS_EXCLUSIVE' => 'isExclusive',
			'EXTERNAL_HASH' => 'externalHash',
			'CREATE_TIME' => 'createTime',
			'TYPE' => 'type',
			'CONTEXT' => 'context',
		];
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 *
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$fields = User::getFieldsForSelect();

		return [
			'OBJECT' => File::class,
			'VERSION' => Version::class,
			'USER' => [
				'class' => User::class,
				'select' => $fields,
			],
			'OWNER' => [
				'class' => User::class,
				'select' => $fields,
			],
		];
	}
}