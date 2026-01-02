<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentException;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\UploaderController;

abstract class EntityController extends UploaderController
{
	protected UserPermissions $userPermissions;

	/**
	 * @param array{
	 *     entityTypeId: int,
	 *     entityId: ?int,
	 *     categoryId: ?int,
	 * } $options
	 * @throws ArgumentException
	 */
	public function __construct(array $options)
	{
		$options['entityTypeId'] ??= \CCrmOwnerType::Undefined;
		$options['entityTypeId'] = (int)$options['entityTypeId'];

		if (empty($options['entityTypeId']) || !\CCrmOwnerType::IsDefined($options['entityTypeId']))
		{
			throw new ArgumentException('Parameter "entityTypeId" must be defined in options.');
		}

		$factory = Container::getInstance()->getFactory($options['entityTypeId']);
		if (!$factory)
		{
			throw new ArgumentException("Entity type {{$options['entityTypeId']}} is not supported.");
		}

		$options['entityId'] ??= 0;
		$options['entityId'] = (int)$options['entityId'];

		$options['categoryId'] ??= null;
		if ($options['categoryId'] !== null)
		{
			$options['categoryId'] = (int)$options['categoryId'];
		}

		parent::__construct($options);

		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function isAvailable(): bool
	{
		[
			'entityTypeId' => $entityTypeId,
			'entityId' => $entityId,
			'categoryId' => $categoryId,
		] = $this->getOptions();

		if ($entityId <= 0)
		{
			return is_null($categoryId)
				? $this->userPermissions->entityType()->canReadItems($entityTypeId)
				: $this->userPermissions->entityType()->canReadItemsInCategory($entityTypeId, $categoryId)
			;
		}

		return $this->userPermissions->item()->canReadItemIdentifier(new ItemIdentifier($entityTypeId, $entityId, $categoryId));
	}

	public function getConfiguration(): Configuration
	{
		return new Configuration();
	}

	public function canUpload(): bool
	{
		[
			'entityTypeId' => $entityTypeId,
			'entityId' => $entityId,
			'categoryId' => $categoryId,
		] = $this->getOptions();

		if ($entityId)
		{
			return $this->userPermissions->item()->canUpdateItemIdentifier(new ItemIdentifier($entityTypeId, $entityId, $categoryId));
		}

		return
			is_null($categoryId)
				? $this->userPermissions->entityType()->canAddItems($entityTypeId)
				: $this->userPermissions->entityType()->canAddItemsInCategory($entityTypeId, $categoryId)
			;
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
	}

	public function canView(): bool
	{
		return false;
	}

	public function canRemove(): bool
	{
		return false;
	}
}
