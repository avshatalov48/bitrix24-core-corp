<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\Crm\Service\Container;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\UploaderController;
use Bitrix\Crm\Service\UserPermissions;

final class MailUploaderController extends UploaderController
{
	protected UserPermissions $userPermissions;

	public function __construct(array $options)
	{
		[
			'ownerId' => $entityId,
			'ownerType' => $entityType,
		] = $options;

		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);

		parent::__construct([
			'entityTypeId' => $entityTypeId,
			'entityId' => (int) $entityId,
		]);

		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function isAvailable(): bool
	{
		[
			'entityTypeId' => $entityTypeId,
			'entityId' => $entityId,
		] = $this->getOptions();

		return $this->userPermissions->checkReadPermissions($entityTypeId, $entityId);
	}

	public function getConfiguration(): \Bitrix\UI\FileUploader\Configuration
	{
		$configuration = new Configuration();

		if(\Bitrix\Main\Loader::includeModule('mail'))
		{
			$maxSize = \Bitrix\Mail\Helper\Message::getMaxAttachedFilesSizeAfterEncoding();
			if ($maxSize > 0)
			{
				$configuration->setMaxFileSize($maxSize);
			}
		}

		return $configuration;
	}

	public function canUpload(): bool
	{
		[
			'entityTypeId' => $entityTypeId,
			'entityId' => $entityId,
		] = $this->getOptions();

		if ($entityId)
		{
			return $this->userPermissions->checkUpdatePermissions($entityTypeId, (int) $entityId);
		}

		return $this->userPermissions->checkAddPermissions($entityTypeId);
	}

	public function canView(): bool
	{
		['entityTypeId' => $entityTypeId, 'entityId' => $entityId] = $this->getOptions();

		return $this->userPermissions->checkReadPermissions($entityTypeId, (int) $entityId);
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
	}

	public function canRemove(): bool
	{
		return false;
	}
}