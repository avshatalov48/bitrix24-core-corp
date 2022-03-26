<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Entity\Model;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\User;
use Bitrix\Disk\Version;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\DateTime;

/**
 * @method File|null getObject()
 * @method Version|null getVersion()
 * @method DocumentInfo|null getInfo()
 * @method \Bitrix\Disk\EmptyUser|\Bitrix\Disk\SystemUser|User getUser()
 * @method \Bitrix\Disk\EmptyUser|\Bitrix\Disk\SystemUser|User getOwner()
 */
final class DocumentSession extends Model
{
	public const TYPE_VIEW = DocumentSessionTable::TYPE_VIEW;
	public const TYPE_EDIT = DocumentSessionTable::TYPE_EDIT;

	public const STATUS_ACTIVE = DocumentSessionTable::STATUS_ACTIVE;
	public const STATUS_NON_ACTIVE = DocumentSessionTable::STATUS_NON_ACTIVE;

	public const REF_USER = 'user';
	public const REF_OWNER = 'owner';
	public const REF_OBJECT = 'object';
	public const REF_VERSION = 'version';
	public const REF_INFO = 'info';

	/** @var int */
	protected $id;
	/** @var int */
	protected $objectId;
	/** @var int */
	protected $versionId;
	/** @var int */
	protected $userId;
	/** @var int */
	protected $ownerId;
	/** @var bool */
	protected $isExclusive;
	/** @var string */
	protected $externalHash;
	/** @var DateTime */
	protected $createTime;
	/** @var int */
	protected $type;
	/** @var int */
	protected $status;
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

	public function getFile(): ?File
	{
		return $this->getObject();
	}

	/**
	 * @return int
	 */
	public function getVersionId(): ?int
	{
		return $this->versionId;
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

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;
		$this->resetReferenceValue(self::REF_USER);

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	public function belongsToUser(int $userId): bool
	{
		return $userId === $this->getUserId();
	}

	/**
	 * @return int
	 */
	public function getOwnerId(): int
	{
		return $this->ownerId;
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

	public function isOutdatedByFileContent(): bool
	{
		$syncUpdateTime = $this->getObject()->getSyncUpdateTime();
		if (!$syncUpdateTime)
		{
			return false;
		}

		return ($syncUpdateTime->getTimestamp() - $this->getCreateTime()->getTimestamp()) > 0;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	public function createInfo(): DocumentInfo
	{
		$documentInfo = $this->getInfo();
		if ($documentInfo)
		{
			return $documentInfo;
		}

		$result = DocumentSessionTable::tryToAddInfo([
			'EXTERNAL_HASH' => $this->getExternalHash(),
			'OBJECT_ID' => $this->getObjectId(),
			'VERSION_ID'    => $this->getVersionId(),
			'OWNER_ID'  => $this->getOwnerId(),
		]);

		if (!$result)
		{
			throw new ObjectNotFoundException("Could not find or creat info for {$this->getExternalHash()}");
		}

		return DocumentInfo::buildFromResult($result);
	}

	/**
	 * @return int
	 */
	public function getStatus(): int
	{
		return $this->status;
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

	public function isActive(): bool
	{
		return $this->getStatus() === self::STATUS_ACTIVE;
	}

	public function isNonActive(): bool
	{
		return $this->getStatus() === self::STATUS_NON_ACTIVE;
	}

	public function setAsActive(): bool
	{
		return $this->update([
			'STATUS' => self::STATUS_ACTIVE,
		]);
	}

	public function setAsNonActive(): bool
	{
		return $this->update([
			'STATUS' => self::STATUS_NON_ACTIVE,
		]);
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

	public function cloneWithNewHash(int $userId, ?DocumentSessionContext $context = null): ?self
	{
		return self::add([
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
			'STATUS' => self::STATUS_ACTIVE,
		]);

		if ($currentEditSession && $currentEditSession->belongsToUser($this->getUserId()))
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
		return $this->canEdit($securityContext);
	}

	public function canEdit(SecurityContext $securityContext): bool
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

		if ($context->getExternalLink() && $context->getExternalLink()->allowEdit())
		{
			return true;
		}

		return $securityContext->canUpdate($this->getObjectId());
	}

	public function canUserEdit(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canEdit($securityContext);
	}

	public function canTransformUserToEdit(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canTransformToEdit($securityContext);
	}

	public function canRename(SecurityContext $securityContext): bool
	{
		return $securityContext->canRename($this->getObjectId());
	}

	public function canUserRename(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canRename($securityContext);
	}

	protected function getSecurityContext(CurrentUser $user): ?SecurityContext
	{
		$file = $this->getObject();
		if (!$file)
		{
			return null;
		}

		$storage = $file->getStorage();
		if (!$storage)
		{
			return null;
		}

		return $storage->getSecurityContext($user);
	}

	public function canUserShare(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canShare($securityContext);
	}

	public function canShare(SecurityContext $securityContext): bool
	{
		if ($securityContext->canShare($this->getObjectId()))
		{
			return true;
		}

		return false;
	}

	public function canUserChangeRights(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canChangeRights($securityContext);
	}

	public function canChangeRights(SecurityContext $securityContext): bool
	{
		if ($securityContext->canChangeRights($this->getObjectId()))
		{
			return true;
		}

		return false;
	}

	public function canUserRead(CurrentUser $user): bool
	{
		$securityContext = $this->getSecurityContext($user);
		if (!$securityContext)
		{
			return false;
		}

		return $this->canRead($securityContext);
	}

	public function canRead(SecurityContext $securityContext): bool
	{
		$context = $this->getContext();
		if (!$context)
		{
			return $securityContext->canRead($this->getObjectId());
		}

		if ($context->getAttachedObject() && $context->getAttachedObject()->canRead($securityContext->getUserId()))
		{
			return true;
		}

		return $securityContext->canRead($this->getObjectId());
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

	public function countActiveSessions(): int
	{
		$countActiveSessions = DocumentSessionTable::getCount([
			'=EXTERNAL_HASH' => $this->getExternalHash(),
			'=STATUS' => DocumentSessionTable::STATUS_ACTIVE,
		]);

		return $countActiveSessions;
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
			'STATUS' => 'status',
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
			self::REF_USER => [
				'class' => User::class,
				'select' => $fields,
				'load' => function(self $documentSession){
					return User::loadById($documentSession->getUserId());
				},
			],
			self::REF_OWNER => [
				'class' => User::class,
				'select' => $fields,
				'load' => function(self $documentSession){
					return User::loadById($documentSession->getOwnerId());
				},
			],
			self::REF_OBJECT => [
				'class' => File::class,
				'load' => function(self $documentSession){
					return File::loadById($documentSession->getObjectId());
				},
			],
			self::REF_VERSION => [
				'class' => Version::class,
				'load' => function(self $documentSession){
					return Version::loadById($documentSession->getVersionId());
				},
			],
			self::REF_INFO => [
				'class' => DocumentInfo::class,
				'load' => function(self $documentSession){
					return DocumentInfo::loadById($documentSession->getExternalHash());
				},
			],
		];
	}
}