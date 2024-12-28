<?php

namespace Bitrix\DocumentGenerator\Integration\Disk;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\DocumentGenerator\Storage\Disk;
use Bitrix\DocumentGenerator\UserPermissions;

final class SecurityContext extends \Bitrix\Disk\Security\SecurityContext
{
	private UserPermissions $userPermissions;

	private array $canReadCache = [];

	public function __construct($user)
	{
		parent::__construct($user);

		$userId = $this->getUserId();
		if ($userId === self::GUEST_USER)
		{
			$userId = 0;
		}

		$this->userPermissions = Driver::getInstance()->getUserPermissions($userId);
	}

	/**
	 * @inheritDoc
	 */
	public function canAdd($targetId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canChangeRights($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canChangeSettings($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canCreateWorkflow($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canDelete($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canMarkDeleted($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canMove($objectId, $targetId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canRead($objectId): bool
	{
		if (isset($this->canReadCache[$objectId]))
		{
			return $this->canReadCache[$objectId];
		}

		$fileId = FileTable::query()
			->setSelect(['ID'])
			->where('STORAGE_TYPE', Disk::class)
			->where('STORAGE_WHERE', $objectId)
			->setLimit(1)
			->fetchObject()
			?->getId()
		;

		if ($fileId === null)
		{
			$this->canReadCache[$objectId] = false;

			return false;
		}

		$canReadFile = $this->canReadFile($fileId);

		$this->canReadCache[$objectId] = $canReadFile;

		return $canReadFile;
	}

	private function canReadFile(int $fileId): bool
	{
		// document should be the most common (if not single) case
		if ($this->isDocument($fileId))
		{
			return $this->userPermissions->canViewDocuments();
		}

		$templateId = $this->getTemplateId($fileId);
		if ($templateId !== null)
		{
			return $this->userPermissions->canModifyTemplate($templateId);
		}

		return false;
	}

	private function isDocument(int $fileId): bool
	{
		return (bool)DocumentTable::query()
			->setSelect(['ID'])
			->where(
				DocumentTable::query()::filter()
					->logic('or')
					->where('FILE_ID', $fileId)
					->where('PDF_ID', $fileId)
					->where('IMAGE_ID', $fileId)
			)
			->setLimit(1)
			->fetch()
		;
	}

	private function getTemplateId(int $fileId): ?int
	{
		return TemplateTable::query()
			->setSelect(['ID'])
			->where('FILE_ID', $fileId)
			->setLimit(1)
			->fetchObject()
			?->getId()
		;
	}

	/**
	 * @inheritDoc
	 */
	public function canRename($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canRestore($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canShare($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canUpdate($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canStartBizProc($objectId): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getSqlExpressionForList($columnObjectId, $columnCreatedBy): string
	{
		return '1=0';
	}
}
